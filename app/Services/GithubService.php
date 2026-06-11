<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GitHubService
{
    public function getRepositories(string $token): array
    {
        return Http::withToken($token)
            ->acceptJson()
            ->get(
                'https://api.github.com/user/repos',
                [
                    'per_page' => 100,
                    'sort' => 'updated',
                ]
            )
            ->json();
    }
}