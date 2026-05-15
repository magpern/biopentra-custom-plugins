# Storefront legacy plugin removal checklist

**Purpose:** Safe **deletion** of standalone plugin folders superseded by **`biopentra-storefront`** (Phases 1–4).  
**Do not run removal** until every gate below is checked. **This document does not delete anything by itself.**

**Related:** `docs/legacy-plugin-retirement-plan.md`, `docs/storefront-0.4.0-production-cutover.md`, `docs/release-process.md`.

---

## Plugins in scope for removal

| Legacy plugin | Replaced by storefront module |
|---------------|------------------------------|
| `biopentra-information-megamenu` | Information megamenu |
| `biopentra-footer-contact` | Footer contact |
| `custom-variation-stock-selector` | Variation stock selector (CVSS) |
| `biopentra-header-auth` | Header auth |

## Do not remove

| Plugin | Reason |
|--------|--------|
| `biopentra-storefront` | Active consolidated plugin |
| `biopentra-loop-card` | Standalone; not part of storefront 0.4.x |
| `biopentra-contact-inbox` | Out of scope |
| `wc-inventory-overview` | Out of scope |

---

## Pre-removal gates (all required)

Work from project root (e.g. `/home/magpern/woocommerce` with `./wp`).

### 1. Storefront active

- [ ] `./wp plugin is-active biopentra-storefront` exits **0**
- [ ] Installed version is **0.4.0** or newer (`./wp plugin get biopentra-storefront --field=version`)

### 2. Replaced legacy plugins inactive

- [ ] `./wp plugin is-active biopentra-information-megamenu` → **not** active
- [ ] `./wp plugin is-active biopentra-footer-contact` → **not** active
- [ ] `./wp plugin is-active custom-variation-stock-selector` → **not** active
- [ ] `./wp plugin is-active biopentra-header-auth` → **not** active

### 3. Stability period or explicit approval

- [ ] **14 calendar days** of stable production with storefront-only behaviour for all four areas **or**
- [ ] **Explicit written owner/ops approval** to remove before 14 days (document approver + date)

Monitoring starts after the **last** cutover change (Elementor cleanup + final cache purge), not first staging deploy. See `docs/legacy-plugin-retirement-plan.md`.

### 4. Backups

- [ ] **Database:** Full dump (or host snapshot) taken **after** stable cutover; stored off-site
- [ ] **Plugin folders:** Tar/ZIP of `wp-content/plugins/` including `biopentra-storefront` **and** all four legacy trees (even if inactive)
- [ ] Restore drill completed on a disposable instance (optional but recommended)

### 5. Elementor — no legacy references

- [ ] No hardcoded `<script src="…/biopentra-information-megamenu/.../information-mega.js">` in `_elementor_data` / element cache (see `docs/elementor-information-mega-js-cleanup.md`)
- [ ] Header **Biopentra login** widget renders from storefront paths
- [ ] Footer `[biopentra_footer_email]` works without legacy plugin

### 6. Frontend — no old plugin paths

Spot-check Network / page source on production (or staging mirror):

- [ ] No requests to `wp-content/plugins/biopentra-information-megamenu/`
- [ ] No requests to `wp-content/plugins/biopentra-footer-contact/`
- [ ] No requests to `wp-content/plugins/custom-variation-stock-selector/`
- [ ] No requests to `wp-content/plugins/biopentra-header-auth/` for runtime assets (except during intentional rollback test)

Expected: assets under `wp-content/plugins/biopentra-storefront/...`.

### 7. No 404s on critical assets

- [ ] Megamenu CSS/JS from storefront loads **200**
- [ ] Footer contact JS from storefront loads **200**
- [ ] CVSS `cvss-bridge.js` from storefront loads **200**
- [ ] Header-auth CSS/JS from storefront loads **200** (when applicable)

### 8. Rollback ZIPs exist

Confirm artifacts are available off the server (repo `builds/zips/` or release storage):

- [ ] `biopentra-storefront-0.4.0.zip` (or current production version)
- [ ] `biopentra-information-megamenu-1.3.2.zip` (or version that was deployed)
- [ ] `biopentra-footer-contact-1.1.1.zip`
- [ ] `custom-variation-stock-selector-1.0.0.zip`
- [ ] `biopentra-header-auth-1.5.0.zip` (includes **guarded** main file if reinstalled)

Build from repo: `./scripts/build-zips.sh`

---

## Removal procedure

**Environment:** Staging first, then production during a low-traffic window.

