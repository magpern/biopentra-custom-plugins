# Legacy storefront plugin removal results

**Date:** 2026-05-15  
**Environment:** Production (`/home/magpern/woocommerce`)  
**Storefront version:** 0.4.0 (active)  
**Runbook:** `docs/storefront-legacy-removal-checklist.md`

## WP-CLI access note

`./wp` failed at session start (`docker-compose.yml` missing from project root; containers still running). WP-CLI was run via one-off `wordpress:cli` container:

```bash
docker run --rm --entrypoint php --network woocommerce_default \
  -v /home/magpern/woocommerce/wp-content:/var/www/html/wp-content \
  -v woocommerce_wp_data:/var/www/html \
  -e WORDPRESS_DB_HOST=db:3306 \
  -e WORDPRESS_DB_NAME=wordpress \
  -e WORDPRESS_DB_USER=wordpress \
  -e WORDPRESS_DB_PASSWORD=*** \
  wordpress:cli -d memory_limit=1G /usr/local/bin/wp --path=/var/www/html <command>
```

`WP_CLI_PHP_ARGS` alone was insufficient (128M limit); `memory_limit=1G` via PHP `-d` was required due to `woo-cart-abandonment-recovery` memory use during bootstrap.

---

## Summary

| # | Plugin | Result | Notes |
|---|--------|--------|-------|
| 1 | `biopentra-information-megamenu` | **Already removed** | Folder absent before this run; not in `wp plugin list` |
| 2 | `biopentra-footer-contact` | **Already removed** | Folder absent before this run; not in `wp plugin list` |
| 3 | `custom-variation-stock-selector` | **Deleted** | Backup + `wp plugin delete` (see below) |
| 4 | `biopentra-header-auth` | **Already removed** | Folder absent before this run; not in `wp plugin list` |

**Preserved (out of scope):** `biopentra-storefront`, `biopentra-loop-card`, `biopentra-contact-inbox`, `wc-inventory-overview`

---

## 1. biopentra-information-megamenu

| Step | Result |
|------|--------|
| Pre-check `wp plugin status` | Plugin not installed (no folder, no DB entry) |
| Backup | Skipped — directory did not exist on host |
| `wp plugin delete` | N/A |
| Folder verification | Absent from `wp-content/plugins/` |
| Frontend | Homepage loads `biopentra-storefront/assets/information-megamenu/information-mega.{css,js}` (200) |

**Conclusion:** Removed in a prior session (likely after `chown` + `wp plugin delete` during megamenu ownership investigation).

---

## 2. biopentra-footer-contact

| Step | Result |
|------|--------|
| Pre-check `wp plugin status` | Plugin not installed |
| Backup | Skipped — directory did not exist |
| `wp plugin delete` | N/A |
| Folder verification | Absent |
| Frontend | Homepage loads `biopentra-storefront/assets/footer-contact/footer-contact-email.js` (200) |

**Conclusion:** Already removed before this run.

---

## 3. custom-variation-stock-selector

| Step | Result |
|------|--------|
| Pre-check | **Inactive**, v1.0.0; folder owned `www-data:www-data` |
| Backup | `/home/magpern/backups/backup-custom-variation-stock-selector-2026-05-15-080432.tar.gz` |
| First `wp plugin delete` | **Failed** — “could not be deleted” (wp-cli default user lacks delete permission) |
| Retry with `--allow-root` | **Success** — `Deleted 1 of 1 plugins` |
| Folder verification | `wp-content/plugins/custom-variation-stock-selector` **gone** |
| Frontend | No legacy CVSS paths in homepage HTML; storefront `cvss-bridge.js` returns **200** |

**Conclusion:** Final legacy plugin removed in this session.

---

## 4. biopentra-header-auth

| Step | Result |
|------|--------|
| Pre-check `wp plugin status` | Plugin not installed |
| Backup | Skipped — directory did not exist |
| `wp plugin delete` | N/A |
| Folder verification | Absent |
| Frontend | Homepage loads `biopentra-storefront/modules/header-auth/assets/header-auth.{css,js}` (200) |

**Conclusion:** Already removed before this run.

---

## Post-removal verification

### Plugin list (relevant entries)

```
biopentra-loop-card          active
biopentra-storefront         active
wc-inventory-overview        active
biopentra-fluentform-contact-orders   must-use
```

Legacy slugs **not** present: `biopentra-information-megamenu`, `biopentra-footer-contact`, `custom-variation-stock-selector`, `biopentra-header-auth`.

### `wp-content/plugins/` folders

Present: `biopentra-storefront`, `biopentra-loop-card`, `biopentra-contact-inbox`, `wc-inventory-overview`  
Absent: all four legacy storefront plugin directories.

### Homepage spot-check (2026-05-15)

- HTTP **200** after redirect
- No requests to legacy plugin paths in page source
- Storefront assets load from `wp-content/plugins/biopentra-storefront/...`

### Not performed in this session

- Full Elementor `_elementor_data` SQL audit
- Variable product page / cart interactive QA
- 24–72h log monitoring
- Cache purge (object cache, Elementor CSS, CDN)

---

## Sign-off

| Field | Value |
|-------|--------|
| Environment | Production |
| Date | 2026-05-15 |
| Storefront version | 0.4.0 |
| Performed by | Cursor agent (automated run) |
| Verified by | Pending owner review |
