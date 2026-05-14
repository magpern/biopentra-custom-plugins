# Storefront consolidation — phased migration checklist

**Status:** Phase **1** (Information mega-menu) **CSS + JS** are implemented in `biopentra-storefront` (enqueue from module). **Production cutover** still requires **removing or replacing** the Elementor HTML widget that injects the **old** `biopentra-information-megamenu/.../information-mega.js` URL — see `docs/information-mega-js-cutover-plan.md`. Phases 2–4 are **not** migrated. Legacy plugin folders remain in the repo; **do not** delete until Elementor cleanup is done.

**Out of scope for this consolidation:** `biopentra-loop-card`, `biopentra-contact-inbox`, `wc-inventory-overview` — do not merge or deactivate as part of these phases.

---

## Current scaffold (`plugins/biopentra-storefront/`)

| Path | Purpose |
|------|---------|
| `biopentra-storefront.php` | Plugin header, constants, `plugins_loaded` → `Biopentra_Storefront::init()` |
| `includes/class-biopentra-storefront.php` | Loads **Information megamenu** module when its class file is readable |
| `modules/information-megamenu/class-information-megamenu-module.php` | Phase 1: enqueue **CSS** (handle `biopentra-information-mega`) + **JS** (handle `biopentra-storefront-information-mega`, `defer`, footer) |
| `assets/information-megamenu/information-mega.css` | Copy of legacy CSS |
| `assets/information-megamenu/information-mega.js` | Copy of legacy JS |
| `modules/*/README.md` | Notes per module |
| Elementor HTML widget | May still reference **legacy** JS URL — **remove/replace** before deleting legacy plugin dir (`docs/information-mega-js-cutover-plan.md`) |

**Cutover:** Do **not** run `biopentra-information-megamenu` and `biopentra-storefront` **both active** for the same site, or the **stylesheet** loads twice. After switching to storefront, plan Elementor cleanup so **JS** is not requested twice from different URLs (runtime guard prevents double execution, but Network will show duplicates until the widget is fixed).

**Do not activate** `biopentra-storefront` in production until you complete QA; instructions unchanged for safety.

## Global rules (every phase)

1. **Never double-register hooks** — With both the legacy plugin and storefront module active, the same `add_action` / `add_filter` / `add_shortcode` would run twice. Workflow:
   - **Develop** with storefront **inactive** or module code behind a **default-off** flag.
   - **Cutover QA:** deactivate **only** the legacy plugin for that phase, enable the storefront module (or activate storefront with that module on).
   - **Production cutover:** same order; monitor, then remove legacy folder from deploy only after a stable period (optional; keeping the folder disabled is fine).

2. **Rollback:** Reactivate the legacy plugin; disable the storefront module / deactivate `biopentra-storefront` if needed. No database migration is required for phases 1–3; header-auth stores no custom tables (Elementor/widget data lives in Elementor).

3. **Text domains:** Prefer keeping original text domains per module (`biopentra-megamenu`, `biopentra-footer-contact`, etc.) during migration to avoid re-translating strings, or plan a single `biopentra-storefront` domain and `.pot` merge later.

4. **Plugin load order:** After merge, ensure `biopentra-storefront` loads early enough for WooCommerce / Elementor hooks (typically `plugins_loaded` priority ≤ 20 for header-auth parity).

---

## Phase 1 — `biopentra-information-megamenu` ✅ *CSS + JS in storefront; Elementor cleanup pending*

**Goal:** Information mega-menu **CSS and JS** owned by `biopentra-storefront` so the legacy plugin can eventually be removed **after** Elementor no longer hardcodes the old script URL.

**Repo status:** `class-information-megamenu-module.php` enqueues CSS + JS on `wp_enqueue_scripts` @ **25** (front-end only). Paths: `assets/information-megamenu/information-mega.css` and `information-mega.js`. Legacy plugin **not** removed.

**Production cutover:** Still requires **removing or replacing** the Elementor-injected `<script src="…/biopentra-information-megamenu/assets/information-mega.js">` — see **`docs/information-mega-js-cutover-plan.md`** (recommended: remove HTML widget, rely on `wp_enqueue_script`).

### Source layout

| File | Role |
|------|------|
| `biopentra-information-megamenu.php` | Legacy: CSS only on `wp_enqueue_scripts` @ 25. |
| `assets/information-mega.css` / `.js` | Legacy originals; **copied** into storefront `assets/information-megamenu/`. |
| `cli-update-megamenu.php` | Injects script tag into Elementor JSON — **cutover** must address DB/widget, not only PHP. |

### Hooks to preserve

| Hook | Type | Priority | Callback purpose |
|------|------|----------|------------------|
| `wp_enqueue_scripts` | action | **25** | Register + enqueue mega-menu **CSS** and **JS** (skip `is_admin()`). |
| `script_loader_tag` | filter | 10 | Add `defer` to storefront mega-menu script handle only. |

