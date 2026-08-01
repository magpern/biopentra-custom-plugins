# Validation artifacts

Playwright reports, manual QA checklists, and before/after screenshots for each milestone.

## Layout

```
validation/
  milestone-0-foundations/
    blocksy-child-sync.txt       diff output from DEV-SYNC verification
    playwright-report.json       copy or symlink from storefront-acceptance/artifacts/
    screenshots/                   optional manual captures
  milestone-A-home/
    before/                        full-page PNGs per viewport (pre-change)
    after/                         full-page PNGs per viewport (post-change)
    qa-checklist.md
```

## Playwright harness

Source: `/opt/biopentra/dev/storefront-acceptance/`

| Command | Purpose |
|---|---|
| `bash tools/run-dev.sh` | Assert against committed `fixtures/budgets.json` |
| `bash tools/run-dev.sh --capture-baseline` | Re-capture baselines after intentional layout change |
| `bash tools/run-prod.sh` | Production validation (no Coming Soon bypass) |

## Viewports (Milestone 0+)

| Project | Size |
|---|---|
| `mobile-360` | 360×800 |
| `mobile-390` | 390×844 |
| `mobile-430` | 430×932 |
| `tablet-768` | 768×1024 |
| `desktop-1440` | 1440×1000 |

## Audit baseline (immutable)

Raw pre-redesign evidence: [../audit/](../audit/) — do not overwrite `metrics.json` or `metrics2.json`.

Search audit added in Milestone 0: [../audit/metrics-search.json](../audit/metrics-search.json).
