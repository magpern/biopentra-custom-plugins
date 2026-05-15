# Phase 5 — `biopentra-loop-card` pre-migration audit

**Status:** Audit complete; **no code migration** performed.  
**Plugin version audited:** Header `1.0.0` / constant `BIOPENTRA_LOOP_CARD_VER` **`1.2.4`** (mismatch — see §2).  
**Storefront:** Phases 1–4 consolidated; loop-card remains **explicitly separate** until a future decision.

This document inventories the legacy plugin, lists hooks and risks, and recommends **merge vs keep standalone** before any implementation work.

---

## 1. Repository layout inspected

Root: `plugins/biopentra-loop-card/`

| Path | Role |
|------|------|
| `biopentra-loop-card.php` | Bootstrap, price/Elementor hooks, loop payload, enqueue, shop search, `pre_get_posts` shop fix, Elementor template DB upgrade on `init` |
| `includes/age-gate-confirm-fix.php` | Age Gate JS inline + failed redirect filter |
| `includes/store-notice.php` | WooCommerce demo store notice markup + CSS |
| `includes/setup-shop-page-cli.php` | **CLI/ops only** — creates/patches Shop Elementor page, menu, WC `shop_page_id` (not loaded by main plugin) |
| `assets/loop-card.css` | Loop card + overlay styles |
| `assets/loop-card.js` | Overlay UI, variation pick, WC AJAX `add_to_cart` via `fetch` |
| `assets/shop-live-search.js` | REST live suggestions on shop page |
| `assets/store-notice.css` | Demo store notice layout |
| `README.md` | Install notes, template ID **3608** |

---

## 2. Complete PHP file list

1. `biopentra-loop-card.php` (~524 lines)  
2. `includes/age-gate-confirm-fix.php`  
3. `includes/store-notice.php`  
4. `includes/setup-shop-page-cli.php` (run via `./wp eval-file`, **not** `require`’d at runtime)

---

## 3. Complete asset list

| Asset | Handle | Enqueued when |
|-------|--------|----------------|
| `assets/loop-card.css` | `biopentra-loop-card` (style) | All front-end WC pages (`wp_enqueue_scripts` @20) |
| `assets/loop-card.js` | `biopentra-loop-card` (script) | Same; deps `jquery`, `wc-cart-fragments` |
| `assets/shop-live-search.js` | `biopentra-shop-live-search` | Shop page only (`is_page( woocommerce_shop_page_id )`) |
| `assets/store-notice.css` | `biopentra-store-notice` | When `is_store_notice_showing()` (@30) |

**Localized objects:** `biopentraLoopCard` (WC AJAX URL + i18n), `biopentraShopSearch` (REST search URL + i18n).

---

## 4. Constants, options, filters

| Symbol | Value / purpose |
|--------|------------------|
| `BIOPENTRA_LOOP_CARD_URL` | `plugin_dir_url( __FILE__ )` |
| `BIOPENTRA_LOOP_CARD_VER` | **`1.2.4`** (asset `ver=`; header still says `1.0.0`) |
| Option `biopentra_loop_card_tpl_v1` | One-time Elementor template upgrade flag |
| Option `biopentra_loop_card_tpl_v2` | Second template upgrade pass |
| Filter `biopentra_loop_card_template_post_id` | Default Elementor library post **3608** |
| `$GLOBALS['biopentra_loop_card_price_scope']` | Scoped price HTML override |

**No shortcodes.** **No** custom post types. **No** HPOS declaration.

---

## 5. Hooks and priorities (preserve if migrated)

| Hook | Type | Priority | Callback / file | Purpose |
|------|------|----------|-----------------|---------|
| `elementor/widget/before_render_content` | action | **10** | `biopentra_loop_card_before_price_widget` | Enable scoped price formatting |
| `woocommerce_get_price_html` | filter | **50** | `biopentra_loop_card_filter_loop_price_html` | “from X” variable prices in loop |
| `elementor/widget/render_content` | filter | **999** | `biopentra_loop_card_after_price_widget_content` | Clear price scope global |
| `elementor/document/wrapper_attributes` | filter | **20** | `biopentra_loop_card_document_wrapper_attributes` | `data-biopentra-product` JSON on loop item |
| `wp_enqueue_scripts` | action | **20** | `biopentra_loop_card_enqueue_assets` | Register/enqueue loop + conditional live search |
| `elementor/query/query_args` | filter | **25** | `biopentra_loop_card_shop_page_search_in_loop_query` | Merge `?s=` into loop-grid on shop page |
| `pre_get_posts` | action | **9999** | `biopentra_loop_card_force_singular_page_for_elementor_shop` | Force shop URL to Elementor **page** query, not WC archive |
| `init` | action | **30** | `biopentra_loop_card_maybe_upgrade_elementor_template` | DB writes to Elementor library template |
| `woocommerce_demo_store` | filter | **10** | `biopentra_store_notice_markup` | Custom notice HTML (no dismiss) |
| `wp_enqueue_scripts` | action | **30** | `biopentra_store_notice_enqueue_assets` | Store notice CSS |
| `wp_print_footer_scripts` | action | **1** | closure in `age-gate-confirm-fix.php` | Inline Age Gate submit fix |
| `age_gate/failed/redirect` | filter | **10** | closure | Limit failure redirect to confirm `0` |

