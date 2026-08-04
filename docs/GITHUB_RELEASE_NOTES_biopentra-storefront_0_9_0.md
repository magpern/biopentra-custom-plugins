# biopentra-storefront 0.9.0

Milestone E — Mobile polish (global chrome) on `dev.biopentra.eu`.

## Highlights

- SDS z-index coordination for CookieYes, mini-cart, and header chrome
- Universal Multicurrency: `sticky_footer` → `manual` with one header switcher (no floating bottom)
- Header touch targets ≥44×44 and frozen search control (focus commercial search or navigate to shop)
- Compact mobile footer (Elementor 3823 + chrome CSS)

## Apply after install (production replay — not in this release cycle)

```bash
wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-milestone-e-chrome-cli.php
wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-milestone-e-footer-cli.php
wp elementor flush-css && wp cache flush
```

Note: production ZIP excludes `scripts/`; copy CLIs from git for replay.

## Rollback

Install `storefront-v0.8.0`; restore Elementor/UMC backups documented in `docs/storefront-redesign/deployment/milestone-E.md`.
