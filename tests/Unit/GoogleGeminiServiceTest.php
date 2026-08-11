<?php

namespace Tests\Unit;

use App\Exceptions\AnalysisException;
use App\Models\Repository;
use App\Services\GoogleGeminiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleGeminiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'gemini.api_key'         => 'test-gemini-key',
            'gemini.base_url'        => 'https://generativelanguage.googleapis.com/v1beta',
            'gemini.timeout'         => 2,
            'gemini.connect_timeout' => 1,
            'gemini.retry_times'     => 0,
            'gemini.models'          => ['gemini-2.5-flash'],
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

    private function fakeGeminiResponse(array $json, int $status = 200): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode($json)],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => [
                    'promptTokenCount'     => 10,
                    'candidatesTokenCount' => 20,
                    'totalTokenCount'      => 30,
                ],
            ], $status),
        ]);
    }

    public function test_success_returns_validated_and_normalized_result(): void
    {
        $this->fakeGeminiResponse([
            'score'                => 85.6,
            'difficulty'           => 'intermediate',
            'portfolio_level'      => 'mid',
            'recruiter_rating'     => 8,
            'estimated_experience' => '1-3 years',
            'hiring_probability'   => 72,
            'market_readiness'     => 'ready',
            'strengths'            => ['Strong tests', 'Clean architecture'],
            'weaknesses'           => 'No CI',
            'recommendations'      => ['Add CI'],
        ]);

        $result = app(GoogleGeminiService::class)->analyzeRepository($this->repository());

        $this->assertSame(86, $result['score']);
        $this->assertSame(['No CI'], $result['weaknesses']);
        $this->assertSame('gemini/gemini-2.5-flash', $result['_model_used']);
        $this->assertSame(30, $result['_total_tokens']);
    }

    public function test_429_throws_rate_limit(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([], 429),
        ]);

        try {
            app(GoogleGeminiService::class)->analyzeRepository($this->repository());
            $this->fail('Expected AnalysisException');
        } catch (AnalysisException $e) {
            $this->assertSame(AnalysisException::AI_RATE_LIMIT, $e->errorType);
        }
    }

    public function test_403_throws_permission_error(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([], 403),
        ]);

        try {
            app(GoogleGeminiService::class)->analyzeRepository($this->repository());
            $this->fail('Expected AnalysisException');
        } catch (AnalysisException $e) {
            $this->assertSame(AnalysisException::AI_PERMISSION_ERROR, $e->errorType);
        }
    }

    public function test_invalid_json_throws_parse_error(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'not json']]]],
                ],
            ], 200),
        ]);

        try {
            app(GoogleGeminiService::class)->analyzeRepository($this->repository());
            $this->fail('Expected AnalysisException');
        } catch (AnalysisException $e) {
            $this->assertSame(AnalysisException::AI_PARSE_ERROR, $e->errorType);
        }
    }

    public function test_missing_api_key_throws_configuration_error(): void
    {
        config(['gemini.api_key' => '']);

        try {
            app(GoogleGeminiService::class)->analyzeRepository($this->repository());
            $this->fail('Expected AnalysisException');
        } catch (AnalysisException $e) {
            $this->assertSame(AnalysisException::AI_CONFIGURATION_ERROR, $e->errorType);
        }
    }
}
