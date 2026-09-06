# biopentra-storefront 0.9.44 — release notes

**Bugfix — show price on out-of-stock simple product pages.**

## Highlights

- Sold-out simple products no longer hide the price on the product detail
  page.
- Root cause: PDP-1 suppresses Blocksy's outer price layer and relocates
  WooCommerce's native price into the purchase panel, which only opens when
  `simple.php` renders the add-to-cart form (in-stock only).
- Out-of-stock simples now output the native price next to the native stock
  message, inside `.bp-pdp-purchase-panel`. Related/upsell cards on the same
  page are unchanged.

## Scope

- Storefront PDP markup only. WooCommerce remains the price authority.
- No database, schema, or settings change.

## Install

1. Download `biopentra-storefront-0.9.44.zip` from this Release.
2. Replace the plugin on the target WordPress host (DEV may bind-mount git;
   production uses this ZIP only when separately authorized).

## Notes

- Tag: `storefront-v0.9.44`
- Rollback baseline: `storefront-v0.9.43`
- Production rollout still requires an explicit GO beyond this tag.
