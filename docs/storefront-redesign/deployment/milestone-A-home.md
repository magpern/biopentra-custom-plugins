# Milestone A — Production deployment (Commercial Homepage)

**Status:** Pending — deploy after dev sign-off  
**Requires:** `biopentra-storefront` **0.6.0** (+ loop-card live-search patch)

## Pre-deploy

- [ ] Tag `storefront-v0.6.0` and publish Release ZIP (product-owner approval)
- [ ] Backup production home `_elementor_data` to `changes/backups/home-pre-A-prod.json`
- [ ] Screenshot production homepage (360×800) → `validation/milestone-A-home/before/`

## Deploy steps

1. Install `biopentra-storefront-0.6.0.zip` on production
2. Sync/install `biopentra-loop-card` release containing front-page live search enqueue
3. Run CLI (slug-based — no dev post IDs):

```bash
cd /path/to/wordpress
wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-home-page-v2-cli.php
```

4. Flush trio:

```bash
wp elementor flush-css
wp cache flush
# Cloudflare: purge https://www.biopentra.eu/
```

5. Validate:

```bash
cd storefront-acceptance
WP_BASE_URL=https://www.biopentra.eu bash tools/run-prod.sh
```

6. Manual QA: search submits to product search; category chips reach WC archives; FAQ/why content still present below grids.

## Rollback

```bash
HOME_ID=$(wp option get page_on_front)
wp post meta update "$HOME_ID" _elementor_data "$(cat backups/home-pre-A-prod.json)"
wp elementor flush-css && wp cache flush
```

Reinstall previous storefront ZIP (v0.5.21).

## QA checklist

| Check | Pass |
|---|---|
| First product ≤ 1 viewport mobile | |
| Search field visible above products | |
| Category chips link to `/product-category/*` | |
| Why / FAQ / research sections present | |
| No horizontal overflow (Playwright) | |
| Structured data unchanged (spot-check Rank Math) | |
