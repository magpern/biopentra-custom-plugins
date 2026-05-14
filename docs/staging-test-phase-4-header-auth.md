# Staging test — Phase 4 header auth (storefront module)

**Preconditions:** Staging mirrors production theme (Blocksy), WooCommerce, Elementor, and header/footer templates using the **Biopentra login** widget and/or `[biopentra_header_auth]`.

**Plugin posture for main pass:** `biopentra-storefront` **active**, `biopentra-header-auth` **inactive** (legacy). Other storefront phases follow your existing checklist (megamenu/footer/CVSS as already deployed).

---

## 1. Guard smoke (legacy on)

1. Activate **`biopentra-header-auth`** only (or activate both legacy + storefront).
2. Expected: **no** PHP fatal; header still works from **legacy** URLs under `plugins/biopentra-header-auth/`.
3. Expected: storefront module **does not** register the shortcode (verify: `shortcode_exists( 'biopentra_header_auth' )` is true **once** from legacy only when legacy alone active).

---

## 2. Cutover posture (legacy off)

1. **Deactivate** `biopentra-header-auth`.
2. Keep **`biopentra-storefront`** active (with header-auth module files present).
3. Flush opcode / page / Elementor CSS caches.

---

## 3. Shortcode

- [ ] Page or widget containing `[biopentra_header_auth]` — logged **out**: “Log in” link to My Account (or wp-login fallback).
- [ ] Same — logged **in**: trigger shows identifier (email / display name / username per shortcode attrs); dropdown toggles; Escape closes.
- [ ] Attributes: `login_text`, `logout_text`, `my_account_text`, `identifier`.

---

## 4. Elementor widget

- [ ] Header template: existing **Biopentra login** widget renders front-end and editor preview.
- [ ] Widget panel: category **Biopentra** lists the widget; drag new instance onto a test page.
- [ ] Repeater dropdown links resolve (My account, Log out, custom URLs, icons).

---

## 5. My Account / checkout styling

- [ ] **My Account:** layout acceptable; **Downloads** endpoint absent from sidebar menu.
- [ ] **Checkout:** guest + logged-in; accent CSS variables present (`--bph-wc-accent*`).
- [ ] Network: `biopentra-wc-account-forms` CSS loads from `…/biopentra-storefront/modules/header-auth/assets/wc-account-forms.css`.

---

## 6. Cart

- [ ] Cart empty: no progress fatal.
- [ ] Cart with items below threshold: “Only … away from free shipping” + progress bar; `aria-valuenow` sensible.
- [ ] Cart above threshold: “You qualify for free shipping”.
- [ ] Optional: filter `biopentra_header_auth_cart_free_shipping_threshold` on staging to confirm filter still runs.

---

## 7. Blocksy AJAX add-to-cart

- [ ] Shop archive or quick view: add simple/variable product with AJAX enabled — **no** unexpected full-page POST navigation.
- [ ] Product page: still behaves normally.

---

## 8. Assets / duplicates

- [ ] Network: `biopentra-header-auth` CSS + JS (when logged in) from **storefront** path only.
- [ ] No duplicate requests from both `plugins/biopentra-header-auth/` and storefront for the same handle on the same page.

---

## 9. Admin / palette

- [ ] Log in as administrator on Blocksy: `admin_init` palette routine does not fatal; if `biopentra_header_auth_blocksy_palette_applied` is already `1`, theme mod is not rewritten unexpectedly.

---

## 10. Console

- [ ] No new JavaScript errors on header dropdown interaction.

---

## Rollback

Reactivate **`biopentra-header-auth`**; deactivate storefront only if required for broader rollback. Clear caches.
