# biopentra-storefront 0.5.15

**Mini-cart remove drawer persistence** — keep the cart drawer open while removing items.

## What Changed

- Tracks mini-cart remove clicks inside the BioPentra drawer.
- Uses WooCommerce’s standard `remove_from_cart` AJAX endpoint for drawer remove clicks.
- Applies returned cart fragments, then re-marks and reopens the active Elementor side-cart or Blocksy cart drawer if needed.
- Leaves quantity controls, add-to-cart behavior, and checkout/cart processing unchanged.

## Compatibility

- Does not modify WooCommerce core, Elementor Pro, Blocksy core, payment gateway plugins, or commerce/business logic plugins.
- Preserves WooCommerce cart fragments and avoids full page navigation when removing items from the drawer.

## Install / Upgrade

1. Download **`biopentra-storefront-0.5.15.zip`** from this release.
2. Upload via **Plugins → Add New → Upload**, or use **Dashboard → Updates** on production.
3. Clear site/cache layers if drawer assets appear stale.

## Rollback

Reinstall **`biopentra-storefront-0.5.14.zip`** from the previous GitHub release, then clear object/site cache if needed.
