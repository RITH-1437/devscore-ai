<?php

namespace Tests\Unit;

use App\Exceptions\AnalysisException;
use App\Models\Repository;
use App\Services\OpenRouterService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenRouterServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'openrouter.api_key'        => 'sk-test-key',
            'openrouter.base_url'       => 'https://openrouter.ai/api/v1',
            'openrouter.timeout'        => 2,
            'openrouter.connect_timeout'=> 1,
            'openrouter.retry_times'    => 0,
            'openrouter.total_budget'   => 5,
            'openrouter.verify_models'  => true,
            'openrouter.models'         => ['openai/gpt-4o-mini:free', 'google/gemma-3-27b-it:free'],
        ]);

        Cache::flush();

        // Default: model catalog is healthy.
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    ['id' => 'openai/gpt-4o-mini:free'],
                    ['id' => 'google/gemma-3-27b-it:free'],
                ],
            ], 200),
        ]);
    }

    private function repository(): Repository
    {
        return new Repository([
            'name'        => 'demo',
            'full_name'   => 'user/demo',
            'description' => 'A demo repository',
            'language'    => 'PHP',
            'stars'       => 5,
            'forks'       => 1,
            'open_issues' => 0,
            'watchers'    => 1,
            'size'        => 100,
            'topics'      => ['php', 'laravel'],
            'readme'      => '# Demo',
        ]);
    }

    private function fakeCompletion(array $response, int $status = 200): void
    {
        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response($response, $status),
        ]);
    }

    public function test_success_returns_validated_and_normalized_result(): void
    {
        $this->fakeCompletion([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'score'                => 85.6,
                    'difficulty'           => 'intermediate',
                    'portfolio_level'      => 'mid',
                    'recruiter_rating'     => 8,
                    'estimated_experience' => '1-3 years',
                    'hiring_probability'   => 72,
                    'market_readiness'     => 'ready',
                    'strengths'            => ['Strong tests', 'Clean architecture'],
                    'weaknesses'           => 'No CI', // string should be normalized to array
                    'recommendations'      => ['Add CI'],
                ])]],
            ],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20, 'total_tokens' => 30],
        ]);

        $result = app(OpenRouterService::class)->analyzeRepository($this->repository());

        $this->assertSame(86, $result['score']);
        $this->assertSame(['No CI'], $result['weaknesses']);
        $this->assertSame('intermediate', $result['difficulty']);
        $this->assertSame('openai/gpt-4o-mini:free', $result['_model_used']);
        $this->assertSame(30, $result['_total_tokens']);
    }

    public function test_score_is_clamped_to_0_100(): void
    {
        $this->fakeCompletion([
            'choices' => [['message' => ['content' => json_encode([
                'score' => 150,
                'strengths' => [],
                'weaknesses' => [],
                'recommendations' => [],
            ])]]],
        ]);

        $result = app(OpenRouterService::class)->analyzeRepository($this->repository());

        $this->assertSame(100, $result['score']);
    }

    public function test_404_model_throws_model_unavailable(): void
    {
        $this->fakeCompletion([], 404);

        $this->expectException(AnalysisException::class);
        $this->expectExceptionCode(0);

        try {
            app(OpenRouterService::class)->analyzeRepository($this->repository());
        } catch (AnalysisException $e) {
            $this->assertSame(AnalysisException::MODEL_UNAVAILABLE, $e->errorType);
            throw $e;
        }
    }

    public function test_429_throws_rate_limit(): void
    {
        $this->fakeCompletion([], 429);

        try {
            app(OpenRouterService::class)->analyzeRepository($this->repository());
            $this->fail('Expected AnalysisException');
        } catch (AnalysisException $e) {
            $this->assertSame(AnalysisException::RATE_LIMIT, $e->errorType);
        }
    }

    public function test_500_throws_server_error(): void
    {
        $this->fakeCompletion([], 500);

        try {
            app(OpenRouterService::class)->analyzeRepository($this->repository());
            $this->fail('Expected AnalysisException');
        } catch (AnalysisException $e) {
            $this->assertSame(AnalysisException::SERVER_ERROR, $e->errorType);
        }
    }

    public function test_missing_score_throws_invalid_response(): void
    {
        $this->fakeCompletion([
            'choices' => [['message' => ['content' => json_encode([
                'strengths' => [],
                'weaknesses' => [],
                'recommendations' => [],
            ])]]],
        ]);

        try {
            app(OpenRouterService::class)->analyzeRepository($this->repository());
            $this->fail('Expected AnalysisException');
        } catch (AnalysisException $e) {
            $this->assertSame(AnalysisException::INVALID_RESPONSE, $e->errorType);
        }
    }

    public function test_invalid_json_throws_invalid_response(): void
    {
        $this->fakeCompletion([
            'choices' => [['message' => ['content' => 'definitely not json']]],
        ]);

        try {
            app(OpenRouterService::class)->analyzeRepository($this->repository());
            $this->fail('Expected AnalysisException');
        } catch (AnalysisException $e) {
            $this->assertSame(AnalysisException::INVALID_RESPONSE, $e->errorType);
        }
    }

    public function test_unavailable_models_are_skipped_via_catalog(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response([
                'data' => [
                    ['id' => 'openai/gpt-4o-mini:free'],
                ],
            ], 200),
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'score' => 70,
                    'strengths' => [],
                    'weaknesses' => [],
                    'recommendations' => [],
                ])]]],
            ], 200),
        ]);

        $result = app(OpenRouterService::class)->analyzeRepository($this->repository());

        $this->assertSame(70, $result['score']);
        $this->assertSame('openai/gpt-4o-mini:free', $result['_model_used']);
    }
}
