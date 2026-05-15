# Elementor CSS generation hardening

**Date:** 2026-05-15  
**Site:** `https://www.biopentra.eu`  
**Project root:** `/home/magpern/woocommerce`

---

## Root cause (incident recap)

Non-home Elementor pages linked per-page generated CSS under `wp-content/uploads/elementor/css/post-{ID}.css`.  
SiteHome (`post-3483.css`) existed and returned **200**; other pages (e.g. Shop `post-3755.css`, Cart `post-82.css`) were **missing on disk** → **404** → unstyled/full-width appearance.

`wp elementor flush-css --regenerate` updated metadata but did not create missing files until the **WP-CLI container user** could write to the bind-mounted uploads directory.

---

## Ownership / permissions audit

| Path | Mode | Owner (host) | Notes |
|------|------|--------------|--------|
| `wp-content/uploads/` | 755 | `www-data:www-data` (33:33) | OK |
| `wp-content/uploads/elementor/` | 755 | `www-data:www-data` (33:33) | OK |
| `wp-content/uploads/elementor/css/` | **775** (was 777 during incident) | `www-data:www-data` (33:33) | Group-writable by web user |

### Container UID mapping

| Container | Process user | `www-data` UID inside image |
|-----------|--------------|-----------------------------|
| `wordpress` (`wordpress:php8.3-apache`) | Apache workers run as `www-data` | **33** |
| `wpcli` (`wordpress:cli`) default | `www-data` | **82** (mismatch) |
| `wpcli` after hardening | `user: "33:33"` in compose | **33** (aligned) |

**Mismatch:** WP-CLI default UID **82** wrote files as host UID 82, while Apache/Elementor runtime expects **33**. With directory mode `755`, WP-CLI could not create new `post-*.css` files → silent regeneration failure.

---

## Fix applied

### 1. `docker-compose.yml` — `wpcli` service

Added:

```yaml
user: "33:33"
```

So `./wp` / `docker compose run wpcli` matches the WordPress Apache container’s `www-data` on bind-mounted `wp-content`.

Canonical live file: `/home/magpern/woocommerce/docker-compose.yml`  
Repo copy (for version control): `deploy/docker-compose.yml`

### 2. Directory permissions (one-time)

```bash
docker exec -u root woocommerce-wordpress-1 sh -c \
  'chown -R www-data:www-data /var/www/html/wp-content/uploads/elementor/css && chmod 775 /var/www/html/wp-content/uploads/elementor/css'
```

Removed temporary `777` from incident response. Do **not** leave `777` in production.

### 3. Ownership cleanup

Files previously created as UID 82 were chowned to `www-data:www-data` (33:33).

---

## Regeneration test (post-hardening)

1. Deleted `post-3755.css` inside the WordPress container as `www-data`.
2. Regenerated via `./wp`:

```bash
cd /home/magpern/woocommerce
./wp eval '$css = new \Elementor\Core\Files\CSS\Post(3755); $css->update();'
```

3. **Result:**
   - File recreated: `post-3755.css`
   - Owner: `www-data:www-data`
   - URL: `https://www.biopentra.eu/wp-content/uploads/elementor/css/post-3755.css` → **200**

`docker compose run --rm --no-deps wpcli id` → `uid=33 gid=33`.

---

## Future recovery commands

From `/home/magpern/woocommerce`:

```bash
# Full Elementor CSS flush + regenerate
./wp elementor flush-css --regenerate

# Single page (example: Shop page ID 3755)
./wp eval '$css = new \Elementor\Core\Files\CSS\Post(3755); $css->update();'

# Verify file exists and is served
curl -sS -o /dev/null -w '%{http_code}\n' \
  'https://www.biopentra.eu/wp-content/uploads/elementor/css/post-3755.css'
```

If `./wp` cannot write (permission error), confirm compose has `user: "33:33"` on `wpcli`, then:

```bash
docker compose config | grep -A2 'wpcli:'
docker compose run --rm --no-deps wpcli id   # expect uid=33
```

Fallback (host, one-time permission repair):

```bash
docker exec -u root woocommerce-wordpress-1 chown -R www-data:www-data \
  /var/www/html/wp-content/uploads/elementor/css
docker exec -u root woocommerce-wordpress-1 chmod 775 \
  /var/www/html/wp-content/uploads/elementor/css
```

---

## Cloudflare purge

**Not purged via API** from this environment (no Cloudflare credentials/tooling configured).

After any Elementor CSS regeneration or styling incident, purge edge cache manually:

1. Cloudflare dashboard → site `biopentra.eu` → **Caching** → **Configuration**
2. **Purge Everything** (or **Custom Purge** → include `www.biopentra.eu/wp-content/uploads/elementor/css/*`)
3. Hard-refresh browser or test in incognito

Compare:

- Logged-in admin vs incognito guest
- URL with `?nocache=1` vs without (origin can be correct while edge is stale)

---

## Operational checklist (after Elementor edits)

- [ ] `./wp elementor flush-css --regenerate` (or edit/save page in Elementor UI, which triggers regen)
- [ ] Spot-check `post-{page_id}.css` exists under `wp-content/uploads/elementor/css/`
- [ ] `curl -I` critical pages’ Elementor CSS URLs return **200**
- [ ] Cloudflare purge if production still looks wrong in guest/incognito
- [ ] Do not run WP-CLI as UID 82 against this stack without `user: "33:33"`

---

## Related docs

- `docs/urgent-styling-loss-investigation.md` — production incident write-up
- `deploy/docker-compose.yml` — versioned compose including `wpcli` user fix
