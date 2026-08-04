# Milestone E2 — Fixed UI and Z-index Coordination

**Status:** COMPLETE on dev  
**Date:** 2026-08-04  
**Order:** First work package of frozen Milestone E (E2 → E1 → E3 → E4)

## Summary

Aligned fixed layers to the SDS z-index contract (100 / 200 / 300 / 400), relocated Universal Multicurrency from sticky footer to a single manual header switcher, and removed the floating bottom conflict with sticky ATC.

## Changes

| Item | Previous | New |
|---|---|---|
| `umc_settings.display.placement` | `sticky_footer` | `manual` |
| Header Elementor **3782** | No UMC shortcode | One `[universal_multicurrency_switcher]` shortcode widget |
| Floating UMC | `.umc-switcher--floating-bottom` present | **Zero** floating instances (config, not CSS hide) |
| CookieYes banner/revisit | Plugin default ~9999999 | `var(--bp-z-cookie, 300)` via `chrome-v1.css` |
| CookieYes preference/modal/backdrop | Plugin default | `var(--bp-z-overlay, 400)` via `chrome-v1.css` |
| Mini-cart drawer/container | Elementor default | `var(--bp-z-overlay, 400)` via `chrome-v1.css` |
| Header-auth dropdown | `z-index: 100000` | `var(--bp-z-header, 200)` |
| UMC CSS var | `--umc-switcher-z-index: 9990` | Bound to `--bp-z-header` in chrome CSS |

**CookieYes options:** No `cky_*` option values were modified. Alignment is CSS-only against SDS tokens (late enqueue after `biopentra-bp-tokens`). Pre-change option backups retained for rollback evidence.

## Files

| Path | Role |
|---|---|
| `plugins/biopentra-storefront/assets/css/chrome-v1.css` | SDS z-index + UMC var binding |
| `plugins/biopentra-storefront/includes/chrome-v1-assets.php` | Enqueue chrome CSS/JS |
| `plugins/biopentra-storefront/scripts/setup-milestone-e-chrome-cli.php` | Idempotent UMC + header insert |
| `plugins/biopentra-storefront/modules/header-auth/assets/header-auth.css` | Dropdown z-index → header layer |

## Backups

Under `docs/storefront-redesign/changes/backups/`:

- `umc_settings-2026-08-04-milestone-E-pre.json`
- `cky_options-2026-08-04-milestone-E-pre.json`
- `cky_banner_template-2026-08-04-milestone-E-pre.json`
- `header-3782-2026-08-04-milestone-E-pre.json` (reconstructed from post by removing E widgets — structural pre-E chrome)
- `header-3782-2026-08-04-milestone-E-post.json`

## Replay (production — not executed in E)

```bash
wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-milestone-e-chrome-cli.php
wp elementor flush-css && wp cache flush
```

## Rollback

1. Restore `umc_settings` from pre backup (`placement=sticky_footer`).
2. Restore header `_elementor_data` from `header-3782-*-pre.json`.
3. Deploy previous storefront ZIP if chrome CSS must be removed.
4. Flush Elementor CSS + object cache.

## Validation

Targeted: `tools/run-dev.sh --e2-only` (see validation record).
