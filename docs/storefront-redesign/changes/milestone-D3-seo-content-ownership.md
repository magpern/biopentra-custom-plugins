# Milestone D3 — SEO Content Ownership

**Status:** COMPLETE on dev (production gate satisfied for B/C/D replay eligibility once Milestone D tagged)  
**Date:** 2026-08-03  
**Version:** `biopentra-storefront` **0.8.0** (ships with Milestone D tag)  
**Owner:** `biopentra-storefront`

## Summary

Curated SEO guide product lists and “Browse all” archive terms moved from plugin PHP inventories to **page-owned post meta**. Runtime reads meta first; deprecated PHP fallback remains for one release (`biopentra_storefront_seo_category_legacy_inventory()`). Structural layout keys (`root_class`, `grid_widget_id`, etc.) stay in PHP.

## Meta keys

| Key | Type | Purpose |
|---|---|---|
| `_bp_seo_grid_products` | `string[]` (serialized) | Ordered product slugs for the Elementor loop grid |
| `_bp_seo_archive_term` | `string` | WC `product_cat` slug for archive link |

## Pages

| Slug | ID (dev) | Products (seed) | Archive term |
|---|---|---|---|
| `growth-hormone-releasing-peptides` | 4430 | 6 slugs (1 published on catalog today) | `growth-performance` |
| `metabolic-research-peptides` | 4431 | 4 slugs (3 published) | `weight-management` |
| `lyophilized-research-materials` | 4432 | 6 slugs (6 published) | `research-peptides` |

## Files

| Path | Role |
|---|---|
| `includes/shopping-v2-helpers.php` | Meta-first resolution; structural configs; legacy fallback; shortcode uses page meta |
| `scripts/migrate-seo-grid-meta-cli.php` | Idempotent seed → meta |
| `scripts/validate-seo-grid-meta-cli.php` | Missing/invalid slug + term checks |
| `scripts/rollback-seo-grid-meta-cli.php` | Delete meta → legacy fallback |
| `scripts/setup-seo-category-v2-cli.php` | Writes meta when applying layout; archive shortcode without hardcoded slug |

## WP-CLI (dev)

```bash
cd /opt/biopentra/apps/wordpress
docker compose run --rm -T wpcli wp eval-file \
  wp-content/plugins/biopentra-storefront/scripts/migrate-seo-grid-meta-cli.php
docker compose run --rm -T wpcli wp eval-file \
  wp-content/plugins/biopentra-storefront/scripts/validate-seo-grid-meta-cli.php
docker compose run --rm -T wpcli wp cache flush
```

Force overwrite: `BIOPENTRA_SEO_META_FORCE=1` on migrate.

## Architecture decisions (vs frozen plan)

1. **Seed lives in migrate CLI + legacy helper**, not in structural `seo_category_configs()` — so production can remigrate after inventories leave the config array.
2. **Validation warns** on unpublished (`private`) product slugs and **errors** only on missing slugs or zero published products — matches pre-existing catalog (several B-era seed slugs are `private`).
3. **Shortcode** `[biopentra_wc_archive_link]` prefers page meta over the `slug` attribute when on an SEO guide page.

## Closes Milestone B debt

See [milestone-B-commercial-shopping.md](milestone-B-commercial-shopping.md) § Technical debt — SEO category product selection. Temporary PHP lists are no longer the source of truth.

## Rollback

```bash
docker compose run --rm -T wpcli wp eval-file \
  wp-content/plugins/biopentra-storefront/scripts/rollback-seo-grid-meta-cli.php
docker compose run --rm -T wpcli wp cache flush
```

While `biopentra_storefront_seo_category_legacy_inventory()` remains, grids continue to work. After that helper is removed (post-0.8.0), restore meta via migrate CLI or restore from backup.

## Validation

| Check | Result |
|---|---|
| Migrate CLI (first run) | written=3 |
| Migrate CLI (idempotent) | skipped=3 |
| Validate CLI | PASSED (0 errors; unpublished warnings documented) |
| Rollback → remigrate | PASSED |
| Playwright `--d3-only` | see [../validation/milestone-D.md](../validation/milestone-D.md) |

## Production gate

**No production replay of Milestones B, C, or D** until this work package is green and Milestone D is tagged. Replay order: install storefront ZIP → run migrate → validate → flush trio.
