# Installation

## Prerequisites

| Requirement | Version |
|-------------|---------|
| PHP | 8.3+ |
| Composer | 2.x |
| Node.js | 18+ (for Vite) |
| npm | 9+ |
| MySQL / MariaDB | 8.0+ / 10.x |
| GitHub OAuth app | [GitHub Developer Settings](https://github.com/settings/developers) |
| AI API key | Gemini (recommended) or OpenRouter |

Optional:

- **Redis** — faster cache/queue (not required for local dev)

## Quick Setup

```bash
git clone <repository-url>
cd devscore-ai
composer setup
```

`composer setup` runs:

1. `composer install`
2. Copies `.env.example` → `.env` if missing
3. `php artisan key:generate`
4. `php artisan migrate --force`
5. `npm install --ignore-scripts && npm run build`

## Manual Setup

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Environment file

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database

Create a MySQL database:

```sql
CREATE DATABASE devscore_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Set in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=devscore_ai
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations:

```bash
php artisan migrate
```

### 4. GitHub OAuth

Create an OAuth app at https://github.com/settings/developers:

| Field | Local value |
|-------|-------------|
| Homepage URL | `http://localhost:8000` |
| Callback URL | `http://localhost:8000/auth/github/callback` |

Add credentials to `.env` — see [authentication.md](authentication.md).

### 5. AI provider

Configure at least one:

```env
# Preferred
GEMINI_API_KEY=your_gemini_api_key

# Or fallback (when GEMINI_API_KEY is empty)
OPENROUTER_API_KEY=your_openrouter_api_key
```

See [ai-providers.md](ai-providers.md).

### 6. Queue configuration

Default (no Redis):

```env
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=330
```

Start a queue worker — required for sync and AI analysis:

```bash
php artisan queue:listen --tries=1 --timeout=0
```

## Development Server

All-in-one (recommended):

```bash
composer dev
```

This starts Laravel (`php artisan serve`), the queue listener, and Vite concurrently.

Open http://localhost:8000 and sign in with GitHub.

## Production Build

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:work --tries=1 --timeout=330
```

After changing `.env` or AI config, restart the queue worker and run `php artisan optimize:clear`.

## Verify Installation

```bash
composer test
php artisan migrate:status
```

Sign in, confirm repositories sync on the dashboard, then run AI analysis on one repository.

## Related Docs

- [configuration.md](configuration.md)
- [environment.md](environment.md)
- [troubleshooting.md](troubleshooting.md)
