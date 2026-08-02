# Milestone B — Commercial Shopping Experience

**Status:** Complete on dev — pending approval  
**Date:** 2026-08-02  
**Version:** `biopentra-storefront` **0.7.0**, `biopentra-loop-card` **1.5.0**

## Summary

Applied commercial-first IA to shop, product search results, and three SEO category resource pages. Products and search controls appear before long-form content; SEO copy remains fully in the DOM below grids.

## Affected pages

| Page | ID | Slug | URL |
|---|---|---|---|
| Shop | 3755 | `shop` | `/shop/` |
| Search results | — | — | `/?s=peptide&post_type=product` (template) |
| GHRP guide | 4430 | `growth-hormone-releasing-peptides` | `/growth-hormone-releasing-peptides/` |
| Metabolic guide | 4431 | `metabolic-research-peptides` | `/metabolic-research-peptides/` |
| Lyophilized guide | 4432 | `lyophilized-research-materials` | `/lyophilized-research-materials/` |

## DOM order changes

### Shop (3755)

| Before | After |
|---|---|
| Hero + trust badges → filter+search row → grid → disclaimer (category text injected **before** grid via PHP) | Compact hero → search → category chips → taxonomy filter → grid → disclaimer → category descriptions **after** grid |

### Search results

| Before | After |
|---|---|
| H1 → Blocksy product grid (no on-page search) | H1 → refinement search (`#biopentra-search-refine`) → Blocksy grid (cards unchanged — Milestone C) |

### SEO category pages (4430–4432)

| Before | After |
|---|---|
| Hero (H1+intro) → long editorial body (0 products) | Hero → search → chips → product grid (3608) → WC archive link → editorial body (unchanged content) |

## Widget / selector reference

| Surface | Key IDs / selectors |
|---|---|
| Shop loop grid | `#ed52b7f` / `.biopentra-loop-card-root` |
| Shop taxonomy filter | `#b3a2918` |
| Shop search | `#biopentra-shop-s` |
| Category chips | `.bp-home-cat-chip` |
| Category descriptions (PHP) | `#mp-category-description` (after grid) |
| SEO grids | `#b2grid4430`, `#b2grid4431`, `#b2grid4432` |
| Search refine | `#biopentra-search-refine` |

## Files changed

| Repo | Path |
|---|---|
| biopentra-storefront | `scripts/setup-shop-page-v2-cli.php` |
| biopentra-storefront | `scripts/setup-seo-category-v2-cli.php` |
| biopentra-storefront | `includes/shopping-v2-helpers.php`, `includes/shopping-v2-assets.php` |
| biopentra-storefront | `assets/css/shop-v2.css`, `search-v2.css`, `seo-category-v2.css` |
| biopentra-loop-card | `includes/shop-category-description.php` (inject after loop) |
| biopentra-loop-card | `biopentra-loop-card.php` (live search on search results) |
| storefront-acceptance | `tests/shop-ia.spec.ts`, `tools/run-dev.sh --milestone-b` |

## Backups

| Artifact | Path |
|---|---|
| Shop pre-B | [backups/shop-pre-B.json](backups/shop-pre-B.json) |
| SEO 4430 pre-B | [backups/seo-cat-4430-pre-B.json](backups/seo-cat-4430-pre-B.json) |
| SEO 4431 pre-B | [backups/seo-cat-4431-pre-B.json](backups/seo-cat-4431-pre-B.json) |
| SEO 4432 pre-B | [backups/seo-cat-4432-pre-B.json](backups/seo-cat-4432-pre-B.json) |

## WP-CLI replay (dev)

```bash
cd /opt/biopentra/apps/wordpress
docker compose run --rm -T wpcli wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-shop-page-v2-cli.php
docker compose run --rm -T wpcli wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-seo-category-v2-cli.php
docker compose run --rm -T wpcli wp elementor flush-css
docker compose run --rm -T wpcli wp cache flush
```

## Temporary renderer note (search)

Search results continue to use **Blocksy native** `ul.products li.product` cards. Canonical Elementor loop template **3608** migration is **Milestone C** scope.

## Commit hashes

| Repo | Commit | Branch |
|---|---|---|
| `biopentra-custom-plugins` | `64e449d` | shop v2 CLI + styles |
| `biopentra-custom-plugins` | `2a6e136` | search + SEO category CLIs |
| `biopentra-custom-plugins` | `df1b4b2` | docs + screenshots |
| `storefront-acceptance` | `fb80b84` | shop-ia + baselines |
| `biopentra-loop-card` | `c0cac58` | category copy + live search |

## Production replay

See [../deployment/milestone-B-commercial-shopping.md](../deployment/milestone-B-commercial-shopping.md)

## Rollback

See change record rollback section and per-page backup JSON files.
