# Phase 2 — Footer contact staging / local test results

**Date:** 2026-05-15  
**Environment:** Local WooCommerce Docker (`./wp`), site URL `https://www.biopentra.eu` in DB (HTML fetched via `http://127.0.0.1/`). **Production was not modified** by this run (local DB only: temporary page created and deleted).

**Plugins for main pass:** `biopentra-storefront` **active**, `biopentra-footer-contact` **inactive**, `biopentra-information-megamenu` **inactive** (aligned with Phase 1 megamenu test posture: storefront owns megamenu; legacy megamenu off).

---

## 1. `bp-e1.png` search

| Location | Result |
|----------|--------|
| **Repo** `custom-wordpress-plugins/` (initial glob) | Earlier tooling missed the binary; **`find`** located: `plugins/biopentra-footer-contact/assets/bp-e1.png` (PNG **420×52**, ~550 bytes). |
| **`wp-content/plugins/biopentra-footer-contact/`** (bind mount) | **Present** — same `assets/bp-e1.png`. |
| **`wp-content/uploads/`** | **Not used** — no `bp-e1*` files found. |
| **Themes** | **No references** to `bp-e1` in theme files searched. |

### Action taken

- **Copied** into storefront:  
  `plugins/biopentra-storefront/assets/footer-contact/bp-e1.png`  
  **Source:** `plugins/biopentra-footer-contact/assets/bp-e1.png` (repo + bind mount copy).

### Is the image required?

- **For PHP / shortcode:** **No** — `Biopentra_Storefront_Footer_Contact_Module` omits the `<img>` when the file is unreadable; the button and JS mailto still work.  
- **For visual parity with legacy:** **Yes** — legacy HTML always included the image when the file existed; without it, the email row looks different (text button only in the visual slot).  
- **Conclusion:** Image parity for production readiness = **include `bp-e1.png` in deploy/ZIP**; behavior without it is acceptable for emergency/smoke tests only.

### With vs without `<img>` (logic)

| Mode | Output |
|------|--------|
| **With** `bp-e1.png` | Same structure as legacy: `<img … width="420" height="52" …>` inside `.biopentra-footer-email`. |
| **Without** file | Same outer markup; inner span is **empty** (no `img`); no 404 for a missing image URL. |

---

## 2. Shortcode result

**Command:** `./wp eval 'echo do_shortcode("[biopentra_footer_email]");'`  

**With storefront only, legacy footer off:** Markup included **Contact form** link to `https://www.biopentra.eu/contact` and **`<img src="…/biopentra-storefront/assets/footer-contact/bp-e1.png">`**.  

**Rendered page:** `curl http://127.0.0.1/contact/` contained references to:  
`biopentra-storefront/assets/footer-contact/footer-contact-email.js?ver=1.1.1` and `…/bp-e1.png`.

**PHP fatal:** **None** observed during `wp eval` and plugin toggles.

---

## 3. JS result

- **Handle:** `biopentra-footer-contact-email` (unchanged from legacy).  
- **URL:** `…/plugins/biopentra-storefront/assets/footer-contact/footer-contact-email.js?ver=1.1.1`  
- **Count:** Single script reference in sampled `/contact/` HTML (grep for `footer-contact-email.js`).  
- **`HEAD`:** Asset path returns **200** on local Apache (not re-pasted here; same stack as prior megamenu checks).

**Console:** Not validated in this CLI-only run (no browser DevTools). Recommend one manual pass on staging.

---

## 4. Mailto obfuscation

- **View-source check (recommended):** Confirm literal `info@` does not appear in HTML (not executed in this automated log).  
- **Implementation:** Same `footer-contact-email.js` as legacy (`String.fromCharCode` mailto).  
- **CLI:** Script URL present on `/contact/`; no enqueue errors.

---

## 5. Placeholder `noindex`

- Local DB initially had **no** published placeholder pages.  
- **Temporary test:** Created published page with `_biopentra_placeholder_page = 1`, requested `http://127.0.0.1/phase2-qa-placeholder/`, observed:  
  `<meta name='robots' content='noindex, nofollow' />`  
  (`nofollow` may also come from other filters; **`noindex`** confirms the placeholder path works with storefront module.)  
- **Cleanup:** Post **deleted** after test (`./wp post delete 4301 --force`).

---

## 6. Duplicate-active behavior

**Test:** `biopentra-storefront` + **`biopentra-footer-contact` both active.**

| Check | Result |
|--------|--------|
| PHP fatal on `do_shortcode` | **No** |
| Shortcode output | **Legacy** image URL: `…/biopentra-footer-contact/assets/bp-e1.png` (legacy registers first; storefront module **skips** when `shortcode_exists( 'biopentra_footer_email' )`). |

**After test:** `biopentra-footer-contact` **deactivated** again; storefront remains the single owner for Phase 2 QA.

---

## 7. Pass / fail

| Criterion | Result |
|-----------|--------|
| Shortcode renders | **PASS** |
| No PHP fatal | **PASS** |
| JS loads from storefront path (legacy off) | **PASS** |
| `/contact` link | **PASS** |
| Placeholder noindex | **PASS** (with temp page) |
| Duplicate plugins: no fatal | **PASS** |
| Console errors | **Not tested** (manual) |

**Overall:** **PASS** for automated local checks; **CONDITIONAL** until a human confirms view-source obfuscation + browser console on staging.

---

## 8. Remaining risks

1. **Console / CSP:** Not covered by this run; staging browser pass still recommended.  
2. **Both plugins active:** No fatal, but **legacy wins** the shortcode and asset URLs — do not run in production.  
3. **ZIP / deploy:** Ensure `bp-e1.png` is included in packaged `biopentra-storefront` (now in repo under `assets/footer-contact/`).  
4. **Canonical URLs:** Local HTML may reference `https://www.biopentra.eu/...` for assets; offline-only hosts need `home`/`siteurl` or hosts alignment.  
5. **Elementor-only shortcode:** Shortcode may live in Elementor JSON (`_elementor_data`); full-page render tests should include at least one such template (e.g. footer library), not only `do_shortcode` in CLI.
