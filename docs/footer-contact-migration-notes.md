# Footer contact migration (Phase 2)

**Goal:** Move `biopentra-footer-contact` behavior into `biopentra-storefront` without changing front-end semantics.

**Legacy plugin:** `plugins/biopentra-footer-contact/` — **do not delete** the folder; deactivate after cutover QA.

---

## Shortcode behavior

| Tag | Callback | Notes |
|-----|----------|--------|
| `[biopentra_footer_email]` | `Biopentra_Storefront_Footer_Contact_Module::shortcode_email` | Same HTML structure as legacy: label, `<button class="biopentra-footer-email-btn">`, inner `<span class="biopentra-footer-email">`, optional `<img>`, “Contact form” link to `home_url( '/contact' )`. |

Legacy did not use shortcode **attributes**; the storefront callback accepts `$atts` for forward compatibility only (unused).

---

## Asset migration

| Legacy path | Storefront path |
|-------------|-----------------|
| `plugins/biopentra-footer-contact/assets/footer-contact-email.js` | `plugins/biopentra-storefront/assets/footer-contact/footer-contact-email.js` |
| `plugins/biopentra-footer-contact/assets/bp-e1.png` (if present in your tree) | `plugins/biopentra-storefront/assets/footer-contact/bp-e1.png` |

The **PNG was not present** in this repository snapshot; production or older archives may still have it—copy into `assets/footer-contact/` when available. If the PNG is **missing**, the module **omits** the `<img>` tag but keeps the button and **JS mailto** behavior (no PHP fatal).

Script **handle** remains **`biopentra-footer-contact-email`**; **version** remains **`1.1.1`** for cache-busting parity with legacy.

---

## SEO / noindex behavior

- **Filter:** `wp_robots`
- **Condition:** `is_singular()` and post meta **`_biopentra_placeholder_page`** equals **`'1'`** (same key/value contract as legacy).
- **Effect:** `$robots['noindex'] = true` for those pages only.

No change to other robots directives from this module alone.

---

## JS email obfuscation

`footer-contact-email.js` is unchanged: listens for click / Enter / Space on `.biopentra-footer-email-btn` and navigates to `mailto:` with the address built from `String.fromCharCode` (no literal address in HTML).

---

## Rollback

1. **Deactivate** `biopentra-storefront` (or leave active but not an option if other modules needed—prefer deactivate only if safe for your site).
2. **Reactivate** `biopentra-footer-contact`.

If only the footer module misbehaves, full plugin rollback is still the supported path until finer-grained toggles exist.

---

## Duplicate shortcode / both plugins active

WordPress allows only one callback per shortcode tag. **Load order** determines the winner if both register:

- This module **bails out entirely** when `shortcode_exists( 'biopentra_footer_email' )` is already true (legacy loaded first in typical alphabetical ordering).
- If `biopentra-storefront` loaded **before** legacy, this module registers first; legacy then **overwrites** the shortcode when its file loads.

**Recommendation:** Do **not** rely on both plugins being active. Use **one** active implementation after staging QA.

---

## Implementation file

- `plugins/biopentra-storefront/modules/footer-contact/class-footer-contact-module.php`
- Bootstrapped from `includes/class-biopentra-storefront.php`

`BIOPENTRA_STOREFRONT_FILE` (main plugin file path) is defined in `biopentra-storefront.php` for correct `plugins_url()` resolution of assets.
