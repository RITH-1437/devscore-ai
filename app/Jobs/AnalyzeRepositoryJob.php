<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\AnalysisException;
use App\Models\Analysis;
use App\Models\Repository;
use App\Models\User;
use App\Services\RepositoryAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Runs the repository analysis pipeline asynchronously.
 *
 * Dispatched from the web request so the user gets an immediate response
 * while OpenRouter calls run in the background. All state transitions and
 * persistence live in RepositoryAnalysisService.
 */
class AnalyzeRepositoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Maximum attempts before giving up */
    public int $tries = 1;

    /** @var int Timeout in seconds — must exceed OpenRouter total_budget */
    public int $timeout = 300;

    public function __construct(
        public readonly Repository $repository,
        public readonly User $user,
    ) {}

    public function handle(RepositoryAnalysisService $analysisService): void
    {
        $this->repository->refresh();

        if (! Gate::forUser($this->user)->allows('analyze', $this->repository)) {
            Log::warning('AnalyzeRepositoryJob: ownership check failed — skipping job.', [
                'repository_id' => $this->repository->id,
                'user_id'       => $this->user->id,
            ]);

            return;
        }

        Log::info('AnalyzeRepositoryJob: starting', [
            'repository_id' => $this->repository->id,
            'repository'    => $this->repository->name,
            'ai_provider'   => app(\App\Services\AiAnalysisService::class)->providerName(),
        ]);

        $analysisService->analyze($this->repository, $this->user);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AnalyzeRepositoryJob: Job failed', [
            'repository'    => $this->repository->name,
            'repository_id' => $this->repository->id,
            'user_id'       => $this->user->id,
            'error'         => $exception->getMessage(),
        ]);

        $this->repository->refresh();

        if ($this->repository->analysis_status !== 'processing') {
            return;
        }

        $this->repository->update([
            'analysis_status'     => 'failed',
            'analysis_started_at' => null,
        ]);

        Analysis::updateOrCreate(
            [
                'user_id'       => $this->user->id,
                'repository_id' => $this->repository->id,
            ],
            [
                'status'        => 'failed',
                'error_message' => 'AI analysis was interrupted. Please try again. [' . AnalysisException::AI_UNKNOWN_ERROR . ']',
            ]
        );
    }
}
