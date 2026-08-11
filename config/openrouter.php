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
    */

    'connect_timeout' => (int) env('OPENROUTER_CONNECT_TIMEOUT', 10),

    'timeout' => (int) env('OPENROUTER_TIMEOUT', 45),

    'total_budget' => (int) env('OPENROUTER_TOTAL_BUDGET', 240),

    'retry_times' => (int) env('OPENROUTER_RETRY_TIMES', 0),

    'verify_models' => (bool) env('OPENROUTER_VERIFY_MODELS', true),

    /*
    |--------------------------------------------------------------------------
    | Model Fallback Chain
    |
    | Try specific :free slugs first — they fail fast on 404/rate-limit.
    | openrouter/free is last: it auto-routes but often hits the full HTTP
    | timeout before falling through, so it must not be first in the chain.
    |--------------------------------------------------------------------------
    */

    'models' => [
        'google/gemini-2.0-flash-exp:free',
        'inclusionai/ling-3.0-flash:free',
        'nvidia/nemotron-nano-9b-v2:free',
        'openai/gpt-oss-20b:free',
        'nvidia/nemotron-nano-12b-v2-vl:free',
        'openrouter/free',
    ],

    'default_model' => env('OPENROUTER_MODEL', ''),

    /*
    |--------------------------------------------------------------------------
    | Generation parameters
    |--------------------------------------------------------------------------
    */

    'temperature' => (float) env('OPENROUTER_TEMPERATURE', 0.2),

    'max_tokens' => (int) env('OPENROUTER_MAX_TOKENS', 2048),

    /*
    |--------------------------------------------------------------------------
    | Prompt payload limits
    |--------------------------------------------------------------------------
    */

    'payload' => [
        'max_readme_chars'      => (int) env('OPENROUTER_MAX_README_CHARS', 3000),
        'max_description_chars' => (int) env('OPENROUTER_MAX_DESCRIPTION_CHARS', 500),
        'max_prompt_chars'      => (int) env('OPENROUTER_MAX_PROMPT_CHARS', 10000),
    ],

];
