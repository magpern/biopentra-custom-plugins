# Validation — V1A Design Specimen

**Date:** 2026-08-20  
**URL:** https://dev.biopentra.eu/  
**Suite:** Targeted only (not full `--milestone-e`)

## Checks

| Check | Result |
|---|---|
| Specimen `#bp-v1a-specimen` renders on homepage | Pass |
| Barlow body + Barlow Condensed H1 computed styles | Pass (see `screenshots/capture-report.json`) |
| Primary `#5980a6`, button radius `4px` | Pass |
| No horizontal overflow @ 360–1680 | Pass |
| Nav ≤1024 collapsed (toggle visible, wrapper `display:none`) @ **1024** | Pass |
| Nav >1024 horizontal (toggle hidden, heading visible) @ **1025** | Pass |
| Template 3608 / loop-card CSS untouched | Pass (by inspection / git) |
| Elementor header/footer JSON untouched | Pass |
| Full Playwright matrix | **Not run** (per V1A brief) |

## Screenshots

Directory: [`screenshots/`](v1a-design-specimen/screenshots/)

Capture tool: `storefront-acceptance/tools/v1a-capture.mjs` via Playwright Docker image.

## Notes

- Page cache may require `?v1a=1` or hard refresh to see the panel immediately after deploy.
- Desktop mega-menu may appear open in some captures if Information was focused; nav contract metrics in `capture-report.json` are authoritative for 1024/1025.
- Card preview used placeholder “VIAL PHOTO” when no non-fixture product image was resolved — sufficient for geometry/type judgment.

## PO gate

Visual approval required before any V1 freeze or V2 work.
