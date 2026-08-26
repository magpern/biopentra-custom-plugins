# M3 DEV UPR v0.2.1 pilot pin

**Status:** DEV-only operational pin (not production).  
**Site:** `https://dev.biopentra.eu`

## Required UPR release

| Item | Value |
|------|--------|
| Annotated tag | `v0.2.1` |
| Commit | `e5b9636a42db7aaf0837c7b6034a24b062fd4275` |
| Version constant | `UPR_VERSION === '0.2.1'` |

## Pin mechanism (existing — do not add a second)

DEV installs UPR via **Docker bind-mount** only:

```yaml
# /opt/biopentra/apps/wordpress/compose.yml
- /opt/biopentra/dev/universal-product-reviews:/var/www/html/wp-content/plugins/universal-product-reviews
```

**Operational pin:** checkout the required commit in the bind-mount source tree:

```bash
cd /opt/biopentra/dev/universal-product-reviews
git fetch --tags origin
git checkout e5b9636a42db7aaf0837c7b6034a24b062fd4275
# Verify: git describe --tags --exact-match  → v0.2.1
```

No ZIP, GitHub Release, or production deploy from this pin.

## Preflight verification (fail-closed)

Run before any DEV pilot or WP6 revalidation:

```bash
cd /opt/biopentra/apps/wordpress
docker compose run --rm wpcli wp biopentra-upr-host verify-pilot-preflight
docker compose run --rm wpcli wp biopentra-upr-host verify-dev-mail
docker compose run --rm wpcli wp biopentra-upr-host verify-wp6-dev
```

`verify-pilot-preflight` refuses when:

- `wp_get_environment_type() !== 'development'`
- `UPR_VERSION !== 0.2.1`
- bind-mount Git HEAD ≠ `e5b9636…`
- host adapter or UPR inactive

## DEV replay sequence

1. Merge host pin branch (`fix/m3-upr-v0.2.1-pilot-pin`) to `main`
2. Checkout UPR at `e5b9636…` (see above)
3. Confirm compose still sets `WP_ENVIRONMENT_TYPE=development` (web + wpcli)
4. `docker compose up -d wordpress` (if needed)
5. Run preflight + mail + WP6 CLI commands
6. Drain Action Scheduler `upr` group via host cron (`scripts/wp-cron.sh`) — controlled jobs only
7. Run storefront/schema acceptance (see `m3-dev-replay.md`)

## Rollback

1. Host adapter: revert pin PR or checkout prior `main` commit (`c5834ce` M3 closure baseline)
2. UPR bind-mount: `git checkout v0.2.0` (or prior known commit `4eb1f96…`)
3. Re-run `verify-pilot-preflight` — expect failure until re-pinned intentionally
4. No production change required for rollback

## Production

This pin and replay are **DEV-only**. Production rollout requires separate approval and must not reuse bind-mount checkout without an approved release process.
