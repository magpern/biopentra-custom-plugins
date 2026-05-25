# biopentra-storefront 0.5.11

**Checkout v2 order summary separators** — visual refinement only.

## What Changed

- Restored visible but soft 1px separators inside the checkout-v2 order summary card.
- Uses `#e1e5ea` / `#e3e6eb` separator tones that remain premium against the off-white card background.
- Adds clearer section boundaries under the heading, between product rows and totals, through discount/shipping/fee sections, before total, and before payment methods.
- Keeps the current card background, padding, radius, payment card styling, and right-aligned prices.
- Header-auth module version **1.5.11**; plugin version **0.5.11**.

## Compatibility

- Scoped to `body.bp-checkout-v2` only.
- WooCommerce checkout AJAX, fragments, field validation, and payment gateway behavior unchanged.
- Does not modify WooCommerce core, Elementor Pro, Blocksy core, or payment gateway plugins.

## Install / Upgrade

1. Download **`biopentra-storefront-0.5.11.zip`** from this release.
2. Upload via **Plugins → Add New → Upload**, or use **Dashboard → Updates** on production.
3. Clear site/cache layers if checkout styles appear stale.

## Rollback

Reinstall **`biopentra-storefront-0.5.10.zip`** from the previous GitHub release.
