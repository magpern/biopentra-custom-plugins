# wc-inventory-overview 1.17.2

**GitHub Release updater** — production installs and updates from monorepo ZIP assets.

## What changed

- **`includes/class-github-updater.php`** — reads [biopentra-custom-plugins releases](https://github.com/magpern/biopentra-custom-plugins/releases) for the latest tag `wc-inventory-overview-v*` and installs **`wc-inventory-overview-X.Y.Z.zip`** (not source archives).
- Enabled on `WP_ENVIRONMENT_TYPE=production` by default; disable on dev with `WC_INVENTORY_OVERVIEW_DISABLE_GITHUB_UPDATER`.

No intentional inventory dashboard behavior changes vs **1.17.1**.

## Install / upgrade

1. Download **`wc-inventory-overview-1.17.2.zip`** from this release.
2. Upload via **Plugins → Add New → Upload**, or use **Dashboard → Updates** on production.

## Rollback

Restore **1.17.1** plugin folder from backup.

Changelog: [plugins/wc-inventory-overview/CHANGELOG.md](../plugins/wc-inventory-overview/CHANGELOG.md)
