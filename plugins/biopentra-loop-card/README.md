# Biopentra Loop Card

WordPress plugin for Elementor **Loop Grid** product cards:

- Image, title, price on the card (WooCommerce widgets + featured image).
- **Hover** (desktop): overlay to pick **variation / strength**, **Clear**, **Close**, **Add to cart** (WooCommerce AJAX).
- **Tap** (touch): first tap opens overlay; card background click goes to product page on hover-capable desktop only.
- **Out of stock**: red banner when the product has **no** purchasable stock; overlay hidden.
- **Partial stock**: in-stock variation pills active; out-of-stock pills disabled / grayed.

## Install

Copy this folder to:

`wp-content/plugins/biopentra-loop-card/`

(requires main file `biopentra-loop-card.php` at that path.)

Activate **Biopentra Loop Card** in WP Admin → Plugins.

On first load the plugin updates Elementor library template ID **3608** (filter `biopentra_loop_card_template_post_id` to override). Clears Elementor CSS cache for that template.

## Requirements

- WooCommerce  
- Elementor Pro (Loop Grid + loop-item template)

## Optional

Delete option `biopentra_loop_card_tpl_v1` and reload once to re-run the Elementor template upgrade.
