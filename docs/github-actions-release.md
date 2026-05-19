# GitHub Actions — plugin releases

**Status:** Implemented for selected plugins (2026-05-19).

---

## Active workflows

| Workflow file | Trigger | Plugin | Release title |
|---------------|---------|--------|----------------|
| [`.github/workflows/release-wc-inventory-overview.yml`](../.github/workflows/release-wc-inventory-overview.yml) | `wc-inventory-overview-v*` | wc-inventory-overview | `wc-inventory-overview {version}` |
| [`.github/workflows/release-biopentra-storefront.yml`](../.github/workflows/release-biopentra-storefront.yml) | `storefront-v*` | biopentra-storefront | `biopentra-storefront {version}` |

Each workflow:

1. Checks out the tag.
2. Verifies tag version matches plugin header + constant.
3. Runs `php -l` on plugin PHP files.
4. Runs `scripts/build-one-plugin-zip.sh {slug}`.
5. Runs `scripts/release-audit-plugin.sh {slug} {version}`.
6. Uploads Actions artifact and creates/updates a GitHub Release with the ZIP attached.
7. Uses `docs/GITHUB_RELEASE_NOTES_{slug}_{version_with_underscores}.md` as the release body.

---

## Published examples

| Tag | Release |
|-----|---------|
| `wc-inventory-overview-v1.17.1` | https://github.com/magpern/biopentra-custom-plugins/releases/tag/wc-inventory-overview-v1.17.1 |

---

## Not released from this monorepo

- **`biopentra-contact-inbox`** — deprecated; use [fluent-imap-support-desk](https://github.com/magpern/fluent-imap-support-desk) (`v2.0.2+`).

Other plugins under `plugins/` may still be built locally via `./scripts/build-zips.sh` for ad hoc backups until they gain a tagged workflow.

---

## Adding a new plugin workflow

1. Add a profile to `scripts/lib/verify-release-zip.py`.
2. Extend `release_read_version_constant()` in `scripts/lib/release-common.sh`.
3. Add `docs/GITHUB_RELEASE_NOTES_{slug}_{version}.md`.
4. Copy `release-wc-inventory-overview.yml` → `release-{slug}.yml` and adjust tag prefix.
5. Extend `release-audit-plugin.sh` repository checks.

---

## Historical note

The original proposal lived in `docs/github-actions-release-proposal.md` (storefront-only, pre-implementation). This file replaces it as the source of truth for automation status.
