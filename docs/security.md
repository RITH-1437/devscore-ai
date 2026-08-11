# Security

Security practices and sensitive data handling in GitRadar.

## Secrets Management

Never commit:

- `.env` file
- API keys (`GEMINI_API_KEY`, `OPENROUTER_API_KEY`)
- GitHub OAuth secrets (`GITHUB_CLIENT_SECRET`)
- GitHub user access tokens

Use `.env.example` with placeholders only. Rotate keys immediately if exposed.

## Authentication

- GitHub OAuth via Socialite (industry-standard authorization code flow)
- Session regeneration after login (anti-fixation)
- OAuth callback rate limited (10/min)
- Logout requires POST + CSRF token

## Authorization

- `RepositoryPolicy` enforces ownership — users cannot access other users' repositories
- Route model binding + `$this->authorize()` on repository actions

## Token Storage

GitHub `access_token` stored in database, hidden from model serialization. Tokens refreshed on each OAuth login.

## CSRF Protection

Laravel CSRF middleware applies to all POST routes (analyze, sync, logout, pin, feature).

## Input & Output

- Repository search uses parameterized queries (`whereRaw` with bindings)
- Export endpoints require authorization and analyzed state
- AI prompts truncate README/description to configured limits (DoS mitigation)

## AI Provider Security

- API keys read from environment only
- No keys logged in production paths (debug logs may include request metadata — disable debug in production)
- AI responses validated before persistence — malformed output rejected

## Production Checklist

| Item | Action |
|------|--------|
| `APP_DEBUG` | Set `false` |
| `APP_KEY` | Unique per environment |
| HTTPS | Terminate TLS at reverse proxy |
| Queue workers | Run as non-root service account |
| Database | Least-privilege DB user |
| Redis | Password protect if exposed |
| Logs | Restrict access to `storage/logs/` |

## Dependency Updates

```bash
composer update
npm update
```

Review Laravel security advisories: https://github.com/laravel/framework/security

## Reporting Issues

Report security vulnerabilities privately to project maintainers — do not open public issues for undisclosed vulnerabilities.

## Related Docs

- [authentication.md](authentication.md)
- [environment.md](environment.md)
