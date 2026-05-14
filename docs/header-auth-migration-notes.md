# Header auth — storefront migration notes (Phase 4)

**Scope:** Code now lives under `plugins/biopentra-storefront/modules/header-auth/`. The standalone plugin `plugins/biopentra-header-auth/` remains in the repository; **production cutover is not implied** by merging this code.

## Parity checklist

| Item | Status |
|------|--------|
| Shortcode `[biopentra_header_auth]` | Same tag and callback name `biopentra_header_auth_shortcode` |
| Elementor widget type | `get_name()` → `biopentra_header_auth` (unchanged) |
| Elementor class | `\Biopentra_Header_Auth\Elementor_Widget_Header_Auth` — **same namespace and class name** (file copied; no `class_alias` required) |
| Style/script handles | `biopentra-header-auth`, `biopentra-wc-account-forms`, `biopentra-cart-enhancements` |
| Hook priorities | `wp_enqueue_scripts` **5** and **100** (account before cart registration order), `woocommerce_account_menu_items` **99**, `blocksy:woocommerce:single-product:post-class` **20**, `woocommerce_before_cart_totals` **5**, `admin_init` **5**, `plugins_loaded` **20** (Elementor boot) |
| Filters / options / constant | Same names as legacy (`biopentra_header_auth_*`, `BIOPENTRA_HEADER_AUTH_CART_FREE_SHIPPING_THRESHOLD`, etc.) |
| Text domain in strings | Still `biopentra-header-auth` in migrated PHP (matches legacy `.pot` expectations) |

## Guard behaviour

`Biopentra_Storefront_Header_Auth_Module::init()` returns immediately when `is_plugin_active( 'biopentra-header-auth/biopentra-header-auth.php' )` is true. That avoids:

- **Duplicate shortcode** registration (PHP fatal).
- **Duplicate Elementor widget** registration.
- **Double** cart progress, Blocksy post-class filter, account menu filter, and duplicate CSS/JS enqueues.

While the legacy plugin is active, **only** the legacy plugin should register header-auth behaviour.

## Risks

### Elementor

Saved documents reference widget type **`biopentra_header_auth`**. The PHP class name and namespace were **not** changed, so Elementor can instantiate the widget the same way as before. Risk: editor cache or opcode cache serving an old class definition after deploy — **regenerate Elementor CSS / clear caches** after cutover.

### Shortcode

Any double registration fatals. The guard prevents storefront from registering when legacy is active.

### Blocksy AJAX add-to-cart

The `blocksy:woocommerce:single-product:post-class` filter is security/UX sensitive for quick-view flows. Regression testing on shop + quick view is mandatory before production.

### Cart progress

`woocommerce_before_cart_totals` at priority **5** must run once. Guard prevents duplicate output when legacy is off.

### Duplicate plugins

Never leave **both** `biopentra-header-auth` and storefront header-auth **hooking** simultaneously. The intended transition is: staging QA with legacy **off**, storefront **on**.

## Rollback

1. Reactivate **`biopentra-header-auth`**.
2. Optionally deactivate **`biopentra-storefront`** if other modules must also roll back; otherwise legacy + storefront (Phases 1–3) can coexist only if legacy header-auth is active — **then** storefront header-auth module no-ops, so header behaviour comes from legacy again.

## Asset URLs

With storefront owning the module, `BIOPENTRA_HEADER_AUTH_URL` points at `…/plugins/biopentra-storefront/modules/header-auth/`. Purge CDN/HTML cache after cutover so clients load new URLs.
