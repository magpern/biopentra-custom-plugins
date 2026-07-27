# Module: header-auth (Phase 4)

**Source (legacy):** `plugins/biopentra-header-auth/` — retired monorepo stub; runtime lives in this module.

**Implemented in storefront:** Shortcode `[biopentra_header_auth]`, Elementor widget type `biopentra_header_auth`, WC account/checkout styles, **checkout v2 order/payment refinements** (`checkout-v2-order-payment.css`, scoped to `body.bp-checkout-v2`), cart free-shipping progress, Blocksy `ct-ajax-add-to-cart` filter, optional Blocksy global palette admin migration.

**Guard:** Legacy `biopentra-header-auth` is not installed on dev or production. This module owns all header-auth behavior via storefront.

**Elementor:** Widget class remains `\Biopentra_Header_Auth\Elementor_Widget_Header_Auth` in `includes/elementor-widget-header-auth.php`.
