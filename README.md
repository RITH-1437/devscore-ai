# GitRadar — AI GitHub Portfolio Analyzer

GitRadar is a Laravel web application that connects to a developer's GitHub account, syncs public repositories, and uses AI to score portfolios, surface recruiter insights, and suggest career improvements.

## Overview

After signing in with GitHub, GitRadar syncs your public repositories in the background, lets you run AI analysis on individual repos, and aggregates results into a portfolio score with strengths, weaknesses, and recommendations.

## Features

- GitHub OAuth login (Socialite)
- Public repository sync from the GitHub API
- Dashboard with repo count, stars, languages, pinned repos, and portfolio score
- Repository browser with search, filters, sorting, and soft-search polling
- Per-repository AI analysis (scores, technical reviews, career suggestions)
- Portfolio analysis page with aggregated assessment
- Insights page (language distribution, topics, activity timeline, score distribution)
- Developer profile page with GitHub metadata
- Export analyzed repositories as JSON or Markdown
- Light / dark / system theme (localStorage)
- Background jobs for sync and AI analysis

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.3+, Laravel 13 |
| Database | MySQL |
| Auth | Laravel Socialite (GitHub OAuth) |
| Queue | Database (default), Redis optional |
| Cache | File (default), Redis optional |
| Frontend | Blade, Tailwind CSS 4, Alpine.js, Vite |
| AI | Google Gemini (primary) or OpenRouter (fallback) |

## Architecture

See [docs/architecture.md](docs/architecture.md) for system diagrams, service layers, and job flow.

## Installation

See [docs/installation.md](docs/installation.md) for prerequisites, setup, and first run.

## Configuration

See [docs/configuration.md](docs/configuration.md) and [docs/environment.md](docs/environment.md) for all environment variables and config files.

## AI

Provider selection is automatic: Gemini when `GEMINI_API_KEY` is set, otherwise OpenRouter. There is no `AI_PROVIDER` switch and no Groq integration.

- [AI Analysis overview](docs/ai-analysis.md)
- [AI Providers](docs/ai-providers.md)
- [Gemini integration](docs/gemini-integration.md)
- [OpenRouter integration](docs/openrouter-integration.md)
- [Analysis pipeline](docs/analysis-pipeline.md)

## Documentation

| Topic | Document |
|-------|----------|
| Architecture | [docs/architecture.md](docs/architecture.md) |
| Installation | [docs/installation.md](docs/installation.md) |
| Configuration | [docs/configuration.md](docs/configuration.md) |
| Environment variables | [docs/environment.md](docs/environment.md) |
| Database schema | [docs/database.md](docs/database.md) |
| Authentication | [docs/authentication.md](docs/authentication.md) |
| GitHub integration | [docs/github-integration.md](docs/github-integration.md) |
| Repository sync | [docs/repository-sync.md](docs/repository-sync.md) |
| AI analysis | [docs/ai-analysis.md](docs/ai-analysis.md) |
| AI providers | [docs/ai-providers.md](docs/ai-providers.md) |
| Gemini | [docs/gemini-integration.md](docs/gemini-integration.md) |
| OpenRouter | [docs/openrouter-integration.md](docs/openrouter-integration.md) |
| Analysis pipeline | [docs/analysis-pipeline.md](docs/analysis-pipeline.md) |
| Insights page | [docs/insights.md](docs/insights.md) |
| Profile page | [docs/profile.md](docs/profile.md) |
| Themes | [docs/themes.md](docs/themes.md) |
| Frontend | [docs/frontend.md](docs/frontend.md) |
| Web routes | [docs/api.md](docs/api.md) |
| Testing | [docs/testing.md](docs/testing.md) |
| Troubleshooting | [docs/troubleshooting.md](docs/troubleshooting.md) |
| Security | [docs/security.md](docs/security.md) |
| Performance | [docs/performance.md](docs/performance.md) |
| Contributing | [docs/contributing.md](docs/contributing.md) |
| Changelog | [docs/changelog.md](docs/changelog.md) |

### Legacy docs

These predate the current documentation system and remain for reference:

- [docs/AI_ANALYSIS_TESTING_GUIDE.md](docs/AI_ANALYSIS_TESTING_GUIDE.md)
- [docs/TRANSFORMATION_REPORT.md](docs/TRANSFORMATION_REPORT.md)
- [docs/CACHE_CLEAR_INSTRUCTIONS.md](docs/CACHE_CLEAR_INSTRUCTIONS.md)

## Development Commands

```bash
composer setup    # install deps, .env, key, migrate, npm build
composer dev      # serve + queue worker + Vite (concurrently)
composer test     # config:clear + php artisan test
npm run dev       # Vite dev server only
npm run build     # production asset build
php artisan queue:listen --tries=1 --timeout=0   # queue worker
./vendor/bin/pint # code formatting
```

## Environment Summary

Minimum required variables:

```env
APP_KEY=
GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=
GITHUB_REDIRECT_URI=

# At least one AI provider:
GEMINI_API_KEY=          # preferred — enables Gemini
OPENROUTER_API_KEY=      # fallback when GEMINI_API_KEY is empty

QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=330   # must exceed job timeout (300s)
```

See [docs/environment.md](docs/environment.md) for the full reference.

## Troubleshooting

Common issues (OAuth, sync, AI, queue) are covered in [docs/troubleshooting.md](docs/troubleshooting.md).

## Contributing

See [docs/contributing.md](docs/contributing.md).

## License

MIT
