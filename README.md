# Biopentra custom WordPress plugins (backup & build)

This directory is a **GitHub-ready snapshot** of first-party plugins used with the WooCommerce Docker project. It does **not** replace `wp-content/plugins/` at runtime; WordPress still loads plugins from the site’s `wp-content/plugins/` tree.

## Layout

| Path | Purpose |
|------|---------|
| `plugins/` | Active monorepo plugins + pointer stubs for standalone repos |
| `docs/` | Audit, consolidation, GitHub backup, and migration plans |
| `builds/zips/` | Output from `scripts/build-zips.sh` (ZIPs are gitignored) |
| `scripts/build-zips.sh` | Builds `{slug}-{version}.zip` per plugin |

## Plugins in this repo

**Standalone (canonical source outside this monorepo):**

| Slug | Monorepo path | Canonical development |
|------|---------------|------------------------|
| `biopentra-loop-card` | `plugins/biopentra-loop-card/` — **pointer stub only** | `/opt/biopentra/dev/biopentra-loop-card` |
| `wc-inventory-overview` | `plugins/wc-inventory-overview/` — **pointer stub only** | `/opt/biopentra/dev/wc-inventory-overview` |
| `fluent-imap-support-desk` | (not in monorepo) | `/opt/biopentra/dev/fluent-imap-support-desk` |

Releases for loop-card and wc-inventory are built only from their standalone GitHub repositories. Monorepo ZIP tooling skips those slugs.

**Deprecated monorepo copy:**

- `biopentra-contact-inbox` — see `plugins/biopentra-contact-inbox/DEPRECATED.md`. Use [fluent-imap-support-desk](https://github.com/magpern/fluent-imap-support-desk).

**Active deployable plugin (monorepo):**

- `biopentra-storefront` — consolidated storefront plugin (header-auth, footer-contact, information-megamenu, variation-stock-selector, and other modules).

**Retired storefront-module stubs (absorbed into `biopentra-storefront`; not deployable):**

| Slug | Monorepo path |
|------|---------------|
| `biopentra-header-auth` | `plugins/biopentra-header-auth/` |
| `biopentra-footer-contact` | `plugins/biopentra-footer-contact/` |
| `biopentra-information-megamenu` | `plugins/biopentra-information-megamenu/` |
| `custom-variation-stock-selector` | `plugins/custom-variation-stock-selector/` |

Monorepo ZIP tooling skips all retired slugs. Edit the corresponding `plugins/biopentra-storefront/modules/*` paths instead.

## Syncing from the live site

After changing code under `woocommerce/wp-content/plugins/`, refresh the deprecated contact-inbox copy only:

```bash
SRC=/path/to/woocommerce/wp-content/plugins
DEST=/path/to/woocommerce/custom-wordpress-plugins/plugins
rsync -a --delete "$SRC/biopentra-contact-inbox/" "$DEST/biopentra-contact-inbox/"
```

Do **not** rsync standalone plugin slugs or retired storefront-module stubs into this monorepo.

## Build ZIPs

```bash
cd custom-wordpress-plugins
./scripts/build-zips.sh
```

Requires `rsync` and either `zip` **or** `python3`. Archives contain a single top-level folder named like the plugin slug (WordPress-compatible).

## Git init (optional)

```bash
cd custom-wordpress-plugins
git init
git add .
git status
```

## Next steps

See `docs/migration-plan-biopentra-storefront.md` for hook-by-hook migration and testing order.
