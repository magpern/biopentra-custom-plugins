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
- `plugins/biopentra-storefront/scripts/verify-motion-1-enqueue-cli.php` (WP printed-output enqueue proof)
- `scripts/run-motion-1-acceptance.sh` (canonical runner)
- `docs/storefront-redesign/validation/motion-1/` (spec + apply helper; Playwright owner is this repo)

## Selectors / CSS

- Hide: `html.bp-motion [data-bp-motion-state="pending"]` only
- Observe: `.bp-home-cats-section`, `.bp-m5-trust`, `.bp-m6-*`, `.bp-m7-guidance`, `.bp-home-products-section`
- Cards: `.bp-home-products-section .elementor-loop-container > .e-loop-item`
- Tokens: `--bp-motion-distance` 14px, duration 360ms, stagger 50ms, init failsafe 2000ms, node failsafe 25000ms

## DB / Elementor

None.

## Acceptance

**Owner:** `biopentra-custom-plugins` (`docs/storefront-redesign/validation/motion-1/`).  
**Runner:** `scripts/run-motion-1-acceptance.sh` copies the spec into sibling `storefront-acceptance`, runs it, and deletes the copies. No storefront-acceptance branch/PR. No new Playwright runner flag.

```
cd /home/magpern/worktrees/biopentra-custom-plugins-motion-1
bash scripts/run-motion-1-acceptance.sh --php-lint-only
bash scripts/run-motion-1-acceptance.sh --enqueue-only
bash scripts/run-motion-1-acceptance.sh --playwright-only
bash scripts/run-motion-1-acceptance.sh --m4-only
```

**PHP syntax (2026-08-31):** `php -l` clean on `motion-assets.php`, `class-biopentra-storefront.php`, `verify-motion-1-enqueue-cli.php`.

**Enqueue proof (2026-08-31):** disposable `wpcli` with extra read-only mount of this worktree’s storefront plugin to `/motion-1-storefront`. Captured real `wp_head()` / `wp_footer()` HTML. Transcript: [runs/enqueue-proof.txt](../validation/motion-1/runs/enqueue-proof.txt). RESULT: PASS.

- Front page (`is_front_page=1`): src-less `biopentra-motion-gate` group 0 in head with inline `classList.add("bp-motion")`; `bp-motion.js` group 1 in footer only; `bp-motion.css` depends on `biopentra-bp-tokens`; noscript pending-visible rule in head.
- Cart (`is_cart=1`), checkout (`is_checkout=1`), shop (`is_shop=1`), admin (`is_admin=1`): MOTION-1 handles absent from enqueue and printed HTML.

**MOTION-1 Playwright (2026-08-31):** 17 passed, 8 skipped, exit 0. Skips documented in [runs/skipped.txt](../validation/motion-1/runs/skipped.txt) — not an acceptance gap.

**M4 parity (2026-08-31):** same five viewports, served `ac60df4` vs injected feature assets. 6 failures each, 0 only-baseline, 0 only-feature, identical error signatures. [runs/m4-parity.txt](../validation/motion-1/runs/m4-parity.txt). Homepage `objectFit` cover vs `""` and desktop overlay not reaching `is-overlay-quick` exist on served main and with MOTION-1 injected.

**M9 shop filter + search (served `main`, 2026-08-30):** 4 passed on 360 and 1440.

Screenshots: [validation/motion-1/screenshots/](../validation/motion-1/screenshots/).

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
