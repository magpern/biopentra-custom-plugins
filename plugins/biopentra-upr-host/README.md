# Biopentra UPR Host

Host adapters for [Universal Product Reviews](https://github.com/magpern/universal-product-reviews) on Biopentra.

## Responsibilities

- Hybrid delivery bridge: `mpcf_fulfillment_state_changed` → `upr_order_delivery_confirmed` (`delivered` / `shipped_fallback`)
- Support invitation actions from structured Fluent `wc_related_order` + allowlisted tags (no free-text)
- PDP review availability UX via named WooCommerce hooks
- Fail-closed DEV pilot preflight and verification CLIs

## Requirements

- WooCommerce
- universal-product-reviews **0.2.1** @ `e5b9636…` (DEV pilot pin — see `docs/upr-integration/m3-dev-upr-v0.2.1-pin.md`)
- mp-commerce-fulfillment with `mpcf_fulfillment_state_changed` (for delivery)

## DEV verification CLIs

```bash
wp biopentra-upr-host verify-pilot-preflight   # UPR pin + env gate
wp biopentra-upr-host verify-dev-mail            # LoggingMailTransport only
wp biopentra-upr-host verify-wp6-dev            # catalogue-hidden lifecycle matrix
```

All refuse when `wp_get_environment_type() !== 'development'`.

## WP6 note

Catalogue-hidden non-reviewable behaviour is enforced in UPR **v0.2.1+**. Do not reimplement revocation in this host plugin.
