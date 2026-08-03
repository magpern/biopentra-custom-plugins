# Milestone C — Deployment

**Status:** COMPLETE (dev baseline frozen)  
**Target:** dev.biopentra.eu only (no production replay)  
**Plugin:** `biopentra-loop-card` **1.6.0**  
**Tag:** `v1.6.0` → commit `5260a84`  
**Artifact:** `biopentra-loop-card-1.6.0.zip`  
**Release:** https://github.com/magpern/biopentra-loop-card/releases/tag/v1.6.0  
**Date:** 2026-08-03

## Pre-deploy checklist

- [x] Architecture doc merged: `design-system/canonical-product-card-architecture.md`
- [x] Plugin files synced to `wp-content/plugins/biopentra-loop-card` (compose mount)
- [x] Tag `v1.6.0` pushed; GitHub Release workflow success
- [x] Release ZIP published: `biopentra-loop-card-1.6.0.zip`
- [x] Acceptance `--milestone-c` green (230 passed)

## Deploy steps (dev)

Dev already mounts the git checkout. After release:

```bash
cd /opt/biopentra/apps/wordpress
docker compose run --rm -T wpcli wp elementor flush-css
docker compose run --rm -T wpcli wp cache flush
curl -sI https://dev.biopentra.eu/product-category/research-peptides/ | head -5
```

Production install (when approved later): upload `biopentra-loop-card-1.6.0.zip` from the GitHub Release.

## Smoke checks

| URL | Expect |
|---|---|
| `/shop/` | `.biopentra-loop-card-root` in `#ed52b7f` grid |
| `/product-category/research-peptides/` | `ul.products.biopentra-canonical-wc-loop`, 0× Blocksy `ct-media-container` in grid |
| `/?s=peptide&post_type=product` | Canonical WC loop + `#biopentra-search-refine` |
| `/product/tirzepatide/` (desktop) | `section.related.products` with `.biopentra-loop-card-root` |

## Rollback

```bash
# Install biopentra-loop-card v1.5.0 release ZIP
docker compose run --rm -T wpcli wp plugin activate biopentra-loop-card
docker compose run --rm -T wpcli wp elementor flush-css
docker compose run --rm -T wpcli wp cache flush
```

WC archives immediately revert to Blocksy cards when the adapter is absent.

## Production

**Do not replay Milestone C to production** until Milestone D SEO grid metadata migration is complete and approved.
