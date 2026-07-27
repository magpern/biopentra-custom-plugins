# Not a WordPress plugin — pointer stub only

**This directory is not a WordPress plugin and must not be deployed.**

| Item | Location |
|------|----------|
| **Canonical Git repository** | [magpern/wc-inventory-overview](https://github.com/magpern/wc-inventory-overview) |
| **Canonical development path** | `/opt/biopentra/dev/wc-inventory-overview` |
| **Production path (example)** | `/home/magpern/woocommerce/wc-inventory-overview` |

## Release and source policy

- Releases are built **only** from the standalone repository (`v*` tags → GitHub Actions `release.yml`).
- Monorepo release tooling **intentionally excludes** the slug `wc-inventory-overview` (`RELEASE_SKIP_PLUGINS` in `scripts/lib/release-common.sh`).
- **Do not edit plugin source here.** All changes belong in the standalone repo.

## Why this stub remains

- Historical monorepo release tags (e.g. `wc-inventory-overview-v1.17.1`) for rollback reference
- Migration traceability from the pre-standalone layout
- Prevents accidental recreation of a stale mirror that could be mistaken for canonical source

See also [RELEASES.md](RELEASES.md).
