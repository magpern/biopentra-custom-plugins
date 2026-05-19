# biopentra-storefront 0.5.2

**GitHub Release updater** — production installs and updates from monorepo ZIP assets.

## What changed

- **`includes/class-github-updater.php`** — reads [biopentra-custom-plugins releases](https://github.com/magpern/biopentra-custom-plugins/releases) for the latest tag `storefront-v*` and installs **`biopentra-storefront-X.Y.Z.zip`** (not source archives).
- Enabled on `WP_ENVIRONMENT_TYPE=production` by default; disable on dev with `BIOPENTRA_STOREFRONT_DISABLE_GITHUB_UPDATER`.

No intentional storefront behavior changes vs **0.5.1**.

## Install / upgrade

1. Download **`biopentra-storefront-0.5.2.zip`** from this release.
2. Upload via **Plugins → Add New → Upload**, or use **Dashboard → Updates** on production.

## Rollback

Restore **0.5.1** plugin folder from backup.

Changelog: [CHANGELOG.md](../CHANGELOG.md) (storefront section)
