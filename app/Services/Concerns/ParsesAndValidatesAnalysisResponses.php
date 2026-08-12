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

        foreach ($this->jsonCandidates($content) as $candidate) {
            $decoded = json_decode($candidate, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        Log::warning("{$providerLabel}: All JSON parsing strategies failed", [
            'content_length' => strlen($content),
            'last_error'     => json_last_error_msg(),
        ]);

        return null;
    }

    /**
     * @return list<string>
     */
    private function jsonCandidates(string $content): array
    {
        $candidates = [];

        $cleaned = preg_replace('/^```(?:json)?\s*/m', '', $content) ?? $content;
        $cleaned = preg_replace('/^```\s*$/m', '', $cleaned) ?? $cleaned;
        $cleaned = trim($cleaned);
        $candidates[] = $cleaned;

        if (preg_match('/(\{[\s\S]*\})/s', $content, $matches)) {
            $candidates[] = $matches[1];
        }

        $repaired = $this->repairTruncatedJson($cleaned);
        if ($repaired !== $cleaned) {
            $candidates[] = $repaired;
        }

        $noTrailingCommas = preg_replace('/,\s*([\]}])/s', '$1', $cleaned) ?? $cleaned;
        $candidates[] = $noTrailingCommas;

        return array_values(array_unique(array_filter($candidates)));
    }

    private function repairTruncatedJson(string $content): string
    {
        $content = trim($content);
        if ($content === '') {
            return $content;
        }

        // Drop an incomplete trailing property or array item.
        $content = (string) preg_replace('/,\s*"[^"]*":\s*"[^"]*$/s', '', $content);
        $content = (string) preg_replace('/,\s*"[^"]*":\s*[\d\[\{][^\}\]]*$/s', '', $content);
        $content = (string) preg_replace('/,\s*"[^"]*":\s*$/s', '', $content);
        $content = (string) preg_replace('/,\s*"[^"]*$/s', '', $content);
        $content = (string) preg_replace('/,\s*$/', '', $content);

        $openBrackets = max(0, substr_count($content, '[') - substr_count($content, ']'));
        $openBraces   = max(0, substr_count($content, '{') - substr_count($content, '}'));

        $content .= str_repeat(']', $openBrackets);
        $content .= str_repeat('}', $openBraces);

        return $content;
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
