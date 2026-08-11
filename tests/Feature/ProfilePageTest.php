<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\GithubAccount;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_renders_fallback_when_github_account_is_missing(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.index'))
            ->assertOk()
            ->assertSee('No GitHub Profile Connected')
            ->assertSee(route('github.login'));
    }

    public function test_profile_page_renders_github_account_data_and_normalized_external_links(): void
    {
        $this->withoutVite();

        $user = User::factory()->create(['name' => 'Ada Lovelace']);
        $account = GithubAccount::create([
            'user_id' => $user->id,
            'github_id' => 'github-' . $user->id,
            'username' => 'ada-dev',
            'name' => 'Ada Dev',
            'avatar_url' => 'https://avatars.githubusercontent.com/u/123',
            'bio' => 'Builds developer tools.',
            'company' => 'Analytical Engines',
            'location' => 'London',
            'blog' => 'ada.example.com/work',
            'access_token' => 'token',
            'followers' => 12,
            'following' => 3,
            'public_repos' => 1,
            'public_gists' => 0,
            'github_created_at' => now()->subYears(2),
        ]);

        Repository::create([
            'github_account_id' => $account->id,
            'repo_id' => 123456,
            'owner' => 'ada-dev',
            'name' => 'compiler-notes',
            'full_name' => 'ada-dev/compiler-notes',
            'description' => 'Notes about compilers and tooling.',
            'language' => 'PHP',
            'stars' => 42,
            'forks' => 7,
            'open_issues' => 1,
            'watchers' => 2,
            'size' => 128,
            'html_url' => 'https://github.com/ada-dev/compiler-notes',
            'clone_url' => 'https://github.com/ada-dev/compiler-notes.git',
            'default_branch' => 'main',
            'is_private' => false,
            'is_fork' => false,
            'is_archived' => false,
            'is_featured' => false,
            'is_pinned' => false,
            'analysis_status' => 'pending',
            'pushed_at' => now()->subDay(),
            'github_created_at' => now()->subYear(),
        ]);

        $this->actingAs($user)
            ->get(route('profile.index'))
            ->assertOk()
            ->assertSee('Ada Dev')
            ->assertSee('ada-dev')
            ->assertDontSee('{{ $account->username }}')
            ->assertSee('Builds developer tools.')
            ->assertSee('compiler-notes')
            ->assertSee('https://ada.example.com/work')
            ->assertSee('https://github.com/ada-dev/compiler-notes');
    }

    public function test_profile_sync_updates_the_connected_github_account(): void
    {
        $user = User::factory()->create();
        $account = GithubAccount::create([
            'user_id' => $user->id,
            'github_id' => 'github-' . $user->id,
            'username' => 'old-username',
            'access_token' => 'token',
        ]);

        Http::fake([
            'https://api.github.com/user' => Http::response([
                'login' => 'updated-username',
                'name' => 'Updated User',
                'followers' => 24,
                'following' => 6,
                'public_repos' => 8,
                'public_gists' => 1,
            ]),
        ]);

        $this->actingAs($user)
            ->postJson(route('profile.sync'))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'GitHub profile synchronized successfully.',
            ]);

        $this->assertDatabaseHas('github_accounts', [
            'id' => $account->id,
            'username' => 'updated-username',
            'name' => 'Updated User',
            'followers' => 24,
        ]);
    }
}
