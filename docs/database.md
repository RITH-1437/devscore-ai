# Database

GitRadar uses MySQL with Eloquent ORM. Migrations live in `database/migrations/`.

## Entity Relationship

```mermaid
erDiagram
    users ||--o| github_accounts : has
    github_accounts ||--o{ repositories : owns
    users ||--o{ analyses : has
    repositories ||--o{ analyses : has

    users {
        bigint id PK
        string name
        string email UK
        string password
        timestamps created_at updated_at
    }

    github_accounts {
        bigint id PK
        bigint user_id FK
        bigint github_id UK
        string username
        string access_token
        int followers
        int public_repos
        timestamps created_at updated_at
    }

    repositories {
        bigint id PK
        bigint github_account_id FK
        bigint repo_id UK
        string name
        string full_name
        json ai_analysis
        string analysis_status
        datetime ai_analyzed_at
        timestamps created_at updated_at
    }

    analyses {
        bigint id PK
        bigint user_id FK
        bigint repository_id FK
        string status
        tinyint score
        json strengths
        json weaknesses
        longtext raw_response
        timestamps created_at updated_at
    }
```

## Tables

### `users`

Standard Laravel users table. Created via GitHub OAuth (`User::firstOrCreate` on email).

### `github_accounts`

One GitHub account per user (linked by `user_id`). Stores OAuth token and profile metadata synced from GitHub.

Key columns:

| Column | Description |
|--------|-------------|
| `github_id` | GitHub user ID (unique) |
| `username` | GitHub login |
| `access_token` | OAuth token (hidden from serialization) |
| `followers`, `following`, `public_repos` | Profile stats |
| `bio`, `company`, `location`, `blog` | Profile fields |

### `repositories`

Synced from GitHub API. One row per GitHub repository (`repo_id` unique).

Key columns:

| Column | Description |
|--------|-------------|
| `repo_id` | GitHub repository ID |
| `github_account_id` | Owner account |
| `readme` | Fetched README content |
| `ai_analysis` | JSON blob of latest validated AI result |
| `ai_analyzed_at` | When analysis completed |
| `analysis_status` | `pending`, `processing`, `completed`, `failed` |
| `analysis_started_at` | When current run started (stale detection) |
| `is_pinned`, `is_featured` | User preferences |

Indexes: `(github_account_id, is_private)`, `analysis_status`, `stars`.

### `analyses`

Historical/detailed analysis records per user + repository. Rebuilt in migration `2026_08_02_000003_rebuild_analyses_table`.

Stores structured fields extracted from AI JSON (score, difficulty, reviews, career suggestions) plus `raw_response` for debugging.

Status values: `pending`, `processing`, `completed`, `failed`.

### Queue & session tables

Laravel defaults:

- `jobs`, `job_batches`, `failed_jobs` — database queue
- `sessions` — database sessions
- `cache`, `cache_locks` — database cache (when used)

## Models

| Model | Table | Relationships |
|-------|-------|---------------|
| `User` | `users` | `hasOne` GithubAccount |
| `GithubAccount` | `github_accounts` | `belongsTo` User, `hasMany` Repository |
| `Repository` | `repositories` | `belongsTo` GithubAccount, `hasMany` Analysis |
| `Analysis` | `analyses` | `belongsTo` User, Repository |

### Repository scopes

- `forUser(User)` — restrict to authenticated user's repos
- `analyzed()` — `ai_analyzed_at` is not null
- `public()`, `featured()`, `pinned()`

## Migrations

Run:

```bash
php artisan migrate
```

Key migration timeline:

1. `create_users_table`, `create_jobs_table`, `create_cache_table`
2. `create_github_accounts_table`, `create_repositories_table`
3. `add_readme_to_repositories_table`, `add_ai_analysis_to_repositories`
4. `enhance_repositories_table` — topics, flags, analysis_status
5. `rebuild_analyses_table` — full AI analysis schema
6. `add_analysis_started_at_to_repositories` — stale processing detection

## Data Flow

1. **Sync** — GitHub API → `repositories` (+ profile → `github_accounts`)
2. **Analysis** — AI JSON → `repositories.ai_analysis` + `analyses` row
3. **Portfolio** — aggregated in memory from `ai_analysis` scores (cached, not stored as a separate column)

## Factories & Seeders

The project uses PHPUnit with model factories in tests. No production seeders are required for normal operation.

## Related Docs

- [architecture.md](architecture.md)
- [repository-sync.md](repository-sync.md)
- [analysis-pipeline.md](analysis-pipeline.md)
