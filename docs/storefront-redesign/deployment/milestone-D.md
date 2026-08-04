# Milestone D — Deployment

**Status:** **TAGGED / RELEASED** on GitHub (2026-08-04) — **dev only**  
**Target:** `dev.biopentra.eu`  
**Production replay:** **NOT performed.** Do not install these ZIPs on production until explicitly approved.

## Final validation (accepted)

| Field | Value |
|---|---|
| Date | 2026-08-04 |
| Command | `storefront-acceptance/tools/run-dev.sh --milestone-d` |
| Result | **253 passed / 0 failed / 0 flaky / 272 skipped** |
| Acceptance HEAD | `1af21d1` (`magpern/storefront-acceptance`) |

## Versions, commits, tags

| Package | Version | Tag | Commit (peeled) |
|---|---|---|---|
| `biopentra-storefront` | **0.8.0** | `storefront-v0.8.0` | `31cac3752f186f593e2f18803ed252446521fb34` |
| `biopentra-loop-card` | **1.6.1** | `v1.6.1` | `31400aff53390369924d669b7190a656999507d1` |
| `biopentra-blocksy-child` | **1.1.0** | `v1.1.0` | `a7e35185d5303ec1bb6db4dd979eae6e26eef8c4` |

## Release artifacts

| Artifact | Size | Release URL |
|---|---|---|
| `biopentra-storefront-0.8.0.zip` | 106844 bytes | https://github.com/magpern/biopentra-custom-plugins/releases/tag/storefront-v0.8.0 |
| `biopentra-loop-card-1.6.1.zip` | 49346 bytes | https://github.com/magpern/biopentra-loop-card/releases/tag/v1.6.1 |
| `blocksy-child-1.1.0.zip` | 17848 bytes | https://github.com/magpern/biopentra-blocksy-child/releases/tag/v1.1.0 |

ZIP checks (2026-08-04): single plugin/theme root; Plugin/Theme headers + version constants match tags; no `.git` / `node_modules` / `.github` / `tests` paths; required PHP/CSS/JS for D1/D2 present.

**Packaging note:** storefront production ZIP **intentionally excludes** `scripts/` (established `release-audit-plugin.sh` policy). SEO migrate/validate/rollback CLIs remain in git and on bind-mounted dev; for production replay, run them from a checkout or copy those three CLI files onto the server before `wp eval-file`.

## Workflow results

| Workflow | Run | Conclusion |
|---|---|---|
| Release biopentra-storefront | https://github.com/magpern/biopentra-custom-plugins/actions/runs/30912844857 | **success** |
| Release loop-card | https://github.com/magpern/biopentra-loop-card/actions/runs/30912864517 | **success** |
| Release blocksy-child | https://github.com/magpern/biopentra-blocksy-child/actions/runs/30912846282 | **success** |

## Dev environment

1. Storefront + loop-card: bind-mounted.
2. Child theme: bind-mounted as of Milestone D closure (`apps/wordpress/compose.yml` → `themes/blocksy-child`). See `biopentra-blocksy-child/docs/DEV-SYNC.md`.
3. SEO meta already migrated on dev; validate CLI warns on pre-existing private seed slugs only.

```bash
cd /opt/biopentra/apps/wordpress
docker compose run --rm -T wpcli wp eval-file \
  wp-content/plugins/biopentra-storefront/scripts/validate-seo-grid-meta-cli.php
docker compose run --rm -T wpcli wp elementor flush-css
docker compose run --rm -T wpcli wp cache flush
```

## Production replay (not started)

1. Install the three release ZIPs above.
2. Provide SEO CLIs (from git) → migrate → validate.
3. Flush trio + Cloudflare purge (especially CSS).
4. `storefront-acceptance/tools/run-prod.sh`
5. Complete QA checklist in this record.

## Rollback

| Layer | Action |
|---|---|
| SEO meta | `rollback-seo-grid-meta-cli.php` (legacy PHP fallback while present) |
| Storefront | Previous ZIP `storefront-v0.7.0` |
| Loop-card | Previous ZIP `v1.6.0` |
| Child | Previous ZIP `v1.0.0` |
| Caches | `wp elementor flush-css && wp cache flush` |
