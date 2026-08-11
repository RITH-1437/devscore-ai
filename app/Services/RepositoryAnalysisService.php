<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AnalysisException;
use App\Models\Analysis;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates the full repository analysis pipeline:
 *
 *   Controller / Job → AiAnalysisService → response validation → persistence → cache.
 *
 * It is the ONLY place that decides how a repository transitions between the
 * pending / processing / completed / failed states and the only place that
 * writes ai_analysis / ai_analyzed_at. A fresh ai_analysis is persisted only
 * after successful validation, and any failure is recorded as an explicit
 * failed state with a user-friendly error — never a fake score of 0.
 */
class RepositoryAnalysisService
{
    public function __construct(
        private readonly AiAnalysisService $ai,
    ) {}

    /**
     * Analyze a repository synchronously.
     *
     * @return bool  true when a validated analysis was persisted.
     */
    public function analyze(Repository $repository, User $user): bool
    {
        $requestId = 'analyze_' . uniqid();
        $timingStart = microtime(true);
        $timingLabel = "[repo:{$repository->id}/{$repository->name}]";

        Log::debug("{$timingLabel} [START] analysis +{$this->timingMs($timingStart)}ms", [
            'request_id'    => $requestId,
            'repository_id' => $repository->id,
            'repository'    => $repository->name,
            'user_id'       => $user->id,
        ]);

        $repository->update([
            'analysis_status'     => 'processing',
            'analysis_started_at' => now(),
        ]);

        try {
            $result = $this->ai->analyzeRepository($repository, $timingStart, $timingLabel);
        } catch (AnalysisException $e) {
            Log::debug("{$timingLabel} [END] analysis failed +{$this->timingMs($timingStart)}ms", [
                'error_type' => $e->errorType,
            ]);
            $this->markFailed($repository, $user, $e);

            return false;
        } catch (\Throwable $e) {
            Log::debug("{$timingLabel} [END] analysis failed +{$this->timingMs($timingStart)}ms", [
                'error_type' => AnalysisException::AI_UNKNOWN_ERROR,
            ]);
            $this->markFailed(
                $repository,
                $user,
                new AnalysisException($e->getMessage(), AnalysisException::AI_UNKNOWN_ERROR, 0, $e)
            );

            return false;
        }

        // Extract metadata before stripping it from the stored payload.
        $modelUsed        = $result['_model_used']        ?? 'unknown';
        $rawResponse      = $result['_raw_response']      ?? '';
        $promptTokens     = (int) ($result['_prompt_tokens']     ?? 0);
        $completionTokens = (int) ($result['_completion_tokens'] ?? 0);
        $totalTokens      = (int) ($result['_total_tokens']      ?? 0);
        $aiRequestId      = $result['_request_id']        ?? $requestId;

        unset(
            $result['_model_used'],
            $result['_raw_response'],
            $result['_request_id'],
            $result['_prompt_tokens'],
            $result['_completion_tokens'],
            $result['_total_tokens']
        );

        Log::debug("{$timingLabel} [DB] saving result +{$this->timingMs($timingStart)}ms");

        // Persist only after successful validation (done inside the service).
        $repository->update([
            'ai_analysis'         => $result,
            'ai_analyzed_at'      => now(),
            'analysis_status'     => 'completed',
            'analysis_started_at' => null,
        ]);

        Analysis::updateOrCreate(
            [
                'user_id'       => $user->id,
                'repository_id' => $repository->id,
            ],
            array_merge(
                $this->mapResultToAnalysis($result),
                [
                    'model_used'        => $modelUsed,
                    'prompt_tokens'     => $promptTokens,
                    'completion_tokens' => $completionTokens,
                    'raw_response'      => $rawResponse,
                    'status'            => 'completed',
                    'error_message'     => null,
                ]
            )
        );

        Cache::forget("portfolio_score_{$user->id}");

        Log::info("{$timingLabel} [END] analysis completed +{$this->timingMs($timingStart)}ms", [
            'request_id'    => $requestId,
            'ai_request_id' => $aiRequestId,
            'repository'    => $repository->name,
            'model'         => $modelUsed,
            'score'         => $result['score'] ?? null,
            'tokens_used'   => $totalTokens,
        ]);

        return true;
    }

    private function timingMs(float $start): string
    {
        return number_format((microtime(true) - $start) * 1000, 1, '.', '');
    }

    /**
     * Record a failure state with a user-friendly error message.
     */
    private function markFailed(Repository $repository, User $user, AnalysisException $e): void
    {
        Log::error('RepositoryAnalysisService: Analysis failed', [
            'repository'    => $repository->name,
            'repository_id' => $repository->id,
            'user_id'       => $user->id,
            'error_type'    => $e->errorType,
            'error_message' => $e->getMessage(),
        ]);

        $repository->update([
            'analysis_status'     => 'failed',
            'analysis_started_at' => null,
        ]);

        Analysis::updateOrCreate(
            [
                'user_id'       => $user->id,
                'repository_id' => $repository->id,
            ],
            [
                'status'        => 'failed',
                'error_message' => $e->friendlyMessage() . ' [' . $e->errorType . ']',
            ]
        );

        Cache::forget("portfolio_score_{$user->id}");
    }

    /**
     * Map the validated AI result array to the Analysis model fields.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function mapResultToAnalysis(array $result): array
    {
        return [
            'score'                => $result['score'] ?? null,
            'difficulty'           => $result['difficulty'] ?? null,
            'portfolio_level'      => $result['portfolio_level'] ?? null,
            'recruiter_rating'     => $result['recruiter_rating'] ?? null,
            'estimated_experience' => $result['estimated_experience'] ?? null,
            'hiring_probability'   => $result['hiring_probability'] ?? null,
            'market_readiness'     => $result['market_readiness'] ?? null,
            'strengths'            => $result['strengths'] ?? [],
            'weaknesses'           => $result['weaknesses'] ?? [],
            'recommendations'      => $result['recommendations'] ?? [],
            'architecture_review'  => $result['architecture_review'] ?? [],
            'security_review'      => $result['security_review'] ?? [],
            'performance_review'   => $result['performance_review'] ?? [],
            'code_style_review'    => $result['code_style_review'] ?? [],
            'missing_features'     => $result['missing_features'] ?? [],
            'resume_suggestions'   => $result['resume_suggestions'] ?? [],
            'cv_suggestions'       => $result['cv_suggestions'] ?? [],
            'linkedin_suggestions' => $result['linkedin_suggestions'] ?? [],
            'interview_questions'  => $result['interview_questions'] ?? [],
            'best_companies'       => $result['best_companies'] ?? [],
            'improvement_roadmap'  => $result['improvement_roadmap'] ?? [],
        ];
    }
}
