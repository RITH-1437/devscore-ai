<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Repository;
use Illuminate\Support\Facades\Log;

/**
 * Routes AI analysis to the configured provider.
 *
 * When GEMINI_API_KEY is set, uses direct Google Gemini API.
 * Otherwise falls back to OpenRouter.
 */
class AiAnalysisService
{
    public function __construct(
        private readonly GoogleGeminiService $gemini,
        private readonly OpenRouterService $openRouter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function analyzeRepository(Repository $repository, ?float $timingStart = null, string $timingLabel = ''): array
    {
        if ($this->usesGemini()) {
            Log::debug('AiAnalysisService: using Google Gemini provider');

            return $this->gemini->analyzeRepository($repository, $timingStart, $timingLabel);
        }

        Log::debug('AiAnalysisService: using OpenRouter provider');

        return $this->openRouter->analyzeRepository($repository, $timingStart, $timingLabel);
    }

    public function providerName(): string
    {
        return $this->usesGemini() ? 'gemini' : 'openrouter';
    }

    private function usesGemini(): bool
    {
        return (string) config('gemini.api_key', '') !== '';
    }
}
