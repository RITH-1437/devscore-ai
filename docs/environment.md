# Environment Variables

Complete reference for GitRadar `.env` settings. Copy from `.env.example` and replace placeholders — never commit real secrets.

## Application

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_NAME` | `GitRadar` | Display name |
| `APP_ENV` | `local` | Environment (`local`, `production`, etc.) |
| `APP_KEY` | — | Encryption key (`php artisan key:generate`) |
| `APP_DEBUG` | `true` | Debug mode (disable in production) |
| `APP_URL` | `http://localhost` | Base URL (must match OAuth callback host) |
| `APP_LOCALE` | `en` | Locale |
| `LOG_CHANNEL` | `stack` | Log channel |
| `LOG_LEVEL` | `debug` | Log verbosity |

## Database

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_CONNECTION` | `mysql` | Database driver |
| `DB_HOST` | `127.0.0.1` | Host |
| `DB_PORT` | `3306` | Port |
| `DB_DATABASE` | `devscore_ai` | Database name |
| `DB_USERNAME` | `root` | Username |
| `DB_PASSWORD` | — | Password |

## Session

| Variable | Default | Description |
|----------|---------|-------------|
| `SESSION_DRIVER` | `database` | `database`, `file`, or `redis` |
| `SESSION_LIFETIME` | `120` | Session lifetime (minutes) |
| `SESSION_ENCRYPT` | `false` | Encrypt session payload |

## Queue

| Variable | Default | Description |
|----------|---------|-------------|
| `QUEUE_CONNECTION` | `database` | `database`, `redis`, or `failover` |
| `DB_QUEUE_RETRY_AFTER` | `330` | Seconds before a reserved job is released — **must exceed 300** (AI job timeout) |

## Cache

| Variable | Default | Description |
|----------|---------|-------------|
| `CACHE_STORE` | `file` | `file`, `redis`, or `failover` |

## Redis (optional)

| Variable | Default | Description |
|----------|---------|-------------|
| `REDIS_CLIENT` | `predis` | `predis` (Windows-friendly) or `phpredis` |
| `REDIS_HOST` | `127.0.0.1` | Redis host |
| `REDIS_PORT` | `6379` | Redis port |
| `REDIS_PASSWORD` | `null` | Password |
| `REDIS_DB` | `0` | Default DB |
| `REDIS_CACHE_DB` | `1` | Cache DB |

## GitHub OAuth

| Variable | Example | Description |
|----------|---------|-------------|
| `GITHUB_CLIENT_ID` | `your_github_client_id` | OAuth app client ID |
| `GITHUB_CLIENT_SECRET` | `your_github_client_secret` | OAuth app secret |
| `GITHUB_REDIRECT_URI` | `http://localhost:8000/auth/github/callback` | Callback URL |

## Google Gemini (primary AI)

When `GEMINI_API_KEY` is set, all repository analysis uses Gemini directly.

| Variable | Default | Description |
|----------|---------|-------------|
| `GEMINI_API_KEY` | — | API key from [Google AI Studio](https://aistudio.google.com/apikey) |
| `GEMINI_MODEL` | `gemini-2.5-flash` | Primary model (fallback: `gemini-flash-latest`) |
| `GEMINI_TIMEOUT` | `45` | Request timeout (seconds) |
| `GEMINI_CONNECT_TIMEOUT` | `10` | Connection timeout (seconds) |
| `GEMINI_RETRY_TIMES` | `0` | HTTP retry count |
| `GEMINI_MAX_TOKENS` | `8192` | Max output tokens |
| `GEMINI_TEMPERATURE` | `0.2` | Generation temperature |
| `GEMINI_MAX_README_CHARS` | `3000` | README truncation limit |
| `GEMINI_MAX_DESCRIPTION_CHARS` | `500` | Description truncation limit |
| `GEMINI_MAX_PROMPT_CHARS` | `10000` | Total prompt size cap |

## OpenRouter (fallback AI)

Used only when `GEMINI_API_KEY` is empty.

| Variable | Default | Description |
|----------|---------|-------------|
| `OPENROUTER_API_KEY` | — | API key from [OpenRouter](https://openrouter.ai/keys) |
| `OPENROUTER_MODEL` | — | Optional single-model override |
| `OPENROUTER_TIMEOUT` | `45` | Per-request timeout (seconds) |
| `OPENROUTER_CONNECT_TIMEOUT` | `10` | Connection timeout (seconds) |
| `OPENROUTER_TOTAL_BUDGET` | `240` | Max wall-clock time across fallbacks (seconds) |
| `OPENROUTER_RETRY_TIMES` | `0` | Retries per model |
| `OPENROUTER_MAX_TOKENS` | `2048` | Max output tokens |
| `OPENROUTER_VERIFY_MODELS` | `true` | Pre-check model availability against catalog |
| `OPENROUTER_TEMPERATURE` | `0.2` | Generation temperature |

## Frontend

| Variable | Default | Description |
|----------|---------|-------------|
| `VITE_APP_NAME` | `${APP_NAME}` | Exposed to Vite build |

## Variables That Do NOT Exist

GitRadar does **not** implement:

- `AI_PROVIDER` — provider is inferred from `GEMINI_API_KEY`
- `GROQ_API_KEY` / Groq integration
- Public REST API tokens

## Security Reminder

- Never commit `.env` to version control
- Use placeholders in documentation and examples
- Rotate keys immediately if exposed

See [security.md](security.md).
