# Phase 1 — Production cutover (Information megamenu only)

**Status:** Phase **1** repository work is technically complete; **manual human QA** and **staging sign-off** are required before using this checklist on production. **Do not** start Phase 2 until production has been stable and QA is finished (see [Why Phase 2 is paused](#why-phase-2-is-paused)).

**Scope:** Information mega-menu **CSS + JS** now ship from **`biopentra-storefront`**; Elementor must no longer inject the legacy **`biopentra-information-megamenu/.../information-mega.js`** tag. This document is **Phase 1 only** (megamenu / storefront slice). It does **not** cover later consolidation phases.

**Related docs**

- Elementor DB/script cleanup (needles, backup, helper script): `docs/elementor-information-mega-js-cleanup.md`  
- Browser and asset QA notes: `docs/phase-1-megamenu-browser-qa.md`  
- Staging CSS-focused checklist (still relevant for mindset): `docs/staging-test-phase-1-megamenu.md`  
- ZIP build: `./scripts/build-zips.sh` → `builds/zips/{slug}-{version}.zip` (artifacts are gitignored; keep copies off-repo for deploy/rollback).

---

## A. Preconditions

Complete **before** changing production traffic.

| Precondition | Notes |
|--------------|--------|
| **Staging QA passed** | Staging (or equivalent) mirrors production plugins/theme/Elementor. **Human** desktop + mobile megamenu checks completed; `docs/phase-1-megamenu-browser-qa.md` gaps closed on a real browser against staging URL. |
| **Storefront ZIP built** | Run `./scripts/build-zips.sh` from repo root. Confirm `builds/zips/biopentra-storefront-*.zip` exists and version matches the release you intend to deploy. |
| **DB backup completed** | Full MySQL/MariaDB dump (or host snapshot) **immediately before** any Elementor meta change. Store outside the web root with retention policy. |
| **Plugin backups completed** | Tarball or ZIP of `wp-content/plugins/biopentra-storefront` (current prod, if any) and `wp-content/plugins/biopentra-information-megamenu` **before** overwrite. |
| **Maintenance window (recommended)** | Low-traffic window + comms if you expect brief double-load of megamenu CSS when both plugins are momentarily active, or any cache propagation delay. |
| **Rollback ZIP available** | Prior known-good **`biopentra-storefront`** ZIP **and** prior **`biopentra-information-megamenu`** package on disk (or in artifact store), plus the **pre-cutover DB dump**, so rollback does not depend on rebuilding from memory. |

**Do not** delete the legacy plugin directory from production servers until long-term stability is proven (see [Post-cutover warning](#e-post-cutover-warning)).

---

## B. Production cutover steps

Order matters: **storefront must be installed and active** so `information-mega.js` is enqueued **before** you remove the Elementor-injected script and **before** deactivating the legacy plugin (otherwise the menu can lose interactivity).

1. **Upload / install `biopentra-storefront` ZIP**  
   WordPress **Plugins → Add New → Upload Plugin**, or deploy `builds/zips/biopentra-storefront-{version}.zip` via your pipeline. Ensure `plugins/biopentra-storefront/assets/information-megamenu/information-mega.css` and `information-mega.js` exist on disk after extract.

2. **Activate `biopentra-storefront`**  
   Confirm no PHP fatals in `debug.log`. Spot-check one front page: Network should show storefront megamenu CSS (and likely JS). If legacy megamenu is **still active**, you may temporarily see **duplicate CSS** handles; keep this window **short** (see precondition above).

3. **Run Elementor `information-mega.js` cleanup**  
   Follow `docs/elementor-information-mega-js-cleanup.md`: full DB backup, then remove **only** the legacy `<script … biopentra-information-megamenu/.../information-mega.js …>` from `_elementor_data` / `_elementor_element_cache` as documented (or use repo helper `scripts/remove-elementor-information-mega-js.php` with `BIOPENTRA_ELEMENTOR_CLEANUP_APPLY=1` in a controlled WP-CLI context). **Re-save or regenerate Elementor cache** if your workflow requires it.

4. **Clear all caches / CDN**  
   Page cache, opcode cache (if safe), object cache, **Elementor** CSS/file cache, **Cloudflare** (or other CDN) purge for HTML and static assets. Purge URLs that include `information-mega` if your CDN supports tag or path purge.

5. **Deactivate `biopentra-information-megamenu`**  
   Plugins screen: deactivate only (keep files on disk for rollback).

6. **Verify frontend assets**  
   Hard-refresh homepage (logged-out). Confirm exactly **one** `information-mega.css` from `biopentra-storefront/.../information-megamenu/`, **one** `information-mega.js` from the same path, **no** request to `biopentra-information-megamenu/.../information-mega.js`, **no 404** on those assets.

7. **Verify menu behavior**  
   Desktop: hover Information, flyouts, keyboard **Escape** closes where applicable. Mobile: drawer, Information accordion/sections. No unexpected console errors.

---

## C. Verification checklist

Use DevTools (Network + Console) on the **homepage** (and any other template that renders the same header).

| Check | Pass criteria |
|--------|----------------|
| **CSS URL** | Single stylesheet from `.../plugins/biopentra-storefront/assets/information-megamenu/information-mega.css` (version query string may vary). |
| **JS URL** | Single script from `.../plugins/biopentra-storefront/assets/information-megamenu/information-mega.js` (handle/id may show as `biopentra-storefront-information-mega-js`). |
| **No legacy JS** | View source / Network: **no** `biopentra-information-megamenu/assets/information-mega.js`. |
| **No 404s** | Megamenu CSS/JS return **200** (or **304** after first load). |
| **No console errors** | No uncaught exceptions tied to megamenu or missing scripts. |
| **Desktop hover** | Information mega opens; flyouts and focus/hover states behave as before. |
| **Mobile accordion** | Drawer opens; Information section expands/collapses; resize desktop ↔ mobile does not leave stuck state. |

---

## D. Rollback procedure

If behaviour or metrics regress:

1. **Reactivate `biopentra-information-megamenu`** (plugin files must still be present).  
2. **Deactivate `biopentra-storefront`** (or remove its enqueue impact per your policy) if you need to return entirely to legacy enqueue behaviour.  
3. **Restore DB backup** if Elementor meta was changed and you need the old `_elementor_data` / cache (e.g. legacy script tag back in HTML). Import only with a tested procedure and a second backup of the failed state.  
4. **Clear caches / CDN** again after rollback.  
5. Re-verify Network (legacy URL may reappear if DB restored to pre-cleanup state).

For **partial** rollback (plugin only, no DB): reactivating legacy may restore CSS from legacy while storefront is off; if Elementor still lacks the old script tag, you may need **DB restore** or manual Elementor edit to restore the previous HTML widget.

---

## E. Post-cutover warning

**Do not delete** the **`biopentra-information-megamenu`** plugin folder from production until **all** of the following are true:

- **Multiple days** of stable production operation with storefront-only megamenu assets and acceptable error logs.  
- **No remaining Elementor or DB references** to the legacy `information-mega.js` URL (grep exports, backups, and `wp_postmeta` for `biopentra-information-megamenu` + `information-mega.js`).  
- **Backups verified**: you have successfully listed or restored a test import from the cutover-era DB dump on a non-production instance.

Keeping the legacy directory on disk does not require the plugin to stay **active**; it is a **safety net** for rollback and for any hardcoded static paths until you are certain they are gone.

---

## Why Phase 2 is paused

Further **storefront consolidation** (Phase 2 and beyond) should **wait** until:

1. **Production cutover stability** — Phase 1 changes (storefront plugin, Elementor cleanup, legacy megamenu inactive) have run in production without emergency rollback, and monitoring shows no spike in front-end errors or support tickets tied to navigation.  
2. **Manual QA completion** — The items marked incomplete or conditional in `docs/phase-1-megamenu-browser-qa.md` (desktop hover, mobile accordion, consent overlays, etc.) are signed off by a human on **production** or **staging that matches production**.  
3. **No regressions from storefront architecture** — You are confident adding more modules to `biopentra-storefront` will not destabilize the site before the megamenu path is proven.

Pausing here avoids mixing **new architectural debt** (more features in one plugin) with an **unfinished cutover** (human QA and production soak time). Resume Phase 2 only after explicit approval and a short written “go” note in your change log or ticket.

---

## Summary: production cutover order (short)

**Install storefront → activate storefront → Elementor legacy script cleanup (after DB backup) → purge caches/CDN → deactivate legacy megamenu plugin → verify Network + behaviour → monitor → only later consider removing legacy plugin files.**
