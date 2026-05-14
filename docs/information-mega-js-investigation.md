# Investigation: `information-mega.js` dependency

**Date:** 2026-05-14  
**Scope:** How the Information mega-menu JavaScript is loaded, whether it is required, and implications for `biopentra-storefront` Phase 1 and later cutovers.  
**Constraints:** No production changes from this document; Phase 2 not started.

---

## Summary

| Question | Answer |
|----------|--------|
| Is the JS used on the frontend? | **Yes** — it drives desktop disclosure/flyout behavior, mobile accordion, template cloning, resize breakpoints, and `MutationObserver` for late-rendered markup. |
| Who loads it? | **Not** the main plugin file `biopentra-information-megamenu.php` (that file only enqueues **CSS**). The script is referenced from **Elementor post content**: an **HTML widget** whose `settings.html` contains a literal `<script defer src="…/biopentra-information-megamenu/assets/information-mega.js?ver=1.3.2">` tag. That structure is **generated** by the CLI script `cli-update-megamenu.php`. |
| Is it “dead” legacy code? | **No** — the file is substantive (~278 lines) and required for correct interactive behavior beyond static CSS. |
| Is production CSS-only cutover safe if the legacy plugin is **deactivated** but the **folder remains**? | **Generally yes** for JS URL resolution: the browser still requests a static file under `wp-content/plugins/biopentra-information-megamenu/`. **No** if the legacy plugin **directory is removed** from the server — the hardcoded Elementor URL will **404** and the mega-menu will lose interactivity. |

---

## Search results: where `information-mega.js` appears

### 1. Runtime asset (source of truth)

| Path | Role |
|------|------|
| `wp-content/plugins/biopentra-information-megamenu/assets/information-mega.js` | Full mega-menu behavior (desktop/mobile, `__BIOPENTRA_INFO_MEGA_JS__` singleton guard). |

Same path exists in the backup repo mirror:

`custom-wordpress-plugins/plugins/biopentra-information-megamenu/assets/information-mega.js`

### 2. PHP that writes the file and builds the `<script>` HTML — **not** `wp_enqueue_script`

| File | What it does |
|------|----------------|
| `…/biopentra-information-megamenu/cli-update-megamenu.php` | Writes `assets/information-mega.js` from `biopentra_megamenu_runtime_js()` (see `file_put_contents` around the `$js_asset_path` block). Builds `$script_loader_html` as either **external** `<script defer src="…plugins_url( 'assets/information-mega.js', __FILE__ )…">` or an **inline** fallback inside `.biopentra-info-mega-script-host` if the file is unreadable. Embeds that HTML into **Elementor JSON** as an **HTML widget** (`widgetType` => `html`, `settings.html` => `$script_loader_html`). |

Reference lines (legacy plugin, mirrored in repo):

- `$js_asset_path` / `$script_loader_html` / `$runtime_script_widget` — approximately lines **619–780** in `cli-update-megamenu.php` (HTML widget `settings['html']` = `$script_loader_html`).

### 3. Main plugin bootstrap — **no** JS enqueue

`biopentra-information-megamenu.php` only registers/enqueues **CSS** on `wp_enqueue_scripts` priority 25. It does **not** call `wp_enqueue_script` for `information-mega.js`.

### 4. Database / exports

A local DB export under `woocommerce/wp-content/uploads/` (e.g. `staging-test-phase1-pre.sql`) contains `_elementor_data` and related Elementor meta with large JSON blobs; those blobs are where the **hardcoded** script URL lives once the CLI (or Elementor save) has published the template.

**Implication:** The dependency is **content in the database**, not only PHP. Changing the plugin slug/path requires either:

- Updating Elementor document meta (search-replace in `_elementor_data` / caches), or  
- Re-running an adapted `cli-update-megamenu.php` that emits the new URL, or  
- Loading the script via `wp_enqueue_script` from `biopentra-storefront` and **removing** the redundant HTML-widget `<script>` tag from the template (cleanest long term).

### 5. Documentation (repo)

References in `custom-wordpress-plugins` (accurate for “not enqueued by main PHP”, but incomplete regarding **Elementor HTML injection**):

- `docs/storefront-migration-checklist.md`
- `plugins/biopentra-storefront/modules/information-megamenu/README.md`
- `docs/migration-plan-biopentra-storefront.md`

This investigation doc supersedes those notes for **JS sourcing**.

### 6. Theme / other PHP / JS / CSS / shortcodes

