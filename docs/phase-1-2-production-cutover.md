# Production cutover — Phase 1 + Phase 2 (`biopentra-storefront` 0.2.0)

**Scope:** Combined cutover for **Phase 1** (Information mega-menu) and **Phase 2** (footer contact shortcode + placeholder `noindex`) into **`biopentra-storefront`**. **Do not** start **Phase 3** (`custom-variation-stock-selector` or other later merges) until production has been stable with this release.

**Target package:** `builds/zips/biopentra-storefront-0.2.0.zip` (version from plugin header `Version: 0.2.0`). Rebuild with `./scripts/build-zips.sh` before deploy so the archive matches git.

**Related runbooks**

| Topic | Doc |
|--------|-----|
| Elementor legacy megamenu script removal | `docs/elementor-information-mega-js-cleanup.md` |
| Phase 1-only cutover (historical) | `docs/phase-1-production-cutover.md` |
| Footer contact migration details | `docs/footer-contact-migration-notes.md` |
| Local/staging test evidence (footer) | `docs/phase-2-footer-contact-test-results.md` |
| Megamenu browser QA | `docs/phase-1-megamenu-browser-qa.md` |

---

## 1. Scope

| Legacy plugin | Replaced by |
|----------------|-------------|
| **`biopentra-information-megamenu`** | `biopentra-storefront` **information-megamenu** module — enqueues `assets/information-megamenu/information-mega.css` + `information-mega.js` (see module README). Elementor must **not** keep injecting the old plugin URL for `information-mega.js`. |
| **`biopentra-footer-contact`** | `biopentra-storefront` **footer-contact** module — shortcode `[biopentra_footer_email]`, `wp_robots` placeholder noindex, `assets/footer-contact/footer-contact-email.js`, `assets/footer-contact/bp-e1.png`. |

**Out of scope here:** Phase 3+, `biopentra-loop-card`, `biopentra-contact-inbox`, `wc-inventory-overview` — unchanged by this plan.

---

## 2. Preconditions

Complete **before** production traffic depends on the new stack.

| Precondition | Notes |
|--------------|--------|
| **Menus tested** | Staging: Information mega-menu desktop + mobile signed off (`docs/phase-1-megamenu-browser-qa.md` + human pass). |
| **Footer tested** | Staging: `[biopentra_footer_email]` renders, JS mailto, `/contact` link, `bp-e1.png` **200**, placeholder `noindex` (`docs/phase-2-footer-contact-test-results.md`, `docs/staging-test-phase-2-footer-contact.md`). |
| **ZIP built** | `./scripts/build-zips.sh` — confirm `builds/zips/biopentra-storefront-0.2.0.zip` exists and includes `assets/information-megamenu/*`, `assets/footer-contact/*`, module PHP. |
| **DB backup** | Full dump **before** Elementor meta edits; store off-site with retention. |
| **Plugin folder backup** | Tar/ZIP of current production `wp-content/plugins/` slice: at minimum `biopentra-storefront` (if present), `biopentra-information-megamenu`, `biopentra-footer-contact`. |
| **Rollback plugins available** | Known-good ZIPs or deploy artifacts for **both** legacy plugins + prior storefront (if any) so reinstall does not require rebuilding from memory. |
| **Maintenance window (recommended)** | Short overlap may show duplicate megamenu **CSS** if legacy megamenu + storefront are both active; keep overlap minimal. |

---

## 3. Production order

Order matters: **`biopentra-storefront` must be active** before (a) removing the Elementor-injected legacy megamenu script and (b) deactivating **`biopentra-information-megamenu`**, or the mega-menu can lose JS. Storefront must also be active **before** deactivating **`biopentra-footer-contact`**, or the shortcode / enqueue path is lost.

1. **Upload / install `biopentra-storefront-0.2.0.zip`**  
   WordPress **Plugins → Add New → Upload Plugin**, or equivalent deploy. After extract, confirm on disk:  
   `assets/information-megamenu/information-mega.css`, `information-mega.js`,  
   `assets/footer-contact/footer-contact-email.js`, `bp-e1.png`,  
   and `modules/*/class-*-module.php` files.

2. **Activate `biopentra-storefront`**  
   Check `debug.log` for fatals. Quick Network check: megamenu CSS/JS and footer JS should be able to load from storefront URLs.

