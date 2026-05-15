# Disaster recovery plan

**Site:** `https://www.biopentra.eu`  
**Stack:** Docker Compose at `/home/magpern/woocommerce`  
**Custom plugins repo:** `github.com:magpern/biopentra-custom-plugins`  
**Storefront release:** `storefront-v0.4.0` / `biopentra-storefront` **0.4.0**

This plan assumes **no further storefront consolidation** during recovery — restore the **current** architecture (storefront + standalone loop-card).

---

## Recovery objectives

| Tier | RTO target (guidance) | Scope |
|------|----------------------|--------|
| **P1** | Same day | Restore public storefront, checkout, contact flows |
| **P2** | 24–48h | Full media, Elementor styling parity, mail worker |

---

## What must be backed up

| Asset | Location | Backup method (current) | Gap |
|-------|----------|----------------------|-----|
| **Database** | Docker volume `woocommerce_db_data` | **Manual** — no cron found | Automate daily dumps |
| **Uploads** | Bind mount `./wp-content` (incl. `uploads/`) | Filesystem copy / host backup | ~108M+; include in off-site |
| **Custom plugins (source)** | GitHub `biopentra-custom-plugins` | `git push` | Primary source of truth |
| **Deployed plugins (runtime)** | `wp-content/plugins/biopentra-*` | Git deploy + ZIP or rsync from repo | Match tagged release |
| **Docker stack** | `docker-compose.yml`, `.env`, `.env.worker` | Git `deploy/docker-compose.yml` + **secure** secrets store | `.env` not in git |
| **Elementor generated CSS** | `wp-content/uploads/elementor/css/` | Regenerate after restore | Do not rely on backup alone |
| **Legacy rollback ZIPs** | `builds/zips/*.zip` in repo workspace | Rebuild `./scripts/build-zips.sh` or keep off-site copies | Gitignored binaries |
| **WP core** | Docker volume `woocommerce_wp_data` | Re-pull image / reinstall | `wp-content` bind mount is critical |

---

## Estimated recovery order

```text
1. Provision host + Docker
2. Restore secrets (.env, .env.worker) from password manager / vault
3. Copy deploy/docker-compose.yml → docker-compose.yml; docker compose up -d db
4. Restore MariaDB dump into db volume
5. Restore wp-content bind mount (themes, plugins, uploads, mu-plugins)
6. docker compose up -d wordpress (+ mail-worker, proton-bridge if used)
7. Verify wpcli user 33:33 in compose; ./wp core version
8. ./wp plugin list — activate biopentra-storefront, loop-card, inventory as needed
9. ./wp option update woocommerce_coming_soon no
10. ./wp elementor flush-css --regenerate + per-page CSS if needed
11. chown/chmod uploads/elementor/css (www-data, 775) — see hardening doc
12. Cloudflare DNS + purge cache
13. Run scripts/health-check.sh
14. Smoke-test Contact, Shop, Cart, Checkout
```

---

## Scenario A: Total server loss

### 1. Restore from GitHub

```bash
git clone git@github.com:magpern/biopentra-custom-plugins.git
cd biopentra-custom-plugins
./scripts/build-zips.sh
```

Deploy custom plugins to `wp-content/plugins/`:

- **Preferred:** unzip `builds/zips/biopentra-storefront-0.4.0.zip` (and `biopentra-loop-card-*.zip`, `wc-inventory-overview-*.zip`) via WP admin or CLI
- **Dev/staging:** rsync `plugins/biopentra-storefront/` → `wp-content/plugins/biopentra-storefront/`

Copy `deploy/docker-compose.yml` to server `woocommerce/docker-compose.yml`. Recreate external networks/volumes or adjust compose for greenfield volumes.

### 2. Restore database

If a dump exists:

```bash
cd /home/magpern/woocommerce
gunzip -c /path/to/backup-YYYY-MM-DD.sql.gz | ./wp db import -
# or: docker exec -i woocommerce-db-1 mariadb -uwordpress -p$MYSQL_PASSWORD wordpress < dump.sql
```