### Step A — Confirm status

```bash
cd /path/to/woocommerce   # project root with ./wp

./wp plugin status biopentra-storefront
./wp plugin status biopentra-information-megamenu
./wp plugin status biopentra-footer-contact
./wp plugin status custom-variation-stock-selector
./wp plugin status biopentra-header-auth
```

All four legacy plugins must show **Inactive** before delete.

### Step B — Delete via WP-CLI (one at a time or in sequence)

```bash
./wp plugin delete biopentra-information-megamenu
./wp plugin delete biopentra-footer-contact
./wp plugin delete custom-variation-stock-selector
./wp plugin delete biopentra-header-auth
```

`wp plugin delete` removes the plugin **directory** from `wp-content/plugins/` when the web user has permission.

### Step C — Verify removal

```bash
./wp plugin list --status=inactive
ls -la wp-content/plugins/ | grep -E 'information-megamenu|footer-contact|variation-stock-selector|header-auth'
```

Legacy folders should be **gone**; `biopentra-storefront`, `biopentra-loop-card`, `biopentra-contact-inbox`, and `wc-inventory-overview` must remain.

### Step D — Post-delete

- [ ] Purge page cache, object cache (if safe), Elementor CSS cache, CDN
- [ ] Re-run frontend spot checks (megamenu, footer, header auth, variable products, cart)
- [ ] Monitor logs for 24–72 hours

---

## Permission fallback (if `wp plugin delete` fails)

WordPress often cannot delete folders when the plugin directory is owned by **root** or another user (e.g. `biopentra-information-megamenu` created as `root:root`).

**Symptom:** `wp plugin delete` reports success partially, or folders remain; admin “Delete” fails silently.

**Fix ownership** (adjust user/group to match your stack — commonly `www-data`):

```bash
sudo chown -R www-data:www-data wp-content/plugins/biopentra-information-megamenu
./wp plugin delete biopentra-information-megamenu
```

Repeat per plugin:

```bash
sudo chown -R www-data:www-data wp-content/plugins/biopentra-footer-contact
./wp plugin delete biopentra-footer-contact

sudo chown -R www-data:www-data wp-content/plugins/custom-variation-stock-selector
./wp plugin delete custom-variation-stock-selector

sudo chown -R www-data:www-data wp-content/plugins/biopentra-header-auth
./wp plugin delete biopentra-header-auth
```

**Docker Compose** example:

```bash
docker compose exec -u root wordpress chown -R www-data:www-data /var/www/html/wp-content/plugins/biopentra-information-megamenu
docker compose exec wordpress wp plugin delete biopentra-information-megamenu --path=/var/www/html
```

**Last resort (host root only, after backup):**

```bash
sudo rm -rf wp-content/plugins/biopentra-information-megamenu
```

Use only when WP-CLI delete fails after `chown`; confirm path twice.

---

## Rollback instructions

If behaviour regresses after removal:

1. **Reinstall legacy ZIPs** (WordPress **Plugins → Add New → Upload**, or unzip into `wp-content/plugins/`).
2. **Reactivate** the legacy plugins you need:
   ```bash
   ./wp plugin activate biopentra-information-megamenu
   ./wp plugin activate biopentra-footer-contact
   ./wp plugin activate custom-variation-stock-selector
   ./wp plugin activate biopentra-header-auth
   ```
3. **Deactivate `biopentra-storefront`** only if you must roll back **all** consolidated features:
   ```bash
   ./wp plugin deactivate biopentra-storefront
   ```
   Partial rollback (e.g. header-auth only): reactivate **one** legacy plugin; storefront module for that phase should no-op when legacy is active — still avoid running duplicate hooks long-term.
4. **Restore DB backup** only if Elementor meta or other data was changed and must be reverted.
5. **Clear caches and CDN** (HTML + plugin static paths).

---

## Repo vs production

| Location | Action |
|----------|--------|
| **Production host** | Remove folders via checklist above when gates pass |
| **Git monorepo** | Optional — teams often **keep** legacy plugin sources in `plugins/` for history; removal from Git is a separate, deliberate commit |

Do **not** remove `biopentra-loop-card`, `biopentra-contact-inbox`, or `wc-inventory-overview` as part of this checklist.

---

## Sign-off

| Field | Value |
|-------|--------|
| Environment | Staging / Production |
| Date | |
| Storefront version | |
| Days stable (or approval ref) | |
| Performed by | |
| Verified by | |
