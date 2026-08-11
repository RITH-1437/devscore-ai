# Profile

The Profile page displays the authenticated developer's GitHub identity and portfolio summary.

## Route

| Method | Path | Controller | Notes |
|--------|------|------------|-------|
| GET | `/profile` | `ProfileController::index` | Main profile view |
| POST | `/profile/sync` | `ProfileController::sync` | Refresh GitHub profile metadata |

Both require authentication. Profile sync is throttled: 5 requests per 10 minutes.

## Data Displayed

Sourced from `GithubAccount` linked to the authenticated user:

- Avatar, name, username, bio, company, location
- Portfolio URL (computed from `blog` field)
- Followers, following, public repos, public gists
- GitHub account creation date

### Repository summary

- Top 6 repositories by stars
- 5 most recently pushed repositories
- Activity timeline by year (repo creation)
- Primary language
- Total stars/forks across synced repos
- Analyzed repository count

### Portfolio score

Same cached assessment as Dashboard (`portfolio_score_{user_id}`).

## Profile Sync

`POST /profile/sync` refreshes **profile metadata only** (not repositories):

- Calls `GithubService::updateProfileFromApi()`
- Updates `github_accounts` fields from GitHub `/user`

Supports:

- **Form POST** — redirect with flash message
- **JSON request** (`Accept: application/json`) — `{ "success": bool, "message": "..." }`

Does not dispatch `SyncRepositoriesJob`. Use Settings → Sync Repositories for full repo sync.

## Missing Account

If user has no `GithubAccount`, renders `profile.not-found` view.

## Related Docs

- [github-integration.md](github-integration.md)
- [repository-sync.md](repository-sync.md)
- [authentication.md](authentication.md)
