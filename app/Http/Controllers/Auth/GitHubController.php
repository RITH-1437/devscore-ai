<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\GithubAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use App\Services\RepositorySyncService;

class GitHubController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('github')->redirect();
    }

    public function callback()
    {
        $githubUser = Socialite::driver('github')->user();

        $user = User::firstOrCreate(
            [
                'email' => $githubUser->email,
            ],
            [
                'name' => $githubUser->nickname ?? 'GitHub User',
                'password' => bcrypt(Str::random(32)),
            ]
        );

        GithubAccount::updateOrCreate(
            [
                'github_id' => $githubUser->id,
            ],
            [
                'user_id' => $user->id,
                'username' => $githubUser->nickname,
                'avatar_url' => $githubUser->avatar,
                'access_token' => $githubUser->token,
            ]
        );

        Auth::login($user);
        app(RepositorySyncService::class)
    ->sync($user->githubAccount);

        return redirect('/dashboard');
    }
}