# Monorepo plugin release process

Standardizes **versioning**, **production ZIPs**, **Git tags**, and **rollback** for first-party plugins in `biopentra-custom-plugins`.

**Do not** edit `wp-content/plugins/` on servers as the source of truth. Commit here, build a production ZIP, deploy the ZIP (or GitHub Release asset).

---

## Tag naming

| Plugin | Git tag format | Example | GitHub workflow |
|--------|----------------|---------|-----------------|
| **wc-inventory-overview** | `wc-inventory-overview-v{version}` | `wc-inventory-overview-v1.17.1` | `release-wc-inventory-overview.yml` |
| **biopentra-storefront** | `storefront-v{version}` | `storefront-v0.5.1` | `release-biopentra-storefront.yml` |

Tag version must match the plugin header `Version:` and the package constant (`WC_INVENTORY_OVERVIEW_VERSION`, `BIOPENTRA_STOREFRONT_VERSION`, etc.).

**Deprecated:** `biopentra-contact-inbox` — see `plugins/biopentra-contact-inbox/DEPRECATED.md`. Releases come from [fluent-imap-support-desk](https://github.com/magpern/fluent-imap-support-desk) (`v2.0.2+`).

---

## Build commands

**One plugin (release):**

```bash
./scripts/build-one-plugin-zip.sh wc-inventory-overview
./scripts/release-audit-plugin.sh wc-inventory-overview 1.17.1
```

**All non-deprecated plugins (local batch):**

```bash
./scripts/build-zips.sh
```

Output: `builds/zips/{slug}-{version}.zip` with top-level folder `{slug}/`.

`build-zips.sh` **skips** `biopentra-contact-inbox` and any plugin with `plugins/{slug}/DEPRECATED.md`.

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
5. Annotated tag and push:

   ```bash
   git tag -a wc-inventory-overview-v1.17.1 -m "Release wc-inventory-overview 1.17.1"
   git push origin main
   git push origin wc-inventory-overview-v1.17.1
   ```

6. Confirm GitHub Actions release workflow succeeded and the Release ZIP is attached.

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

See also `docs/storefront-0.4.0-production-cutover.md` and `CHANGELOG.md` (storefront sections). Current header **0.5.1** aligns with tag **`storefront-v0.5.1`** (supersedes `storefront-v0.4.0` for new ZIP deploys).

---

## Automation reference

Implemented workflows are documented in **`docs/github-actions-release.md`**.
