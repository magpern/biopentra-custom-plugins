# Proposal: GitHub Actions for release artifacts

**Status:** Superseded — see **[github-actions-release.md](github-actions-release.md)** for implemented workflows.

This file is kept for history. The original proposal described storefront-only automation before `wc-inventory-overview` and production ZIP hardening were added.

**Implemented (2026-05-19):**

- `release-wc-inventory-overview.yml` — tag `wc-inventory-overview-v*`
- `release-biopentra-storefront.yml` — tag `storefront-v*`
- Shared scripts: `build-one-plugin-zip.sh`, `release-audit-plugin.sh`, `verify-release-zip.py`

**Next plugins (optional):** `biopentra-loop-card` and others — follow the same pattern when ready.
