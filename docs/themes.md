# Themes

GitRadar supports light and dark themes on authenticated pages. Light is the default.

## Storage

Preference is stored in browser `localStorage`:

```
Key: gitradar-theme
Values: light | dark
Default: light
```

Legacy key `devscore-theme` is read as fallback for backward compatibility but writes use `gitradar-theme`. Any stored `system` value is migrated to `light` on the next visit.

## Implementation

### Flash prevention

An inline script in `resources/views/components/layouts/app.blade.php` runs **before first paint**:

1. Read `gitradar-theme` from localStorage
2. Normalize to `light` or `dark` (default `light`)
3. Add `dark` class to `<html>` when theme is dark

### Theme toggle

Shared Alpine.js store in `resources/js/app.js`:

- Header: single button toggles `light` ↔ `dark` on click
- Settings: Light / Dark segmented buttons
- `apply(value)` updates localStorage, `data-theme-pref` attribute, and `dark` class

### CSS variables

`resources/css/app.css` defines CSS custom properties for both themes:

- `--bg-primary`, `--text-primary`, `--border-color`
- `--glow-primary`, `--glow-secondary`
- Semantic colors: `--success`, `--danger`, `--info`

Tailwind `dark:` variants are used alongside CSS variables.

## Landing Page

The public landing page (`landing.blade.php`) uses a fixed dark aesthetic and does **not** include the theme switcher.

## Settings Page

Settings (`/settings`) offers Light / Dark buttons for users who prefer changing the theme there.

## Related Docs

- [frontend.md](frontend.md)
