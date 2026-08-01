# Milestone 0 — Foundations + Storefront Design System

**Status:** Complete — pending approval before Milestone A  
**Customer-visible changes:** None  
**Date:** 2026-08-01

## Summary

Milestone 0 establishes documentation scaffolds, expands the Playwright harness to five viewports, audits the product search results page, verifies blocksy-child synchronization, confirms biopentra-storefront version alignment, and defines the Storefront Design System (SDS) spec.

## 0.1 Foundations

### Documentation scaffold

| Path | Purpose |
|---|---|
| `changes/README.md` | Change record template and required fields |
| `changes/backups/` | Elementor `_elementor_data` pre-change JSON |
| `deployment/README.md` | Production replay procedure |
| `validation/README.md` | Playwright + QA artifact layout |

### Playwright harness (storefront-acceptance)

Expanded from 3 to **5 viewport projects**, aligned with audit convention:

| Project | Size |
|---|---|
| `mobile-360` | 360×800 |
| `mobile-390` | 390×844 |
| `mobile-430` | 430×932 |
| `tablet-768` | 768×1024 |
| `desktop-1440` | 1440×1000 |

Renamed `chromium-desktop` → `desktop-1440` (1440×1000, was 1440×900).

Added **search results** to `fixtures/pages.ts`:

- URL: `/?s=peptide&post_type=product`
- Gated: yes (Coming Soon bypass required)
- Expected H1 substring: `search results for peptide`

### Search results audit

Script: `audit/audit-search.mjs`  
Output: `audit/metrics-search.json`, `audit/shots-search/*.png`

| Finding | Detail |
|---|---|
| URL | `https://dev.biopentra.eu/?s=peptide&post_type=product` |
| Template | Blocksy parent `search.php` (no child override) |
| Card renderer | **Blocksy native** (`ul.products li.product`) — **not** canonical 3608 |
| Product count | 9 results for query `peptide` |
| Milestone owner | **B** (Commercial Shopping Experience) — migrate to 3608 in **C** |
| Search field on page | Header search only; no dedicated prominent search block |

See `metrics-search.json` for per-viewport `firstProductVh`, `firstProductTop`, overflow.

### blocksy-child synchronization

Verified per [DEV-SYNC.md](/opt/biopentra/dev/biopentra-blocksy-child/docs/DEV-SYNC.md):

```bash
diff <(docker exec wordpress cat .../style.css) dev/biopentra-blocksy-child/style.css   # clean
diff <(docker exec wordpress cat .../functions.php) dev/biopentra-blocksy-child/functions.php  # clean
```

Active theme: `blocksy-child` v1.0.0. Repo and live container are **in sync**. Theme is not bind-mounted; use DEV-SYNC procedure before Milestone D work.

Evidence: `validation/milestone-0-foundations/blocksy-child-sync.txt`

### Version drift (biopentra-storefront)

| Source | Version |
|---|---|
| Plugin header | 0.5.21 |
| `BIOPENTRA_STOREFRONT_VERSION` | 0.5.21 |
| `readme.txt` Stable tag | 0.5.21 |
| Symlink mirror (`biopentra-custom-plugins/plugins/`) | 0.5.21 |

Previously reported drift (Stable tag stuck at 0.5.17) was fixed in release 0.5.21. **No further code change required.**

## 0.2 Storefront Design System

| File | Purpose |
|---|---|
| `design-system/README.md` | Spacing, typography, buttons, cards, breakpoints, touch, z-index |
| `design-system/tokens.css` | CSS custom properties spec (not enqueued yet) |
| `design-system/image-guidelines.md` | Hero, card, PDP, lazy-load, srcset rules |

**Approval required** before Milestone A — see checklist in `design-system/README.md`.

## Files changed (version control)

| Repo | Paths |
|---|---|
| `storefront-acceptance` | `playwright.config.ts`, `fixtures/pages.ts`, `fixtures/budgets.json`, `README.md` |
| `biopentra-custom-plugins` | `docs/storefront-redesign/**` (mirror of VPS docs) |

## DB changes

None.

## WP-CLI commands

None (audit/search used read-only HTTP + existing share key).

## Acceptance results

```bash
cd /opt/biopentra/dev/storefront-acceptance
bash tools/run-dev.sh
# Expected: PASS — all pages × 5 viewports
```

## Production replay

Not applicable — Milestone 0 is docs + harness only. See `deployment/milestone-0-foundations.md`.

## Rollback

Remove scaffold files; revert Playwright config to previous commit in `storefront-acceptance`.

## Commit hashes

| Repo | Commit | Branch |
|---|---|---|
| `storefront-acceptance` | `bd40c82` | main |
| `biopentra-custom-plugins` | _(see docs commit below)_ | main |

Git mirror: `biopentra-custom-plugins/docs/storefront-redesign/` (canonical VPS path: `/opt/biopentra/docs/storefront-redesign/`).
