# Changelog

All notable changes to **`biopentra-storefront`** in this repository are documented here. Other plugins under `plugins/` may maintain their own changelogs when needed.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html) for the **storefront** package version.

## [Unreleased]

### Planned

- Future improvements only after production soak; see `docs/legacy-plugin-retirement-plan.md`.

---

## [0.9.32] — 2026-08-22

### Fixed

- **Information mega-menu hover handoff on non-home pages:** Header template 3782 includes a second container row (`f38da8b`, ~20px) below the nav on non-home pages (collapsed on home). Pointer entry on that row dropped `.e-n-menu-content:hover` and closed the panel. Disable pointer events on non-menu header rows while the Information mega is open.

---

## [0.9.31] — 2026-08-22

### Fixed

- **Desktop Information mega-menu regression:** M1 drawer CSS used `position: revert` on `.e-n-menu-content` at ≥1025, which resolved to UA `static` instead of Elementor's `absolute` overlay — panel opened in document flow and pushed the header/page. Restored absolute overlay in `bp-header-m1.css`, centered the panel under the Information nav item (instead of Elementor stretch anchoring it too far right), and reset header mega-menu `open_on` to `hover` via `setup-header-mega-menu-desktop-restore-cli.php`. Added desktop-only E1.2 hover-handoff patch (`bp-header-mega-hover.js` + CSS bridge) so moving from Information into the panel no longer closes on sibling titles or the title→panel gap. Mobile drawer behaviour unchanged.

### Notes

- Corrective only — header visual design/content unchanged. Awaiting PO review; does not re-freeze header milestone.

---

## [0.9.30] — 2026-08-22

### Fixed

- **M9 Shop desktop search alignment:** `b2srch0` has no `.e-con-inner`, so the 1240px rail never applied and search hugged the viewport left edge. Desktop now rails the search container to the same centered 1240 band as category chips; search spans that rail width. Mobile unchanged (PO-approved).

### Notes

- Acceptance: `storefront-acceptance` `tests/m9-shop-discovery.spec.ts` via `tools/run-dev.sh --m9-only` (44 passed / 0 failed).

### Release

- **M9 Shop Discovery Consolidation — PO-approved / frozen on DEV** (Path A filter architecture + visual correctives through `0.9.30`). Git tag: **`storefront-v0.9.30`**. Companion: **`biopentra-loop-card` `v1.6.4`**. Spec: [docs/storefront-redesign/plans/MILESTONE_M9_SHOP_DISCOVERY.md](docs/storefront-redesign/plans/MILESTONE_M9_SHOP_DISCOVERY.md). Production replay not performed — DEV only until a separate, explicit PO GO.

---

## [0.9.29] — 2026-08-22

### Fixed

- **M9 Shop discovery visual corrective (dev, untagged):** remove pale/grey discovery panel backgrounds around search + category rail; tighten hero→search→chips→grid vertical rhythm (including Elementor products wrapper `e56029b` padding/gap overrides); constrain search to a compact left-rail field with magnifier affordance and stronger focus/hover; desktop shop hero framing softened (`min-height` 280px + `center right` cover) so `cover` + Elementor `ypos -241` no longer over-magnifies glassware. Elementor taxonomy-filter `b3a2918` / Path A architecture unchanged. No product-card / loop-card / M1–M8 homepage changes.

### Notes

- Acceptance: `tools/run-dev.sh --m9-only`. Visual correctives continued in `0.9.29` / `0.9.30`; freeze recorded on `0.9.30`.

---

## [0.9.28] — 2026-08-22

### Added

- **M9 — Shop Discovery Consolidation (dev, untagged):** one Shop discovery band — search + M3-styled category chips that filter loop `ed52b7f` in place. WP3 Path A: keep Elementor taxonomy-filter `b3a2918` as the supported filter engine; remove duplicate archive-chip section `b2cats0`; hide Uncategorized from customer-facing chips; keep All; inject “Shop by category” label. Idempotent CLI `scripts/setup-m9-shop-discovery-cli.php`. Backup: `docs/storefront-redesign/changes/backups/shop-pre-M9.json`. Spec: [docs/storefront-redesign/plans/MILESTONE_M9_SHOP_DISCOVERY.md](docs/storefront-redesign/plans/MILESTONE_M9_SHOP_DISCOVERY.md).

### Changed

- Shop search keeps `[biopentra_shop_search]` / `#biopentra-shop-s` / live-search; adopts M3 discovery visual language only (`shop-v2.css` under `body.bp-shop-m9`).
- Responsive: ≥1025 single compact chip row; ≤1024 horizontal scroll; 360–390 partial next-chip affordance.

