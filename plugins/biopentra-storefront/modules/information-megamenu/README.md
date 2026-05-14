# Module: information-megamenu

**Migrated from:** `plugins/biopentra-information-megamenu/` (legacy plugin folder is **retained**; do not run both plugins active for this behavior — duplicate CSS).

## Implementation

- `class-information-megamenu-module.php` — `wp_enqueue_scripts` @ **25**, handle **`biopentra-information-mega`**, version **`1.3.2`**, skips when `is_admin()`.

## Assets

| Legacy | Storefront |
|--------|------------|
| `assets/information-mega.css` | `assets/information-megamenu/information-mega.css` |

## `information-mega.js` — not migrated

The legacy plugin **does not enqueue** `assets/information-mega.js` in `biopentra-information-megamenu.php`. That file remains only under the legacy plugin folder for reference; no JS was copied into storefront for Phase 1.

## Ops script

`cli-update-megamenu.php` was not moved; it still lives under the legacy plugin until you decide to relocate it to `docs/` or a `bin/` folder.
