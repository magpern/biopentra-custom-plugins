# Milestone D1 — Image System

**Status:** COMPLETE on dev  
**Date:** 2026-08-03  
**Versions:** ships with `biopentra-storefront` **0.8.0**, `biopentra-loop-card` **1.6.1**, `biopentra-blocksy-child` **1.1.0** (tagged at Milestone D close)

## Summary

Shipped SDS tokens globally, capped commercial heroes/cards/PDP gallery, applied card `sizes` + LCP policy, and disabled sticky PDP gallery on mobile.

## Deliverables

| Item | Detail |
|---|---|
| `bp-tokens.css` | Enqueued globally (`biopentra-bp-tokens`, priority 5) |
| Hero caps | Home/shop use SDS commercial tokens (28vh / 24–20vh ≤ 40vh max) |
| Card images | SDS ratio/max-h; `sizes=(max-width: 480px) 45vw, (max-width: 768px) 30vw, 22vw` |
| LCP | First commercial card: `loading=eager` + `fetchpriority=high` (PHP + enhance JS fallback) |
| Lazy | Subsequent cards remain lazy |
| PDP gallery | Max 50vh mobile / 520px desktop; sticky disabled ≤767px; thumbs ≥44px |
| Trust icons | `max-height: 48px` when present |

## Files

| Repo | Path |
|---|---|
| storefront | `assets/css/bp-tokens.css`, `includes/bp-tokens-assets.php`, home/shop CSS, enqueue deps |
| loop-card | `includes/card-image-attributes.php`, `assets/loop-card.js` LCP promote |
| blocksy-child | `assets/pdp/gallery.css`, `functions.php` enqueue |
| acceptance | `tests/image-budget.spec.ts` |

## Architecture notes vs plan

1. **Commercial hero caps remain tighter than SDS absolute max** (`40vh`) — intentional, validated in A/B; exposed as `--bp-hero-home-max-height` / `--bp-hero-shop-*`.
2. **Elementor Loop** often bakes `loading=lazy` into widget HTML; PHP attribute filters + `loop-card.js` first-card promote ensure LCP hints apply.
3. **Sticky gallery** disabled via CSS on mobile only — desktop may keep Blocksy `has_product_sticky_gallery`.

## Rollback

1. Revert storefront / loop-card / child releases.
2. Remove child `assets/pdp/` via DEV-SYNC restore of prior theme ZIP.
3. Flush Elementor CSS + object cache.

## Validation

`tools/run-dev.sh --d1-only` — see milestone-D validation record.
