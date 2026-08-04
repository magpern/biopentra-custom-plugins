# Milestone E1 — Header and Mobile Navigation

**Status:** COMPLETE on dev  
**Date:** 2026-08-04  

## Summary

Raised primary header controls to ≥44×44, inserted a compact search control with frozen focus/navigate behaviour, and kept a single commercial search system.

## Changes

| Item | Implementation |
|---|---|
| Menu toggle hit area | `chrome-v1.css` min 44×44 on `.e-n-menu-toggle` |
| Account trigger | min 44×44 + padding on `.biopentra-header-auth__trigger` |
| Search control | HTML widget in header **3782** (`data-bp-chrome-search`) |
| Search behaviour | `chrome-v1.js`: focus `#biopentra-shop-s` / `#biopentra-search-refine` if visible; else navigate to shop `#biopentra-shop-s` |
| Focus visibility | `:focus-visible` outlines on menu / account / search |
| UMC in header | Coordinated with E2 shortcode insert |

## CLI

```bash
wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-milestone-e-chrome-cli.php
```

Idempotent: skips UMC/search widgets when markers already present.

## Out of scope (preserved)

Homepage/shop IA, canonical cards, sticky-bar.js, checkout.

## Validation

Targeted: `tools/run-dev.sh --e1-only`
