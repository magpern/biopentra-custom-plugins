# Not a deployable WordPress plugin — retired stub

**This directory is not a WordPress plugin and must not be deployed.**

The functionality was **absorbed into `biopentra-storefront`**.

| Item | Location |
|------|----------|
| **Replacement module** | `plugins/biopentra-storefront/modules/information-megamenu/` |
| **Releases** | `storefront-v*` tags on this monorepo only |
| **Release ZIP** | `biopentra-storefront-{version}.zip` |

## Policy

- The slug `biopentra-information-megamenu` is **intentionally excluded** from monorepo release tooling (`RELEASE_SKIP_PLUGINS`).
- **Do not edit source here.** New changes belong in the storefront information-megamenu module.
- This stub remains for migration history and to prevent accidental recreation of a stale standalone copy.

## Runtime (now in storefront)

Front-end CSS/JS for the Elementor Information mega-menu panel (`wp_enqueue_scripts` @ priority 25).

## Dev-only CLI (moved)

Elementor mega-menu layout updater: `plugins/biopentra-storefront/scripts/cli-update-megamenu.php` (run via `wp eval-file` when storefront is active).
