# Phase 4 — `biopentra-header-auth` pre-migration audit

**Status:** Audit complete; **no code migration** has been performed in this step.  
**Plugin version audited:** `1.5.0` (`BIOPENTRA_HEADER_AUTH_VERSION`).  
**Storefront placeholder:** `plugins/biopentra-storefront/modules/header-auth/README.md` (planned only).

This document inventories the legacy plugin, lists hooks and public APIs to preserve, calls out migration risks, and recommends how to land Phase 4 in `biopentra-storefront` **without** changing production or deleting legacy folders yet.

---

## 1. Repository layout inspected

Root: `plugins/biopentra-header-auth/`

| Path | Role |
|------|------|
| `biopentra-header-auth.php` | Constants, requires, asset registration, WC account/checkout enqueue, account menu filter, Blocksy product-class filter, Elementor category + `plugins_loaded` boot + shortcode registration |
| `includes/markup.php` | URL helpers, icon markup, `biopentra_header_auth_get_html()` (used by shortcode + Elementor `render()`) |
| `includes/elementor-widget-header-auth.php` | Elementor `Widget_Base` subclass (`get_name()` → **`biopentra_header_auth`**) |
| `includes/cart-enhancements.php` | Free-shipping threshold helpers, cart-only CSS enqueue, `woocommerce_before_cart_totals` progress UI |
| `includes/blocksy-global-palette.php` | One-time Blocksy `colorPalette` theme mod + option flag |
| `assets/header-auth.css` | Header auth / dropdown styling |
| `assets/header-auth.js` | Vanilla JS: click toggle for logged-in dropdown, outside click + Escape to close |
| `assets/wc-account-forms.css` | WooCommerce My Account + Checkout cosmetic overrides |
| `assets/cart-enhancements.css` | Cart page layout + free-shipping progress styling |

**Note:** There is no `index.php` silencer in this tree; migration can add one under storefront for parity with other plugins if desired.

---

## 2. Complete PHP file list

1. `biopentra-header-auth.php`  
2. `includes/markup.php`  
3. `includes/elementor-widget-header-auth.php`  
4. `includes/cart-enhancements.php`  
5. `includes/blocksy-global-palette.php`  

---

## 3. Complete asset list

| Asset | Enqueued when |
|-------|----------------|
| `assets/header-auth.css` | Registered `wp_enqueue_scripts` @5; enqueued from shortcode, `biopentra_header_auth_get_html()`, and Elementor `get_style_depends()` |
| `assets/header-auth.js` | Registered @5; enqueued when logged-in markup is built (`get_html` / widget render path) |
| `assets/wc-account-forms.css` | `is_account_page()` **or** `is_checkout()` — `wp_enqueue_scripts` @100 |
| `assets/cart-enhancements.css` | `is_cart()` — `wp_enqueue_scripts` @100 |

---

## 4. Shortcodes

| Tag | Callback | Attributes (`shortcode_atts`) |
|-----|----------|-------------------------------|
| `[biopentra_header_auth]` | `biopentra_header_auth_shortcode` | `login_text`, `logout_text`, `my_account_text`, `identifier` (`email` \| `display_name` \| `user_login`) |

Default dropdown when logged in (no Elementor repeater HTML) is built in `markup.php` via `biopentra_header_auth_default_dropdown_html()`.

---

## 5. Elementor integration

| Item | Detail |
|------|--------|
| **Category** | `elementor/elements/categories_registered` → category slug **`biopentra`**, title “Biopentra” |
| **Widget registration** | `plugins_loaded` @**20** → if `\Elementor\Plugin` exists, add `elementor/widgets/register` → `register( new \Biopentra_Header_Auth\Elementor_Widget_Header_Auth() )` |
| **Critical:** `get_name()` | Returns **`biopentra_header_auth`** — Elementor persists this as `_widgetType` in post meta. **Do not rename** without a documented migration / alias strategy. |
| **Script/style deps** | Widget declares `get_style_depends()` / `get_script_depends()` → both **`biopentra-header-auth`** (same string for style and script handles; different asset types). |

**Boot order note (from source comments):** Using only `elementor/loaded` is insufficient if this plugin loads after Elementor in `active_plugins` order; **`plugins_loaded` @20** is intentional.

---

## 6. WooCommerce hooks and behaviour

