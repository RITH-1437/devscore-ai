# AI Analysis

GitRadar analyzes individual GitHub repositories with AI, producing structured scores and actionable feedback.

## What AI Analysis Returns

Each successful analysis includes (stored in `repositories.ai_analysis` and normalized in `analyses`):

| Field | Description |
|-------|-------------|
| `score` | 0–100 repository quality score |
| `difficulty` | beginner / intermediate / advanced / expert |
| `portfolio_level` | junior / mid / senior / staff / principal |
| `recruiter_rating` | 0–100 recruiter appeal |
| `estimated_experience` | Experience level estimate |
| `hiring_probability` | 0–100 |
| `market_readiness` | Market readiness label |
| `strengths`, `weaknesses`, `recommendations` | String arrays (max 3 each) |
| `architecture_review`, `security_review`, `performance_review`, `code_style_review` | Technical review bullets |
| `missing_features` | Suggested missing features |
| `resume_suggestions`, `cv_suggestions`, `linkedin_suggestions` | Career content |
| `interview_questions`, `best_companies`, `improvement_roadmap` | Career guidance |

## User Flow

1. Open repository detail page (`/repositories/{id}`)
2. Click **Analyze with AI**
3. Controller sets `analysis_status=processing`, dispatches `AnalyzeRepositoryJob`
4. Page polls `/repositories/{id}/analysis-status` for completion
5. Results render from `ai_analysis` JSON

## Provider Selection

Handled by `AiAnalysisService` — see [ai-providers.md](ai-providers.md):

- **Gemini** when `GEMINI_API_KEY` is set
- **OpenRouter** when Gemini key is empty

There is no Groq integration and no `AI_PROVIDER` environment variable.

## Portfolio Score

The portfolio score is **not** a separate AI call. `PortfolioScoreService` computes a weighted average of per-repository `ai_analysis.score` values (weight: `sqrt(stars + 1)`).

If no repository has been analyzed, the portfolio score is `null` (not zero).

## Rate Limits

| Route | Limit |
|-------|-------|
| POST `/repositories/{id}/analyze` | 20/minute per user |
| POST `/analysis/run` | 5/minute (portfolio cache refresh only) |

## Error Handling

Failures set `analysis_status=failed` and store a user-friendly message in `analyses.error_message`. Error types are defined in `AnalysisException` (e.g. `AI_RATE_LIMIT`, `AI_AUTH_ERROR`).

Never persists a fake score of 0 on failure.

## Health Check

Authenticated endpoint `GET /health/ai` returns JSON status for the active provider. See [api.md](api.md).

## Related Docs

- [ai-providers.md](ai-providers.md)
- [analysis-pipeline.md](analysis-pipeline.md)
- [gemini-integration.md](gemini-integration.md)
- [openrouter-integration.md](openrouter-integration.md)

### Legacy

- [AI_ANALYSIS_TESTING_GUIDE.md](AI_ANALYSIS_TESTING_GUIDE.md) — manual testing notes (OpenRouter-focused)
