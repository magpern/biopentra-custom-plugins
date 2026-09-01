# biopentra-storefront 0.9.42 — release notes

## Changed

- Automatic updates now come from a private update server via the bundled
  [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) v5
  library (`lib/plugin-update-checker/`). The bespoke GitHub-release updater
  (`includes/class-github-updater.php`) has been removed.
- The update check runs only when the `PRIVATE_UPDATE_SERVER` constant is defined
  in `wp-config.php` (admin/cron only).
- Added a CI workflow that uploads the release ZIP to the update server on each
  `storefront-v*` tag.

## Install

Deploy `biopentra-storefront` **0.9.42** / tag **`storefront-v0.9.42`**.

Rollback: **0.9.41** / `storefront-v0.9.41`.
