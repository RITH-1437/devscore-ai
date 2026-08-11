# GitRadar — AI Portfolio Analyzer

GitRadar is a Laravel web app that connects to a developer's GitHub account, syncs public repositories, calculates a portfolio score, and uses AI to review individual repositories.

It is useful for developer communities, students, bootcamp members, and job seekers who want quick feedback on their GitHub portfolio: strengths, weaknesses, recruiter signals, resume suggestions, interview questions, and improvement roadmaps.

## Main Features

- GitHub login with OAuth
- Public repository sync from the GitHub API
- Dashboard with repository count, stars, top languages, pinned repositories, and portfolio score
- Repository list with search, language filter, sorting, and pagination
- Individual repository detail pages
- AI analysis for each repository through Google Gemini (direct) or OpenRouter (fallback)
- AI score, difficulty, portfolio level, recruiter rating, hiring probability, and market readiness
- Technical review sections for architecture, security, performance, and code style
- Career suggestions for resume, CV, LinkedIn, target companies, and interviews
- Portfolio insights with language distribution, top repositories, topics, and activity timeline
- Export analyzed repositories as JSON or Markdown
- Background jobs for repository sync and AI analysis

## Tech Stack

- PHP 8.3+
- Laravel 13
- Laravel Socialite for GitHub OAuth
- MySQL by default
- File cache and database queue by default (Redis optional for performance)
- Blade templates
- Tailwind CSS 4
- Alpine.js
- Vite
- OpenRouter API for AI model access (fallback when Gemini is not configured)
- Google Gemini API for direct AI analysis (preferred when configured)

## How The App Works

1. A user signs in with GitHub.
2. The app stores the GitHub account connection.
3. A background job syncs the user's public repositories.
4. Repository metadata and README content are saved locally.
5. The dashboard calculates a portfolio score from repository count, language diversity, documentation, stars, descriptions, and recent activity.
6. The user can open a repository and run AI analysis.
7. AI results are saved and can be viewed later or exported.

## Requirements

Before running the project, install:

- PHP 8.3 or newer
- Composer
- Node.js and npm
- MySQL or MariaDB
- A GitHub OAuth app
- A Google Gemini API key (recommended) or an OpenRouter API key
- Redis (optional, recommended for production performance)

## Setup

Clone the project and enter the folder:

```bash
git clone <your-repository-url>
cd devscore-ai
```

Install PHP dependencies:

```bash
composer install
```

Install JavaScript dependencies:

```bash
npm install
```

Create your environment file:

```bash
cp .env.example .env
```

Generate the Laravel app key:

```bash
php artisan key:generate
```

Create a MySQL database:

```sql
CREATE DATABASE devscore_ai;
```

Update these values in `.env`:

```env
APP_NAME="GitRadar"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=devscore_ai
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=330
CACHE_STORE=file
```

`DB_QUEUE_RETRY_AFTER` must be greater than the AI job timeout (300 seconds). If it is too low, Laravel may re-release a still-running analysis job and duplicate work.

Run the database migrations:

```bash
php artisan migrate
```

## Redis Setup (Optional)

Redis is **not required** to run GitRadar. The app works out of the box with file cache, database sessions, and a database queue. Enable Redis when you want faster cache reads, lower-latency job dispatch, or session storage that scales across multiple app servers.

### Install Redis

**Docker (easiest, all platforms):**

```bash
docker run -d --name gitradar-redis -p 6379:6379 redis:7-alpine
```

**Linux (Ubuntu/Debian):**

