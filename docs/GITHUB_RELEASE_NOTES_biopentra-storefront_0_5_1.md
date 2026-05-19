# biopentra-storefront 0.5.1

Aligns the Git tag **`storefront-v0.5.1`** with the plugin header (**0.5.1**). Supersedes **`storefront-v0.4.0`** for new deployments.

## What changed since 0.4.0

- Shop product infinite scroll and Elementor load-more label control.
- Production storefront cleanup tooling and technical SEO module updates (see repository `CHANGELOG.md` and recent commits on `main`).
- **Production ZIP** excludes in-plugin `scripts/`, `docs/`, and other dev-only paths.

## Install

1. Download **`biopentra-storefront-0.5.1.zip`** from this release.
2. Upload via **Plugins → Add New → Upload** or extract to `wp-content/plugins/biopentra-storefront/`.
3. Deactivate superseded legacy plugins per `docs/storefront-migration-checklist.md`.

## Upgrade from 0.4.0

Replace the plugin folder with this ZIP. No separate database migration is documented for this packaging release.

## Rollback

Restore the previous plugin folder from backup or reinstall **0.4.0** from tag `storefront-v0.4.0`.

Monorepo changelog: [CHANGELOG.md](../CHANGELOG.md)
