# Milestone C — Deployment

**Target:** dev.biopentra.eu only (no production replay)  
**Plugin:** `biopentra-loop-card` 1.6.0  
**Tag (after green validation):** `v1.6.0`

## Pre-deploy checklist

- [ ] Architecture doc merged: `design-system/canonical-product-card-architecture.md`
- [ ] Plugin files synced to `wp-content/plugins/biopentra-loop-card` (symlink or copy)
- [ ] `docker compose run --rm wpcli wp plugin list` shows 1.6.0 active

## Deploy steps (dev)

```bash
cd /opt/biopentra/dev/biopentra-loop-card
# After tag: install from GitHub Release ZIP, or symlink is already live on dev VPS

cd /opt/biopentra/apps/wordpress
docker compose run --rm -T wpcli wp elementor flush-css
docker compose run --rm -T wpcli wp cache flush
curl -sI https://dev.biopentra.eu/product-category/research-peptides/ | head -5
```

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
