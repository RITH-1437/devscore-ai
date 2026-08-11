<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Gemini API Configuration
    |--------------------------------------------------------------------------
    |
    | Direct integration with Google AI Studio. When GEMINI_API_KEY is set,
    | repository analysis uses Gemini instead of OpenRouter.
    |
    */

    'api_key' => env('GEMINI_API_KEY'),

    'base_url' => 'https://generativelanguage.googleapis.com/v1beta',

    /*
    |--------------------------------------------------------------------------
    | Model Fallback Chain
    |--------------------------------------------------------------------------
    */

    'models' => array_values(array_unique(array_filter([
        env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'gemini-2.5-flash-lite',
    ]))),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeouts (seconds)
    |--------------------------------------------------------------------------
    */

    'connect_timeout' => (int) env('GEMINI_CONNECT_TIMEOUT', 10),

    'timeout' => (int) env('GEMINI_TIMEOUT', 45),

    'retry_times' => (int) env('GEMINI_RETRY_TIMES', 0),

    /*
    |--------------------------------------------------------------------------
    | Generation parameters
    |--------------------------------------------------------------------------
    */

    'temperature' => (float) env('GEMINI_TEMPERATURE', 0.2),

    'max_tokens' => (int) env('GEMINI_MAX_TOKENS', 2048),

    /*
    |--------------------------------------------------------------------------
    | Prompt payload limits
    |--------------------------------------------------------------------------
    */

    'payload' => [
        'max_readme_chars'      => (int) env('GEMINI_MAX_README_CHARS', 3000),
        'max_description_chars' => (int) env('GEMINI_MAX_DESCRIPTION_CHARS', 500),
        'max_prompt_chars'      => (int) env('GEMINI_MAX_PROMPT_CHARS', 10000),
    ],

];
