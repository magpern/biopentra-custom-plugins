# Monorepo plugin release process

Standardizes **versioning**, **production ZIPs**, **Git tags**, and **rollback** for first-party plugins in `biopentra-custom-plugins`.

**Do not** edit `wp-content/plugins/` on servers as the source of truth. Commit here, build a production ZIP, deploy the ZIP (or GitHub Release asset).

---

## Tag naming

| Plugin | Git tag format | Example | GitHub workflow |
|--------|----------------|---------|-----------------|
| **biopentra-loop-card** | `v{version}` on **[magpern/biopentra-loop-card](https://github.com/magpern/biopentra-loop-card)** | `v1.3.3` | `release.yml` (standalone repo) |
| **wc-inventory-overview** | `v{version}` on **[magpern/wc-inventory-overview](https://github.com/magpern/wc-inventory-overview)** | `v1.17.2` | `release.yml` (standalone repo) |
| **biopentra-storefront** | `storefront-v{version}` on **biopentra-custom-plugins** | `storefront-v0.5.2` | `release-biopentra-storefront.yml` |

Tag version must match the plugin header `Version:` and the package constant (`WC_INVENTORY_OVERVIEW_VERSION`, `BIOPENTRA_STOREFRONT_VERSION`, etc.).

**Deprecated:** `biopentra-contact-inbox` — see `plugins/biopentra-contact-inbox/DEPRECATED.md`. Releases come from [fluent-imap-support-desk](https://github.com/magpern/fluent-imap-support-desk) (`v2.0.2+`).

---

## Build commands

**One plugin (monorepo release only):**

```bash
./scripts/build-one-plugin-zip.sh biopentra-storefront
./scripts/release-audit-plugin.sh biopentra-storefront 0.5.20
```

**Standalone plugins** (`biopentra-loop-card`, `wc-inventory-overview`): build and audit in their own repositories — monorepo `build-one-plugin-zip.sh` rejects those slugs.

**All non-deprecated monorepo plugins (local batch):**

```bash
./scripts/build-zips.sh
```

Output: `builds/zips/{slug}-{version}.zip` with top-level folder `{slug}/`.

`build-zips.sh` **skips** `biopentra-contact-inbox`, standalone-only slugs in `RELEASE_SKIP_PLUGINS` (`biopentra-loop-card`, `wc-inventory-overview`), retired storefront-module slugs (`biopentra-header-auth`, `biopentra-footer-contact`, `biopentra-information-megamenu`, `custom-variation-stock-selector`), and any plugin with `plugins/{slug}/DEPRECATED.md`.

---

## Production ZIP rules

**Included (typical):** main plugin PHP, runtime PHP (`includes/`, `modules/`), `assets/`, `readme.txt`, `LICENSE`, plugin `CHANGELOG.md` when present.

**Excluded:** `.git`, `.github`, `scripts/`, `tests/`, `docs/`, `cli/`, `builds/`, `node_modules/`, Composer `vendor/` at plugin root, env/log/cache files. **`assets/vendor/`** (e.g. Chart.js) is allowed.

Verify with `scripts/lib/verify-release-zip.py` (profiles for released plugins).

---

## Release checklist

1. Bump `Version:` and version constant in the plugin main file.
2. Update plugin or repo `CHANGELOG.md` and `docs/GITHUB_RELEASE_NOTES_{slug}_{version}.md` (underscores in version).
3. Run `./scripts/build-one-plugin-zip.sh {slug}` and `./scripts/release-audit-plugin.sh {slug} {version}`.
4. Commit on `main`.
5. Annotated tag and push (monorepo example — **biopentra-storefront** only):

   ```bash
   git tag -a storefront-v0.5.20 -m "Release biopentra-storefront 0.5.20"
   git push origin main
   git push origin storefront-v0.5.20
   ```

   For **biopentra-loop-card** and **wc-inventory-overview**, tag and push in the standalone repository (`v*` tags), not in this monorepo.

6. Confirm GitHub Actions release workflow succeeded and the Release ZIP is attached.

---

## GitHub Release updaters (production)

Released plugins include `includes/class-github-updater.php`. On `WP_ENVIRONMENT_TYPE=production`, WordPress checks [monorepo releases](https://api.github.com/repos/magpern/biopentra-custom-plugins/releases) for the latest tag matching the plugin prefix and offers the matching **`{slug}-X.Y.Z.zip`** asset.

| Plugin | Tag prefix | Disable on dev |
|--------|------------|----------------|
| wc-inventory-overview | `v*` ([standalone repo](https://github.com/magpern/wc-inventory-overview)) | `WC_INVENTORY_OVERVIEW_DISABLE_GITHUB_UPDATER` |
| biopentra-loop-card | `v*` ([standalone repo](https://github.com/magpern/biopentra-loop-card)) | `BIOPENTRA_LOOP_CARD_DISABLE_GITHUB_UPDATER` |
| biopentra-storefront | `storefront-v*` (this monorepo) | `BIOPENTRA_STOREFRONT_DISABLE_GITHUB_UPDATER` |

Filters: `wc_inventory_overview_github_updater_enabled`, `biopentra_storefront_github_updater_enabled`.

---

## Production deployment

- Install **only** from GitHub Release ZIPs (or CI artifacts), not from a raw git clone on the server.
- Keep a copy of the previous Release ZIP for rollback.
- Database backup before upgrades when migrations or data changes are involved.
- Purge page/object/CDN caches after plugin file deploy (see storefront-specific notes in historical cutover docs).

---

## Rollback

1. Deactivate plugin (optional).
2. Replace `wp-content/plugins/{slug}/` with the previous Release ZIP contents (or restore folder backup).
3. Reactivate; verify version in **Plugins** screen.
4. For **biopentra-storefront**, follow legacy coexistence rules in `docs/storefront-migration-checklist.md` if reactivating old standalone plugins.

---

## Storefront-specific notes

See also `docs/storefront-0.4.0-production-cutover.md` and `CHANGELOG.md` (storefront sections). Current header **0.5.2** aligns with tag **`storefront-v0.5.2`**.

---

## Automation reference

Implemented workflows are documented in **`docs/github-actions-release.md`**.
