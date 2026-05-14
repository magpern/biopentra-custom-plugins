# Module: variation-stock-selector (CVSS)

**Migrated from:** `plugins/custom-variation-stock-selector/` (legacy retained; **deactivate** legacy when storefront owns this behavior).

## Behavior

- **`before_woocommerce_init`:** Declares HPOS / custom order tables compatibility via `FeaturesUtil::declare_compatibility( 'custom_order_tables', … )` using the **storefront** main plugin file path (same semantics as legacy using its `__FILE__`).
- **`woocommerce_before_variations_form`:** Enqueues a small bridge script (`assets/variation-stock-selector/cvss-bridge.js`) depending on `jquery` + `wc-add-to-cart-variation`, then appends the **same** inline jQuery as legacy via `wp_add_inline_script` (no runtime logic change).

## Duplicate plugin guard

If **`custom-variation-stock-selector`** is still **active**, this module **does not register** any hooks (avoids double HPOS declare and double auto-selection).

## WooCommerce threshold

When variation count exceeds `woocommerce_ajax_variation_threshold`, WooCommerce omits embedded `product_variations` data — the inline script no-ops (same as legacy).
