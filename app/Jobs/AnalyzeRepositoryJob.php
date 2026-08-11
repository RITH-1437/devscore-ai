<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Repository;
use App\Models\User;
use App\Services\RepositoryAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Runs the repository analysis pipeline.
 *
 * Dispatched asynchronously when a queue worker is available
 * (`AnalyzeRepositoryJob::dispatch(...)`) or synchronously from the web
 * request (`AnalyzeRepositoryJob::dispatchSync(...)`) so analysis always
 * completes even without a running worker. All state transitions and
 * persistence live in RepositoryAnalysisService.
 */
class AnalyzeRepositoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Maximum attempts before giving up */
    public int $tries = 2;

    /** @var int Timeout in seconds */
    public int $timeout = 120;

    /** @var int Seconds to wait before retrying */
    public int $backoff = 10;

    public function __construct(
        public readonly Repository $repository,
        public readonly User $user,
    ) {}

    public function handle(RepositoryAnalysisService $analysisService): void
    {
        $analysisService->analyze($this->repository, $this->user);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AnalyzeRepositoryJob: Exhausted all retries', [
            'repository' => $this->repository->name,
            'user_id'    => $this->user->id,
            'error'      => $exception->getMessage(),
        ]);
    }
}
