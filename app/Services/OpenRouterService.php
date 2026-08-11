<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AnalysisException;
use App\Models\Repository;
use App\Services\Concerns\BuildsAnalysisPrompts;
use App\Services\Concerns\ParsesAndValidatesAnalysisResponses;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * OpenRouter AI Service.
 *
 * Responsibilities:
 *  - Bounded HTTP requests (connect + request timeouts) so a request never
 *    hangs indefinitely.
 *  - Model availability pre-check (cached) before requesting a model.
 *  - Exponential backoff retries with a hard overall time budget.
 *  - Explicit handling of rate limits, unavailable models, provider errors,
 *    timeouts, empty responses and invalid JSON.
 *  - Strict validation and normalization of the AI response BEFORE it is
 *    handed back to the caller. On any failure an AnalysisException is thrown
 *    — a fake score-0 result is never produced.
 */
class OpenRouterService
{
    use BuildsAnalysisPrompts;
    use ParsesAndValidatesAnalysisResponses;

    /** @var list<string> */
    private array $models;

    private string $apiKey;
    private int $timeout;
    private int $connectTimeout;
    private int $maxRetries;
    private int $totalBudget;
    private string $baseUrl;
    private float $temperature;
    private int $maxTokens;
    private bool $verifyModels;

    public function __construct()
    {
        $this->apiKey        = (string) config('openrouter.api_key', '');
        $this->timeout       = (int) config('openrouter.timeout', 45);
        $this->connectTimeout= (int) config('openrouter.connect_timeout', 10);
        $this->maxRetries    = (int) config('openrouter.retry_times', 1);
        $this->totalBudget   = (int) config('openrouter.total_budget', 180);
        $this->baseUrl       = (string) config('openrouter.base_url', 'https://openrouter.ai/api/v1');
        $this->temperature   = (float) config('openrouter.temperature', 0.2);
        $this->maxTokens     = (int) config('openrouter.max_tokens', 2048);
        $this->verifyModels  = (bool) config('openrouter.verify_models', false);
        $this->maxReadmeChars      = (int) config('openrouter.payload.max_readme_chars', 3000);
        $this->maxDescriptionChars = (int) config('openrouter.payload.max_description_chars', 500);
        $this->maxPromptChars      = (int) config('openrouter.payload.max_prompt_chars', 10000);

        // Build the model chain:
        $defaultModel = (string) config('openrouter.default_model', '');
        $fallbackModels = (array) config('openrouter.models', [
            'google/gemini-2.0-flash-exp:free',
            'inclusionai/ling-3.0-flash:free',
            'nvidia/nemotron-nano-9b-v2:free',
            'openai/gpt-oss-20b:free',
            'nvidia/nemotron-nano-12b-v2-vl:free',
            'openrouter/free',
        ]);

        // If OPENROUTER_MODEL is set, prepend it to the chain
        if ($defaultModel !== '' && !in_array($defaultModel, $fallbackModels, true)) {
            array_unshift($fallbackModels, $defaultModel);
        } elseif ($defaultModel !== '') {
            // Move it to the front if it's already in the list
            $fallbackModels = array_values(array_diff($fallbackModels, [$defaultModel]));
            array_unshift($fallbackModels, $defaultModel);
        }

        $this->models = $fallbackModels;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Public API
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Analyze a repository and return structured, validated data.
     *
     * @return array<string, mixed>
     * @throws AnalysisException When no model could produce a valid response.
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
     * Analyze a portfolio (collection of repositories).
     *
     * @param  \Illuminate\Support\Collection<int, Repository>  $repositories
     * @return array<string, mixed>
     * @throws AnalysisException When no model could produce a valid response.
     */
    public function analyzePortfolio(\Illuminate\Support\Collection $repositories): array
    {
        $requestId = $this->generateRequestId();
        $prompt    = $this->buildPortfolioPrompt($repositories);

        Log::info('OpenRouter: Starting portfolio analysis', [
            'request_id'   => $requestId,
            'repo_count'   => $repositories->count(),
            'prompt_chars' => strlen($prompt),
        ]);

        [$result, $modelUsed, $rawResponse, $tokens] = $this->callWithFallback($requestId, $prompt);

        return array_merge($result, [
            '_model_used'        => $modelUsed,
            '_request_id'        => $requestId,
            '_raw_response'      => $rawResponse,
            '_prompt_tokens'     => $tokens['prompt_tokens'] ?? 0,
            '_completion_tokens' => $tokens['completion_tokens'] ?? 0,
            '_total_tokens'      => $tokens['total_tokens'] ?? 0,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Core HTTP + Fallback Logic
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Try each available model (with bounded retries) until one succeeds, but
     * never spend more than the configured total budget.
     *
     * @return array{0: array<string, mixed>, 1: string, 2: string, 3: array<string, int>}
     * @throws AnalysisException When every model fails or the budget is exhausted.
     */
    private function callWithFallback(string $requestId, string $prompt, ?float $timingStart = null, string $timingLabel = ''): array
    {
        $timingStart ??= microtime(true);
        $deadline = microtime(true) + max(1, $this->totalBudget);

        $models = $this->verifyModels ? $this->filterAvailableModels($this->models) : $this->models;
        $models = array_values(array_unique($models));

        if (empty($models)) {
            throw new AnalysisException(
                'None of the configured OpenRouter models are currently available.',
                AnalysisException::AI_NO_MODELS_AVAILABLE
            );
        }

        Log::info('OpenRouter: Model fallback chain', [
            'request_id'    => $requestId,
            'models'        => $models,
            'verify_models' => $this->verifyModels,
        ]);

        $lastType    = AnalysisException::AI_UNKNOWN_ERROR;
        $lastMessage = 'No models available.';
        $rawResponse = '';

        foreach ($models as $model) {
            if (microtime(true) > $deadline) {
                Log::warning("{$timingLabel} [AI] budget exhausted +{$this->timingMs($timingStart)}ms", [
                    'request_id' => $requestId,
                    'budget'     => $this->totalBudget,
                ]);
                break;
            }

            Log::debug("{$timingLabel} [AI] model fallback trying +{$this->timingMs($timingStart)}ms", [
                'request_id' => $requestId,
                'model'      => $model,
            ]);

            try {
                [$raw, $tokens] = $this->callModelWithRetry($requestId, $model, $prompt, $timingStart, $timingLabel);
                $rawResponse = $raw;

                if (empty($raw)) {
                    $lastType    = AnalysisException::AI_EMPTY_RESPONSE;
                    $lastMessage = "Empty response from model: {$model}";
                    Log::warning('OpenRouter: Empty response', [
                        'request_id' => $requestId,
                        'model'      => $model,
                    ]);
                    continue;
                }

                $parsed = $this->parseJson($raw, 'OpenRouter');

                if ($parsed === null) {
                    $lastType    = AnalysisException::AI_PARSE_ERROR;
                    $lastMessage = "Invalid JSON from model: {$model}";
                    Log::warning('OpenRouter: Invalid JSON from model', [
                        'request_id'     => $requestId,
                        'model'          => $model,
                        'response_start' => substr($raw, 0, 200),
                    ]);
                    continue;
                }

                // Validate & normalize before returning to the caller.
                $validated = $this->validateResult($parsed);

                Log::debug("{$timingLabel} [AI] response received +{$this->timingMs($timingStart)}ms", [
                    'request_id'     => $requestId,
                    'model'          => $model,
                    'tokens_used'    => $tokens['total_tokens'] ?? 0,
                    'response_chars' => strlen($raw),
                ]);

                return [$validated, $model, $raw, $tokens];

            } catch (AnalysisException $e) {
                $lastType    = $e->errorType;
                $lastMessage = $e->getMessage();

                Log::warning("{$timingLabel} [AI] model fallback failed +{$this->timingMs($timingStart)}ms", [
                    'request_id' => $requestId,
                    'model'      => $model,
                    'error_type' => $e->errorType,
                    'error'      => $lastMessage,
                ]);

                // Do not waste budget on errors that won't succeed on retry.
                if (in_array($e->errorType, [
                    AnalysisException::AI_RATE_LIMIT,
                    AnalysisException::AI_MODEL_UNAVAILABLE,
                    AnalysisException::AI_AUTH_ERROR,
                    AnalysisException::AI_INSUFFICIENT_CREDITS,
                    AnalysisException::AI_TIMEOUT,
                    AnalysisException::AI_CONFIGURATION_ERROR,
                    AnalysisException::AI_NETWORK_ERROR,
                ], true)) {
                    continue;
                }
            } catch (\Throwable $e) {
                $lastType    = AnalysisException::AI_NETWORK_ERROR;
                $lastMessage = "Model {$model} failed: " . $e->getMessage();

                Log::warning('OpenRouter: Model threw exception, trying next', [
                    'request_id' => $requestId,
                    'model'      => $model,
                    'error_type' => get_class($e),
                    'error'      => $e->getMessage(),
                ]);

                // If this looks like a timeout, don't retry this model
                if (str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'timeout')) {
                    continue;
                }
            }
        }

        throw new AnalysisException(
            $lastMessage,
            $lastType,
        );
    }

    /**
     * Call a single model with a small number of retries and backoff.
     *
     * @return array{0: string, 1: array<string, int>}
     * @throws AnalysisException
     */
    private function callModelWithRetry(string $requestId, string $model, string $prompt, ?float $timingStart = null, string $timingLabel = ''): array
    {
        $timingStart ??= microtime(true);
        $attempt        = 0;
        $lastException  = null;

        while ($attempt < max(1, $this->maxRetries + 1)) {
            $attempt++;
            $backoffSeconds = pow(2, $attempt - 1); // 1s, 2s, 4s

            try {
                return $this->callModel($requestId, $model, $prompt, $timingStart, $timingLabel);
            } catch (AnalysisException $e) {
                $lastException = $e;

                Log::debug('OpenRouter: Retry attempt '.$attempt, [
                    'request_id' => $requestId,
                    'model'      => $model,
                    'error_type' => $e->errorType,
                    'backoff_s'  => $backoffSeconds,
                ]);

                // Some errors are pointless to retry on the same model.
                if (in_array($e->errorType, [
                    AnalysisException::AI_RATE_LIMIT,
                    AnalysisException::AI_MODEL_UNAVAILABLE,
                    AnalysisException::AI_AUTH_ERROR,
                    AnalysisException::AI_INSUFFICIENT_CREDITS,
                    AnalysisException::AI_TIMEOUT,
                    AnalysisException::AI_CONFIGURATION_ERROR,
                    AnalysisException::AI_NETWORK_ERROR,
                ], true)) {
                    throw $e;
                }

                if ($attempt <= $this->maxRetries) {
                    sleep($backoffSeconds);
                }
            }
        }

        throw $lastException ?? new AnalysisException('Max retries exceeded', AnalysisException::AI_UNKNOWN_ERROR);
    }

    /**
     * Make a single API call to OpenRouter.
     *
     * @return array{0: string, 1: array<string, int>}
     * @throws AnalysisException
     */
    private function callModel(string $requestId, string $model, string $prompt, ?float $timingStart = null, string $timingLabel = ''): array
    {
        $timingStart ??= microtime(true);
        if ($this->apiKey === '') {
            throw new AnalysisException(
                'OPENROUTER_API_KEY is not configured.',
                AnalysisException::AI_CONFIGURATION_ERROR
            );
        }

        Log::debug("{$timingLabel} [AI] request started +{$this->timingMs($timingStart)}ms", [
            'request_id'    => $requestId,
            'model'         => $model,
            'prompt_chars'  => strlen($prompt),
            'timeout_s'     => $this->timeout,
            'connect_timeout_s' => $this->connectTimeout,
        ]);

        try {
            $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'HTTP-Referer'  => config('app.url'),
                    'X-Title'       => config('app.name', 'GitRadar'),
                    'X-Request-ID'  => $requestId,
                ])
                ->connectTimeout($this->connectTimeout)
                ->timeout($this->timeout)
                ->post($this->baseUrl . '/chat/completions', [
                    'model'       => $model,
                    'messages'    => [
                        [
                            'role'    => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => $this->temperature,
                    'max_tokens'  => $this->maxTokens,
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $isTimeout = str_contains($e->getMessage(), 'timed out')
                || str_contains($e->getMessage(), 'timeout');

            Log::error("{$timingLabel} [AI] connection failed +{$this->timingMs($timingStart)}ms", [
                'request_id' => $requestId,
                'model'      => $model,
                'error'      => $e->getMessage(),
                'is_timeout' => $isTimeout,
            ]);

            throw new AnalysisException(
                "Connection to OpenRouter failed for model {$model}: " . $e->getMessage(),
                $isTimeout ? AnalysisException::AI_TIMEOUT : AnalysisException::AI_NETWORK_ERROR,
                0,
                $e
            );
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $status = $e->response?->status() ?? 0;

            Log::error('OpenRouter: HTTP request exception', [
                'request_id' => $requestId,
                'model'      => $model,
                'status'     => $status,
                'error'      => $e->getMessage(),
                'response_body' => $e->response?->body() ? substr($e->response->body(), 0, 500) : null,
            ]);

            // Try to categorize the error type from the exception
            if (str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'timeout')) {
                throw new AnalysisException(
                    "Request timeout for model {$model}: " . $e->getMessage(),
                    AnalysisException::AI_TIMEOUT,
                    $status,
                    $e
                );
            }

            throw new AnalysisException(
                "HTTP request failed for model {$model}: " . $e->getMessage(),
                AnalysisException::AI_NETWORK_ERROR,
                $status,
                $e
            );
        } catch (\Throwable $e) {
            Log::error('OpenRouter: Unexpected error during HTTP request', [
                'request_id' => $requestId,
                'model'      => $model,
                'error_type' => get_class($e),
                'error'      => $e->getMessage(),
            ]);

            throw new AnalysisException(
                "Unexpected error calling model {$model}: " . $e->getMessage(),
                AnalysisException::AI_UNKNOWN_ERROR,
                0,
                $e
            );
        }

        $status = $response->status();

        Log::debug('OpenRouter: Received response', [
            'request_id' => $requestId,
            'model'      => $model,
            'status'     => $status,
            'response_length' => strlen($response->body()),
            'headers'    => $response->headers(),
        ]);

        if ($status === 429) {
            $errorBody = substr($response->body(), 0, 500);
            Log::warning('OpenRouter: Rate limited', [
                'request_id' => $requestId,
                'model'      => $model,
                'status'     => $status,
                'response'   => $errorBody,
            ]);

            throw new AnalysisException(
                "Rate limited on model: {$model}",
                AnalysisException::AI_RATE_LIMIT,
                $status
            );
        }

        if ($status === 401) {
            Log::error('OpenRouter: Authentication failed', [
                'request_id' => $requestId,
                'model'      => $model,
                'status'     => $status,
            ]);

            throw new AnalysisException(
                'Invalid API key',
                AnalysisException::AI_AUTH_ERROR,
                $status
            );
        }

        if ($status === 402) {
            Log::error('OpenRouter: Insufficient credits', [
                'request_id' => $requestId,
                'model'      => $model,
                'status'     => $status,
                'response'   => substr($response->body(), 0, 500),
            ]);

            throw new AnalysisException(
                "Insufficient credits for model: {$model}",
                AnalysisException::AI_INSUFFICIENT_CREDITS,
                $status
            );
        }

        // 404 usually means the model slug is not served by OpenRouter.
        if ($status === 404) {
            Log::warning('OpenRouter: Model not found', [
                'request_id' => $requestId,
                'model'      => $model,
                'status'     => $status,
                'response'   => substr($response->body(), 0, 500),
            ]);

            throw new AnalysisException(
                "Model not available: {$model}",
                AnalysisException::AI_MODEL_UNAVAILABLE,
                $status
            );
        }

        if ($status >= 500) {
            $errorBody = substr($response->body(), 0, 500);
            Log::error('OpenRouter: Server error', [
                'request_id' => $requestId,
                'model'      => $model,
                'status'     => $status,
                'response'   => $errorBody,
            ]);

            throw new AnalysisException(
                "HTTP {$status} from OpenRouter for model {$model}: " . $errorBody,
                AnalysisException::AI_SERVER_ERROR,
                $status
            );
        }

        if (! $response->successful()) {
            $errorBody = substr($response->body(), 0, 500);
            Log::error('OpenRouter: HTTP error', [
                'request_id' => $requestId,
                'model'      => $model,
                'status'     => $status,
                'response'   => $errorBody,
            ]);

            throw new AnalysisException(
                "HTTP {$status} from OpenRouter for model {$model}: " . $errorBody,
                AnalysisException::AI_UNKNOWN_ERROR,
                $status
            );
        }

        $responseData = $response->json();

        // Log the full response structure for debugging
        Log::debug('OpenRouter: Response structure', [
            'request_id'     => $requestId,
            'model'          => $model,
            'has_choices'    => isset($responseData['choices']),
            'choices_count'  => isset($responseData['choices']) ? count($responseData['choices']) : 0,
            'has_usage'      => isset($responseData['usage']),
            'response_keys'  => array_keys($responseData ?? []),
        ]);

        // Validate response structure
        if (!isset($responseData['choices']) || !is_array($responseData['choices'])) {
            Log::error('OpenRouter: Invalid response structure - missing choices array', [
                'request_id' => $requestId,
                'model'      => $model,
                'response'   => substr($response->body(), 0, 1000),
            ]);

            throw new AnalysisException(
                "Invalid response structure from model {$model}: missing 'choices' array",
                AnalysisException::AI_INVALID_RESPONSE,
                $status
            );
        }

        if (empty($responseData['choices'])) {
            Log::error('OpenRouter: Empty choices array', [
                'request_id' => $requestId,
                'model'      => $model,
                'response'   => substr($response->body(), 0, 1000),
            ]);

            throw new AnalysisException(
                "Empty choices array from model {$model}",
                AnalysisException::AI_EMPTY_RESPONSE,
                $status
            );
        }

        $firstChoice = $responseData['choices'][0] ?? null;
        if (!$firstChoice || !isset($firstChoice['message'])) {
            Log::error('OpenRouter: Invalid choice structure', [
                'request_id' => $requestId,
                'model'      => $model,
                'choice'     => $firstChoice,
            ]);

            throw new AnalysisException(
                "Invalid choice structure from model {$model}: missing 'message'",
                AnalysisException::AI_INVALID_RESPONSE,
                $status
            );
        }

        $content = $this->extractContent($firstChoice['message']['content'] ?? null);

        if (empty($content)) {
            Log::error('OpenRouter: Empty content in response', [
                'request_id'   => $requestId,
                'model'        => $model,
                'message'      => $firstChoice['message'] ?? null,
            ]);

            throw new AnalysisException(
                "Empty content from model {$model}",
                AnalysisException::AI_EMPTY_RESPONSE,
                $status
            );
        }

        Log::debug('OpenRouter: Extracted content', [
            'request_id'     => $requestId,
            'model'          => $model,
            'content_length' => strlen($content),
            'content_start'  => substr($content, 0, 200),
        ]);

        $usage   = $responseData['usage'] ?? [];

        $tokens = [
            'prompt_tokens'     => (int) ($usage['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
            'total_tokens'      => (int) ($usage['total_tokens'] ?? 0),
        ];

        return [$content, $tokens];
    }

    /**
     * Normalize the message content (string vs. multi-part array) to a string.
     */
    private function extractContent(mixed $content): string
    {
        if (is_string($content)) {
            return $content;
        }

        if (! is_array($content)) {
            return '';
        }

        $parts = [];
        foreach ($content as $part) {
            if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                $parts[] = $part['text'];
            } elseif (is_string($part)) {
                $parts[] = $part;
            }
        }

        return implode('', $parts);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Model Availability
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Filter the configured model chain down to models currently served by
     * OpenRouter. Fails open: if the model catalog cannot be fetched, the
     * full chain is returned so analysis is not blocked.
     *
     * @param  list<string>  $models
     * @return list<string>
     */
    private function filterAvailableModels(array $models): array
    {
        $available = $this->fetchAvailableModels();

        if ($available === null) {
            return $models;
        }

        $filtered = array_values(array_filter(
            $models,
            fn (string $model) => in_array($model, $available, true)
        ));

        // If the catalog is stale and none of our models match, fall back to
        // the configured chain rather than failing silently.
        return $filtered !== [] ? $filtered : $models;
    }

    /**
     * Fetch the list of available model IDs, cached for an hour.
     *
     * @return list<string>|null  null when the catalog could not be fetched.
     */
    private function fetchAvailableModels(): ?array
    {
        if ($this->apiKey === '') {
            return null;
        }

        return Cache::remember('openrouter.models', 3600, function () {
            try {
                $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                    ])
                    ->connectTimeout(5)
                    ->timeout(10)
                    ->get($this->baseUrl . '/models');

                if (! $response->successful()) {
                    Log::warning('OpenRouter: Could not fetch model catalog.', [
                        'status' => $response->status(),
                    ]);
                    return null;
                }

                return array_values(array_filter(
                    array_map(
                        fn ($m) => is_array($m) && isset($m['id']) ? (string) $m['id'] : '',
                        (array) ($response->json('data') ?? [])
                    ),
                    fn (string $id) => $id !== ''
                ));

            } catch (\Throwable $e) {
                Log::warning('OpenRouter: Model catalog fetch failed.', [
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Utilities
    // ═══════════════════════════════════════════════════════════════════════════

    private function generateRequestId(): string
    {
        return 'req_' . Str::random(16);
    }

    private function timingMs(float $start): string
    {
        return number_format((microtime(true) - $start) * 1000, 1, '.', '');
    }
}
