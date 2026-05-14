# Staging test checklist — Phase 1 (Information mega-menu CSS)

**Scope:** Validate that **`biopentra-storefront`** can replace **`biopentra-information-megamenu`** for mega-menu styling **only** (same CSS handle, path inside storefront plugin, priority 25).

**Environments:** Use a **staging** WordPress site that mirrors production theme/Elementor data closely. **Do not** run this checklist on production unless explicitly approved later.

---

## Warning — duplicate CSS

**Do not** keep **`biopentra-storefront`** and **`biopentra-information-megamenu`** both **active** after cutover (staging or production). The Information mega-menu stylesheet would register/enqueue **twice** (same handle may dedupe in some setups, but behavior is undefined and wastes bandwidth). For the real cutover: **deactivate** the legacy mega-menu plugin when storefront is active for this feature.

---

## Pre-test backup (staging)

1. **Database:** Export a full SQL dump of the staging DB (or use host backup tools).
2. **Files:** Snapshot `wp-content/plugins/` (tar/rsync or hosting snapshot) so you can restore `biopentra-information-megamenu` and remove `biopentra-storefront` quickly.
3. **Document** current plugin versions (Plugins screen screenshot or `wp plugin list` output).
4. **Optional:** Export Elementor **Site Settings** / relevant header template if your workflow stores critical layout outside DB-only backup.

---

## Required plugins active **before** test

Confirm on staging (adjust to your stack):

| Plugin | Why |
|--------|-----|
| **WooCommerce** | If the mega-menu lives in shop/header flows tied to WC templates. |
| **Elementor** (+ **Elementor Pro** if used in production) | Mega-menu markup is Elementor-driven. |
| **Theme** (e.g. Blocksy) | Match production theme version where possible. |
| **`biopentra-information-megamenu`** | **Active** during baseline pass — proves current styling. |
| Other Biopentra plugins you rely on for layout | e.g. `biopentra-header-auth`, `biopentra-loop-card` — leave **unchanged** for this test unless they conflict. |

**Do not** activate `biopentra-storefront` until you are ready for the test segment below.

---

## Install `biopentra-storefront` on staging (ZIP)

1. Build or obtain the release ZIP from the backup repo:  
   `custom-wordpress-plugins/builds/zips/biopentra-storefront-0.1.0.zip`  
   (Rebuild locally with `./scripts/build-zips.sh` after any code change.)
2. In WP Admin: **Plugins → Add New → Upload Plugin** → choose `biopentra-storefront-0.1.0.zip` → **Install Now**.
3. Confirm the installed folder is **`wp-content/plugins/biopentra-storefront/`** with:
   - `biopentra-storefront.php`
   - `modules/information-megamenu/class-information-megamenu-module.php`
   - `assets/information-megamenu/information-mega.css`
4. **Do not activate yet** until baseline is recorded.

---

## Staging test order (recommended)

### A — Baseline (legacy only)

