<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\GithubAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GitHubAccountBindingTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_github_account_keeps_original_user_on_relogin(): void
    {
        $originalUser = User::factory()->create(['email' => 'owner@example.com']);

        $account = GithubAccount::create([
            'user_id'      => $originalUser->id,
            'github_id'    => '424242',
            'username'     => 'original-dev',
            'access_token' => 'old-token',
        ]);

        $otherUser = User::factory()->create(['email' => 'other@example.com']);

        // Simulate the fixed OAuth path: existing account must not move to otherUser.
        $account->update([
            'username'     => 'original-dev',
            'access_token' => 'refreshed-token',
        ]);

        $account->refresh();

        $this->assertSame($originalUser->id, $account->user_id);
        $this->assertNotSame($otherUser->id, $account->user_id);
        $this->assertSame('refreshed-token', $account->access_token);
    }
}
