# Urgent styling loss investigation

**Date:** 2026-05-15  
**Environment:** `/home/magpern/woocommerce`  
**Issue severity:** Production visual regression (non-home pages reportedly unstyled)

---

## Executive result

- **Root cause identified:** Missing Elementor generated CSS files for key non-home pages (`post-3755.css`, `post-82.css`) were being referenced in page HTML but returned **404**.
- **Why only SiteHome looked fine:** SiteHome (`post-3483.css`) existed and returned 200, while other page-specific CSS files were absent.
- **Fix applied:** Regenerated Elementor post CSS after restoring write capability for the CSS directory in the running stack.
- **Rollback used:** **No plugin rollback**. No plugin activation/deactivation changes. No storefront code changes.
- **Current status:** Shop/cart styling file links now resolve to **200** and pages render with normal structure/classes.

---

## 1) What was compared (SiteHome vs affected pages)

Pages compared:
- `/` (SiteHome, page 3483)
- `/shop/` (page 3755)
- `/cart-2/` (page 82)
- `/my-account-2/` (page 84)
- `/product/kisspeptin/` (product 3705)

### Findings before fix

- SiteHome HTML linked `wp-content/uploads/elementor/css/post-3483.css` (200).
- Shop HTML linked `.../post-3755.css` (**404**).
- Cart HTML linked `.../post-82.css` (**404**).
- Body classes and template wrappers were otherwise present (Blocksy + Elementor classes present, not stripped markup).
- Theme CSS and global Elementor CSS (`base-desktop.css`) loaded.
- Missing piece was page-specific Elementor generated CSS on non-home pages.

This pattern explains:
- “SiteHome styled” (its page CSS existed), and
- “other pages look full-width / missing hero/image layout” (their page CSS missing).

---

## 2) Elementor generated CSS checks

Directory checked:
- `/home/magpern/woocommerce/wp-content/uploads/elementor/css/`

Before fix, files present included:
- `post-3483.css` (home)
- `post-3782.css`, `post-3823.css`, `post-7.css`

Missing (but referenced by HTML):
- `post-3755.css` (shop)
- `post-82.css` (cart)

`wp elementor flush-css --regenerate` updated metadata timestamps but did not initially create missing page files due write-path mismatch in this environment.

---

## 3) Template/layout assignment checks

- Front page: `page_on_front = 3483` (SiteHome)
- `show_on_front = page`
- Shop page option: `woocommerce_shop_page_id = 3755`
- Cart page option: `woocommerce_cart_page_id = 82`
- Checkout page option: `woocommerce_checkout_page_id = 83`
- My Account page option: `woocommerce_myaccount_page_id = 84`

Elementor/WP template metadata:
- Shop (3755): `_elementor_edit_mode=builder`, `_wp_page_template=elementor_header_footer`
- Cart (82): `_elementor_edit_mode=builder`, `_wp_page_template=elementor_header_footer`

No evidence that page assignment itself was wrong.

---

## 4) Removed plugin asset reference checks

Searched frontend HTML and post meta DB for old plugin paths:
- `biopentra-information-megamenu`
- `biopentra-footer-contact`
- `biopentra-header-auth`
- `custom-variation-stock-selector`

Result:
- No `wp-content/plugins/<legacy-plugin>/...` URL paths found in DB query.
- Frontend uses `wp-content/plugins/biopentra-storefront/...` asset paths.

So this incident was **not** caused by residual hardcoded legacy plugin file URLs.

---

## 5) Cache/CDN checks

- `?nocache=1` still showed missing page CSS file URLs before fix.
- This indicates origin-side generated CSS file availability issue, not only stale edge cache.
- Cloudflare purge was not API-driven here; manual purge remains recommended after urgent fix.

---

## 6) Emergency rollback assessment

Rollback was evaluated but **not needed**:
- No evidence linked styling loss to storefront module logic itself.
- Issue was isolated to missing Elementor generated CSS files.
- Legacy plugin reactivation and storefront deactivation were therefore not justified.

---

## 7) Minimal fix applied

1. Confirmed missing files referenced from live HTML (`post-3755.css`, `post-82.css`) were 404.
2. Ensured Elementor CSS directory writeability for runtime generation in current container setup.
3. Regenerated per-page Elementor CSS for affected IDs.
4. Re-verified:
   - `post-3755.css` -> **200**
   - `post-82.css` -> **200**
   - SiteHome CSS remained healthy (`post-3483.css` -> 200)

No Elementor layout edits were performed.
No storefront plugin code changes were performed.
No plugin deletion/reactivation changes were performed.

---

## 8) Post-fix verification status

- SiteHome: styled, normal.
- Shop: page-specific Elementor CSS now present and 200.
- Cart: page-specific Elementor CSS now present and 200.
- My Account: normal structure; no missing placeholder issue.
- Product: normal template rendering and module assets load.

Recent logs:
- No new PHP fatals in the immediate post-fix window.
- Some Elementor font-icon warnings observed for contact page requests (non-fatal, separate concern).

---

## 9) Remaining risks / follow-up

1. **WP-CLI container UID mismatch** can reappear for future CSS regeneration actions in this stack.
   - Recommend aligning wpcli container user/group with WordPress container write user for `wp-content/uploads`.
2. Run manual Cloudflare purge to eliminate any stale edge variants.
3. Keep watch on Elementor icon warnings (`shield-halved`) — not root cause here, but should be cleaned up.

---

## Final incident status

- **Root cause:** Missing Elementor page CSS files for non-home pages (404).
- **Exact fix:** Restore CSS generation write path + regenerate missing Elementor post CSS.
- **Styling restored:** **Yes** for affected core pages based on link-level and markup verification.
- **Production stability:** **Stable after fix**, with follow-up recommended for container user alignment and CDN purge.
