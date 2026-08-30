# BioPentra Storefront UI Overhaul — Program Closure

**Status:** **CLOSED / PO-APPROVED ON DEV**  
**Closure date:** 2026-08-23  
**Program end:** Milestone **M10** (no M11 as continuation of this program)

This document is the **authoritative closure record** for the Premium Ecommerce numbered track (**M1–M10**). For day-to-day implementation history, see root [`CHANGELOG.md`](../../CHANGELOG.md). For the superseded lettered track (0, A–E, P0), see historical material in [`ROADMAP.md`](ROADMAP.md) and [`README.md`](README.md) — that track is **not** the active program that ended here.

---

## A. Program verdict

**BIOPENTRA STOREFRONT UI OVERHAUL: CLOSED / PO-APPROVED ON DEV**

The planned redesign program ended with **M10 — Contact Page Experience Redesign**. There is **no M11** planned as a continuation of this program.

**Motion & Interaction Polish** is now specified as a separate initiative ([MOTION-1](plans/MOTION-1_STOREFRONT_MOTION_SYSTEM.md); plan frozen 2026-08-30). It is **not** unfinished M11 work and is **not** part of this program’s original closure scope.

**Production:** The UI overhaul is closed on DEV. It has **not** been deployed to production by this closure. Production rollout requires a **separate explicit Product Owner GO**.

---

## B. Scope completed (M1–M10)

| Milestone | Name / scope | Final status | Storefront version / tag | Companion / notes |
|---|---|---|---|---|
| **M1** | Responsive Premium Header / Navigation — Broadsheet palette, left mobile drawer, desktop utility chrome, Information mega in drawer | **PO-approved / frozen on DEV** | `0.9.14` · `storefront-v0.9.14` | Post-freeze header mega correctives in `0.9.31`–`0.9.32` (folded into final `0.9.33` baseline; do not re-freeze M1) |
| **M2** | Premium Ecommerce Homepage Hero — compact commercial hero, 1240 rail alignment | **PO-approved / frozen on DEV** | `0.9.15` · `storefront-v0.9.15` | Plan: `dev/biopentra-storefront-redesign/plans/M2-PREMIUM-HOMEPAGE-HERO.md` |
| **M3** | Homepage Category Discovery — chip rail under hero, Uncategorized excluded on home | **PO-approved / frozen on DEV** | `0.9.16` · `storefront-v0.9.16` | Plan: `dev/biopentra-storefront-redesign/plans/M3-HOMEPAGE-CATEGORY-DISCOVERY.md` |
| **M4** | Premium Product Cards + Homepage Product Sections — Featured/Newest/Popular rhythm | **PO-approved / frozen on DEV** | `0.9.17` · `storefront-v0.9.17` | `biopentra-loop-card` **1.6.2** / `v1.6.2` (desktop corrective: loop-card **1.6.3** — see redesign `changes/m4-desktop-product-card-corrective.md`) |
| **M5** | Trust & Fulfillment Story — editorial trust band on homepage | **PO-approved / frozen on DEV** | `0.9.20` · `storefront-v0.9.20` | Plan: `dev/biopentra-storefront-redesign/plans/M5-TRUST-FULFILLMENT-STORY.md` |
| **M6** | Brand Story & Ordering Confidence — Why BioPentra, confidence strip, FAQ accordion | **PO-approved / frozen on DEV** | `0.9.23` · `storefront-v0.9.23` | Plan: `dev/biopentra-storefront-redesign/plans/M6-BRAND-STORY-ORDERING-CONFIDENCE.md` |
| **M7** | Research & Product Guidance — light editorial guidance hub on homepage | **PO-approved / frozen on DEV** | `0.9.25` · `storefront-v0.9.25` | Plan: `dev/biopentra-storefront-redesign/plans/M7-RESEARCH-PRODUCT-GUIDANCE.md` |
| **M8** | Global Footer Redesign — Direction B dark footer, email machinery removed | **PO-approved / frozen on DEV** | `0.9.27` · `storefront-v0.9.27` | Plan: [plans/MILESTONE_M8_GLOBAL_FOOTER_REDESIGN.md](plans/MILESTONE_M8_GLOBAL_FOOTER_REDESIGN.md) · Backup: [changes/backups/footer-pre-M8.json](changes/backups/footer-pre-M8.json) |
| **M9** | Shop Discovery Consolidation — unified search + in-place category filter rail, frozen hero | **PO-approved / frozen on DEV** | `0.9.30` · `storefront-v0.9.30` | `biopentra-loop-card` **1.6.4** · Plan: [plans/MILESTONE_M9_SHOP_DISCOVERY.md](plans/MILESTONE_M9_SHOP_DISCOVERY.md) · Change: [changes/m9-shop-discovery.md](changes/m9-shop-discovery.md) |
| **M10** | Contact Page Experience Redesign — Reason-driven form, channels rail, Chat/Telegram | **PO-approved / frozen on DEV** | `0.9.33` · `storefront-v0.9.33` | `universal-telegram` **0.8.1** (no UT tag) · Plan: [plans/MILESTONE_M10_CONTACT_PAGE_REDESIGN.md](plans/MILESTONE_M10_CONTACT_PAGE_REDESIGN.md) · Change: [changes/m10-contact-page.md](changes/m10-contact-page.md) |

