# AI Analysis Testing & Troubleshooting Guide

## Overview

The AI analysis feature has been enhanced with comprehensive error handling and logging to help diagnose and fix issues quickly.

## What Was Fixed

### 1. HTTP Client Error Handling
- **Connection errors** are now explicitly caught and logged
- **Request exceptions** (timeouts, HTTP errors) are properly handled
- **Timeout detection** identifies slow responses vs network issues
- All exceptions include detailed context in logs

### 2. Detailed Logging
All requests now log:
- Request parameters (model, timeouts, prompt length)
- Response status, length, and headers
- Error responses with truncated body (500 chars max)
- JSON parsing attempts and which strategy succeeded
- Response structure validation results
- API key is NEVER logged (only first 10 chars)

### 3. Response Validation
- Validates `choices` array exists and is not empty
- Validates `message` object structure
- Validates content is not empty
- Throws specific AnalysisException types for each failure type
- Logs full response on structure validation failures

### 4. JSON Parsing
- 5 recovery strategies with detailed logging
- Logs which strategy succeeded
- Logs failures with error messages
- Handles markdown-wrapped JSON, trailing commas, truncated JSON

## Testing the AI Analysis

### Prerequisites

1. **Configure OpenRouter API Key** in `.env`:
   ```env
   OPENROUTER_API_KEY=your_actual_api_key_here
   ```

2. **Optional Configuration**:
   ```env
   # Override the default model
   OPENROUTER_MODEL=openai/gpt-4o-mini:free
   
   # Adjust timeouts if needed
   OPENROUTER_TIMEOUT=60
   OPENROUTER_CONNECT_TIMEOUT=10
   OPENROUTER_TOTAL_BUDGET=120
   ```

3. **Clear config cache**:
   ```bash
   php artisan config:clear
   ```

### Test Steps

1. **Login to the application** with GitHub OAuth
2. **Navigate to a repository page**
3. **Click "Analyze with AI"** button
4. **Watch the logs** in real-time:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### What to Look For in Logs

#### Successful Analysis
```
[INFO] OpenRouter: Starting repository analysis
[DEBUG] OpenRouter: Making API request
[DEBUG] OpenRouter: Received response (status: 200)
[DEBUG] OpenRouter: Response structure (has_choices: true)
[DEBUG] OpenRouter: Extracted content (content_length: 2547)
[DEBUG] OpenRouter: Parsing JSON
[DEBUG] OpenRouter: JSON parsed successfully (direct decode)
[INFO] OpenRouter: Analysis successful (model: openai/gpt-4o-mini:free)
[INFO] RepositoryAnalysisService: Analysis completed
```

#### Common Errors

**1. Missing API Key**
```
[ERROR] OpenRouter: OPENROUTER_API_KEY is not configured
Error Type: AUTH_ERROR
```
**Fix**: Add OPENROUTER_API_KEY to `.env`

**2. Invalid API Key**
```
[ERROR] OpenRouter: Authentication failed (status: 401)
Error Type: AUTH_ERROR
```
**Fix**: Check your OpenRouter API key at https://openrouter.ai/keys

**3. Rate Limit**
```
[WARNING] OpenRouter: Rate limited (status: 429)
Error Type: RATE_LIMIT
```
**Fix**: Wait a few minutes and try again. Free tier has rate limits.

**4. Model Not Available**
```
[WARNING] OpenRouter: Model not found (status: 404)
Error Type: MODEL_UNAVAILABLE
```
**Fix**: The model slug may be outdated. Check available models at https://openrouter.ai/models

**5. Timeout**
```
[ERROR] OpenRouter: Connection failed
Error Type: TIMEOUT
```
**Fix**: 
- Check internet connection
- Increase timeouts in .env
- Try again (network issues are transient)

**6. Invalid JSON Response**
```
[WARNING] OpenRouter: All JSON parsing strategies failed
Error Type: INVALID_RESPONSE
```
**Fix**: This usually means the AI returned malformed JSON. The system will:
- Try the next model in the fallback chain automatically
- Log the full response for debugging

**7. Empty Response**
```
[ERROR] OpenRouter: Empty content in response
Error Type: EMPTY_RESPONSE
```
**Fix**: The model returned an empty response. System will try next model.

## Monitoring & Debugging

### Check Recent Analysis Failures

```sql
SELECT 
    r.name as repository,
    a.status,
    a.error_message,
    a.model_used,
    a.updated_at
FROM analyses a
JOIN repositories r ON a.repository_id = r.id
WHERE a.status = 'failed'
ORDER BY a.updated_at DESC
LIMIT 10;
```

### Check OpenRouter Model Availability

The service has a model fallback chain:
1. `openai/gpt-4o-mini:free` (default, very reliable)
2. `nvidia/nemotron-nano-12b-v2-vl:free`
3. `google/gemma-3-27b-it:free`
4. `qwen/qwen3-32b:free`
5. `meta-llama/llama-3.3-8b-instruct:free`

If one fails, it automatically tries the next one.

### Enable Model Verification (Optional)

In `config/openrouter.php` or `.env`:
```env
OPENROUTER_VERIFY_MODELS=true
```

This will check which models are currently available before attempting to use them. However, it may filter out working models if OpenRouter's catalog is stale, so it's disabled by default.

## Error Messages for Users

All errors now show user-friendly messages with error codes:

- **RATE_LIMIT**: "The AI service is rate-limited right now. Please wait a moment and try again."
- **MODEL_UNAVAILABLE**: "The selected AI model is currently unavailable. Please try again."
- **AUTH_ERROR**: "AI authentication failed. Please check your OpenRouter API key."
- **TIMEOUT**: "The AI service took too long to respond. Please try again."
- **INVALID_RESPONSE**: "The AI returned an invalid response. Please try again."
- **UNKNOWN**: "AI analysis failed unexpectedly. Please try again."

## Performance Tuning

### If Analysis is Too Slow

1. **Reduce max_tokens** (less detailed analysis):
   ```env
   OPENROUTER_MAX_TOKENS=2048
   ```

2. **Increase timeout** (allow more time):
   ```env
   OPENROUTER_TIMEOUT=90
   OPENROUTER_TOTAL_BUDGET=180
   ```

3. **Use a faster model**:
   ```env
   OPENROUTER_MODEL=openai/gpt-4o-mini:free
   ```

### If Analysis Fails Too Often

1. **Enable model verification**:
   ```env
   OPENROUTER_VERIFY_MODELS=true
   ```

2. **Add more retries**:
   ```env
   OPENROUTER_RETRY_TIMES=3
   ```

3. **Check OpenRouter status**: https://status.openrouter.ai/

## Next Steps for Testing

1. ✅ **Test with a valid API key** - Verify successful analysis
2. ✅ **Test with an invalid API key** - Verify AUTH_ERROR handling
3. ✅ **Test with a removed API key** - Verify config error handling
4. ✅ **Test with a non-existent model** - Verify fallback chain
5. ✅ **Monitor logs during analysis** - Verify detailed logging
6. ✅ **Test multiple analyses in succession** - Verify rate limit handling
7. ✅ **Check the database** - Verify analysis results are stored correctly

## Support

If analysis continues to fail after following this guide:

1. **Check the logs** at `storage/logs/laravel.log` for detailed error information
2. **Check OpenRouter status** at https://status.openrouter.ai/
3. **Verify your API key** has credits at https://openrouter.ai/
4. **Try a different model** by setting OPENROUTER_MODEL
5. **Check network connectivity** to https://openrouter.ai

The enhanced logging should now provide enough detail to identify and fix any issues quickly.
