<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\AnalyzeRepositoryJob;
use App\Models\GithubAccount;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class MultiUserIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Repository, 2: User, 3: Repository}
     */
    private function seedTwoUsersWithRepositories(): array
    {
        $userA = User::factory()->create(['email' => 'user-a@example.com']);
        $accountA = GithubAccount::create([
            'user_id'      => $userA->id,
            'github_id'    => '1001',
            'username'     => 'user-a',
            'access_token' => 'token-a',
        ]);
        $repoA = Repository::create([
            'repo_id'           => 9001,
            'github_account_id' => $accountA->id,
            'owner'             => 'user-a',
            'name'              => 'repo-a',
            'full_name'         => 'user-a/repo-a',
            'stars'             => 1,
            'forks'             => 0,
            'open_issues'       => 0,
            'watchers'          => 0,
            'size'              => 1,
            'html_url'          => 'https://github.com/user-a/repo-a',
            'default_branch'    => 'main',
            'is_private'        => false,
            'is_fork'           => false,
            'is_archived'       => false,
            'analysis_status'   => 'pending',
        ]);

        $userB = User::factory()->create(['email' => 'user-b@example.com']);
        $accountB = GithubAccount::create([
            'user_id'      => $userB->id,
            'github_id'    => '1002',
            'username'     => 'user-b',
            'access_token' => 'token-b',
        ]);
        $repoB = Repository::create([
            'repo_id'           => 9002,
            'github_account_id' => $accountB->id,
            'owner'             => 'user-b',
            'name'              => 'repo-b',
            'full_name'         => 'user-b/repo-b',
            'stars'             => 2,
            'forks'             => 0,
            'open_issues'       => 0,
            'watchers'          => 0,
            'size'              => 1,
            'html_url'          => 'https://github.com/user-b/repo-b',
            'default_branch'    => 'main',
            'is_private'        => false,
            'is_fork'           => false,
            'is_archived'       => false,
            'analysis_status'   => 'pending',
        ]);

        return [$userA, $repoA, $userB, $repoB];
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_user_cannot_view_another_users_repository(): void
    {
        [$userA, $repoA, $userB] = $this->seedTwoUsersWithRepositories();

        $this->actingAs($userB)
            ->get(route('repositories.show', $repoA))
            ->assertNotFound();

        $this->actingAs($userA)
            ->get(route('repositories.show', $repoA))
            ->assertOk();
    }

    public function test_user_cannot_analyze_another_users_repository(): void
    {
        [$userA, $repoA, $userB] = $this->seedTwoUsersWithRepositories();

        Bus::fake();

        $this->actingAs($userB)
            ->post(route('repositories.analyze', $repoA))
            ->assertNotFound();

        Bus::assertNothingDispatched();
    }

    public function test_user_cannot_export_another_users_repository(): void
    {
        [$userA, $repoA, $userB] = $this->seedTwoUsersWithRepositories();

        $repoA->update([
            'ai_analysis'    => ['score' => 80],
            'ai_analyzed_at' => now(),
            'analysis_status'=> 'completed',
        ]);

        $this->actingAs($userB)
            ->get(route('repositories.export.json', $repoA))
            ->assertNotFound();

        $this->actingAs($userA)
            ->get(route('repositories.export.json', $repoA))
            ->assertOk();
    }

    public function test_user_cannot_poll_another_users_analysis_status(): void
    {
        [$userA, $repoA, $userB] = $this->seedTwoUsersWithRepositories();

        $this->actingAs($userB)
            ->getJson(route('repositories.analysis-status', $repoA))
            ->assertNotFound();
    }

    public function test_user_cannot_pin_another_users_repository(): void
    {
        [$userA, $repoA, $userB] = $this->seedTwoUsersWithRepositories();

        $this->actingAs($userB)
            ->post(route('repositories.pin', $repoA))
            ->assertNotFound();
    }

    public function test_repository_index_only_lists_own_repositories(): void
    {
        [$userA, $repoA, $userB, $repoB] = $this->seedTwoUsersWithRepositories();

        $this->actingAs($userA)
            ->get(route('repositories.index'))
            ->assertOk()
            ->assertSee($repoA->name)
            ->assertDontSee($repoB->name);

        $this->actingAs($userB)
            ->get(route('repositories.index'))
            ->assertOk()
            ->assertSee($repoB->name)
            ->assertDontSee($repoA->name);
    }

    public function test_analyze_job_skips_when_user_does_not_own_repository(): void
    {
        [$userA, $repoA, $userB] = $this->seedTwoUsersWithRepositories();

        $job = new AnalyzeRepositoryJob($repoA, $userB);
        $job->handle(app(\App\Services\RepositoryAnalysisService::class));

        $repoA->refresh();
        $this->assertSame('pending', $repoA->analysis_status);
        $this->assertNull($repoA->ai_analyzed_at);
    }

    public function test_github_access_token_is_encrypted_at_rest(): void
    {
        $user = User::factory()->create();

        $account = GithubAccount::create([
            'user_id'      => $user->id,
            'github_id'    => '5555',
            'username'     => 'encrypted-user',
            'access_token' => 'gh_secret_token_value',
        ]);

        $raw = (string) \Illuminate\Support\Facades\DB::table('github_accounts')
            ->where('id', $account->id)
            ->value('access_token');

        $this->assertNotSame('gh_secret_token_value', $raw);
        $this->assertSame('gh_secret_token_value', $account->fresh()->access_token);
    }

    public function test_public_health_endpoint_returns_ok_without_secrets(): void
    {
        $response = $this->getJson(route('health'));

        $response->assertOk()
            ->assertJson(['status' => 'ok'])
            ->assertJsonMissing(['database', 'env', 'token', 'key']);
    }
}
