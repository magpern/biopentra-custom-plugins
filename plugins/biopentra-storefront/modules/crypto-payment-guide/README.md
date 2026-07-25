# Module: crypto-payment-guide

Cart informational banner and compact checkout link pointing to `/how-to-pay-with-crypto/`.

## Options

- `biopentra_crypto_guide_enabled` — default `yes`
- `biopentra_crypto_guide_page_id` — optional page ID override; falls back to slug `how-to-pay-with-crypto`

## Hooks

| Hook | Priority | Output |
|------|----------|--------|
| `woocommerce_before_cart` | 8 | Full banner |
| `woocommerce_review_order_before_payment` | 3 | Compact link only |
| `wp_enqueue_scripts` | 125 | Scoped CSS on cart/checkout |

## Filters

- `biopentra_crypto_guide_page_url`
- `biopentra_crypto_guide_cart_banner_html`
- `biopentra_crypto_guide_checkout_link_html`

Guide page content is imported separately — see `dev/biopentra-custom-plugins/content/crypto-payment-guide/`.
