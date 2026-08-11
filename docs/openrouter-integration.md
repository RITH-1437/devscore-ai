# OpenRouter Integration

OpenRouter is the **fallback** AI provider when `GEMINI_API_KEY` is not set. Implemented in `App\Services\OpenRouterService`.

## Configuration

File: `config/openrouter.php`

| Setting | Env variable | Default |
|---------|--------------|---------|
| API key | `OPENROUTER_API_KEY` | — |
| Base URL | — | `https://openrouter.ai/api/v1` |
| Default model override | `OPENROUTER_MODEL` | — (uses chain if empty) |
| Per-request timeout | `OPENROUTER_TIMEOUT` | 45s |
| Connect timeout | `OPENROUTER_CONNECT_TIMEOUT` | 10s |
| Total budget | `OPENROUTER_TOTAL_BUDGET` | 240s |
| Retries per model | `OPENROUTER_RETRY_TIMES` | 0 |
| Verify models | `OPENROUTER_VERIFY_MODELS` | true |
| Temperature | `OPENROUTER_TEMPERATURE` | 0.2 |
| Max tokens | `OPENROUTER_MAX_TOKENS` | 2048 |

### Payload limits

Same pattern as Gemini (`OPENROUTER_MAX_README_CHARS`, etc.) — defaults 3000/500/10000.

## Model Fallback Chain

When `OPENROUTER_MODEL` is empty, models are tried in order:

1. `google/gemini-2.0-flash-exp:free`
2. `inclusionai/ling-3.0-flash:free`
3. `nvidia/nemotron-nano-9b-v2:free`
4. `openai/gpt-oss-20b:free`
5. `nvidia/nemotron-nano-12b-v2-vl:free`
6. `openrouter/free` (last — auto-routes but slower)

If `OPENROUTER_VERIFY_MODELS=true`, unavailable models are filtered using a cached catalog (`openrouter.models`, 1 hour TTL).

## API Key

Obtain from https://openrouter.ai/keys:

```env
OPENROUTER_API_KEY=your_openrouter_api_key
```

Leave `GEMINI_API_KEY` empty to activate OpenRouter.

## Request Flow

1. Build prompt from repository metadata + README
2. Iterate model chain within `total_budget` wall-clock limit
3. POST `/chat/completions` per model
4. Parse JSON from response content (multiple parse strategies)
5. Validate against expected schema
6. Return result with metadata (`_model_used`, token counts, `_raw_response`)

## Error Handling

| HTTP Status | Error type |
|-------------|------------|
| 401 | `AI_AUTH_ERROR` |
| 402 | `AI_INSUFFICIENT_CREDITS` |
| 404 | Model unavailable — try next |
| 429 | `AI_RATE_LIMIT` — try next |
| 5xx | `AI_SERVER_ERROR` |

If all models fail: `AI_NO_MODELS_AVAILABLE`.

## Health Check

When OpenRouter is active (`GEMINI_API_KEY` empty), `GET /health/ai` returns:

```json
{
  "provider": "openrouter",
  "configured": true,
  "model": "openrouter chain",
  "available": null,
  "status": "configured"
}
```

## Job Timeout Alignment

`AnalyzeRepositoryJob` timeout is **300 seconds**. Ensure:

- `OPENROUTER_TOTAL_BUDGET` ≤ 300 (default 240)
- `DB_QUEUE_RETRY_AFTER` > 300 (default 330)

## Testing

- `tests/Unit/OpenRouterServiceTest.php`
- `tests/Feature/RepositoryAnalysisTest.php`
- `tests/Unit/AiAnalysisServiceTest.php` (fallback path)

Legacy manual guide: [AI_ANALYSIS_TESTING_GUIDE.md](AI_ANALYSIS_TESTING_GUIDE.md)

## Related Docs

- [ai-providers.md](ai-providers.md)
- [analysis-pipeline.md](analysis-pipeline.md)
- [troubleshooting.md](troubleshooting.md)
