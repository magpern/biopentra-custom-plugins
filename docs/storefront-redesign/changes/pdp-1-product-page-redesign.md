# PDP-1 — Expanded Product Page Redesign — change record

**Status:** **PO-approved / VERIFIED / frozen on DEV / CLOSED**
**Formerly named:** "Purchase Summary Redesign" — superseded by this expanded scope.
**Storefront:** `0.9.35` / tag **`storefront-v0.9.35`**
**Blocksy Child:** `1.2.10` / tag **`v1.2.10`** (final, corrected — see "Accessibility corrective" below; `v1.2.9` is an immutable historical pre-corrective tag)
**Plan:** [plans/PDP-1_PURCHASE_SUMMARY_REDESIGN.md](../plans/PDP-1_PURCHASE_SUMMARY_REDESIGN.md) (incl. Addendum A — Blocksy price-ownership correction; Addendum B — WP3/WP4 visual PO decisions)
**Plan-freeze commit:** `4ae5ee7`
**Impl range:** `4ae5ee7`..`466d0c0` (`biopentra-custom-plugins`), `v1.1.0`..`4512cca` (`biopentra-blocksy-child`)
**Rollback baseline:** Storefront `storefront-v0.9.33` / Blocksy Child `1.1.0`

**Verification history:** initial closure pass (code/docs/tags) completed without a working
Playwright runtime; a follow-up post-tag verification pass ran the real acceptance suite via
Docker and found one real, confirmed accessibility defect (`.bp-pdp-meta-label` contrast) —
see "Accessibility corrective" below. All other gates passed live. The corrective is now
merged, retested, and tagged (`v1.2.10`); PDP-1 is fully verified and closed as of that tag.

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
| `biopentra-blocksy-child` | `1.2.10` (final, corrected) | `v1.2.10` |

`v1.2.9` remains an immutable historical tag — it is the pre-accessibility-corrective
PDP-1 state and **did not pass final accessibility verification** (see below). `v1.2.10`
is the authoritative final PDP-1 theme baseline. Neither `v1.2.9` nor `storefront-v0.9.35`
were moved or retagged.

## Final validation (this closure, 2026-08-24)

- **WP-CLI / live DOM (via `curl`):** confirmed on `bacteriostatic-water` (simple), `tirzepatide` (variable), `m21-postrelease-variable` (zero-purchasable-variations):
  - Simple: exactly one native `.price` in the summary/panel (the `bp-pdp-sticky__price` D2B copy and an unrelated Elementor related-product-card price are separate, expected occurrences elsewhere on the page); `bp-pdp-purchase-panel` present once; `bp-pdp-eyebrow` present; trust row present; `.ct-ajax-add-to-cart` present.
  - Variable: top-level `.price` range still renders natively (8 `.price`-class occurrences on the page include the variation-table range plus nested `woocommerce-Price-amount` spans — consistent with an unduplicated single range, not a second top-level price block); panel present once; AJAX class present.
  - Zero-variations fixture: `bp-pdp-purchase-panel` count is **0** (panel correctly never opened — render-state flag working, no orphaned closing tag); native `out-of-stock`/"unavailable" messaging renders correctly.
  - Gallery (`woocommerce-product-gallery` classes) and tabs (`woocommerce-Tabs-panel`) present on the simple-product page.
  - D2B sticky-bar markup present and unmodified.
- **Superseded — see "Post-tag verification" below.** The gap noted in the original closure pass (no Node/Playwright runtime available at that time) was closed in a follow-up session using the repository's established Dockerized Playwright pattern (`storefront-acceptance/tools/run-dev.sh` conventions — `mcr.microsoft.com/playwright:v1.51.0-jammy`, no `docker.sock` mount, Coming-Soon bypass toggled on the host via `tools/wp-coming-soon.sh`).

## Post-tag verification (real Playwright/axe-core run, 2026-08-24)

Run against `storefront-v0.9.35` / `v1.2.9` (pre-corrective):

