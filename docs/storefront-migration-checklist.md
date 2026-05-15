# Storefront consolidation — phased migration checklist

**Status:** Phases **1–4 code** live in `biopentra-storefront` (mega-menu, footer contact, variation stock selector, **header auth**). **Phase 4 production cutover** (deactivate standalone `biopentra-header-auth`) is **not** implied until staging QA passes — see **`docs/staging-test-phase-4-header-auth.md`** and **`docs/header-auth-migration-notes.md`**. Pre-migration audit: **`docs/header-auth-phase-4-audit.md`**. Elementor megamenu legacy script cleanup remains part of Phase 1 production hygiene where applicable. Legacy plugin folders stay in the repo; **do not** delete until soak completes.

**Phase 5 (`biopentra-loop-card`):** **Keep standalone / not part of storefront 0.4.x** — hardened as separate plugin; see **`docs/loop-card-hardening-plan.md`** and audit **`docs/loop-card-phase-5-audit.md`**. Do not merge into `biopentra-storefront` for the 0.4.x release line.

**Out of scope for Phases 1–4 cutover:** `biopentra-loop-card`, `biopentra-contact-inbox`, `wc-inventory-overview` — do not merge or deactivate as part of Phases 1–4.

---

## Current scaffold (`plugins/biopentra-storefront/`)

| Path | Purpose |
|------|---------|
| `biopentra-storefront.php` | Plugin header, constants, `plugins_loaded` → `Biopentra_Storefront::init()` |
| `includes/class-biopentra-storefront.php` | Loads **Information megamenu**, **Footer contact**, **Variation stock selector**, and **Header auth** modules when class files are readable |
| `modules/header-auth/class-header-auth-module.php` | Phase 4: guarded init; shortcode + Elementor + WC + Blocksy + cart; skips if legacy `biopentra-header-auth` active |
| `modules/header-auth/assets/*`, `modules/header-auth/includes/*` | Phase 4: copied from legacy header-auth plugin |
| `modules/variation-stock-selector/class-variation-stock-selector-module.php` | Phase 3: HPOS declare + `woocommerce_before_variations_form` inline CVSS (bridge JS handle) |
| `assets/variation-stock-selector/cvss-bridge.js` | Phase 3: minimal real `src` for enqueue; logic is inline |
| `modules/footer-contact/class-footer-contact-module.php` | Phase 2: shortcode `[biopentra_footer_email]`, `wp_robots` placeholder noindex, script @ priority **5** |
| `assets/footer-contact/footer-contact-email.js` | Phase 2: copied from legacy |
| `assets/footer-contact/bp-e1.png` | Phase 2: optional image (add from deploy if not in repo) |
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

## Phase 2 — `biopentra-footer-contact` ✅ *migrated into storefront; legacy retained*

**Goal:** Placeholder `noindex`, footer email shortcode with JS-built mailto.

**Repo status:** Implemented under `modules/footer-contact/` and `assets/footer-contact/`. See **`docs/footer-contact-migration-notes.md`** and staging **`docs/staging-test-phase-2-footer-contact.md`**. Legacy plugin **not** removed.

### Source layout (legacy, unchanged in repo)

| File | Role |
|------|------|
| `biopentra-footer-contact.php` | All logic (constants, assets, filter, shortcode). |
| `assets/footer-contact-email.js` | Client script for mailto behavior. |
| `assets/bp-e1.png` | Email image asset (may exist only on some deploy trees). |
| `index.php` | Silence / direct access guard. |

### Storefront layout

| File | Role |
|------|------|
| `modules/footer-contact/class-footer-contact-module.php` | Same hooks + shortcode; skips init if legacy shortcode already registered. |
| `assets/footer-contact/footer-contact-email.js` | Same script as legacy. |
| `assets/footer-contact/bp-e1.png` | Optional; copy from legacy/deploy when available. |

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

- Post meta key **`_biopentra_placeholder_page`** — value **`'1'`** when set on placeholder pages (class constant `PLACEHOLDER_META` in module).

### Test steps

See **`docs/staging-test-phase-2-footer-contact.md`**.

### Rollback

- Deactivate `biopentra-storefront` (if footer contact is the only blocker) or leave storefront active for Phase 1 while reactivating legacy footer contact only if module guard allows — **simpler:** deactivate storefront, reactivate `biopentra-footer-contact`.

