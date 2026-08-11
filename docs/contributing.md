# Contributing

Thank you for contributing to GitRadar.

## Getting Started

1. Fork and clone the repository
2. Follow [installation.md](installation.md)
3. Copy `.env.example` to `.env` with your test credentials
4. Run `composer dev` for local development

## Development Workflow

```bash
composer dev          # server + queue + vite
composer test         # run test suite
./vendor/bin/pint       # format PHP code
```

## Code Standards

- PHP 8.3+ with `declare(strict_types=1);` on new files
- Follow existing Laravel conventions in `app/`
- Match surrounding naming, types, and documentation level
- Minimize scope — focused diffs preferred
- No unrelated refactors in feature PRs

## Documentation

- Detailed docs belong in `/docs`
- Update README.md only for navigation/entry-point changes
- Document **actual** implementation only — no speculative features
- Never include real API keys in docs or commits

## Testing Requirements

- Add or update PHPUnit tests for behavior changes
- Use `Http::fake()` for external API calls
- Run `composer test` before submitting PRs

## Pull Requests

1. Create a feature branch from `main`
2. Write clear commit messages (why, not just what)
3. Ensure tests pass
4. Update relevant docs in `/docs`
5. Do not commit `.env` or secrets

## Areas Welcome for Contribution

- UI/UX improvements (Blade, Tailwind, Alpine)
- Test coverage expansion
- Documentation accuracy
- Error message clarity
- Performance optimizations (caching, query efficiency)

## Out of Scope (Unless Discussed First)

- Groq or additional AI providers
- Public REST API / mobile API
- `AI_PROVIDER` env switch (current design uses Gemini-key detection)
- Features not reflected in existing codebase

## Related Docs

- [testing.md](testing.md)
- [architecture.md](architecture.md)
- [changelog.md](changelog.md)
