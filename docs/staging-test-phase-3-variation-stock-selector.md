# Staging / local test plan — Phase 3 variation stock selector (CVSS)

**Scope:** `Biopentra_Storefront_Variation_Stock_Selector_Module` vs legacy `custom-variation-stock-selector`. **Do not** start Phase 4 from this doc.

---

## Preconditions

- WooCommerce active; **HPOS** mode matches your production intent (compatibility declared from storefront when legacy is off).
- **`biopentra-storefront` active**, **`custom-variation-stock-selector` inactive** for cutover tests.
- Phases 1–2 may remain active in storefront; do **not** use this doc to change loop-card, inbox, inventory, or header-auth.

---

## Test matrix

| # | Scenario | Steps | Expected |
|---|----------|--------|----------|
| 1 | **Under AJAX threshold** | Variable product with ≤ `woocommerce_ajax_variation_threshold` variations, all combinations in embedded JSON. Open single product, no `attribute_*` query args, no preselected variation. | After short delay, attributes select so the **highest display_price** among **in-stock** and **purchasable** variations is chosen; price updates; add to cart works. |
| 2 | **URL attribute override** | Append `?attribute_pa_*=` (any `attribute_*` with value) matching a valid combination. | Script **returns early** — **no** auto override (legacy behavior). |
| 3 | **Already selected** | Preload `variation_id` input with a valid variation before `wc_variation_form` runs (or deep-link with variation already resolved). | Script **returns early** — no second auto-selection. |
| 4 | **Above threshold** | Product with **more** variations than threshold so WC omits embedded JSON. | **No-op** — no errors; WC’s own AJAX variation flow applies. |
| 5 | **Out-of-stock / unpurchasable** | Variations exist but highest price line is OOS; lower in-stock exists. | Highest **in-stock + purchasable** price wins (same loop as legacy). |
| 6 | **Console** | DevTools open on product page. | **No** new errors from `cvss-bridge.js` / inline block. |
| 7 | **Network** | Filter `cvss-bridge` / `biopentra-storefront-custom-variation-stock-selector`. | **200** on bridge JS; inline appended; **no 404** empty script URL. |
| 8 | **Duplicate plugins** | Briefly activate **both** legacy + storefront (not production). | Prefer **avoid** — if tested, expect legacy-only hooks if `is_plugin_active` detects legacy; if load order differs, watch for **double** auto-select and remove immediately. |

---

## HPOS note

Declaration runs on `before_woocommerce_init` from storefront when legacy is inactive. Confirm WooCommerce **Settings → Advanced → Features** shows no new compatibility warnings after cutover.

---

## Rollback

1. **Activate** `custom-variation-stock-selector`.  
2. **Deactivate** `biopentra-storefront` **only** if safe for other migrated features — on staging, toggle as needed.  
3. Clear caches; re-run scenario #1 against legacy to confirm parity.

---

## Pass criteria

- Parity with legacy for scenarios **1–5**.  
- No PHP fatals with `WP_DEBUG` on staging.  
- No duplicate auto-selection in normal (single-plugin) operation.