**M1–M7** plans and many change records live in the sibling repo `dev/biopentra-storefront-redesign/` (same VPS path). **M8–M10** plans and change records live in this repo under `docs/storefront-redesign/`.

---

## C. Final DEV baseline (verified 2026-08-23)

| Artifact | Value |
|---|---|
| **Final Storefront source baseline** | **`storefront-v0.9.33`** |
| Storefront version | `0.9.33` |
| Storefront commit | `5e59babe725efc86065c2f3ca27416d8e20d4138` |
| `biopentra-custom-plugins` branch | `main` (synced with `origin/main` at closure) |
| Universal Telegram version | `0.8.1` |
| Universal Telegram commit (`universal_telegram_chat_is_enabled`) | `7b4b52be274dc1a7425d3f2f5f2be2b096e627f8` (on `universal-telegram` `main`; HEAD `a126a85` includes this commit) |
| Universal Telegram tag | **None** for this companion bump |
| `storefront-acceptance` checkpoint | `e98c5e1607e21d9fb2a9198c19dc88923d9c04e0` — M10 specs + `--m10-only` runner |
| M1–M10 on DEV | **PO-approved / frozen** |
| Environment | **DEV only** (`dev.biopentra.eu`) — production not modified by M10 or this closure |

Deploying **only** `storefront-v0.9.33` does **not** by itself reproduce every historical Elementor mutation, companion plugin, or ops prerequisite across M1–M10. Use the production-replay index below and per-milestone change records.

---

## D. Global surfaces (header / mega menu / footer)

The completed program includes final approved **global chrome** on DEV:

| Surface | Milestone | Frozen baseline | Notes |
|---|---|---|---|
| Header / navigation | M1 | `storefront-v0.9.14` (+ correctives through `0.9.33`) | Template **3782** · mobile left drawer · desktop utility row |
| Desktop Information mega menu | M1 + E1.2 lineage + `0.9.31`/`0.9.32` | Folded into `0.9.33` | **Interaction contract (high level):** desktop **hover** opens; panel is **overlay / out-of-flow** (absolute, not document-flow push); **hover-handoff bridge** (`bp-header-mega-hover.js`) for stable pointer path from title into panel; **no layout shift** when opening |
| Global footer | M8 | `storefront-v0.9.27` (content/CSS in `0.9.33` tree) | Template **3823** · Direction B dark endpoint |

---

## E. Homepage (M2–M7)

Homepage redesign sections are **complete and frozen** on DEV. Ownership by milestone:

| Section | Milestone |
|---|---|
| Hero | M2 |
| Category discovery rail | M3 |
| Featured / Newest / Popular product stacks | M4 |
| Trust & fulfillment story | M5 |
| Brand story, confidence strip, ordering FAQ | M6 |
| Research & product guidance hub | M7 |

Later work should **not** casually reopen these sections for visual tweaking. Defects or regressions may be corrected narrowly; deliberate redesign requires a **new project/milestone**.

---

## F. Shop (M9)

**M9 — Shop Discovery Consolidation** is frozen on DEV.

| Topic | Record |
|---|---|
| Freeze tag | `storefront-v0.9.30` |
| Hero | **Preserved / approved** — Elementor `61f84d4` / `.bp-shop-hero` unchanged by M9 freeze |
| Discovery | Unified search + M3-styled category chips filtering loop `ed52b7f` in place (Path A: Elementor taxonomy-filter `b3a2918` retained as engine) |
| Responsive rail | ≥1025 compact row; ≤1024 horizontal scroll; 360–390 partial next-chip affordance |
| Product display | Loop template **3608** + `biopentra-loop-card` **1.6.4** |
| DEV ops prerequisite | Anonymous Shop access required **WooCommerce Coming Soon disabled** on DEV — **operational prerequisite**, not a production deployment decision (Ops A in M9 plan) |

---

## G. Contact (M10)

**M10 — Contact Page Experience Redesign** is frozen on DEV.

