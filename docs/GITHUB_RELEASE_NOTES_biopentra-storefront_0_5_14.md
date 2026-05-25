# biopentra-storefront 0.5.14

**Checkout payment display labels/icons** — display-only checkout refinement.

## What Changed

- Added a checkout payment display module for supported gateway icons.
- BTCPay checkout rows now use the bundled Bitcoin SVG icon.
- Banxa, Klarna, and Guardarian checkout rows now use bundled Visa and Mastercard SVG icons.
- Added compact checkout-only icon alignment styles.
- Payment gateway IDs and payment processing behavior are unchanged.

## Compatibility

- Uses WooCommerce display filters only.
- Does not modify WooCommerce core, Elementor Pro, Blocksy core, payment gateway plugins, commerce/business logic plugins, or checkout processing.
- Preserves checkout AJAX refresh and selected payment behavior.

## Install / Upgrade

1. Download **`biopentra-storefront-0.5.14.zip`** from this release.
2. Upload via **Plugins → Add New → Upload**, or use **Dashboard → Updates** on production.
3. Clear site/cache layers if checkout styles appear stale.

## Rollback

Reinstall **`biopentra-storefront-0.5.13.zip`** from the previous GitHub release and restore the saved WooCommerce gateway title settings if needed.
