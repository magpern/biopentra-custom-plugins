# Operations and maintenance

**Environment:** `/home/magpern/woocommerce`  
**Custom plugins repo:** `github.com:magpern/biopentra-custom-plugins`  
**Production URL:** `https://www.biopentra.eu`

Routine operations for the consolidated plugin layout (storefront **0.5.20**, standalone plugins, pointer/retired stubs). Do **not** consolidate additional plugins without a new change plan.

---

## Architecture snapshot

| Component | Notes |
|-----------|--------|
| `biopentra-storefront` 0.5.20 | Active monorepo plugin (megamenu, footer, header auth, CVSS, and other modules) |
| `biopentra-loop-card` | Standalone — canonical source `/opt/biopentra/dev/biopentra-loop-card` |
| `wc-inventory-overview` | Standalone — canonical source `/opt/biopentra/dev/wc-inventory-overview` |
| `fluent-imap-support-desk` 2.0.4 | Standalone Support Desk — canonical source `fluent-imap-support-desk` repo |
| `biopentra-contact-inbox` | **Deprecated** monorepo archive (`DEPRECATED.md`); replaced by FISD |
| Legacy 4 storefront plugins | **Retired** — monorepo pointer stubs only; runtime in storefront modules |
| Standalone pointer stubs | `plugins/biopentra-loop-card`, `plugins/wc-inventory-overview` — docs only |
| Docker | `wordpress`, `db`, `wpcli`, `biopentra-mail-worker` |
| WP-CLI | `./wp` → `docker compose run wpcli` (**user 33:33**) |

---

## Normal deploy flow (custom plugin)

1. **Develop** in the canonical repository for the plugin (monorepo `plugins/biopentra-storefront/` or standalone repo under `/opt/biopentra/dev/`)
2. Bump version in plugin header + constant (storefront: also `CHANGELOG.md`)
3. **Commit / push** to `main`; tag if release (`storefront-v0.x.x`)
4. **Build ZIPs:**

   ```bash
   cd /home/magpern/woocommerce/custom-wordpress-plugins
   ./scripts/build-zips.sh
   ```

5. **Deploy** to production `wp-content/plugins/`:
   - Upload ZIP via WP Admin → Plugins → Add New, or
   - `rsync -a plugins/biopentra-storefront/ /home/magpern/woocommerce/wp-content/plugins/biopentra-storefront/`
6. **Activate** if new: `./wp plugin activate biopentra-storefront`
7. **Post-deploy:**

   ```bash
   cd /home/magpern/woocommerce
   ./wp cache flush
   ./wp elementor flush-css --regenerate   # if templates/CSS changed
   ```

8. **Cloudflare:** Purge Everything or `wp-content/uploads/elementor/css/*`
9. **Verify:** `custom-wordpress-plugins/scripts/health-check.sh`

---

## Rollback flow

### Storefront-only (bad 0.4.x deploy)

1. `./wp plugin deactivate biopentra-storefront`
2. Install previous ZIP from `builds/zips/biopentra-storefront-{prev}.zip`
3. Reactivate legacy plugin ZIPs **only if** full pre-consolidation behaviour required
4. Purge caches + Cloudflare

See `docs/legacy-plugin-retirement-plan.md` and `docs/release-process.md`.

### Elementor / styling regression

```bash
cd /home/magpern/woocommerce
./wp option get woocommerce_coming_soon    # must be: no
./wp elementor flush-css --regenerate
./wp cache flush
# Regenerate all builder pages if needed — see disaster-recovery-plan.md
```

### Database rollback

Restore latest `wp db export` dump only with maintenance window and owner approval.

---

## Elementor recovery commands

```bash
cd /home/magpern/woocommerce

# Full flush
./wp elementor flush-css --regenerate

# Single page (Contact = 3410, Shop = 3755, About = 3825, FAQ = 3826)
./wp eval '( new \Elementor\Core\Files\CSS\Post(3410) )->update();'

# Verify URL
curl -sS -o /dev/null -w '%{http_code}\n' \
  'https://www.biopentra.eu/wp-content/uploads/elementor/css/post-3410.css'
```