3. **Run Elementor megamenu JS cleanup**  
   Per **`docs/elementor-information-mega-js-cleanup.md`** (DB backup first): remove only the legacy `<script … biopentra-information-megamenu/.../information-mega.js …>` from `_elementor_data` / `_elementor_element_cache` as documented, or use `scripts/remove-elementor-information-mega-js.php` with `BIOPENTRA_ELEMENTOR_CLEANUP_APPLY=1` in controlled WP-CLI. Regenerate Elementor cache if your process requires it.

4. **Clear caches / CDN**  
   Full page cache, object cache, Elementor/CSS cache, CDN purge (include paths for `information-mega` and `footer-contact` if applicable).

5. **Deactivate `biopentra-information-megamenu`**  
   Plugins screen — **deactivate only**; **do not delete** the folder yet.

6. **Deactivate `biopentra-footer-contact`**  
   **Deactivate only**; **do not delete** the folder yet.

7. **Verify frontend**  
   Use section **4** below on homepage, `/contact` (or pages using the footer shortcode), and a known **placeholder** page (`_biopentra_placeholder_page` = `1`).

---

## 4. Verification

Use a **logged-out** hard refresh and DevTools **Network** + **Console** (and **View source** for mailto obfuscation on footer).

| Check | Pass criteria |
|--------|----------------|
| **Megamenu CSS** | Single load from `…/biopentra-storefront/assets/information-megamenu/information-mega.css` (ver may vary). |
| **Megamenu JS** | Single load from `…/biopentra-storefront/assets/information-megamenu/information-mega.js`. |
| **No legacy megamenu JS** | No request to `biopentra-information-megamenu/.../information-mega.js` in HTML or Network. |
| **Footer shortcode** | `[biopentra_footer_email]` expands (Elementor/footer templates + any raw post content). |
| **Footer email JS** | `footer-contact-email.js` loads **once** from `…/biopentra-storefront/assets/footer-contact/` (handle `biopentra-footer-contact-email`). |
| **`bp-e1.png`** | Loads **200** from `…/biopentra-storefront/assets/footer-contact/bp-e1.png`. |
| **Placeholder noindex** | Singular pages with `_biopentra_placeholder_page` = `1` emit **noindex** via `wp_robots` (inspect meta / headers). |
| **No console errors** | No new uncaught errors tied to megamenu or footer-contact. |
| **No 404s** | Above assets return **200** (or **304**). |

---

## 5. Rollback

If you must revert behavior:

1. **Reactivate `biopentra-information-megamenu`** (files on disk).  
2. **Reactivate `biopentra-footer-contact`** (files on disk).  
3. **Deactivate `biopentra-storefront`** (or remove from active list if policy requires).  
4. **Restore DB backup** only if Elementor cleanup must be undone (shortcode/HTML in DB is unchanged by Phase 1–2, but Elementor `_elementor_data` **was** changed during cleanup).  
5. **Clear caches / CDN** again.

**Partial rollback:** Reactivating only one legacy plugin may leave gaps (e.g. megamenu back but footer still on storefront, or vice versa). Prefer the **full trio** above for a known-good state unless you are surgically debugging.

---

## 6. Warnings

1. **Do not delete** legacy plugin folders (`biopentra-information-megamenu`, `biopentra-footer-contact`) from production until **multi-day stability**, backups verified, and **no** remaining references to legacy megamenu JS URLs in Elementor/exports.  
2. **Do not start Phase 3** until Phase 1–2 production behavior is accepted and monitored.  
3. **Do not keep legacy + storefront active long-term** — short overlap for cutover only. With both megamenu-related plugins active, **CSS may duplicate**; with both footer plugins active, **legacy wins** the shortcode registration (see `docs/footer-contact-migration-notes.md`). Neither is a steady-state configuration.

---

## Summary — final production cutover order

**Install `biopentra-storefront-0.2.0.zip` → activate `biopentra-storefront` → Elementor megamenu legacy JS cleanup (after DB backup) → purge caches/CDN → deactivate `biopentra-information-megamenu` → deactivate `biopentra-footer-contact` → verify megamenu + footer + placeholder SEO + Network/console → monitor.**