Create dumps going forward:

```bash
./wp db export /home/magpern/backups/wp-$(date +%F).sql
gzip /home/magpern/backups/wp-$(date +%F).sql
```

### 3. Restore uploads

Restore tarball/rsync of `wp-content/uploads/` to `/home/magpern/woocommerce/wp-content/uploads/`.

```bash
sudo chown -R www-data:www-data wp-content/uploads
sudo chmod 775 wp-content/uploads/elementor/css
```

### 4. Restore Docker stack

```bash
cd /home/magpern/woocommerce
docker compose up -d
docker compose ps
./wp --info
```

Ensure `wpcli` service includes `user: "33:33"` (see `deploy/docker-compose.yml`).

### 5. Restore Elementor CSS

```bash
./wp elementor flush-css --regenerate
./wp eval '
foreach ( get_posts( array( "post_type" => "page", "posts_per_page" => -1,
  "meta_key" => "_elementor_edit_mode", "meta_value" => "builder" ) ) as $p ) {
  ( new \Elementor\Core\Files\CSS\Post( $p->ID ) )->update();
}'
```

Verify: `scripts/health-check.sh`

### 6. Restore storefront / legacy ZIPs

| ZIP | Use |
|-----|-----|
| `biopentra-storefront-0.4.0.zip` | Production consolidated plugin |
| `biopentra-loop-card-1.2.4.zip` | Shop loop (standalone) |
| Legacy ZIPs in `builds/zips/` | Rollback only — do not install alongside storefront unless rolling back |

Legacy plugin **folders** remain in git for history; production should **not** re-enable them unless rolling back storefront.

---

## Scenario B: Database corruption only

1. Put site in maintenance (optional).  
2. Restore latest `wp db export` dump.  
3. `./wp cache flush`  
4. Spot-check orders, WC pages, Elementor pages.

---

## Scenario C: Styling loss (Elementor CSS 404)

See `docs/urgent-styling-loss-investigation.md` and `docs/elementor-css-generation-hardening.md`.

1. Confirm `woocommerce_coming_soon` = `no`  
2. Fix `wpcli` UID **33:33**  
3. Regenerate CSS + Cloudflare purge  
4. `scripts/health-check.sh`

---

## Scenario D: Bad plugin deploy

1. Deactivate affected plugin: `./wp plugin deactivate <slug>`  
2. Remove folder or replace with previous ZIP from `builds/zips/`  
3. Activate known-good version  
4. Clear caches

Storefront rollback: deactivate storefront, reinstall legacy ZIPs **only** if full feature rollback required (see `docs/legacy-plugin-retirement-plan.md`).

---

## Retention policy (recommended)

| Backup | Retention | Storage |
|--------|-----------|---------|
| DB daily | 14 daily + 4 weekly | Off-server encrypted |
| `wp-content/uploads` | 7 daily incrementals | Off-server |
| Git tags / ZIPs | Indefinite (tags), 90d (artifacts) | GitHub Releases |
| Legacy plugin tar (host) | 90d | `/home/magpern/backups/` |

**Current state:** Only ad-hoc backup observed (`backup-custom-variation-stock-selector-2026-05-15-*.tar.gz`). **Implement scheduled DB + uploads backups.**

---

## Post-recovery validation

- [ ] `scripts/health-check.sh` → PASS  
- [ ] Guest view: Home, Contact, About, FAQ, Shop, Cart  
- [ ] No Elementor `post-*.css` 404  
- [ ] Storefront megamenu, footer email, header auth  
- [ ] Orders/checkout (if stock available)  
- [ ] Mail worker + proton-bridge (if applicable)

---

## Contacts / references

- Operations runbook: `docs/operations-and-maintenance.md`  
- Final consolidation status: `docs/final-storefront-consolidation-status.md`  
- Compose reference: `deploy/docker-compose.yml`
