<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\AnalyzeRepositoryJob;
use App\Models\Repository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RepositoryController extends Controller
{
    public function index(Request $request): View
    {
        $user   = $request->user();
        $search = $request->get('search');
        $lang   = $request->get('language');
        $sort   = $request->get('sort', 'stars');
        $order  = $request->get('order', 'desc');

        $visibility = $request->get('visibility'); // public | private
        $origin     = $request->get('origin');     // source | fork
        $state      = $request->get('state');      // active | archived
        $analysis   = $request->get('analysis');   // analyzed | pending | failed
        $highlight  = $request->get('highlight');  // pinned | featured

        $allowedSorts = ['stars', 'forks', 'name', 'updated_at', 'pushed_at', 'open_issues'];
        $sort         = in_array($sort, $allowedSorts, true) ? $sort : 'stars';
        $order        = $order === 'asc' ? 'asc' : 'desc';

        $repositories = Repository::forUser($user)
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $needle = '%' . mb_strtolower($search) . '%';
                $q->whereRaw('lower(name) like ?', [$needle])
                  ->orWhereRaw('lower(description) like ?', [$needle]);
            }))
            ->when($lang, fn ($q) => $q->where('language', $lang))
            ->when($visibility, fn ($q) => $q->where('is_private', $visibility === 'private'))
            ->when($origin, fn ($q) => $q->where('is_fork', $origin === 'fork'))
            ->when($state, fn ($q) => $q->where('is_archived', $state === 'archived'))
            ->when($analysis, function ($q) use ($analysis) {
                match ($analysis) {
                    'analyzed' => $q->whereNotNull('ai_analyzed_at'),
                    'pending'  => $q->whereNull('ai_analyzed_at')
                                    ->whereNotIn('analysis_status', ['processing', 'failed']),
                    'failed'   => $q->where('analysis_status', 'failed'),
                    default    => $q,
                };
            })
            ->when($highlight, fn ($q) => $q->where($highlight === 'pinned' ? 'is_pinned' : 'is_featured', true))
            ->orderBy($sort, $order)
            ->paginate(18)
            ->withQueryString();

        $languages = Repository::forUser($user)
            ->pluck('language')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $data = compact(
            'repositories', 'languages', 'search', 'lang', 'sort', 'order',
            'visibility', 'origin', 'state', 'analysis', 'highlight',
        );

        // Soft-search: the repositories page polls this same route with
        // Accept: application/json and swaps the grid without a full reload.
        if ($request->wantsJson()) {
            return view('repositories.partials.grid', $data);
        }

        return view('repositories.index', $data);
    }

    public function show(Request $request, Repository $repository): View|RedirectResponse
    {
        // Authorize via policy
        $this->authorize('view', $repository);

        $analysis = $repository->ai_analysis;

        $failureMessage = null;
        if ($repository->hasFailed()) {
            $failureMessage = \App\Models\Analysis::query()
                ->where('user_id', $request->user()->id)
                ->where('repository_id', $repository->id)
                ->where('status', 'failed')
                ->latest('updated_at')
                ->value('error_message');
        }

        return view('repositories.show', compact('repository', 'analysis', 'failureMessage'));
    }

    /**
     * Run AI analysis for a repository (dispatched to background queue).
     */
    public function analyze(Request $request, Repository $repository): RedirectResponse
    {
        $this->authorize('analyze', $repository);

        if ($repository->isAnalyzing() && ! $repository->isStaleProcessing()) {
            return back()->with('info', 'Analysis is already in progress.');
        }

        $repository->update([
            'analysis_status'     => 'processing',
            'analysis_started_at' => now(),
        ]);

        AnalyzeRepositoryJob::dispatch($repository, $request->user());

        return redirect()->route('repositories.show', $repository)
            ->with('info', 'AI analysis started. Results will appear shortly.');
    }

    /**
     * Poll the current analysis status for a repository (JSON).
     */
    public function analysisStatus(Request $request, Repository $repository): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $repository);

        $repository->refresh();

        $failureMessage = null;
        if ($repository->hasFailed()) {
            $failureMessage = \App\Models\Analysis::query()
                ->where('user_id', $request->user()->id)
                ->where('repository_id', $repository->id)
                ->where('status', 'failed')
                ->latest('updated_at')
                ->value('error_message');
        }

        return response()->json([
            'status'          => $repository->analysis_status ?? 'pending',
            'is_analyzed'     => $repository->isAnalyzed(),
            'is_analyzing'    => $repository->isAnalyzing(),
            'has_failed'      => $repository->hasFailed(),
            'score'           => $repository->score,
            'failure_message' => $failureMessage,
            'analyzed_at'     => $repository->ai_analyzed_at?->toIso8601String(),
        ]);
    }

    public function togglePin(Request $request, Repository $repository): RedirectResponse
    {
        $this->authorize('update', $repository);

        $repository->update(['is_pinned' => ! $repository->is_pinned]);

        return back()->with('success', $repository->is_pinned ? 'Repository pinned.' : 'Repository unpinned.');
    }

    public function toggleFeature(Request $request, Repository $repository): RedirectResponse
    {
        $this->authorize('update', $repository);

        $repository->update(['is_featured' => ! $repository->is_featured]);

        return back()->with('success', $repository->is_featured ? 'Repository featured.' : 'Repository unfeatured.');
    }

    /**
     * Export repository analysis as JSON.
     */
    public function exportJson(Request $request, Repository $repository): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('view', $repository);

        if (! $repository->isAnalyzed()) {
            abort(404, 'Repository has not been analyzed yet.');
        }

        $analysis = $repository->ai_analysis ?? [];

        $data = [
            'repository' => [
                'name'              => $repository->name,
                'full_name'         => $repository->full_name,
                'description'       => $repository->description,
                'language'          => $repository->language,
                'stars'             => $repository->stars,
                'forks'             => $repository->forks,
                'open_issues'       => $repository->open_issues,
                'watchers'          => $repository->watchers,
                'topics'            => $repository->topics,
                'license'           => $repository->license,
                'html_url'          => $repository->html_url,
                'is_fork'           => $repository->is_fork,
                'is_archived'       => $repository->is_archived,
                'pushed_at'         => $repository->pushed_at?->toDateTimeString(),
                'github_created_at' => $repository->github_created_at?->toDateTimeString(),
            ],
            'analysis' => $analysis,
            'analyzed_at' => $repository->ai_analyzed_at?->toDateTimeString(),
            'exported_at' => now()->toDateTimeString(),
            'exported_by' => $request->user()->name,
        ];

        $filename = str_replace('/', '_', $repository->full_name) . '_analysis_' . now()->format('Y-m-d') . '.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Export repository analysis as Markdown.
     */
    public function exportMarkdown(Request $request, Repository $repository): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('view', $repository);

        if (! $repository->isAnalyzed()) {
            abort(404, 'Repository has not been analyzed yet.');
        }

        $analysis = $repository->ai_analysis ?? [];

        $markdown = "# {$repository->name} — AI Portfolio Analysis\n\n";
        $markdown .= "> Analysis Report generated by GitRadar\n\n";
        $markdown .= "**Repository:** [{$repository->full_name}]({$repository->html_url})\n\n";
        
        if ($repository->description) {
            $markdown .= "**Description:** {$repository->description}\n\n";
        }

        $markdown .= "---\n\n";
        $markdown .= "## 📊 Overview\n\n";
        $markdown .= "| Metric | Value |\n";
        $markdown .= "|--------|-------|\n";
        $markdown .= "| **AI Score** | {$analysis['score']}/100 |\n";
        $markdown .= "| **Difficulty** | " . ucfirst($analysis['difficulty'] ?? 'N/A') . " |\n";
        $markdown .= "| **Portfolio Level** | " . ucfirst($analysis['portfolio_level'] ?? 'N/A') . " |\n";
        $markdown .= "| **Recruiter Rating** | {$analysis['recruiter_rating']}/10 |\n";
        $markdown .= "| **Hiring Probability** | {$analysis['hiring_probability']}% |\n";
        $markdown .= "| **Estimated Experience** | " . ($analysis['estimated_experience'] ?? 'N/A') . " |\n";
        $markdown .= "| **Market Readiness** | " . ucfirst($analysis['market_readiness'] ?? 'N/A') . " |\n\n";

        $markdown .= "| Repository Stats | Value |\n";
        $markdown .= "|-----------------|-------|\n";
        $markdown .= "| **Language** | {$repository->language} |\n";
        $markdown .= "| **Stars** | ⭐ {$repository->stars} |\n";
        $markdown .= "| **Forks** | 🍴 {$repository->forks} |\n";
        $markdown .= "| **Open Issues** | {$repository->open_issues} |\n";
        $markdown .= "| **License** | " . ($repository->license ?? 'None') . " |\n\n";

        if (! empty($analysis['strengths'])) {
            $markdown .= "## ✅ Strengths\n\n";
            foreach ($analysis['strengths'] as $item) {
                $markdown .= "- {$item}\n";
            }
            $markdown .= "\n";
        }

        if (! empty($analysis['weaknesses'])) {
            $markdown .= "## ⚠️ Areas for Improvement\n\n";
            foreach ($analysis['weaknesses'] as $item) {
                $markdown .= "- {$item}\n";
            }
            $markdown .= "\n";
        }

        if (! empty($analysis['recommendations'])) {
            $markdown .= "## 💡 Recommendations\n\n";
            foreach ($analysis['recommendations'] as $item) {
                $markdown .= "- {$item}\n";
            }
            $markdown .= "\n";
        }

        if (! empty($analysis['architecture_review'])) {
            $markdown .= "## 🏗 Architecture Review\n\n";
            foreach ($analysis['architecture_review'] as $item) {
                $markdown .= "- {$item}\n";
            }
            $markdown .= "\n";
        }

        if (! empty($analysis['security_review'])) {
            $markdown .= "## 🔒 Security Review\n\n";
            foreach ($analysis['security_review'] as $item) {
                $markdown .= "- {$item}\n";
            }
            $markdown .= "\n";
        }

        if (! empty($analysis['performance_review'])) {
            $markdown .= "## ⚡ Performance Review\n\n";
            foreach ($analysis['performance_review'] as $item) {
                $markdown .= "- {$item}\n";
            }
            $markdown .= "\n";
        }

        if (! empty($analysis['resume_suggestions'])) {
            $markdown .= "## 📄 Resume & Portfolio Suggestions\n\n";
            foreach ($analysis['resume_suggestions'] as $item) {
                $markdown .= "- {$item}\n";
            }
            $markdown .= "\n";
        }

        if (! empty($analysis['interview_questions'])) {
            $markdown .= "## ❓ Potential Interview Questions\n\n";
            foreach ($analysis['interview_questions'] as $i => $item) {
                $markdown .= ($i + 1) . ". {$item}\n";
            }
            $markdown .= "\n";
        }

        if (! empty($analysis['best_companies'])) {
            $markdown .= "## 🏢 Best Companies to Target\n\n";
            foreach ($analysis['best_companies'] as $company) {
                $markdown .= "- {$company}\n";
            }
            $markdown .= "\n";
        }

        if (! empty($analysis['improvement_roadmap'])) {
            $markdown .= "## 🗺 Improvement Roadmap\n\n";
            foreach ($analysis['improvement_roadmap'] as $i => $item) {
                $markdown .= ($i + 1) . ". {$item}\n";
            }
            $markdown .= "\n";
        }

        $markdown .= "---\n\n";
        $markdown .= "_Report generated on " . now()->format('F j, Y') . " by **GitRadar**_\n";
        $markdown .= "_Analyzed at: " . $repository->ai_analyzed_at?->format('Y-m-d H:i:s') . " UTC_\n";

        $filename = str_replace('/', '_', $repository->full_name) . '_analysis_' . now()->format('Y-m-d') . '.md';

        return response()->streamDownload(function () use ($markdown) {
            echo $markdown;
        }, $filename, [
            'Content-Type' => 'text/markdown',
        ]);
    }
}
