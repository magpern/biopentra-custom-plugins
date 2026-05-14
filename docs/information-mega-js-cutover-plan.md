# Information mega-menu JS — production cutover plan

**Purpose:** After `biopentra-storefront` enqueues `information-mega.js`, eliminate reliance on the **legacy plugin URL** embedded in Elementor so the `biopentra-information-megamenu` directory can eventually be removed.

**Do not** delete the legacy plugin folder until this cutover is verified on staging/production.

---

## Current problem

1. **Storefront** now registers `information-mega.js` with handle `biopentra-storefront-information-mega` from:
   - `plugins/biopentra-storefront/assets/information-megamenu/information-mega.js`
2. **Elementor** document meta (`_elementor_data`) often still contains an **HTML widget** whose `settings.html` is a literal tag:
   - `<script defer src="https://example.com/wp-content/plugins/biopentra-information-megamenu/assets/information-mega.js?ver=1.3.2"></script>`
3. That URL was produced by **`cli-update-megamenu.php`** when the mega-menu was published.

**Risk:** Two HTTP requests for the same logic; **mitigation:** the script sets `window.__BIOPENTRA_INFO_MEGA_JS__` and exits early on second load — **no double-init**, but wasteful and confusing in Network tab.

**Removal risk:** If the **legacy plugin directory** is deleted while the Elementor tag still points at it, the **hardcoded** script returns **404** and only the storefront enqueue saves you — **only if** storefront is active. If storefront is off, mega-menu JS breaks.

---

## New storefront JS URL (reference)

After deploy, the WordPress-enqueued script will look like:

`…/wp-content/plugins/biopentra-storefront/assets/information-megamenu/information-mega.js?ver=1.3.2`

(Handle: `biopentra-storefront-information-mega`; WP may print `id="biopentra-storefront-information-mega-js"`.)

---

## Safe cutover options

### A — Update Elementor HTML widget to the new storefront URL

- **Pros:** Keeps explicit script in template; predictable.
- **Cons:** Still bypasses `wp_enqueue_script` (no dependency/version centralization from WP’s perspective unless you match `ver=` manually); easy to drift on version query string.

### B — Remove the Elementor script widget; rely on `wp_enqueue_script` (recommended)

- **Pros:** Single source of truth; versioning and `defer`/footer controlled in PHP; no hardcoded plugin paths in DB.
- **Cons:** Requires editing the Elementor template (or re-running an adapted CLI that **omits** `$runtime_script_widget` / clears `settings.html` for that widget).

**Recommended: B** — delete the HTML widget that only loads this script, or empty its HTML after confirming storefront’s enqueue runs on all templates that render `.biopentra-information-mega`.

---

## Testing steps (staging)

1. Deploy storefront build with **both** CSS and JS assets.
2. With **only** storefront active for mega-menu (legacy mega-menu plugin **deactivated**):
   - Open homepage / header templates using the mega-menu.
   - **Network:** confirm `biopentra-storefront/.../information-mega.js` loads **200**.
3. **Before** removing Elementor script: confirm mega-menu still works (guard prevents double logic).
4. **After** removing Elementor HTML script widget (option B):
   - Hard refresh; confirm **only one** `information-mega.js` request (storefront URL).
   - Desktop + mobile behaviors: flyouts, `.is-active`, mobile accordion, Escape key.
5. Flush Elementor/CSS caches if applicable.

---

## Rollback steps

1. Restore the Elementor HTML widget from backup/revision if you removed it incorrectly.
2. Reactivate `biopentra-information-megamenu` if CSS was only on storefront and you need legacy again.
3. Deactivate `biopentra-storefront` if necessary.
4. Clear CDN/browser caches.

---

## Order of operations for “legacy folder no longer needed”

1. Storefront active + mega-menu JS/CSS verified.
2. **Edit Elementor** (option B): remove hardcoded `<script …megamenu…>` widget content.
3. Re-test; confirm single script load from storefront.
4. **Then** optionally remove `biopentra-information-megamenu` from deploy artifacts (separate change; not done automatically here).
