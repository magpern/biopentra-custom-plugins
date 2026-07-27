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

**Legacy copies (candidates for future merge into `biopentra-storefront`):**

- `biopentra-header-auth`
- `biopentra-footer-contact`
- `biopentra-information-megamenu`
- `custom-variation-stock-selector`

**Draft consolidated plugin (no migrated logic yet):**

- `biopentra-storefront` — Scaffold only; **do not** use in production until modules are migrated and tested.

## Syncing from the live site

After changing code under `woocommerce/wp-content/plugins/`, refresh copies:

```bash
SRC=/path/to/woocommerce/wp-content/plugins
DEST=/path/to/woocommerce/custom-wordpress-plugins/plugins
for d in biopentra-contact-inbox biopentra-header-auth biopentra-footer-contact biopentra-information-megamenu custom-variation-stock-selector; do
  rsync -a --delete "$SRC/$d/" "$DEST/$d/"
done
```

Do **not** rsync `biopentra-loop-card` or `wc-inventory-overview` into this monorepo — those slugs are pointer stubs; edit the standalone repositories instead.

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
