# biopentra-storefront 0.8.0

## Summary

Milestone D — SEO content ownership, SDS image tokens, and commercial image caps on home/shop.

## Changes

- **D3 SEO content ownership:** curated SEO grid inventories move to page post meta (`_bp_seo_grid_products`, `_bp_seo_archive_term`) with migrate / validate / rollback CLIs and a one-release legacy fallback.
- **D1 image system:** global SDS token stylesheet (`bp-tokens.css`), hero max-height caps on home/shop v2 CSS, versioned asset enqueue.
- Documentation: `docs/storefront-redesign/changes/milestone-D*.md`, deployment and validation records.

## Companion releases

| Package | Version | Tag |
|---|---|---|
| biopentra-loop-card | 1.6.1 | `v1.6.1` |
| biopentra-blocksy-child | 1.1.0 | `v1.1.0` |

## Deploy (dev)

```bash
wp eval-file wp-content/plugins/biopentra-storefront/scripts/migrate-seo-grid-meta-cli.php
wp eval-file wp-content/plugins/biopentra-storefront/scripts/validate-seo-grid-meta-cli.php
wp elementor flush-css
wp cache flush
```

## Rollback

Install the previous storefront release ZIP (`0.7.0`). Optionally run `rollback-seo-grid-meta-cli.php` if reverting D3 meta.
