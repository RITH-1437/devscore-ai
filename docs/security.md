# Security

Security practices and sensitive data handling in GitRadar.

## Secrets Management

Never commit:

- `.env` file
- `*.pem`, `*.key` private keys
- API keys (`GEMINI_API_KEY`, `OPENROUTER_API_KEY`)
- GitHub OAuth secrets (`GITHUB_CLIENT_SECRET`)
- GitHub user access tokens

Use `.env.example` with placeholders only. Rotate keys immediately if exposed.

## Authentication

- GitHub OAuth via Socialite (authorization code flow)
- Session regeneration after login (anti-fixation)
- OAuth callback rate limited (10/min)
- Logout requires POST + CSRF token
- **OAuth account binding:** existing `github_id` always logs in as the original linked user — `user_id` is never reassigned on re-login

## Authorization

- `RepositoryPolicy` enforces ownership — users cannot access other users' repositories
- **Scoped route binding:** `{repository}` resolves only within `Repository::forUser(auth()->user())` → 404 for foreign IDs
- `$this->authorize()` on all repository mutations

## Token Storage

GitHub `access_token`:

- Encrypted at rest via Laravel `Crypt` (on save)
- Hidden from model JSON serialization (`$hidden`)
- Refreshed on each OAuth login
- Legacy plaintext tokens remain readable until the user re-authenticates (then re-saved encrypted)

## CSRF Protection

Laravel CSRF middleware applies to all POST routes (analyze, sync, logout, pin, feature).

## Input & Output

- Repository search uses parameterized queries (`whereRaw` with bindings)
- Sort fields whitelisted in controller
- Export endpoints require authorization and analyzed state
- AI prompts truncate README/description to configured limits
- Blade templates use `{{ }}` escaping for user content

## AI Provider Security

- API keys read from environment only — never exposed to frontend
- Do not log Authorization headers, API keys, or OAuth tokens
- AI responses validated (score 0–100, typed arrays) before persistence
- Malformed AI JSON does not crash the app — explicit failed state
- Repository README/content treated as untrusted (prompt injection awareness)

## Rate Limiting

| Endpoint | Limit |
|----------|-------|
| OAuth callback | 10/min |
| Repository analyze | 20/min |
| Portfolio analysis | 5/min |
| Profile/settings sync | 5 per 10 min |
| Pin / feature | 30/min |
| `/health/ai` | 12/min |

## Security Headers (Application)

`SecurityHeaders` middleware sets:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Strict-Transport-Security` (when HTTPS/production)
- `Permissions-Policy` (camera/mic/geo disabled)

Mirror these at Nginx for defense in depth.

## Production Checklist

| Item | Action |
|------|--------|
| `APP_DEBUG` | `false` |
| `APP_KEY` | Unique per environment |
| `LOG_LEVEL` | `warning` or `error` |
| `SESSION_SECURE_COOKIE` | `true` |
| `SESSION_ENCRYPT` | `true` |
| HTTPS | Terminate TLS at Nginx |
| Queue workers | Supervisor, non-root user |
| Database | Least-privilege DB user |
| Backups | See [PRODUCTION_BACKUP.md](PRODUCTION_BACKUP.md) |
| Dependencies | Run `composer audit` regularly |

## Queue Job Security

`AnalyzeRepositoryJob` re-verifies `RepositoryPolicy::analyze` before calling AI — prevents cross-user analysis if job payload is tampered.

## Health Endpoints

- `GET /health` — public, returns `{ "status": "ok" }` only
- `GET /up` — Laravel framework health (DB check)
- `GET /health/ai` — authenticated, minimal status (no API keys)

## Dependency Updates

```bash
composer audit
composer update   # staging first
npm audit
```

Review Laravel security advisories: https://github.com/laravel/framework/security

## Reporting Issues

Report security vulnerabilities privately — see [SECURITY.md](../SECURITY.md) at the repository root. Do not open public issues for undisclosed vulnerabilities.

For implementation-level security details in this document, see the sections above.

## Related Docs

- [SECURITY.md](../SECURITY.md) — vulnerability reporting policy
- [ULTIMATE_AUDIT.md](ULTIMATE_AUDIT.md)
- [FINAL_PRODUCTION_AUDIT.md](FINAL_PRODUCTION_AUDIT.md)
- [authentication.md](authentication.md)
- [DEPLOYMENT.md](DEPLOYMENT.md)
- [environment.md](environment.md)