### Notes

- Acceptance: `storefront-acceptance` `tests/m9-shop-discovery.spec.ts` via `tools/run-dev.sh --m9-only` (44 passed / 0 failed).
- Requires companion **`biopentra-loop-card` 1.6.4**. Ops A (Coming Soon disable) remains a separate DEV ops prerequisite.
- Freeze / PO approval recorded on **`0.9.30`** / **`storefront-v0.9.30`**.

---

## [0.9.27] — 2026-08-22

### Fixed

- **M8 mobile density corrective (dev, untagged):** removed excessive vertical whitespace between the Telegram row and the Information/Legal navigation grid, mobile-only. Root cause: two leftover Elementor per-post custom-CSS rules from the pre-M8 light-background design — `flex-direction: column` on `.bp-ft-v2-contact-group` and a direct dark `color: #0f1f33` on `.bp-ft-v2-contact-line` — were stacking "Telegram" above an invisible "@biopentra" handle on two lines, inflating that widget from ~44px to ~64px and pushing the nav grid down. Fixed with an explicit mobile-only `flex-direction: row` + visible colors. Also fixed the same class of defect on the "Information"/"Legal" section labels, which were rendering fully invisible (dark-on-dark) via a leftover per-widget `title_color` setting — mobile-only fix again.
- Telegram widget height: 64px → 44px (label + handle now sit in one row instead of two stacked lines). Gap between Telegram and the navigation grid: a deliberate ~32px section break (was visually reading as a much larger dead block due to the 2-line stack pushing content down). Total mobile footer height is materially unchanged (~834px) — this was a qualitative fix (removing broken/invisible layout, not adding new whitespace budget), not a further height-reduction pass.
- **Desktop is confirmed byte-for-byte unchanged**: both fixes are scoped inside `@media (max-width: 1024px)` only; the same two defects (stacked/invisible Telegram handle, invisible nav labels) remain present on desktop exactly as PO reviewed and approved it, flagged here for a separate PO decision rather than silently fixed.

### Notes

- Acceptance: `storefront-acceptance` `tests/m8-footer.spec.ts` via `tools/run-dev.sh --m8-only` (19 passed / 0 failed).

### Release

- **M8 Global Footer Redesign — PO-approved / frozen on DEV** (desktop + mobile density corrective). Git tag: **`storefront-v0.9.27`**. Spec: [docs/storefront-redesign/plans/MILESTONE_M8_GLOBAL_FOOTER_REDESIGN.md](docs/storefront-redesign/plans/MILESTONE_M8_GLOBAL_FOOTER_REDESIGN.md). Production replay not performed — DEV only until a separate, explicit PO GO. The two pre-existing desktop defects noted above (stacked/invisible Telegram handle, invisible "Information"/"Legal" labels) were reviewed and approved as part of the desktop sign-off; still available as a follow-up fix if the PO wants them addressed later.

---

## [0.9.26] — 2026-08-22

### Added

- **M8 Global Footer Redesign (dev, untagged):** implemented Direction B (dark/structured endpoint) on Elementor footer template 3823 via idempotent `setup-m8-global-footer-cli.php`. Desktop: two-zone composition (brand+CTA left ~54%, grouped Information/Legal nav zone right). Mobile (≤1024): purpose-built composition — full-width Contact-form CTA, secondary Telegram, 2-column Information/Legal grid (no accordion), ≥44px hit areas. New `assets/css/footer-v2.css` + `includes/footer-v2-assets.php` own the visual system; `chrome-v1.css`'s legacy 767px footer block is retired/superseded.

### Removed

- **Footer email machinery removed architecturally** (not hidden): `modules/footer-contact/class-footer-contact-module.php`, `assets/footer-contact/footer-contact-email.js`, `assets/footer-contact/bp-e1.png`, the `[biopentra_footer_email]` shortcode registration, and the dead footer-email CSS selectors in `chrome-v1.css`. Verified footer-only/single-plugin-owned before deletion (see MILESTONE_M8 plan §2.2); zero other callers, zero DB usage of `_biopentra_placeholder_page`.

### Fixed

