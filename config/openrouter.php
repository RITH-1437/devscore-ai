<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenRouter API Configuration
    |--------------------------------------------------------------------------
    */

    'api_key' => env('OPENROUTER_API_KEY'),

    'base_url' => 'https://openrouter.ai/api/v1',

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeouts (seconds)
    |--------------------------------------------------------------------------
    | connect_timeout caps the TCP/TLS handshake. timeout caps the full
    | request. total_budget caps the total wall-clock time spent across all
    | model fallbacks and retries, so a request can never hang indefinitely.
    |
    | Increased defaults to handle slower free-tier models that may take
    | 60+ seconds to respond.
    */

    'connect_timeout' => (int) env('OPENROUTER_CONNECT_TIMEOUT', 10),

    'timeout' => (int) env('OPENROUTER_TIMEOUT', 60),

    'total_budget' => (int) env('OPENROUTER_TOTAL_BUDGET', 120),

    'retry_times' => (int) env('OPENROUTER_RETRY_TIMES', 2),

    // Verify the configured models against OpenRouter's model catalog before
    // requesting them (results are cached for one hour).
    //
    // WARNING: Setting this to true may filter out working models if OpenRouter's
    // catalog is stale or incomplete. Disabled by default for reliability.
    'verify_models' => (bool) env('OPENROUTER_VERIFY_MODELS', false),

    /*
    |--------------------------------------------------------------------------
    | Model Fallback Chain
    |
    | Models are tried in order. If one fails (rate limit, empty response, bad
    | JSON), the next model in the chain is attempted automatically.
    | All models listed here are free-tier on OpenRouter.
    |
    | NOTE: openai/gpt-oss-20b:free is NOT a valid model — do not use it.
    |--------------------------------------------------------------------------
    */

    'models' => [
        'openai/gpt-4o-mini:free',
        'nvidia/nemotron-nano-12b-v2-vl:free',
        'google/gemma-3-27b-it:free',
        'qwen/qwen3-32b:free',
        'meta-llama/llama-3.3-8b-instruct:free',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default / Primary Model
    |--------------------------------------------------------------------------
    |
    | Set via OPENROUTER_MODEL env variable. This model will be tried FIRST
    | before falling back to the models array below. If not set, the first
    | model in the 'models' array is used.
    |
    | Examples:
    |   OPENROUTER_MODEL=openai/gpt-4o-mini:free
    |   OPENROUTER_MODEL=anthropic/claude-3.5-sonnet:beta
    |
    | Leave empty to use only the models array.
    */

    'default_model' => env('OPENROUTER_MODEL', ''),

    /*
    |--------------------------------------------------------------------------
    | Generation parameters
    |--------------------------------------------------------------------------
    */

    'temperature' => (float) env('OPENROUTER_TEMPERATURE', 0.2),

    'max_tokens' => (int) env('OPENROUTER_MAX_TOKENS', 4096),

];
