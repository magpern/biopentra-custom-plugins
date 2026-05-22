# biopentra-storefront 0.5.3

**Product stock display** — configurable labels and colors instead of WooCommerce exact stock counts.

## What changed

- New module `product-stock-display` filters `woocommerce_get_availability` on the storefront (single product, variations, `wc_get_stock_html`).
- Settings: **WooCommerce → Stock display** — thresholds, label text, hex colors; `{stock}` placeholder for “only left” messages.
- CSS: `assets/product-stock-display/stock-display.css` with `bp-stock-status--*` classes.

Does not change admin stock UI, inventory, cart, or purchasability.

## Install / upgrade

1. Download **`biopentra-storefront-0.5.3.zip`** from this release.
2. Upload via **Plugins → Add New → Upload**, or use **Dashboard → Updates** on production.

## Rollback

Restore **0.5.2** plugin folder from backup.
