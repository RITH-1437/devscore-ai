# Performance

Performance considerations and tuning for GitRadar.

## Default Stack (No Redis)

Out of the box:

| Component | Driver | Notes |
|-----------|--------|-------|
| Cache | `file` | Portfolio scores, OpenRouter model catalog |
| Queue | `database` | Sync + AI jobs |
| Session | `database` | OAuth sessions |

Suitable for local development and small deployments.

## Redis (Optional)

Enable for lower latency job dispatch and cache reads:

```env
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

CACHE_STORE=redis
QUEUE_CONNECTION=redis
# SESSION_DRIVER=redis  # optional, multi-server
```

### Failover drivers

Graceful degradation when Redis is unavailable:

```env
CACHE_STORE=failover      # redis → database → file
QUEUE_CONNECTION=failover # redis → database
```

## Caching Strategy

| Key | TTL | Purpose |
|-----|-----|---------|
| `portfolio_score_{user_id}` | 600s | Aggregated portfolio assessment |
| `openrouter.models` | 3600s | OpenRouter model catalog |

Invalidated on:

- Repository sync complete
- AI analysis complete or fail
- Manual portfolio refresh (`POST /analysis/run`)
- Settings sync (explicit forget)

## Queue Tuning

| Job | Timeout | Notes |
|-----|---------|-------|
| `AnalyzeRepositoryJob` | 300s | Must align with AI provider budgets |
| `SyncRepositoriesJob` | 180s | Paginated GitHub fetch |

Critical: `DB_QUEUE_RETRY_AFTER=330` > job timeout 300.

Production worker:

```bash
php artisan queue:work --tries=1 --timeout=330
```

Development:

```bash
php artisan queue:listen --tries=1 --timeout=0
```

## Database Indexes

Repositories table indexes (from migrations):

- `(github_account_id, is_private)`
- `analysis_status`
- `stars`

Analyses table:

- `(user_id, status)`
- `(repository_id, status)`

## GitHub API

- Paginated fetch (100/page, max 10 pages)
- HTTP retry (2 attempts) on connection failures
- README fetched per repo during sync — large accounts take longer

## AI Provider Budgets

| Provider | Budget setting | Default |
|----------|----------------|---------|
| OpenRouter | `OPENROUTER_TOTAL_BUDGET` | 240s |
| Gemini | Per-request `GEMINI_TIMEOUT` | 45s |

Keep total AI wall time under job timeout (300s).

## Frontend

- Vite production build (`npm run build`) for minified assets
- `php artisan view:cache` and `route:cache` in production
- Soft-search avoids full page reloads on repository filter

## Monitoring

Watch:

- Queue depth (`jobs` table or Redis queue length)
- `failed_jobs` table
- `storage/logs/laravel.log` for rate limits and timeouts

## Related Docs

- [configuration.md](configuration.md)
- [analysis-pipeline.md](analysis-pipeline.md)
- [troubleshooting.md](troubleshooting.md)
