<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use App\Exceptions\AnalysisException;
use Illuminate\Support\Facades\Log;

trait ParsesAndValidatesAnalysisResponses
{
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
     * @return array<string, mixed>|null
     */
    protected function parseJson(string $content, string $providerLabel = 'AI'): ?array
    {
        Log::debug("{$providerLabel}: Parsing JSON", [
            'content_length' => strlen($content),
            'content_start'  => substr($content, 0, 100),
        ]);

        $cleaned = preg_replace('/^```(?:json)?\s*/m', '', $content) ?? $content;
        $cleaned = preg_replace('/^```\s*$/m', '', $cleaned) ?? $cleaned;
        $cleaned = trim($cleaned);

        $decoded = json_decode($cleaned, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/(\{[\s\S]*\})/s', $content, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        $fixed = preg_replace('/,\s*([\]}])/s', '$1', $content) ?? $content;
        $decoded = json_decode($fixed, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if (substr_count($content, '{') > substr_count($content, '}')) {
            $fixed = $content . str_repeat('}', substr_count($content, '{') - substr_count($content, '}'));
            $decoded = json_decode($fixed, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        Log::warning("{$providerLabel}: All JSON parsing strategies failed", [
            'content_length' => strlen($content),
            'content'        => substr($content, 0, 500),
            'last_error'     => json_last_error_msg(),
        ]);

        return null;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     *
     * @throws AnalysisException
     */
    protected function validateResult(array $result): array
    {
        if (! array_key_exists('score', $result) || ! is_numeric($result['score'])) {
            throw new AnalysisException(
                'AI response did not include a numeric score.',
                AnalysisException::AI_INVALID_RESPONSE
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
}