---

## 6. Behaviour summary

### Elementor / loop grid

- Targets **Elementor Pro** `Loop` document (`\ElementorPro\Modules\LoopBuilder\Documents\Loop`).
- Injects `biopentra-loop-card-root` + JSON payload for JS overlay (variations, stock, permalink).
- **Runtime DB migration** rewrites Elementor library template (default post **3608**) on first loads via options `biopentra_loop_card_tpl_v*`.

### WooCommerce price HTML

- Global filter on `woocommerce_get_price_html` gated by `$GLOBALS['biopentra_loop_card_price_scope']` set only around `woocommerce-product-price` widget outside single product pages.

### Query / archive

- **`pre_get_posts` @9999** rewrites main query on shop permalink when page is Elementor-built — prevents WC `archive-product` from bypassing Elementor `the_content()`.
- **`elementor/query/query_args` @25** passes shop search `?s=` into loop-grid queries.

### AJAX / cart

- `loop-card.js` POSTs to `WC_AJAX::get_endpoint( 'add_to_cart' )` with `variation_id` when applicable; triggers `added_to_cart` + fragments via jQuery for cart widgets.

### Shop live search

- HTML form from `biopentra_loop_card_get_shop_search_form_html()` (intended for Elementor HTML widget on shop page).
- `shop-live-search.js` calls `wp/v2/search?subtype=product` for suggestions; Enter submits `?s=` to filter grid.

### Age Gate

- Separate from loop UI: fixes duplicate `age_gate[confirm]` FormData issue and tightens failed redirect.

### Store notice

- Themed WooCommerce demo store banner; separate CSS handle.

### `setup-shop-page-cli.php`

- **One-shot provisioning:** clones SiteHome Elementor JSON → Shop page, sets loop-grid/taxonomy-filter/search HTML, updates `woocommerce_shop_page_id`, menu, home CTA. **Site-specific element IDs** (`ed52b7f`, `c4b3a91`, etc.).

---

## 7. Dependencies

| Dependency | Required? |
|------------|-----------|
| **WooCommerce** | Yes |
| **Elementor Pro** | Yes (Loop Grid, loop template, taxonomy-filter on shop) |
| **jQuery** | Yes (`wc-cart-fragments`, `added_to_cart` trigger) |
| **Age Gate** plugin | Optional (age-gate-confirm-fix no-ops if script not enqueued) |
| **Blocksy** | No direct hooks (header-auth Blocksy filter is separate plugin) |
| **WordPress REST** | Shop live search (`/wp/v2/search`) |

---

## 8. Risk register

| Risk | Severity | Notes |
|------|----------|--------|
| **`pre_get_posts` @9999** | **Critical** | Global main-query rewrite; wrong conditions break shop, SEO, or other archives. |
| **Elementor template auto-upgrade on `init`** | **High** | Writes `_elementor_data` for hardcoded post **3608**; dangerous on wrong site or after manual template edits. |
| **`woocommerce_get_price_html` filter** | **High** | Global filter; scope global must remain tight or single-product / email prices skew. |
| **Loop payload / `get_available_variations()`** | **Med–High** | Heavy per card; performance on large grids; threshold/AJAX behaviour must match product catalog. |
| **AJAX add-to-cart from grid** | **High** | Regression affects revenue; must work with Blocksy/header-auth `ct-ajax-add-to-cart` on single pages separately. |
| **Shop search + `elementor/query/query_args`** | **Med** | Only loop-grid on shop page; other Elementor queries unaffected if guard holds. |
| **Age Gate coupling** | **Low–Med** | Unrelated to shop cards; bundling increases test matrix. |
| **Store notice coupling** | **Low** | Cosmetic; could stay standalone or move to theme. |
| **Version header vs constant** | **Low** | Confusing deploys / cache busting. |
| **Cache/CDN** | **Med** | Global enqueue of loop-card CSS/JS on all WC front pages; purge after URL change if merged into storefront. |
| **Duplicate plugin + storefront** | **High** | Double `pre_get_posts`, double price filter, double template upgrade if both active without guard. |

---

## 9. Exact files to migrate (if merge approved later)

**Runtime (candidate module `modules/loop-card/`):**

- `biopentra-loop-card.php` → split into `class-loop-card-module.php` + thin includes  
- `includes/age-gate-confirm-fix.php` *(optional submodule or separate tiny plugin)*  
- `includes/store-notice.php` *(optional submodule)*  
- `assets/loop-card.css`, `loop-card.js`, `shop-live-search.js`, `store-notice.css`

