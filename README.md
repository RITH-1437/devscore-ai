# GitRadar

GitRadar is a Laravel web application that connects to a developer's GitHub account, syncs public repositories, and uses AI to score portfolios, surface recruiter insights, and suggest career improvements.

After signing in with GitHub, GitRadar syncs your public repositories in the background, lets you run AI analysis on individual repos, and aggregates results into a portfolio score with strengths, weaknesses, and recommendations.

## Features

- GitHub OAuth login (Laravel Socialite)
- Public repository sync from the GitHub API
- Dashboard with repo count, stars, languages, pinned repos, and portfolio score
- Repository browser with search, filters, sorting, and sync polling
- Per-repository AI analysis (scores, technical reviews, career suggestions)
- Portfolio analysis page with aggregated assessment
- Insights page (language distribution, topics, activity timeline, score distribution)
- Developer profile page with GitHub metadata
- Export analyzed repositories as JSON or Markdown
- Light / dark theme (localStorage)
- Background jobs for sync and AI analysis

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.3+, Laravel 13 |
| Database | PostgreSQL (production) / MySQL or SQLite (local) |
| Auth | Laravel Socialite (GitHub OAuth) |
| Queue | Database (default), Redis optional |
| Cache | File (default), Redis optional |
| Frontend | Blade, Tailwind CSS 4, Alpine.js, Vite 8 |
| AI | Google Gemini (primary) or OpenRouter (fallback) |

## Requirements

| Tool | Version |
|------|---------|
| PHP | 8.3+ |
| Composer | 2.x |
| Node.js | 18+ |
| npm | 9+ |
| MySQL / MariaDB | 8.0+ / 10.x (SQLite for tests) |
| GitHub OAuth app | [GitHub Developer Settings](https://github.com/settings/developers) |
| AI API key | Gemini (recommended) or OpenRouter |

Optional: Redis for faster cache/queue.

## Installation

```bash
git clone https://github.com/RITH-1437/devscore-ai.git
cd devscore-ai
composer setup
```

`composer setup` installs dependencies, creates `.env` from `.env.example`, generates `APP_KEY`, runs migrations, and builds frontend assets.

For step-by-step setup, see [docs/installation.md](docs/installation.md).

## Environment Configuration

Copy `.env.example` to `.env` and fill in placeholders. Never commit real credentials.

```bash
cp .env.example .env
php artisan key:generate
```

Minimum required variables:

```env
APP_KEY=
GITHUB_CLIENT_ID=your_github_client_id
GITHUB_CLIENT_SECRET=your_github_client_secret
GITHUB_REDIRECT_URI=http://localhost:8000/auth/github/callback

# At least one AI provider:
GEMINI_API_KEY=          # preferred — enables Gemini
OPENROUTER_API_KEY=      # fallback when GEMINI_API_KEY is empty

QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=330   # must exceed job timeout (300s)
```

Full reference: [docs/environment.md](docs/environment.md)

## Database Setup

Create a MySQL database:

```sql
CREATE DATABASE devscore_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Configure in `.env`:

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

Schema details: [docs/database.md](docs/database.md)

## Running Locally

All-in-one (recommended):

```bash
composer dev
```

Starts Laravel (`php artisan serve`), the queue listener, and Vite concurrently.

Or run separately:

```bash
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
npm run dev
```

Open http://localhost:8000 and sign in with GitHub. Sync and AI analysis require the queue worker.

Production asset build:

```bash
npm run build
```

## Testing

```bash
composer test
```

Equivalent to `php artisan config:clear && php artisan test`.

See [docs/testing.md](docs/testing.md) for test layout and conventions.

## Project Structure

```
app/
  Http/Controllers/     # Web controllers
  Jobs/                 # Queue jobs (sync, AI analysis)
  Models/               # Eloquent models
  Policies/             # Authorization policies
  Services/             # GitHub, AI, scoring services
config/                 # Laravel configuration
database/migrations/    # Database schema
docs/                   # Detailed documentation
resources/views/        # Blade templates
routes/web.php          # Web routes
tests/                  # PHPUnit tests
```

Architecture overview: [docs/architecture.md](docs/architecture.md)

## GitHub OAuth Setup

Create an OAuth app at https://github.com/settings/developers:

| Field | Local development |
|-------|-------------------|
| Homepage URL | `http://localhost:8000` |
| Authorization callback URL | `http://localhost:8000/auth/github/callback` |

Add your client ID and secret to `.env`. Each developer should use their own OAuth app for local development — do not share production credentials.

Details: [docs/authentication.md](docs/authentication.md)

## AI Configuration

Provider selection is automatic: Gemini when `GEMINI_API_KEY` is set, otherwise OpenRouter.

- [AI Analysis overview](docs/ai-analysis.md)
- [AI Providers](docs/ai-providers.md)
- [Gemini integration](docs/gemini-integration.md)
- [OpenRouter integration](docs/openrouter-integration.md)

## Development Commands

```bash
composer setup    # install deps, .env, key, migrate, npm build
composer dev      # serve + queue worker + Vite
composer test     # config:clear + php artisan test
npm run dev       # Vite dev server only
npm run build     # production asset build
./vendor/bin/pint # PHP code formatting
```

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
| AI analysis | [docs/ai-analysis.md](docs/ai-analysis.md) |
| Testing | [docs/testing.md](docs/testing.md) |
| Troubleshooting | [docs/troubleshooting.md](docs/troubleshooting.md) |
| Security (implementation) | [docs/security.md](docs/security.md) |
| Deployment | [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) |
| Contributing (detailed docs) | [docs/contributing.md](docs/contributing.md) |

## Production

- [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) — EC2, Nginx, PHP-FPM
- [docs/QUEUE.md](docs/QUEUE.md) — Supervisor workers
- [docs/PRODUCTION_BACKUP.md](docs/PRODUCTION_BACKUP.md) — PostgreSQL backups

## Security

Report vulnerabilities privately — see [SECURITY.md](SECURITY.md).

For authentication, authorization, token storage, and production hardening details, see [docs/security.md](docs/security.md).

## Contributing

Contributions are welcome. Read [CONTRIBUTING.md](CONTRIBUTING.md) for setup, workflow, and PR guidelines.

Please follow the [Code of Conduct](CODE_OF_CONDUCT.md).

## License

GitRadar is open-source software licensed under the [MIT License](LICENSE).

See [LICENSE](LICENSE) for the full text.
