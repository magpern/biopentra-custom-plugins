# Canonical Product Card Architecture

**Status:** Milestone C — permanent reference  
**Version:** Renderer `1.0.0` · Plugin `biopentra-loop-card` 1.6.0+  
**Canonical visual:** Elementor Loop Template **3608** + `biopentra-loop-card` JS/CSS + Storefront Design System (SDS)

## Objective

There is **one** product-card renderer and **multiple rendering adapters**. Adapters translate each platform’s lifecycle into a single `render_product_card()` call. There must never be multiple independent card HTML implementations.

## Diagram

```
                    ┌─────────────────────────────────────┐
                    │   Biopentra_Product_Card_Renderer   │
                    │   render_product_card( $product )   │
                    └─────────────────┬───────────────────┘
                                      │
              Elementor Loop Template 3608 (print_content)
                                      │
                         loop-card.js / loop-card.css
                                      │
                         Storefront Design System tokens
                                      │
        ┌─────────────┬───────────────┼───────────────┬─────────────┐
        │             │               │               │             │
   Elementor      WooCommerce    Programmatic      Future
   Loop Grid      content-product   PHP grids      integrations
   (native)       adapter           adapter
```

## Renderer

**Location:** `biopentra-loop-card/includes/class-product-card-renderer.php`

**Public entry points:**

| Function | Purpose |
|---|---|
| `biopentra_loop_card_render_product_card( $product, $args )` | Single card HTML |
| `biopentra_loop_card_render_product_cards( $ids, $args )` | Ordered grid/list of cards |

**Renderer responsibilities (and only these):**

| Concern | Detail |
|---|---|
| Template location | Default post ID **3608**; override via `biopentra_loop_card_template_post_id` |
| Template rendering | Elementor Loop document `print_content()` with correct post/globals |
| Wrapper attributes | `e-loop-item`, `biopentra-canonical-loop-item`, optional `data-biopentra-render-context` |
| CSS | Enqueue loop template CSS + per-item dynamic CSS once per request |
| Fallback | Minimal SDS-aligned markup when Elementor/3608 unavailable |
| Compatibility | Checks for Elementor Pro Loop Builder classes |
| Error handling | Empty string for invisible products; fallback on missing post/document |
| Versioning | `Biopentra_Loop_Card_Product_Card_Renderer::VERSION` for diagnostics |

The renderer **does not** know which adapter invoked it. It accepts an optional **render context** string for minor presentation tweaks (layout class, wrapper metadata), not separate card implementations.

### Render contexts

| Context | Typical source |
|---|---|
| `homepage` | Elementor home grids (native; no PHP adapter) |
| `shop` | Elementor shop loop (native) |
| `seo_page` | Elementor SEO category grids (native) |
| `archive` | WC taxonomy archives via content-product adapter |
| `search` | WC product search via content-product adapter |
| `related` | WC related loop or programmatic sidebar |
| `upsell` | WC up-sells loop |
| `cross_sell` | WC cross-sells loop |
| `shortcode` | `[biopentra_related_research_products]` |
| `future` | Reserved for new integrations |

Contexts affect **layout hints** (e.g. `compact-list` for sidebar) and wrapper metadata only — never duplicate HTML.

## Adapters

Adapters prepare the environment and call the renderer. They contain **minimal logic**.

### 1. Elementor Loop Grid (native)

**Entry:** Elementor Pro Loop Grid widget → template 3608  
**Adapter role:** None in PHP — Elementor renders the loop document directly.  
**Verification:** Home, shop, SEO pages already use 3608; Milestone C confirms parity via acceptance tests.

### 2. WooCommerce `content-product` adapter

**Location:** `includes/adapters/wc-content-product-adapter.php`  
**Entry:** `wc_get_template_part( 'content', 'product' )` → plugin `templates/woocommerce/content-product.php`

**Integration point:** `wc_get_template_part` filter (primary). `wc_get_template` filter (secondary for direct template loads). **Not** `woocommerce_locate_template`.

**Adapter duties:**

