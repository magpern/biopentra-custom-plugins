# biopentra-storefront 0.5.17

## Summary

Mini-cart template compatibility update for WooCommerce 10.8.x.

## Changes

- Restores the standard `woocommerce_widget_shopping_cart_total` hook inside the custom drawer subtotal area.
- Restores the standard `woocommerce_widget_shopping_cart_buttons` hook inside the sticky drawer footer.
- Aligns mini-cart remove links with the current WooCommerce mini-cart attributes, including `role="button"` and `data-success_message`.
- Preserves the premium BioPentra drawer layout, quantity controls, payment/trust logos, Elementor/Blocksy classes, and cart fragment behavior.

## Notes

The active Blocksy child checkout overrides already match the current WooCommerce core template versions and were not changed. The remaining outdated active template is in the Blocksy parent theme (`woocommerce/cart/cart.php`), and should be resolved by a vendor theme update when available.
