# Gemini Integration

Direct Google Gemini integration via `App\Services\GoogleGeminiService`. Active when `GEMINI_API_KEY` is set in `.env`.

## Configuration

File: `config/gemini.php`

| Setting | Env variable | Default |
|---------|--------------|---------|
| API key | `GEMINI_API_KEY` | — |
| Base URL | — | `https://generativelanguage.googleapis.com/v1beta` |
| Primary model | `GEMINI_MODEL` | `gemini-2.5-flash` |
| Fallback model | — | `gemini-flash-latest` |
| Timeout | `GEMINI_TIMEOUT` | 45s |
| Connect timeout | `GEMINI_CONNECT_TIMEOUT` | 10s |
| Retries | `GEMINI_RETRY_TIMES` | 0 |
| Temperature | `GEMINI_TEMPERATURE` | 0.2 |
| Max tokens | `GEMINI_MAX_TOKENS` | 8192 |

### Payload limits

| Setting | Default |
|---------|---------|
| `GEMINI_MAX_README_CHARS` | 3000 |
| `GEMINI_MAX_DESCRIPTION_CHARS` | 500 |
| `GEMINI_MAX_PROMPT_CHARS` | 10000 |

## Structured Output

Gemini requests include a `responseSchema` defined in `config/gemini.php` matching the analysis JSON shape (score, reviews, career fields). This reduces parse failures compared to free-form text.

Required schema fields: `score`, `difficulty`, `portfolio_level`, `recruiter_rating`, `estimated_experience`, `hiring_probability`, `market_readiness`, `strengths`, `weaknesses`, `recommendations`.

## Model Fallback

Models are tried in order from `config('gemini.models')`:

1. `GEMINI_MODEL` (default `gemini-2.5-flash`)
2. `gemini-flash-latest`

## API Key

Obtain from [Google AI Studio](https://aistudio.google.com/apikey):

```env
GEMINI_API_KEY=your_gemini_api_key
```

When this key is set, OpenRouter is **not** used for analysis.

## Health Check

When Gemini is the active provider, `GET /health/ai` returns:

```json
{
  "provider": "gemini",
  "configured": true,
  "model": "gemini-2.5-flash",
  "available": true,
  "status": "ok"
}
```

(Exact fields from `GoogleGeminiService::healthCheck()`.)

## Error Types

Gemini errors map to `AnalysisException` types including:

- `AI_AUTH_ERROR`, `AI_PERMISSION_ERROR`
- `AI_RATE_LIMIT`
- `AI_INVALID_RESPONSE`, `AI_PARSE_ERROR`
- `AI_TIMEOUT`, `AI_NETWORK_ERROR`

User-facing messages are in `AnalysisException::friendlyMessage()`.

## Testing

Unit tests: `tests/Unit/GoogleGeminiServiceTest.php` (HTTP faked).

Run: `composer test`

## Related Docs

- [ai-providers.md](ai-providers.md)
- [analysis-pipeline.md](analysis-pipeline.md)
- [environment.md](environment.md)