- **PDP-1 acceptance suite** (`pdp-purchase-panel.spec.ts`, `pdp-layout.spec.ts`, `pdp-sticky.spec.ts`): 12 passed, 0 failed (84 skipped — viewport-filtered by design, not failures).
- **Simple/variable Add to cart:** live-exercised — single native price inside panel, Blocksy's fetch-based AJAX (`added_to_cart` event, no page reload) confirmed on both product types.
- **Variation switching / SKU / availability:** live-exercised on `tirzepatide` — table stays outside panel, bundled price+availability+SKU update inside panel on variation change.
- **Zero-purchasable-variations fixture (`m21-postrelease-variable`):** no orphaned panel markup, correct native out-of-stock/unavailable messaging.
- **UMC:** `?currency=SEK` smoke-checked — simple relocated price, variable top-range, and selected-variation price all convert correctly; single price element preserved.
- **D2B sticky bar:** confirmed unaffected by the panel wrapper (regression assertion + full `pdp-sticky.spec.ts` suite, both passed).
- **Responsive matrix:** all 8 required viewports (360/390/430/768/1024/1025/1440/1680) exercised via the suite's configured projects — 0 PDP-file failures at any width.
- **Accessibility (axe-core, real Chromium):** **found a real, confirmed defect** — see "Accessibility corrective" below. Also noted (pre-existing, out of PDP-1 scope, not introduced or touched by any PDP-1 CSS file): `.elementor-menu-cart__container` `aria-hidden-focus` (Elementor mini-cart), generic footer/breadcrumb contrast items, and native WooCommerce/Blocksy contrast on `.sku` value text (2.97:1), `a[rel="tag"]` links (3.74:1), strikethrough sale-price `del` (2.43:1), and `.single_add_to_cart_button` (4.3:1, borderline) — none of these selectors have a color rule in any `biopentra-blocksy-child/assets/pdp/*.css` file (confirmed via grep), so they predate and are independent of PDP-1.
- **Targeted/broader regression sweep:** 50 failures surfaced in `baseline.spec.ts`, `card-interaction.spec.ts`, `home-ia.spec.ts` — **zero in any PDP file**. Pre-existing, unrelated to PDP-1, not investigated or fixed per the M1–M10 regression-boundary rule (do not reopen unrelated defects).
- **Screenshots:** `storefront-acceptance/artifacts/pdp1-verify-closure/` (16 files — simple/variable × 390/1440, full page + purchase-summary/gallery/tabs crops).
- **Cache:** DEV full-page cache cleared before and after this verification pass.

## Accessibility corrective (v1.2.9 → v1.2.10)

**Defect (blocking, confirmed via axe-core on real Chromium):** `.bp-pdp-meta-label`
(the SKU/Category/Tags label wrapper `purchase-panel.css` introduces) measured
**2.97:1** effective contrast against a required **4.5:1**, on both `bacteriostatic-water`
(simple) and `tirzepatide` (variable).

**Root cause:** Blocksy's native `.product_meta > span > *` rule (in the parent theme's
compiled `woocommerce.min.css`, not a PDP-1 file) applies `opacity: .7` to every direct
child of a `.product_meta > span`, including the label span PDP-1 introduces.
`var(--bp-color-text-muted, #666)` measures ~5.5:1 at full opacity (comfortably AA) but
drops to 2.97:1 once that inherited 0.7 opacity is applied — a CSS interaction bug, not
an architectural or color-token problem.

**Fix (`purchase-panel.css`, commit `4512cca`):** scoped `opacity: 1` reset added to the
existing `.bp-pdp-meta-label` rule. Only the label span is affected — the adjacent value
text (SKU code, category/tag links) keeps Blocksy's native muted treatment untouched, and
no other metadata/typography/layout rule was changed.

**Retest (real axe-core/Playwright, both fixtures, post-fix):** `.bp-pdp-meta-label`
shows **zero color-contrast violations** — confirmed resolved. Computed style verified
`opacity: 1`, `color: rgb(102, 102, 102)` on both "SKU:"/"Category:"/"Categories:"/"Tags:"
labels. Narrow PDP-1 acceptance suite re-run post-fix: 12 passed, 0 failed (unchanged).

**Version/tag:** `biopentra-blocksy-child` bumped `1.2.9` → `1.2.10`, commit `4512cca`,
tag `v1.2.10` (pushed). `v1.2.9` left immutable as the historical pre-corrective tag.
`biopentra-custom-plugins`/`storefront-v0.9.35` unchanged — no PHP/storefront code was
touched by this corrective (CSS-only fix, in the theme repo).

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

A later production rollout would require, in order: (1) deploy `biopentra-custom-plugins` at `storefront-v0.9.35` to production plugin directory; (2) deploy `biopentra-blocksy-child` at `v1.2.10` (the corrected, final tag — not `v1.2.9`) to production theme directory; (3) confirm production's `has_ajax_add_to_cart` / `woocommerce_cart_redirect_after_add` / `blocksy_get_product_view_type()` settings match DEV's (the plan's mechanisms are gated on these); (4) clear production full-page cache; (5) explicit separate PO GO per repository conventions (this closure does not authorize or perform any production action).