1. Ensure **`biopentra-information-megamenu`** is **active**.
2. Ensure **`biopentra-storefront`** is **inactive** (or not installed yet).
3. Clear **browser cache** and, if applicable, **CDN / full-page cache** for staging.
4. Open each page under [Pages to inspect](#pages-to-inspect); capture **screenshots** or note specific visual markers (panel width, column borders, flyout card shadow).
5. In browser devtools **Network**, filter `information-mega` — confirm **one** CSS response from the **legacy** plugin URL pattern (path under `biopentra-information-megamenu`).

### B — Switch to storefront (staging only)

1. **Activate** `biopentra-storefront`.
2. **Deactivate** **`biopentra-information-megamenu`** only (do not delete the folder).
3. Clear **browser cache** and **CDN / server cache** again.
4. Repeat the same [Pages to inspect](#pages-to-inspect).
5. In **Network**, confirm **one** CSS load for handle/URL under **`biopentra-storefront/assets/information-megamenu/information-mega.css`** (or verify single `biopentra-information-mega` link in page source).

### C — Rollback rehearsal

1. **Deactivate** `biopentra-storefront`.
2. **Reactivate** `biopentra-information-megamenu`.
3. Clear caches; confirm mega-menu matches **baseline** again.

---

## Pages to inspect

Pick URLs that actually render the **Information** mega-menu panel (adjust to your site map):

- [ ] **Homepage** (if header includes the mega-menu).
- [ ] **One primary catalog / shop** page where the header template loads.
- [ ] **One inner page** (e.g. About or Contact) if the same header template is global.
- [ ] **Mobile width** (≤1024px): stacked columns, no flyout, inline disclosure regions.
- [ ] **Desktop** (≥1025px): three columns, flyouts, hover/`is-active` disclosure behavior if JS-driven elsewhere.

---

## Browser cache / CDN cache clearing

| Layer | Action |
|-------|--------|
| **Browser** | Hard reload (Ctrl+Shift+R / Cmd+Shift+R) or disable cache in DevTools during inspection. |
| **WordPress** | If using a caching plugin, **purge all** on staging after each activate/deactivate step. |
| **CDN** (Cloudflare, etc.) | Purge staging hostname or equivalent; wait for propagation if documented by provider. |
| **Object cache** | If Redis/Memcached on staging, flush if your host documents theme asset changes being sticky. |

---

## Visual checks

Compare **baseline (A)** vs **storefront (B)**:

- [ ] Mega-menu panel **width** and **max-width** (e.g. `min(1180px, …)` look).
- [ ] **Background**, **border-radius**, **box-shadow** of the panel.
- [ ] **Three-column** grid and **dividers** between columns (desktop).
- [ ] **Link** colors and hover underline in link lists.
- [ ] **Flyout** cards (desktop): position, shadow, open/close with hover or active state (CSS supports; JS may come from Elementor/theme).
- [ ] **Trust strip** / bottom section if present in template.
- [ ] **Responsive** breakpoints: tablet/mobile stacking per CSS.
- [ ] **No** layout “flash” or missing styles indicating 404 on CSS URL.

---

## Rollback steps (if test fails)

1. **Deactivate** `biopentra-storefront`.
2. **Reactivate** `biopentra-information-megamenu`.
3. Purge all caches; verify mega-menu CSS loads once from legacy path.
4. If the ZIP install corrupted files: restore **`wp-content/plugins/biopentra-storefront/`** from backup or delete the folder and reinstall from known-good ZIP.
5. **Do not** delete the legacy plugin folder unless a separate maintenance window approves it.

---

## Pass / fail criteria

| Result | Criteria |
|--------|----------|
| **PASS** | With only `biopentra-storefront` active (and legacy mega-menu **inactive**), visual parity with baseline within acceptable tolerance; **exactly one** `information-mega` / `biopentra-information-mega` stylesheet load; no PHP fatals/notices with `WP_DEBUG` on staging. |
| **FAIL** | Missing styles, wrong URL (404), double-loaded CSS with both plugins active, broken responsive/flyout layout vs baseline, or PHP errors. → Roll back; file GitHub issue with screenshots and Network HAR if possible. |

---

## Production reminder

- **Do not** activate `biopentra-storefront` on **production** until staging **PASS** and sign-off.
- **Do not** delete **`biopentra-information-megamenu`** from production until cutover is approved; keep the ZIP/repo copy for rollback.

---

## Reference (repo paths, not staging paths)

| Artifact | Location in `biopentra-custom-plugins` repo |
|----------|-----------------------------------------------|
| Storefront ZIP | `builds/zips/biopentra-storefront-0.1.0.zip` |
| Module | `plugins/biopentra-storefront/modules/information-megamenu/class-information-megamenu-module.php` |
| CSS | `plugins/biopentra-storefront/assets/information-megamenu/information-mega.css` |
