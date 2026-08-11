<?php

namespace Tests\Unit;

use App\Models\Repository;
use App\Services\AiAnalysisService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAnalysisServiceTest extends TestCase
{
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
            'topics'      => ['php'],
            'readme'      => '# Demo',
        ]);
    }

    public function test_uses_gemini_when_api_key_is_set(): void
    {
        config([
            'gemini.api_key'  => 'test-gemini-key',
            'gemini.models'   => ['gemini-2.5-flash'],
            'openrouter.api_key' => '',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode([
                        'score' => 90,
                        'strengths' => [],
                        'weaknesses' => [],
                        'recommendations' => [],
                    ])]]]],
                ],
            ], 200),
        ]);

        $service = app(AiAnalysisService::class);

        $this->assertSame('gemini', $service->providerName());

        $result = $service->analyzeRepository($this->repository());

        $this->assertSame(90, $result['score']);
        $this->assertSame('gemini/gemini-2.5-flash', $result['_model_used']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'generativelanguage.googleapis.com'));
    }

    public function test_falls_back_to_openrouter_when_gemini_key_missing(): void
    {
        config([
            'gemini.api_key'        => '',
            'openrouter.api_key'    => 'sk-test-key',
            'openrouter.verify_models' => false,
            'openrouter.models'     => ['google/gemma-3-27b-it:free'],
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'score' => 75,
                    'strengths' => [],
                    'weaknesses' => [],
                    'recommendations' => [],
                ])]]],
            ], 200),
        ]);

        $service = app(AiAnalysisService::class);

        $this->assertSame('openrouter', $service->providerName());

        $result = $service->analyzeRepository($this->repository());

        $this->assertSame(75, $result['score']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'openrouter.ai'));
    }
}
