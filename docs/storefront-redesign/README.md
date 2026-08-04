# Mobile-First Storefront Redesign

Working docs for the mobile-first restructure of the Biopentra WooCommerce storefront: `dev.biopentra.eu` first, replayed on `www.biopentra.eu` once each milestone is validated.

**Status:** Milestone D **tagged and released** (2026-08-04): `storefront-v0.8.0`, loop-card `v1.6.1`, child `v1.1.0`. Frozen baseline for Milestone E. **No production replay.**

**Definitive roadmap:** [ROADMAP.md](ROADMAP.md) — milestone order, effort, models, and work-package rationale.

**Audit evidence:** [audit/](audit/) — baseline metrics and screenshots (2026-07-31 / 2026-08-01); do not overwrite.

## Why

The storefront reads as scientific/editorial rather than commercial on mobile: homepage products sit **~3 viewports** down ([audit/metrics.json](audit/metrics.json)), SEO “category” pages have **zero products**, card renderers differ between shop and WooCommerce archives, and search is not treated as primary navigation.

**Principle:** Commercial intent always wins. Scientific and SEO content stays fully rendered — **below** products or in accessible disclosures. We are building an **ecommerce experience**, not a content website.

## Milestone structure (A–F)

| Milestone | Scope | Primary owner |
|---|---|---|
| **0** Foundations + **Storefront Design System** | Playwright harness, docs scaffold, SDS tokens/spec | multi-repo |
| **A** Commercial Homepage | Flagship `/` — search, category shortcuts, products within ~1 viewport | `biopentra-storefront` v0.6.0 |
| **B** Commercial Shopping Experience | Shop reorder, search results, SEO category grids | `biopentra-loop-card` v1.5.0–v1.7.0 |
| **C** Canonical Product Components | One card (3608) on **all** surfaces including related/upsell/cross-sell | `biopentra-loop-card` |
| **D** Product Detail + Image System + SEO Content Ownership | Sticky purchase, gallery, SDS images; page-owned SEO grids (**D3 hard production gate**) | [plans/MILESTONE_D_IMPLEMENTATION.md](plans/MILESTONE_D_IMPLEMENTATION.md) **FROZEN** |
| **E** Mobile Polish | Touch targets, cookie/currency placement, header/footer | `biopentra-storefront` v0.7.0 |
| **F** Production Deployment | Replay all deployment records on production | ops |

Each milestone is independently shippable, testable, and replayable. **Do not start a milestone before the previous one is accepted.** Tagging/deploying any plugin or theme release requires explicit product-owner approval.

### Legacy phase numbering

The original phases 0–8 map to milestones 0–F — see [ROADMAP.md § traceability](ROADMAP.md#old-phase--new-milestone-traceability). Key strategic changes:

- **Homepage (old phase 5) → milestone A** — highest commercial priority, before card migration
- **Card clickability (old phase 1) → milestone C** — after commercial pages defined
- **Design tokens (old phase 7) → milestone 0 SDS** — before any layout work
- **Images → milestone D1** — dedicated work package

## Ownership map

| Surface | Page/template ID (dev) | Owning repo | Milestone |
|---|---|---|---|
| Header | Elementor template 3782 | `biopentra-storefront` | E |
| Footer | Elementor template 3823 | `biopentra-storefront` | E |
| Home | Page 4444 | `biopentra-storefront` (`setup-home-page-v2-cli.php`) | **A** |
| Shop | Page 3755 | `biopentra-loop-card` (`setup-shop-page-v2-cli.php`) | B |
| Search results | TBD (audit in milestone 0) | TBD | B |
| Category SEO pages | 4431 + siblings | `biopentra-loop-card` | B |
| WC archives (`/product-category/*`) | Blocksy today → Theme Builder | `biopentra-loop-card` | C |
| Product card (**all surfaces**) | Elementor Loop item **3608** | `biopentra-loop-card` | C |
| Related / upsell / cross-sell | Custom HTML today → **3608** | `biopentra-loop-card` | C |
| Single product | Blocksy + Customizer | `biopentra-blocksy-child` | D |
| Cart / Checkout | Pages 82 / 4506 | `biopentra-blocksy-child` — **do not restructure checkout** | — |

Every DB-backed page is looked up by **slug or WooCommerce option** in CLI scripts — never hardcoded dev post IDs — so scripts replay on production.

## Storefront Design System (milestone 0)

Before any layout change, define lightweight standards in `design-system/`:

Spacing, typography, buttons, cards, image ratios, border radius, shadows, breakpoints (**480 / 768 / 1024**), touch targets (44px min), section spacing, CSS tokens, responsive behavior.

Canonical CSS file (when implemented): `biopentra-storefront/assets/css/bp-tokens.css`. Existing per-component namespaces (`--bp-loop-card-*`, etc.) remain until migrated during milestones C/D.

## Breakpoint convention

New/touched CSS uses **480 / 768 / 1024** only. Legacy breakpoints in untouched files are not mass-migrated — see `changes/` for files migrated per milestone.

## Documentation rule

**No undocumented manual editor change counts as complete.** Every change must be backed by (in order of preference):

1. Version-controlled theme/plugin code
2. Version-controlled template overrides
3. Exported Elementor templates/kits in a repo
4. Repeatable idempotent WP-CLI script
5. Precisely documented settings (admin path, before/after, screenshots)
6. Manual change only when no alternative — documented as if code

Never copy the development database to production.

## Backup and rollback conventions

Before any DB-backed CLI script:

```bash
cd /opt/biopentra/apps/wordpress
docker compose run --rm -T wpcli wp post meta get <page_id> _elementor_data \
  > /opt/biopentra/docs/storefront-redesign/changes/backups/<page-slug>-pre-<milestone>.json
```

Rollback = restore JSON via `wp post meta update`, delete Elementor CSS cache postmeta, flush trio. Code rollback = previous Release ZIP.

**Flush trio** after every DB/Elementor change:

```bash
cd /opt/biopentra/apps/wordpress
docker compose run --rm -T wpcli wp elementor flush-css
docker compose run --rm -T wpcli wp cache flush
# + Cloudflare path purge
```

## Production replay rules

1. Install release ZIP for the milestone
2. Run CLI script(s) on production (`wp eval-file`)
3. Apply documented settings with screenshots
4. Flush trio
5. Run `storefront-acceptance/tools/run-prod.sh`
6. Complete QA table in `deployment/phase-*.md`

See [ROADMAP.md § milestone F](ROADMAP.md#milestone-f--production-deployment).

## Directory guide

| Path | Purpose |
|---|---|
| [ROADMAP.md](ROADMAP.md) | Definitive milestone order, effort, models |
| [design-system/](design-system/) | SDS spec (milestone 0) |
| [audit/](audit/) | Baseline metrics/screenshots — preserved |
| [changes/](changes/) | Per-change records + `_elementor_data` backups |
| [deployment/](deployment/) | Production replay records |
| [validation/](validation/) | Playwright reports, manual QA |

Required fields per change record: URL, page ID, template ID, component owner, previous/new state, files, selectors, settings, DB changes, export artifacts, WP-CLI commands, cache steps, screenshots, acceptance results, production replay, rollback, commit hash.
