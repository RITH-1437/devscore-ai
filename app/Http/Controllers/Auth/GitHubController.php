<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SyncRepositoriesJob;
use App\Models\GithubAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GitHubController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('github')
            ->scopes(['user', 'public_repo'])
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $githubUser = Socialite::driver('github')->user();
        } catch (InvalidStateException $e) {
            Log::warning('GitHub OAuth invalid state.', ['error' => $e->getMessage()]);
            return redirect('/')->withErrors(['github' => 'OAuth session expired. Please try again.']);
        } catch (\Throwable $e) {
            Log::error('GitHub OAuth callback failed.', ['error' => $e->getMessage()]);
            return redirect('/')->withErrors(['github' => 'GitHub authentication failed. Please try again.']);
        }

        try {
            // Find or create user
            $user = User::firstOrCreate(
                ['email' => $githubUser->getEmail() ?? $githubUser->getNickname() . '@github.invalid'],
                [
                    'name'     => $githubUser->getName() ?? $githubUser->getNickname() ?? 'GitHub User',
                    'password' => bcrypt(Str::random(40)),
                ]
            );

            // Update name if it was previously the default
            if ($user->name === 'GitHub User' && $githubUser->getName()) {
                $user->update(['name' => $githubUser->getName()]);
            }

            // Upsert GitHub account (token refresh)
            $githubAccount = GithubAccount::updateOrCreate(
                ['github_id' => $githubUser->getId()],
                [
                    'user_id'     => $user->id,
                    'username'    => $githubUser->getNickname(),
                    'name'        => $githubUser->getName(),
                    'avatar_url'  => $githubUser->getAvatar(),
                    'access_token'=> $githubUser->token,
                ]
            );

            Auth::login($user, remember: true);

            // Regenerate the session ID to prevent session fixation.
            request()->session()->regenerate();

            // Run repository sync after the redirect is sent so a missing queue
            // worker does not leave new users with zero GitHub data.
            SyncRepositoriesJob::dispatchAfterResponse($user)->onQueue('default');

            return redirect()->route('dashboard');

        } catch (\Throwable $e) {
            Log::error('GitHub login database error.', ['error' => $e->getMessage()]);
            return redirect('/')->withErrors(['github' => 'Login failed. Please try again.']);
        }
    }
}
