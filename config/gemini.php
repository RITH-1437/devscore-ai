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
        'gemini-flash-latest',
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

    'max_tokens' => (int) env('GEMINI_MAX_TOKENS', 8192),

    /*
    |--------------------------------------------------------------------------
    | Structured JSON schema (Gemini responseSchema)
    |--------------------------------------------------------------------------
    |
    | Forces complete, valid JSON matching the analysis payload shape.
    |
    */

    'response_schema' => [
        'type' => 'object',
        'properties' => [
            'score'                 => ['type' => 'integer'],
            'difficulty'            => ['type' => 'string'],
            'portfolio_level'       => ['type' => 'string'],
            'recruiter_rating'      => ['type' => 'integer'],
            'estimated_experience'  => ['type' => 'string'],
            'hiring_probability'    => ['type' => 'integer'],
            'market_readiness'      => ['type' => 'string'],
            'strengths'             => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 3],
            'weaknesses'            => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 3],
            'recommendations'       => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 3],
            'architecture_review'   => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 2],
            'security_review'       => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 2],
            'performance_review'    => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 2],
            'code_style_review'     => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 2],
            'missing_features'      => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 2],
            'resume_suggestions'    => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 2],
            'cv_suggestions'        => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 2],
            'linkedin_suggestions'  => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 2],
            'interview_questions'   => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 2],
            'best_companies'        => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 2],
            'improvement_roadmap'   => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 2],
        ],
        'required' => [
            'score', 'difficulty', 'portfolio_level', 'recruiter_rating',
            'estimated_experience', 'hiring_probability', 'market_readiness',
            'strengths', 'weaknesses', 'recommendations',
        ],
    ],

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
