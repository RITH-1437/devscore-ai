<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\PortfolioAssessment;
use Illuminate\Support\Collection;

/**
 * Single source of truth for the portfolio score.
 *
 * The portfolio score is always derived from the stored per-repository AI
 * analyses (ai_analysis.score). It is NOT calculated from arbitrary metadata
 * heuristics, and it is never hardcoded or faked.
 *
 * Responsibilities:
 *  - Portfolio score  : weighted average of the analyzed repositories' AI scores.
 *  - Strengths        : aggregated from every analyzed repository.
 *  - Weaknesses       : aggregated from every analyzed repository.
 *  - Recommendations  : aggregated from every analyzed repository.
 *
 * If no repository has completed an AI analysis, the assessment reports a
 * null score (a "pending / not analyzed" state) instead of a misleading 0.
 */
class PortfolioScoreService
{
    /** @var list<string> */
    private const ARRAY_FIELDS = [
        'strengths',
        'weaknesses',
        'recommendations',
    ];

    private const MAX_ITEMS_PER_FIELD = 8;

    /**
     * Build the authoritative portfolio assessment for a collection of
     * repositories (which must be scoped to a single user).
     *
     * @param  Collection<int, \App\Models\Repository>  $repositories
     */
    public function assess(Collection $repositories): PortfolioAssessment
    {
        $total    = $repositories->count();
        $analyzed = $repositories->filter(fn ($r) => $r->isAnalyzed() && is_array($r->ai_analysis));

        if ($analyzed->isEmpty()) {
            return new PortfolioAssessment(null, $total, 0);
        }

        $score = $this->weightedScore($analyzed);

        return new PortfolioAssessment(
            score: $score,
            totalRepositories: $total,
            analyzedRepositories: $analyzed->count(),
            strengths: $this->aggregate($analyzed, 'strengths'),
            weaknesses: $this->aggregate($analyzed, 'weaknesses'),
            recommendations: $this->aggregate($analyzed, 'recommendations'),
        );
    }

    /**
     * Convenience method returning just the portfolio score (or null when the
     * portfolio has not been analyzed yet).
     *
     * @param  Collection<int, \App\Models\Repository>  $repositories
     */
    public function score(Collection $repositories): ?int
    {
        return $this->assess($repositories)->score;
    }

    /**
     * Weighted average of repository AI scores.
     *
     * Weighting uses sqrt(stars + 1) so that a very popular repository cannot
     * dominate the result, while still giving slightly more weight to
     * repositories with real community traction.
     *
     * @param  Collection<int, \App\Models\Repository>  $analyzed
     */
    private function weightedScore(Collection $analyzed): int
    {
        $weightedSum   = 0;
        $weightTotal   = 0;

        foreach ($analyzed as $repository) {
            $repoScore = (int) ($repository->ai_analysis['score'] ?? 0);
            $repoScore = max(0, min(100, $repoScore));

            $weight    = sqrt(max((int) $repository->stars, 0) + 1);
            $weightedSum += $repoScore * $weight;
            $weightTotal += $weight;
        }

        if ($weightTotal <= 0) {
            return 0;
        }

        return max(0, min(100, (int) round($weightedSum / $weightTotal)));
    }

    /**
     * Collect a given field across all analyzed repositories, deduplicate,
     * and cap the number of items to keep the summary readable.
     *
     * @param  Collection<int, \App\Models\Repository>  $analyzed
     * @return list<string>
     */
    private function aggregate(Collection $analyzed, string $field): array
    {
        $items = $analyzed
            ->flatMap(fn ($r) => $r->ai_analysis[$field] ?? [])
            ->filter(fn ($item) => is_string($item) && $item !== '')
            ->map(fn (string $item) => trim($item))
            ->unique()
            ->values()
            ->take(self::MAX_ITEMS_PER_FIELD)
            ->all();

        return array_values($items);
    }
}
