# Validation — V1A Design Specimen

**Date:** 2026-08-20  
**URL:** https://dev.biopentra.eu/  
**Suite:** Targeted only (not full `--milestone-e`)

## Iteration 1 (rejected)

Validated presence of injected `#bp-v1a-specimen` panel. That approach was **rejected by PO** — evidence retained under `screenshots/v1a/screenshots/` for history only.

## Iteration 2 (corrected)

Capture: `screenshots/v1a/iteration-2/capture-report.json`  
Tool: `storefront-acceptance/tools/v1a-iter2-capture.mjs`

| Check | Result |
|---|---|
| `#bp-v1a-specimen` absent | Pass |
| No “V1A Design Specimen” / “VIAL PHOTO” text | Pass |
| `body.bp-v1a-trial` present on homepage | Pass |
| Barlow Condensed on hero/section headings | Pass |
| Card / chip / button radius `4px` | Pass |
| Card `box-shadow: none` | Pass |
| Real hero / search / chips / cards visible | Pass |
| Search accepts input (`tirz`) | Pass |
| Quick-add control visible / clickable | Pass |
| Nested `<a>` count = 0 | Pass |
| No horizontal overflow @ tested widths | Pass |
| Nav @ **1024** collapsed | Pass |
| Nav @ **1025** horizontal | Pass |
| Page console errors from capture | None reported |
| Full Playwright matrix | **Not run** |

## PO gate

Visual approval of the **real** homepage required before any V1 freeze or V2 work.
