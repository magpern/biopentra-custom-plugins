# Phase 4 — Header auth staging / local QA results

**Date:** 2026-05-13  
**Environment:** Local Docker WordPress (`./wp`), `http://127.0.0.1/`  
**Setup:** `biopentra-storefront` **active**, `biopentra-header-auth` **inactive** (after sync of storefront `0.4.0` tree from repo into bind-mounted `wp-content/plugins/biopentra-storefront/`). `./wp cache flush` executed.

**Overall:** **Conditional pass** — automated checks (WP-CLI, `curl`, grep) pass for shortcode registration, My Account assets, cart asset URLs, storefront asset `200`, and menu filter logic. **Not run in this pass:** logged-in header behaviour, Elementor editor UI, browser console, full checkout render (redirect / Coming soon), non-empty cart free-shipping HTML, quick-view click-through.

---

## 1. Header / auth (browser-heavy)

| Check | Result | Notes |
|-------|--------|--------|
| Logged-out header link | **Partial** | Homepage HTML includes `biopentra-storefront/.../header-auth.css` and `header-auth.js`; markup contains `biopentra-header-auth` widget classes when rendered through Elementor. **Not** manually verified in a real browser session. |
| Logged-in dropdown / toggle / Escape | **Not run** | Requires authenticated browser or cookie session. |
| Console errors | **Not run** | No DevTools pass. |

---

## 2. Elementor

| Check | Result | Notes |
|-------|--------|--------|
| Existing header template renders widget | **Partial** | Front HTML references header-auth assets and header auth markup patterns; **no** Elementor editor load test. |
| Editor opens without widget error | **Not run** | Needs wp-admin + Elementor in browser. |

---

## 3. WooCommerce My Account

| Check | Result | Notes |
|-------|--------|--------|
| Page loads | **Pass** | `curl -sL http://127.0.0.1/my-account-2/` → **200** |
| Downloads hidden | **Pass** | `./wp eval` with `apply_filters( 'woocommerce_account_menu_items', … )` → `downloads` key absent; keys `dashboard,orders,edit-address,edit-account,customer-logout`. |
| Styling normal | **Partial** | `biopentra-wc-account-forms` link present (3 occurrences in saved HTML); visual pass not done. |

---

## 4. Checkout

| Check | Result | Notes |
|-------|--------|--------|
| Page loads / styling / console | **Partial / blocked** | `curl -sL http://127.0.0.1/checkout-2/` followed redirects to **Cart** title HTML (likely empty-cart redirect or **WooCommerce Coming soon** / store rules). `woocommerce_coming_soon` = **yes** in DB. `biopentra-wc-account-forms` count **0** on that final document. **Not** a definitive checkout shortcode test. |

---

## 5. Cart

| Check | Result | Notes |
|-------|--------|--------|
| Page loads | **Pass** | `curl` cart page returns full HTML document. |
| Free-shipping progress | **N/A** | Cart **empty**; `bph-cart-free-shipping` count **0** (implementation returns early when cart empty — expected). **Not** verified with non-empty cart (CLI `add_to_cart` failed for variable SKU in this DB). |
| Duplicate progress bars | **N/A** | No progress markup emitted on empty cart. |

---

## 6. Blocksy / product AJAX

| Check | Result | Notes |
|-------|--------|--------|
| Quick-view / AJAX ATC | **Not run** | No manual shop interaction in this pass. |

---

## 7. Network

| Asset | Result |
|-------|--------|
| `header-auth.css` from `biopentra-storefront/modules/header-auth/` | **Pass** on home + cart HTML; **HEAD** `…/header-auth.css` → **200** |
| `header-auth.js` | **Pass** (present on home/cart; Elementor may enqueue even logged-out) |
| `wc-account-forms.css` on account | **Pass** (link href under storefront module path) |
| `cart-enhancements.css` on cart | **Pass** |
| 404 on above | **None** observed for listed storefront URLs |

---

## 8. Rollback / coexistence

| Step | Result |
|------|--------|
| Activate `biopentra-header-auth` while storefront active (first attempt) | **Fail (fatal):** `Cannot redeclare biopentra_header_auth_register_assets()` — storefront had already loaded hook callbacks in the same request lifecycle used by WP-CLI activation. |
| **Repo fix applied:** early `return` in `plugins/biopentra-header-auth/biopentra-header-auth.php` when `function_exists( 'biopentra_header_auth_register_assets' )` | Prevents redeclare if storefront loaded first; legacy then no-ops for the rest of its main file. **Re-test on Docker was blocked** (permission denied writing bind-mounted legacy file from this environment). |

**Steady-state expectation:** With legacy **active**, storefront header-auth module **no-ops** (no double shortcode). Prefer **only one** plugin providing header-auth in production after cutover.

---

## Summary

| Area | Verdict |
|------|---------|
| Shortcode registered (legacy off) | **Pass** |
| Account menu filter | **Pass** |
| My Account HTTP + account CSS URL | **Pass** |
| Cart page + cart CSS URL | **Pass** |
| Storefront asset 200 | **Pass** |
| Checkout | **Inconclusive** (redirect / Coming soon) |
| Cart progress UI | **Not exercised** (empty cart) |
| Browser / Elementor editor / console / AJAX | **Not run** |
| Rollback activate (without repo fix on disk) | **Fail** (fatal) |
| Rollback with legacy guard (repo) | **Not re-verified on Docker** (fs permission) |

### Blockers / follow-ups

1. **Human browser QA** for dropdown, Escape, console, Elementor editor, checkout, and shop AJAX.  
2. **Non-empty cart** test for free-shipping markup and duplicate `woocommerce_before_cart_totals` output.  
3. **Deploy legacy early-return** to any environment where both plugins might be toggled in one admin request, then re-run rollback smoke.  
4. **WooCommerce Coming soon** continues to shape which templates return useful HTML for checkout QA.

---

**Pass / fail (strict):** **Fail** if rollback without legacy guard is a hard requirement; **Conditional pass** for storefront-only static/automated checks with gaps above.
