# Biopentra custom WordPress plugins (backup & build)

This directory is a **GitHub-ready snapshot** of first-party plugins used with the WooCommerce Docker project. It does **not** replace `wp-content/plugins/` at runtime; WordPress still loads plugins from the site’s `wp-content/plugins/` tree.

## Layout

| Path | Purpose |
|------|---------|
| `plugins/` | Copies of custom plugins + draft `biopentra-storefront` scaffold |
| `docs/` | Audit, consolidation, GitHub backup, and migration plans |
| `builds/zips/` | Output from `scripts/build-zips.sh` (ZIPs are gitignored) |
| `scripts/build-zips.sh` | Builds `{slug}-{version}.zip` per plugin |

## Plugins in this repo

**Standalone (stay separate for now):**

- `biopentra-contact-inbox` — Support desk (Fluent, IMAP, DB tables, REST worker).
- `wc-inventory-overview` — Inventory / costing admin.
- `biopentra-loop-card` — Elementor loop grid / shop UX.

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
for d in biopentra-contact-inbox biopentra-header-auth biopentra-loop-card biopentra-footer-contact biopentra-information-megamenu wc-inventory-overview custom-variation-stock-selector; do
  rsync -a --delete "$SRC/$d/" "$DEST/$d/"
done
```

## Loop Card version header

The repo copy of `biopentra-loop-card` uses **`Version: 1.2.4`** in the plugin header to match `BIOPENTRA_LOOP_CARD_VER`. If your bind-mounted `wp-content/plugins` is not writable from this environment, **manually align** the same line in the live plugin file when you deploy.

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
