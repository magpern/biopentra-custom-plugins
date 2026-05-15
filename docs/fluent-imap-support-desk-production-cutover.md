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
| Moved legacy folder | `wp-content/plugins/biopentra-contact-inbox.backup-2026-05-15-101126/` |
| Uploads | **Not tarred** (~108M; noted only) |

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
| Legacy plugin | **inactive** (folder only in `.backup-*`) |
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
mv wp-content/plugins/biopentra-contact-inbox.backup-2026-05-15-101126 \
   wp-content/plugins/biopentra-contact-inbox
./wp plugin activate biopentra-contact-inbox
./wp cache flush
# DB restore only if data corruption — use wp-pre-fisd-cutover-2026-05-15-101126.sql
```

---

## Remaining risks

1. **Dual folder confusion** — backup folder must not be activated accidentally; remove after 7–14 days stable.
2. **Monorepo drift** — `custom-wordpress-plugins/plugins/biopentra-contact-inbox/` still exists; sync policy: patch **fluent-imap-support-desk-repo** first, then rsync to production.
3. **Integrity check** — update `custom-plugin-integrity-check.sh` manifest to expect `fluent-imap-support-desk` instead of `biopentra-contact-inbox` (follow-up).
4. **REST health metadata** — cosmetic; change in a future release when renaming internals.
5. **Plugin directory permissions** — use Docker `chown` or `magpern:magpern` `775` after deploys (same lesson as loop-card).

---

## Cutover result

**Success:** Yes — production runs **`fluent-imap-support-desk`** with preserved data and integrations.
