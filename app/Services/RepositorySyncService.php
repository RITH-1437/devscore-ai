<?php

namespace App\Services;

use App\Models\GithubAccount;
use App\Models\Repository;

class RepositorySyncService
{
    public function sync(GithubAccount $account): void
    {
        $repositories = app(GitHubService::class)
            ->getRepositories($account->access_token);

        foreach ($repositories as $repo) {

            Repository::updateOrCreate(
                [
                    'repo_id' => $repo['id'],
                ],
                [
                    'github_account_id' => $account->id,
                    'name' => $repo['name'],
                    'description' => $repo['description'],
                    'language' => $repo['language'],
                    'stars' => $repo['stargazers_count'],
                    'forks' => $repo['forks_count'],
                    'open_issues' => $repo['open_issues_count'],
                    'html_url' => $repo['html_url'],
                ]
            );
        }
    }
}