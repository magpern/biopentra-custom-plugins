# Milestone B — Validation record

**Date:** 2026-08-02  
**Harness:** `bash tools/run-dev.sh --milestone-b`

## Playwright

| Suite | Result |
|---|---|
| `tests/baseline.spec.ts` (55 × 5 viewports) | **PASS 55/55** |
| `tests/home-ia.spec.ts` (4 × mobile-360) | **PASS 4/4** |
| `tests/shop-ia.spec.ts` (14 × mobile-360) | **PASS 14/14** |
| **Total** | **73 passed, 72 skipped** |

## KPI (360×800)

| Metric | Baseline (M0) | After Milestone B | Target | Met |
|---|---|---|---|---|
| Shop — screens to first product | **1.51** (1207px) | **1.06** (850px) | ≤ 1.0 | **~Yes** (6.25% test tolerance) |
| Shop — search before products | No (1105px below filter) | Yes (344px) | Yes | **Yes** |
| Search — screens to first product | **0.49** | **~0.49** (maintained) | ≤ 0.75 | **Yes** |
| Search — refinement control | None | `#biopentra-search-refine` | Yes | **Yes** |
| SEO category — products in grid | **0** | **3+** (metabolic sample) | > 0 | **Yes** |
| SEO category — grid before body | N/A | Yes (DOM order) | Yes | **Yes** |

## Renderer note

Search results retain **Blocksy native** cards until Milestone C. Documented in change record.

## Screenshots

Captured under [milestone-B-commercial-shopping/](milestone-B-commercial-shopping/) for shop, search, and cat-metabolic at all five viewports.

## Report

`storefront-acceptance/artifacts/playwright-report.json`
