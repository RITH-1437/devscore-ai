<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AnalysisException;
use App\Models\Repository;
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
        $this->timeout       = (int) config('openrouter.timeout', 60);
        $this->connectTimeout= (int) config('openrouter.connect_timeout', 10);
        $this->maxRetries    = (int) config('openrouter.retry_times', 2);
        $this->totalBudget   = (int) config('openrouter.total_budget', 120);
        $this->baseUrl       = (string) config('openrouter.base_url', 'https://openrouter.ai/api/v1');
        $this->temperature   = (float) config('openrouter.temperature', 0.2);
        $this->maxTokens     = (int) config('openrouter.max_tokens', 4096);
        $this->verifyModels  = (bool) config('openrouter.verify_models', false);

        // Build the model chain: OPENROUTER_MODEL env takes priority, then fall back to configured models
        $defaultModel = (string) config('openrouter.default_model', '');
        $fallbackModels = (array) config('openrouter.models', [
            'openai/gpt-4o-mini:free',
            'nvidia/nemotron-nano-12b-v2-vl:free',
            'google/gemma-3-27b-it:free',
            'qwen/qwen3-32b:free',
            'meta-llama/llama-3.3-8b-instruct:free',
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
    public function analyzeRepository(Repository $repository): array
    {
        $requestId = $this->generateRequestId();
        $prompt    = $this->buildRepositoryPrompt($repository);

        Log::info('OpenRouter: Starting repository analysis', [
            'request_id'   => $requestId,
            'repository'   => $repository->name,
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
    // Prompt Builders
    // ═══════════════════════════════════════════════════════════════════════════

    private function buildRepositoryPrompt(Repository $repository): string
    {
        $topics  = implode(', ', $repository->topics ?? []);
        $readme  = mb_substr($repository->readme ?? 'Not available', 0, 6000);
        $license = $repository->license ?? 'Not specified';
        $lastPushed = $repository->pushed_at?->toDateString() ?? 'Unknown';
        $createdAt = $repository->github_created_at?->toDateString() ?? 'Unknown';

        return <<<PROMPT
You are a senior software engineer, technical recruiter, and portfolio analyst reviewing a GitHub repository.

Analyze this repository thoroughly and return ONLY valid JSON. No markdown code blocks, no explanations, no preamble — just raw JSON.

Repository Details:
- Name: {$repository->name}
- Full Name: {$repository->full_name}
- Description: {$repository->description}
- Primary Language: {$repository->language}
- Stars: {$repository->stars}
- Forks: {$repository->forks}
- Open Issues: {$repository->open_issues}
- Watchers: {$repository->watchers}
- Size (KB): {$repository->size}
- Topics: {$topics}
- License: {$license}
- Is Fork: {$this->bool($repository->is_fork)}
- Is Archived: {$this->bool($repository->is_archived)}
- Last Pushed: {$lastPushed}
- Created: {$createdAt}

README (first 6000 chars):
{$readme}

Return this exact JSON structure. All fields are required — do not omit any field:

{
  "score": <integer 0-100>,
  "difficulty": "<beginner|intermediate|advanced|expert>",
  "portfolio_level": "<junior|mid|senior|staff|principal>",
  "recruiter_rating": <integer 1-10>,
  "estimated_experience": "<e.g., 0-1 years, 1-3 years, 3-5 years, 5+ years>",
  "hiring_probability": <integer 0-100>,
  "market_readiness": "<not-ready|emerging|ready|production-grade>",
  "strengths": ["<string>", "<string>", ...],
  "weaknesses": ["<string>", "<string>", ...],
  "recommendations": ["<string>", "<string>", ...],
  "architecture_review": ["<string>", "<string>", ...],
  "security_review": ["<string>", "<string>", ...],
  "performance_review": ["<string>", "<string>", ...],
  "code_style_review": ["<string>", "<string>", ...],
  "missing_features": ["<string>", "<string>", ...],
  "resume_suggestions": ["<string>", "<string>", ...],
  "cv_suggestions": ["<string>", "<string>", ...],
  "linkedin_suggestions": ["<string>", "<string>", ...],
  "interview_questions": ["<string>", "<string>", ...],
  "best_companies": ["<string>", "<string>", ...],
  "improvement_roadmap": ["<string>", "<string>", ...]
}

CRITICAL: Return ONLY the JSON object. No text before or after.
PROMPT;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Repository>  $repositories
     */
    private function buildPortfolioPrompt(\Illuminate\Support\Collection $repositories): string
    {
        $repoList = $repositories->map(function (Repository $r) {
            return "- {$r->name} ({$r->language}, ⭐{$r->stars}): " . ($r->description ?? 'No description');
        })->implode("\n");

        $totalRepos  = $repositories->count();
        $totalStars  = $repositories->sum('stars');
        $totalForks  = $repositories->sum('forks');
        $languages   = $repositories->pluck('language')->filter()->unique()->implode(', ');

        return <<<PROMPT
You are a senior software engineer and technical recruiter reviewing a developer's complete GitHub portfolio.

Analyze the portfolio and return ONLY valid JSON. No markdown, no code blocks, no explanations.

Portfolio Summary:
- Total Repositories: {$totalRepos}
- Total Stars: {$totalStars}
- Total Forks: {$totalForks}
- Languages Used: {$languages}

Repositories:
{$repoList}

Return this exact JSON structure (all fields required):

{
  "score": <integer 0-100>,
  "portfolio_level": "<junior|mid|senior|staff|principal>",
  "estimated_experience": "<e.g., 0-1 years, 1-3 years, 3-5 years, 5+ years>",
  "hiring_probability": <integer 0-100>,
  "market_readiness": "<not-ready|emerging|ready|production-grade>",
  "recruiter_rating": <integer 1-10>,
  "strengths": ["<string>", ...],
  "weaknesses": ["<string>", ...],
  "recommendations": ["<string>", ...],
  "missing_features": ["<string>", ...],
  "resume_suggestions": ["<string>", ...],
  "cv_suggestions": ["<string>", ...],
  "linkedin_suggestions": ["<string>", ...],
  "best_companies": ["<string>", ...],
  "improvement_roadmap": ["<string>", ...]
}

CRITICAL: Return ONLY the JSON object.
PROMPT;
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
    private function callWithFallback(string $requestId, string $prompt): array
    {
        $deadline = microtime(true) + max(1, $this->totalBudget);

        $models = $this->verifyModels ? $this->filterAvailableModels($this->models) : $this->models;
        $models = array_values(array_unique($models));

        if (empty($models)) {
            throw new AnalysisException(
                'None of the configured OpenRouter models are currently available.',
                AnalysisException::NO_MODELS_AVAILABLE
            );
        }

        Log::info('OpenRouter: Model fallback chain', [
            'request_id'    => $requestId,
            'models'        => $models,
            'verify_models' => $this->verifyModels,
        ]);

        $lastType    = AnalysisException::UNKNOWN;
        $lastMessage = 'No models available.';
        $rawResponse = '';

        foreach ($models as $model) {
            if (microtime(true) > $deadline) {
                Log::warning('OpenRouter: Budget exhausted', [
                    'request_id' => $requestId,
                    'budget'     => $this->totalBudget,
                ]);
                break;
            }

            Log::debug('OpenRouter: Trying model', [
                'request_id' => $requestId,
                'model'      => $model,
            ]);

            try {
                [$raw, $tokens] = $this->callModelWithRetry($requestId, $model, $prompt);
                $rawResponse = $raw;

                if (empty($raw)) {
                    $lastType    = AnalysisException::EMPTY_RESPONSE;
                    $lastMessage = "Empty response from model: {$model}";
                    Log::warning('OpenRouter: Empty response', [
                        'request_id' => $requestId,
                        'model'      => $model,
                    ]);
                    continue;
                }

                $parsed = $this->parseJson($raw);

                if ($parsed === null) {
                    $lastType    = AnalysisException::INVALID_RESPONSE;
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

                Log::info('OpenRouter: Analysis successful', [
                    'request_id'     => $requestId,
                    'model'          => $model,
                    'tokens_used'    => $tokens['total_tokens'] ?? 0,
                    'response_chars' => strlen($raw),
                ]);

                return [$validated, $model, $raw, $tokens];

            } catch (AnalysisException $e) {
                $lastType    = $e->errorType;
                $lastMessage = $e->getMessage();

                Log::warning('OpenRouter: Model failed, trying next', [
                    'request_id' => $requestId,
                    'model'      => $model,
                    'error_type' => $e->errorType,
                    'error'      => $lastMessage,
                ]);

                // Do not waste budget on a model that is rate-limited or missing.
                if (in_array($e->errorType, [
                    AnalysisException::RATE_LIMIT,
                    AnalysisException::MODEL_UNAVAILABLE,
                    AnalysisException::AUTH_ERROR,
                    AnalysisException::INSUFFICIENT_CREDITS,
                ], true)) {
                    continue;
                }
            } catch (\Throwable $e) {
                // Catch HTTP client exceptions (timeouts, connection errors)
                $lastType    = AnalysisException::TIMEOUT;
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
    private function callModelWithRetry(string $requestId, string $model, string $prompt): array
    {
        $attempt        = 0;
        $lastException  = null;

        while ($attempt < max(1, $this->maxRetries + 1)) {
            $attempt++;
            $backoffSeconds = pow(2, $attempt - 1); // 1s, 2s, 4s

            try {
                return $this->callModel($requestId, $model, $prompt);
            } catch (AnalysisException $e) {
                $lastException = $e;

                Log::debug('OpenRouter: Retry attempt '.$attempt, [
                    'request_id' => $requestId,
                    'model'      => $model,
                    'error_type' => $e->errorType,
                    'backoff_s'  => $backoffSeconds,
                ]);

                // Some errors are pointless to retry.
                if (in_array($e->errorType, [
                    AnalysisException::RATE_LIMIT,
                    AnalysisException::MODEL_UNAVAILABLE,
                    AnalysisException::AUTH_ERROR,
                    AnalysisException::INSUFFICIENT_CREDITS,
                ], true)) {
                    throw $e;
                }

                if ($attempt <= $this->maxRetries) {
                    sleep($backoffSeconds);
                }
            }
        }

        throw $lastException ?? new AnalysisException('Max retries exceeded', AnalysisException::UNKNOWN);
    }

    /**
     * Make a single API call to OpenRouter.
     *
     * @return array{0: string, 1: array<string, int>}
     * @throws AnalysisException
     */
    private function callModel(string $requestId, string $model, string $prompt): array
    {
        if ($this->apiKey === '') {
            throw new AnalysisException(
                'OPENROUTER_API_KEY is not configured.',
                AnalysisException::AUTH_ERROR
            );
        }

        Log::debug('OpenRouter: Making API request', [
            'request_id'  => $requestId,
            'model'       => $model,
            'base_url'    => $this->baseUrl,
            'temperature' => $this->temperature,
            'max_tokens'  => $this->maxTokens,
            'timeout'     => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
            'prompt_length' => strlen($prompt),
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
            Log::error('OpenRouter: Connection failed', [
                'request_id' => $requestId,
                'model'      => $model,
                'error'      => $e->getMessage(),
            ]);

            throw new AnalysisException(
                "Connection to OpenRouter failed for model {$model}: " . $e->getMessage(),
                AnalysisException::TIMEOUT,
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
                    AnalysisException::TIMEOUT,
                    $status,
                    $e
                );
            }

            throw new AnalysisException(
                "HTTP request failed for model {$model}: " . $e->getMessage(),
                AnalysisException::SERVER_ERROR,
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
                AnalysisException::UNKNOWN,
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
                AnalysisException::RATE_LIMIT,
                $status
            );
        }

        if ($status === 401) {
            Log::error('OpenRouter: Authentication failed', [
                'request_id' => $requestId,
                'model'      => $model,
                'status'     => $status,
                'api_key_prefix' => substr($this->apiKey, 0, 10) . '...',
            ]);

            throw new AnalysisException(
                'Invalid API key',
                AnalysisException::AUTH_ERROR,
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
                AnalysisException::INSUFFICIENT_CREDITS,
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
                AnalysisException::MODEL_UNAVAILABLE,
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
                AnalysisException::SERVER_ERROR,
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
                AnalysisException::UNKNOWN,
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
                AnalysisException::INVALID_RESPONSE,
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
                AnalysisException::EMPTY_RESPONSE,
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
                AnalysisException::INVALID_RESPONSE,
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
                AnalysisException::EMPTY_RESPONSE,
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
    // JSON Parsing & Recovery
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Attempt to parse JSON with multiple recovery strategies.
     *
     * @return array<string, mixed>|null
     */
    private function parseJson(string $content): ?array
    {
        Log::debug('OpenRouter: Parsing JSON', [
            'content_length' => strlen($content),
            'content_start'  => substr($content, 0, 100),
        ]);

        // Strategy 1: Strip markdown code blocks
        $cleaned = preg_replace('/^```(?:json)?\s*/m', '', $content) ?? $content;
        $cleaned = preg_replace('/^```\s*$/m', '', $cleaned) ?? $cleaned;
        $cleaned = trim($cleaned);

        // Strategy 2: Direct decode
        $decoded = json_decode($cleaned, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            Log::debug('OpenRouter: JSON parsed successfully (direct decode)');
            return $decoded;
        }

        Log::debug('OpenRouter: Direct decode failed', [
            'error' => json_last_error_msg(),
        ]);

        // Strategy 3: Extract JSON object via regex (greedy match)
        if (preg_match('/(\{[\s\S]*\})/s', $content, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                Log::debug('OpenRouter: JSON parsed successfully (regex extraction)');
                return $decoded;
            }

            Log::debug('OpenRouter: Regex extraction decode failed', [
                'error' => json_last_error_msg(),
                'extracted' => substr($matches[1], 0, 200),
            ]);
        }

        // Strategy 4: Fix trailing commas
        $fixed = preg_replace('/,\s*([\]}])/s', '$1', $content) ?? $content;
        $decoded = json_decode($fixed, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            Log::debug('OpenRouter: JSON parsed successfully (trailing comma fix)');
            return $decoded;
        }

        // Strategy 5: Try to complete truncated JSON
        if (substr_count($content, '{') > substr_count($content, '}')) {
            $fixed = $content . str_repeat('}', substr_count($content, '{') - substr_count($content, '}'));
            $decoded = json_decode($fixed, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                Log::debug('OpenRouter: JSON parsed successfully (truncation fix)');
                return $decoded;
            }
        }

        Log::warning('OpenRouter: All JSON parsing strategies failed', [
            'content_length' => strlen($content),
            'content'        => substr($content, 0, 500),
            'last_error'     => json_last_error_msg(),
        ]);

        return null;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Validation & Normalization
    // ═══════════════════════════════════════════════════════════════════════════

    /** @var list<string> */
    private const STRING_FIELDS = [
        'difficulty',
        'portfolio_level',
        'estimated_experience',
        'market_readiness',
    ];

    /** @var list<string> */
    private const INT_FIELDS = [
        'recruiter_rating',
        'hiring_probability',
    ];

    /** @var list<string> */
    private const ARRAY_FIELDS = [
        'strengths',
        'weaknesses',
        'recommendations',
        'architecture_review',
        'security_review',
        'performance_review',
        'code_style_review',
        'missing_features',
        'resume_suggestions',
        'cv_suggestions',
        'linkedin_suggestions',
        'interview_questions',
        'best_companies',
        'improvement_roadmap',
    ];

    /**
     * Validate and normalize an AI response. Throws AnalysisException when the
     * response is structurally invalid (e.g. missing a numeric score), so we
     * never persist a fake or corrupt analysis.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     * @throws AnalysisException
     */
    private function validateResult(array $result): array
    {
        if (! array_key_exists('score', $result) || ! is_numeric($result['score'])) {
            throw new AnalysisException(
                'AI response did not include a numeric score.',
                AnalysisException::INVALID_RESPONSE
            );
        }

        $normalized = $result;

        $score = max(0, min(100, (int) round((float) $result['score'])));
        $normalized['score'] = $score;

        foreach (self::STRING_FIELDS as $field) {
            $value = $result[$field] ?? null;
            $normalized[$field] = (is_string($value) && $value !== '') ? $value : null;
        }

        foreach (self::INT_FIELDS as $field) {
            $value = $result[$field] ?? null;
            $normalized[$field] = is_numeric($value)
                ? max(0, min($field === 'recruiter_rating' ? 10 : 100, (int) round((float) $value)))
                : null;
        }

        foreach (self::ARRAY_FIELDS as $field) {
            $items = $result[$field] ?? [];
            if (is_string($items)) {
                $items = [$items];
            }
            if (! is_array($items)) {
                $items = [];
            }
            $normalized[$field] = array_values(array_filter(
                $items,
                fn ($item) => is_string($item) && trim($item) !== ''
            ));
        }

        return $normalized;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Utilities
    // ═══════════════════════════════════════════════════════════════════════════

    private function generateRequestId(): string
    {
        return 'req_' . Str::random(16);
    }

    private function bool(?bool $value): string
    {
        return $value ? 'Yes' : 'No';
    }
}
