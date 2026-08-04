# Milestone E — Validation

**Date:** 2026-08-04  
**Environment:** `dev.biopentra.eu`  
**Prerequisite:** Milestone D baseline (253 passed / 0 failed / 0 flaky)

## Targeted runs (during implementation)

| WP | Command | Result |
|---|---|---|
| E2 | `tools/run-dev.sh --e2-only` | **PASS** — 7 passed / 0 failed / 28 skipped |
| E1+E3 | `tools/run-dev.sh --e1-only` | **PASS** — 8 passed / 0 failed (after footer grid compaction) |

### E2 notes
- Exactly one `.umc-switcher` with `--manual`; zero `--floating-bottom`
- Sticky z=100; cookie ≤400; sticky usable after cookie accept

### E1 notes
- Menu / account / search ≥44×44 @ mobile-360/390/430
- Search focuses `#biopentra-shop-s` on home; navigates to shop on PDP
- No horizontal overflow on home

### E3 notes
- Footer height @360: **~1424px → ~778px** (material reduction)
- Crawlable `a[href]` retained; research text not `display:none`

## Full suite (E4 — once before tag)

| Field | Value |
|---|---|
| Command | `tools/run-dev.sh --milestone-e` |
| Result | **264 passed / 0 failed / 0 flaky / 306 skipped** |
| Date | 2026-08-04 |
| Note | Prior contaminated run had 1 flaky (`canonical-cards` quick-add click timeout); hardened with scrollIntoView + visibility waits; `wp-coming-soon.sh` now `--skip-plugins=ai-multilingual` for wpcli fatals |

## Screenshots

Under `validation/milestone-E/`:

- `e-header-360.png`
- `e-footer-360.png`
- `e-pdp-sticky-360.png`

## KPI updates

| Metric | Before E | After E | Target |
|---|---|---|---|
| Mobile header menu toggle | 20×20 | ≥44×44 | ≥44×44 |
| Footer height @360 | ~1424px | ~778px | Materially shorter |
| Floating UMC sticky_footer | Present | Removed (manual header) | No bottom conflict |
