# Changelog

All notable documentation and product changes for GitRadar.

Format based on [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

### Added

- Complete documentation system under `/docs/`:
  - architecture, installation, configuration, environment, database
  - authentication, github-integration, repository-sync
  - ai-analysis, ai-providers, gemini-integration, openrouter-integration, analysis-pipeline
  - insights, profile, themes, frontend, api, testing
  - troubleshooting, security, performance, contributing, changelog
- Concise root `README.md` with documentation navigation table

### Changed

- Landing page copy: "Powered By Cross Multiple AI" (replacing OpenRouter-specific marketing text)
- Landing footer: "Built with Laravel · Cross Multiple AI"

### Documentation Notes

- Provider selection documented as Gemini-when-key-set / OpenRouter-fallback (no `AI_PROVIDER`, no Groq)
- Web routes documented in `api.md` (no REST API)
- Legacy docs retained for reference:
  - [AI_ANALYSIS_TESTING_GUIDE.md](AI_ANALYSIS_TESTING_GUIDE.md)
  - [TRANSFORMATION_REPORT.md](TRANSFORMATION_REPORT.md)
  - [CACHE_CLEAR_INSTRUCTIONS.md](CACHE_CLEAR_INSTRUCTIONS.md)

## Prior Implementation (Pre-Changelog)

Features already in codebase before this documentation pass:

- Laravel 13 + PHP 8.3 + MySQL + Blade + Tailwind 4 + Alpine.js + Vite
- GitHub OAuth (Socialite, scopes: `user`, `public_repo`)
- Repository sync job with README fetch
- AI analysis via `AiAnalysisService` (Gemini primary, OpenRouter fallback)
- Portfolio score from weighted AI repository scores
- Dashboard, repositories, analysis, insights, profile, settings pages
- Export analysis as JSON/Markdown
- Light/dark/system theme (`gitradar-theme` localStorage key)
- PHPUnit test suite
- Database queue default with `DB_QUEUE_RETRY_AFTER=330`

See [TRANSFORMATION_REPORT.md](TRANSFORMATION_REPORT.md) for historical refactor notes.
