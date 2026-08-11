<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Repository;
use App\Services\PortfolioScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly PortfolioScoreService $portfolioScore,
    ) {}

    public function index(Request $request): View
    {
        $user   = $request->user();
        $search = $request->get('search');

        // Fetch only THIS user's repositories
        $repositories = Repository::forUser($user)
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('language', 'like', "%{$search}%");
            }))
            ->orderByDesc('stars')
            ->get();

        $totalRepos  = $repositories->count();
        $totalStars  = $repositories->sum('stars');
        $totalForks  = $repositories->sum('forks');

        $topLanguages = $repositories
            ->pluck('language')
            ->filter()
            ->countBy()
            ->sortDesc();

        // Single source of truth for the portfolio assessment. Shares the
        // exact same cache key as the AI Analysis page.
        // Store as array to avoid serialization issues with readonly properties.
        $portfolioData = Cache::remember(
            "portfolio_score_{$user->id}",
            600,
            fn () => $this->portfolioScore->assess($repositories)->toArray()
        );

        $portfolio = \App\Support\PortfolioAssessment::fromArray($portfolioData);

        $analyzedCount = $repositories->filter(fn ($r) => $r->isAnalyzed())->count();

        $recentlyAnalyzed = Repository::forUser($user)
            ->analyzed()
            ->orderByDesc('ai_analyzed_at')
            ->limit(5)
            ->get();

        $pinnedRepos = Repository::forUser($user)
            ->pinned()
            ->orderByDesc('stars')
            ->get();

        return view('dashboard.index', [
            'repositories'    => $repositories,
            'totalRepos'      => $totalRepos,
            'totalStars'      => $totalStars,
            'totalForks'      => $totalForks,
            'topLanguages'    => $topLanguages,
            'portfolio'       => $portfolio,
            'analyzedCount'   => $analyzedCount,
            'recentlyAnalyzed'=> $recentlyAnalyzed,
            'pinnedRepos'     => $pinnedRepos,
            'account'         => $user->githubAccount,
        ]);
    }
}