**Permissions** (if regen writes fail):

```bash
docker exec -u root woocommerce-wordpress-1 chown -R www-data:www-data \
  /var/www/html/wp-content/uploads/elementor/css
docker exec -u root woocommerce-wordpress-1 chmod 775 \
  /var/www/html/wp-content/uploads/elementor/css
```

Details: `docs/elementor-css-generation-hardening.md`

---

## Cache clearing

| Layer | Command / action |
|-------|------------------|
| WordPress object cache | `./wp cache flush` |
| Rewrite rules | `./wp rewrite flush` |
| WooCommerce transients | `./wp wc tool run clear_transients --user=bp_manager` |
| Elementor CSS | `./wp elementor flush-css --regenerate` |
| Cloudflare | Dashboard → Caching → Purge Everything |
| Browser | Hard refresh / incognito |

---

## WP-CLI usage

```bash
cd /home/magpern/woocommerce
./wp --info
./wp plugin list
./wp plugin is-active biopentra-storefront
./wp option get woocommerce_coming_soon
./wp db export ~/backups/wp-$(date +%F).sql
```

**Important:** `docker-compose.yml` must set `wpcli` `user: "33:33"` so Elementor can write CSS. Reference: `deploy/docker-compose.yml`.

---

## Docker usage

```bash
cd /home/magpern/woocommerce
docker compose ps
docker compose logs -f wordpress
docker compose restart wordpress
docker compose run --rm --no-deps wpcli wp plugin list --path=/var/www/html
```

Volumes:

| Volume | Purpose |
|--------|---------|
| `woocommerce_wp_data` | WordPress core (not plugins/uploads bind mount) |
| `woocommerce_db_data` | MariaDB data |
| `./wp-content` | **Plugins, themes, uploads** (bind mount) |

---

## Health check script

```bash
/home/magpern/woocommerce/custom-wordpress-plugins/scripts/health-check.sh
```

Checks: storefront active, coming soon off, Elementor CSS dir writable, critical `post-*.css` HTTP 200, legacy plugins inactive.

---

## Custom plugin integrity check

Detects **partial plugin folder loss** (e.g. missing main `fluent-imap-support-desk.php`) before WordPress drops the admin menu.

```bash
/home/magpern/woocommerce/custom-wordpress-plugins/scripts/custom-plugin-integrity-check.sh
```

Checks per active custom plugin (`biopentra-storefront`, `biopentra-loop-card`, `fluent-imap-support-desk`, `wc-inventory-overview`):

- Production folder and main plugin PHP file exist
- Critical `includes/`, `assets/`, and module paths exist
- File count vs source tree (monorepo `plugins/<slug>/` or `fluent-imap-support-desk-repo` for Support Desk)
- Plugin active in WordPress; **legacy `biopentra-contact-inbox` must be inactive**
- Support Desk REST `/wp-json/biopentra-support/v1/health` → HTTP 200 (health JSON may still report `plugin: biopentra-contact-inbox` — compatibility)
- Admin menu slug `biopentra-inbox`, capability `manage_biopentra_inbox`, options/tables `biopentra_inbox_*` unchanged
- Elementor CSS directory writable via `wpcli` (uid 33)

**Recommended schedule:** daily via cron (e.g. 06:00) and after any deploy/rsync into `wp-content/plugins/`. On **FAIL**, compare production to source and restore:

```bash
rsync -a /home/magpern/fluent-imap-support-desk-repo/ \
  wp-content/plugins/fluent-imap-support-desk/
./wp plugin activate fluent-imap-support-desk
```

See `docs/plugin-integrity-check-report.md`, `docs/fluent-imap-support-desk-production-cutover.md`, and `docs/support-desk-menu-restoration.md`.

**Legacy backup folder** (remove after 7–14 stable days): `wp-content/plugins/biopentra-contact-inbox.backup-*`

---

## Backup strategy (current vs recommended)

### Current (observed)

