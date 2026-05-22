# biopentra-storefront 0.5.4

**GitHub updater fixes** — reliable update notices via the normal Plugins / Updates screens.

## What changed

- Clear cached GitHub release data when WordPress runs `wp_update_plugins` (avoids stale “no update” for up to 12 hours after a new release).
- Select the **highest** `storefront-v*` semver from GitHub releases (not merely the first API result).
- Normalize versions for comparison (`0.5.3` and `v0.5.3`).
- Compare against the version WordPress has in the update transient (plugin header), not only the PHP constant.

No storefront module behavior changes.

## Install / upgrade

1. Download **`biopentra-storefront-0.5.4.zip`** from this release.
2. Upload via **Plugins → Add New → Upload**, or use **Dashboard → Updates** on production.

Sites on **0.5.3** should see this update in the normal WordPress plugin update UI after WordPress checks for updates.

## Rollback

Restore **0.5.3** plugin folder from backup.
