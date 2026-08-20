# V1A — Design Specimen (trial visual system)

**Status:** Live on `dev.biopentra.eu` — awaiting Product Owner visual review  
**Date:** 2026-08-20  
**Not:** SDS v2 freeze · V1 complete · V2 started · release tag · production

## Summary

Implemented a **reversible homepage-only** visual specimen so the Product Owner can judge the proposed direction (Barlow / Barlow Condensed, steel-blue `#5980a6`, 4px radii, hairline borders, denser commercial chrome, specimen-level card preview). No architecture changes. No template 3608 migration. No header/cart V2 work.

## URLs affected

| Environment | URL |
|---|---|
| Dev (inspect) | https://dev.biopentra.eu/ (hard-refresh / `?v1a=1` if cached) |
| Production | **not deployed** |

## Page / template IDs

| Item | Dev ID | Change |
|---|---|---|
| Home | 4444 | No Elementor `_elementor_data` mutation |
| Header TB | 3782 | Untouched |
| Loop card | 3608 | Untouched |

## Component owner

`biopentra-storefront` (working tree; **not tagged**; still reports version **0.9.4**)

## Previous state

Homepage commercial IA from Milestones A–E; soft/pill SDS v1 tokens; theme fonts; no Barlow; saturated primary via theme palette.

## New state

- Body class `bp-v1a-trial` on front page
- Specimen panel `#bp-v1a-specimen` injected after Elementor header (`elementor/theme/after_do_header`)
- Light scoped home chrome (chips/search radius + heading fonts) under `body.bp-v1a-trial.home`
- Self-hosted Barlow + Barlow Condensed (OFL)

## Files changed

| Path | Role |
|---|---|
| `plugins/biopentra-storefront/includes/v1a-specimen.php` | Enqueue, body class, panel HTML |
| `plugins/biopentra-storefront/includes/class-biopentra-storefront.php` | Require V1A include |
| `plugins/biopentra-storefront/assets/css/bp-v1a-fonts.css` | `@font-face` |
| `plugins/biopentra-storefront/assets/css/bp-v1a-specimen.css` | Specimen + scoped trial chrome |
| `plugins/biopentra-storefront/assets/fonts/barlow/*.woff2` | Self-hosted body weights |
| `plugins/biopentra-storefront/assets/fonts/barlow-condensed/*.woff2` | Self-hosted heading weights |
| `plugins/biopentra-storefront/assets/fonts/OFL-Barlow.txt` | License notice |

**Not changed:** `bp-tokens.css` defaults (so loop-card / archives do not inherit V1A radii globally), template 3608, chrome-v1 header/cart, UMC, sticky bar, SEO meta.

## Typography / fonts

| Role | Family | Source |
|---|---|---|
| Body / UI | Barlow 400/500/700 | Self-hosted WOFF2 (Fontsource latin subset) |
| Headings / display / buttons | Barlow Condensed 600/700 | Same |
| License | SIL OFL 1.1 | `assets/fonts/OFL-Barlow.txt` |
| Loading | `font-display: swap` + preload of 400 body + 700 condensed | |

## Design tokens demonstrated (specimen-scoped)

| Token | Value |
|---|---|
| Primary | `#5980a6` |
| Primary hover / pressed | `#416180` / `#2c455d` |
| Background | `#f2f2f3` |
| Surface | `#e9e9ea` |
| Text | `#1d1f20` |
| Border | ~16% ink hairline |
| Radius | `4px` |
| Card shadow | `none` (hairline only) |

## Selectors

- `#bp-v1a-specimen`, `.bp-v1a-specimen__*`
- `.bp-v1a-card-preview`
- `body.bp-v1a-trial.home` scoped chip/search/heading overrides

## Settings / DB changes

None. No Elementor JSON, no options, no UMC/CookieYes changes.

## WP-CLI / cache

```bash
cd /opt/biopentra/apps/wordpress
docker compose run --rm -T wpcli wp cache flush
# Hard-refresh browser; append ?v1a=1 if edge/page cache sticky
```

## Screenshots

[`validation/v1a-design-specimen/screenshots/`](../validation/v1a-design-specimen/screenshots/)

| File | Viewport |
|---|---|
| `home-v1a-{360,390,430,768,1024,1025,1440,1680}.png` | Full above-the-fold home |
| `specimen-{…}.png` | Specimen panel crop |
| `capture-report.json` | Automated font/radius/nav/overflow checks |

## Acceptance / targeted validation

See [`validation/v1a-design-specimen.md`](../validation/v1a-design-specimen.md). Full acceptance matrix **not** run.

## Production replay

**N/A** — V1A is dev specimen only. P0 remains on hold.

## Rollback

1. Disable without deploy: `add_filter( 'biopentra_v1a_specimen_enabled', '__return_false' );` (mu-plugin or temporary snippet), **or**
2. Remove `require_once` of `v1a-specimen.php` from `class-biopentra-storefront.php`, **or**
3. Revert the V1A commit on `biopentra-custom-plugins`.

Then `wp cache flush`. No Elementor restore needed (no DB mutation).

## Commit hash(es)

Recorded after commit in validation doc / this section update.

## Intentionally deferred

| Item | Phase |
|---|---|
| Freeze SDS v2 / replace `bp-tokens.css` defaults | V1 (after PO approve) |
| Header cart icon / kill floating pill | V2 |
| Page-wide home/shop/search polish | V3 |
| Template 3608 / loop-card full visual migration | V4 |
| Cart/checkout/account styling | V5 |
| Blueprint `+` marks, duotone, account-gated checkout | Rejected |
| Catalog / Uncategorized / featured-loop merchandising | Out of scope |

## Explicit status

**V1A specimen ready for Product Owner visual review.**  
**V1 is NOT frozen. V2 has NOT started.**
