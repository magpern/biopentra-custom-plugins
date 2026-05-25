# biopentra-storefront 0.5.12

**Mini-cart payment/trust logos** — visual refinement only.

## What Changed

- Added a centered payment/trust logo row below the mini-cart trust text.
- Bundled static SVG assets in the plugin source:
  - `Bitcoin-Cryptocurrency.svg`
  - `Usdc-Cryptocurrency.svg`
  - `Visa-logo.svg`
  - `Mastercard-logo.svg`
- References logos from the plugin asset URL, not the Media Library.
- Keeps the footer compact and mobile-friendly with scoped `.bp-mini-cart--drawer` CSS.
- Header-auth module version **1.5.12**; plugin version **0.5.12**.

## Compatibility

- Scoped to `.bp-mini-cart--drawer` only.
- WooCommerce fragments, mini-cart AJAX, and quantity update behavior unchanged.
- Does not modify WooCommerce core, Elementor Pro, Blocksy core, payment gateway plugins, or commerce/business logic plugins.

## Install / Upgrade

1. Download **`biopentra-storefront-0.5.12.zip`** from this release.
2. Upload via **Plugins → Add New → Upload**, or use **Dashboard → Updates** on production.
3. Clear site/cache layers if drawer styles or assets appear stale.

## Rollback

Reinstall **`biopentra-storefront-0.5.11.zip`** from the previous GitHub release.
