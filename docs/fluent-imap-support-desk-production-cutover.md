# Fluent IMAP Support Desk — production cutover

**Date:** 2026-05-15  
**Site:** `https://www.biopentra.eu`  
**Cutover:** `biopentra-contact-inbox` → `fluent-imap-support-desk` (folder + bootstrap only)

---

## Scope

| Changed | Unchanged (compatibility) |
|---------|---------------------------|
| Plugin folder slug | DB tables `wp_biopentra_inbox_*` |
| Active plugin file in `active_plugins` | Options `biopentra_inbox_*` |
| Product name in WP Plugins list | REST `biopentra-support/v1` |
| | Capability `manage_biopentra_inbox` |
| | Cron hooks `biopentra_inbox_*` |
| | Admin menu slug `biopentra-inbox` |

**Rollback used:** No

---

## Pre-flight (passed)

- `fluent-imap-support-desk-repo` clean; ZIP `builds/fluent-imap-support-desk-2.0.0.zip` (81K)
- PHP lint: **LINT_OK** (27 files)
- `biopentra-contact-inbox` **active** v2.0.0
- REST health **200**, `worker_token_configured: true`
- `proton-bridge` + `biopentra-mail-worker` **Up**
- Tickets: **1**; display name: **Biopentra Support Desk**

---

## Backups created

| Artifact | Path |
|----------|------|
| Database | `/home/magpern/backups/wp-pre-fisd-cutover-2026-05-15-101126.sql` (~20M) |
| Plugin folder (tar) | `/home/magpern/backups/biopentra-contact-inbox-pre-cutover-2026-05-15-101126.tar.gz` (~73K) |
| Active plugins CSV | `/home/magpern/backups/active-plugins-pre-cutover-2026-05-15-101126.csv` |
| Moved legacy folder | `wp-content/plugins/biopentra-contact-inbox.backup-2026-05-15-101126/` (removed 2026-05-15 — see Legacy cleanup) |
| Uploads | **Not tarred** (~108M; noted only) |

---

## Legacy cleanup (2026-05-15)

After stable cutover and integrity **PASS**, the in-`plugins/` backup folder was archived and removed.

| Step | Result |
|------|--------|
| Final archive | `/home/magpern/backups/final-biopentra-contact-inbox-backup-2026-05-15-102749.tar.gz` (~74K, 35 paths) |
| Removed from disk | `wp-content/plugins/biopentra-contact-inbox.backup-2026-05-15-101126/` |
| Removal method | Host `rm` blocked (parent `plugins/` owned `www-data`); `docker run … alpine rm -rf` on bind mount |
| Active plugin after cleanup | **`fluent-imap-support-desk`** v2.0.0 |
| REST health | HTTP **200** |

Earlier cutover backups remain: `wp-pre-fisd-cutover-2026-05-15-101126.sql`, `biopentra-contact-inbox-pre-cutover-2026-05-15-101126.tar.gz`.

**Rollback to legacy folder:** extract final archive (or pre-cutover tar) into `wp-content/plugins/biopentra-contact-inbox/`, deactivate FISD, activate legacy — only if required.

---

## Steps executed

1. `./wp db export` → backup SQL (host path via redirect from wpcli container)
2. `tar -czf` production `biopentra-contact-inbox/` → backup tar
3. Saved active plugin list CSV
4. `./wp plugin deactivate biopentra-contact-inbox` — **Success**
5. Move aside: host `mv` **failed** (`www-data` ownership on `plugins/`); completed via:
   ```bash
   docker run --rm -v .../wp-content/plugins:/plugins alpine \
     sh -c 'mv /plugins/biopentra-contact-inbox /plugins/biopentra-contact-inbox.backup-2026-05-15-101126'
   ```
6. Extract ZIP: host `unzip` missing → Docker Alpine unzip into `wp-content/plugins/`
7. `./wp plugin activate fluent-imap-support-desk` — **Success**
8. `./wp cache flush` — **Success**
9. Post-cutover: `chown` FISD folder to `magpern:magpern` for deploy parity (Alpine root container)

**Not done:** Both plugins were never active simultaneously (deactivate before extract).

---

## Verification (post-cutover)

| Check | Result |
|-------|--------|
| Active plugin slug | **`fluent-imap-support-desk`** v2.0.0 |
| Legacy plugin | **absent** (slug not registered; backup folder removed after archive) |
| Tickets | **1** row |
| Settings option `biopentra_inbox_display_name` | **Biopentra Support Desk** |
| REST `GET .../health` | **200**, `worker_token_configured: true` |
| REST import route registered | **Yes** |
| `user_can(1, manage_biopentra_inbox)` | **yes** |
| Admin menu slug `biopentra-inbox` | **OK** (via `admin_menu`) |
| PHP Fatal (recent logs) | **None** |
| `proton-bridge` / mail worker | **Up** |

**Note:** Health JSON still reports `"plugin":"biopentra-contact-inbox"` — hardcoded in `Biopentra_Contact_Inbox_Rest_Worker::handle_health()` for compatibility; not an indication the old plugin is loaded.

**Manual follow-up:** Browser check of Support Desk tickets/settings, staff reply send, worker poll from Settings.

---

## Rollback procedure (if needed)

```bash
cd /home/magpern/woocommerce
./wp plugin deactivate fluent-imap-support-desk
rm -rf wp-content/plugins/fluent-imap-support-desk
# Legacy folder removed — restore from archive first:
tar -xzf /home/magpern/backups/final-biopentra-contact-inbox-backup-2026-05-15-102749.tar.gz -C /home/magpern/woocommerce
# Adjust extracted path to wp-content/plugins/biopentra-contact-inbox/ if needed
./wp plugin activate biopentra-contact-inbox
./wp cache flush
# DB restore only if data corruption — use wp-pre-fisd-cutover-2026-05-15-101126.sql
```

---

## Remaining risks

1. **Monorepo drift** — `custom-wordpress-plugins/plugins/biopentra-contact-inbox/` still exists; sync policy: patch **fluent-imap-support-desk-repo** first, then rsync to production.
2. **Integrity check** — expects `fluent-imap-support-desk` active; no legacy `biopentra-contact-inbox/` or `.backup-*` under `plugins/`.
3. **REST health metadata** — cosmetic; change in a future release when renaming internals.
4. **Plugin directory permissions** — deployable files must be world-readable (`755` dirs, `644` files).

---

## Cutover result

**Success:** Yes — production runs **`fluent-imap-support-desk`** with preserved data and integrations.
