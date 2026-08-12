# Queue Workers

GitRadar uses Laravel's **database queue** for:

- `SyncRepositoriesJob` — GitHub repository import after login/settings sync
- `AnalyzeRepositoryJob` — AI repository analysis (long-running)

---

## Architecture

```
Browser POST /repositories/{id}/analyze
    ↓
Controller sets status=processing
    ↓
AnalyzeRepositoryJob dispatched
    ↓
Immediate HTTP redirect (user not blocked)
    ↓
Supervisor worker picks job
    ↓
AI provider (Gemini or OpenRouter)
    ↓
Validated JSON → PostgreSQL
    ↓
Frontend polls /repositories/{id}/analysis-status
```

---

## Configuration

`.env`:

```env
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=330
```

Job timeouts:
- `AnalyzeRepositoryJob`: 300s
- `SyncRepositoriesJob`: 180s

---

## Supervisor (Production)

Create `/etc/supervisor/conf.d/gitradar-worker.conf`:

```ini
[program:gitradar-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/gitradar/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/gitradar/storage/logs/worker.log
stopwaitsecs=3600
```

Apply:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start gitradar-worker:*
```

---

## After Code Deploy

```bash
sudo supervisorctl restart gitradar-worker:*
```

Required so workers load new job logic.

---

## Failed Jobs

Inspect:

```bash
php artisan queue:failed
php artisan queue:retry all   # after fixing root cause
php artisan queue:flush       # destructive — clear all failed
```

Failed analysis jobs mark repository `analysis_status=failed` and create/update `analyses` record with user-friendly error.

Monitor table: `failed_jobs`

---

## Local Development

```bash
php artisan queue:work --tries=1
```

Or `QUEUE_CONNECTION=sync` in `.env` for synchronous debugging (not for production).

---

## Idempotency Notes

- Re-analyzing a repo resets `processing` state before dispatch
- Stale processing detection via `isStaleProcessing()` on Repository model
- Sync job upserts by `repo_id` — safe to re-run

---

## Related Docs

- [DEPLOYMENT.md](DEPLOYMENT.md)
- [analysis-pipeline.md](analysis-pipeline.md)
- [troubleshooting.md](troubleshooting.md)
