<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Models\Repository;
use App\Services\PortfolioScoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AnalysisController extends Controller
{
    public function __construct(
        private readonly PortfolioScoreService $portfolioScore,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        // Scoped to authenticated user only
        $repositories = Repository::forUser($user)
            ->orderByDesc('stars')
            ->get();

        // Single source of truth for the portfolio assessment. Both the
        // Dashboard and this page read from the same cached key so the
        // displayed portfolio score can never diverge.
        // Store as array to avoid serialization issues with readonly properties.
        $portfolioData = Cache::remember(
            "portfolio_score_{$user->id}",
            600,
            fn () => $this->portfolioScore->assess($repositories)->toArray()
        );

        $portfolio = \App\Support\PortfolioAssessment::fromArray($portfolioData);

        // Latest AI analyses per repository
        $recentAnalyses = Analysis::where('user_id', $user->id)
            ->where('status', 'completed')
            ->with('repository')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        // Repository-level analysis rows (latest completed analysis per repo)
        $analyzedRepos = Repository::forUser($user)
            ->analyzed()
            ->with(['analyses' => fn ($q) => $q->where('status', 'completed')->latest()->limit(1)])
            ->orderByDesc('stars')
            ->get();

        $topRepoByScore = $analyzedRepos
            ->sortByDesc(fn ($r) => $r->score ?? -1)
            ->first();

        $weakestRepo = $analyzedRepos
            ->sortBy(fn ($r) => $r->score ?? 101)
            ->first();

        $languageCount = $repositories->pluck('language')->filter()->unique()->count();

        return view('analysis.index', [
            'repositories'   => $repositories,
            'portfolio'      => $portfolio,
            'recentAnalyses' => $recentAnalyses,
            'topRepoByScore' => $topRepoByScore,
            'weakestRepo'    => $weakestRepo,
            'analyzedRepos'  => $analyzedRepos,
            'languageCount'  => $languageCount,
        ]);
    }

    /**
     * Recompute the cached portfolio assessment. The score is always derived
     * from the stored repository AI analyses, so this only refreshes the
     * aggregated result.
     */
    public function runPortfolioAnalysis(Request $request): RedirectResponse
    {
        $user = $request->user();

        $repositories = Repository::forUser($user)->get();

        if ($repositories->isEmpty()) {
            return back()->with('error', 'No repositories found to analyze.');
        }

        Cache::forget("portfolio_score_{$user->id}");

        $assessment = $this->portfolioScore->assess($repositories);

        if (! $assessment->isAnalyzed()) {
            return back()->with('info', 'No repositories have been analyzed yet. Run AI analysis on a repository first.');
        }

        // Store as array to avoid serialization issues with readonly properties
        Cache::put("portfolio_score_{$user->id}", $assessment->toArray(), 600);

        return back()->with('success', 'Portfolio analysis refreshed.');
    }
}
