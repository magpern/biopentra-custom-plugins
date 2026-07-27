# wc-inventory-overview 1.17.1

**Packaging-only release** from the `biopentra-custom-plugins` monorepo. **No plugin behavior changes** vs 1.17.0.

## What changed

- Production ZIP built via `scripts/build-one-plugin-zip.sh` — runtime files only.
- Excludes `cli/`, `scripts/`, `docs/`, `.github/`, and other dev paths.
- GitHub Actions release on tag `wc-inventory-overview-v*`.

## Install

1. Download **`wc-inventory-overview-1.17.1.zip`** from this release.
2. Upload via **Plugins → Add New → Upload** or extract to `wp-content/plugins/wc-inventory-overview/`.
3. Activate if needed.

## Upgrade from 1.17.0

Replace the plugin folder with this ZIP. No database migration expected for this packaging release.

## Rollback

Restore the previous plugin folder from backup.

Plugin changelog: [CHANGELOG.md](https://github.com/magpern/wc-inventory-overview/blob/main/CHANGELOG.md) (standalone repository)
