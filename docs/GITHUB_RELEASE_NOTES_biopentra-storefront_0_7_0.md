# biopentra-storefront 0.7.0

## Summary

Milestone B — commercial shopping IA on dev: shop page v2, search refinement UI, and SEO category product grids.

## Changes

- Shop page (3755): compact hero → search → category chips → taxonomy filter → Elementor loop grid → disclaimer; category descriptions moved below the grid.
- Search results: on-page refinement search (`#biopentra-search-refine`) before the product loop.
- SEO category pages (4430–4432): hero → search → chips → curated product grid (template 3608) → WC archive link → editorial body unchanged.
- New CLIs: `setup-shop-page-v2-cli.php`, `setup-seo-category-v2-cli.php`.
- Helpers/assets: `shopping-v2-helpers.php`, `shopping-v2-assets.php`, `shop-v2.css`, `search-v2.css`, `seo-category-v2.css`.

## Notes

- Product cards on search and native WC archives remain Blocksy-native until Milestone C.
- SEO grid product slugs are curated in PHP temporarily; page-owned post meta migration is scheduled for Milestone D (before production replay).
- Documentation: `/opt/biopentra/docs/storefront-redesign/changes/milestone-B-commercial-shopping.md`.

## Deploy (dev)

```bash
wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-shop-page-v2-cli.php
wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-seo-category-v2-cli.php
wp elementor flush-css
```

## Rollback

Restore Elementor backups under `docs/storefront-redesign/changes/backups/` and reinstall the previous storefront release ZIP.