| Item | Method |
|------|--------|
| Plugin source | GitHub `biopentra-custom-plugins` |
| ZIP artifacts | `./scripts/build-zips.sh` (local `builds/zips/`, gitignored) |
| DB | `./wp db export` capable; **no scheduled cron** |
| Uploads | Host path `wp-content/uploads/` (~108M); **no automated off-site backup** |
| Docker volumes | `woocommerce_db_data`, `woocommerce_wp_data` on host under `/var/lib/docker/volumes/` |
| Ad-hoc | `/home/magpern/backups/` (e.g. legacy plugin tar) |
| `woocommerce/backups/` | Empty placeholder directory |

### Recommended

- Daily `./wp db export` + gzip → off-server (14d retention)
- Daily/rsync `wp-content/uploads` incrementals
- Store release ZIPs on GitHub Releases per tag
- Encrypt and store `.env` / `.env.worker` outside git

Full DR steps: `docs/disaster-recovery-plan.md`

---

## Monitoring recommendations

| Signal | How |
|--------|-----|
| **PHP fatals** | `docker logs woocommerce-wordpress-1 --since 24h \| grep -i 'PHP Fatal'` |
| **Disk usage** | `df -h`; `du -sh wp-content/uploads` |
| **Elementor CSS 404** | `scripts/health-check.sh` or cron curl spot-check |
| **Partial plugin loss** | `scripts/custom-plugin-integrity-check.sh` (daily + post-deploy) |
| **WooCommerce logs** | `wp-content/wc-logs/` (enable if missing) |
| **WP-Cron** | `./wp cron event list`; ensure system cron if traffic low |
| **Uptime** | External monitor on `/` and `/shop/` (200, keyword) |
| **Mail worker** | `docker compose logs biopentra-mail-worker` |

Alert on: repeated PHP Fatal, disk >85%, health-check FAIL, checkout error rate (manual).

---

## Common incidents and fixes

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| All pages unstyled except home | Missing `post-*.css` | Regenerate Elementor CSS; fix wpcli UID 33 |
| “Great things are on the horizon” | `woocommerce_coming_soon=yes` | `./wp option update woocommerce_coming_soon no` |
| `./wp` fails | Missing `docker-compose.yml` | Restore from `deploy/docker-compose.yml` |
| Contact form broken | FluentForm / unrelated | Check FluentForm plugin, not storefront |
| Support Desk menu missing | Incomplete `wp-content/plugins/fluent-imap-support-desk/` | Run integrity check; rsync from `fluent-imap-support-desk-repo/` |
| 404 legacy plugin assets | Stale Elementor HTML | DB cleanup + purge CDN |
| Shop loop wrong | `biopentra-loop-card` | Update loop-card only; not storefront |

---

## GitHub backup coverage (verified 2026-07-27)

| Item | In git? |
|------|---------|
| `plugins/biopentra-storefront` (active) + pointer/retired stubs | Yes |
| `docs/**` operational + consolidation | Yes |
| `scripts/build-zips.sh`, `scripts/health-check.sh`, `scripts/custom-plugin-integrity-check.sh` | Yes |
| `builds/zips/*.zip` | No (gitignored; rebuild via script) |
| `.env` / secrets | **No** (correct) |
| Live `wp-content` (uploads, third-party plugins) | **No** — backup separately |

Clean clone build test: `git clone` + `./scripts/build-zips.sh` → **1 ZIP** (`biopentra-storefront-{version}.zip`; skipped slugs excluded).

---

## Do-not-touch (monitoring window)

- No new storefront consolidation
- Do not recreate deployable source under retired stub directories
- Do not change `wpcli` user away from **33:33**
- Do not set `woocommerce_coming_soon` to `yes` on production

---

## Related documents

- `docs/disaster-recovery-plan.md`
- `docs/final-storefront-consolidation-status.md`
- `docs/release-process.md`
- `docs/elementor-css-generation-hardening.md`
- `docs/github-backup-plan.md`
- `docs/plugin-integrity-check-report.md`
- `docs/support-desk-menu-restoration.md`
- `docs/fluent-imap-support-desk-production-cutover.md`
