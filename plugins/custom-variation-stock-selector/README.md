# Not a deployable WordPress plugin — retired stub

**This directory is not a WordPress plugin and must not be deployed.**

The functionality was **absorbed into `biopentra-storefront`**.

| Item | Location |
|------|----------|
| **Replacement module** | `plugins/biopentra-storefront/modules/variation-stock-selector/` |
| **Releases** | `storefront-v*` tags on this monorepo only |
| **Release ZIP** | `biopentra-storefront-{version}.zip` |

## Policy

- The slug `custom-variation-stock-selector` is **intentionally excluded** from monorepo release tooling (`RELEASE_SKIP_PLUGINS`).
- **Do not edit source here.** New changes belong in the storefront variation-stock-selector module.
- This stub remains for migration history and to prevent accidental recreation of a stale standalone copy.

## Runtime (now in storefront)

On variable single-product pages with embedded variation data, auto-selects the highest-priced in-stock purchasable variation (same inline jQuery logic as legacy 1.0.0).
