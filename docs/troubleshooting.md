# Troubleshooting

Common GitRadar issues and fixes.

## GitHub Login Fails

**Symptoms:** Redirect error, "OAuth session expired", or callback mismatch.

**Checks:**

1. `APP_URL` matches your browser URL (include port): `http://localhost:8000`
2. `GITHUB_REDIRECT_URI` exactly matches GitHub OAuth app callback URL
3. `GITHUB_CLIENT_ID` and `GITHUB_CLIENT_SECRET` are correct
4. Restart server after `.env` changes
5. Run `php artisan config:clear`

## Repositories Not Appearing

**Symptoms:** Empty dashboard after login.

**Checks:**

1. Queue worker is running:
   ```bash
   php artisan queue:listen --tries=1 --timeout=0
   ```
   Or use `composer dev`.
2. User has public repositories on GitHub
3. Check `storage/logs/laravel.log` for sync errors
4. Manually trigger sync from Settings
5. Inspect `failed_jobs` table: `php artisan queue:failed`

## AI Analysis Stuck on "Processing"

**Symptoms:** Status never completes.

**Checks:**

1. Queue worker running (analysis runs in `AnalyzeRepositoryJob`)
2. Job timeout: 300s — wait or check logs
3. Stale processing auto-expires after 5 minutes — retry analysis
4. `DB_QUEUE_RETRY_AFTER` must be **> 300** (default 330)
5. Restart queue worker after config changes

## AI Analysis Fails Immediately

**Symptoms:** `analysis_status=failed` with error message.

| Error hint | Fix |
|------------|-----|
| Authentication failed | Verify `GEMINI_API_KEY` or `OPENROUTER_API_KEY` |
| Rate limited | Wait and retry; try different model/time |
| Not configured | Set at least one AI key |
| Credits exhausted | Top up OpenRouter balance |
| Invalid response | Transient — retry; check provider status |

Check logs: `storage/logs/laravel.log`

Provider status:

- Gemini: https://aistudio.google.com/
- OpenRouter: https://status.openrouter.ai/

## Environment Changes Not Applied

```bash
php artisan optimize:clear
```

Restart queue workers — they cache config at startup.

## Redis Connection Errors

If Redis is enabled but not running:

```env
CACHE_STORE=file
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

Or use failover:

```env
CACHE_STORE=failover
QUEUE_CONNECTION=failover
```

## Portfolio Score Shows Empty

Portfolio score requires at least one **completed** AI analysis. Run analysis on a repository first, then visit Dashboard or POST `/analysis/run` to refresh cache.

## Session / CSRF Errors

- Ensure `@csrf` on forms
- For fetch/AJAX, send `X-CSRF-TOKEN` from meta tag
- Logout requires POST to `/logout`

## Cache Issues

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

See also [CACHE_CLEAR_INSTRUCTIONS.md](CACHE_CLEAR_INSTRUCTIONS.md).

## Getting Help

1. Check `storage/logs/laravel.log`
2. Run `GET /health/ai` when logged in
3. Run `composer test` to verify local setup
4. Review [analysis-pipeline.md](analysis-pipeline.md) for state flow

## Related Docs

- [installation.md](installation.md)
- [configuration.md](configuration.md)
- [openrouter-integration.md](openrouter-integration.md)
- [gemini-integration.md](gemini-integration.md)