**Do NOT migrate into storefront runtime:**

- `includes/setup-shop-page-cli.php` — keep as **CLI script** under `scripts/` or plugin `includes/` loaded only by `eval-file`; never on `plugins_loaded`.  
- Hardcoded Elementor JSON surgery tied to production post IDs — replace with documented WP-CLI runbook, not automatic `init` upgrades on every environment.

---

## 10. Module structure recommendation (if merge)

```
modules/loop-card/
  class-loop-card-module.php      # guarded init(); legacy is_plugin_active check
  includes/loop-card-hooks.php  # or split by concern
  includes/age-gate-confirm.php   # optional: separate module flag default-off
  includes/store-notice.php       # optional: separate module flag
  assets/...
  README.md
```

- **Guard:** `is_plugin_active( 'biopentra-loop-card/biopentra-loop-card.php' )` → early return (same pattern as Phases 3–4).  
- **Sub-modules:** Consider **three** logical units inside one guarded module: (1) loop grid + shop query, (2) store notice, (3) age gate — so ops can reason about blast radius.  
- **Remove or gate** `biopentra_loop_card_maybe_upgrade_elementor_template` on `init` for non-dev environments; use explicit CLI version bumps instead.

---

## 11. Staging test matrix

| # | Area | Steps |
|---|------|--------|
| 1 | Guard | Legacy active → storefront loop module must not register hooks |
| 2 | Shop page render | Elementor shop loads (not raw WC archive); loop grid shows products |
| 3 | Loop card UI | Hover overlay (desktop), tap (mobile), stock banners |
| 4 | Variable ATC | Pick strength → AJAX add to cart; cart fragment updates |
| 5 | Simple ATC | Single variation / simple product from grid |
| 6 | OOS | Overlay hidden / disabled pills when no stock |
| 7 | Price display | “from X” on multi-price variables; single price when one variation |
| 8 | Shop search | `?s=` filters grid; live REST suggestions |
| 9 | Single product | Price on PDP unchanged (scope global off) |
| 10 | Age Gate | Yes/No submit; under-18 redirect only when confirm=0 |
| 11 | Store notice | Notice visible when WC demo store enabled; no dismiss link |
| 12 | Elementor editor | Shop + loop template preview; template ID filter documented |
| 13 | Network | Handles load once; correct `ver=`; no 404 |
| 14 | Console | No errors on shop interaction |
| 15 | Rollback | Reactivate standalone plugin; deactivate storefront loop module |

---

## 12. Rollback plan

1. **Reactivate** `biopentra-loop-card` (folder on disk).  
2. **Deactivate** storefront loop module or entire `biopentra-storefront` only if required for other phases.  
3. **DB:** Roll back `_elementor_data` only if template upgrade or `setup-shop-page-cli` was re-run and broke layout (restore pre-migration dump).  
4. **Flush** page cache, Elementor CSS cache, CDN.  
5. **Options:** `biopentra_loop_card_tpl_v1/v2` may prevent re-upgrade — document if intentional.

---

## 13. Recommendation: merge or keep separate?

### **Recommendation: keep `biopentra-loop-card` standalone** (do not merge into `biopentra-storefront` in Phase 5)

**Rationale:**

1. **Blast radius** — `pre_get_posts` @9999 and global price filters are among the highest-risk hooks in the stack; Phases 1–4 were intentionally smaller surface area.  
2. **Elementor Pro coupling** — Loop Grid, loop document attributes, query_args, and **DB template migrations** are shop-specific, not general “storefront chrome.”  
3. **Operational scripts** — `setup-shop-page-cli.php` is environment provisioning, not runtime plugin behaviour.  
4. **Orthogonal features** — Age Gate fix and store notice are not loop-card core; merging bloats storefront and mixes release cadences.  
5. **Existing policy** — Checklist already lists loop-card as **out of scope** for Phases 1–4; production cutover 0.4.0 did not depend on it.  
6. **Release independence** — Shop UX (overlay, search, grid) can ship fixes without redeploying megamenu/header-auth/CVSS.

### When a merge might make sense (later)

- Team wants **one** deployable ZIP and accepts a **multi-flag** storefront module with strict guards.  
- Site-specific `init` template upgrades are **removed** or moved to CLI.  
- Full staging matrix (§11) passes with storefront-only loop module for **≥14 days**.

### Safest next step

1. **Do not migrate code.**  
2. **Harden standalone plugin:** align header `Version` with `BIOPENTRA_LOOP_CARD_VER`; document template post ID filter; consider disabling auto-upgrade on production via option or filter.  
3. **Staging QA** using §11 on current standalone plugin after any shop changes.  
4. **Revisit merge decision** only after storefront 0.4.x soak completes and stakeholders want fewer plugins.

---

*End of audit — implementation intentionally deferred.*
