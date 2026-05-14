# Phase 1 — Information megamenu browser QA

**Date:** 2026-05-14  
**Scope:** Phase **1** only (CSS + JS in `biopentra-storefront`, Elementor legacy `<script …/biopentra-information-megamenu/.../information-mega.js>` removed). **Not** Phase 2. **Not** production changes.

---

## Preconditions (local Docker)

Verified via WP-CLI against the WooCommerce Docker stack:

| Check | Result |
|--------|--------|
| `biopentra-storefront` active | Yes |
| `biopentra-information-megamenu` inactive | Yes |
| No `biopentra-information-megamenu/.../information-mega.js` in `wp_postmeta` | `0` rows (`LIKE` on that path) |
| Homepage fetched | `http://127.0.0.1/` (follow redirects as configured by WP) |

---

## URL tested

| Layer | URL | Notes |
|--------|-----|--------|
| **Local HTML / assets** | `http://127.0.0.1/` | Source of truth for “local Docker” markup and relative asset checks. |
| **Cursor IDE Browser** | Navigated to `http://127.0.0.1/` | The embedded browser **followed redirects** to the public canonical homepage `https://www.biopentra.eu/` (same pattern as a prior `curl` to `localhost`). Interactive steps below therefore ran against the **canonical** URL, not the loopback origin. **No production data was modified** (read-only load). |

---

## Browser used

**Cursor IDE Browser** (Chromium-based automation bundled with Cursor), plus **server-side** checks (`curl`, `./wp`).

---

## Network result

### Local Docker (`curl` + `HEAD`)

- **Homepage HTML** (`curl -fsSL http://127.0.0.1/`): exactly **one** stylesheet href containing  
  `biopentra-storefront/assets/information-megamenu/information-mega.css` and **one** script src containing  
  `biopentra-storefront/assets/information-megamenu/information-mega.js`.
- **Legacy plugin script URL** in HTML: **0** occurrences of  
  `biopentra-information-megamenu/assets/information-mega.js`.
- **Direct asset `HEAD`:**  
  `http://127.0.0.1/wp-content/plugins/biopentra-storefront/assets/information-megamenu/information-mega.js` → **200 OK** (no 404 for megamenu JS on the container).

### Embedded browser session (after redirect to `https://www.biopentra.eu/`)

From `browser_network_requests` on the loaded tab:

- `…/plugins/biopentra-storefront/assets/information-megamenu/information-mega.css?ver=1.3.2` — **one** request, **200**.
- `…/plugins/biopentra-storefront/assets/information-megamenu/information-mega.js?ver=1.3.2` — **one** request, **200**.
- **No** request URL contained `biopentra-information-megamenu/assets/information-mega.js`.
- No **404** observed for those megamenu asset URLs in the captured request list.

---

## Console result

From `browser_console_messages` on the same tab:

- **No `error`-level** messages recorded.
- **Warnings only:** Cursor browser dialog shim, jQuery Migrate informational line.

---

## Desktop result

| Task | Result |
|------|--------|
| Hover / open Information menu | **Not completed** in the embedded browser. Primary desktop mega-menu nodes (`li` for “Information”) reported **zero dimensions** / “not visible” to the automation layer at **1440×900**, so hover/click could not be driven reliably on the **true** desktop chrome. |
| Flyouts, active classes, Escape, moving between items | **Not exercised** (blocked as above). |
| No console errors | **Yes** (see Console). |

**Interpretation:** Desktop megamenu behaviour should be re-checked in a **local** Chrome/Firefox session on `http://127.0.0.1/` (or staging) with DevTools, because the embedded browser did not complete this slice.

---

## Mobile result

| Task | Result |
|------|--------|
| Viewport **390×812** | Applied via `browser_resize`. |
| Open mobile menu | **PASS** — `browser_click` on **Menu Toggle** moved the control to **expanded** and exposed drawer links (Home, Shop, Information, **Open Information**, etc.). |
| Open / close Information accordion | **PARTIAL** — **Open Information** (`button`) clicks returned **“Click target intercepted”** (full-viewport overlay; cookie consent UI present in the tree). Accordion expand/collapse was **not** confirmed end-to-end. |
| Resize mobile → desktop and back | **PARTIAL** — resize executed; full regression of layout state not asserted after overlay issue. |
| No console errors | **Yes** (see Console). |

---

## Baseline visual comparison

**Not performed** in this run (no captured baseline screenshots or side-by-side staging diff). Recommend a quick human pass comparing header/mega spacing against a known-good screenshot or staging before production cutover.

---

## Pass / fail

| Area | Verdict |
|------|---------|
| **Asset wiring + no legacy Elementor JS URL** (local HTML + local `HEAD`) | **PASS** |
| **Network hygiene** (embedded session after redirect) | **PASS** for storefront single CSS/JS + no legacy megamenu JS URL + 200s |
| **Console** (embedded session) | **PASS** (no errors) |
| **Full interactive desktop + mobile matrix** (embedded browser) | **FAIL / incomplete** — desktop targets not interactable; mobile accordion blocked by overlay |

**Overall:** **CONDITIONAL PASS** — integration and asset behaviour look correct on **local Docker** and match expectations on the **canonical** page load observed in the IDE browser, but **human QA on real local browser** is still recommended for hover/flyout/accordion/Escape.

---

## Remaining risks

1. **Canonical URL vs loopback:** Local pages may still emit absolute `https://www.biopentra.eu/...` asset URLs (from `siteurl`/`home`). Assets load if that host is reachable; **fully offline** local runs need consistent local domain or hosts mapping.
2. **Embedded browser ≠ user Chrome:** Automation could not complete desktop megamenu and mobile accordion clicks; **do not** treat this doc as a substitute for a short manual pass on `127.0.0.1` or staging.
3. **Cookie / consent overlays:** Can block automated clicks; manual tests should dismiss consent first.
4. **Deploy drift:** Ensure deployed `biopentra-storefront` includes `assets/information-megamenu/information-mega.js` (and the PHP **8.3**-safe `script_loader_tag` filter); missing files previously caused silent omission of JS on some copies.
5. **Elementor revisions:** If any revision still contained the legacy string, it would be cosmetic for live output but confusing in DB exports; the cleanup script targets known revision IDs (see `docs/elementor-information-mega-js-cleanup.md`).

---

## Related docs

- `docs/elementor-information-mega-js-cleanup.md` — DB cleanup procedure and revision note.  
- `docs/information-mega-js-cutover-plan.md` — Cutover rationale.  
- `docs/staging-test-phase-1-megamenu.md` — Staging checklist (CSS-focused; extend mentally for JS).
