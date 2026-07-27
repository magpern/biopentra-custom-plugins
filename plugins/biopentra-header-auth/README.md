# Not a deployable WordPress plugin — retired stub

**This directory is not a WordPress plugin and must not be deployed.**

The functionality was **absorbed into `biopentra-storefront`**.

| Item | Location |
|------|----------|
| **Replacement module** | `plugins/biopentra-storefront/modules/header-auth/` |
| **Releases** | `storefront-v*` tags on this monorepo only |
| **Release ZIP** | `biopentra-storefront-{version}.zip` |

## Policy

- The slug `biopentra-header-auth` is **intentionally excluded** from monorepo release tooling (`RELEASE_SKIP_PLUGINS`).
- **Do not edit source here.** New changes belong in the storefront header-auth module.
- This stub remains for migration history and to prevent accidental recreation of a stale standalone copy.

## Runtime (now in storefront)

Shortcode `[biopentra_header_auth]`, Elementor widget, WC account/checkout/cart styles, Blocksy integrations, mini-cart drawer (storefront additions beyond legacy 1.5.0).
