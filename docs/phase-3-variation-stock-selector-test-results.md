# Phase 3 — Variation stock selector (storefront CVSS) staging QA

**Date:** 2026-05-13  
**Repo:** `custom-wordpress-plugins` (storefront module)  
**Environment:** Local WooCommerce Docker (`./wp` against bind-mounted `wp-content`), site URL option `https://www.biopentra.eu`.

## Plugin / HPOS setup

| Item | State during QA |
|------|-----------------|
| `biopentra-storefront` | Active |
| `custom-variation-stock-selector` (legacy) | **Inactive** (not run alongside storefront CVSS) |
| WooCommerce | Active |
| HPOS | Enabled (`./wp wc hpos status` → HPOS enabled: yes) |
| HPOS incompatible plugins list | `get_option('_wc_feature_custom_order_tables_incompatible_plugins')` unset / empty — **no** storefront compatibility warning surfaced via that mechanism |
| Storefront HPOS declaration | `FeaturesUtil::declare_compatibility( 'custom_order_tables', BIOPENTRA_STOREFRONT_FILE, true )` on `before_woocommerce_init` (see storefront variation module) |

## Catalog notes

- All variable products in this database have **2** published variations (none exceed the default AJAX variation threshold **30**). **Above-threshold product:** not available in this catalog (N/A).
- **WooCommerce Coming soon:** `woocommerce_coming_soon` was **`yes`** at the start of the run. While enabled, single product views render the **Coming soon** block instead of the variable add-to-cart template, so **`woocommerce_before_variations_form` does not run** and CVSS assets do not appear. For HTML/network checks, Coming soon was set to **`no` temporarily**, then restored to **`yes`** after captures.

## Test products

| # | Product ID | Slug | Role in QA |
|---|------------|------|------------|
| 1 | **3594** | `bpc-157` | Multiple in-stock purchasable variations (stock adjusted temporarily — see below) |
| 2 | **3595** | `tb-500` | Mixed stock: one variation in stock, one out of stock (temporary adjustment) |
| 3 | **3700** | `retatrutide` | Third variable product reference (2 variations; used for catalog/threshold context only) |

**URLs used (canonical):**

- `https://www.biopentra.eu/product/bpc-157/`
- `https://www.biopentra.eu/product/tb-500/`
- `https://www.biopentra.eu/product/retatrutide/`

**Attribute for URL tests (custom attribute, not `pa_*`):** `attribute_strength` — values `10mg`, `20mg` (BPC-157 / TB-500).

**Temporary stock (for logic + ATC, then reverted to original out-of-stock state):**

- **3594:** variations `3710` (10mg), `3711` (20mg) — both set in stock with quantity 10; prices 79.99 / 139.98 EUR.
- **3595:** `3712` (10mg) in stock; `3713` (20mg) out of stock.

---

## Results matrix

| # | Scenario | Expected | Actual | Pass? |
|---|----------|----------|--------|-------|
| 1 | Default product page (no `attribute_*`, empty `variation_id`) | Highest **display_price** among variations with `is_in_stock` and `is_purchasable` | **3594:** only `3711` (139.98) qualifies as best per `WC_Product_Variable::get_available_variations()` mirroring the inline JS loop. **3595:** only `3712` (79.99) qualifies. | **Pass** (data + server render with Coming soon off) |
| 2 | URL `attribute_*` present | Inline JS **returns early** — no auto-select | `class-variation-stock-selector-module.php` `INLINE_JS`: `skipUrl` when any `attribute_*` query param is non-empty. | **Pass** (implementation review) |
| 3 | `variation_id` input already set | No override | Inline JS: if parsed `variation_id` > 0, **return** before selection. | **Pass** (implementation review) |
| 4 | Out-of-stock variation | Must not be chosen as “best” | **3595:** `3713` excluded (`is_in_stock` false); best remains `3712`. | **Pass** |
| 5 | Add to cart | Selected variation adds once | `WC()->cart->add_to_cart( 3594, 1, 3711 )` and `add_to_cart( 3595, 1, 3712 )` returned cart line keys; cart emptied after. | **Pass** (server-side; not full browser session) |
| 6 | Network: `cvss-bridge.js` | Loaded **once** from storefront path | With Coming soon **off**, saved HTML contained **one** `src` to `.../biopentra-storefront/assets/variation-stock-selector/cvss-bridge.js?ver=1.0.0`. HEAD `http://127.0.0.1/wp-content/plugins/biopentra-storefront/.../cvss-bridge.js` → **200 OK**. | **Pass** |
| 7 | Inline auto-select | Single inline block after bridge | One `id="biopentra-storefront-custom-variation-stock-selector-js-after"` block (WordPress `wp_add_inline_script` after). | **Pass** |
| 8 | Legacy CVSS inactive | No legacy plugin script URLs | **0** matches for `plugins/custom-variation-stock-selector/` in product HTML. | **Pass** |
| 9 | Console errors | None | **Not executed** in this run (no automated browser session). | **Partial** — manual follow-up recommended |
| 10 | Duplicate add-to-cart | No double-submit from CVSS | Only defensive flag in script is `cvssAutoDone` on `wc_variation_form`; duplicate ATC **not** browser-tested. | **Partial** |

---

## Overall

| Area | Verdict |
|------|---------|
| Selection logic / stock rules | **Pass** (PHP variation data + code review) |
| Asset loading / no legacy handle | **Pass** (HTML grep + 200 on bridge) |
| HPOS declaration / incompatible list | **Pass** (WC HPOS on; no incompatible option data for storefront) |
| Full default template under Coming soon | **N/A / blocked** when `woocommerce_coming_soon=yes` (no variations form) |
| Browser console + interactive ATC | **Partial** |

**Summary pass/fail:** **Conditional pass** — core logic, enqueue path, HPOS signal, and server-side ATC succeed on a normal variable product template. **Gaps:** real browser console/network waterfall and duplicate-click behaviour were not automated; **Coming soon** hides the entire flow on product URLs until the store is launched or the mode is disabled.

## Remaining risks

1. **Coming soon / alternative templates:** If the variable form never renders, CVSS never enqueues. Any future Elementor-only product layout that omits `woocommerce_before_variations_form` has the same effect.
2. **Browser-only behaviour:** `wc_variation_form` timing, theme JS conflicts, and duplicate add-to-cart under rapid clicks were not validated in a live DevTools session.
3. **Above-threshold catalog:** Not exercised here; behaviour should match Woo’s variation form data source (client-side JSON vs AJAX) — monitor first product that exceeds the threshold in production.
4. **URL `variation_id` query string:** The inline script keys off the **form field** `variation_id`, not the query string alone; deep links that only set `?variation_id=` without populating the form may differ from expectation (document if product marketing relies on that).

---

## QA environment toggles (restored)

- `woocommerce_coming_soon`: temporarily `no` for HTML capture → restored to **`yes`**.
- Variation stock on **3594** / **3595**: temporarily adjusted for tests → restored to **out of stock** / zero quantity as before QA.