```bash
sudo apt update && sudo apt install redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

**Windows:**

- [Memurai](https://www.memurai.com/) (Redis-compatible, native Windows), or
- WSL2 with `sudo apt install redis-server`, or
- Docker Desktop with the command above

### Redis client

GitRadar ships with [predis/predis](https://github.com/predis/predis) (pure PHP, works on Windows without extra extensions). For better throughput on Linux/macOS, install the [phpredis](https://github.com/phpredis/phpredis) extension and set `REDIS_CLIENT=phpredis`.

Verify connectivity:

```bash
php artisan tinker --execute="Illuminate\Support\Facades\Redis::ping();"
```

### Enable Redis drivers

Add or update these values in `.env` after Redis is running:

```env
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Recommended production settings:
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# Optional — faster session I/O when running multiple app instances:
# SESSION_DRIVER=redis
```

### Graceful fallback (Redis optional)

If Redis may be unavailable (local dev, shared hosting), use Laravel's failover drivers instead of hard-coding `redis`:

```env
CACHE_STORE=failover
QUEUE_CONNECTION=failover
```

Failover order:

- **Cache:** redis → database → file
- **Queue:** redis → database

The app keeps working when Redis is down; it automatically uses the next driver in the chain.

To stay on database-only queues (no Redis at all), keep the defaults:

```env
QUEUE_CONNECTION=database
CACHE_STORE=file
SESSION_DRIVER=database
```

### What uses Redis when enabled

| Driver | Used for |
|--------|----------|
| **Cache** | Portfolio score (`portfolio_score_{user_id}`, 10 min TTL), OpenRouter model list (1 hr TTL) |
| **Queue** | Repository sync jobs, AI analysis jobs (`AnalyzeRepositoryJob`) |
| **Session** | OAuth login session data (optional) |

Repository and profile data are stored in MySQL; GitHub API responses are persisted during sync and are not cached separately.

### Run queue worker with Redis

When `QUEUE_CONNECTION=redis`, start the worker the same way:

```bash
php artisan queue:listen --tries=1 --timeout=0
```

Or in production:

```bash
php artisan queue:work redis --tries=3
```

## GitHub OAuth Setup

Create a GitHub OAuth app from:

```text
https://github.com/settings/developers
```

Use these local development values:

```text
Application name: GitRadar
Homepage URL: http://localhost:8000
Authorization callback URL: http://localhost:8000/auth/github/callback
```

Copy the Client ID and Client Secret into `.env`:

```env
GITHUB_CLIENT_ID=your_github_client_id
GITHUB_CLIENT_SECRET=your_github_client_secret
GITHUB_REDIRECT_URI=http://localhost:8000/auth/github/callback
```

The app requests these GitHub scopes:

- `user`
- `public_repo`

## Google Gemini Setup (Recommended)

Create a Google AI Studio API key from:

```text
https://aistudio.google.com/apikey
```

Add it to `.env`:

```env
GEMINI_API_KEY=your_gemini_api_key
```

When `GEMINI_API_KEY` is set, repository analysis uses direct Gemini (`gemini-2.5-flash` by default). OpenRouter is not used unless you remove the Gemini key.

Optional Gemini settings:

```env
GEMINI_MODEL=gemini-2.5-flash
GEMINI_TIMEOUT=45
GEMINI_RETRY_TIMES=0
GEMINI_TEMPERATURE=0.2
GEMINI_MAX_TOKENS=2048
```

**Security:** Never commit your `.env` file or share API keys in chat. If a key is exposed, rotate it immediately in Google AI Studio.

## OpenRouter Setup (Fallback)

If you prefer not to use direct Gemini, leave `GEMINI_API_KEY` empty and configure OpenRouter instead.

Create an OpenRouter API key from:

```text
https://openrouter.ai/keys
```

Add it to `.env`:

```env
OPENROUTER_API_KEY=your_openrouter_api_key
```

Optional OpenRouter settings:

```env
OPENROUTER_MODEL=openai/gpt-4o-mini:free
OPENROUTER_TIMEOUT=90
OPENROUTER_RETRY_TIMES=3
OPENROUTER_TEMPERATURE=0.2
OPENROUTER_MAX_TOKENS=4096
```

The app has a fallback model chain in `config/openrouter.php`, so if one model fails or rate limits, it can try another.

## Run The Project

The easiest way to run everything in development is:

```bash
composer run dev
```

This starts:

- Laravel server at `http://localhost:8000`
- Vite development server
- Queue listener for repository sync and AI analysis jobs

