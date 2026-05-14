# Elementor cleanup: legacy `information-mega.js` reference

**Scope:** Local Docker / staging only. **Do not** run this against production without change control.

**Goal:** Remove the Elementor-stored `<script>` tag that points at  
`…/plugins/biopentra-information-megamenu/assets/information-mega.js`  
so **`biopentra-storefront`** is the single owner of mega-menu JS (`wp_enqueue_script` → `assets/information-megamenu/information-mega.js`).

**Do not:** delete legacy plugin folders, alter mega-menu HTML/CSS classes, or start Phase 2 migrations.

---

## Database search (verified on local)

Strings searched:

- `information-mega.js`
- `biopentra-information-megamenu/assets/information-mega.js`

### Affected rows (`wp_postmeta`)

| Post ID | Post title | Post type | Status | Meta key |
|--------:|------------|-----------|--------|----------|
| **3782** | Elementor Header #3782 | `elementor_library` | `publish` | `_elementor_data` |
| **3782** | Elementor Header #3782 | `elementor_library` | `publish` | `_elementor_element_cache` |
| 4132 | Elementor Header #3782 | `revision` | `inherit` | `_elementor_data` |
| 4289 | Elementor Header #3782 | `revision` | `inherit` | `_elementor_data` |
| 4296 | Elementor Header #3782 | `revision` | `inherit` | `_elementor_data` |
| 4297 | Elementor Header #3782 | `revision` | `inherit` | `_elementor_data` |
| 4299 | Elementor Header #3782 | `revision` | `inherit` | `_elementor_data` |
| 4300 | Elementor Header #3782 | `revision` | `inherit` | `_elementor_data` |

**Live site behavior** is driven by the **published** template **3782**. Revisions still contain the same string until cleaned (optional for display; cleaning them keeps grep/exports tidy).

### Current stored reference (canonical)

Inside `_elementor_data` JSON, an Elementor **HTML** widget (`widgetType` → `html`) has `settings.html` equal to **only** this tag (JSON-escaped slashes and quotes as stored):

```text
<script defer src=\"http://www.biopentra.eu/wp-content/plugins/biopentra-information-megamenu/assets/information-mega.js?ver=1.3.2\"></script>
```

In the database string, quotes and slashes appear escaped for JSON, e.g. `\"` and `\/`. The same logical tag appears inside `_elementor_element_cache` for post **3782**.

---

## Recommended cleanup method

1. **Export a full SQL backup** (mandatory before any meta write).
2. **Surgical string removal:** remove **only** the exact legacy `<script …information-mega.js…></script>` substring from:
   - `_elementor_data` for post IDs **3782, 4132, 4289, 4296, 4297, 4299, 4300** (only rows that still contain the needle),
   - `_elementor_element_cache` for **3782** only.
3. **Do not** edit other Elementor nodes, mega-menu containers, or `css_classes` such as `biopentra-information-mega`.
4. In the UI, the equivalent operation is opening the header in Elementor and **clearing** the HTML widget that only contained this script (or deleting that widget). The scripted approach matches that outcome without a manual Elementor session.

**Repo helper:** `scripts/remove-elementor-information-mega-js.php` (run via `wp eval-file` with a bind mount; see below).

### Implementation note: `revision` posts and `update_post_meta()`

On this stack (WordPress **6.9** + Elementor, PHP **8.3**), `update_post_meta( $revision_id, '_elementor_data', … )` could report success while the `wp_postmeta` row for **post revisions** (`post_type` = `revision`) was **unchanged**. The helper therefore updates matching rows by **`meta_id`** via `$wpdb->update()` and clears the post meta cache. The published template **3782** (`elementor_library`) updated correctly with the API in an earlier check, but revisions **4132, 4289, 4296, 4297, 4299, 4300** required the direct write.

### Storefront script tag / PHP 8.3

