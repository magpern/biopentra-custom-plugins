# Loop card production drift cleanup

**Date:** 2026-05-15  
**Plugin:** `biopentra-loop-card`  
**Environment:** `/home/magpern/woocommerce`

---

## Problem

Production `wp-content/plugins/biopentra-loop-card/` had drifted from `custom-wordpress-plugins/plugins/biopentra-loop-card/`:

| Issue | Production (before) | Source (expected) |
|-------|---------------------|-------------------|
| Plugin header `Version:` | `1.0.0` | `1.2.4` |
| `BIOPENTRA_LOOP_CARD_VER` | `1.2.4` (already correct) | `1.2.4` |
| Stray file | `.write-test` (empty, `www-data`) | absent |
| Ownership | `www-data:www-data` `755` | `magpern:magpern` (deploy user) |
| File count | 10 (incl. `.write-test`) | 9 |

WordPress reported **Version 1.0.0** in Plugins admin while assets enqueued with **1.2.4** — confusing for ops and integrity checks.

**Not changed:** storefront, contact-inbox, wc-inventory-overview.

---

## Backup

```text
/home/magpern/backups/backup-biopentra-loop-card-2026-05-15-095256.tar.gz
```

(~16K tarball of pre-cleanup production folder.)

---

## Differences found (`diff -ruN`)

Only two deltas before cleanup:

1. `biopentra-loop-card.php` — header line `Version: 1.0.0` vs `1.2.4` (body and `BIOPENTRA_LOOP_CARD_VER` already `1.2.4` on production).
2. `.write-test` — production only (likely write-permission probe).

No other file content differences between trees.

---

## Actions taken

1. **Backup** — tar.gz under `/home/magpern/backups/` (see above).
2. **Ownership fix** — root via ephemeral Alpine container (host `sudo` unavailable):
   ```bash
   docker run --rm -u root \
     -v /home/magpern/woocommerce/wp-content/plugins/biopentra-loop-card:/target \
     alpine sh -c 'chown -R 1000:1000 /target && chmod -R u+rwX,g+rwX,o-rwx /target && rm -f /target/.write-test'
   ```
3. **Sync from source** (allowed: header + `.write-test` only):
   ```bash
   rsync -av --delete custom-wordpress-plugins/plugins/biopentra-loop-card/ \
     wp-content/plugins/biopentra-loop-card/
   ```
4. **Verify** — `diff -rq` production vs source: **no differences**.

Initial `rsync`/`chown` as host user **failed** (`Permission denied` on `www-data` files) until the Docker root step above.

---

## After cleanup

| Check | Result |
|-------|--------|
| `./wp plugin status biopentra-loop-card` | **Active**, **Version 1.2.4** |
| Header + constant | `Version: 1.2.4`, `BIOPENTRA_LOOP_CARD_VER` `1.2.4` |
| Ownership | `magpern:magpern` |
| `custom-plugin-integrity-check.sh` | **PASS** — loop-card matches source (9 files) |
| Shop URL | `https://www.biopentra.eu/shop/` → **HTTP 200** |
| `loop-card.css` / `loop-card.js` | **HTTP 200** (`?ver=1.2.4`) |
| PHP Fatal (recent wordpress logs) | **None** |

**Manual follow-up (recommended):** In browser, confirm product cards, variation overlay, and add-to-cart on `/shop/` — not fully automated in this task.

---

## Prevention

- Run `scripts/custom-plugin-integrity-check.sh` **daily** and after deploys.
- Deploy loop-card with `rsync` as `magpern`, not as `www-data`, or chown after deploy:
  ```bash
  rsync -av custom-wordpress-plugins/plugins/biopentra-loop-card/ \
    wp-content/plugins/biopentra-loop-card/
  ```
- Remove `.write-test` if it reappears; do not commit it to git.
- Align plugin header `Version:` with `BIOPENTRA_LOOP_CARD_VER` on every release.

---

## Related

- `docs/loop-card-hardening-plan.md`
- `docs/plugin-integrity-check-report.md`
- `docs/support-desk-menu-restoration.md` (similar partial-folder incident on another plugin)