- **Elementor `content_width`/default-padding leak on new containers:** the brand zone, nav zone, and nav columns created/repurposed for M8 fell back to Elementor's default 10px container padding and per-`<li>` margin (never explicitly zeroed on the original nodes), and new containers defaulted to Elementor's "boxed" mode (an extra `.e-con-inner` wrapper) which silently defeated the CSS-driven 54/42 desktop split and the mobile 2-column grid. Fixed via `content_width: full` on the affected nodes plus explicit padding/margin resets in `footer-v2.css`. Result: desktop footer height 654px → 465px, mobile 1293px → 834px, with no loss of content.
- **Stale Cloudflare edge cache during iteration:** `footer-v2.css` had no cache-busting beyond the static plugin version, so edits weren't visible until `includes/footer-v2-assets.php` started appending the file's `filemtime()` to the enqueued version (pattern already used by `home-v2-assets.php`).
- **Telegram mobile hit area:** the secondary Telegram link measured ~20px tall (only the `@handle` text, not the full contact block); `min-height: 44px` added directly to the anchor.

### Notes

- **Untagged development version — awaiting PO visual review.** Does not freeze M8. Spec: [docs/storefront-redesign/plans/MILESTONE_M8_GLOBAL_FOOTER_REDESIGN.md](docs/storefront-redesign/plans/MILESTONE_M8_GLOBAL_FOOTER_REDESIGN.md). Elementor pre-change backup: `docs/storefront-redesign/changes/backups/footer-pre-M8.json`. Acceptance: `storefront-acceptance` `tests/m8-footer.spec.ts` via `tools/run-dev.sh --m8-only` (19 passed / 0 failed). `--m7-only` and the E3 footer regression check remain green.

---

## [0.9.25] — 2026-08-21

### Fixed

- **M7 desktop density:** stop stretching the primary Research Use panel to the secondary column height (`align-items: flex-start`); tighten desktop section/intro/primary padding. Mobile rules unchanged.

### Release

- **M7 PO-approved / frozen on DEV** (desktop density corrective). Git tag: **`storefront-v0.9.25`**.

---

## [0.9.24] — 2026-08-21

### Added

- **M7 Research & Product Guidance:** light editorial guidance hub on homepage `238bcc6` (primary Research Use + secondary Storage / Ordering rows). CLI `setup-m7-research-guidance-cli.php`; CSS `.bp-m7-*` in `home-v2.css`. No image.

### Release

- **M7 PO-approved / frozen on DEV.** Git tag: **`storefront-v0.9.24`**.

---

## [0.9.23] — 2026-08-21

### Changed

- **M6 freeze:** final desktop Ordering Questions density (tighter section pad + accordion row padding ≥1025). Why BioPentra left unchanged (PO-approved). Mobile FAQ untouched.

### Release

- **M6 PO-approved / frozen on DEV.** Git tag: **`storefront-v0.9.23`**.

---

## [0.9.22] — 2026-08-21

### Changed

- **M6 desktop refinement (dev):** Why BioPentra is now content-left | visual-right using PO-selected master crop (`m6-why-biopentra-visual.png`); mobile Why stack preserved (image omitted ≤1024). FAQ desktop vertical spacing tightened.
- Idempotent CLI imports Why visual via attachment meta `_biopentra_m6_why_visual` (no hardcoded attachment ID).

### Notes

- Untagged development version — awaiting PO visual review. Does not freeze M6.

---

## [0.9.21] — 2026-08-21

### Added

- **M6 Brand Story & Ordering Confidence (dev):** compact Confidence Strip (`7fe474f`), typography-led Why BioPentra (`why4444`, 3 proofs, no image), Ordering Questions Elementor accordion (`faqPreview4444`, collapsed by default, no FAQPage schema).
- Idempotent CLI: `scripts/setup-m6-brand-story-cli.php`.
- Homepage CSS in `home-v2.css` + tiny `m6-faq-init.js` for initial collapsed FAQ state.

### Notes

- Development version on DEV only — **not tagged / not released**. Awaiting Product Owner visual review before freeze.

---

## [0.9.20] — 2026-08-21

### Fixed

- **M5 desktop rhythm (safe):** content wrapper `m5cnt01` + two-column grid (`media | content`) with compact flex gaps; replaces the reverted 0.9.19 absolute-column attempt.

### Release

- **M5 PO-approved / frozen on dev.** Git tag: **`storefront-v0.9.20`**. Production replay still requires an explicit GO.

---

## [0.9.19] — 2026-08-21

### Fixed

- Attempted M5 desktop rhythm via absolute image column — **reverted in 0.9.20** (collapsed content width / vertical glyph stacking on some viewports).

### Release

- Superseded by **0.9.20** / `storefront-v0.9.20`.

---

## [0.9.18] — 2026-08-21

### Added

- **M5 Trust & Fulfillment Story:** editorial image-left / content-right homepage band (Elementor `0b22897`), compact trust rows, idempotent CLI + media import (`setup-m5-trust-fulfillment-cli.php`), styles in `home-v2.css`.

### Release

