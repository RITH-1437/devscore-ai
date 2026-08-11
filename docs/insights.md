# Insights

The Insights page (`/insights`) provides portfolio-wide statistics and visual summaries derived from synced repository data and AI analysis results.

## Route

| Method | Path | Controller |
|--------|------|------------|
| GET | `/insights` | `InsightsController::index` |

Requires authentication.

## Data Sources

All data is scoped to the authenticated user via `Repository::forUser($user)`.

### Repository metrics

- Total forks, stars, open issues, watchers
- Language distribution (`countBy` on `language`)
- Top 10 repositories by stars
- Topic frequency (top 20)
- Activity timeline (repos grouped by `github_created_at` year)
- Most recently pushed repository
- Documentation coverage (% with non-empty README)
- Recently active count (pushed within 90 days)

### AI-derived metrics

- Analyzed vs not analyzed counts
- Analysis coverage percentage
- Best / weakest analyzed repositories by AI score
- High-scoring repos (score ≥ 75, top 5)
- Low-scoring repos (score < 60, top 5)
- Score distribution buckets:
  - Excellent: ≥ 90
  - Strong: 75–89
  - Developing: 60–74
  - Needs work: 40–59
  - Weak: < 40

### Portfolio assessment

Cached via `portfolio_score_{user_id}` (600s TTL), same key as Dashboard and Analysis pages:

- Portfolio score (weighted average of AI scores)
- Aggregated strengths, weaknesses, recommendations

## View

Template: `resources/views/insights/index.blade.php`

Uses the app layout with sidebar navigation.

## Related Docs

- [ai-analysis.md](ai-analysis.md) — what AI scores mean
- [profile.md](profile.md) — developer profile page
- [architecture.md](architecture.md) — PortfolioScoreService
