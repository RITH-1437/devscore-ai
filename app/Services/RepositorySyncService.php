<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GithubAccount;
use App\Models\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RepositorySyncService
{
    public function __construct(
        private readonly GitHubService $github,
    ) {}

    /**
     * Sync all repositories for the given GitHub account.
     * Fetches all pages, enriches with README, and upserts records.
     *
     * @throws RuntimeException
     */
    public function sync(GithubAccount $account): int
    {
        Log::info('Starting repository sync.', ['user_id' => $account->user_id]);

        // Refresh GitHub profile metadata
        $this->syncProfile($account);

        try {
            $repos = $this->github->getRepositories($account->access_token);
        } catch (RuntimeException $e) {
            Log::error('Repository sync failed — could not fetch repos.', [
                'user_id' => $account->user_id,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }

        $synced = 0;

        foreach ($repos as $repo) {
            try {
                $this->syncSingleRepository($account, $repo);
                $synced++;
            } catch (\Throwable $e) {
                Log::warning('Failed to sync single repository.', [
                    'repo'  => $repo['full_name'] ?? $repo['name'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                // Continue syncing the rest
            }
        }

        if (! empty($repos) && $synced === 0) {
            throw new RuntimeException('GitHub returned repositories, but none could be saved. Check the sync logs for repository-specific errors.');
        }

        Log::info("Repository sync complete. Synced {$synced} repositories.", [
            'user_id' => $account->user_id,
            'fetched' => count($repos),
        ]);

        // Repository data feeds the portfolio score — invalidate the cache.
        Cache::forget("portfolio_score_{$account->user_id}");

        return $synced;
    }

    /**
     * Sync (upsert) a single repository record.
     *
     * @param array<string, mixed> $repo
     */
    private function syncSingleRepository(GithubAccount $account, array $repo): void
    {
        $owner = $repo['owner']['login'] ?? $account->username;
        $name  = $repo['name'];

        $readme = $this->github->getReadme($account->access_token, $owner, $name);

        // Parse license name
        $license = null;
        if (! empty($repo['license']['name'])) {
            $license = $repo['license']['name'];
        } elseif (! empty($repo['license']['spdx_id'])) {
            $license = $repo['license']['spdx_id'];
        }

        Repository::updateOrCreate(
            ['repo_id' => $repo['id']],
            [
                'github_account_id' => $account->id,
                'owner'             => $owner,
                'name'              => $name,
                'full_name'         => $repo['full_name'] ?? "{$owner}/{$name}",
                'description'       => $repo['description'],
                'language'          => $repo['language'],
                'stars'             => $repo['stargazers_count'] ?? 0,
                'forks'             => $repo['forks_count'] ?? 0,
                'open_issues'       => $repo['open_issues_count'] ?? 0,
                'watchers'          => $repo['watchers_count'] ?? 0,
                'size'              => $repo['size'] ?? 0,
                'html_url'          => $repo['html_url'],
                'clone_url'         => $repo['clone_url'] ?? null,
                'default_branch'    => $repo['default_branch'] ?? 'main',
                'topics'            => $repo['topics'] ?? [],
                'license'           => $license,
                'is_private'        => $repo['private'] ?? false,
                'is_fork'           => $repo['fork'] ?? false,
                'is_archived'       => $repo['archived'] ?? false,
                'pushed_at'         => isset($repo['pushed_at'])
                    ? \Carbon\Carbon::parse($repo['pushed_at'])
                    : null,
                'github_created_at' => isset($repo['created_at'])
                    ? \Carbon\Carbon::parse($repo['created_at'])
                    : null,
                'readme'            => $readme,
            ]
        );
    }

    /**
     * Refresh the GitHub account's profile metadata.
     */
    private function syncProfile(GithubAccount $account): void
    {
        try {
            $profile = $this->github->getUserProfile($account->access_token);

            if (empty($profile)) {
                return;
            }

            $account->update([
                'name'              => $profile['name'] ?? $account->name,
                'bio'               => $profile['bio'] ?? null,
                'company'           => $profile['company'] ?? null,
                'location'          => $profile['location'] ?? null,
                'blog'              => $profile['blog'] ?? null,
                'email'             => $profile['email'] ?? null,
                'avatar_url'        => $profile['avatar_url'] ?? $account->avatar_url,
                'followers'         => $profile['followers'] ?? 0,
                'following'         => $profile['following'] ?? 0,
                'public_repos'      => $profile['public_repos'] ?? 0,
                'github_created_at' => isset($profile['created_at'])
                    ? \Carbon\Carbon::parse($profile['created_at'])
                    : null,
            ]);

        } catch (\Throwable $e) {
            Log::warning('GitHub profile refresh failed.', ['error' => $e->getMessage()]);
        }
    }
}
