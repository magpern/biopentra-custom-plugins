# Milestone E — Deployment

**Status:** **TAGGED / RELEASED** on GitHub (2026-08-04) — **dev only**  
**Target:** `dev.biopentra.eu`  
**Production replay:** **NOT performed.** Do not install on production until Milestone F / explicit approval.

## Final validation (accepted)

| Field | Value |
|---|---|
| Date | 2026-08-04 |
| Command | `storefront-acceptance/tools/run-dev.sh --milestone-e` |
| Result | **264 passed / 0 failed / 0 flaky / 306 skipped** |

## Versions

| Package | Version | Tag |
|---|---|---|
| `biopentra-storefront` | **0.9.0** | `storefront-v0.9.0` |
| `biopentra-loop-card` | 1.6.1 (unchanged) | — |
| `biopentra-blocksy-child` | 1.1.0 (unchanged; no sticky safe-area bump required) | — |

## Release artifacts

| Artifact | Size | Release URL |
|---|---|---|
| `biopentra-storefront-0.9.0.zip` | 110976 bytes | https://github.com/magpern/biopentra-custom-plugins/releases/tag/storefront-v0.9.0 |

ZIP checks (2026-08-04): single plugin root; Plugin header + `BIOPENTRA_STOREFRONT_VERSION` = 0.9.0; chrome-v1 CSS/JS present; no `.git` / `node_modules` / `scripts` paths.

**Workflow:** https://github.com/magpern/biopentra-custom-plugins/actions/runs/30951867048 — **success**

**Acceptance HEAD:** `e09d66c` (`magpern/storefront-acceptance`)

## Dev apply procedure (idempotent)

```bash
# 1) Plugin bind-mount already serves 0.9.0 sources

# 2) UMC + header 3782 (UMC shortcode + search control)
cd /opt/biopentra/apps/wordpress
docker compose run --rm -T wpcli wp eval-file \
  wp-content/plugins/biopentra-storefront/scripts/setup-milestone-e-chrome-cli.php

# 3) Footer 3823 mobile spacing
docker compose run --rm -T wpcli wp eval-file \
  wp-content/plugins/biopentra-storefront/scripts/setup-milestone-e-footer-cli.php

# 4) Flush trio
docker compose run --rm -T wpcli wp elementor flush-css
docker compose run --rm -T wpcli wp cache flush
# Cloudflare CSS purge recommended after release
```

## Option keys

| Key | Previous | New |
|---|---|---|
| `umc_settings.display.placement` | `sticky_footer` | `manual` |
| CookieYes `cky_*` | (unchanged) | CSS-only SDS alignment |
| Elementor `_elementor_data` 3782 | pre backup | UMC shortcode + search HTML |
| Elementor `_elementor_data` 3823 | pre backup | mobile padding/gap overrides |

## Production replay (NOT executed in E)

1. Install `biopentra-storefront-0.9.0.zip` from GitHub Release.
2. Copy/run chrome + footer CLIs from git (ZIPs exclude `scripts/` per release policy) **or** restore Elementor meta from documented backups + set `umc_settings.display.placement=manual`.
3. Flush Elementor CSS + object cache + Cloudflare CSS.
4. `storefront-acceptance/tools/run-prod.sh` (when approved).

## Rollback

| Layer | Action |
|---|---|
| Storefront | Previous ZIP `storefront-v0.8.0` |
| UMC | Restore `umc_settings-2026-08-04-milestone-E-pre.json` |
| Header 3782 | Restore `header-3782-2026-08-04-milestone-E-pre.json` |
| Footer 3823 | Restore `footer-3823-2026-08-04-milestone-E-pre.json` |
| Caches | `wp elementor flush-css && wp cache flush` |

## Backups

`docs/storefront-redesign/changes/backups/` — see change records E1/E2/E3.
