# Custom plugin audit (Biopentra + WC Inventory)

**Scope:** Plugins vendored under `plugins/` in this repository (snapshots from the WooCommerce project). Third-party plugins (WooCommerce core, Elementor, etc.) are out of scope.

## Inventory

| Slug | Plugin name | Main file | Version | Author |
|------|-------------|-----------|---------|--------|
| biopentra-contact-inbox | Biopentra Support Desk | `biopentra-contact-inbox.php` | 2.0.0 | Biopentra |
| biopentra-header-auth | Biopentra Header Auth | `biopentra-header-auth.php` | 1.5.0 | BioPentra |
| biopentra-loop-card | Biopentra Loop Card | `biopentra-loop-card.php` | 1.2.4 | *(header)* |
| biopentra-footer-contact | Biopentra Footer Contact | `biopentra-footer-contact.php` | 1.1.1 | Biopentra |
| biopentra-information-megamenu | Biopentra Information Mega-menu | `biopentra-information-megamenu.php` | 1.3.2 | Biopentra |
| wc-inventory-overview | WC Inventory Overview | `wc-inventory-overview.php` | 1.17.0 | WC Inventory Overview |
| custom-variation-stock-selector | Custom Variation Stock Selector | `custom-variation-stock-selector.php` | 1.0.0 | Biopentra |
| biopentra-storefront | Biopentra Storefront | `biopentra-storefront.php` | 0.1.0 (draft) | Biopentra |

## biopentra-contact-inbox

- **Purpose:** Support desk, Fluent Forms bridge, custom tables (`*_biopentra_inbox_*`), IMAP/SMTP, mail worker REST (`biopentra-support/v1`), reply templates.
- **Admin:** Top-level menu + settings; capability `manage_biopentra_inbox`.
- **Cron:** `biopentra_inbox_imap_sync`, `biopentra_inbox_archived_cleanup`.
- **REST:** `/health`, `/messages/import`, `/worker/status`.
- **WooCommerce:** Optional placeholders (store address, logos) in email template class only.

## biopentra-header-auth

- **Purpose:** Elementor widget + shortcode `[biopentra_header_auth]`; WC account/checkout CSS; cart free-shipping progress; Blocksy hooks.
- **Key hooks:** `wp_enqueue_scripts`, `elementor/*`, `woocommerce_before_cart_totals`, `woocommerce_account_menu_items`, `blocksy:woocommerce:single-product:post-class`, `admin_init` (palette).

## biopentra-loop-card

- **Purpose:** Loop grid price HTML, Elementor widget timing, shop query / live search, store notice, Age Gate compatibility, optional Elementor template upgrade.
- **Key hooks:** `woocommerce_get_price_html`, Elementor `widget/*`, `elementor/query/query_args`, `pre_get_posts`, `wp_enqueue_scripts`, `init`.

## biopentra-footer-contact

- **Purpose:** `[biopentra_footer_email]` shortcode; `wp_robots` noindex for placeholder pages (`_biopentra_placeholder_page`).

## biopentra-information-megamenu

- **Purpose:** Front-end CSS for Information mega-menu (`wp_enqueue_scripts`).

## wc-inventory-overview

- **Purpose:** Inventory movements, batches, profitability, exchange rates, admin AJAX and `admin_post_*`.
- **Tables:** `wc_io_inventory_movements`, `wc_io_purchase_batches`, `wc_io_purchase_batch_lines`, `wc_io_purchase_batch_costs`, `wc_io_exchange_rates`.
- **Options:** `wc_io_*` prefix (see plugin settings class).

## custom-variation-stock-selector

- **Purpose:** Auto-select in-stock variation when WC embeds `product_variations` JSON.
- **Hooks:** `before_woocommerce_init` (HPOS), `woocommerce_before_variations_form`.

## Cross-plugin overlap

- HPOS `declare_compatibility` appears in WC IO and CVSS (and will appear in storefront variation module).
- Multiple plugins enqueue front-end assets on `wp_enqueue_scripts` (no shared pipeline yet).
- Elementor coupling: header-auth, loop-card, information-megamenu.

## Risks (summary)

- **contact-inbox:** Credentials in options; REST worker token; mailer vs other SMTP plugins.
- **wc-inventory-overview:** Large admin surface, custom SQL / `posts_clauses`.
- **loop-card:** Elementor internal hooks, `pre_get_posts` priority 9999.
- **CVSS:** No-op when variation threshold prevents embedded JSON.