Then open:

```text
http://localhost:8000
```

Sign in with GitHub, wait for repositories to sync, then open a repository and click "Analyze with AI".

## Run Services Manually

If you prefer separate terminals, run these commands:

Terminal 1:

```bash
php artisan serve
```

Terminal 2:

```bash
npm run dev
```

Terminal 3:

```bash
php artisan queue:listen --tries=1 --timeout=0
```

The queue worker is important. Without it, repository syncing and AI analysis will not finish in the background.

## Build For Production

Build frontend assets:

```bash
npm run build
```

Cache Laravel configuration:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

For production, run a real queue worker instead of `queue:listen`, for example:

```bash
php artisan queue:work --tries=1 --timeout=330
```

**Important:** After changing `.env`, AI models, or Gemini/OpenRouter config, stop and restart the queue worker. Long-running workers cache old configuration and will keep using deprecated model names until restarted.

```bash
php artisan optimize:clear
# Then restart queue:work in a fresh terminal
```

## Testing

Run the test suite:

```bash
composer test
```

Or run Laravel's test command directly:

```bash
php artisan test
```

## Useful Commands

Clear cached config:

```bash
php artisan config:clear
```

Clear app cache:

```bash
php artisan cache:clear
```

Retry failed jobs:

```bash
php artisan queue:retry all
```

View failed jobs:

```bash
php artisan queue:failed
```

Run code formatting:

```bash
./vendor/bin/pint
```

## Project Structure

Important folders and files:

```text
app/Http/Controllers        Page and action controllers
app/Jobs                    Background jobs for syncing and AI analysis
app/Models                  User, GitHub account, repository, and analysis models
app/Policies                Repository ownership policy
app/Services                GitHub, Gemini, OpenRouter, sync, score, and analysis services
config/gemini.php           Google Gemini model and request settings
config/openrouter.php       OpenRouter model and request settings
database/migrations         Database schema
resources/views             Blade UI pages and components
resources/css/app.css       Tailwind CSS entry file
resources/js/app.js         Alpine.js entry file
routes/web.php              Web routes
```

## Important Pages

- `/` - Landing page
- `/dashboard` - Main portfolio dashboard
- `/repositories` - Repository browser
- `/repositories/{repository}` - Repository details and AI analysis
- `/analysis` - Portfolio analysis overview
- `/insights` - Portfolio statistics and trends
- `/settings` - GitHub profile, sync button, and logout

## Troubleshooting

If GitHub login fails, check:

- `APP_URL` is `http://localhost:8000`
- `GITHUB_REDIRECT_URI` is `http://localhost:8000/auth/github/callback`
- GitHub OAuth callback URL matches exactly
- You restarted the server after editing `.env`

If repositories do not appear, check:

- The queue worker is running
- Your GitHub account has public repositories
- The GitHub token is valid
- `storage/logs/laravel.log` for API or rate limit errors

If AI analysis does not finish, check:

- `GEMINI_API_KEY` or `OPENROUTER_API_KEY` is set correctly
- The queue worker is running
- Your API provider account has quota available
- `storage/logs/laravel.log` for AI provider errors

If environment changes are not picked up, run:

```bash
php artisan config:clear
```

If Redis is enabled but the app cannot connect, either start Redis or switch back to fallback drivers:

```env
CACHE_STORE=file
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

Or use `CACHE_STORE=failover` and `QUEUE_CONNECTION=failover` to auto-fallback when Redis is down.

## Notes For Community Members

This project is designed as a practical learning app. Good areas to explore are:

- Laravel OAuth authentication
- API integration with GitHub
- Background jobs and database queues
- Prompting AI models for structured JSON
- Portfolio scoring logic
- Blade component-based UI
- Exporting generated analysis as JSON and Markdown

Keep real secrets out of Git. Share `.env.example`, not `.env`.

## License

This project is open-source software under the MIT license.
