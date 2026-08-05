# Storefront Mobile-First Redesign — Definitive Roadmap

**Status:** Phase 0 (Foundations) formally released and closed. Phase 1
(Product Card Clickability) in progress.
**Site:** `dev.biopentra.eu` first → replay on `www.biopentra.eu` per phase.  
**Audit baseline:** [audit/](audit/) (metrics unchanged; evidence preserved).

## Sequencing (2026-08-01 — supersedes milestone A–F ordering below)

Per Product Owner direction, execution order reverts to the original
numbered phases **0 → 1 → 2 → … → 8**. The Milestone A–F grouping and the
"Old phase → new milestone" traceability table further down this document
remain as **reference material** (they still describe useful groupings and
rationale for the underlying work) but **no longer govern execution order**.
In particular, card clickability (documented below as "old Phase 1 → new
Milestone C, deferred until after A/B") is **not deferred** — it is the
current active phase. Milestone 0's SDS approval checklist is **not** a
gate for Phase 1; it only gates milestone-A-style visual/layout work
(home/shop page redesign), which Phase 1 does not touch.

- **Phase 0 — Foundations:** formally released and closed.
- **Phase 1 / Milestone C — Canonical Product Card:** COMPLETE — `biopentra-loop-card` **v1.6.0**.
- **Milestone D:** COMPLETE / tagged (2026-08-04) — storefront `storefront-v0.8.0`, loop-card `v1.6.1`, child `v1.1.0`. Frozen baseline for Milestone E; no production replay.
- **Milestone E:** **APPROVED / tagged** (2026-08-04/05) — storefront `storefront-v0.9.0`.  
- **Milestone P0:** **PLAYBOOK READY** — [plans/MILESTONE_P0_PRODUCTION_ROLLOUT.md](plans/MILESTONE_P0_PRODUCTION_ROLLOUT.md). Production deploy not started. Do not begin execution until explicitly prompted.
- **Milestone F onward:** not started; do not begin until explicitly directed.
- **Phase 2–8:** unchanged, not started; scope per the milestone sections
  below (treat "Milestone B" ≈ old Phase 2/4, "Milestone D" ≈ old Phase 7,
  etc., per the traceability table).

## Design principle

**Commercial intent always wins.** On every commercial page (home, shop, category, search, WooCommerce archive), visitors reach **search → category shortcuts → products** immediately. Scientific and SEO content is **never removed** — it is **repositioned below** the commercial layer or placed in accessible expandable sections.

DOM order = visual order = screen-reader order. **No CSS `order`** to fake hierarchy.

---

## Executive summary (from audit — unchanged)

| Surface | Mobile first product @ 360×800 | Card renderer | Commercial gap |
|---|---|---|---|
| Home (4444) | **~3.1 viewports** | Elementor 3608 + loop-card | Weakest page — editorial-first |
| Shop (3755) | **~1.5 viewports** | Elementor 3608 + loop-card | Hero/trust before search/chips |
| WC archive | **~0.6 viewports** | Blocksy native | Wrong card; products early |
| SEO category pages | **0 products** | N/A | Pure essays — no grid |

Full measurements: [audit/metrics.json](audit/metrics.json), [audit/metrics2.json](audit/metrics2.json).

---

## Milestone map (A–F)

High-level groups replace the old numbered phases 0–8. Technical detail is preserved in sub-tasks below.

| Milestone | Name | Customer-visible outcome |
|---|---|---|
| **0** | Foundations + Design System | Harness, docs, tokens — no layout change |
| **A** | Commercial Homepage | Flagship storefront; products within ~1 viewport |
| **B** | Commercial Shopping Experience | Shop, search, category pages — products-first |
| **C** | Canonical Product Components | One card everywhere; whole-card click |
| **D** | Product Detail Experience | Sticky buy bar, gallery, price, stock, imagery |
| **E** | Mobile Polish | Touch targets, cookie/currency chrome |
| **P0** | Production Rollout | Replay A–E on production (deployment only) |
| **F** | *(legacy)* | Superseded by P0 |

### Old phase → new milestone (traceability)

| Old | New | Notes |
|---|---|---|
| 0 Foundations | **0** | Unchanged scope |
| 7 tokens (partial) | **0** SDS | **Moved earlier** — tokens defined before visual work |
| 5 Home v2 | **A** | **Moved to highest commercial priority** |
| 2 Shop v2 | **B** | |
| 4 Category pages v2 | **B** | Merged with shop — same commercial IA pattern |
| Search (audit gap) | **B** | New explicit sub-task |
| 1 Card clickability | **C** | **Deferred until after A** — home ships first |
| 3 Archive template | **C** | |
| Related / upsell HTML | **C** | **Expanded** — all recommendation surfaces |
| 7 PDP (partial) | **D** | **Expanded** — sticky bar, gallery, price, stock |
| Image sizing (scattered) | **D + cross-cutting** | **Dedicated image work package** |
| 6 Mobile chrome | **E** | After PDP sticky bar designed (z-index) |
| 8 Production replay | **F** | |

---

## Milestone 0 — Foundations + Storefront Design System

**Goal:** Everything needed before the first layout change. No customer-visible redesign yet.

### 0.1 Foundations (technical)

| Task | Owner | Deliverable |
|---|---|---|
| Docs scaffold | `docs/storefront-redesign/` | `changes/`, `deployment/`, `validation/` README templates |
| Playwright baseline | `storefront-acceptance` | Five viewports: 360, 390, 430, 768, 1440 |
| Search page audit | `audit/` | Add search results URL to fixtures + metrics |
| blocksy-child sync | `biopentra-blocksy-child` | Verify [DEV-SYNC.md](../../dev/biopentra-blocksy-child/docs/DEV-SYNC.md) |
| Version drift fix | `biopentra-storefront` | readme.txt / header consistency |

**Acceptance:** `baseline.spec.ts` green on dev; search page ownership documented.

**Effort:** ~2–3 days · **Model:** Composer (Cursor Agent)

### 0.2 Storefront Design System (SDS)

**Goal:** Lightweight, documented standards before any page is rebuilt.

Create **`docs/storefront-redesign/design-system/`** (spec only in milestone 0; CSS file lands in milestone C/D):

| Token category | Examples | Applied in |
|---|---|---|
| Spacing scale | `--bp-space-1` … `--bp-space-8` (4px base) | All phases |
| Typography | H1/H2/body/price/meta sizes mobile-first | A, B, D |
| Buttons | Primary, ghost, chip, icon — min 44×44 touch | A, B, C, D, E |
| Cards | Radius, shadow, padding, stretch-link rules | C |
| Image ratios | Hero 16:9 mobile / 21:9 desktop; card 1:1; PDP gallery max height | A, D, IMG |
| Breakpoints | **480 / 768 / 1024** (new CSS only) | All |
| Section spacing | Commercial block gaps vs editorial block gaps | A, B |
| Z-index stack | Cookie banner, sticky bar, mini-cart, overlay | E, D |

**Output files (planned):**

- `design-system/README.md` — human-readable spec
- `design-system/tokens.css` — canonical CSS custom properties (implemented in `biopentra-storefront/assets/css/bp-tokens.css` during milestone C)
- `design-system/image-guidelines.md` — hero height caps, srcset rules, lazy-load policy

**Acceptance:** Design review sign-off; no page changes until SDS is approved.

**Effort:** ~2–3 days · **Model:** Opus (architecture + token naming) or Sonnet with human review

---

## Milestone A — Commercial Homepage

**Goal:** Make `/` the flagship ecommerce entry — not an editorial landing page.

**Priority:** **Highest commercial milestone** — executes before canonical-card migration (milestone C). Home already uses template **3608**; milestone A is IA + search + imagery, not a new card renderer.

### Target mobile hierarchy (DOM order)

1. Header (existing TB 3782 — compact)
2. Compact hero — one H1 + one line value prop (**≤ ~40vh mobile**)
3. **Primary search** — large field, above the fold
4. **Category shortcut rail** — large touch-friendly chips/buttons
5. Featured products (loop grid → 3608)
6. Newest products (loop grid or second query)
7. Popular products (loop grid or third query)
8. Research collections (compact links to WC archives / SEO pages)
9. Scientific content — trust, “Why Biopentra”, compliance (accordion/disclosure)
10. FAQ (accordion)
11. Footer

**Success metric:** First product card top **≤ ~800px** on 360×800 (currently **2459px / 3.07vh** — [metrics.json](audit/metrics.json)).

### Search on homepage (primary navigation)

Evaluate and document in change record:

| Option | Scope |
|---|---|
| Larger search field | Elementor widget + SDS typography |
| Predictive / live search | Extend [shop-live-search.js](../../dev/biopentra-loop-card/assets/shop-live-search.js) to home |
| Category-aware suggestions | REST query scoped to `product_cat` |
| Mobile placement | Immediately below hero — not buried in header |

### Category shortcuts

| Option | Evaluate |
|---|---|
| Horizontal scroll chips | Best for 6–10 categories on mobile |
| 2×2 button grid | Better if ≤ 4 primary categories |
| Icon + label | Match SDS touch targets (44px min height) |

Link targets: **WC archive URLs** (`/product-category/...`) as primary commercial destinations; SEO pages as secondary “learn more”.

### Deliverables

| Item | Type |
|---|---|
| `biopentra-storefront/scripts/setup-home-page-v2-cli.php` | Idempotent WP-CLI |
| Home `_elementor_data` backup | `changes/backups/home-pre-A.json` |
| `home-ia.spec.ts` | Playwright — scroll budget, search visible, first product ≤ 1vh |
| Change + deployment records | `changes/phase-A-home.md`, `deployment/phase-A-home.md` |

**Repos / versions:** `biopentra-storefront` v0.6.0 (CLI + home search assets if needed)

**Effort:** ~5–8 days · **Model:** Composer — large Elementor tree surgery; human QA on 5 viewports

**Depends on:** Milestone 0 complete (SDS approved)

---

## Milestone B — Commercial Shopping Experience

**Goal:** Shop, search results, and SEO category pages follow the same commercial IA as home.

### Scope

| Surface | Current issue | Target |
|---|---|---|
| Shop (3755) | Search @ 1105px, products @ 1207px on mobile | Intro → search → chips → grid → compliance → description |
| Search results | Not audited | Products-first; canonical card (after C) |
| SEO category pages (4431+) | 0 products; 8–11 screens of copy | H1 → intro → **grid** → accordion SEO body |

### Search as primary navigation (site-wide)

| Enhancement | Milestone B task |
|---|---|
| Prominent search on shop | Reorder via `setup-shop-page-v2-cli.php` |
| Search results page audit + rebuild | New CLI or Blocksy/Elementor template |
| Predictive search | Shared module home + shop |
| Category-aware filtering in search | Query args preserved in URL for SEO |

### Shop reorder (unchanged technical intent)

Uses [setup-shop-page-v2-cli.php](../../dev/biopentra-loop-card/includes/) pattern — widgets `ed52b7f` (grid), `b3a2918` (filter).

**Repos / versions:** `biopentra-loop-card` v1.5.0–v1.7.0

**Effort:** ~5–7 days · **Model:** Composer

**Depends on:** Milestone A (home sets IA pattern + search module)

---

## Milestone C — Canonical Product Components

**Status:** COMPLETE (2026-08-03) — tag `biopentra-loop-card` **v1.6.0** @ `5260a84`  
**Artifact:** `biopentra-loop-card-1.6.0.zip`  
**Docs:** [changes](changes/milestone-C-canonical-cards.md) · [architecture](design-system/canonical-product-card-architecture.md) · [validation](validation/milestone-C-canonical-cards.md)

**Goal:** **Zero** page-specific card implementations. One renderer on every product listing surface.

### Canonical stack

```
Adapters → biopentra_loop_card_render_product_card() → Elementor template 3608 → loop-card.js/CSS → SDS
```

### Surfaces (shipped)

| Surface | Entry | Status |
|---|---|---|
| Home / shop / SEO grids | Elementor Loop Grid → 3608 | Verified |
| WC archives | `wc_get_template_part` adapter | Migrated |
| Product search | content-product adapter | Migrated |
| Related / upsells | content-product adapter | Migrated |
| Sidebar related research | Programmatic adapter | Migrated |

**Repos / versions:** `biopentra-loop-card` **v1.6.0** (frozen baseline). Storefront unchanged at **0.7.0**.

**Depends on:** Milestones A + B (complete)

---

## Milestone D — Product Detail Experience + Image System + SEO Content Ownership

**Status:** **COMPLETE / TAGGED** (2026-08-04). Spec: [plans/MILESTONE_D_IMPLEMENTATION.md](plans/MILESTONE_D_IMPLEMENTATION.md)  
**Tags:** `storefront-v0.8.0`, loop-card `v1.6.1`, child `v1.1.0` — see [deployment/milestone-D.md](deployment/milestone-D.md).  
**Validation:** 253 passed / 0 failed / 0 flaky — [validation/milestone-D.md](validation/milestone-D.md).  
**Frozen baseline for Milestone E. Production replay has not occurred.**  
**Implemented:** D3 → D1 → D2A → D2B.  
**Goal:** PDP optimized for mobile purchase; image discipline site-wide; SEO guide content ownership on pages (not plugin PHP).

### Milestone invariants

1. **Production gate (hard):** No production replay of Milestones **B**, **C**, or **D** until **D3 SEO Content Ownership** completes successfully.
2. **Test policy:** Targeted Playwright during implementation; full `--milestone-d` **exactly once** before tag.
3. **D2** stays one milestone package; implement internally as **D2A** then **D2B**.

### Implementation order

**D3 → D1 → D2A → D2B → `--milestone-d` once → tags**

### D3 — SEO Content Ownership

**Goal:** Move curated SEO guide product/content ownership from plugin PHP to page-owned data (`_bp_seo_grid_products`, `_bp_seo_archive_term`).

| Deliverable | Detail |
|---|---|
| Post meta | Ordered product slugs + optional WC archive term |
| Migration CLI | Idempotent copy from current PHP configs → page meta |
| Runtime | Meta first; brief deprecated PHP fallback during landing |
| Cleanup | Remove hardcoded product inventories from PHP before D tag |
| Out of scope | ACF/editor UI |

**Effort:** ~1–1.5 days · **Model:** Composer  
**Hard gate for:** production replay of B, C, and D

### D1 — Image system

Addresses oversized imagery (heroes, cards, PDP gallery, `sizes`/LCP/lazy, ship `bp-tokens.css`).

**Effort:** ~3–4 days · **Model:** Composer

### D2 — Product page commerce (D2A + D2B)

**D2A — Product Detail Layout:** gallery, images, price, stock, trust, disclaimer, related, upsells, responsive layout (~2–3 days, Composer).

**D2B — Sticky Purchase Experience:** sticky bar, variation/ATC sync, IntersectionObserver, a11y, safe-area, keyboard (~2–3.5 days; Opus for sync).

**Constraint:** Do not alter checkout logic, gateways, or cart AJAX.

**Repos / versions:** `biopentra-blocksy-child` **1.1.0**; `biopentra-storefront` **0.8.0**

**Depends on:** Milestone C (canonical related/upsell cards); D1 tokens for z-index/gallery caps

**Total Milestone D effort:** ~9–14 days

---

## Milestone E — Mobile Polish

**Status:** **COMPLETE / TAGGED** (2026-08-04). Spec: [plans/MILESTONE_E_IMPLEMENTATION.md](plans/MILESTONE_E_IMPLEMENTATION.md)  
**Release:** `biopentra-storefront` **0.9.0** (`storefront-v0.9.0`); child/loop-card unchanged  
**Validation:** `tools/run-dev.sh --milestone-e` — see [validation/milestone-E.md](validation/milestone-E.md)  
**Frozen baseline for Milestone F. Production replay has not occurred.**

**Goal:** Header, footer, cookie, currency — no conflicts with sticky commerce UI.

| Task | Detail |
|---|---|
| Header touch targets | Menu toggle 20×20 → 44×44 minimum |
| Header search | Compact 44×44 control: focus on-page commercial search, else navigate to shop |
| CookieYes | Banner/revisit → z 300; preference modal/backdrop → z 400 |
| Currency (Universal Multicurrency) | `umc_settings.display.placement` `sticky_footer` → **`manual`**; one `[universal_multicurrency_switcher]` in header 3782 |
| Footer disclosures | Compact; crawlable |
| Megamenu | Prefer links to WC archives over essay pages |

**Repos / versions:** `biopentra-storefront` **0.9.0** (primary); child **1.2.0** only if sticky coexistence CSS requires it — **not required**

**Effort:** ~4–5.5 days · **Model:** Composer

**Depends on:** Milestone D sticky bar (z-index stack finalized)

**Order:** E2 → E1 → E3 → E4 (`--milestone-e` once before tag)

---

## Milestone P0 — Production Rollout

**Status:** **PLAYBOOK READY** — not executed. Spec: [plans/MILESTONE_P0_PRODUCTION_ROLLOUT.md](plans/MILESTONE_P0_PRODUCTION_ROLLOUT.md)  
**Goal:** Safely replay the completed storefront redesign (Milestones A–E) on `www.biopentra.eu`.  
**Packages:** storefront **0.9.0**, loop-card **1.6.1**, child **1.1.0**  
**Nature:** Deployment only — no new product features.  
**Do not deploy** until an explicit execution prompt.

Order: packages → Elementor A → B → SEO migrate/validate → Elementor E → flush trio + Cloudflare → `run-prod.sh` + mobile smoke.

**Effort:** ~3.5–6.5 hours clean (+ buffer) · **Model:** Composer (ops)  
**Never:** copy dev DB to production.

---

## Milestone F — Production Deployment *(legacy name)*

**Superseded by Milestone P0.** Keep this heading for traceability only. Use [plans/MILESTONE_P0_PRODUCTION_ROLLOUT.md](plans/MILESTONE_P0_PRODUCTION_ROLLOUT.md).

---

## Work package merge/split rationale

| Change | Why |
|---|---|
| **Home moved before card work** | Audit S2: 3.1vh to first product — largest conversion gap; shop already ~1.5vh |
| **SDS extracted to milestone 0** | Prevents inconsistent spacing/imagery across A→E; replaces “tokens in phase 7” |
| **Old phases 2+4 → milestone B** | Same commercial IA pattern; one shopping-experience milestone |
| **Old phase 1 → milestone C** | Card code exists; migration matters after flagship pages defined |
| **Images → dedicated D1 package** | Original requirement; spans hero/card/PDP — needs explicit owner |
| **Old phase 6 → milestone E** | Chrome polish depends on sticky bar/cookie z-index from D |
| **Upsells/cross-sells added to C** | User requirement: no page-specific card anywhere |

---

## Documentation (unchanged strategy, improved layout)

```
docs/storefront-redesign/
  README.md                 ← index + conventions
  ROADMAP.md                ← this file (definitive milestone order)
  design-system/            ← SDS spec (milestone 0)
  audit/                    ← baseline evidence (immutable)
  changes/                  ← one record per shipped change
  deployment/               ← production replay records
  validation/               ← Playwright + manual QA
```

**Required fields per change** (unchanged): URL, page ID, template ID, component owner, previous/new state, files, selectors, settings, DB changes, export artifacts, WP-CLI commands, cache steps, screenshots, acceptance results, production replay, rollback, commit hash.

Deployment record naming: `phase-A-home.md`, `phase-B-shop.md`, … (replacing old `phase-N-*`).

---

## SEO, accessibility, performance (unchanged commitments)

Preserved from audit plan — see [README.md](README.md) and audit architecture section:

- Preserve WC archive URLs, pagination, filters, breadcrumbs, structured data
- Crawlable accordion content (not JS-only)
- One tab stop per card + separate quick-add control
- Zero horizontal overflow; tightening scroll budgets per milestone in `fixtures/budgets.json`

---

## Effort summary

| Milestone | Effort (dev days) | Cumulative |
|---|---|---|
| 0 Foundations + SDS | 4–6 | 4–6 |
| A Commercial Homepage | 5–8 | 9–14 |
| B Commercial Shopping | 5–7 | 14–21 |
| C Canonical Components | 6–10 | 20–31 |
| D PDP + Images | 7–10 | 27–41 |
| E Mobile Polish | 3–5 | 30–46 |
| F Production (all) | 4–6 | 34–52 |

Estimates assume one implementer + review; Theme Builder archive work (C) is the highest variance.

---

## Recommended implementation model

| Milestone | Model | Rationale |
|---|---|---|
| 0 Foundations | **Composer / Cursor Agent** | Harness + docs scaffolding |
| 0 SDS | **Opus** (or Sonnet + design review) | Token architecture, cross-cutting standards |
| A Homepage | **Composer** | Elementor CLI surgery; iterative Playwright |
| B Shopping | **Composer** | Repeatable IA pattern from A |
| C Archives + parity | **Opus** | Theme Builder, SEO, pagination risk |
| D PDP sticky bar | **Opus** | Blocksy + WC + variation selector + z-index |
| D Images | **Composer** | CSS + Elementor settings; SDS-driven |
| E Polish | **Composer** | Settings + CSS |
| F Production | **Composer** | Ops playbook execution |

---

## Risks (updated)

1. **Home before card acceptance** — home uses 3608; card clickability should still pass `card-interaction.spec.ts` before milestone C sign-off (can run in parallel during A QA).
2. **Search results ownership** — must complete in milestone 0 audit before B implementation.
3. **SEO vs archive coexistence** — keyword/H1 map before B category page copy changes.
4. **Image caps on home hero** — balance commercial speed vs brand presence; approve in SDS review.
5. **blocksy-child not bind-mounted** — sync before milestone D.
6. **CookieYes + sticky bar** — z-index defined in SDS before D implementation.

---

## Recommendation

**The roadmap is ready for implementation** after milestone 0 sign-off.

### First implementation task (after approval)

1. **Milestone 0.1 — Foundations:** scaffold `changes/`, `deployment/`, `validation/`; extend Playwright to five viewports; audit search results page; verify blocksy-child sync.
2. **Milestone 0.2 — Storefront Design System:** write `design-system/README.md`, `tokens.css` spec, and `image-guidelines.md`; obtain design approval.

**Do not start milestone A (homepage layout) until SDS is approved.**

Immediately after 0.2: **Milestone A — Commercial Homepage** (`setup-home-page-v2-cli.php` + primary search + category rail + product grids within ~1 viewport).