| Hook | Type | Priority | File | Purpose |
|------|------|----------|------|---------|
| `wp_enqueue_scripts` | action | **100** | `biopentra-header-auth.php` | Enqueue `biopentra-wc-account-forms` on account + checkout; inline CSS variables `--bph-wc-accent*` |
| `woocommerce_account_menu_items` | filter | **99** | `biopentra-header-auth.php` | Remove `downloads` endpoint from sidebar |
| `wp_enqueue_scripts` | action | **100** | `cart-enhancements.php` | Cart-only `biopentra-cart-enhancements` + inline vars on `body.woocommerce-cart` |
| `woocommerce_before_cart_totals` | action | **5** | `cart-enhancements.php` | Free-shipping progress message + progress bar |

**URL helpers (Woo-aware):** `biopentra_header_auth_login_url()`, `biopentra_header_auth_my_account_url()` use `wc_get_page_permalink( 'myaccount' )` when available.

---

## 7. Blocksy hooks

| Hook | Type | Priority | File | Purpose |
|------|------|----------|------|---------|
| `blocksy:woocommerce:single-product:post-class` | filter | **20** | `biopentra-header-auth.php` | Conditionally append **`ct-ajax-add-to-cart`** when Blocksy AJAX add-to-cart is enabled but product context would otherwise omit the class (quick view / non-product `get_post_type()` during admin-ajax). |

**Dependencies:** `blocksy_get_theme_mod`, `blocksy_manager()`, `WC_Product` global, `woocommerce_cart_redirect_after_add`, product types include `woosb` (bundle).

**Admin-only Blocksy migration:** `admin_init` @5 in `blocksy-global-palette.php` — sets theme mod `colorPalette` when active (or parent) theme template is **`blocksy`**, once per site unless option reset.

---

## 8. Cart / free-shipping logic

| Mechanism | Detail |
|-----------|--------|
| **Constant** | `BIOPENTRA_HEADER_AUTH_CART_FREE_SHIPPING_THRESHOLD` (default **200.0**, store currency units) — definable in `wp-config.php` before load |
| **Filter** | `biopentra_header_auth_cart_free_shipping_threshold` |
| **Subtotal** | Default `WC()->cart->get_subtotal()`; override via `biopentra_header_auth_cart_free_shipping_subtotal` |
| **UI** | Echoed HTML `.bph-cart-free-shipping*` with `wc_price()` for “remaining” copy |

Accent on cart reuses **`biopentra_header_auth_wc_account_accent`** / **`_hover`** filters (same as account/checkout inline vars).

---

## 9. My Account and checkout styling

- **Condition:** `is_account_page() || is_checkout()`.
- **Style handle:** `biopentra-wc-account-forms`.
- **Dependencies:** Prefer `ct-woocommerce-styles`, else `ct-main-styles`, else first available of `woocommerce-general` / `woocommerce-layout`.
- **Inline:** Sets CSS custom properties on `body.woocommerce-account` and `body.woocommerce-checkout`.

---

## 10. Dependencies (runtime)

| Dependency | Where used |
|--------------|------------|
| **WooCommerce** | Account/checkout/cart pages, URLs, cart totals hook, `wc_price`, `WC()->cart`, optional HPOS (no explicit declare in this plugin) |
| **Elementor** | Category + widget (editor + front) |
| **Blocksy theme stack** | Account style deps; `blocksy:woocommerce:single-product:post-class`; optional palette migration |
| **WordPress** | Shortcode, users, theme mods, options |

**Header JS:** No jQuery dependency (IIFE, native DOM).

---

## 11. Hook priorities and enqueue order (preserve)

1. **`wp_enqueue_scripts` @5** — Register `biopentra-header-auth` style + script (no unconditional enqueue except registration).
2. **`wp_enqueue_scripts` @100** — Two callbacks registered in this order: first main plugin (account/checkout), then `cart-enhancements.php` (cart). **Keep relative order** when consolidating so inline deps on Blocksy handles behave the same.

Other priorities: **`woocommerce_account_menu_items` @99**, **`woocommerce_before_cart_totals` @5**, **`blocksy:woocommerce:single-product:post-class` @20**, **`admin_init` @5** (palette), **`plugins_loaded` @20** (Elementor boot).

---

## 12. Filters and options (public / persisted)

