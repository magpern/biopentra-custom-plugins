# Module: information-megamenu

**Migrated from:** `plugins/biopentra-information-megamenu/` (legacy folder retained in repo; do not delete until Elementor cutover is done).

## Implementation

- `class-information-megamenu-module.php`
  - **CSS:** `wp_enqueue_scripts` @ **25**, handle `biopentra-information-mega`, version `1.3.2`, path `assets/information-megamenu/information-mega.css`.
  - **JS:** same hook, handle `biopentra-storefront-information-mega`, version `1.3.2`, path `assets/information-megamenu/information-mega.js`, footer + `defer` via `script_loader_tag`.

## Duplicate loading (Elementor)

The mega-menu template may still include an **HTML widget** with a hardcoded `<script src="…/biopentra-information-megamenu/assets/information-mega.js">` (from `cli-update-megamenu.php`). With **storefront** also enqueuing the same logic from a **new URL**, the browser may **download twice**. The script uses `window.__BIOPENTRA_INFO_MEGA_JS__` so **only the first execution runs** — behavior stays correct, but cutover should **remove or replace** that widget per `docs/information-mega-js-cutover-plan.md` before deleting the legacy plugin directory.

## `cli-update-megamenu.php`

Still only under the legacy plugin; not loaded by storefront. Future: point CLI output at storefront URLs or stop emitting inline script HTML once enqueue-only path is verified.
