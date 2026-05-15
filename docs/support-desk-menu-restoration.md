# Support Desk admin menu restoration

**Date:** 2026-05-15  
**Site:** `https://www.biopentra.eu`  
**Plugin:** `biopentra-contact-inbox` (Biopentra Support Desk v2.0.0)

---

## Symptom

**Biopentra Support Desk** missing from the WordPress admin left menu. Mail worker logs showed REST **404** for `/wp-json/biopentra-support/v1/health`.

---

## Root cause

**Incomplete plugin directory on production bind mount** — not a capability problem.

| Check | Result |
|-------|--------|
| `wp-content/plugins/biopentra-contact-inbox/` | Only **2** PHP files (`class-form-resolver.php`, `class-reply-repository.php`) |
| Missing | `biopentra-contact-inbox.php` (main plugin file) and **all** other includes/assets |
| `wp plugin list` | Plugin **not registered** (“could not be found”) |
| `active_plugins` | Plugin **not** in list (WordPress had dropped unrecognized slug) |
| `manage_biopentra_inbox` on `administrator` | **Present** (`wp cap list administrator`) |
| User `bp_manager` (ID 1) | `user_can( manage_biopentra_inbox )` → **yes** after restore |
| PHP fatals in docker logs | **None** related to contact-inbox |

WordPress only loads plugins with a valid header file at `{slug}/{slug}.php` or top-level PHP with `Plugin Name:`. Without the main file, **no `admin_menu` hook runs**, **no REST routes**, and **no admin menu**.

Likely cause: accidental partial delete, failed deploy, or incomplete copy into `wp-content/plugins/` (May 15 — directory mtime ~07:52).

**Not the cause:** missing `manage_biopentra_inbox` cap, storefront changes, or plugin PHP fatal before `add_menu_page`.

---

## Fix applied

1. **PHP lint** on canonical source: `custom-wordpress-plugins/plugins/biopentra-contact-inbox/` — all files **LINT_OK** (Docker `php:8.2-cli`).
2. **Restore files** (no plugin logic changes):
   ```bash
   cd /home/magpern/woocommerce
   rsync -a custom-wordpress-plugins/plugins/biopentra-contact-inbox/ \
     wp-content/plugins/biopentra-contact-inbox/
   ```
3. **Activate plugin:**
   ```bash
   ./wp plugin activate biopentra-contact-inbox
   ```

**Storefront / other plugins:** not modified.

---

## Verification

| Check | Result |
|-------|--------|
| Plugin status | **Active** v2.0.0 |
| `admin_menu` registers slug `biopentra-inbox` | **Yes** (WP-CLI after `do_action('admin_menu')`) |
| REST `/biopentra-support/v1/health` | **Registered** (`REST_OK`) |
| Tables | `wp_biopentra_inbox_tickets`, `_messages`, `_replies` present |
| Ticket data | **1** ticket, **3** messages (preserved) |
| Display name option | `Biopentra Support Desk` |
| PHP fatal (post-fix logs) | **None** |

**Admin UI:** Log in as `bp_manager` (or any administrator) → left menu should show **Biopentra Support Desk** (dashicons-email-alt, position ~58) with **Tickets** and **Settings** submenus.

---

## Capability reference (for future incidents)

| Constant | Value |
|----------|--------|
| `BIOPENTRA_INBOX_CAP` | `manage_biopentra_inbox` |

Granted to `administrator` on activation via `Biopentra_Contact_Inbox_Activator::activate()`. Menu uses `add_menu_page( …, BIOPENTRA_INBOX_CAP, 'biopentra-inbox', … )` in `includes/class-plugin.php`.

If menu is missing but plugin is active, check capability first. If plugin is “not found”, check filesystem integrity first.

---

## Remaining risks

1. **No git tracking** under `wp-content/plugins/biopentra-contact-inbox/` — another partial wipe could recur; deploy from `custom-wordpress-plugins` or a release ZIP.
2. **Worker** was polling while plugin was down; should recover now that REST is registered (confirm worker health in **Support Desk → Settings**).
3. **Investigate** how only two `includes/` files remained (backup/restore, manual edit, or bad rsync) to prevent recurrence.
4. **Monorepo vs production** — keep `custom-wordpress-plugins/plugins/biopentra-contact-inbox/` as source of truth until a standalone repo exists.

---

## Quick recovery command

```bash
cd /home/magpern/woocommerce
rsync -a custom-wordpress-plugins/plugins/biopentra-contact-inbox/ \
  wp-content/plugins/biopentra-contact-inbox/
./wp plugin activate biopentra-contact-inbox
./wp cache flush
```