### Filters (must remain callable with same names unless a compatibility shim is provided)

| Filter | Default / behaviour |
|--------|---------------------|
| `biopentra_header_auth_wc_account_accent` | `#1f5fae` — used account/checkout **and** cart inline CSS |
| `biopentra_header_auth_wc_account_accent_hover` | `#174a87` |
| `biopentra_header_auth_cart_free_shipping_threshold` | From constant, min clamped to 0.01 |
| `biopentra_header_auth_cart_free_shipping_subtotal` | Cart subtotal for progress |
| `biopentra_header_auth_skip_blocksy_palette_migration` | If `true`, skip palette `admin_init` routine |

### Options / theme mods

| Key | Purpose |
|-----|---------|
| `biopentra_header_auth_blocksy_palette_applied` | `'1'` after one-time palette write |
| Theme mod `colorPalette` | Written once by palette migration (Blocksy) |

---

## 13. Risk register

| Risk | Severity | Notes |
|------|----------|--------|
| **Duplicate shortcode** | High | If legacy + storefront both call `add_shortcode( 'biopentra_header_auth', … )`, PHP fatals. Mirror footer-contact pattern: **`shortcode_exists()` guard** or require legacy deactivated before module init. |
| **Duplicate Elementor widget type** | High | Second registration of `biopentra_header_auth` can fatal or corrupt editor. Guard: only register when legacy plugin inactive **or** detect class already registered. |
| **Duplicate `biopentra` Elementor category** | Low–Med | `add_category` may be idempotent; verify no duplicate categories / notices when both plugins register. Prefer single registration in storefront or `category_exists`-style check if Elementor API allows. |
| **Widget class namespace / autoload** | Med | Moving file under storefront changes path; **keep** `\Biopentra_Header_Auth\Elementor_Widget_Header_Auth` class name and file load, **or** prove Elementor does not serialize PHP class beyond widget type (still risky to rename). |
| **Account menu filter interaction** | Med | Priority **99** — other plugins may add/remove endpoints; confirm “Downloads” still hidden and no unexpected menu loss. |
| **Checkout / account CSS regression** | Med | Depends on Blocksy dequeue strategy; dep chain on `ct-woocommerce-styles` must remain. |
| **Cart progress double output** | Med | If hook registered twice, duplicate progress bars. |
| **Blocksy AJAX class regression** | High | Quick-view / AJAX add-to-cart is subtle; wrong `post-class` behaviour → full page POST instead of AJAX. |
| **Asset load order / duplicate handles** | Med | Same handle `biopentra-header-auth` for CSS and JS is valid in WP; changing handles breaks Elementor declared deps — if URLs change, bust caches aggressively. |
| **Frontend auth state** | Med | Logged-out link vs logged-in dropdown; `wp_enqueue_script` only when logged in — ensure fragment cache / FPC does not serve logged-in HTML to anonymous users (generic FPC issue, not plugin-specific). |
| **Palette admin_init** | Low | Runs on **any** admin load until option set; moving to storefront should preserve option key or document migration for sites that skipped legacy. |
| **HPOS** | Low | Legacy plugin does not declare `custom_order_tables`; storefront may want a **single** declare for the whole plugin if policy is “all modules compatible”. |

---

## 14. Exact files to migrate (copy or rewrite under storefront)

Move or re-require under e.g. `plugins/biopentra-storefront/modules/header-auth/`:

- `biopentra-header-auth.php` → split into **`class-header-auth-module.php`** (or equivalent) + thin bootstrap from storefront loader; logic must not stay in a second top-level plugin once cut over.
- `includes/markup.php`
- `includes/elementor-widget-header-auth.php`
- `includes/cart-enhancements.php`
- `includes/blocksy-global-palette.php`
- `assets/header-auth.css`
- `assets/header-auth.js`
- `assets/wc-account-forms.css`
- `assets/cart-enhancements.css`

Update **constants** to storefront-prefixed paths/URLs (`BIOPENTRA_STOREFRONT_URL` + relative path) while keeping **filter names** and **shortcode tag** stable unless a compatibility layer is agreed.

---

## 15. Exact hooks to preserve

All rows in sections **6**, **7**, and **11** above — same hook names, priorities, and callback semantics.

---

## 16. Exact assets to migrate

