# Biopentra Storefront

Consolidated WordPress plugin for Biopentra WooCommerce storefront behavior (Elementor, header, SEO, stock labels, and related modules).

**Current version:** 0.5.5  
**Production releases:** [magpern/biopentra-custom-plugins](https://github.com/magpern/biopentra-custom-plugins) — tags `storefront-v*` → ZIP `biopentra-storefront-{version}.zip`

## Requirements

- WordPress 6.0+
- PHP 7.4+
- WooCommerce
- Elementor Pro (for mega-menu and header-auth widget features)

## Modules

Each module loads from `includes/class-biopentra-storefront.php` when its bootstrap file exists. Module-specific notes live under `modules/*/README.md`.

| Module | Purpose | Legacy plugin (deactivate when using storefront) |
|--------|---------|--------------------------------------------------|
| [information-megamenu](modules/information-megamenu/README.md) | Information mega-menu assets | `biopentra-information-megamenu` |
| [footer-contact](modules/footer-contact/README.md) | Footer email shortcode, placeholder noindex | `biopentra-footer-contact` |
| [variation-stock-selector](modules/variation-stock-selector/README.md) | Auto-select highest-priced in-stock variation | `custom-variation-stock-selector` |
| [header-auth](modules/header-auth/README.md) | Header auth shortcode, Elementor widget, cart UX | `biopentra-header-auth` |
| [technical-seo](modules/technical-seo/README.md) | Meta, robots, schema, sitemap hygiene | — |
| [product-stock-display](modules/product-stock-display/README.md) | Configurable stock labels (no exact qty on storefront) | — |

**Guards:** If the matching legacy plugin is still **active**, several modules skip registration to avoid duplicate shortcodes/hooks. Do not run legacy + storefront for the same feature.

## Settings

| Location | Module |
|----------|--------|
| **WooCommerce → Stock display** | Product stock display — thresholds, label text, hex colors (`{stock}` in “only left” text) |

Other modules use theme/Elementor configuration or have no admin UI.

## Installation

### Production

1. Download `biopentra-storefront-{version}.zip` from a [GitHub Release](https://github.com/magpern/biopentra-custom-plugins/releases) (`storefront-v*` tag).
2. **Plugins → Add New → Upload**, or use **Dashboard → Updates** when the GitHub updater is enabled (`WP_ENVIRONMENT_TYPE=production`).
3. Activate and deactivate duplicate legacy plugins listed above.

Disable the updater on dev/staging:

```php
define( 'BIOPENTRA_STOREFRONT_DISABLE_GITHUB_UPDATER', true );
```

### Development (Docker project)

Canonical copy in the monorepo:

```text
woocommerce/custom-wordpress-plugins/plugins/biopentra-storefront/
```

Sync to the running site:

```bash
rsync -av --delete \
  custom-wordpress-plugins/plugins/biopentra-storefront/ \
  wp-content/plugins/biopentra-storefront/
```

Use `./wp` from the WooCommerce project root for WP-CLI.

## Build release ZIP (monorepo)

From `custom-wordpress-plugins/`:

```bash
bash scripts/build-one-plugin-zip.sh biopentra-storefront
bash scripts/release-audit-plugin.sh biopentra-storefront 0.5.3
```

Tag and push to trigger CI release:

```bash
git tag -a storefront-v0.5.3 -m "Release biopentra-storefront 0.5.3"
git push origin storefront-v0.5.3
```

Production ZIPs exclude in-plugin `scripts/`, `tests/`, and other dev paths (see `scripts/lib/release-common.sh`).

## In-plugin dev scripts (not shipped in ZIP)

| Script | Use |
|--------|-----|
| `scripts/seo-audit.php` | Technical SEO checks (`./wp eval-file …`) |
| `scripts/production-cleanup-audit.php` | Production cleanup audit |
| `scripts/production-cleanup-apply.php` | Apply cleanup (use with care) |

## Changelog (summary)

| Version | Highlights |
|---------|------------|
| **0.5.5** | Rank Math guard: no duplicate meta, OG, Twitter, Organization/WebSite/BreadcrumbList JSON-LD |
| **0.5.4** | GitHub updater: release cache refresh, highest-tag selection, version normalization |
| **0.5.3** | Product stock display module; **WooCommerce → Stock display** |
| **0.5.2** | GitHub Release updater (`storefront-v*`) |
| **0.5.1** | Release ZIP automation, shop/Elementor fixes |
| **0.4.0** | Header auth module (Phase 4) |

Full release notes: `docs/GITHUB_RELEASE_NOTES_biopentra-storefront_*.md` in the monorepo.

## Related plugins (separate repos)

Not bundled in storefront:

- **biopentra-loop-card** — [magpern/biopentra-loop-card](https://github.com/magpern/biopentra-loop-card) (shop loop / Elementor cards)
- **wc-inventory-overview** — [magpern/wc-inventory-overview](https://github.com/magpern/wc-inventory-overview)
- **fluent-imap-support-desk** — support desk (replaces legacy contact-inbox)

## License

GPLv2 or later — see [LICENSE](LICENSE).
