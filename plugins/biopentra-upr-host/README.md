# Biopentra UPR Host

Host adapters for [Universal Product Reviews](https://github.com/magpern/universal-product-reviews) on Biopentra.

## Responsibilities

- Hybrid delivery bridge: `mpcf_fulfillment_state_changed` → `upr_order_delivery_confirmed` (`delivered` / `shipped_fallback`)
- Support invitation actions from structured Fluent `wc_related_order` + allowlisted tags (no free-text)
- PDP review availability UX via named WooCommerce hooks
- Fail-closed DEV mail verification: `wp biopentra-upr-host verify-dev-mail`

## Requirements

- WooCommerce
- universal-product-reviews ≥ 0.2.0
- mp-commerce-fulfillment with `mpcf_fulfillment_state_changed` (for delivery)

## WP6 note

Catalogue-hidden discontinued behaviour is an UPR-core gap at v0.2.0 — see `docs/upr-integration/m3-wp6-discontinued-verification.md`. Do not patch around it here.
