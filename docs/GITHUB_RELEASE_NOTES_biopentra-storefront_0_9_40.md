# biopentra-storefront 0.9.40

**MOTION-1 — Storefront Motion System (PO-approved / frozen on DEV).**

## Highlights

- Homepage: CSS-first section reveal and curated product-card stagger. Hero and first-viewport content stay visible (no flash-hidden).
- Shop: per-card reveal on `.elementor-element-ed52b7f` loop items (initial SSR, category filter, in-shop search, load-more). First-viewport cards stay visible; below-fold cards reveal once.
- Enqueue: src-less head handle `biopentra-motion-gate`, `bp-motion.css` (depends on SDS tokens), footer `bp-motion.js`. Not loaded on cart, checkout, or admin.
- Reduced-motion and no-JS paths are static. No Elementor JSON mutation and no database change.

## Install

1. Download `biopentra-storefront-0.9.40.zip` from this Release.
2. Replace the plugin on the target WordPress host (DEV already bind-mounts git; production uses this ZIP when separately authorized).
3. No WP-CLI replay or cache-bust beyond normal full-page cache if HTML was cached without the new handles.

## Notes

- Tag: `storefront-v0.9.40`
- Production rollout still requires an explicit GO beyond this tag.
- Spec: `docs/storefront-redesign/plans/MOTION-1_STOREFRONT_MOTION_SYSTEM.md`
- Shop-card amendment: `docs/storefront-redesign/plans/MOTION-1_AMENDMENT_2026-08-31_SHOP_CARD_REVEAL.md`
