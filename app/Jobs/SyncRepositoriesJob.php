<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\RepositorySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncRepositoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 180;
    public int $backoff = 30;

    public function __construct(
        public readonly User $user,
    ) {}

    public function handle(RepositorySyncService $syncService): void
    {
        $account = $this->user->githubAccount;

        if (! $account) {
            Log::warning('SyncRepositoriesJob: user has no GitHub account.', [
                'user_id' => $this->user->id,
            ]);
            return;
        }

        try {
            $synced = $syncService->sync($account);

            Log::info("SyncRepositoriesJob complete.", [
                'user_id' => $this->user->id,
                'synced'  => $synced,
            ]);
        } catch (\Throwable $e) {
            Log::error("SyncRepositoriesJob failed.", [
                'user_id' => $this->user->id,
                'error'   => $e->getMessage(),
            ]);
            $this->fail($e);
        }
    }
}
