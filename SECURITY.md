# Security Policy

## Supported versions

Security fixes are applied to the active `main` branch. Older releases are not maintained separately unless explicitly tagged and announced.

| Version | Supported |
|---------|-----------|
| `main` (latest) | Yes |
| Older commits / forks | No |

If you run GitRadar in production, track `main` or a recent release tag and keep dependencies updated.

## Reporting a vulnerability

**Do not open a public GitHub issue for security vulnerabilities.**

Report security issues privately using one of these channels:

1. **GitHub Security Advisories** (preferred): [Report a vulnerability](https://github.com/RITH-1437/devscore-ai/security/advisories/new) on this repository
2. **Email:** neyrithneyrith@gmail.com with subject `GitRadar Security Report`

We aim to acknowledge reports within **3 business days**.

## What to include

A helpful report usually includes:

- Description of the vulnerability and potential impact
- Steps to reproduce (URLs, HTTP requests, user roles involved)
- Affected version or commit hash
- Any proof-of-concept you are comfortable sharing
- Your contact information for follow-up

Please do not include live credentials, production `.env` contents, or real user tokens in your report.

## Response process

1. **Acknowledge** receipt of the report
2. **Investigate** and confirm the issue
3. **Develop** a fix on a private branch when possible
4. **Release** a patch and document the advisory
5. **Credit** the reporter if they wish (unless anonymity is requested)

## Responsible disclosure

We ask reporters to:

- Give us reasonable time to investigate and patch before public disclosure
- Avoid accessing, modifying, or deleting data that does not belong to you
- Avoid denial-of-service testing against production systems
- Avoid social engineering of maintainers or users

We will work with you on a coordinated disclosure timeline when appropriate.

## Secrets and sensitive data

Never commit or share:

- `.env` files
- GitHub OAuth client secrets
- AI provider API keys (`GEMINI_API_KEY`, `OPENROUTER_API_KEY`)
- Database passwords
- Private keys (`*.pem`, `*.key`)
- GitHub user access tokens

If you accidentally expose a secret, rotate it immediately and notify the maintainer.

## Application security reference

For implementation details — authentication, authorization, token encryption, rate limits, and production hardening — see [docs/security.md](docs/security.md).
