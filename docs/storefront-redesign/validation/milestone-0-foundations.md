# Milestone 0 — Validation record

**Date:** 2026-08-01  
**Harness:** `/opt/biopentra/dev/storefront-acceptance`

## Playwright baseline

| Item | Value |
|---|---|
| Command | `bash tools/run-dev.sh --baseline-only` |
| Result | **PASS** — 55/55 tests (2026-08-01) |
| Viewports | 5 (360, 390, 430, 768, 1440) |
| Pages | 11 (includes new `search`) |
| Baseline file | `fixtures/budgets.json` |
| Report | `playwright-report.json` (this directory) |

### Capture command (if re-baselining)

```bash
bash tools/run-dev.sh --capture-baseline
```

## blocksy-child sync

Verified 2026-08-01 — see `blocksy-child-sync.txt` in this directory.

## Search audit

| Artifact | Path |
|---|---|
| Metrics | `../audit/metrics-search.json` |
| Screenshots | `../audit/shots-search/` |
| Script | `../audit/audit-search.mjs` |

## SDS approval checklist

Before Milestone A, confirm in `design-system/README.md`:

- [ ] Spacing scale
- [ ] Typography scale
- [ ] Card 1:1 ratio
- [ ] Hero 40vh mobile cap
- [ ] Z-index stack
- [ ] Breakpoints 480/768/1024

## Manual QA

| Check | Result |
|---|---|
| No Elementor/page layout changes on dev | Pass |
| Search page loads with Coming Soon bypass | Pass |
| SDS files readable and complete | Pass |
| Audit metrics.json unchanged | Pass |

## Screenshots

Milestone 0 does not change storefront layout. Reference baseline audit:

- `../audit/shots/` — pre-redesign full-page captures
- `../audit/shots-search/` — search results (Milestone 0 addition)

## Report artifacts

After `run-dev.sh`:

- `storefront-acceptance/artifacts/playwright-report.json`
- `storefront-acceptance/artifacts/playwright-html/`
