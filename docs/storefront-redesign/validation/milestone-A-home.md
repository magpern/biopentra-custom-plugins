# Milestone A — Validation record

**Date:** 2026-08-02 (updated after baseline cleanup)  
**Harness:** `bash tools/run-dev.sh --milestone-a`

## Playwright

| Suite | Result |
|---|---|
| `tests/baseline.spec.ts` (55 tests × 5 viewports) | **PASS 55/55** |
| `tests/home-ia.spec.ts` (4 tests × mobile-360) | **PASS 4/4** |
| **Total** | **59 passed, 16 skipped** (home-ia skipped on non-360 viewports) |

### home-ia checks

- Primary search visible above first product card
- First product within ~1 viewport (836px @ 360×800, 5% tolerance)
- Category shortcut chips present (≥3)
- Editorial `why4444` section below first product grid

### Baseline cleanup (2026-08-02)

Pre-fix run: 39 passed, 16 failed. Root cause: Coming Soon bypass flake on long multi-viewport runs (14 failures) plus stale cart scroll budgets (2 failures). Fixed in harness; re-captured `fixtures/budgets.json`. See [milestone-A-baseline-failures.md](milestone-A-baseline-failures.md).

## KPI (360×800)

| Metric | Before | After |
|---|---|---|
| Screens to first product | 3.07 | **1.04** |
| Search on homepage | No | Yes (`#biopentra-shop-s`) |
| Category chips | No | 6 WC archive links |

## Screenshots

| | Path |
|---|---|
| Before | [before/home-mobile-360.png](before/home-mobile-360.png) |
| After | [after/home-mobile-360.png](after/home-mobile-360.png) |

## Report

`storefront-acceptance/artifacts/playwright-report.json`
