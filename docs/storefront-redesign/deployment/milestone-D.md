# Milestone D — Deployment

**Status:** COMPLETE on dev — awaiting product-owner approval before tags / production  
**Target:** `dev.biopentra.eu` only  
**No production replay** until approval (D3 is the hard gate for B/C/D replay).

## Versions

| Package | Version | Tag (pending approval) |
|---|---|---|
| `biopentra-storefront` | **0.8.0** | `storefront-v0.8.0` |
| `biopentra-loop-card` | **1.6.1** | `v1.6.1` |
| `biopentra-blocksy-child` | **1.1.0** | `v1.1.0` |

## Dev deploy steps

1. Code is bind-mounted for storefront + loop-card.
2. Sync child theme via `docs/DEV-SYNC.md` (docker cp + chown www-data).
3. Migrate SEO meta (idempotent):

```bash
cd /opt/biopentra/apps/wordpress
docker compose run --rm -T wpcli wp eval-file \
  wp-content/plugins/biopentra-storefront/scripts/migrate-seo-grid-meta-cli.php
docker compose run --rm -T wpcli wp eval-file \
  wp-content/plugins/biopentra-storefront/scripts/validate-seo-grid-meta-cli.php
docker compose run --rm -T wpcli wp elementor flush-css
docker compose run --rm -T wpcli wp cache flush
```

4. Run `tools/run-dev.sh --milestone-d` once.

## Production replay (after approval)

1. Install release ZIPs for storefront 0.8.0, loop-card 1.6.1, child 1.1.0.
2. Run migrate + validate SEO meta CLIs.
3. Flush trio + Cloudflare purge (especially CSS).
4. `storefront-acceptance/tools/run-prod.sh`
5. Complete QA in this record.

**Forbidden until D3 evidence + D tagged:** replaying B/C/D Elementor/CLI work to production.

## Rollback

| Layer | Action |
|---|---|
| SEO meta | `rollback-seo-grid-meta-cli.php` (legacy PHP fallback while present) |
| Storefront | Previous ZIP `storefront-v0.7.0` |
| Loop-card | Previous ZIP `v1.6.0` |
| Child | Previous ZIP `1.0.0` + DEV-SYNC / extract |
| Caches | `wp elementor flush-css && wp cache flush` |
