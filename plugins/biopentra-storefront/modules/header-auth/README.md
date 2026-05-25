# Module: header-auth (Phase 4)

**Source (legacy):** `plugins/biopentra-header-auth/`

**Implemented in storefront:** Shortcode `[biopentra_header_auth]`, Elementor widget type `biopentra_header_auth`, WC account/checkout styles, **checkout v2 order/payment refinements** (`checkout-v2-order-payment.css`, scoped to `body.bp-checkout-v2`), cart free-shipping progress, Blocksy `ct-ajax-add-to-cart` filter, optional Blocksy global palette admin migration.

**Guard:** If `biopentra-header-auth/biopentra-header-auth.php` is **active**, this module **does nothing** (no shortcode, Elementor, cart, Blocksy, or other hooks from storefront). **Do not** run legacy and storefront header-auth together.

**Cutover (later):** Deactivate legacy `biopentra-header-auth` after staging QA; assets load from `…/biopentra-storefront/modules/header-auth/assets/`.

**Elementor:** Widget class remains `\Biopentra_Header_Auth\Elementor_Widget_Header_Auth` in `includes/elementor-widget-header-auth.php`.
