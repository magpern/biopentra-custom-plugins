# Module: information-megamenu

**Migrated from:** `plugins/biopentra-information-megamenu/` (retired monorepo stub; runtime lives here).

## Implementation

- `class-information-megamenu-module.php`
  - **CSS:** `wp_enqueue_scripts` @ **25**, handle `biopentra-information-mega`, version `1.3.2`, path `assets/information-megamenu/information-mega.css`.
  - **JS:** same hook, handle `biopentra-storefront-information-mega`, version `1.3.2`, path `assets/information-megamenu/information-mega.js`, footer + `defer` via `script_loader_tag`.

## Duplicate loading (Elementor)

The mega-menu template may still include an **HTML widget** with a hardcoded `<script src="…/biopentra-information-megamenu/assets/information-mega.js">` (from `cli-update-megamenu.php`). With **storefront** also enqueuing the same logic from a **new URL**, the browser may **download twice**. The script uses `window.__BIOPENTRA_INFO_MEGA_JS__` so **only the first execution runs** — behavior stays correct, but cutover should **remove or replace** that widget per `docs/information-mega-js-cutover-plan.md` before deleting the legacy plugin directory.

## `cli-update-megamenu.php`

Located at `plugins/biopentra-storefront/scripts/cli-update-megamenu.php` (dev-only Elementor layout updater; not loaded at runtime). Run via `wp eval-file` when storefront is active.