- Development build; freeze completed at **0.9.20**.

---

## [0.9.17] — 2026-08-21

### Changed

- **M4 homepage product sections:** Premium teal headings and CTAs for Featured/Newest/Popular; tighter section rhythm. Card chrome remains in `biopentra-loop-card` (soft/rounded/elevated baseline preserved).
- **M4 product-stack rhythm (PO):** compact M3→Featured handoff; zero Elementor button `margin-top`; unified Featured/Newest/Popular inner padding so mobile grid→CTA and CTA→next-heading spacing are consistent without changing cards.

### Release

- **M4 PO-approved / frozen on dev.** Git tag: **`storefront-v0.9.17`**. Requires companion **`biopentra-loop-card` 1.6.2** / `v1.6.2`. Production replay still requires an explicit GO.

---

## [0.9.16] — 2026-08-21

### Added

- **M3 Homepage Category Discovery:** compact low-radius category rail under the M2 hero, visible “Shop by category” label on the front page, homepage Uncategorized exclusion, idempotent `setup-m3-homepage-category-cli.php`.

### Changed

- Quieter chip visual weight after PO feedback: ~38px chip height, 5px radius, lighter muted label.

### Removed

- Homepage under-hero search widget instance (M1 header search remains; `[biopentra_home_search]` PHP retained for SEO pages).

### Release

- **M3 PO-approved / frozen on dev.** Git tag: **`storefront-v0.9.16`**. Production replay still requires an explicit GO.

---

## [0.9.15] — 2026-08-20

### Added

- **M2 Premium Ecommerce homepage hero:** Broadsheet teal/cyan surfaces, desktop ≥1025 two-column composition with 1240 content-rail alignment, compact mobile text-over-image, env-relative CTA URLs, `setup-m2-homepage-hero-cli.php`, hero CSS in `home-v2.css` (migrated off Elementor page custom CSS).

### Changed

- Homepage hero height: preferred ~28vh, hard maximum 40vh (no longer clamps hard at 28vh).

### Release

- **M2 PO-approved / frozen on dev.** Git tag: **`storefront-v0.9.15`**. Production replay still requires an explicit GO.

---

## [0.9.14] — 2026-08-20

### Fixed

- **M1 drawer Information caret:** keep the dropdown arrow on the same row as the label (nowrap flex; title link no longer `width:100%`).

### Release

- **M1 PO-approved / frozen on dev.** Git tag: **`storefront-v0.9.14`**. Production replay still requires an explicit GO (see redesign `deployment/m1-premium-header.md`).

---

## [0.9.13] — 2026-08-20

### Fixed

- **M1 drawer Information toggle:** second tap on Information collapses the in-drawer panel (previously always re-forced open).

**Git tag:** none — awaiting Product Owner visual review.

---

## [0.9.12] — 2026-08-20

### Fixed

- **M1 drawer polish:** animate slide-in from the left (Elementor `display:none` previously skipped CSS transform); hide header hamburger→X and UMC while open so they do not float over scrolling Information content or shine through the panel; keep the drawer close control in normal document flow so it scrolls with the menu.

**Production replay:** still `deployment/m1-premium-header.md` (prefer ≥0.9.13). **Git tag:** none — awaiting Product Owner visual review.

---

## [0.9.7] — 2026-08-20

### Fixed

- **M1 drawer Information panel:** Elementor left the dropdown’s inner `.e-con` at `display:none` (zero-height mega); force in-flow flex when the panel is active. Rebuild stripped `<template>` detail nodes so mobile accordion can clone copy. Stop using document capture `stopPropagation` (it blocked accordion button handlers); use wrapper bubble-phase guards instead so leaf links and disclosures still receive clicks.

**Production replay:** documented in redesign repo `deployment/m1-premium-header.md` (not replayed; requires ≥0.9.7). **Git tag:** none — awaiting Product Owner visual review.

---

## [0.9.6] — 2026-08-20

### Fixed

- **M1 mobile drawer:** left-align nav labels (stop center/right crop on phone/tablet); elevate header stacking context while open so the backdrop no longer steals taps; in-flow Information mega/accordion inside the drawer; leaf links navigate without an instant close.

**Git tag:** none — awaiting Product Owner visual review.

---

## [0.9.5] — 2026-08-20

### Added

- **M1 — Responsive Premium Header / Navigation (dev).** Scoped Broadsheet / Premium Ecommerce header palette tokens (`bp-header-m1.css`), in-place left mobile nav drawer (`bp-nav-drawer.js`, Approach A — no Elementor DOM reparenting), desktop utility chrome (Search / Account / UMC / Cart), kill floating-pill cart trigger in the header, store-notice dark-teal alignment. V1A homepage trial disabled via `biopentra_v1a_specimen_enabled` filter only (`m1-review-hygiene.php`); V1A files retained. No production tag/release.

