# V1A — Design Specimen (trial visual system)

**Status:** Iteration 2 live on `dev.biopentra.eu` — awaiting Product Owner visual review  
**Dates:** 2026-08-20 (iter 1) · 2026-08-20 (iter 2 correction)  
**Not:** SDS v2 freeze · V1 complete · V2 started · release tag · production

---

## Iteration history

### V1A iteration 1 — REJECTED BY PRODUCT OWNER

**What shipped:** Injected `#bp-v1a-specimen` demo panel after the Elementor header (typography demos, fake buttons/chips/search, fake card / “VIAL PHOTO”, explanatory banners).

**Why rejected:** Misunderstood “design specimen.” The Product Owner opened the real homepage and saw a style-guide panel before the storefront. The development storefront itself must *be* the specimen — evaluate the proposed visual language on **real** BioPentra components, not a demo inserted into customer-facing markup.

### V1A iteration 2 — corrected real-homepage specimen

Removed all demo HTML. Applies Barlow / steel-blue `#5980a6` / 4px geometry / hairline cards **only** via scoped CSS on existing Milestone A homepage components under `body.bp-v1a-trial.home`.

---

## Summary (iteration 2)

Reversible homepage-only visual trial so the Product Owner can browse normally and judge whether the proposed system improves the real storefront. No architecture changes. No template 3608 migration. No header/cart V2 work.

## URLs

| Environment | URL |
|---|---|
| Dev (inspect) | https://dev.biopentra.eu/ (hard-refresh / `?v1a2=1` if cached) |
| Production | **not deployed** |

## Page / template IDs

| Item | Dev ID | Change |
|---|---|---|
| Home | 4444 | No Elementor `_elementor_data` mutation |
| Header TB | 3782 | Untouched |
| Loop card | 3608 | Untouched (homepage CSS overlay only) |

## Component owner

`biopentra-storefront` (working tree; **not tagged**; header still **0.9.4**)

## Real components receiving V1A treatment

| Component | How |
|---|---|
| Homepage body / surfaces | `body.bp-v1a-trial.home` fonts + `#f2f2f3` ground |
| Hero heading | Barlow Condensed |
| Hero CTAs | 4px radius; primary steel-blue fill; secondary outline preserved |
| Category chips | 4px, hairline, steel text |
| Search field | 4px, surface fill, hairline |
| Featured / product section headings | Barlow Condensed |
| Homepage product cards (Featured/Newest/Popular grids) | 4px, hairline, no soft shadow; title/price type; square quick-add |
| Quick-add control | 4px square on homepage cards only |

**Not treated (deferred):** header/cart chrome (V2), shop/archives (V3/V4), cart/checkout (V5), global `bp-tokens.css` freeze (V1).

## Files changed (iteration 2)

| Path | Role |
|---|---|
| `plugins/biopentra-storefront/includes/v1a-specimen.php` | Enqueue + body class only (no HTML injection) |
| `plugins/biopentra-storefront/assets/css/bp-v1a-specimen.css` | Real-homepage scoped trial styles |
| Fonts / `bp-v1a-fonts.css` | Unchanged from iter 1 (still used) |

## Settings / DB

None.

## Screenshots (iteration 2)

Canonical project repo:

`screenshots/v1a/iteration-2/home-v1a2-{360,390,430,1024,1025,1440,1680}.png`  
`screenshots/v1a/iteration-2/capture-report.json`

Iteration 1 panel crops remain under `screenshots/v1a/screenshots/` as historical evidence of the rejected approach.

## Validation

See matching validation record. Full acceptance matrix **not** run.

## Rollback

```php
add_filter( 'biopentra_v1a_specimen_enabled', '__return_false' );
```

or remove the `v1a-specimen.php` require / revert V1A commits. Then `wp cache flush`. No Elementor restore.

## Explicit status

**V1A iteration 1: REJECTED.**  
**V1A iteration 2: corrected real-homepage specimen ready for Product Owner visual review.**  
**V1 is NOT frozen. V2 has NOT started.**
