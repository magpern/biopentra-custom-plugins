# MOTION-1 — Storefront Motion System — change record

**Status:** Implemented on `feature/motion-1-storefront-motion-system` (DEV bind-mount still serves `main`; no version bump, tag, or production replay)
**Plan:** [MOTION-1_STOREFRONT_MOTION_SYSTEM.md](../plans/MOTION-1_STOREFRONT_MOTION_SYSTEM.md)
**Plan-freeze commit:** `a824adad5922594be4e7497830caf84405f7f4d7`
**Implementation commit:** `93fe25fb03c588749654600807d9c861ed452f0f`
**Component owner:** `biopentra-storefront` (plugin header remains `0.9.39` until PO visual freeze)

## Summary

Homepage-first CSS-first motion: section reveal, curated-grid card stagger, storefront chip/button press. Two-handle WordPress enqueue (src-less head gate + footer IIFE). Runtime stamps only; no Elementor JSON mutation.

## URLs affected

- `https://dev.biopentra.eu/` (when this branch is served)
- Shop/search/SEO/PDP: no card motion stamps

## Files changed

- `plugins/biopentra-storefront/includes/class-biopentra-storefront.php`
- `plugins/biopentra-storefront/includes/motion-assets.php` (new)
- `plugins/biopentra-storefront/assets/css/bp-tokens.css`
- `plugins/biopentra-storefront/assets/css/bp-motion.css` (new)
- `plugins/biopentra-storefront/assets/js/bp-motion.js` (new)
- `docs/storefront-redesign/validation/motion-1/motion-1.spec.ts` (new; copy onto `storefront-acceptance/tests/` for a run)

## Selectors / CSS

- Hide: `html.bp-motion [data-bp-motion-state="pending"]` only
- Observe: `.bp-home-cats-section`, `.bp-m5-trust`, `.bp-m6-*`, `.bp-m7-guidance`, `.bp-home-products-section`
- Cards: `.bp-home-products-section .elementor-loop-container > .e-loop-item`
- Tokens: `--bp-motion-distance` 14px, duration 360ms, stagger 50ms, init failsafe 2000ms, node failsafe 25000ms

## DB / Elementor

None.

## Acceptance

Harness: sibling `/opt/biopentra/dev/storefront-acceptance`. No new runner flag. Spec is copied onto `tests/motion-1.spec.ts` for the run; CSS/JS copied to `tmp-motion/` because live DEV still bind-mounts `main`.

```
cd /opt/biopentra/dev/storefront-acceptance
mkdir -p tmp-motion
cp <worktree>/plugins/biopentra-storefront/assets/css/bp-motion.css tmp-motion/
cp <worktree>/plugins/biopentra-storefront/assets/js/bp-motion.js tmp-motion/
cp <worktree>/docs/storefront-redesign/validation/motion-1/motion-1.spec.ts tests/motion-1.spec.ts
bash tools/run-dev-playwright.sh tests/motion-1.spec.ts \
  --project=mobile-360 --project=mobile-390 --project=mobile-430 \
  --project=tablet-768 --project=desktop-1440
```

**MOTION-1 spec (2026-08-30):** 17 passed, 8 skipped (desktop-only cases on other projects). Exit 0.

Coverage: hero never pending; no horizontal overflow; in-view cards not pending; Popular still pending after 2.5s at hero then reveals once (no repeat on scroll-back); shop unstamped with injected controller; gate-without-controller failsafe; `prefers-reduced-motion: reduce`; JS disabled; chip `:focus-visible` transform none.

Screenshots: [validation/motion-1/screenshots/](../validation/motion-1/screenshots/) (`motion-1-mobile-360.png`, `motion-1-mobile-390.png`, `motion-1-mobile-430.png`, `motion-1-tablet-768.png`, `motion-1-desktop-1440.png`).

**M9 shop filter + search (served `main`, 2026-08-30):**

```
bash tools/run-dev-playwright.sh tests/m9-shop-discovery.spec.ts \
  --project=mobile-360 --project=desktop-1440 \
  --grep "category filter|search remains"
```

4 passed (filter in-place + search on 360 and 1440). Exit 0.

**M4 cards (served `main`, 2026-08-30):** `tests/m4-cards.spec.ts` on the five MOTION-1 viewports — homepage `objectFit` expected `"cover"` received `""`; desktop overlay click navigated to PDP instead of `is-overlay-quick`. These run against bind-mounted `main` and `biopentra-loop-card`, which MOTION-1 did not modify. Not treated as a MOTION-1 regression.

## Production replay

Not performed. Not authorized.

## Rollback

Revert feature-branch commits or dequeue `motion-assets.php`. No Elementor restore.

## Deferred

- `biopentra-loop-card` `prefers-reduced-motion` (hover/overlay/pulse)
- PDP motion; ATC success pop
- Contact/SEO section reveals
- Plugin version / `storefront-v*` tag after PO visual review
- Serving this branch on DEV (bind-mount still `main`)
