# biopentra-storefront 0.9.43 — release notes

**Feature — PDP Bulk Pricing selector module.**

## Highlights

- New `pdp-bulk-pricing` module that consumes the
  `mp_cp_bulk_pricing_storefront_v1` contract from `mp-commerce-promotions`
  (Bulk Pricing v1).
- PDP fieldset with radio tier cards, custom quantity preview, and sticky
  price sync bridge.
- Module is registered into the purchase panel after price/stock.

## Scope

- Storefront UI only; pricing authority remains in `mp-commerce-promotions`
  / LinePricingArbiter.
- Requires a compatible `mp-commerce-promotions` build with Bulk Pricing v1
  enabled and the storefront contract published.

## Install

1. Download `biopentra-storefront-0.9.43.zip` from this Release.
2. Replace the plugin on the target WordPress host (DEV may bind-mount git;
   production uses this ZIP only when separately authorized).
3. Ensure `mp-commerce-promotions` Bulk Pricing v1 is present before enabling
   live bulk SKUs.

## Notes

- Tag: `storefront-v0.9.43`
- Rollback baseline: `storefront-v0.9.42`
- Production rollout still requires an explicit GO beyond this tag.
