<?php

namespace Tests\Unit;

use App\Models\Repository;
use App\Services\PortfolioScoreService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PortfolioScoreServiceTest extends TestCase
{
    private function analyzedRepo(int $score, int $stars = 0, array $ai = []): Repository
    {
        return new Repository(array_merge([
            'name'          => 'repo-' . $score . '-' . $stars,
            'stars'         => $stars,
            'ai_analysis'   => array_merge([
                'score'           => $score,
                'strengths'       => ['Clean code'],
                'weaknesses'      => ['No tests'],
                'recommendations' => ['Add tests'],
            ], $ai),
            'ai_analyzed_at' => now(),
        ]));
    }

    private function unanalyzedRepo(): Repository
    {
        return new Repository([
            'name'            => 'pending-repo',
            'stars'           => 0,
            'ai_analysis'     => null,
            'ai_analyzed_at'  => null,
            'analysis_status' => 'pending',
        ]);
    }

    public function test_no_analyzed_repos_reports_not_analyzed_state(): void
    {
        $repos = new Collection([
            $this->unanalyzedRepo(),
            $this->unanalyzedRepo(),
        ]);

        $assessment = app(PortfolioScoreService::class)->assess($repos);

        $this->assertNull($assessment->score);
        $this->assertFalse($assessment->isAnalyzed());
        $this->assertSame('pending', $assessment->status());
        $this->assertSame(2, $assessment->totalRepositories);
        $this->assertSame(0, $assessment->analyzedRepositories);
    }

    public function test_empty_collection_is_not_analyzed(): void
    {
        $assessment = app(PortfolioScoreService::class)->assess(new Collection());

        $this->assertNull($assessment->score);
        $this->assertSame('not-analyzed', $assessment->status());
    }

    public function test_from_array_rejects_stale_non_array_cache_data(): void
    {
        // Simulates a cache entry serialized before the toArray() format existed,
        // which unserializes as __PHP_Incomplete_Class rather than an array.
        $assessment = \App\Support\PortfolioAssessment::fromArray(
            unserialize('O:31:"App\\Support\\PortfolioAssessment":0:{}')
        );

        $this->assertNull($assessment->score);
        $this->assertSame(0, $assessment->totalRepositories);
        $this->assertSame('not-analyzed', $assessment->status());
        $this->assertFalse($assessment->isAnalyzed());
    }

    public function test_from_array_round_trips(): void
    {
        $original = app(PortfolioScoreService::class)->assess(
            new Collection([$this->analyzedRepo(75, 3)])
        );

        $restored = \App\Support\PortfolioAssessment::fromArray($original->toArray());

        $this->assertSame(75, $restored->score);
        $this->assertSame(1, $restored->analyzedRepositories);
        $this->assertSame($original->strengths, $restored->strengths);
        $this->assertSame('completed', $restored->status());
    }

    public function test_single_repo_score_is_used(): void
    {
        $repos = new Collection([$this->analyzedRepo(80, 10)]);

        $assessment = app(PortfolioScoreService::class)->assess($repos);

        $this->assertTrue($assessment->isAnalyzed());
        $this->assertSame(80, $assessment->score);
        $this->assertSame('completed', $assessment->status());
    }

    public function test_score_is_stars_weighted_average(): void
    {
        $repos = new Collection([
            $this->analyzedRepo(100, 10), // weight sqrt(11) ≈ 3.32
            $this->analyzedRepo(0, 0),    // weight sqrt(1) = 1
        ]);

        $assessment = app(PortfolioScoreService::class)->assess($repos);

        // (100*3.32 + 0*1) / (3.32 + 1) ≈ 76.8 → 77
        $this->assertSame(77, $assessment->score);
    }

    public function test_strengths_weaknesses_recommendations_are_aggregated_and_deduped(): void
    {
        $repos = new Collection([
            $this->analyzedRepo(70, 1, [
                'strengths'       => ['Clean code', 'Great docs'],
                'weaknesses'      => ['No tests'],
                'recommendations' => ['Add CI'],
            ]),
            $this->analyzedRepo(60, 1, [
                'strengths'       => ['Clean code'],
                'weaknesses'      => ['No tests', 'No readme'],
                'recommendations' => ['Add CI', 'Write docs'],
            ]),
        ]);

        $assessment = app(PortfolioScoreService::class)->assess($repos);

        $this->assertSame(['Clean code', 'Great docs'], $assessment->strengths);
        $this->assertSame(['No tests', 'No readme'], $assessment->weaknesses);
        $this->assertSame(['Add CI', 'Write docs'], $assessment->recommendations);
    }

    public function test_unanalyzed_repos_do_not_affect_the_score(): void
    {
        $repos = new Collection([
            $this->analyzedRepo(90, 5),
            $this->unanalyzedRepo(),
            $this->unanalyzedRepo(),
        ]);

        $assessment = app(PortfolioScoreService::class)->assess($repos);

        $this->assertSame(90, $assessment->score);
        $this->assertSame(1, $assessment->analyzedRepositories);
        $this->assertSame(3, $assessment->totalRepositories);
    }
}