### Shortcodes

None.

### Assets (storefront)

- `assets/information-megamenu/information-mega.css` — **done**
- `assets/information-megamenu/information-mega.js` — **done** (enqueued; handle `biopentra-storefront-information-mega`)

### Implementation notes

- Style handle **`biopentra-information-mega`** unchanged for CSS parity.
- Script handle **`biopentra-storefront-information-mega`** (new) to distinguish from legacy Elementor tag.
- Version **`1.3.2`** for both CSS and JS `ver` query args.

### Test steps

1. Staging: **deactivate** `biopentra-information-megamenu`; **activate** `biopentra-storefront`.
2. Front: mega-menu interactions (desktop flyout / mobile accordion).
3. **Network:** confirm `biopentra-storefront/.../information-mega.css` and `.../information-mega.js` load; plan Elementor edit to drop duplicate legacy script request.
4. Admin: no PHP notices with `WP_DEBUG`.

### Rollback

- Deactivate `biopentra-storefront` (or disable module); reactivate `biopentra-information-megamenu`.

### Legacy plugin during migration

| Stage | `biopentra-information-megamenu` |
|-------|-----------------------------------|
| Before cutover | **Active** (while storefront inactive or module off) |
| After successful QA | **Deactivated** when storefront serves this behavior |

---

## Phase 2 — `biopentra-footer-contact`

**Goal:** Placeholder `noindex`, footer email shortcode with JS-built mailto.

### Source layout

| File | Role |
|------|------|
| `biopentra-footer-contact.php` | All logic (constants, assets, filter, shortcode). |
| `assets/footer-contact-email.js` | Client script for mailto behavior. |
| `assets/bp-e1.png` | Email image asset. |
| `index.php` | Silence / direct access guard (optional in new structure). |

### Hooks / filters to preserve

| Hook | Type | Priority | Purpose |
|------|------|----------|---------|
| `wp_enqueue_scripts` | action | **5** | Register script `biopentra-footer-contact-email`. |
| `wp_robots` | filter | default | Set `noindex` when placeholder meta matches. |

### Shortcodes to preserve

| Tag | Must remain |
|-----|-------------|
| `[biopentra_footer_email]` | **Yes** — same tag to avoid re-editing Elementor/content. |

### Constants / data contract

- **`BIOPENTRA_PLACEHOLDER_META`** / post meta key **`_biopentra_placeholder_page`** — must remain **`'1'`** when set on placeholder pages, or provide a one-time migration if renamed (not recommended).

### Assets to move

- `assets/footer-contact-email.js`
- `assets/bp-e1.png`

### Public URLs / content

- Shortcode uses `home_url( '/contact' )` for the “Contact form” link — verify path still correct after any routing changes.

### Test steps

1. Placeholder page with meta: confirm `noindex` in robots meta / `wp_robots` output.
2. Page with `[biopentra_footer_email]`: image loads, button works, mailto built via JS; **no** raw email in HTML source (privacy requirement).
3. Non-placeholder singular: no unintended `noindex` from this module alone.

### Rollback

- Deactivate storefront module; reactivate `biopentra-footer-contact`.

### Legacy plugin during migration

| Stage | `biopentra-footer-contact` |
|-------|------------------------------|
| Implementation | **Active** until cutover |
| After QA | **Deactivated** when storefront owns these hooks |

---

## Phase 3 — `custom-variation-stock-selector` (CVSS)

**Goal:** Auto-select highest-priced in-stock purchasable variation when WC embeds `product_variations` JSON.

### Source layout

| File | Role |
|------|------|
| `custom-variation-stock-selector.php` | HPOS declare + enqueue + inline jQuery. |

### Hooks to preserve

| Hook | Type | Purpose |
|------|------|---------|
| `before_woocommerce_init` | action | `FeaturesUtil::declare_compatibility( 'custom_order_tables', … )` |
| `woocommerce_before_variations_form` | action | Register empty-src script handle `custom-variation-stock-selector`, deps `jquery` + `wc-add-to-cart-variation`, `wp_add_inline_script` after. |

### Shortcodes

None.

### Assets to move

- Inline script only today — optional refactor: move JS to `modules/variation-stock-selector/assets/variation-stock-selector.js` and enqueue with `filemtime` versioning (behavior must stay identical).

### Test steps

1. Variable product **under** `woocommerce_ajax_variation_threshold`: embedded variations present → after load, attributes select to highest in-stock purchasable price (compare to legacy).
2. URL with `attribute_*` query params: **must not** override (preserve current behavior).
3. Already-selected `variation_id`: no change.
4. Product **over** threshold (no embedded JSON): plugin still no-op (documented).
5. HPOS: no compatibility notices from WooCommerce.

### Rollback

- Deactivate storefront module; reactivate `custom-variation-stock-selector`.

