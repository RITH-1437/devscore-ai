<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\GithubAccount;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepositoryIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_repositories_can_be_filtered_by_language_and_repository_traits(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $account = $this->createGithubAccount($user);

        $this->createRepository($account, [
            'name' => 'alpha-source',
            'language' => 'PHP',
            'is_private' => false,
            'is_fork' => false,
            'is_archived' => false,
            'ai_analyzed_at' => now(),
            'analysis_status' => 'completed',
        ]);

        $this->createRepository($account, [
            'name' => 'beta-fork',
            'language' => 'JavaScript',
            'is_private' => true,
            'is_fork' => true,
            'is_archived' => true,
            'analysis_status' => 'processing',
        ]);

        $this->createRepository($account, [
            'name' => 'gamma-pending',
            'language' => 'PHP',
            'is_private' => false,
            'is_fork' => false,
            'is_archived' => false,
            'analysis_status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('repositories.index', [
            'language' => 'PHP',
            'visibility' => 'public',
            'origin' => 'source',
            'state' => 'active',
            'analysis' => 'analyzed',
        ]));

        $response
            ->assertOk()
            ->assertSee('alpha-source')
            ->assertDontSee('beta-fork')
            ->assertDontSee('gamma-pending')
            ->assertSee('Language: PHP')
            ->assertSee('Public')
            ->assertSee('Source repos')
            ->assertSee('Active')
            ->assertSee('Analyzed');
    }

    public function test_repositories_can_be_filtered_by_highlight_and_pending_analysis(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $account = $this->createGithubAccount($user);

        $this->createRepository($account, [
            'name' => 'delta-pinned',
            'is_pinned' => true,
            'analysis_status' => 'pending',
        ]);

        $this->createRepository($account, [
            'name' => 'epsilon-featured',
            'is_featured' => true,
            'analysis_status' => 'pending',
        ]);

        $this->createRepository($account, [
            'name' => 'zeta-processing',
            'is_pinned' => true,
            'analysis_status' => 'processing',
        ]);

        $response = $this->actingAs($user)->get(route('repositories.index', [
            'highlight' => 'pinned',
            'analysis' => 'pending',
        ]));

        $response
            ->assertOk()
            ->assertSee('delta-pinned')
            ->assertDontSee('epsilon-featured')
            ->assertDontSee('zeta-processing')
            ->assertSee('Pinned')
            ->assertSee('Not analyzed');
    }

    private function createGithubAccount(User $user): GithubAccount
    {
        return GithubAccount::create([
            'user_id' => $user->id,
            'github_id' => 'github-' . $user->id,
            'username' => 'user-' . $user->id,
            'access_token' => 'token',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createRepository(GithubAccount $account, array $attributes = []): Repository
    {
        $name = $attributes['name'] ?? 'repo-' . fake()->unique()->numberBetween(1, 100000);

        return Repository::create(array_merge([
            'github_account_id' => $account->id,
            'repo_id' => fake()->unique()->numberBetween(1, 1000000),
            'owner' => $account->username,
            'name' => $name,
            'full_name' => $account->username . '/' . $name,
            'description' => null,
            'language' => 'PHP',
            'stars' => 0,
            'forks' => 0,
            'open_issues' => 0,
            'watchers' => 0,
            'size' => 0,
            'html_url' => 'https://github.com/' . $account->username . '/' . $name,
            'clone_url' => 'https://github.com/' . $account->username . '/' . $name . '.git',
            'default_branch' => 'main',
            'is_private' => false,
            'is_fork' => false,
            'is_archived' => false,
            'is_featured' => false,
            'is_pinned' => false,
            'analysis_status' => 'pending',
        ], $attributes));
    }
}
