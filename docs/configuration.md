# Configuration

GitRadar configuration is split between `.env` variables and Laravel config files under `config/`.

## Config Files

| File | Env prefix | Purpose |
|------|------------|---------|
| `config/app.php` | `APP_*` | App name, URL, debug |
| `config/database.php` | `DB_*` | MySQL connection |
| `config/services.php` | `GITHUB_*` | GitHub OAuth (Socialite) |
| `config/gemini.php` | `GEMINI_*` | Google Gemini API |
| `config/openrouter.php` | `OPENROUTER_*` | OpenRouter API |
| `config/queue.php` | `QUEUE_*`, `DB_QUEUE_*` | Job queue drivers |
| `config/cache.php` | `CACHE_*` | Cache drivers |
| `config/session.php` | `SESSION_*` | Session storage |

## Application

```env
APP_NAME="GitRadar"
APP_ENV=local
APP_KEY=                    # php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000
```

`APP_URL` must match your GitHub OAuth callback base URL.

## Database

MySQL is the default connection. See [database.md](database.md).

## Session & Auth

```env
SESSION_DRIVER=database    # default
SESSION_LIFETIME=120
```

Sessions use the `sessions` table (Laravel default migration).

## Queue

```env
QUEUE_CONNECTION=database   # database | redis | failover
DB_QUEUE_RETRY_AFTER=330    # MUST be > AnalyzeRepositoryJob timeout (300)
```

When using Redis:

```env
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

Failover (Redis down → database):

```env
QUEUE_CONNECTION=failover
```

## Cache

```env
CACHE_STORE=file           # file | redis | failover
```

Portfolio scores are cached for 600 seconds under `portfolio_score_{user_id}`.

## GitHub OAuth

Configured in `config/services.php`:

```php
'github' => [
    'client_id'     => env('GITHUB_CLIENT_ID'),
    'client_secret' => env('GITHUB_CLIENT_SECRET'),
    'redirect'      => env('GITHUB_REDIRECT_URI'),
],
```

See [github-integration.md](github-integration.md).

## AI Providers

Provider selection is **not** controlled by an `AI_PROVIDER` env var. `AiAnalysisService` uses Gemini when `GEMINI_API_KEY` is non-empty; otherwise OpenRouter.

| Config file | When used |
|-------------|-----------|
| `config/gemini.php` | `GEMINI_API_KEY` is set |
| `config/openrouter.php` | `GEMINI_API_KEY` is empty |

See [ai-providers.md](ai-providers.md), [gemini-integration.md](gemini-integration.md), [openrouter-integration.md](openrouter-integration.md).

## Vite / Frontend

```env
VITE_APP_NAME="${APP_NAME}"
```

Assets are built via Vite; see [frontend.md](frontend.md).

## Clearing Config Cache

After editing `.env` in production:

```bash
php artisan config:clear
# or
php artisan optimize:clear
```

Restart long-running queue workers — they cache config at boot.

## Related Docs

- [environment.md](environment.md) — full variable reference
- [performance.md](performance.md) — Redis and caching tuning