**Git tag:** none — awaiting Product Owner visual review.

---

## [0.9.4] — 2026-08-07

### Fixed

- **Legal/info pages lost their boxed layout.** Privacy Policy, Cookie Policy, Terms & Conditions, Refund Policy, Shipping Policy, FAQ, and Research Use Disclaimer had `_wp_page_template = elementor_header_footer` (Elementor's edge-to-edge canvas) despite holding plain WordPress content (no real `_elementor_data`) — that template skips the theme's boxed `.entry-content` column, so text rendered unconstrained to the viewport edge. Fixed via `scripts/fix-legal-page-content-template.php`: reset to the theme's `default` template (matching Contact and every other plain-content page) and disabled Blocksy's `has_hero_section` for them, since the theme's own title bar was duplicating each page's own `<h1>`.
- **Homepage quick-search row was unboxed and always stacked.** The search input and category-chip containers (`bp-home-search-section`, `bp-home-cats-section`) explicitly set `content_width: full`, unlike every other homepage section, so they hugged the viewport edge instead of sitting in the site's centered ~1240px column; they were also two separate top-level Elementor containers, so they could never share a row. Fixed via `scripts/fix-home-search-cats-row.php`: merged the category-chips widget into the search container, boxed it to `1240px` to match Featured Products, and made it a flex row (chips left, search field fixed-width right) above Elementor's own tablet cutoff (1025px, matching the mega-menu's breakpoint), falling back to the existing stacked/scrollable mobile layout below that. `assets/css/home-v2.css` updated to size the two children within the merged row.

**Git tag:** none yet — patches prepared but not tagged/released.

---

## [0.9.2] — 2026-08-07

### Fixed

- **Milestone E1.2 — Information mega-menu closed while hovering toward its own items.** With Elementor Pro's mega-menu default `open_on=hover`, moving the mouse from the "Information" title down into its open panel very often crossed a sibling top-level title (Home / Shop / About Us / Contact) still in the same row — Elementor Pro's own `mega-menu.js` unconditionally deactivates the active tab whenever the mouse enters *any* title (`onMouseTitleEnter` → `changeActiveTab()` → `deactivateActiveTab()`), even a plain link with no dropdown of its own. Confirmed live: hovering "Shop" while "Information" was open closed the panel every time. This was unreachable before Milestone E1.1 — desktop was stuck in the collapsed/hamburger layout, so the hover-open codepath never actually ran on desktop until E1.1 restored the horizontal nav; this is a newly-exposed, pre-existing widget-interaction issue, not a regression introduced by the E1.1 fix itself.
  Fix: set the mega-menu widget's own `open_on` setting to `click` (a supported Elementor Pro control, applied via a new idempotent CLI: `scripts/setup-milestone-e1-2-mega-menu-open-on-click.php`) in header 3782. Click-to-open sidesteps the fragile hover geometry entirely and matches the behaviour already used on mobile/tablet (which always open on click, regardless of this setting). No CSS changes; no JS changes.

**Git tag:** none yet — patch prepared but not tagged/released; see `MILESTONE_P0_PRODUCTION_ROLLOUT.md` amendment.

---

## [0.9.1] — 2026-08-07

### Fixed

- **Milestone E1.1 — Desktop header regressed to hamburger nav.** `assets/css/chrome-v1.css`'s `.e-n-menu-toggle` touch-target rule (added in 0.9.0 for the ≥44×44 requirement) declared `display: inline-flex` with no `@media` guard. That selector ties Elementor Pro's mega-menu widget's own desktop `.elementor-widget-n-menu .e-n-menu-toggle{display:none}` rule on specificity (2 classes each); because `chrome-v1.css` loads after Elementor's CSS in the enqueue order, it won the cascade at every width, forcing the hamburger toggle open on desktop (confirmed 1024px–1688px) while the horizontal nav (`.e-n-menu-wrapper`) rendered underneath as an unstyled block. Elementor's own responsive config (`item_layout=horizontal`, `breakpoint_selector=tablet` / 1024px) was untouched and correct. Fix: drop the `display` declaration from that rule — sizing/centering are still applied whenever Elementor's own (higher-specificity, media-scoped) rule shows the toggle. No Elementor config or CLI changes required.
- Discovered during pre-production smoke testing at ~1688px; dev header 3782 (Elementor `_elementor_data`) required no changes.

**Git tag:** `storefront-v0.9.1` (patch; supersedes `0.9.0` — 0.9.0 should not be installed on production, see updated `MILESTONE_P0_PRODUCTION_ROLLOUT.md`)

---

## [0.9.0] — 2026-08-04

### Milestone E — Global chrome polish (dev)

- **E2 Fixed UI:** SDS z-index contract for CookieYes (300/400), mini-cart overlay (400), header chrome (200); UMC `placement` `sticky_footer` → `manual` with one `[universal_multicurrency_switcher]` in Elementor header 3782 (no floating-bottom instance; no CSS-hide workaround).
- **E1 Header:** ≥44×44 menu/account/search; frozen header search focuses `#biopentra-shop-s` / `#biopentra-search-refine` or navigates to shop; header-auth dropdown z-index aligned to `--bp-z-header`.
- **E3 Footer:** Elementor footer 3823 mobile padding/gap compaction + chrome CSS two-column link band; crawlable links preserved.
- **Assets/CLIs:** `assets/css/chrome-v1.css`, `assets/js/chrome-v1.js`, `includes/chrome-v1-assets.php`, `scripts/setup-milestone-e-chrome-cli.php`, `scripts/setup-milestone-e-footer-cli.php`.

**Git tag:** `storefront-v0.9.0`

## [0.8.0] — 2026-08-04

### Added

- **Milestone D3 — SEO content ownership:** page meta `_bp_seo_grid_products` / `_bp_seo_archive_term`, migrate/validate/rollback CLIs, one-release legacy inventory fallback.
- **Milestone D1 — SDS image tokens:** `assets/css/bp-tokens.css`, `includes/bp-tokens-assets.php`; hero max-height caps in home/shop v2 CSS.

**Git tag:** `storefront-v0.8.0`

---

## [0.7.0] — 2026-08-03

### Added

- **Milestone B — commercial shopping IA:** shop page v2, search refinement, SEO category product grids (see release notes `GITHUB_RELEASE_NOTES_biopentra-storefront_0_7_0.md`).

**Git tag:** `storefront-v0.7.0`

---

## [0.6.0] — 2026-08-02

### Added

- **Milestone A — commercial homepage:** `setup-home-page-v2-cli.php` rebuilds front-page Elementor IA (compact hero, product search shortcode, category chips, featured/newest/popular grids before editorial content).
- Home v2 assets: `assets/css/home-v2.css`, `includes/home-v2-helpers.php`, `includes/home-v2-assets.php`, shortcodes `[biopentra_home_search]` and `[biopentra_home_categories]`.

### Changed

- Requires `biopentra-loop-card` update that enqueues live search on `is_front_page()`.

---

## [0.5.21] — 2026-08-01

### Fixed

- **`readme.txt` Stable tag drift:** the field was stuck at `0.5.17` while the plugin header/`BIOPENTRA_STOREFRONT_VERSION` constant had already moved to `0.5.20`. Synced `Stable tag` to `0.5.21` and backfilled the missing 0.5.18–0.5.20 entries in `readme.txt`'s own `== Changelog ==` section. No functional change; part of Phase 0 (Foundations) of the mobile-first storefront redesign — see `/opt/biopentra/docs/storefront-redesign/`.

**Git tag:** `storefront-v0.5.21` (not yet tagged/pushed — pending product-owner approval)

---

## [0.5.20] — 2026-07-25

### Added

- **Crypto payment guide module:** Cart informational banner and compact checkout link to `/how-to-pay-with-crypto/` (`biopentra_crypto_guide_enabled`, optional `biopentra_crypto_guide_page_id`). Does not duplicate VCCP checkout notice.
- **Content bundle:** `content/crypto-payment-guide/` with import script, extracted Blockchain.com walkthrough media, and reproducible Elementor page import.
- **Mega-menu CLI:** `BIOPENTRA_MEGA_CRYPTO_GUIDE_LINK=1` patch appends Learn-column link idempotently.
- **Checkout pause banner copy:** Extended default info-banner text with card-to-crypto settlement guidance.

**Git tag:** `storefront-v0.5.20`

---

## [0.5.19] — 2026-07-24

### Added

- **Checkout pause module (recovered into git):** Outage mode that disables payment gateways and shows a storefront banner; previously lived only on the production filesystem.
- **Info banner mode:** `biopentra_checkout_banner` / `biopentra_checkout_banner_message` show a site/cart/checkout notice without disabling gateways (default copy: payments back online; prefer BTCPay, then Banxa, then Blockchain.com).
- **Card gateway title rewrite:** `Card to USDC via {Provider}` → `Card to crypto via {Provider} (4% fee)` (settings helper + `woocommerce_gateway_title` filter); BTCPay unchanged.

**Git tag:** `storefront-v0.5.19`

---

## [0.5.17] — 2026-05-28

### Fixed

- **Mini-cart template compatibility (header-auth 1.5.18):** Restored WooCommerce’s standard mini-cart total and button hooks inside the custom drawer footer, and aligned remove-link attributes with the current WooCommerce mini-cart template while preserving the BioPentra drawer layout, quantity controls, trust logos, and fragment behavior.

**Git tag:** `storefront-v0.5.17`

---

## [0.5.16] — 2026-05-28

### Fixed

- **Mini-cart close after remove (header-auth 1.5.17):** Cancel pending drawer-restore timers when the user intentionally closes the cart drawer after a remove action. This keeps the empty cart visible after removal, but prevents the drawer from reopening repeatedly after an outside-page click.
- **Mini-cart quantity updates:** Route drawer quantity changes through the BioPentra cart quantity endpoint consistently and save the cart session before refreshing fragments, so plus/minus changes persist instead of reverting.

**Git tag:** `storefront-v0.5.16`

---

## [0.5.15] — 2026-05-28

### Fixed

- **Mini-cart remove flow (header-auth 1.5.15):** Preserve the active Elementor side-cart or Blocksy cart drawer after clicking a row remove button. Drawer remove clicks now use WooCommerce’s standard `remove_from_cart` AJAX endpoint directly, apply returned fragments, and keep/reopen the drawer after refresh.

**Git tag:** `storefront-v0.5.15`

---

## [0.5.14] — 2026-05-25

### Added

- **Checkout payment display:** Added a display-only checkout payment module that replaces supported gateway icons through `woocommerce_gateway_icon` for BTCPay and the enabled VCCP card gateways. Uses bundled Bitcoin, Visa, and Mastercard SVG assets and a checkout-only stylesheet; gateway IDs and payment processing logic are unchanged.

**Git tag:** `v0.5.14`, `storefront-v0.5.14`

---

## [0.5.13] — 2026-05-25

### Changed

- **Mini-cart cart trigger (header-auth 1.5.13):** Implemented a soft elevated pill treatment for the Elementor/Blocksy cart trigger with off-white background, subtle border, hover/focus elevation, refined amount typography, compact icon spacing, and a smaller red badge. CSS-only and scoped to the mini-cart/header cart selectors.

**Git tag:** `v0.5.13`, `storefront-v0.5.13`

---

## [0.5.12] — 2026-05-25

### Added

- **Mini-cart payment/trust logos (header-auth 1.5.12):** Added a centered logo row below the secure checkout trust text using bundled Bitcoin, USDC, Visa, and Mastercard SVG assets. Scoped to `.bp-mini-cart--drawer` and preserves WooCommerce fragments/AJAX behavior.

**Git tag:** `v0.5.12`, `storefront-v0.5.12`

---

## [0.5.11] — 2026-05-25

### Changed

- **Checkout v2 order summary (header-auth 1.5.11):** Restored clearer but still soft 1px separators between the heading, products, subtotal/discount/shipping sections, total, and payment methods. Keeps the off-white card background and current padding while improving section readability.

**Git tag:** `v0.5.11`, `storefront-v0.5.11`

---

## [0.5.10] — 2026-05-25

### Changed

- **Checkout v2 order summary (header-auth 1.5.10):** Applied the premium off-white container treatment to both `.bp-checkout-v2__summary-card` and Blocksy’s `.ct-order-review` wrapper. Added stronger internal padding, softer border color, subtle radius/shadow, and improved product/totals row spacing while preserving right-aligned prices and full-width payment cards.

**Git tag:** `v0.5.10`, `storefront-v0.5.10`

---

## [0.5.9] — 2026-05-25

### Fixed

- **Mini-cart product rows (header-auth 1.5.9):** Removed the harsh inherited frame around the product row area by resetting row borders/outlines/shadows and replacing them with a soft bottom-only separator. Improved image/content/quantity vertical alignment while preserving WooCommerce fragments and AJAX behavior.

**Git tag:** `v0.5.9`, `storefront-v0.5.9`

---

## [0.5.8] — 2026-05-25

### Changed

- **Checkout v2 (header-auth 1.5.8):** Refined order summary and payment method presentation — more internal padding, off-white surfaces (`#f7f7f8` / `#f5f5f6`), lighter borders, premium card-style gateways, improved totals hierarchy. Scoped to `body.bp-checkout-v2` only.

**Git tag:** `v1.5.8`, `storefront-v0.5.8`

---

## [0.5.7] — 2026-05-25

### Changed

- **Mini-cart product rows:** Larger thumbnail (72–76px), accent title color, variation/short-description subtitle, bold line price, circular remove on image, pill-style quantity controls, tighter spacing. UI-only; fragments and AJAX unchanged.

**Git tag:** `storefront-v0.5.7`

---

## [0.5.6] — 2026-05-25

### Added

- **Header auth / mini-cart drawer:** Premium Scandinavian-style WooCommerce mini-cart for Elementor side-cart and Blocksy offcanvas (`modules/header-auth/`).
  - Custom `woocommerce/cart/mini-cart.php` template override (Elementor-compatible markup).
  - Sticky subtotal + trust row + checkout-first CTAs, compact product rows, quantity inputs.
  - `mini-cart-drawer.css` / `mini-cart-drawer.js` with loading states and fragment-friendly qty updates (Blocksy AJAX + fallback).
  - Header-auth module version **1.5.6**.

### Notes

- Does not modify theme builder templates, global Elementor JSON, or WooCommerce core.
- Deploy via GitHub Release tag `storefront-v0.5.6`.

**Git tag:** `storefront-v0.5.6`

---

## [0.5.2] — 2026-05-19

### Added

- `includes/class-github-updater.php` — production updates from monorepo GitHub Releases (tag `storefront-v*`, asset `biopentra-storefront-X.Y.Z.zip`).
- Disable on dev: `BIOPENTRA_STOREFRONT_DISABLE_GITHUB_UPDATER` or filter `biopentra_storefront_github_updater_enabled`.

### Notes

- No intentional behavior changes vs **0.5.1**.

**Git tag:** `storefront-v0.5.2`

---

## [0.5.1] — 2026-05-19

### Added

- Monorepo release workflow `.github/workflows/release-biopentra-storefront.yml` (tag `storefront-v*`).
- Distribution files under `plugins/biopentra-storefront/`: `readme.txt`, `LICENSE`.

### Changed

- Shop product infinite scroll; Elementor load-more button text control.
- Production cleanup scripts and technical SEO module updates (see git history since 0.4.0).
- Production ZIP excludes in-plugin `scripts/` and dev-only paths.

**Git tag:** `storefront-v0.5.1`

---

## [0.4.0] — 2026-05-15

### Added

- **Header auth (Phase 4):** `modules/header-auth/` — shortcode `[biopentra_header_auth]`, Elementor widget type `biopentra_header_auth`, WC account/checkout styles, cart free-shipping progress, Blocksy `ct-ajax-add-to-cart` filter, optional Blocksy global palette admin hook.
- Documentation: `docs/header-auth-migration-notes.md`, `docs/staging-test-phase-4-header-auth.md`, `docs/header-auth-phase-4-audit.md`, `docs/phase-4-header-auth-test-results.md`, `docs/storefront-0.4.0-production-cutover.md`.

### Changed

- Storefront bootstrap loads `Biopentra_Storefront_Header_Auth_Module`; module no-ops when legacy `biopentra-header-auth` is active.

### Fixed (rollback / coexistence)

- **`biopentra-header-auth`:** Early exit in `biopentra-header-auth.php` when `biopentra_header_auth_register_assets()` already exists — prevents **Cannot redeclare** fatal if storefront loads header-auth callbacks before legacy in the same request (e.g. WP-CLI activation edge case).

**Git tag (recommended):** `storefront-v0.4.0`

---

## [0.3.0]

### Added

- **Variation stock selector (Phase 3):** CVSS bridge + inline auto-select on `woocommerce_before_variations_form`; HPOS `custom_order_tables` compatibility declaration; skips when legacy `custom-variation-stock-selector` is active.
- Assets: `assets/variation-stock-selector/cvss-bridge.js`.

**Git tag (recommended):** `storefront-v0.3.0`

---

## [0.2.0]

### Added

- **Footer contact (Phase 2):** Shortcode `[biopentra_footer_email]`, `wp_robots` placeholder handling, footer contact JS/CSS; skips shortcode registration if legacy `biopentra-footer-contact` is active.

**Git tag (recommended):** `storefront-v0.2.0`

---

## [0.1.0]

### Added

- **Information megamenu (Phase 1):** Front-end CSS/JS under `assets/information-megamenu/`; script handle distinct from legacy to support Elementor cutover; `defer` on script where applicable.

**Git tag (recommended):** `storefront-v0.1.0`

---

Version links: after you push annotated tags (`storefront-v0.1.0` … `storefront-v0.4.0`), add GitHub compare URLs here if desired.
