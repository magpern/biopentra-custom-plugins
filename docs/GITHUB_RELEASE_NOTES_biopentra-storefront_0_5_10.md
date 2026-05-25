# biopentra-storefront 0.5.10

**Checkout v2 order summary card refinement** — visual refinement only.

## What Changed

- Added stronger internal padding to the checkout-v2 order summary card.
- Applied the off-white summary background to both custom checkout-v2 and Blocksy classic checkout wrappers.
- Softened the summary card border and kept a restrained premium radius/shadow.
- Improved product and totals row spacing without making the panel oversized.
- Preserved right-aligned prices, prominent totals, and full-width payment cards.
- Header-auth module version **1.5.10**; plugin version **0.5.10**.

## Compatibility

- Scoped to `body.bp-checkout-v2` only.
- WooCommerce checkout AJAX, fragments, field validation, and payment gateway behavior unchanged.
- Does not modify WooCommerce core, Elementor Pro, Blocksy core, or payment gateway plugins.

## Install / Upgrade

1. Download **`biopentra-storefront-0.5.10.zip`** from this release.
2. Upload via **Plugins → Add New → Upload**, or use **Dashboard → Updates** on production.
3. Clear site/cache layers if checkout styles appear stale.

## Rollback

Reinstall **`biopentra-storefront-0.5.9.zip`** from the previous GitHub release.