| Topic | Implementation |
|---|---|
| Freeze tag | `storefront-v0.9.33` |
| Interaction model | **Reason for contact** is the sole mode switch; mode cards and bottom Request Product band removed |
| Chat channel | Dynamic row based on **site-level** `chat_widget_enabled` via `universal_telegram_chat_is_enabled()` — not per-user on the Contact page |
| Open Chat | Universal Telegram owns post-click eligibility, authentication, and widget UI (`UniversalTelegramChat.open` / `universal-telegram:open-chat`) |
| Telegram | Retained (`@biopentra`) |
| Product request | Conditional `requested_product` field; `?prefill=product_request` preserved (Storefront `contact-v2.js`) |
| Related order | `wc_related_order` with submit-time ownership validation |
| Mobile | Purpose-built compact channels-above-form composition |
| Hero | Frozen `ef97090` preserved |

---

## H. Motion & Interaction Polish

Specified as a **separate initiative** — **[MOTION-1: Storefront Motion System](plans/MOTION-1_STOREFRONT_MOTION_SYSTEM.md)**. Plan frozen 2026-08-30; implementation is on `feature/motion-1-storefront-motion-system` (plugin header remains `0.9.39`). Not M11. Not a reopen of M1–M10. Not part of this program’s original closure scope. Version bump, tag, DEV bind-mount switch, and production replay remain after PO visual review.

---

## I. Known non-blocking issues

Issues still relevant, supported by repository evidence, not resolved by this closure:

| Issue | Evidence | Disposition |
|---|---|---|
| **No consolidated deterministic M1–M10 production replay** | Per-milestone deployment/change records exist; no single runbook replays all Elementor/CLI/companion steps | Documented in §J; future ops work |
| **Stale `biopentra-storefront-redesign` STATUS.md** | Still stops at M7 / “do not start M8” | Historical sibling repo; **authority is this closure doc + `CHANGELOG.md`** (see M9/M10 plan convention notes) |
| **M8 desktop footer defects (PO-approved at freeze)** | [`CHANGELOG.md` `[0.9.27]`](../../CHANGELOG.md) — stacked/invisible Telegram handle, invisible “Information”/“Legal” labels on desktop | Optional follow-up; not a blocker to program closure |
| **Acceptance runner result line vs exit code** | `tools/run-dev.sh` prints `== result: PASS/FAIL` from `PW_EXIT`; PO M10 validation also ran `M10_CHAT_DISABLED=1` (52 passed — Open Chat tests skipped). If a run ever shows `FAIL` with shell exit `0`, treat as **tooling cleanup** — not a UI-overhaul blocker | Do not fix in closure task |
| **GitHub Actions Release ZIP billing** | `dev/biopentra-storefront-redesign/changes/m6-freeze-closure.md` — tag pushed, ZIP build may fail on billing | Ops; tags remain source of truth |

Do not treat resolved historical issues (e.g. M9 Coming Soon on DEV, desktop mega regression before `0.9.31`) as open closure blockers.

---

## J. Production status

| Statement | Fact |
|---|---|
| UI overhaul closed on DEV | **Yes** — M1–M10 PO-approved / frozen |
| UI overhaul deployed to production | **No** — not authorized by M10 or this closure |
| Production rollout | **Separate operational activity** requiring explicit PO GO |

Do not conflate **design/program closure** with **production deployment completion**.

---

## K. Production-replay index

**There is no single consolidated production replay runbook for M1–M10.** Production rollout must account for **code releases**, **Elementor/CLI mutations**, **companion plugins**, **ops prerequisites**, and **acceptance** per milestone.

### Final deploy anchor

| Deploy | Version / tag |
|---|---|
| **Storefront (UI overhaul final)** | **`biopentra-storefront` `0.9.33`** · Release ZIP from tag **`storefront-v0.9.33`** |
| Loop card (M4/M9 surfaces) | **`biopentra-loop-card` `1.6.4`** (verify M4 corrective `1.6.3` only if rolling back cards) |
| Universal Telegram (M10 Chat seam) | **`universal-telegram` `0.8.1`** (commit `7b4b52b` line; no UT tag — install from repo/release per UT conventions) |

### Every production replay

1. Install required Release ZIP(s) for the milestone(s) being replayed
2. Run idempotent WP-CLI script(s) — **slug/option lookups**, never hardcoded DEV post IDs
3. Apply documented admin settings
4. **Flush trio:** `wp elementor flush-css` · `wp cache flush` · Cloudflare purge for affected paths
5. Run acceptance: `dev/storefront-acceptance/tools/run-prod.sh` or targeted milestone flags
6. Complete QA tables in deployment records

### Authoritative per-milestone records

