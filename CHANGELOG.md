# Changelog

All notable changes to **`biopentra-storefront`** in this repository are documented here. Other plugins under `plugins/` may maintain their own changelogs when needed.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html) for the **storefront** package version.

## [Unreleased]

### Planned

- Future improvements only after production soak; see `docs/legacy-plugin-retirement-plan.md`.

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