### Legacy plugin during migration

| Stage | `custom-variation-stock-selector` |
|-------|-----------------------------------|
| Implementation | **Active** until cutover |
| After QA | **Deactivated** when storefront owns hooks |

---

## Phase 4 — `biopentra-header-auth`

**Goal:** Header auth Elementor widget + shortcode, WC account/checkout styling, cart enhancements, Blocksy filters, optional Blocksy palette admin hook.

### Source layout

| File | Role |
|------|------|
| `biopentra-header-auth.php` | Bootstrap, hooks, shortcode, Elementor category + widget bootstrapping. |
| `includes/markup.php` | HTML helpers for shortcode/widget. |
| `includes/elementor-widget-header-auth.php` | Elementor widget class. |
| `includes/cart-enhancements.php` | Cart CSS/JS enqueue + `woocommerce_before_cart_totals` UI. |
| `includes/blocksy-global-palette.php` | `admin_init` palette file hook. |
| `assets/header-auth.css`, `header-auth.js` | Core widget/shortcode assets. |
| `assets/wc-account-forms.css` | Account/checkout styling. |
| `assets/cart-enhancements.css` | Cart-only styles. |

### Hooks / filters to preserve (summary)

| Hook | Type | Priority | Source file |
|------|------|----------|-------------|
| `wp_enqueue_scripts` | action | **5** | `biopentra-header-auth.php` — register header-auth CSS/JS |
| `wp_enqueue_scripts` | action | **100** | `biopentra-header-auth.php` — WC account/checkout styles + inline CSS vars |
| `woocommerce_account_menu_items` | filter | **99** | `biopentra-header-auth.php` — remove Downloads |
| `blocksy:woocommerce:single-product:post-class` | filter | **20** | `biopentra-header-auth.php` — `ct-ajax-add-to-cart` class |
| `elementor/elements/categories_registered` | action | default | `biopentra-header-auth.php` — Biopentra category |
| `plugins_loaded` | action | **20** | `biopentra-header-auth.php` — boot Elementor widget registration |
| `elementor/widgets/register` | action | default | Registered inside boot when Elementor exists |
| `wp_enqueue_scripts` | action | **100** | `cart-enhancements.php` — cart assets |
| `woocommerce_before_cart_totals` | action | **5** | `cart-enhancements.php` — free shipping progress block |
| `admin_init` | action | **5** | `blocksy-global-palette.php` — optional palette file |

### Shortcodes to preserve

| Tag | Notes |
|-----|--------|
| `[biopentra_header_auth]` | Attributes: `login_text`, `logout_text`, `my_account_text`, `identifier` — preserve defaults and behavior. |

### Filters (public API for themes)

Preserve these **filter names** and semantics if themes customize them:

- `biopentra_header_auth_wc_account_accent`
- `biopentra_header_auth_wc_account_accent_hover`

### Elementor

- Widget class namespace: `\Biopentra_Header_Auth\Elementor_Widget_Header_Auth` — either keep namespace or register equivalent class under storefront autoload rules **without** breaking saved Elementor documents (widget `name` / `_widgetType` must remain compatible — verify Elementor data on staging).

### Assets to move

All under `assets/` listed above, plus all `includes/*.php` for this feature set.

### Test steps

1. **Logged out / logged in:** shortcode and Elementor widget render; login/logout links correct.
2. **My Account / Checkout:** styles and CSS variables; Downloads hidden from menu.
3. **Cart:** free-shipping progress + cart CSS.
4. **Quick view / non-product contexts:** Blocksy `ct-ajax-add-to-cart` behavior where previously fixed.
5. **Elementor editor:** widget appears under **Biopentra** category; new page can add widget.
6. **Admin:** Blocksy global palette routine (if used) still runs without fatal.

### Rollback

- Deactivate storefront module / plugin; reactivate `biopentra-header-auth`.

### Legacy plugin during migration

| Stage | `biopentra-header-auth` |
|-------|-------------------------|
| Implementation | **Active** until cutover |
| After QA | **Deactivated** when storefront owns all hooks |

---

## Recommended first migration

**Phase 1 — `biopentra-information-megamenu`**

- **Smallest blast radius:** one hook, one CSS file, no shortcodes, no DB, no Elementor PHP class.
- **Easiest rollback:** reactivate the old plugin and deactivate storefront (or toggle module).
- **Risk:** Global CSS enqueue on all front pages today — confirm that is still desired after review (optional follow-up: conditional enqueue by template / body class).

---

## Post–all-phases cleanup (optional, later)

- Remove deactivated plugin folders from **deploy** artifacts only after a soak period; Git history can retain them.
- Re-run `./scripts/build-zips.sh` for a release that packages only `biopentra-storefront` + standalone plugins.
- Update `docs/migration-plan-biopentra-storefront.md` to point to this checklist as the operational runbook.
