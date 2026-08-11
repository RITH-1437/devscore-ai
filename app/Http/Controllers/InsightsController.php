<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Repository;
use App\Services\PortfolioScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class InsightsController extends Controller
{
    public function __construct(
        private readonly PortfolioScoreService $scoreService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $repositories = Repository::forUser($user)
            ->orderByDesc('stars')
            ->get();

        $totalForks    = $repositories->sum('forks');
        $totalStars    = $repositories->sum('stars');
        $totalIssues   = $repositories->sum('open_issues');
        $totalWatchers = $repositories->sum('watchers');

        $languages = $repositories
            ->pluck('language')
            ->filter()
            ->countBy()
            ->sortDesc();

        $totalLangCount = $languages->sum();
        $languageCount  = $languages->count();

        $topRepositories = $repositories->sortByDesc('stars')->take(10);

        $analyzedRepos = $repositories->filter(fn ($r) => $r->isAnalyzed());
        $notAnalyzedCount = $repositories->count() - $analyzedRepos->count();

        $bestRepo    = $analyzedRepos->sortByDesc(fn ($r) => $r->score ?? -1)->first();
        $weakestRepo = $analyzedRepos->sortBy(fn ($r) => $r->score ?? 101)->first();

        $highScoringRepos = $analyzedRepos
            ->filter(fn ($r) => ($r->score ?? 0) >= 75)
            ->sortByDesc(fn ($r) => $r->score ?? 0)
            ->take(5);

        $lowScoringRepos = $analyzedRepos
            ->filter(fn ($r) => ($r->score ?? 0) < 60)
            ->sortBy(fn ($r) => $r->score ?? 0)
            ->take(5);

        $timeline = $repositories
            ->filter(fn ($r) => $r->github_created_at)
            ->groupBy(fn ($r) => $r->github_created_at->year)
            ->map(fn ($group) => $group->count())
            ->sortKeys();

        $topics = $repositories
            ->pluck('topics')
            ->flatten()
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(20);

        $portfolioData = Cache::remember(
            "portfolio_score_{$user->id}",
            600,
            fn () => $this->scoreService->assess($repositories)->toArray()
        );

        $portfolio = \App\Support\PortfolioAssessment::fromArray($portfolioData);

        $mostActiveRepo = $repositories
            ->filter(fn ($r) => $r->pushed_at)
            ->sortByDesc('pushed_at')
            ->first();

        $withReadme = $repositories->filter(fn ($r) => filled($r->readme))->count();
        $docCoverage = $repositories->count() > 0
            ? (int) round(($withReadme / $repositories->count()) * 100)
            : 0;

        $recentlyActive = $repositories
            ->filter(fn ($r) => $r->pushed_at && $r->pushed_at->greaterThan(now()->subDays(90)))
            ->count();

        $analysisCoverage = $repositories->count() > 0
            ? (int) round(($analyzedRepos->count() / $repositories->count()) * 100)
            : 0;

        $scoreDistribution = [
            'excellent' => $analyzedRepos->filter(fn ($r) => ($r->score ?? 0) >= 90)->count(),
            'strong'    => $analyzedRepos->filter(fn ($r) => ($r->score ?? 0) >= 75 && ($r->score ?? 0) < 90)->count(),
            'developing'=> $analyzedRepos->filter(fn ($r) => ($r->score ?? 0) >= 60 && ($r->score ?? 0) < 75)->count(),
            'needsWork' => $analyzedRepos->filter(fn ($r) => ($r->score ?? 0) >= 40 && ($r->score ?? 0) < 60)->count(),
            'weak'      => $analyzedRepos->filter(fn ($r) => ($r->score ?? 0) < 40)->count(),
        ];

        return view('insights.index', [
            'repositories'       => $repositories,
            'totalForks'         => $totalForks,
            'totalStars'         => $totalStars,
            'totalIssues'        => $totalIssues,
            'totalWatchers'      => $totalWatchers,
            'languages'          => $languages,
            'totalLangCount'     => $totalLangCount,
            'languageCount'      => $languageCount,
            'topRepositories'    => $topRepositories,
            'bestRepo'           => $bestRepo,
            'weakestRepo'        => $weakestRepo,
            'highScoringRepos'   => $highScoringRepos,
            'lowScoringRepos'    => $lowScoringRepos,
            'timeline'           => $timeline,
            'topics'             => $topics,
            'portfolio'          => $portfolio,
            'mostActiveRepo'     => $mostActiveRepo,
            'analyzedCount'      => $analyzedRepos->count(),
            'notAnalyzedCount'   => $notAnalyzedCount,
            'docCoverage'        => $docCoverage,
            'recentlyActive'     => $recentlyActive,
            'analysisCoverage'   => $analysisCoverage,
            'scoreDistribution'  => $scoreDistribution,
        ]);
    }
}
