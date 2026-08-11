<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\GithubAccount;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RepositoryAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'gemini.api_key'            => '',
            'openrouter.api_key'        => 'sk-test-key',
            'openrouter.timeout'        => 2,
            'openrouter.connect_timeout'=> 1,
            'openrouter.retry_times'    => 0,
            'openrouter.total_budget'   => 5,
            'openrouter.verify_models'  => true,
            'openrouter.models'         => ['google/gemma-3-27b-it:free'],
        ]);

        Cache::flush();

        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [['id' => 'google/gemma-3-27b-it:free']],
            ], 200),
        ]);
    }

    private function setupUser(): array
    {
        $user = User::factory()->create();

        $account = GithubAccount::create([
            'user_id'      => $user->id,
            'github_id'    => 1,
            'username'     => 'testuser',
            'access_token' => 'gh_test_token',
        ]);

        $repo = Repository::create([
            'repo_id'           => 100,
            'github_account_id' => $account->id,
            'owner'             => 'testuser',
            'name'              => 'demo',
            'full_name'         => 'testuser/demo',
            'description'       => 'A demo repository',
            'language'          => 'PHP',
            'stars'             => 5,
            'forks'             => 1,
            'open_issues'       => 0,
            'watchers'          => 1,
            'size'              => 100,
            'html_url'          => 'https://github.com/testuser/demo',
            'clone_url'         => 'https://github.com/testuser/demo.git',
            'default_branch'    => 'main',
            'topics'            => ['php'],
            'license'           => 'MIT',
            'is_private'        => false,
            'is_fork'           => false,
            'is_archived'       => false,
            'readme'            => '# Demo',
            'analysis_status'   => 'pending',
        ]);

        return [$user, $repo];
    }

    public function test_analyze_dispatches_job_and_persists_results(): void
    {
        [$user, $repo] = $this->setupUser();

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'score'                => 82,
                    'difficulty'           => 'intermediate',
                    'portfolio_level'      => 'mid',
                    'recruiter_rating'     => 7,
                    'estimated_experience' => '1-3 years',
                    'hiring_probability'   => 70,
                    'market_readiness'     => 'ready',
                    'strengths'            => ['Clean code', 'Great docs'],
                    'weaknesses'           => ['No CI'],
                    'recommendations'      => ['Add CI'],
                ])]]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20, 'total_tokens' => 30],
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->post(route('repositories.analyze', $repo));

        $response->assertRedirect(route('repositories.show', $repo));
        $response->assertSessionHas('info');

        $repo->refresh();
        $this->assertTrue($repo->isAnalyzed());
        $this->assertSame('completed', $repo->analysis_status);
        $this->assertSame(82, $repo->ai_analysis['score']);
        $this->assertSame(['No CI'], $repo->ai_analysis['weaknesses']);

        $analysis = Analysis::where('repository_id', $repo->id)->first();
        $this->assertNotNull($analysis);
        $this->assertSame('completed', $analysis->status);
        $this->assertSame(82, $analysis->score);
        $this->assertNull($analysis->error_message);
    }

    public function test_analyze_marks_repository_failed_on_api_error(): void
    {
        [$user, $repo] = $this->setupUser();

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([], 429),
        ]);

        $response = $this->actingAs($user)
            ->post(route('repositories.analyze', $repo));

        $response->assertRedirect(route('repositories.show', $repo));
        $response->assertSessionHas('info');

        $repo->refresh();
        $this->assertTrue($repo->hasFailed());
        $this->assertNull($repo->ai_analyzed_at);
        $this->assertNull($repo->ai_analysis);

        $analysis = Analysis::where('repository_id', $repo->id)->first();
        $this->assertNotNull($analysis);
        $this->assertSame('failed', $analysis->status);
        $this->assertNotNull($analysis->error_message);
    }

    public function test_duplicate_analysis_while_running_is_blocked(): void
    {
        [$user, $repo] = $this->setupUser();

        $repo->update([
            'analysis_status'      => 'processing',
            'analysis_started_at'  => now(),
        ]);

        Http::fake();

        $response = $this->actingAs($user)
            ->post(route('repositories.analyze', $repo));

        $response->assertSessionHas('info', 'Analysis is already in progress.');

        $this->assertSame(0, Analysis::where('repository_id', $repo->id)->count());
    }

    public function test_stale_processing_can_be_retried(): void
    {
        [$user, $repo] = $this->setupUser();

        $repo->update([
            'analysis_status'      => 'processing',
            'analysis_started_at'  => now()->subMinutes(10),
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'score' => 60,
                    'strengths' => [],
                    'weaknesses' => [],
                    'recommendations' => [],
                ])]]],
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->post(route('repositories.analyze', $repo));

        $repo->refresh();
        $this->assertSame('completed', $repo->analysis_status);
        $this->assertSame(60, $repo->ai_analysis['score']);
    }

    public function test_analysis_status_endpoint_returns_json(): void
    {
        [$user, $repo] = $this->setupUser();

        $repo->update([
            'analysis_status' => 'processing',
            'analysis_started_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('repositories.analysis-status', $repo));

        $response->assertOk();
        $response->assertJson([
            'status'       => 'processing',
            'is_analyzing' => true,
            'is_analyzed'  => false,
            'has_failed'   => false,
        ]);
    }

    public function test_unanalyzed_repo_cannot_be_exported(): void
    {
        [$user, $repo] = $this->setupUser();

        $this->actingAs($user)
            ->get(route('repositories.export.json', $repo))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('repositories.export.markdown', $repo))
            ->assertNotFound();
    }

    public function test_analyze_uses_gemini_when_api_key_is_set(): void
    {
        config(['gemini.api_key' => 'test-gemini-key', 'gemini.models' => ['gemini-2.5-flash']]);

        [$user, $repo] = $this->setupUser();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode([
                        'score' => 88,
                        'strengths' => ['Good structure'],
                        'weaknesses' => [],
                        'recommendations' => [],
                    ])]]]],
                ],
            ], 200),
        ]);

        $this->actingAs($user)
            ->post(route('repositories.analyze', $repo))
            ->assertRedirect(route('repositories.show', $repo));

        $repo->refresh();
        $this->assertSame('completed', $repo->analysis_status);
        $this->assertSame(88, $repo->ai_analysis['score']);

        $analysis = Analysis::where('repository_id', $repo->id)->first();
        $this->assertSame('gemini/gemini-2.5-flash', $analysis->model_used);
    }
}
