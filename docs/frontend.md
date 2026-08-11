# Frontend

GitRadar's UI is server-rendered Blade with Tailwind CSS 4 and Alpine.js, bundled by Vite.

## Stack

| Tool | Version | Purpose |
|------|---------|---------|
| Vite | 8.x | Asset bundling |
| Tailwind CSS | 4.x | Utility-first styling |
| Alpine.js | 3.15.x | Client-side interactivity |
| Axios | 1.17.x | HTTP (available, minimal use) |

## Entry Points

| File | Loaded by |
|------|-----------|
| `resources/css/app.css` | `@vite` in layouts |
| `resources/js/app.js` | `@vite` in layouts |

`app.js` initializes Alpine:

```javascript
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
```

## Layouts

| Layout | Used by |
|--------|---------|
| `components/layouts/app.blade.php` | Authenticated pages (dashboard, repos, etc.) |
| `landing.blade.php` | Public homepage (standalone, no sidebar) |

App layout includes:

- Sidebar (`x-sidebar` component)
- Mobile sidebar overlay
- Theme switcher
- Flash message display
- Skip-to-content link

## Key Views

| Path | View |
|------|------|
| `/` | `landing.blade.php` |
| `/dashboard` | `dashboard/index.blade.php` |
| `/repositories` | `repositories/index.blade.php` |
| `/repositories/{id}` | `repositories/show.blade.php` |
| `/analysis` | `analysis/index.blade.php` |
| `/insights` | `insights/index.blade.php` |
| `/profile` | `profile/index.blade.php` |
| `/settings` | `settings/index.blade.php` |

Components live in `resources/views/components/`.

## Soft Search

Repository index supports JSON partial responses:

- Client sends `Accept: application/json` with filter query params
- Server returns `repositories.partials.grid` HTML fragment
- Alpine/JS swaps grid without full page reload

See `RepositoryController::index`.

## Analysis Polling

Repository show page polls `GET /repositories/{id}/analysis-status` while `analysis_status=processing`.

## Build Commands

```bash
npm run dev      # Vite HMR (development)
npm run build    # Production assets → public/build/
composer dev     # Laravel + queue + Vite together
```

## Static Assets

- `public/images/logo.png`, `logo.svg` — branding
- Favicon referenced in layouts

## Related Docs

- [themes.md](themes.md)
- [installation.md](installation.md)
