# Milestone C — Canonical Product Components

**Status:** COMPLETE — tagged `biopentra-loop-card` `v1.6.0`  
**Date:** 2026-08-03  
**Version:** `biopentra-loop-card` **1.6.0**  
**Release commit:** `5260a84`  
**Feature commit:** `33a6c54`  
**Artifact:** [biopentra-loop-card-1.6.0.zip](https://github.com/magpern/biopentra-loop-card/releases/download/v1.6.0/biopentra-loop-card-1.6.0.zip)  
**Release URL:** https://github.com/magpern/biopentra-loop-card/releases/tag/v1.6.0

**Storefront:** No bump — `biopentra-storefront` remains **0.7.0** / `storefront-v0.7.0` (Milestone B). Milestone C shipped only in `biopentra-loop-card`.

## Summary

One canonical product-card renderer (Elementor loop template **3608** + `biopentra-loop-card` JS/CSS) with multiple thin adapters. WooCommerce archives, product search, related/upsell loops, and programmatic sidebar grids all call the same `biopentra_loop_card_render_product_card()` entry point.

Architecture reference: [canonical-product-card-architecture.md](../design-system/canonical-product-card-architecture.md)

## Renderer + adapters

| Layer | Location | Role |
|---|---|---|
| Renderer | `includes/class-product-card-renderer.php` | Template 3608, CSS, fallback, context metadata |
| WC adapter | `includes/adapters/wc-content-product-adapter.php` | `wc_get_template_part` → `content-product.php` |
| Programmatic adapter | `includes/adapters/programmatic-adapter.php` | `biopentra_loop_card_render_product_cards()` |
| Elementor (native) | Loop Grid widget | Already uses template 3608 — no PHP adapter |

## Surfaces migrated

| Surface | Entry | Context | Status |
|---|---|---|---|
| Home grids | Elementor Loop Grid | `homepage` (native) | Verified |
| Shop grid | Elementor Loop Grid | `shop` (native) | Verified |
| SEO category pages | Elementor Loop Grid | `seo_page` (native) | Verified |
| WC archives | `content-product` adapter | `archive` | Migrated |
| Product search | `content-product` adapter | `search` | Migrated |
| PDP related | `content-product` adapter | `related` | Migrated |
| PDP upsells | `content-product` adapter | `upsell` | Migrated |
| Sidebar related research | Programmatic adapter | `related` / `shortcode` | Migrated |

## Legacy renderers (out of scope)

| Surface | Current renderer | Notes |
|---|---|---|
| WC sidebar widgets | `content-widget-product.php` | Blocksy/WC mini cards |
| Cart cross-sells | WC default | Adapter ready; no fixture products configured |

## Release record

| Field | Value |
|---|---|
| Tag | `v1.6.0` |
| Tagged commit | `5260a84b588047e4030cf47ae2677cbc89f88594` |
| Release workflow | success — https://github.com/magpern/biopentra-loop-card/actions/runs/30843253401 |
| ZIP | `biopentra-loop-card-1.6.0.zip` |
| Published | 2026-08-03 |
| Validation | `--milestone-c` PASS (230 expected / 0 unexpected) |

## Related commits (supporting repos)

| Repo | Commit | Notes |
|---|---|---|
| `biopentra-custom-plugins` | `3feffa1` | Architecture + Milestone C docs |
| `storefront-acceptance` | `f4f5a94` | `--milestone-c` suite |

## WP-CLI replay (dev)

No Elementor CLI required — plugin deploy only:

```bash
cd /opt/biopentra/apps/wordpress
docker compose run --rm -T wpcli wp plugin list --name=biopentra-loop-card
docker compose run --rm -T wpcli wp elementor flush-css
docker compose run --rm -T wpcli wp cache flush
```

## Rollback

1. Deactivate or revert `biopentra-loop-card` to **1.5.0** release ZIP.
2. `wp elementor flush-css && wp cache flush`
3. WC archives revert to Blocksy native cards automatically (adapter removed).

## Known constraints

- Blocksy hides PDP related/upsell sections on mobile/tablet (`ct-hidden-sm/md`) — acceptance tests use `desktop-1440` for those surfaces.
- Sidebar `bp-related-products` widget replaces demo block `block-16` only when present on the PDP sidebar.
- **Production replay:** deferred until Milestone D SEO grid metadata migration is complete.
