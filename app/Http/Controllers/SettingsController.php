<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\SyncRepositoriesJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        return view('settings.index', [
            'user'    => $request->user(),
            'account' => $request->user()->githubAccount,
        ]);
    }

    /**
     * Trigger a fresh repository sync for the authenticated user.
     */
    public function syncRepositories(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->githubAccount) {
            return back()->with('error', 'No GitHub account connected.');
        }

        // Clear caches
        Cache::forget("portfolio_score_{$user->id}");
        Cache::forget("portfolio_analysis_{$user->id}");

        SyncRepositoriesJob::dispatchAfterResponse($user)->onQueue('default');

        return back()->with('success', 'Repository sync started. Your repos will update shortly.');
    }

    /**
     * Log the user out securely (POST only, CSRF protected).
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
