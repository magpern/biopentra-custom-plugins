# PDP-1 — Expanded Product Page Redesign — change record

**Status:** **PO-approved / frozen on DEV**
**Formerly named:** "Purchase Summary Redesign" — superseded by this expanded scope.
**Storefront:** `0.9.35` / tag **`storefront-v0.9.35`**
**Blocksy Child:** `1.2.9` / tag **`v1.2.9`**
**Plan:** [plans/PDP-1_PURCHASE_SUMMARY_REDESIGN.md](../plans/PDP-1_PURCHASE_SUMMARY_REDESIGN.md) (incl. Addendum A — Blocksy price-ownership correction; Addendum B — WP3/WP4 visual PO decisions)
**Plan-freeze commit:** `4ae5ee7`
**Impl range:** `4ae5ee7`..`466d0c0` (`biopentra-custom-plugins`), `v1.1.0`..`1c8ff83` (`biopentra-blocksy-child`)
**Rollback baseline:** Storefront `storefront-v0.9.33` / Blocksy Child `1.1.0`

## Scope inventory

### A — Original PDP-1 purchase-panel scope (as frozen)
Purchase panel wrapping native price/stock/variation/quantity/Add-to-cart via additive hooks only; category eyebrow with non-"Uncategorized" fallback; text-only trust row (§11, later overridden by B1); no template override, no new JS.

### B — WP0 Addendum A: Blocksy price-ownership architecture correction
Live WP0 testing found the plan's original `remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10)` returned `false` and duplicated the price on `bacteriostatic-water`. Root cause: Blocksy's `single-modifications.php` hooks `wp` at priority `9000000000`, strips WooCommerce's native summary hooks (title/price/rating/excerpt/add_to_cart/meta/sharing), and re-renders them itself via a theme-owned ordered-layer renderer (`render_layout()` / `blocksy:woocommerce:product-single:layout` filter). Corrected mechanism: PDP-1 hooks that filter and disables only the `product_price` layer, only for `is_type('simple')`, only for the current in-memory render (no persisted theme-mod write) — WooCommerce's own `woocommerce_template_single_price()` is then called exactly once inside the panel. Variable products are excluded from this filter entirely; their native top-level price range is untouched. Verified: shop archive/loop prices unaffected (filter cannot leak into the separate loop-card layer set).

### C — PO-authorized expansion (beyond the original frozen plan)
- **B1 (trust row):** icon + two-line copy ("EU Warehouse / Fast delivery", "Secure Payment / SSL encrypted", "Lab Quality / Purity verified"), overriding the originally-locked single-line copy.
- **B2 (gallery zoom removal):** desktop hover-to-zoom disabled via scoped CSS suppression of the `.zoomImg` overlay — no JS/theme-mod toggle exists, so this is CSS-only; explicit, scoped exception to the plan's "gallery stays frozen" boundary.
- **B3 (variation availability restyling):** PO asked for availability to move outside the panel; confirmed this needs new custom variation-state JS (prohibited); PO accepted the constraint and took the CSS-only alternative — green checkmark restyle, still bundled inside the panel per §2.2's documented asymmetry.
- **B4 (metadata label alignment):** Category/Tags labels wrapped via scoped `gettext`/`ngettext` filter; SKU handled via output-buffer string-replace instead (gettext would double-escape through `esc_html_e()`).
- **Simple-product stock relocation (final commit, `466d0c0`):** simple-product stock line moved into the panel to match variable's bundled-availability treatment, via the same suppress-native/re-render pattern applied to `woocommerce_get_stock_html`, plus a checkmark-scope fix so the green check only shows for genuine in-stock states.
- **Full gallery/thumbnail feature + `flexy.js` root-cause fix** (`biopentra-blocksy-child` `1.1.4`→`1.2.9`): thumbnail-strip clipping fixed at its root cause (centering/spacing), sticky gallery explicitly disabled; tabs (Description/Additional Information) restyled; SKU CSS-only fallback removed as redundant once B4 landed; divider spacing tightened.

## Final architecture / ownership

- **Commerce authority:** WooCommerce core, unchanged. No template override (`templates/single-product/add-to-cart/{simple,variable}.php` untouched at the file level), no duplicated price/stock/variation state, no custom variation JS, no CSS-hidden duplicate price.
- **Outer summary layer ownership:** Blocksy's `render_layout()` (title/price-range/excerpt), extended via its own supported `blocksy:woocommerce:product-single:layout` filter seam — not a private/internal hook.
- **Inner add-to-cart form:** native WooCommerce hooks only (`woocommerce_before/after_add_to_cart_form`, `woocommerce_after_variations_table/_form`), per the plan's original §2 architecture — unaffected by the Addendum A correction.
- **New code surface:** one PHP module (`plugins/biopentra-storefront/modules/pdp-purchase-panel/class-pdp-purchase-panel-module.php`) + CSS-only additions in `biopentra-blocksy-child/assets/pdp/{purchase-panel,gallery,layout,tabs}.css`. Zero new JavaScript.
- **Add-to-cart transport:** unchanged — Blocksy's existing bespoke fetch-based AJAX (`add-to-cart-single.js` → `blocksy_add_to_cart` → `added_to_cart` event/fragments), confirmed still wired (`.ct-ajax-add-to-cart` present on both simple and variable product pages, verified via live `curl` DOM fetch during this closure).
- **Variation SKU swap:** native, unmodified WooCommerce core JS (`add-to-cart-variation.js`), untouched by the panel wrapper.

## Final responsive behavior

