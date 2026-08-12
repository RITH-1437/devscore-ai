<?php

use App\Http\Controllers\AiHealthController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\Auth\GitHubController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InsightsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
})->name('health');

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');

Route::get('/', function () {
    return view('landing');
})->name('home');

Route::get('/login', function () {
    return redirect()->route('github.login');
})->name('login');

// GitHub OAuth
Route::get('/auth/github', [GitHubController::class, 'redirect'])
    ->name('github.login');

Route::get('/auth/github/callback', [GitHubController::class, 'callback'])
    ->name('github.callback')
    ->middleware('throttle:10,1'); // 10 attempts per minute

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    // ── Dashboard ──────────────────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // ── Repositories ───────────────────────────────────────────────────────
    Route::prefix('repositories')->name('repositories.')->group(function () {
        Route::get('/', [RepositoryController::class, 'index'])
            ->name('index');

        Route::get('/{repository}', [RepositoryController::class, 'show'])
            ->name('show');

        Route::get('/{repository}/analysis-status', [RepositoryController::class, 'analysisStatus'])
            ->name('analysis-status');

        // AI Analysis — rate limited to 20 per minute per user
        Route::post('/{repository}/analyze', [RepositoryController::class, 'analyze'])
            ->name('analyze')
            ->middleware('throttle:20,1');

        // Toggle pin/feature
        Route::post('/{repository}/pin', [RepositoryController::class, 'togglePin'])
            ->name('pin')
            ->middleware('throttle:30,1');

        Route::post('/{repository}/feature', [RepositoryController::class, 'toggleFeature'])
            ->name('feature')
            ->middleware('throttle:30,1');

        // Export
        Route::get('/{repository}/export/json', [RepositoryController::class, 'exportJson'])
            ->name('export.json');

        Route::get('/{repository}/export/markdown', [RepositoryController::class, 'exportMarkdown'])
            ->name('export.markdown');
    });

    // ── Portfolio Analysis ─────────────────────────────────────────────────
    Route::get('/analysis', [AnalysisController::class, 'index'])
        ->name('analysis');

    Route::post('/analysis/run', [AnalysisController::class, 'runPortfolioAnalysis'])
        ->name('analysis.run')
        ->middleware('throttle:5,1');

    // ── Insights ───────────────────────────────────────────────────────────
    Route::get('/insights', [InsightsController::class, 'index'])
        ->name('insights');

    // ── Profile ────────────────────────────────────────────────────────────
    Route::get('/profile', [ProfileController::class, 'index'])
        ->name('profile.index');

    Route::post('/profile/sync', [ProfileController::class, 'sync'])
        ->name('profile.sync')
        ->middleware('throttle:5,10'); // 5 syncs per 10 minutes

    // ── Settings ───────────────────────────────────────────────────────────
    Route::get('/settings', [SettingsController::class, 'index'])
        ->name('settings');

    Route::post('/settings/sync', [SettingsController::class, 'syncRepositories'])
        ->name('settings.sync')
        ->middleware('throttle:5,10'); // 5 syncs per 10 minutes

    // ── AI Health (authenticated) ──────────────────────────────────────────
    Route::get('/health/ai', AiHealthController::class)
        ->name('health.ai')
        ->middleware('throttle:12,1');

    // ── Logout (POST only — CSRF protected) ────────────────────────────────
    Route::post('/logout', [SettingsController::class, 'logout'])
        ->name('logout');
});
