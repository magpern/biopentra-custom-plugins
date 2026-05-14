# Migration plan: `biopentra-storefront` modules

This document maps **legacy standalone plugins** → **`biopentra-storefront` modules**. No logic has been moved yet; use this as the checklist when implementing each module.

**Rule:** Avoid duplicate hooks. During each phase, either the legacy plugin **or** the new module should be active for that concern—not both.

---

## Phase 0 — Preparation

1. Keep **all legacy plugins active** until a module is fully tested.
2. Implement module behind a **constant or option** (e.g. `BIOPENTRA_STOREFRONT_ENABLE_HEADER_AUTH`) defaulting **off** until QA passes.
3. After QA, deactivate only the corresponding legacy plugin.

---

## Module: `modules/header-auth/` (from `biopentra-header-auth`)

### Shortcodes / public API to preserve

- `[biopentra_header_auth]` — same shortcode tag **or** documented alias with deprecation (prefer keeping the same tag).

### Hooks to migrate

| Legacy hook | Purpose |
|-------------|---------|
| `wp_enqueue_scripts` (priority 5) | Register `header-auth` CSS/JS |
| `wp_enqueue_scripts` (priority 100) | WC account/checkout form styles |
| `woocommerce_account_menu_items` | Hide Downloads menu item |
| `woocommerce_before_cart_totals` | Free shipping progress (cart-enhancements) |
| `wp_enqueue_scripts` (cart-enhancements) | Cart CSS/JS |
| `blocksy:woocommerce:single-product:post-class` | AJAX add-to-cart class |
| `elementor/elements/categories_registered` | Elementor category |
| `plugins_loaded` (20) | Register Elementor widget |
| `elementor/widgets/register` | Widget registration |
| `admin_init` (5) | Blocksy global palette file (see `includes/blocksy-global-palette.php`) |

### Assets to relocate

- `assets/header-auth.css`, `assets/header-auth.js`
- `assets/wc-account-forms.css`
- `assets/cart-enhancements.css` (+ related JS if any in includes)
- PHP: `includes/markup.php`, `includes/elementor-widget-header-auth.php`, `includes/cart-enhancements.php`, `includes/blocksy-global-palette.php`

### Dependencies

Elementor, WooCommerce (conditional), Blocksy (optional paths).

### Tests before deactivating legacy

- Header widget + shortcode on logged-in / logged-out states.
- My Account and Checkout styling with Blocksy active.
- Cart page free-shipping block.
- Elementor editor: widget appears under custom category.

---

## Module: `modules/footer-contact/` (from `biopentra-footer-contact`)

### Shortcodes

- `[biopentra_footer_email]` — preserve tag.

### Hooks

| Legacy hook | Purpose |
|-------------|---------|
| `wp_enqueue_scripts` (5) | Register footer email JS |
| `wp_robots` | Noindex placeholder pages |

### Assets / constants

- `assets/footer-contact-email.js`
- Image `assets/bp-e1.png` (referenced from main plugin)
- Constant `BIOPENTRA_PLACEHOLDER_META` / `_biopentra_placeholder_page`

### Tests

- Shortcode renders; mailto still built via JS; placeholder page gets `noindex`.

---

## Module: `modules/information-megamenu/` (from `biopentra-information-megamenu`)

### Hooks

| Legacy hook | Purpose |
|-------------|---------|
| `wp_enqueue_scripts` (25) | CSS `information-mega.css` |

### Assets

- `assets/information-mega.css`
- `assets/information-mega.js` (if used after review—legacy main file currently enqueues CSS; include JS only if still needed)

### Optional

- `cli-update-megamenu.php` — keep as dev-only CLI script or document under `docs/`; do not load in production bootstrap unless required.

### Tests

- Mega-menu panel matches prior typography/layout on front.

---

## Module: `modules/variation-stock-selector/` (from `custom-variation-stock-selector`)

### Hooks

| Legacy hook | Purpose |
|-------------|---------|
| `before_woocommerce_init` | HPOS compatibility |
| `woocommerce_before_variations_form` | Register/enqueue inline jQuery after `wc-add-to-cart-variation` |

### Assets / behavior

- Inline script only today; consider moving to `assets/` file + `wp_add_inline_script` for maintainability during migration.

### Tests

- Variable product **with** embedded variations: auto-select highest in-stock purchasable price.
- Deep-link with `attribute_*` query args: must **not** override.
- Product over AJAX threshold: still no-op (documented limitation).

---

## Plugins that stay active during entire storefront rollout

- `biopentra-contact-inbox`
- `wc-inventory-overview`
- `biopentra-loop-card`

## Suggested migration order (minimize risk)

1. **information-megamenu** — CSS-only, smallest coupling.  
2. **footer-contact** — isolated shortcode + robots + one JS file.  
3. **variation-stock-selector** — single product template behavior.  
4. **header-auth** — highest coupling (Elementor + WC + Blocksy); do last.

## Double-activation guard

Before each cutover, **grep** for `add_shortcode`, `add_action`, `add_filter` in both legacy and new module; ensure only one registration runs (feature flag or `! class_exists` guard during transition).

## Rollback

If a module fails: reactivate the legacy plugin, disable the module flag, flush opcode cache if applicable.