Targeted ripgrep under `wp-content` for `information-mega.js` (excluding the large SQL dump in ad-hoc reads) shows **no** theme or other plugin PHP enqueuing this file; the **cli** and **Elementor-derived** storage are the meaningful sources.

---

## Is the JS “actually used”?

**Yes.** It:

- Initializes every `.biopentra-information-mega` root (`initMega`, `data-bp-mega-init`).
- Implements **desktop** `(min-width: 1025px)` disclosure: `pointerenter` / `pointerleave`, `focusin` / `focusout`, `.is-active`, flyout prefill from `<template>` IDs `bp-detail-template-*`.
- Implements **mobile** click accordion and inline detail panels.
- Listens for `resize` and `matchMedia` changes to re-apply mode.
- Uses `MutationObserver` to attach to mega menus added after initial `DOMContentLoaded` (relevant for Elementor dynamic rendering).

Pure CSS (`information-mega.css`) handles layout and **:hover** visibility for flyouts, but **`.is-active`**, template **cloning into flyouts**, and **mobile** behavior depend on this script.

---

## Does it execute successfully?

- When the **legacy plugin directory** exists and the web server serves static files from `wp-content/plugins/…`, the **defer** external script loads and runs; the global guard prevents double-init if loaded twice.
- If the **path 404s** (plugin removed, wrong deploy, CDN rule), interactivity breaks; CSS-only appearance may remain partially usable but disclosure/flyout content population will fail.

---

## Risk assessment

| Risk | Severity | Notes |
|------|-----------|------|
| Hardcoded URL to `biopentra-information-megamenu/` inside Elementor | **High** for removals | Deleting the legacy plugin **without** replacing the HTML widget or enqueuing JS from storefront **breaks** the mega-menu JS. |
| Phase 1 storefront **CSS** from `biopentra-storefront` + legacy **deactivated** | **Low** if folder kept | Static JS URL under old folder still works. |
| Duplicate script if storefront later **also** enqueues the same file | **Low** | `window.__BIOPENTRA_INFO_MEGA_JS__` short-circuits a second run. |
| Stale Elementor **element cache** | **Medium** | After URL changes, flush Elementor/CSS caches per your stack. |

---

## Recommended actions (before treating Phase 1 as “fully migrated”)

1. **Short term (safest with zero DB edits):** Keep the **`biopentra-information-megamenu` plugin directory** on the server (plugin may stay **inactive**) so the existing Elementor `<script src="…/biopentra-information-megamenu/assets/information-mega.js">` keeps working. Document this as a **deployment requirement** until the HTML widget is updated.

2. **Medium term (recommended):**  
   - **Copy** `information-mega.js` into `biopentra-storefront` (e.g. `assets/information-megamenu/information-mega.js`).  
   - **Enqueue** it from `Biopentra_Storefront_Information_Megamenu_Module` with `wp_enqueue_script` (deps: none or `jquery` if ever needed — current file is vanilla IIFE).  
   - **Remove** the literal `<script src=…>` from the Elementor HTML widget via a controlled DB export/replace or a revised CLI that rewrites the mega-menu template JSON **once**, then verify no duplicate loads (guard still protects if both exist briefly).

3. **Not recommended:** Rely on “CSS only” and delete the legacy plugin **without** addressing the Elementor script tag.

---

## Production cutover: safe or not?

- **Safe without extra fixes** only if you accept: **legacy plugin files remain on disk** (inactive is OK) so the **hardcoded Elementor URL** keeps resolving.  
- **Not safe** to delete the legacy mega-menu plugin from production **until** either the Elementor HTML is updated to point at `biopentra-storefront` **or** the script is properly enqueued from storefront and the old `<script>` tag is removed from the template.

**Do not** activate `biopentra-storefront` on production until your runbook includes one of the above JS strategies.

---

## Files to keep in mind for a future PR

| Path | Why |
|------|-----|
| `plugins/biopentra-information-megamenu/cli-update-megamenu.php` | Source of HTML-widget injection pattern. |
| `plugins/biopentra-information-megamenu/assets/information-mega.js` | Behavior reference / file to copy into storefront. |
| Elementor document(s) for the header / mega-menu | Post meta `_elementor_data` (and caches) containing the `<script>` tag. |

---

## Phase 2

**Do not start** Phase 2 until this JS strategy is agreed and (if required) the enqueue + Elementor cleanup tasks are scheduled.