1. Detect canonical WC loop contexts (archives, product search, related, upsells, cross-sells — **not** the Elementor shop page).
2. Swap in plugin `content-product.php`.
3. Remove default WC loop item markup hooks (thumbnail, title, price, add-to-cart) so Blocksy/WC native cards do not duplicate.
4. Resolve render context from WC loop state and call `biopentra_loop_card_render_product_card()`.
5. Mark `ul.products` with `biopentra-canonical-wc-loop` for SDS layout integration.
6. Preserve outer loop structure, pagination, sorting, result count, filters, breadcrumbs, and structured data hooks **outside** the card.

### 3. Programmatic adapter

**Location:** `includes/adapters/programmatic-adapter.php`  
**Entry:** `biopentra_loop_card_render_product_cards( $ids, $args )`

Used by sidebar related research products, shortcodes, and future custom grids. Wraps IDs in a SDS grid container and calls the renderer per product. **No custom card HTML.**

## Rendering flow

```
Adapter invoked
    │
    ├─ Resolve WC_Product + render context
    │
    └─ biopentra_loop_card_render_product_card( $product, [
           'context' => 'archive',
           'layout'  => 'grid',          // optional override
           'wrapper_class' => '',        // optional
       ] )
           │
           ├─ Elementor available? ──no──► fallback card (SDS tokens)
           │
           └─ yes: enqueue CSS → setup post → print 3608 → loop-card enhance()
```

After HTML is in the DOM, `loop-card.js` enhances all `.biopentra-loop-card-root` nodes (stretched link, overlay, payload JSON) regardless of adapter.

## Extension points

| Hook / filter | Use |
|---|---|
| `biopentra_loop_card_template_post_id` | Alternate loop template post ID |
| `biopentra_loop_card_is_wc_canonical_loop_context` | Extend WC adapter activation |
| `biopentra_loop_card_is_product_search` | Treat ambiguous search as product search |
| `biopentra_loop_card_render_context` | Adjust context string before render |
| `biopentra_loop_card_render_args` | Adjust renderer args per product |

New surfaces **must** add an adapter (or use programmatic adapter) — never inline card markup.

## Fallback behaviour

When Elementor Pro Loop Builder or template 3608 is unavailable:

1. Renderer outputs `biopentra-loop-card-root--fallback` with title link + price from existing loop-card helpers.
2. `loop-card.js` still enhances if payload is present.
3. Admin/cron diagnostics can read `Biopentra_Loop_Card_Product_Card_Renderer::VERSION`.

Fallback is **temporary degradation**, not a second design system.

## Design system

All paths inherit SDS spacing tokens (`--bp-space-*`), touch targets (`--bp-touch-min`), and shared `loop-card.css`. Adapter-specific CSS (`canonical-wc-loop.css`, `related-research-products.css`) handles **layout integration only** — no forked card component styling.

## Legacy surfaces (Milestone C scope)

| Surface | Milestone C status |
|---|---|
| Home / shop / SEO Elementor grids | Verified (3608 native) |
| WC archives | Migrated via content-product adapter |
| Product search | Migrated via content-product adapter |
| PDP related / upsells | Migrated via content-product adapter |
| Sidebar related research | Migrated via programmatic adapter |
| WC sidebar widgets (`content-widget-product.php`) | **Legacy** — out of scope unless explicitly requested |
| Cross-sells on cart | **Legacy** if no products configured — adapter ready when data exists |

## Related files

| Path | Role |
|---|---|
| `includes/class-product-card-renderer.php` | Renderer service |
| `includes/adapters/wc-content-product-adapter.php` | WooCommerce adapter |
| `includes/adapters/programmatic-adapter.php` | Programmatic adapter |
| `templates/woocommerce/content-product.php` | WC loop item shell |
| `assets/loop-card.js`, `assets/loop-card.css` | Shared interaction + SDS card chrome |
| `assets/canonical-wc-loop.css` | WC loop grid integration |
| `storefront-acceptance/tests/canonical-cards.spec.ts` | Milestone C acceptance |
