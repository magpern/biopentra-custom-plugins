# Changelog

All notable changes to **`biopentra-storefront`** in this repository are documented here. Other plugins under `plugins/` may maintain their own changelogs when needed.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html) for the **storefront** package version.

## [Unreleased]

### Planned

- Future improvements only after production soak; see `docs/legacy-plugin-retirement-plan.md`.

---

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