All files listed in section **3**; enqueue handles should remain **`biopentra-header-auth`**, **`biopentra-wc-account-forms`**, **`biopentra-cart-enhancements`** unless a deliberate v2 rename is planned (would require Elementor widget `get_*_depends` updates + cache docs).

---

## 17. Recommended module structure

**Single storefront module** entry point, e.g. `modules/header-auth/class-header-auth-module.php`:

- `init()` registers all hooks if **`! is_plugin_active( 'biopentra-header-auth/biopentra-header-auth.php' )`** (mirror CVSS / footer-contact pattern).
- **Internal includes** (not separate togglable storefront modules):
  - `markup.php` — pure helpers
  - `class-elementor-widget-header-auth.php` — keep namespace `Biopentra_Header_Auth` or add `class_alias` from new namespace to old class name for Elementor stability
  - `cart-enhancements.php`
  - `blocksy-global-palette.php`

Optional **`modules/header-auth/README.md`** update: status, cutover order, link to this audit.

**Split into multiple storefront “modules” with separate flags?** **Not recommended** for production toggles: shortcode, Elementor, WC surfaces, and Blocksy filter are too coupled; partial activation invites double hooks or missing deps. **Do** split **files** for readability.

---

## 18. Staging test matrix (before production cutover)

| # | Area | Steps |
|---|------|--------|
| 1 | Plugin guard | With legacy **active**, storefront header-auth module **must not** register shortcode/widget/hooks (or must no-op safely). |
| 2 | Cutover | Legacy **deactivate** → storefront **active**; flush opcode + page + Elementor CSS cache. |
| 3 | Shortcode | Page/HTML widget with `[biopentra_header_auth]` — logged out / logged in, attribute variants. |
| 4 | Elementor | Header template: existing **Biopentra login** widget renders; editor loads widget panel; style controls still apply. |
| 5 | My Account | Downloads absent from menu; layout and accent vars match expectation. |
| 6 | Checkout | Guest + logged-in checkout; no layout breakage. |
| 7 | Cart | Empty cart, partial subtotal, above threshold; progress bar `aria-*`; currency formatting. |
| 8 | Blocksy quick view | Variable/simple product from shop/quick view: add to cart stays AJAX (no surprise full navigation). |
| 9 | Network | Single pair of `header-auth` CSS/JS per page where expected; new URLs under `biopentra-storefront/`. |
| 10 | Console | No new errors on header interaction, cart, checkout. |
| 11 | Admin | Log in as admin on Blocksy site: palette routine does not fatal; option still respected when already `1`. |
| 12 | Rollback | Reactivate legacy, deactivate storefront module: site returns to prior behaviour. |

---

## 19. Rollback plan

1. **Reactivate** `biopentra-header-auth` (plugin files still on disk).  
2. **Deactivate** the storefront header-auth integration (or entire `biopentra-storefront` only if acceptable for other phases — prefer **module-level** off switch if implemented).  
3. **Caches:** purge page + Elementor + CDN.  
4. **DB:** normally **no** rollback needed for header-auth (no custom tables). If storefront accidentally renamed Elementor widget type in data (should never happen), restore Elementor backup.

---

## 20. Recommendation: one cutover vs sub-phases

| Approach | Verdict |
|----------|---------|
| **Smaller production sub-phases** (e.g. ship cart only first) | **Poor fit** — would require legacy and storefront to share hook namespaces without collision, or leave half the behaviour in the old plugin. High chance of double `woocommerce_before_cart_totals` or split shortcode ownership. |
| **Single atomic migration** (all hooks in one module, one PR, one staging cycle) | **Recommended** — matches Phases 1–3 pattern: develop behind guard, validate on staging, then one production deactivate of legacy. |
| **Internal file split only** | **Recommended** — keep one `init()` path, multiple `require` files for maintainability. |

---

## 21. Open questions for implementation PR

1. Should storefront declare **HPOS compatibility** once for the whole plugin (including header-auth surfaces)?  
2. Should script handles gain a **`biopentra-storefront-`** prefix for Network clarity (requires Elementor `get_*_depends` update)?  
3. Confirm no other plugin registers shortcode **`biopentra_header_auth`**.  
4. FPC / edge caching: document that header HTML is user-specific when logged in.

---

*End of audit — migration implementation intentionally deferred.*
