# Milestone A — Commercial Homepage

**Status:** Complete on dev — pending production replay approval  
**Date:** 2026-08-02  
**Version:** `biopentra-storefront` **0.6.0**, `biopentra-loop-card` (live search on front page)

## Summary

Rebuilt the homepage Elementor IA via idempotent CLI: compact hero, primary product search, category shortcut chips, three product grids (featured / newest / popular), then preserved editorial sections (research, trust, why, FAQ, CTA). No URLs or SEO content removed.

## KPI impact (360×800)

| Metric | Before | After | Target |
|---|---|---|---|
| Screens to first product | 3.07 | **1.04** | ≤ 1.0 |
| Primary search on page | No | Yes | Yes |
| Category chips | No | 6 WC archive links | Yes |
| Editorial below products | No | Yes | Yes |

Full scoreboard: [KPI_BASELINE.md](../validation/KPI_BASELINE.md)

## URLs affected

| Environment | URL | Page ID (dev) |
|---|---|---|
| Dev | `https://dev.biopentra.eu/` | 4444 (slug `home`) |
| Production | `https://www.biopentra.eu/` | Lookup: `page_on_front` or slug `home` |

## Component owner

`biopentra-storefront` v0.6.0 — CLI, CSS, helpers  
`biopentra-loop-card` — live search enqueued on `is_front_page()`

## Previous state

DOM order: hero → trust badges → why Biopentra → products → research → FAQ → CTA. First product at **2459px** (3.07 viewports).

Backup: [backups/home-pre-A.json](backups/home-pre-A.json)

## New state

DOM order: hero → search → category chips → featured / newest / popular grids → research → trust → why → FAQ → CTA.

## Files changed

| Repo | Path |
|---|---|
| biopentra-storefront | `scripts/setup-home-page-v2-cli.php` |
| biopentra-storefront | `includes/home-v2-helpers.php`, `includes/home-v2-assets.php` |
| biopentra-storefront | `assets/css/home-v2.css` |
| biopentra-storefront | `includes/class-biopentra-storefront.php` |
| biopentra-loop-card | `biopentra-loop-card.php` (live search on front page) |
| storefront-acceptance | `tests/home-ia.spec.ts` |

## WP-CLI commands (replay)

```bash
# 1. Backup production _elementor_data
cd /opt/biopentra/apps/wordpress
HOME_ID=$(docker compose run --rm -T wpcli wp option get page_on_front | tr -d '\r')
docker compose run --rm -T wpcli wp post meta get "$HOME_ID" _elementor_data \
  > /path/to/backups/home-pre-A-prod.json

# 2. Install biopentra-storefront v0.6.0 + loop-card update
# 3. Apply layout
docker compose run --rm -T wpcli wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-home-page-v2-cli.php

# 4. Flush trio
docker compose run --rm -T wpcli wp elementor flush-css
docker compose run --rm -T wpcli wp cache flush
# Cloudflare purge /
```

## Cache steps

Elementor CSS cache deleted on page meta update; run flush trio after CLI.

## Screenshots

| When | Path |
|---|---|
| Before | [../validation/milestone-A-home/before/home-mobile-360.png](../validation/milestone-A-home/before/home-mobile-360.png) |
| After | [../validation/milestone-A-home/after/home-mobile-360.png](../validation/milestone-A-home/after/home-mobile-360.png) |

## Acceptance

```bash
cd /opt/biopentra/dev/storefront-acceptance
bash tools/run-dev.sh --baseline-only
# includes tests/home-ia.spec.ts
```

## Rollback

```bash
HOME_ID=$(wp option get page_on_front)
wp post meta update "$HOME_ID" _elementor_data "$(cat backups/home-pre-A.json)"
wp elementor flush-css && wp cache flush
```

Reinstall `biopentra-storefront` v0.5.21 ZIP if reverting code.

## Production replay

See [../deployment/milestone-A-home.md](../deployment/milestone-A-home.md)

## Commit hashes

| Repo | Commit | Branch |
|---|---|---|
| `biopentra-custom-plugins` | `ffe7de7` | main |
| `biopentra-custom-plugins` | `82c0a57` | main (docs + after screenshot) |
| `storefront-acceptance` | `b45f0d4` | main |
| `biopentra-loop-card` | `f48234a` | main |
