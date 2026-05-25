# biopentra-storefront 0.5.6

**Premium WooCommerce mini-cart drawer** — Elementor side-cart and Blocksy offcanvas styling.

## What changed

- New `modules/header-auth/` mini-cart drawer: compact product rows, sticky checkout CTA, trust row, empty/loading states.
- Custom `woocommerce/cart/mini-cart.php` template (Elementor-compatible markup).
- Assets: `mini-cart-drawer.css`, `mini-cart-drawer.js` (fragment-friendly qty updates via Blocksy AJAX + fallback).
- Header-auth module version **1.5.6**; plugin version **0.5.6**.

## Compatibility

- Elementor Pro menu cart (`side-cart`) fragments unchanged.
- Blocksy offcanvas `#woo-cart-panel` supported.
- Does not modify theme builder templates, global Elementor JSON, or WooCommerce core.

## Install / upgrade

1. Download **`biopentra-storefront-0.5.6.zip`** from this release.
2. Upload via **Plugins → Add New → Upload**, or use **Dashboard → Updates** on production (GitHub updater).
3. Clear site cache (SWAG/CDN) and run **Elementor → Tools → Regenerate CSS** if styles look stale.

## Rollback

Restore **0.5.5** plugin folder from backup or reinstall the `storefront-v0.5.5` release ZIP.