If `biopentra-storefront` is active, the mega-menu module’s `script_loader_tag` filter must not pass a **literal** fourth argument to `str_replace()` (PHP 8.3 treats it as the by-reference `$count` parameter and fatals). The repo uses `preg_replace( …, 1 )` for the `defer` injection — see `plugins/biopentra-storefront/modules/information-megamenu/class-information-megamenu-module.php`.

### Local HTML checks

Use **`http://127.0.0.1/`** (or your mapped host) for `curl` against Docker. On some hosts, **`http://localhost/`** can follow redirects to the public site instead of the container.

---

## Backup command (local Docker)

From the WooCommerce project root (where `docker-compose.yml` and `./wp` live):

```bash
cd /home/magpern/woocommerce
./wp db export wp-content/uploads/elementor-information-mega-js-cleanup-pre.sql
```

Example filename used for the validated local run: `wp-content/uploads/elementor-information-mega-js-cleanup-pre-2026-05-13.sql` (do not commit).

Exports land under the bind-mounted `wp-content/uploads/` directory (ignored by git in the WooCommerce tree). **Never commit** SQL dumps to `biopentra-custom-plugins`.

---

## Apply cleanup (local / staging)

```bash
cd /home/magpern/woocommerce
export BIOPENTRA_ELEMENTOR_CLEANUP_APPLY=1
docker compose run --rm --no-deps \
  -e BIOPENTRA_ELEMENTOR_CLEANUP_APPLY \
  -v /home/magpern/woocommerce/custom-wordpress-plugins/scripts/remove-elementor-information-mega-js.php:/tmp/remove-elementor-information-mega-js.php:ro \
  wpcli eval-file /tmp/remove-elementor-information-mega-js.php
unset BIOPENTRA_ELEMENTOR_CLEANUP_APPLY
```

Omit `BIOPENTRA_ELEMENTOR_CLEANUP_APPLY=1` to run in **dry-run** mode (prints what would change).

---

## Rollback command

Restore the database from the backup taken before cleanup:

```bash
cd /home/magpern/woocommerce
./wp db import wp-content/uploads/elementor-information-mega-js-cleanup-pre.sql
```

**Alternative:** re-save the original `_elementor_data` / `_elementor_element_cache` values from the backup file manually (not recommended unless import is impossible).

After import, clear any object/page cache in front of the site if applicable.

---

## Test steps (after cleanup)

1. **Plugins:** `biopentra-storefront` **active**; `biopentra-information-megamenu` **inactive**.
2. Load the **homepage** (or any page using the Elementor header).
3. **View source** or `curl` the HTML:
   - **Must not** contain `biopentra-information-megamenu/.../information-mega.js`.
   - **Must** contain exactly **one** reference to storefront JS, e.g.  
     `plugins/biopentra-storefront/assets/information-megamenu/information-mega.js`  
     (WordPress may add `?ver=` query args).
4. **Behavior:** Information mega-menu opens/closes as before (desktop + mobile).
5. **Elementor:** Optional — open header **3782** in the editor and confirm no stray duplicate script requests in the preview network panel.

Example quick grep (adjust origin if not port 80):

```bash
curl -fsS http://localhost/ | grep -oE '[^"]*information-mega\.js[^"]*' | sort -u
```

---

## Production cutover warning

- **Do not** import a staging dump into production or run ad hoc SQL against production without a runbook.
- Take a **hosting-level backup** (DB + files) before any production Elementor meta edit.
- Coordinate **plugin** state: storefront must enqueue JS **before** removing the only script source from Elementor, or the mega-menu will lose interactivity.
- Revisions and caches can still reference old URLs until cleaned; prioritize the **published** template **3782** and clear/regenerate Elementor cache if your stack uses persistent HTML cache.

---

## Rollback test (procedure)

1. With cleanup applied and tests passing, run `./wp db import …cleanup-pre.sql`.
2. Re-fetch homepage HTML and confirm the **legacy** `biopentra-information-megamenu/.../information-mega.js` URL appears again in source (rollback succeeded).
3. For day-to-day local dev after a successful rollback drill, re-export backup and re-apply cleanup if you want the DB left in the cleaned state.
