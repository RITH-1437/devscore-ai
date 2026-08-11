# Web Routes

GitRadar exposes **web routes only** — no REST API for external clients. This document lists all routes from `routes/web.php`.

Legend:

- **Auth:** `Public` or `Authenticated` (session + `auth` middleware)
- **Throttle:** rate limit middleware when applied

## Public Routes

| Method | Path | Name | Auth | Purpose |
|--------|------|------|------|---------|
| GET | `/` | `home` | Public | Landing page; redirects to dashboard if logged in |
| GET | `/login` | `login` | Public | Redirects to GitHub OAuth |
| GET | `/auth/github` | `github.login` | Public | Initiate GitHub OAuth |
| GET | `/auth/github/callback` | `github.callback` | Public | OAuth callback (throttle: 10/min) |

## Authenticated Routes

All routes below require `auth` middleware.

### Dashboard

| Method | Path | Name | Throttle | Purpose |
|--------|------|------|----------|---------|
| GET | `/dashboard` | `dashboard` | — | Portfolio dashboard |

### Repositories

| Method | Path | Name | Throttle | Purpose |
|--------|------|------|----------|---------|
| GET | `/repositories` | `repositories.index` | — | List/search/filter repos (JSON partial if `Accept: application/json`) |
| GET | `/repositories/{repository}` | `repositories.show` | — | Repository detail + AI results |
| GET | `/repositories/{repository}/analysis-status` | `repositories.analysis-status` | — | JSON polling endpoint for analysis progress |
| POST | `/repositories/{repository}/analyze` | `repositories.analyze` | 20/min | Start AI analysis (queues job) |
| POST | `/repositories/{repository}/pin` | `repositories.pin` | — | Toggle pin |
| POST | `/repositories/{repository}/feature` | `repositories.feature` | — | Toggle featured |
| GET | `/repositories/{repository}/export/json` | `repositories.export.json` | — | Download analysis as JSON |
| GET | `/repositories/{repository}/export/markdown` | `repositories.export.markdown` | — | Download analysis as Markdown |

Route model binding: `{repository}` resolves to `App\Models\Repository`. Authorization via `RepositoryPolicy`.

### Portfolio Analysis

| Method | Path | Name | Throttle | Purpose |
|--------|------|------|----------|---------|
| GET | `/analysis` | `analysis` | — | Portfolio analysis overview |
| POST | `/analysis/run` | `analysis.run` | 5/min | Refresh cached portfolio assessment |

### Insights

| Method | Path | Name | Purpose |
|--------|------|------|---------|
| GET | `/insights` | `insights` | Portfolio statistics and charts |

### Profile

| Method | Path | Name | Throttle | Purpose |
|--------|------|------|----------|---------|
| GET | `/profile` | `profile.index` | — | Developer profile |
| POST | `/profile/sync` | `profile.sync` | 5/10min | Sync GitHub profile metadata (JSON or redirect) |

### Settings

| Method | Path | Name | Throttle | Purpose |
|--------|------|------|----------|---------|
| GET | `/settings` | `settings` | — | Account settings |
| POST | `/settings/sync` | `settings.sync` | 5/10min | Trigger repository sync job |
| POST | `/logout` | `logout` | — | Log out (CSRF POST) |

### Health

| Method | Path | Name | Purpose |
|--------|------|------|---------|
| GET | `/health/ai` | `health.ai` | JSON AI provider health status |

## JSON Endpoints

These return JSON (not HTML):

| Path | Response |
|------|----------|
| `GET /repositories/{id}/analysis-status` | Analysis state + score |
| `GET /health/ai` | Provider configuration/health |
| `POST /profile/sync` | When `Accept: application/json` |
| `GET /repositories` | HTML partial when `Accept: application/json` |

## CSRF

All POST routes require CSRF token (`@csrf` in forms or `X-CSRF-TOKEN` header for fetch).

## No API Versioning

There is no `/api/v1` prefix, no API tokens, and no Sanctum/Passport integration.

## Related Docs

- [authentication.md](authentication.md)
- [analysis-pipeline.md](analysis-pipeline.md)
