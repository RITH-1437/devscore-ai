<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Immutable snapshot of a user's portfolio assessment.
 *
 * This is the single source of truth for the portfolio score and the
 * qualitative summary (strengths / weaknesses / recommendations) shown on
 * the Dashboard and the AI Analysis page. It is always derived from the
 * stored per-repository AI analyses — never from a second, ad-hoc heuristic.
 */
final class PortfolioAssessment
{
    public const STATUS_NOT_ANALYZED = 'not-analyzed';
    public const STATUS_PENDING      = 'pending';
    public const STATUS_COMPLETED    = 'completed';

    /**
     * @param  list<string>  $strengths
     * @param  list<string>  $weaknesses
     * @param  list<string>  $recommendations
     */
    public function __construct(
        public readonly ?int $score,
        public readonly int $totalRepositories,
        public readonly int $analyzedRepositories,
        public readonly array $strengths = [],
        public readonly array $weaknesses = [],
        public readonly array $recommendations = [],
    ) {}

    public function isAnalyzed(): bool
    {
        return $this->score !== null && $this->analyzedRepositories > 0;
    }

    public function status(): string
    {
        if ($this->isAnalyzed()) {
            return self::STATUS_COMPLETED;
        }

        return $this->totalRepositories > 0 ? self::STATUS_PENDING : self::STATUS_NOT_ANALYZED;
    }

    /**
     * A short label describing the current state of the portfolio assessment.
     */
    public function statusLabel(): string
    {
        return match ($this->status()) {
            self::STATUS_COMPLETED    => 'Analyzed',
            self::STATUS_PENDING      => 'Not analyzed',
            default                   => 'No data',
        };
    }

    /**
     * Convert to array for safe serialization (cache storage).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'score'                  => $this->score,
            'totalRepositories'      => $this->totalRepositories,
            'analyzedRepositories'   => $this->analyzedRepositories,
            'strengths'              => $this->strengths,
            'weaknesses'             => $this->weaknesses,
            'recommendations'        => $this->recommendations,
        ];
    }

    /**
     * Reconstruct from array (from cache).
     *
     * Tolerates stale cache entries (e.g. a PortfolioAssessment object
     * serialized before this array format existed, which unserializes as
     * __PHP_Incomplete_Class) by falling back to an unanalyzed assessment.
     *
     * @param  mixed  $data
     */
    public static function fromArray(mixed $data): self
    {
        if (! is_array($data)) {
            return new self(null, 0, 0);
        }

        return new self(
            score: $data['score'] ?? null,
            totalRepositories: $data['totalRepositories'] ?? 0,
            analyzedRepositories: $data['analyzedRepositories'] ?? 0,
            strengths: $data['strengths'] ?? [],
            weaknesses: $data['weaknesses'] ?? [],
            recommendations: $data['recommendations'] ?? [],
        );
    }
}
