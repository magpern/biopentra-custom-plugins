# DEV replay — Blocksy `woo_has_product_tabs=no`

**Status:** Authoritative DEV configuration correction for B4 PDP reviews.  
**Scope:** DEV (`https://dev.biopentra.eu`) only.  
**Owner repository:** `magpern/biopentra-custom-plugins` (M3 freeze / DEV replay docs).  
**Mechanism:** WordPress **theme_mod** on the active child stylesheet (`blocksy-child`), via `/opt/biopentra/scripts/dev-wp`.

## Why

Frozen B4 ([`m3-pdp-reviews-section.md`](m3-pdp-reviews-section.md)) requires Blocksy product tabs to remain **disabled** so Description / Additional / Reviews tab chrome is absent and the dedicated storefront section is the sole global `#reviews` anchor.

DEV drifted to the Blocksy default (`yes` when unset), which reintroduced Description/Additional panels and failed acceptance **A18**.

## Non-goals

- No production configuration
- No other Blocksy / customizer options
- No Elementor, sticky-buy-bar, UPR core, schema, or cache/SWAG changes
- Not a top-level `wp_options.woo_has_product_tabs` row (WordPress does not store theme mods that way)

## Script

```bash
docs/upr-integration/bin/dev-replay-blocksy-product-tabs.sh status
docs/upr-integration/bin/dev-replay-blocksy-product-tabs.sh apply    # idempotent; records rollback once
docs/upr-integration/bin/dev-replay-blocksy-product-tabs.sh verify   # expects exact "no"
docs/upr-integration/bin/dev-replay-blocksy-product-tabs.sh rollback # restores recorded pre-apply value
```

Rollback state file (gitignored under docs):

`docs/upr-integration/.dev-replay-state/woo_has_product_tabs.prev`

## Validation

```bash
bash -n docs/upr-integration/bin/dev-replay-blocksy-product-tabs.sh
# On DEV only:
docs/upr-integration/bin/dev-replay-blocksy-product-tabs.sh apply
docs/upr-integration/bin/dev-replay-blocksy-product-tabs.sh verify
/opt/biopentra/scripts/dev-wp wp theme mod get woo_has_product_tabs
# → woo_has_product_tabs   no
```

## Rollback

```bash
docs/upr-integration/bin/dev-replay-blocksy-product-tabs.sh rollback
```
