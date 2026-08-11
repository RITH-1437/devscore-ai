<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AnalysisException;
use App\Models\Repository;
use App\Services\Concerns\BuildsAnalysisPrompts;
use App\Services\Concerns\ParsesAndValidatesAnalysisResponses;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Direct Google Gemini API service for repository analysis.
 *
 * Uses the same prompt structure, JSON parsing, and validation as OpenRouterService
 * so analysis results remain consistent regardless of provider.
 */
class GoogleGeminiService
{
    use BuildsAnalysisPrompts;
    use ParsesAndValidatesAnalysisResponses;

    /** @var list<string> */
    private array $models;

    private string $apiKey;
    private int $timeout;
    private int $connectTimeout;
    private int $maxRetries;
    private string $baseUrl;
    private float $temperature;
    private int $maxTokens;

    public function __construct()
    {
        $this->apiKey         = (string) config('gemini.api_key', '');
        $this->timeout        = (int) config('gemini.timeout', 45);
        $this->connectTimeout = (int) config('gemini.connect_timeout', 10);
        $this->maxRetries     = (int) config('gemini.retry_times', 0);
        $this->baseUrl        = (string) config('gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        $this->temperature    = (float) config('gemini.temperature', 0.2);
        $this->maxTokens      = (int) config('gemini.max_tokens', 2048);
        $this->maxReadmeChars      = (int) config('gemini.payload.max_readme_chars', 3000);
        $this->maxDescriptionChars = (int) config('gemini.payload.max_description_chars', 500);
        $this->maxPromptChars      = (int) config('gemini.payload.max_prompt_chars', 10000);

        $this->models = array_values(array_unique(array_filter(
            (array) config('gemini.models', ['gemini-2.5-flash']),
            fn (string $model) => $model !== ''
        )));
    }

    /**
     * @return array<string, mixed>
     *
     * @throws AnalysisException
     */
    public function analyzeRepository(Repository $repository, ?float $timingStart = null, string $timingLabel = ''): array
    {
        $requestId = $this->generateRequestId();
        $timingStart ??= microtime(true);
        if ($timingLabel === '') {
            $timingLabel = "[repo:{$repository->id}/{$repository->name}]";
        }

        Log::debug("{$timingLabel} [PROMPT] building prompt +{$this->timingMs($timingStart)}ms");
        $prompt = $this->buildRepositoryPrompt($repository);

        Log::debug("{$timingLabel} [PROMPT] built +{$this->timingMs($timingStart)}ms", [
            'request_id'   => $requestId,
            'repository'   => $repository->name,
            'prompt_chars' => strlen($prompt),
            'provider'     => 'gemini',
        ]);

        [$result, $modelUsed, $rawResponse, $tokens] = $this->callWithFallback($requestId, $prompt, $timingStart, $timingLabel);

        return array_merge($result, [
            '_model_used'        => $modelUsed,
            '_request_id'        => $requestId,
            '_raw_response'      => $rawResponse,
            '_prompt_tokens'     => $tokens['prompt_tokens'] ?? 0,
            '_completion_tokens' => $tokens['completion_tokens'] ?? 0,
            '_total_tokens'      => $tokens['total_tokens'] ?? 0,
        ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: string, 2: string, 3: array<string, int>}
     *
     * @throws AnalysisException
     */
    private function callWithFallback(string $requestId, string $prompt, ?float $timingStart = null, string $timingLabel = ''): array
    {
        $timingStart ??= microtime(true);

        if ($this->apiKey === '') {
            throw new AnalysisException(
                'GEMINI_API_KEY is not configured.',
                AnalysisException::AI_CONFIGURATION_ERROR
            );
        }

        if ($this->models === []) {
            throw new AnalysisException(
                'No Gemini models are configured.',
                AnalysisException::AI_NO_MODELS_AVAILABLE
            );
        }

        $lastType    = AnalysisException::AI_UNKNOWN_ERROR;
        $lastMessage = 'No Gemini models available.';
        $rawResponse = '';

        foreach ($this->models as $model) {
            Log::debug("{$timingLabel} [AI] gemini trying model +{$this->timingMs($timingStart)}ms", [
                'request_id' => $requestId,
                'model'      => $model,
            ]);

            try {
                [$raw, $tokens] = $this->callModelWithRetry($requestId, $model, $prompt, $timingStart, $timingLabel);
                $rawResponse = $raw;

                if ($raw === '') {
                    $lastType    = AnalysisException::AI_EMPTY_RESPONSE;
                    $lastMessage = "Empty response from Gemini model: {$model}";
                    continue;
                }

                $parsed = $this->parseJson($raw, 'Gemini');

                if ($parsed === null) {
                    $lastType    = AnalysisException::AI_PARSE_ERROR;
                    $lastMessage = "Invalid JSON from Gemini model: {$model}";
                    continue;
                }

                $validated = $this->validateResult($parsed);

                Log::debug("{$timingLabel} [AI] gemini response received +{$this->timingMs($timingStart)}ms", [
                    'request_id'     => $requestId,
                    'model'          => $model,
                    'tokens_used'    => $tokens['total_tokens'] ?? 0,
                    'response_chars' => strlen($raw),
                ]);

                return [$validated, "gemini/{$model}", $raw, $tokens];
            } catch (AnalysisException $e) {
                $lastType    = $e->errorType;
                $lastMessage = $e->getMessage();

                Log::warning("{$timingLabel} [AI] gemini model failed +{$this->timingMs($timingStart)}ms", [
                    'request_id' => $requestId,
                    'model'      => $model,
                    'error_type' => $e->errorType,
                    'error'      => $lastMessage,
                ]);
            }
        }

        throw new AnalysisException($lastMessage, $lastType);
    }

    /**
     * @return array{0: string, 1: array<string, int>}
     *
     * @throws AnalysisException
     */
    private function callModelWithRetry(string $requestId, string $model, string $prompt, ?float $timingStart = null, string $timingLabel = ''): array
    {
        $timingStart ??= microtime(true);
        $attempt       = 0;
        $lastException = null;

        while ($attempt < max(1, $this->maxRetries + 1)) {
            $attempt++;

            try {
                return $this->callModel($requestId, $model, $prompt, $timingStart, $timingLabel);
            } catch (AnalysisException $e) {
                $lastException = $e;

                if (in_array($e->errorType, [
                    AnalysisException::AI_RATE_LIMIT,
                    AnalysisException::AI_MODEL_UNAVAILABLE,
                    AnalysisException::AI_AUTH_ERROR,
                    AnalysisException::AI_TIMEOUT,
                    AnalysisException::AI_CONFIGURATION_ERROR,
                    AnalysisException::AI_NETWORK_ERROR,
                ], true)) {
                    throw $e;
                }

                if ($attempt <= $this->maxRetries) {
                    sleep((int) pow(2, $attempt - 1));
                }
            }
        }

        throw $lastException ?? new AnalysisException('Max retries exceeded', AnalysisException::AI_UNKNOWN_ERROR);
    }

    /**
     * @return array{0: string, 1: array<string, int>}
     *
     * @throws AnalysisException
     */
    private function callModel(string $requestId, string $model, string $prompt, ?float $timingStart = null, string $timingLabel = ''): array
    {
        $timingStart ??= microtime(true);
        $url = "{$this->baseUrl}/models/{$model}:generateContent?key=" . urlencode($this->apiKey);

        Log::debug("{$timingLabel} [AI] gemini request started +{$this->timingMs($timingStart)}ms", [
            'request_id'        => $requestId,
            'model'             => $model,
            'prompt_chars'      => strlen($prompt),
            'timeout_s'         => $this->timeout,
            'connect_timeout_s' => $this->connectTimeout,
        ]);

        try {
            $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->connectTimeout($this->connectTimeout)
                ->timeout($this->timeout)
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature'     => $this->temperature,
                        'maxOutputTokens' => $this->maxTokens,
                        'responseMimeType'  => 'application/json',
                    ],
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $isTimeout = str_contains($e->getMessage(), 'timed out')
                || str_contains($e->getMessage(), 'timeout');

            throw new AnalysisException(
                "Connection to Gemini failed for model {$model}: " . $e->getMessage(),
                $isTimeout ? AnalysisException::AI_TIMEOUT : AnalysisException::AI_NETWORK_ERROR,
                0,
                $e
            );
        } catch (\Throwable $e) {
            throw new AnalysisException(
                "Unexpected error calling Gemini model {$model}: " . $e->getMessage(),
                AnalysisException::AI_UNKNOWN_ERROR,
                0,
                $e
            );
        }

        return $this->handleResponse($response, $requestId, $model, $timingStart, $timingLabel);
    }

    /**
     * @return array{0: string, 1: array<string, int>}
     *
     * @throws AnalysisException
     */
    private function handleResponse(
        \Illuminate\Http\Client\Response $response,
        string $requestId,
        string $model,
        ?float $timingStart = null,
        string $timingLabel = ''
    ): array {
        $timingStart ??= microtime(true);
        $status = $response->status();

        if ($status === 429) {
            throw new AnalysisException(
                "Rate limited on Gemini model: {$model}",
                AnalysisException::AI_RATE_LIMIT,
                $status
            );
        }

        if (in_array($status, [401, 403], true)) {
            throw new AnalysisException(
                'Invalid Gemini API key',
                AnalysisException::AI_AUTH_ERROR,
                $status
            );
        }

        if ($status === 404) {
            throw new AnalysisException(
                "Gemini model not available: {$model}",
                AnalysisException::AI_MODEL_UNAVAILABLE,
                $status
            );
        }

        if ($status >= 500) {
            throw new AnalysisException(
                "HTTP {$status} from Gemini for model {$model}",
                AnalysisException::AI_SERVER_ERROR,
                $status
            );
        }

        if (! $response->successful()) {
            throw new AnalysisException(
                "HTTP {$status} from Gemini for model {$model}",
                AnalysisException::AI_UNKNOWN_ERROR,
                $status
            );
        }

        $responseData = $response->json();

        if (! isset($responseData['candidates']) || ! is_array($responseData['candidates']) || $responseData['candidates'] === []) {
            throw new AnalysisException(
                "Empty or invalid response from Gemini model {$model}",
                AnalysisException::AI_EMPTY_RESPONSE,
                $status
            );
        }

        $parts = $responseData['candidates'][0]['content']['parts'] ?? [];
        $content = '';

        foreach ($parts as $part) {
            if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                $content .= $part['text'];
            }
        }

        if ($content === '') {
            throw new AnalysisException(
                "Empty content from Gemini model {$model}",
                AnalysisException::AI_EMPTY_RESPONSE,
                $status
            );
        }

        $usage = $responseData['usageMetadata'] ?? [];

        Log::debug("{$timingLabel} [AI] gemini response parsed +{$this->timingMs($timingStart)}ms", [
            'request_id'     => $requestId,
            'model'          => $model,
            'content_length' => strlen($content),
        ]);

        return [$content, [
            'prompt_tokens'     => (int) ($usage['promptTokenCount'] ?? 0),
            'completion_tokens' => (int) ($usage['candidatesTokenCount'] ?? 0),
            'total_tokens'      => (int) ($usage['totalTokenCount'] ?? 0),
        ]];
    }

    private function generateRequestId(): string
    {
        return 'gem_' . Str::random(16);
    }

    private function timingMs(float $start): string
    {
        return number_format((microtime(true) - $start) * 1000, 1, '.', '');
    }
}
