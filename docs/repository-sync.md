# Repository Sync

Repository sync fetches public GitHub repositories and upserts them into the local database, including README content and profile metadata.

## Trigger Points

| Event | Mechanism |
|-------|-----------|
| First GitHub login | `SyncRepositoriesJob::dispatchAfterResponse($user)` in `GitHubController::callback` |
| Manual sync (Settings) | `SettingsController::syncRepositories` → same job |
| Profile sync | **Does not** sync repos — only profile metadata via `ProfileController::sync` |

Both sync entry points clear `portfolio_score_{user_id}` cache before dispatching.

## Job

`App\Jobs\SyncRepositoriesJob`

| Property | Value |
|----------|-------|
| Timeout | 180 seconds |
| Tries | 2 |
| Backoff | 30 seconds |

Requires a running queue worker (`composer dev` or `php artisan queue:listen`).

## Service

`App\Services\RepositorySyncService::sync(GithubAccount $account): int`

### Steps

1. **Refresh profile** — `syncProfile()` updates `github_accounts` from `/user`
2. **Fetch repos** — `GithubService::getRepositories($token)`
3. **Upsert each repo** — `syncSingleRepository()` for every returned repo
4. **Invalidate cache** — `portfolio_score_{user_id}`
5. **Return count** — number of successfully synced repositories

### Per-repository upsert

`Repository::updateOrCreate(['repo_id' => ...], [...])` stores:

- Metadata: name, description, language, stars, forks, topics, license, flags
- Timestamps: `pushed_at`, `github_created_at`
- Content: `readme` from GitHub README API

Individual repo failures are logged and skipped; sync continues for remaining repos.

If GitHub returns repos but **zero** could be saved, a `RuntimeException` is thrown.

## Data Model

Repositories belong to `GithubAccount` via `github_account_id`. Users access repos through `Repository::forUser($user)` scope.

## User Experience

- After login, user lands on dashboard; sync runs in background
- Settings page shows sync button with flash message: "Repository sync started..."
- Without queue worker, repositories will not appear until worker runs

## Rate Limiting

Settings sync: `throttle:5,10` (5 requests per 10 minutes).

## Related Docs

- [github-integration.md](github-integration.md)
- [database.md](database.md)
- [troubleshooting.md](troubleshooting.md)
