# Plugin integrity check report

**Last run:** 2026-05-15 (post legacy backup folder removal)  
**Script:** `scripts/custom-plugin-integrity-check.sh`  
**Result:** **PASS** (exit 0)

## Summary

| Plugin | Status | Notes |
|--------|--------|-------|
| biopentra-storefront | OK | v0.4.0 active |
| biopentra-loop-card | OK | v1.2.4 active |
| fluent-imap-support-desk | OK | v2.0.0 active, 29 deployable files match source |
| wc-inventory-overview | OK | v1.17.0 active |

**Support desk:** legacy slug inactive; **no** `biopentra-contact-inbox/` or `.backup-*` under `wp-content/plugins/`. Final legacy archive: `/home/magpern/backups/final-biopentra-contact-inbox-backup-2026-05-15-102749.tar.gz`. REST health HTTP 200.

## Full log

```
=== Custom plugin integrity check ===
woocommerce: /home/magpern/woocommerce
source repo: /home/magpern/woocommerce/custom-wordpress-plugins/plugins
fisd source: /home/magpern/fluent-imap-support-desk-repo
site:      https://www.biopentra.eu

--- biopentra-storefront ---
OK   biopentra-storefront: folder exists
OK   biopentra-storefront: permissions 775 magpern:magpern
OK   biopentra-storefront: main file biopentra-storefront.php
OK   biopentra-storefront: includes/ present
OK   biopentra-storefront: modules/header-auth/ present
OK   biopentra-storefront: modules/footer-contact/ present
OK   biopentra-storefront: modules/information-megamenu/ present
OK   biopentra-storefront: modules/variation-stock-selector/ present
OK   biopentra-storefront: assets/ present
OK   biopentra-storefront: matches source tree (25 files)
OK   biopentra-storefront: active in WordPress (v0.4.0)

--- biopentra-loop-card ---
OK   biopentra-loop-card: folder exists
OK   biopentra-loop-card: permissions 755 magpern:magpern
OK   biopentra-loop-card: main file biopentra-loop-card.php
OK   biopentra-loop-card: includes/ present
OK   biopentra-loop-card: assets/ present
OK   biopentra-loop-card: matches source tree (9 files)
OK   biopentra-loop-card: active in WordPress (v1.2.4)

--- fluent-imap-support-desk ---
OK   fluent-imap-support-desk: folder exists
OK   fluent-imap-support-desk: permissions 755 magpern:magpern
OK   fluent-imap-support-desk: main file fluent-imap-support-desk.php
OK   fluent-imap-support-desk: includes/ present
OK   fluent-imap-support-desk: assets/ present
OK   fluent-imap-support-desk: matches deployable source (29 files)
OK   fluent-imap-support-desk: active in WordPress (v2.0.0)

--- wc-inventory-overview ---
OK   wc-inventory-overview: folder exists
OK   wc-inventory-overview: permissions 775 magpern:magpern
OK   wc-inventory-overview: main file wc-inventory-overview.php
OK   wc-inventory-overview: includes/ present
OK   wc-inventory-overview: assets/ present
OK   wc-inventory-overview: cli/ present
OK   wc-inventory-overview: matches source tree (34 files)
OK   wc-inventory-overview: active in WordPress (v1.17.0)

--- support desk compatibility ---
OK   legacy plugin biopentra-contact-inbox inactive
OK   no live legacy biopentra-contact-inbox/ folder
WARN manage_biopentra_inbox not true in WP-CLI context (verify logged-in admin in browser)
--- runtime ---
OK   Support Desk REST health HTTP 200
OK   REST health worker_token_configured=true
OK   REST health plugin id biopentra-contact-inbox (compatibility metadata)
OK   REST import route reachable (HTTP 401 without token — expected)
OK   Elementor CSS dir writable via wpcli (775 www-data:www-data)

Result: PASS
```

## Run locally

```bash
cd /home/magpern/woocommerce/custom-wordpress-plugins
bash scripts/custom-plugin-integrity-check.sh
```
