# MOTION-1 — Storefront Motion System — change record

**Status:** Implemented on `feature/motion-1-storefront-motion-system` (DEV bind-mount still serves `main`; no version bump, tag, or production replay)
**Plan:** [MOTION-1_STOREFRONT_MOTION_SYSTEM.md](../plans/MOTION-1_STOREFRONT_MOTION_SYSTEM.md)
**Plan-freeze commit:** `a824adad5922594be4e7497830caf84405f7f4d7`
**Amendment commit (docs):** `314221347ed6362eaca29a26ac67570f5a5bbb2d` — 2026-08-31 `/shop` cards in scope
**Implementation commit:** `93fe25fb03c588749654600807d9c861ed452f0f` (homepage) + `73a790573944c31a8aa5ce238f74b83fca3019a6` (`/shop` cards)
**Component owner:** `biopentra-storefront` (plugin header remains `0.9.39` until PO visual freeze)

## Summary

CSS-first motion owned by `biopentra-storefront`: homepage section reveal + curated-grid stagger, plus **2026-08-31** per-card `/shop` `ed52b7f` reveal (initial SSR, filter/search replacement, load-more append). Two-handle WordPress enqueue (src-less head gate + footer IIFE). Runtime stamps only; no Elementor JSON mutation.

## URLs affected

- `https://dev.biopentra.eu/` (when this branch is served) — homepage motion unchanged
- `https://dev.biopentra.eu/shop/` — `ed52b7f` loop cards stamped `data-bp-motion="shop-card"`
- Search templates, SEO landings, Woo category archives, cart, checkout, admin: no MOTION-1 enqueue / no shop-card stamps

## Files changed

- `plugins/biopentra-storefront/includes/class-biopentra-storefront.php`
- `plugins/biopentra-storefront/includes/motion-assets.php`
- `plugins/biopentra-storefront/assets/css/bp-tokens.css`
- `plugins/biopentra-storefront/assets/css/bp-motion.css`
- `plugins/biopentra-storefront/assets/js/bp-motion.js`
- `plugins/biopentra-storefront/scripts/verify-motion-1-enqueue-cli.php`
- `scripts/run-motion-1-acceptance.sh`
- `docs/storefront-redesign/validation/motion-1/`

## Selectors / CSS

- Hide: `html.bp-motion [data-bp-motion-state="pending"]` only
- Homepage observe: section bands + `.bp-home-products-section` **containers**
- Homepage cards: `.bp-home-products-section .elementor-loop-container > .e-loop-item`
- Shop cards: `body.woocommerce-shop .elementor-element-ed52b7f .elementor-loop-container > .e-loop-item` (per-card IO; no `:nth-child` stagger)
- Tokens: `--bp-motion-distance` 14px, duration 360ms, stagger 50ms (homepage only), init failsafe 2000ms, node failsafe 25000ms

## DB / Elementor

None.

## Acceptance

**Owner:** `biopentra-custom-plugins` (`docs/storefront-redesign/validation/motion-1/`).  
**Runner:** `scripts/run-motion-1-acceptance.sh`

```
cd /home/magpern/worktrees/biopentra-custom-plugins-motion-1
bash scripts/run-motion-1-acceptance.sh --php-lint-only
bash scripts/run-motion-1-acceptance.sh --enqueue-only
bash scripts/run-motion-1-acceptance.sh --playwright-only
bash scripts/run-motion-1-acceptance.sh --m9-only
bash scripts/run-motion-1-acceptance.sh --m4-only
```

**PHP syntax (2026-08-31):** `php -l` clean on `motion-assets.php`, `class-biopentra-storefront.php`, `verify-motion-1-enqueue-cli.php`.

**Enqueue proof (2026-08-31, after `/shop` amendment):** disposable `wpcli` extra read-only mount of this worktree. Transcript: [runs/enqueue-proof.txt](../validation/motion-1/runs/enqueue-proof.txt). RESULT: PASS.

- Front page and **shop** (`is_shop=1`): src-less `biopentra-motion-gate` in head; `bp-motion.js` in footer; `bp-motion.css` depends on `biopentra-bp-tokens`.
- Cart, checkout, admin: MOTION-1 handles absent.

**Isolated preview HTML (2026-08-31):** `http://127.0.0.1:18080/` (basic auth). Home and `/shop/` print gate + CSS in `<head>` and `bp-motion.js` in the footer. Cart/checkout/admin do not. Note: preview `/shop/` currently falls back to the WooCommerce product archive (no `ed52b7f` loop); Elementor shop-card stamps are proven against live DEV DOM with injected branch assets and by WP enqueue on `is_shop()`.

**MOTION-1 Playwright (2026-08-31, shop amendment):** 23 passed, 12 skipped, exit 0. Skips: [runs/skipped.txt](../validation/motion-1/runs/skipped.txt). Shop tests cover first-viewport / below-fold / scroll-back, filter+search replacement, and desktop load-more. Homepage tests unchanged and passing.

**M9 shop discovery (served `main`, five MOTION viewports, 2026-08-31):** 29 passed / 151 skipped (M9 spec gates extra viewports not in this run). Filter, search, chip rail still functional. [runs/m9-shop-discovery.json](../validation/motion-1/runs/m9-shop-discovery.json).

**M4 parity (2026-08-31, after `/shop` amendment):** same five viewports, served `ac60df4` vs injected feature assets. 6 failures each, 0 only-baseline, 0 only-feature. [runs/m4-parity.txt](../validation/motion-1/runs/m4-parity.txt). Not a MOTION-1 delta.

Screenshots: [validation/motion-1/screenshots/](../validation/motion-1/screenshots/) (includes `motion-1-shop-*.png`).

## Production replay

Not performed. Not authorized.

## Rollback

Revert feature-branch commits or dequeue `motion-assets.php`. No Elementor restore.

## Deferred

- `biopentra-loop-card` `prefers-reduced-motion` (hover/overlay/pulse)
- PDP motion; ATC success pop
- Contact/SEO section reveals; dedicated search / Woo archive card motion
- Plugin version / `storefront-v*` tag after PO visual review
- Serving this branch on DEV (bind-mount still `main`)