| Milestone | Plan / change (primary) | Deployment replay | Acceptance |
|---|---|---|---|
| M1 | `dev/biopentra-storefront-redesign/plans/M1-PREMIUM-HEADER.md` · `changes/m1-premium-header.md` | `dev/biopentra-storefront-redesign/deployment/m1-premium-header.md` | `tests/m1-header.spec.ts` · `tests/chrome-header.spec.ts` |
| M2 | `dev/biopentra-storefront-redesign/plans/M2-PREMIUM-HOMEPAGE-HERO.md` · `changes/m2-premium-homepage-hero.md` | CLI `setup-m2-*` in storefront scripts (see change record) | `tests/m2-hero.spec.ts` |
| M3 | `dev/biopentra-storefront-redesign/plans/M3-HOMEPAGE-CATEGORY-DISCOVERY.md` · `changes/m3-homepage-category-discovery.md` | `setup-m3-homepage-category-cli.php` | `tests/m3-category.spec.ts` |
| M4 | `dev/biopentra-storefront-redesign/plans/M4-PREMIUM-PRODUCT-CARDS.md` | loop-card release + storefront `0.9.17` | `tests/m4-cards.spec.ts` |
| M5 | `dev/biopentra-storefront-redesign/plans/M5-TRUST-FULFILLMENT-STORY.md` · `changes/m5-trust-fulfillment-story.md` | `setup-m5-trust-fulfillment-cli.php` | `tests/m5-trust.spec.ts` |
| M6 | `dev/biopentra-storefront-redesign/plans/M6-BRAND-STORY-ORDERING-CONFIDENCE.md` | `setup-m6-brand-story-cli.php` | `tests/m6-brand.spec.ts` |
| M7 | `dev/biopentra-storefront-redesign/plans/M7-RESEARCH-PRODUCT-GUIDANCE.md` · `changes/m7-research-product-guidance.md` | `setup-m7-research-guidance-cli.php` | `tests/m7-guidance.spec.ts` |
| M8 | [plans/MILESTONE_M8_GLOBAL_FOOTER_REDESIGN.md](plans/MILESTONE_M8_GLOBAL_FOOTER_REDESIGN.md) | `setup-m8-global-footer-cli.php` · backup `changes/backups/footer-pre-M8.json` | `tests/m8-footer.spec.ts` |
| M9 | [plans/MILESTONE_M9_SHOP_DISCOVERY.md](plans/MILESTONE_M9_SHOP_DISCOVERY.md) · [changes/m9-shop-discovery.md](changes/m9-shop-discovery.md) | `setup-m9-shop-discovery-cli.php` · backup `changes/backups/shop-pre-M9.json` | `tests/m9-shop-discovery.spec.ts` |
| M10 | [plans/MILESTONE_M10_CONTACT_PAGE_REDESIGN.md](plans/MILESTONE_M10_CONTACT_PAGE_REDESIGN.md) · [changes/m10-contact-page.md](changes/m10-contact-page.md) | `setup-m10-contact-page-cli.php` · backup `changes/backups/contact-pre-M10.json` | `tests/m10-contact.spec.ts` · `--m10-only` |

**Lettered track (historical, predates M-track):** [deployment/](deployment/) — milestones A–E, P0 playbook [plans/MILESTONE_P0_PRODUCTION_ROLLOUT.md](plans/MILESTONE_P0_PRODUCTION_ROLLOUT.md). Production replay of A–E was **never executed**; P0 remains on hold.

**Header mega correctives (`0.9.31`–`0.9.32`):** Included in `0.9.33` code; production may need `setup-header-mega-menu-desktop-restore-cli.php` if replaying from an older header baseline — see [`CHANGELOG.md` `[0.9.31]`](../../CHANGELOG.md).

---

## L. Future change governance

| Rule | Detail |
|---|---|
| Frozen baselines | **M1–M10** are frozen design baselines on DEV |
| Defects / regressions | Narrow corrective fixes allowed |
| New functionality or visual redesign | Open as a **new project/milestone** — do not silently modify a frozen milestone |
| Motion work | Separate initiative **MOTION-1** — plan frozen; implementation on `feature/motion-1-storefront-motion-system` (no version/tag): [plans/MOTION-1_STOREFRONT_MOTION_SYSTEM.md](plans/MOTION-1_STOREFRONT_MOTION_SYSTEM.md) |
| Production | Separate from redesign development; requires explicit PO GO |
| Authority | This document + root `CHANGELOG.md` + per-milestone plans/changes — not stale `biopentra-storefront-redesign/STATUS.md` |

---

## M. M10 validation (closure reference)

| Run | Result |
|---|---|
| Chat-enabled (`tools/run-dev.sh --m10-only`) | **59 passed** ([`CHANGELOG.md` `[0.9.33]`](../../CHANGELOG.md)) |
| Chat-disabled (`M10_CHAT_DISABLED=1`) | **52 passed** (Open Chat seam tests skipped across viewports; chat state restored to enabled after validation) |
| Acceptance repo commit | `e98c5e1` |

---

## Related documents

- [README.md](README.md) — entry point (updated for program closure)
- [ROADMAP.md](ROADMAP.md) — historical lettered track + sequencing notes
- [changes/README.md](changes/README.md) — change-record conventions
- [deployment/README.md](deployment/README.md) — production replay conventions