### Legacy plugin during migration

| Stage | `biopentra-footer-contact` |
|-------|------------------------------|
| Implementation / Phase 1-only sites | **Active** until Phase 2 cutover |
| After Phase 2 QA | **Deactivated** when storefront owns these hooks |

---

## Phase 3 — `custom-variation-stock-selector` (CVSS) ✅ *migrated into storefront; legacy retained*

**Goal:** Auto-select highest-priced in-stock purchasable variation when WC embeds `product_variations` JSON.

**Repo status:** `modules/variation-stock-selector/` + `assets/variation-stock-selector/cvss-bridge.js`. See **`docs/variation-stock-selector-migration-notes.md`** and **`docs/staging-test-phase-3-variation-stock-selector.md`**.

### Source layout (legacy)

| File | Role |
|------|------|
| `custom-variation-stock-selector.php` | HPOS declare + enqueue + inline jQuery. |

### Storefront layout

| File | Role |
|------|------|
| `modules/variation-stock-selector/class-variation-stock-selector-module.php` | Same hooks + inline JS; bridge `src`; bails if legacy plugin active. |
| `assets/variation-stock-selector/cvss-bridge.js` | Minimal script URL for `wp_register_script`. |

### Hooks to preserve

| Hook | Type | Priority | Purpose |
|------|------|----------|---------|
| `before_woocommerce_init` | action | default | `FeaturesUtil::declare_compatibility( 'custom_order_tables', … )` |
| `woocommerce_before_variations_form` | action | default | Register/enqueue bridge + inline script (deps `jquery`, `wc-add-to-cart-variation`). |

### Shortcodes

None.

### Test steps

See **`docs/staging-test-phase-3-variation-stock-selector.md`**.

### Rollback

- Activate `custom-variation-stock-selector`; deactivate `biopentra-storefront` only if safe for other storefront features.

### Legacy plugin during migration

| Stage | `custom-variation-stock-selector` |
|-------|-----------------------------------|
| Before cutover | **Active** until storefront validated |
| After QA | **Deactivated** when storefront owns hooks |

---

## Phase 4 — `biopentra-header-auth` ✅ *migrated into storefront; legacy retained*

**Status:** Module code under `modules/header-auth/`; **deactivate legacy only after staging QA**. Notes: **`docs/header-auth-migration-notes.md`**, staging: **`docs/staging-test-phase-4-header-auth.md`**, audit: **`docs/header-auth-phase-4-audit.md`**.

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
- `biopentra_header_auth_cart_free_shipping_threshold`
- `biopentra_header_auth_cart_free_shipping_subtotal`
- `biopentra_header_auth_skip_blocksy_palette_migration` (admin palette routine)

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

## Phase 5 — `biopentra-loop-card`

**Status:** **Keep standalone / not part of storefront 0.4.x** — version aligned at **1.2.4**; hardening plan: **`docs/loop-card-hardening-plan.md`**. Audit: **`docs/loop-card-phase-5-audit.md`**.

**Policy:** Do **not** merge into `biopentra-storefront` for 0.4.x. Maintain as separate plugin with its own ZIP and release cadence.

**Goal (standalone):** Elementor Loop Grid product cards, shop query fix, live search, price formatting, Age Gate + store notice helpers.

### Source layout (legacy, unchanged)

| File | Role |
|------|------|
| `biopentra-loop-card.php` | Main runtime hooks |
| `includes/age-gate-confirm-fix.php` | Age Gate compatibility |
| `includes/store-notice.php` | WC demo store notice |
| `includes/setup-shop-page-cli.php` | **CLI only** — not loaded at runtime |
| `assets/loop-card.css`, `loop-card.js` | Grid overlay + AJAX ATC |
| `assets/shop-live-search.js` | REST suggestions on shop |
| `assets/store-notice.css` | Notice styling |

### Hooks to preserve (summary)

See audit doc for full priority table. Notable: `pre_get_posts` **9999**, `woocommerce_get_price_html` **50**, `elementor/document/wrapper_attributes` **20**, `elementor/query/query_args` **25**, `wp_enqueue_scripts` **20** / **30**.

### Legacy plugin during migration

| Stage | `biopentra-loop-card` |
|-------|-------------------------|
| Current | **Active** (standalone) |
| If merge approved later | **Deactivate** when storefront module owns hooks + guard verified |

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
