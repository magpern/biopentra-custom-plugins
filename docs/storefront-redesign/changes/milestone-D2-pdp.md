# Milestone D2 — Product Detail Experience

**Status:** COMPLETE on dev (D2A + D2B)  
**Date:** 2026-08-03  
**Owner:** `biopentra-blocksy-child` **1.1.0** (with storefront tokens)

## D2A — Product Detail Layout

| Item | Implementation |
|---|---|
| Gallery / thumbs | Inherited from D1 `gallery.css` |
| Price / title / stock | SDS typography in `assets/pdp/layout.css` |
| Qty / ATC touch | min 44px |
| Related / upsells | Unhide `ct-hidden-sm/md` on mobile/tablet |
| Disclaimer | Remains in description tabs; spacing via layout.css |
| Cross-sells | Unhide CSS ready if fixtures appear |

**Exit:** `tests/pdp-layout.spec.ts` (`--d2a-only`)

## D2B — Sticky Purchase Experience

| Item | Implementation |
|---|---|
| Sticky bar | Mobile-only (`max-width: 767px`), `#bp-pdp-sticky` |
| Sync | Price, variation meta, stock, ATC enabled state from native form |
| Visibility | IntersectionObserver on `.ct-product-add-to-cart` |
| A11y | `role="region"`, focusable ATC when enabled |
| Safe area | `env(safe-area-inset-bottom)` |
| Z-index | `--bp-z-sticky-bar` (100) — under cookie/header/overlays |
| Reduced motion | `prefers-reduced-motion` honored |

**Files:** `inc/pdp-sticky-bar/class-pdp-sticky-bar.php`, `assets/pdp/sticky-bar.{css,js}`

**Exit:** `tests/pdp-sticky.spec.ts` (`--d2b-only`)

## Sync

Child theme is **not** bind-mounted — after every change run DEV-SYNC (`docs/DEV-SYNC.md`).

## Rollback

Revert child theme to 1.0.0 release ZIP; flush caches. Sticky markup and CSS disappear with the module.

## Out of scope

CookieYes / WOOCS UI repositioning → Milestone E. Checkout / cart AJAX unchanged.
