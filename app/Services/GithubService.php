<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GithubAccount;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GitHubService
{
    private const BASE_URL  = 'https://api.github.com';
    private const PER_PAGE  = 100;
    private const MAX_PAGES = 10; // safety cap: max 1 000 repos

    // ─── Repositories ────────────────────────────────────────────────────────

    /**
     * Fetch all repositories for the authenticated user across all pages.
     *
     * @return array<int, array<string, mixed>>
     * @throws RuntimeException
     */
    public function getRepositories(string $token): array
    {
        $all  = [];
        $page = 1;

        do {
            try {
                $response = Http::withToken($token)
                    ->acceptJson()
                    ->timeout(30)
                    ->retry(2, 500)
                    ->get(self::BASE_URL . '/user/repos', [
                        'per_page'   => self::PER_PAGE,
                        'sort'       => 'updated',
                        'visibility' => 'public',
                        'page'       => $page,
                    ]);

                if ($response->status() === 401) {
                    throw new RuntimeException('GitHub token is invalid or expired.');
                }

                if ($response->status() === 403) {
                    $rateLimitReset = $response->header('X-RateLimit-Reset');
                    Log::warning('GitHub API rate limit hit.', ['reset_at' => $rateLimitReset]);
                    throw new RuntimeException('GitHub API rate limit reached. Please wait before retrying.');
                }

                $response->throw();

                $repos = $response->json();

                if (! is_array($repos) || empty($repos)) {
                    break;
                }

                $all  = array_merge($all, $repos);
                $page++;

            } catch (ConnectionException $e) {
                Log::error('GitHub connection failed.', ['error' => $e->getMessage()]);
                throw new RuntimeException('Could not connect to GitHub API: ' . $e->getMessage());
            } catch (RequestException $e) {
                Log::error('GitHub request failed.', [
                    'status' => $e->response?->status(),
                    'body'   => $e->response?->body(),
                ]);
                throw new RuntimeException('GitHub API request failed: ' . $e->getMessage());
            }

        } while (count($repos) === self::PER_PAGE && $page <= self::MAX_PAGES);

        return $all;
    }

    // ─── README ──────────────────────────────────────────────────────────────

    /**
     * Fetch the raw README content for a repository.
     */
    public function getReadme(string $token, string $owner, string $repo): ?string
    {
        try {
            $response = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/vnd.github.raw+json'])
                ->timeout(15)
                ->get(self::BASE_URL . "/repos/{$owner}/{$repo}/readme");

            if (! $response->successful()) {
                return null;
            }

            $content = $response->body();

            // Truncate very long READMEs to avoid oversized prompts
            return mb_substr($content, 0, 8000);

        } catch (\Throwable $e) {
            Log::debug('README fetch failed.', [
                'repo'  => "{$owner}/{$repo}",
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // ─── User Profile ────────────────────────────────────────────────────────

    /**
     * Fetch the authenticated user's GitHub profile.
     *
     * @return array<string, mixed>
     */
    public function getUserProfile(string $token): array
    {
        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(15)
                ->retry(2, 500)
                ->get(self::BASE_URL . '/user');

            if ($response->status() === 401) {
                throw new RuntimeException('Your GitHub session has expired. Please reconnect your GitHub account.');
            }

            if ($response->status() === 403) {
                $rateLimitReset = $response->header('X-RateLimit-Reset');
                Log::warning('GitHub API rate limit hit while fetching profile.', ['reset_at' => $rateLimitReset]);
                throw new RuntimeException('GitHub API rate limit reached. Please wait a few minutes and try again.');
            }

            $response->throw();

            return $response->json() ?? [];

        } catch (RuntimeException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            Log::error('GitHub profile fetch failed — connection error.', ['error' => $e->getMessage()]);
            throw new RuntimeException('Could not connect to GitHub. Please check your connection and try again.');
        } catch (RequestException $e) {
            Log::error('GitHub profile fetch failed.', [
                'status' => $e->response?->status(),
                'error'  => $e->getMessage(),
            ]);
            throw new RuntimeException('GitHub returned an unexpected response. Please try again later.');
        } catch (\Throwable $e) {
            Log::error('GitHub profile fetch failed — unexpected error.', ['error' => $e->getMessage()]);
            throw new RuntimeException('An unexpected error occurred while contacting GitHub.');
        }
    }

    /**
     * Update a GithubAccount model with profile data from GitHub API.
     *
     * Note: this intentionally lets a RuntimeException from getUserProfile()
     * (expired token, rate limit, network error) propagate to the caller so
     * it can be translated into a specific, friendly message instead of a
     * generic failure.
     *
     * @throws RuntimeException
     */
    public function updateProfileFromApi(string $token, GithubAccount $githubAccount): bool
    {
        $profile = $this->getUserProfile($token);

        if (empty($profile)) {
            Log::warning('Empty profile data received from GitHub API', [
                'github_account_id' => $githubAccount->id,
            ]);
            return false;
        }

        // Map GitHub API fields to our database fields
        $updateData = [
            'username'           => $profile['login'] ?? null,
            'name'               => $profile['name'] ?? null,
            'avatar_url'         => $profile['avatar_url'] ?? null,
            'bio'                => $profile['bio'] ?? null,
            'company'            => $profile['company'] ?? null,
            'location'           => $profile['location'] ?? null,
            'blog'               => $profile['blog'] ?? null,
            'email'              => $profile['email'] ?? null,
            'twitter_username'   => $profile['twitter_username'] ?? null,
            'hireable'           => $profile['hireable'] ?? null,
            'followers'          => $profile['followers'] ?? 0,
            'following'          => $profile['following'] ?? 0,
            'public_repos'       => $profile['public_repos'] ?? 0,
            'public_gists'       => $profile['public_gists'] ?? 0,
            'github_created_at'  => $profile['created_at'] ?? null,
            'github_updated_at'  => $profile['updated_at'] ?? null,
        ];

        // Remove null values to avoid overwriting existing data with null.
        // Booleans and 0 are kept — only strip actual nulls.
        $updateData = array_filter($updateData, fn ($value) => $value !== null);

        $githubAccount->update($updateData);

        Log::info('GitHub profile updated', [
            'github_account_id' => $githubAccount->id,
            'username'          => $githubAccount->username,
        ]);

        return true;
    }

    // ─── Repository Details ──────────────────────────────────────────────────

    /**
     * Fetch topics for a repository.
     *
     * @return array<int, string>
     */
    public function getTopics(string $token, string $owner, string $repo): array
    {
        try {
            $response = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/vnd.github.mercy-preview+json'])
                ->timeout(10)
                ->get(self::BASE_URL . "/repos/{$owner}/{$repo}/topics");

            if (! $response->successful()) {
                return [];
            }

            return $response->json('names') ?? [];

        } catch (\Throwable $e) {
            return [];
        }
    }

    // ─── Languages ───────────────────────────────────────────────────────────

    /**
     * Fetch language breakdown (bytes) for a repository.
     *
     * @return array<string, int>
     */
    public function getLanguages(string $token, string $owner, string $repo): array
    {
        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(10)
                ->get(self::BASE_URL . "/repos/{$owner}/{$repo}/languages");

            return $response->successful() ? ($response->json() ?? []) : [];

        } catch (\Throwable $e) {
            return [];
        }
    }
}
