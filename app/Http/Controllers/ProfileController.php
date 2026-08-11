<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GithubService;
use App\Services\PortfolioScoreService;
use App\Support\PortfolioAssessment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;

class ProfileController extends Controller
{
    public function __construct(
        private readonly GithubService $githubService,
        private readonly PortfolioScoreService $portfolioScore,
    ) {}

    /**
     * Display the authenticated user's developer profile.
     *
     * All GitHub data is read from the GithubAccount belonging to the
     * currently authenticated user — never from a hardcoded account.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $account = $user->githubAccount;

        if (! $account) {
            return view('profile.not-found');
        }

        // Repositories synced for this user's GitHub account only.
        $repositories = $account->repositories()
            ->orderByDesc('stars')
            ->orderByDesc('pushed_at')
            ->get();

        $totalStars = $repositories->sum('stars');
        $totalForks = $repositories->sum('forks');

        $topLanguages = $repositories
            ->pluck('language')
            ->filter()
            ->countBy()
            ->sortDesc();

        $topRepositories = $repositories
            ->sortByDesc(fn ($repo) => $repo->stars)
            ->take(6)
            ->values();

        $recentlyUpdated = $repositories
            ->filter(fn ($repo) => $repo->pushed_at)
            ->sortByDesc('pushed_at')
            ->take(5)
            ->values();

        // Repository activity by year — mirrors the Insights page so the
        // timeline is consistent across the app.
        $activityTimeline = $repositories
            ->filter(fn ($repo) => $repo->github_created_at)
            ->groupBy(fn ($repo) => $repo->github_created_at->year)
            ->map(fn ($group) => $group->count())
            ->sortKeys();

        // Single source of truth for the portfolio score — shares the exact
        // same cache key as the Dashboard, Analysis, and Insights pages.
        $portfolioData = Cache::remember(
            "portfolio_score_{$user->id}",
            600,
            fn () => $this->portfolioScore->assess($repositories)->toArray()
        );

        $portfolio = PortfolioAssessment::fromArray($portfolioData);

        $stats = [
            'followers'        => $account->followers ?? 0,
            'following'        => $account->following ?? 0,
            'public_repos'     => $account->public_repos ?? 0,
            'public_gists'     => $account->public_gists ?? 0,
            'total_stars'      => $totalStars,
            'total_forks'      => $totalForks,
            'primary_language' => $topLanguages->keys()->first(),
            'synced_repos'     => $repositories->count(),
            'analyzed_repos'   => $repositories->filter(fn ($r) => $r->isAnalyzed())->count(),
        ];

        return view('profile.index', [
            'user'             => $user,
            'account'          => $account,
            'stats'            => $stats,
            'topLanguages'     => $topLanguages,
            'topRepositories'  => $topRepositories,
            'recentlyUpdated'  => $recentlyUpdated,
            'activityTimeline' => $activityTimeline,
            'portfolio'        => $portfolio,
        ]);
    }

    /**
     * Sync the authenticated user's GitHub profile data (not repositories).
     *
     * Supports both a traditional form submission (redirect + flash) and an
     * AJAX/fetch request (JSON) so the Profile page can update in place
     * without a full page reload.
     */
    public function sync(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $account = $user->githubAccount;

        if (! $account || ! $account->access_token) {
            return $this->syncResponse(
                $request,
                success: false,
                message: 'No GitHub account is connected. Please reconnect your GitHub account.',
            );
        }

        try {
            $success = $this->githubService->updateProfileFromApi($account->access_token, $account);

            if ($success) {
                return $this->syncResponse($request, success: true, message: 'GitHub profile synchronized successfully.');
            }

            return $this->syncResponse(
                $request,
                success: false,
                message: 'GitHub returned an empty profile response. Please try again later.',
            );
        } catch (RuntimeException $e) {
            // Expected, user-facing failures: expired token, rate limit, network error.
            Log::warning('Profile sync failed.', [
                'user_id' => $user->id,
                'reason'  => $e->getMessage(),
            ]);

            return $this->syncResponse($request, success: false, message: $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Profile sync failed unexpectedly.', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return $this->syncResponse(
                $request,
                success: false,
                message: 'An unexpected error occurred while syncing. Please try again.',
            );
        }
    }

    /**
     * Build the appropriate response for either an AJAX sync request or a
     * classic form submission.
     */
    private function syncResponse(Request $request, bool $success, string $message): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => $success,
                'message' => $message,
            ], $success ? 200 : 422);
        }

        return redirect()->route('profile.index')
            ->with($success ? 'success' : 'error', $message);
    }
}
