# biopentra-storefront 0.5.8

**Checkout v2 order/payment refinement** — visual refinement only.

## What Changed

- Added scoped checkout-v2 styling for the order summary and payment area.
- Softer off-white surfaces for the summary and payment descriptions.
- Lighter borders, slightly larger radii, and less visually harsh separators.
- More internal padding around totals, payment rows, and gateway descriptions.
- Premium selectable payment cards with improved radio, label, and logo spacing.
- Header-auth module version **1.5.8**; plugin version **0.5.8**.

## Compatibility

- Scoped to `body.bp-checkout-v2` only.
- WooCommerce checkout AJAX, fragments, field validation, and payment gateway behavior unchanged.
- Does not modify WooCommerce core, Elementor Pro, Blocksy core, or payment gateway plugins.
- Elementor compatibility preserved; the stylesheet only loads when checkout v2 is active.

## Install / Upgrade

1. Download **`biopentra-storefront-0.5.8.zip`** from this release.
2. Upload via **Plugins → Add New → Upload**, or use **Dashboard → Updates** on production.
3. Clear site/cache layers if checkout styles appear stale.

## Rollback

Reinstall **`biopentra-storefront-0.5.7.zip`** from the previous GitHub release, or disable checkout v2:

```bash
./wp option update biopentra_checkout_v2_enabled no
```
