# biopentra-storefront 0.5.9

**Mini-cart product-row border fix** — visual refinement only.

## What Changed

- Removed the harsh inherited frame around mini-cart product rows.
- Replaced accidental boxed borders with a soft bottom-only separator.
- Reset row-level `border`, `box-shadow`, `outline`, and pseudo-elements under `.bp-mini-cart--drawer`.
- Improved product image, title, price, and quantity control alignment.
- Kept the compact premium drawer layout and existing quantity controls.
- Header-auth module version **1.5.9**; plugin version **0.5.9**.

## Compatibility

- Scoped to `.bp-mini-cart--drawer` only.
- WooCommerce fragments, mini-cart AJAX, and quantity update behavior unchanged.
- Does not modify WooCommerce core, Elementor Pro, Blocksy core, or payment gateway plugins.

## Install / Upgrade

1. Download **`biopentra-storefront-0.5.9.zip`** from this release.
2. Upload via **Plugins → Add New → Upload**, or use **Dashboard → Updates** on production.
3. Clear site/cache layers if drawer styles appear stale.

## Rollback

Reinstall **`biopentra-storefront-0.5.8.zip`** from the previous GitHub release.
