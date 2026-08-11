# Themes

GitRadar supports light, dark, and system themes on authenticated pages.

## Storage

Preference is stored in browser `localStorage`:

```
Key: gitradar-theme
Values: light | dark | system
Default: light
```

Legacy key `devscore-theme` is read as fallback for backward compatibility but writes use `gitradar-theme`.

## Implementation

### Flash prevention

An inline script in `resources/views/components/layouts/app.blade.php` runs **before first paint**:

1. Read `gitradar-theme` from localStorage
2. If `system`, resolve via `prefers-color-scheme`
3. Add `dark` class to `<html>` when resolved theme is dark

### Theme switcher

Alpine.js component in the app layout header and Settings page:

- Dropdown with Light / Dark / System options
- `apply(value)` updates localStorage, `data-theme-pref` attribute, and `dark` class
- Listens for system preference changes when pref is `system`

### CSS variables

`resources/css/app.css` defines CSS custom properties for both themes:

- `--bg-primary`, `--text-primary`, `--border-color`
- `--glow-primary`, `--glow-secondary`
- Semantic colors: `--success`, `--danger`, `--info`

Tailwind `dark:` variants are used alongside CSS variables.

## Landing Page

The public landing page (`landing.blade.php`) uses a fixed dark aesthetic and does **not** include the theme switcher.

## Settings Page

Settings (`/settings`) duplicates the theme picker for users who prefer changing it there.

## Related Docs

- [frontend.md](frontend.md)
