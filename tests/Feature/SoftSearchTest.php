<?php

namespace Tests\Feature;

use App\Models\GithubAccount;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftSearchTest extends TestCase
{
    use RefreshDatabase;

    private function setupUser(): array
    {
        $user = User::factory()->create();

        $account = GithubAccount::create([
            'user_id'      => $user->id,
            'github_id'    => 1,
            'username'     => 'testuser',
            'access_token' => 'gh_test_token',
        ]);

        $repos = [
            Repository::create([
                'repo_id'           => 1,
                'github_account_id' => $account->id,
                'owner'             => 'testuser',
                'name'              => 'LaravelApi',
                'full_name'         => 'testuser/LaravelApi',
                'description'       => 'A PHP API for payments',
                'language'          => 'PHP',
                'html_url'          => 'https://github.com/testuser/LaravelApi',
                'stars'             => 10,
                'analysis_status'   => 'pending',
            ]),
            Repository::create([
                'repo_id'           => 2,
                'github_account_id' => $account->id,
                'owner'             => 'testuser',
                'name'              => 'ReactDashboard',
                'full_name'         => 'testuser/ReactDashboard',
                'description'       => 'A TypeScript dashboard',
                'language'          => 'TypeScript',
                'html_url'          => 'https://github.com/testuser/ReactDashboard',
                'stars'             => 5,
                'analysis_status'   => 'pending',
            ]),
        ];

        return [$user, $account, $repos];
    }

    public function test_json_request_returns_the_grid_partial(): void
    {
        [$user] = $this->setupUser();

        $response = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->getJson(route('repositories.index'));

        $response->assertOk();
        $response->assertSee('LaravelApi', false);
        $response->assertSee('ReactDashboard', false);
    }

    public function test_soft_search_filters_by_name_case_insensitively(): void
    {
        [$user] = $this->setupUser();

        $response = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->getJson(route('repositories.index', ['search' => 'laravel']));

        $response->assertOk();
        $response->assertSee('LaravelApi', false);
        $response->assertDontSee('ReactDashboard', false);
    }

    public function test_soft_search_filters_by_description(): void
    {
        [$user] = $this->setupUser();

        $response = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->getJson(route('repositories.index', ['search' => 'typescript']));

        $response->assertOk();
        $response->assertSee('ReactDashboard', false);
        $response->assertDontSee('LaravelApi', false);
    }

    public function test_search_combined_with_language_and_sort(): void
    {
        [$user] = $this->setupUser();

        $response = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->getJson(route('repositories.index', [
                'search'   => 'dashboard',
                'language' => 'TypeScript',
                'sort'     => 'name',
                'order'    => 'asc',
            ]));

        $response->assertOk();
        $response->assertSee('ReactDashboard', false);
        $response->assertDontSee('LaravelApi', false);
    }

    public function test_no_results_renders_empty_state(): void
    {
        [$user] = $this->setupUser();

        $response = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->getJson(route('repositories.index', ['search' => 'nonexistent']));

        $response->assertOk();
        $response->assertDontSee('LaravelApi', false);
        $response->assertDontSee('ReactDashboard', false);
    }

    public function test_full_page_still_renders_grid(): void
    {
        [$user] = $this->setupUser();

        $response = $this->actingAs($user)->get(route('repositories.index'));

        $response->assertOk();
        $response->assertSee('LaravelApi', false);
    }
}
