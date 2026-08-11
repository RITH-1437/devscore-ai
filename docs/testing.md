# Testing

GitRadar uses PHPUnit 12 with Laravel's testing utilities.

## Running Tests

```bash
composer test
# equivalent to:
php artisan config:clear --ansi && php artisan test

# or directly:
php artisan test
```

## Test Suite Overview

Tests live in `tests/`:

| Directory | Focus |
|-----------|-------|
| `tests/Unit/` | Services, helpers, isolated logic |
| `tests/Feature/` | HTTP flows, integration |

### Unit tests

| File | Coverage |
|------|----------|
| `AiAnalysisServiceTest.php` | Gemini vs OpenRouter provider routing |
| `GoogleGeminiServiceTest.php` | Gemini HTTP integration (faked) |
| `OpenRouterServiceTest.php` | OpenRouter model chain, errors, parsing |
| `PortfolioScoreServiceTest.php` | Weighted scoring, aggregation |
| `GithubAccountPortfolioUrlTest.php` | Portfolio URL normalization |
| `ExampleTest.php` | Scaffold |

### Feature tests

| File | Coverage |
|------|----------|
| `RepositoryAnalysisTest.php` | End-to-end analysis flow |
| `RepositoryIndexTest.php` | Repository listing |
| `ProfilePageTest.php` | Profile page rendering |
| `SoftSearchTest.php` | JSON partial search |
| `ExampleTest.php` | Scaffold |

HTTP calls to external APIs are faked with `Http::fake()` in tests — no real API keys required.

## Configuration in Tests

Tests override config via `$this->app['config']->set(...)`:

```php
config(['gemini.api_key' => 'test-key']);
config(['openrouter.api_key' => 'sk-test-key']);
```

## Database

Feature tests use Laravel's database traits (RefreshDatabase or similar per test class). Ensure `.env.testing` or phpunit.xml database settings point to a test database if running against MySQL.

Default `phpunit.xml` typically uses in-memory SQLite or a dedicated test DB — check project configuration before running.

## Code Style

```bash
./vendor/bin/pint
```

## CI Recommendations

```bash
composer install --no-interaction
cp .env.example .env
php artisan key:generate
php artisan test
```

No AI or GitHub credentials needed for the test suite.

## Legacy Manual Testing

For live OpenRouter debugging, see [AI_ANALYSIS_TESTING_GUIDE.md](AI_ANALYSIS_TESTING_GUIDE.md).

## Related Docs

- [ai-analysis.md](ai-analysis.md)
- [contributing.md](contributing.md)
