# GitHub Integration

GitRadar integrates with the GitHub REST API using the user's OAuth access token stored on `GithubAccount.access_token`.

## Service

`App\Services\GithubService` — all GitHub HTTP calls.

Base URL: `https://api.github.com`

## Endpoints Used

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/user/repos` | Paginated public repositories (up to 1000) |
| GET | `/repos/{owner}/{repo}/readme` | Repository README (base64 decoded) |
| GET | `/user` | Authenticated user profile |

### Repository listing

- `per_page=100`, sorted by `updated`
- `visibility=public` filter
- Max 10 pages (1000 repos safety cap)
- Retries: 2 attempts with 500ms delay
- Handles 401 (invalid token) and 403 (rate limit) explicitly

### README fetch

Fetches and decodes README for each repository during sync. Failures are logged but do not block other repos.

### Profile

Used by:

- `RepositorySyncService::syncProfile()` during full sync
- `GithubService::updateProfileFromApi()` during profile-only sync (`ProfileController::sync`)

## OAuth Configuration

`config/services.php`:

```php
'github' => [
    'client_id'     => env('GITHUB_CLIENT_ID'),
    'client_secret' => env('GITHUB_CLIENT_SECRET'),
    'redirect'      => env('GITHUB_REDIRECT_URI'),
],
```

Socialite driver: `github` with scopes `user`, `public_repo`.

## Token Storage

Access tokens are stored in `github_accounts.access_token` and hidden from JSON serialization (`GithubAccount::$hidden`).

Tokens are refreshed on each OAuth login via `updateOrCreate`.

## Rate Limits

GitHub API rate limits apply per token. On 403, `GithubService` throws with a message referencing `X-RateLimit-Reset` when available.

## Portfolio URL

`GithubAccount` exposes a computed `portfolio_url` attribute from the `blog` field — normalized to HTTPS, validated, never pointing to localhost routes in production contexts.

## Error Handling

| Status | Behavior |
|--------|----------|
| 401 | "GitHub token is invalid or expired" |
| 403 | Rate limit message |
| Connection errors | Wrapped in `RuntimeException` with log entry |

## Related Docs

- [authentication.md](authentication.md)
- [repository-sync.md](repository-sync.md)
- [profile.md](profile.md)