Two-column split at Blocksy's native `min-width: 1000px` boundary (D2A, unchanged). All 8 required viewports (360/390/430/768 single-column; 1024/1025/1440/1680 two-column) fall cleanly on one side of that boundary — no new breakpoint introduced by PDP-1.

## Final versions / tags

| Repo | Version | Tag |
|---|---|---|
| `biopentra-custom-plugins` (storefront) | `0.9.35` | `storefront-v0.9.35` |
| `biopentra-blocksy-child` | `1.2.9` | `v1.2.9` |

## Final validation (this closure, 2026-08-24)

- **WP-CLI / live DOM (via `curl`):** confirmed on `bacteriostatic-water` (simple), `tirzepatide` (variable), `m21-postrelease-variable` (zero-purchasable-variations):
  - Simple: exactly one native `.price` in the summary/panel (the `bp-pdp-sticky__price` D2B copy and an unrelated Elementor related-product-card price are separate, expected occurrences elsewhere on the page); `bp-pdp-purchase-panel` present once; `bp-pdp-eyebrow` present; trust row present; `.ct-ajax-add-to-cart` present.
  - Variable: top-level `.price` range still renders natively (8 `.price`-class occurrences on the page include the variation-table range plus nested `woocommerce-Price-amount` spans — consistent with an unduplicated single range, not a second top-level price block); panel present once; AJAX class present.
  - Zero-variations fixture: `bp-pdp-purchase-panel` count is **0** (panel correctly never opened — render-state flag working, no orphaned closing tag); native `out-of-stock`/"unavailable" messaging renders correctly.
  - Gallery (`woocommerce-product-gallery` classes) and tabs (`woocommerce-Tabs-panel`) present on the simple-product page.
  - D2B sticky-bar markup present and unmodified.
- **Automated Playwright acceptance suite, D2A/D2B regression specs, and screenshot capture: NOT executed as part of this closure.** No Node.js/npx runtime is present in this environment (`node`, `npx`: command not found), even though `storefront-acceptance/node_modules/.bin/playwright` and Chromium/Firefox browser binaries are installed under `~/.cache/ms-playwright`. This is an environment limitation of the session that ran this closure, not a defect in PDP-1 or in the acceptance harness. **The plan's own WP0/Addendum-A structural proof and the WP2/B1–B4 visual validation were run live via Playwright during implementation** (per the plan document's own verification sections) — this closure could not independently re-run that suite and relied on WP-CLI + `curl`-based DOM inspection instead. This is reported honestly as a gap, not claimed as a pass.
- **Accessibility:** not independently re-verified at closure (no browser tooling available); relies on the plan's own accessibility section (§13 — eyebrow is `<p>` not a heading, H1 remains sole heading, plain `<div>`/`<ul>` markup, no `aria-hidden` misuse) and the implementation-time Playwright/axe checks referenced in the plan and Addendum B evidence notes.
- **Cache:** DEV full-page cache (`biopentra-cache-infrastructure/tests/http/dev-clear-cache.sh`) cleared successfully before this validation pass.
- **Multicurrency (UMC):** not independently re-tested at closure (would require a browser session to exercise `?currency=`); relies on the plan's WP0/Addendum-A verification notes, which record a live spot-check as part of implementation.

## Known non-blocking issues

- `storefront-acceptance` has 4 pre-existing untracked files (`tools/v1a-capture-desktop.mjs`, `tools/v1a-capture.mjs`, `tools/v1a-iter2-capture.mjs`, `screenshots/header-mega-menu-regression-2026-08-22/1440-test-run-before-keyboard-fix.png`, all dated Aug 20-22) — leftover tooling from an earlier, unrelated header-mega-menu regression investigation (commits `d24d6af`/`ad4b709`/`c72d460`/`505390c`/`4042877`/`0a837c9` in `biopentra-custom-plugins`, same Aug 20-22 window). Not part of PDP-1, not committed by this closure, left as-is.
- No Node/Playwright runtime available in this closure's execution environment — automated regression/screenshot re-verification deferred to a session with that tooling.

## Rollback

Deterministic, code/tag-only (no WP-mutation required — all PDP-1 changes are additive hooks/CSS, no database writes, no Elementor JSON mutation):

1. Redeploy `biopentra-custom-plugins` at tag `storefront-v0.9.33` (removes the `pdp-purchase-panel` module entirely — `remove_action`/`add_filter` calls it registered simply don't run).
2. Redeploy `biopentra-blocksy-child` at tag `v1.1.0` (removes `assets/pdp/{purchase-panel,tabs}.css` and the gallery/layout CSS additions layered on top of D2A/D2B).
3. Clear DEV full-page cache (`biopentra-cache-infrastructure/tests/http/dev-clear-cache.sh`).

No plugin reactivation, no theme-mod reset, no database rollback required — WooCommerce's native hook registrations resume at their default priorities automatically on the next page load once PDP-1's code is absent (hook removal is per-request, not persisted).

## Production replay requirements (not performed by this closure)

A later production rollout would require, in order: (1) deploy `biopentra-custom-plugins` at `storefront-v0.9.35` to production plugin directory; (2) deploy `biopentra-blocksy-child` at `v1.2.9` to production theme directory; (3) confirm production's `has_ajax_add_to_cart` / `woocommerce_cart_redirect_after_add` / `blocksy_get_product_view_type()` settings match DEV's (the plan's mechanisms are gated on these); (4) clear production full-page cache; (5) explicit separate PO GO per repository conventions (this closure does not authorize or perform any production action).
