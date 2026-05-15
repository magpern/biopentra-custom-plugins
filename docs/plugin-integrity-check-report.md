# Custom plugin integrity check — report

**Date:** 2026-05-15

## Result

- **Overall:** PASS (`Result: PASS`, exit code `0`)
- Runtime checks: Support Desk REST health HTTP 200; Elementor CSS dir writable via WP-CLI (775 www-data:www-data).

## Recommended schedule

Run **daily** and **after every deploy** (or wire the same checks into CI/CD so deploys cannot complete without a green run).

## Audit summary

- **biopentra-storefront:** 25 prod / 25 src, trees match, `775` `magpern:magpern`.
- **biopentra-loop-card:** 10 prod / 9 src — extra `.write-test` in prod only; main `biopentra-loop-card.php` differs between prod and source; ownership `755` **`www-data:www-data`** (script emitted WARN).
- **biopentra-contact-inbox:** 31/31 match after restore; `775` `magpern:magpern`.
- **wc-inventory-overview:** 34/34 match; `775` `magpern:magpern`.
- **WordPress:** all four plugins active (versions at run time: storefront v0.4.0, loop-card v1.0.0, contact-inbox v2.0.0, inventory-overview v1.17.0).

## Integrity script — full output

Captured from `bash scripts/custom-plugin-integrity-check.sh` (same as `/tmp/integrity-check-output.txt` for this run):

```
=== Custom plugin integrity check ===
woocommerce: /home/magpern/woocommerce
source repo: /home/magpern/woocommerce/custom-wordpress-plugins/plugins
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
WARN biopentra-loop-card: owned www-data:www-data mode 755 (deploy user may not sync without sudo/chown)
OK   biopentra-loop-card: main file biopentra-loop-card.php
OK   biopentra-loop-card: includes/ present
OK   biopentra-loop-card: assets/ present
WARN biopentra-loop-card: prod=10 src=9 — diff: Only in /home/magpern/woocommerce/wp-content/plugins/biopentra-loop-card: .write-test;Files /home/magpern/woocommerce/custom-wordpress-plugins/plugins/biopentra-loop-card/biopentra-loop-card.php and /home/magpern/woocommerce/wp-content/plugins/biopentra-loop-card/biopentra-loop-card.php differ;
OK   biopentra-loop-card: active in WordPress (v1.0.0)

--- biopentra-contact-inbox ---
OK   biopentra-contact-inbox: folder exists
OK   biopentra-contact-inbox: permissions 775 magpern:magpern
OK   biopentra-contact-inbox: main file biopentra-contact-inbox.php
OK   biopentra-contact-inbox: includes/ present
OK   biopentra-contact-inbox: assets/ present
OK   biopentra-contact-inbox: matches source tree (31 files)
OK   biopentra-contact-inbox: active in WordPress (v2.0.0)

--- wc-inventory-overview ---
OK   wc-inventory-overview: folder exists
OK   wc-inventory-overview: permissions 775 magpern:magpern
OK   wc-inventory-overview: main file wc-inventory-overview.php
OK   wc-inventory-overview: includes/ present
OK   wc-inventory-overview: assets/ present
OK   wc-inventory-overview: cli/ present
OK   wc-inventory-overview: matches source tree (34 files)
OK   wc-inventory-overview: active in WordPress (v1.17.0)

--- runtime ---
OK   Support Desk REST health HTTP 200
OK   Elementor CSS dir writable via wpcli (775 www-data:www-data)

Result: PASS
```
