# Variation stock selector (CVSS) migration notes

**Source:** `plugins/custom-variation-stock-selector/custom-variation-stock-selector.php`  
**Target:** `plugins/biopentra-storefront/modules/variation-stock-selector/class-variation-stock-selector-module.php`  
**Bridge asset:** `plugins/biopentra-storefront/assets/variation-stock-selector/cvss-bridge.js` (minimal file so `wp_register_script` has a real `src`; inline logic unchanged).

---

## Preserved behavior

| Item | Detail |
|------|--------|
| **HPOS** | `before_woocommerce_init` → `FeaturesUtil::declare_compatibility( 'custom_order_tables', …, true )` — second argument is now **`BIOPENTRA_STOREFRONT_FILE`** (storefront main plugin) instead of the legacy plugin `__FILE__`. |
| **Hook** | `woocommerce_before_variations_form` → enqueue + inline (same priority as legacy: default **10**). |
| **Inline JS** | Byte-for-byte same heredoc as legacy (jQuery handler on `wc_variation_form`, 150ms timeout, URL `attribute_*` skip, existing `variation_id` skip, highest `display_price` among in-stock purchasable, select sync, triggers). |
| **Script deps** | `jquery`, `wc-add-to-cart-variation` (same order as legacy `wp_register_script` deps). |
| **Version** | Script version string **`1.0.0`** (unchanged). |
| **Handle** | New handle **`biopentra-storefront-custom-variation-stock-selector`** to avoid colliding with legacy handle `custom-variation-stock-selector`. |

---

## Enqueue implementation change (non-runtime)

Legacy used `wp_register_script( 'custom-variation-stock-selector', '', … )` and `wp_add_inline_script` on that empty handle. Storefront registers **`biopentra-storefront-custom-variation-stock-selector`** with a real URL to `cvss-bridge.js`, then attaches the **same** inline block **after**. Runtime in the browser is unchanged once scripts load.

---

## WooCommerce variation threshold

If the product has **more** variations than `woocommerce_ajax_variation_threshold` (default **30**), WooCommerce does **not** embed `data-product_variations` / `product_variations` JSON. The handler exits early — **no-op** (same as legacy).

---

## Duplicate plugin risk

If **`custom-variation-stock-selector`** remains **active**, the module **returns early** from `init()` (checks `is_plugin_active( 'custom-variation-stock-selector/custom-variation-stock-selector.php' )`). Legacy keeps HPOS declare + enqueue. **Do not** run both long term; pick one after staging QA.

---

## Rollback

1. **Deactivate** `biopentra-storefront` (if acceptable for your site) **or** add a temporary guard — operational rollback is: **reactivate** `custom-variation-stock-selector` and **deactivate** `biopentra-storefront` only if no other storefront phases are required live.  
2. Prefer: **reactivate legacy CVSS**, **deactivate storefront** only when storefront is not yet depended on for Phases 1–2 — in practice use **staging** to validate before production.  
3. Simpler rollback for CVSS-only: activate legacy plugin; module auto-bails when legacy is active.

---

## Related

- Staging checklist: `docs/staging-test-phase-3-variation-stock-selector.md`  
- Storefront roadmap: `docs/storefront-migration-checklist.md`
