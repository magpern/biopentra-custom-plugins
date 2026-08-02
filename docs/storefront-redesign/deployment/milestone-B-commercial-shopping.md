# Milestone B — Production deployment (Commercial Shopping)

**Status:** Pending — do not deploy until approved  
**Requires:** `biopentra-storefront` **0.7.0**, `biopentra-loop-card` **1.5.0**

## Pre-deploy

- [ ] Tag `storefront-v0.7.0` after approval (do not tag until validation green)
- [ ] Backup production `_elementor_data` for shop + SEO pages → `changes/backups/*-pre-B-prod.json`
- [ ] Screenshot key surfaces at 360×800 before change

## Deploy steps

1. Install `biopentra-storefront-0.7.0.zip` and sync `biopentra-loop-card` **1.5.0**
2. Run CLIs (slug-based, no hard-coded dev IDs):

```bash
wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-shop-page-v2-cli.php
wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-seo-category-v2-cli.php
```

3. Flush trio:

```bash
wp elementor flush-css
wp cache flush
# Cloudflare purge /
```

4. Validate:

```bash
cd storefront-acceptance
bash tools/run-prod.sh --milestone-b   # when prod harness flag exists
# or WP_BASE_URL=https://www.biopentra.eu bash tools/run-dev.sh --milestone-b
```

## Rollback

Per page:

```bash
SHOP_ID=$(wp option get woocommerce_shop_page_id)
wp post meta update "$SHOP_ID" _elementor_data "$(cat backups/shop-pre-B-prod.json)"
# Repeat for 4430/4431/4432 with matching backup files
wp elementor flush-css && wp cache flush
```

Reinstall previous storefront ZIP (v0.6.0) and loop-card if reverting code.

## QA checklist

| Check | Pass |
|---|---|
| Shop search + chips above first product | |
| Shop first product ≤ ~1 viewport mobile | |
| Search refine preserves query | |
| Search still uses Blocksy cards (expected until C) | |
| SEO pages show product grid above body | |
| SEO long content still in page source | |
| Playwright `--milestone-b` green | |
