# Staging / local test plan — Phase 2 footer contact

**Scope:** `biopentra-storefront` footer-contact module vs legacy `biopentra-footer-contact`. **Do not** use production for destructive tests.

---

## Preconditions

- Staging or local Docker with the same theme/Elementor content that uses `[biopentra_footer_email]`.
- At least one **placeholder** page with post meta `_biopentra_placeholder_page` = `1` (if you rely on noindex behavior).

---

## A. Shortcode rendering

1. **Baseline (legacy):** With `biopentra-footer-contact` **active** and `biopentra-storefront` **inactive**, load a page containing `[biopentra_footer_email]`.
   - Confirm markup: `.bp-ft-v2-contact-group`, `.biopentra-footer-email-btn`, contact link to `/contact`.
2. **Cutover:** **Deactivate** `biopentra-footer-contact`, **activate** `biopentra-storefront`.
   - Reload the same page: markup should match (modulo image `src` path now under `biopentra-storefront` if PNG is deployed).
3. **Logged-out** and **logged-in** (if applicable): shortcode still expands.

---

## B. Email obfuscation

1. **View source** (not DevTools pretty-print alone): confirm **no** literal `info@biopentra.eu` string in HTML.
2. Click the email button: client should open mail composer with the correct address (same as legacy).
3. **Keyboard:** With focus on the button, **Enter** and **Space** trigger navigation (handled in JS).

---

## C. JS checks

1. **Network:** `footer-contact-email.js` loads **once** from  
   `…/plugins/biopentra-storefront/assets/footer-contact/footer-contact-email.js`  
   with `?ver=1.1.1` (or cached 304).
2. **Console:** no uncaught errors on pages using the shortcode.
3. If `bp-e1.png` is deployed: image returns **200**; if omitted, confirm no **404** for a missing `img` tag (there should be no `img` when the file is absent).

---

## D. Robots / noindex

1. On a **placeholder** singular page (`_biopentra_placeholder_page` = `1`), inspect rendered robots meta or `wp_robots` output: **`noindex`** present.
2. On a **normal** singular page without that meta: **no** unintended `noindex` from this module alone (other plugins/theme may still set robots).

---

## E. Duplicate plugin warning

With **both** plugins active:

- The module intentionally **does not register** if the shortcode already exists (legacy wins for shortcode + script registration when legacy loads first).
- Do **not** treat this as a supported steady state—pick **one** active plugin after QA.

---

## F. Rollback

1. **Deactivate** `biopentra-storefront`.
2. **Reactivate** `biopentra-footer-contact`.
3. Clear page/object/CDN caches and re-verify shortcode and noindex behavior.

---

## Pass criteria

| Check | Expected |
|-------|----------|
| Shortcode output | Same structure and copy as legacy |
| Mailto | Built only via JS; works on click / keyboard |
| Placeholder SEO | `noindex` when meta flag set |
| Network | Single storefront JS URL for this feature; no duplicate registration errors |
| Console | No new errors attributable to footer-contact |
