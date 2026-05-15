# Post–coming-soon fix QA

**Date:** 2026-05-15  
**Site:** `https://www.biopentra.eu`  
**Context:** `woocommerce_coming_soon` changed from `yes` → `no` (Launch your store / store-only coming soon disabled).

**Scope:** Verification only — no Elementor layout changes, no storefront plugin code changes, no consolidation work.

---

## 1. Cache purge

| Layer | Action | Result |
|-------|--------|--------|
| Elementor CSS | `./wp elementor flush-css --regenerate` | Success |
| WP object cache | `./wp cache flush` | Success |
| Rewrite rules | `./wp rewrite flush` | Success |
| WooCommerce transients | `./wp wc tool run clear_transients --user=bp_manager` | Success |
| Cloudflare / CDN | No API credentials in this environment | **Manual purge recommended** if edge HTML still looks stale |
| Browser test session | URLs requested with `?nocache=1` | Fresh origin fetch for automated checks |

---

## 2. WooCommerce option

```bash
wp option get woocommerce_coming_soon
```

**Result:** `no` (expected).

Related (unchanged): `woocommerce_store_pages_only` = `yes` (harmless while coming soon is off).

---

## 3. Pages tested — visual / structural status

| Page | URL | Status | Notes |
|------|-----|--------|--------|
| Homepage | `/` | **Pass** | Hero/marketing content restored: logo, bottle image, info images, trust icons, product grid headings. **No** “Great things are on the horizon”. Elementor sections present. |
| Shop | `/shop/` | **Pass** | Catalog layout, category filters, live search, product cards — normal. |
| Product (variable) | `/product/kisspeptin/` | **Pass (layout)** | Title, `variations_form`, Strength select, Add to cart, tabs — normal PDP. **Inventory:** all sampled variations show **Out of stock** in browser (see §4). |
| Cart | `/cart-2/` | **Pass** | “Your Cart”, empty-cart / return-to-shop — normal WooCommerce cart (not placeholder). |
| Checkout | `/checkout-2/` | **Pass (redirect)** | Empty cart → **302 to `/cart-2/`** (expected). Full checkout form **not** exercised (no line items). |
| My Account | `/my-account-2/` | **Pass** | Login + register forms render. |

**Styling restored:** **Yes** — compared to coming-soon placeholder state; homepage imagery and store templates render as designed.

**Unexpected full-width / broken layout:** **Not observed** on pages tested.

---

## 4. Storefront module QA

| Module | Status | Evidence |
|--------|--------|----------|
| Information megamenu | **Pass** | Menu toggle opens nav; Information mega columns (Learn / Quality & Trust / Policies) visible on home and shop. Storefront `information-mega.{css,js}` HTTP **200**. |
| Footer email | **Pass** | “Send email to Biopentra” control on footer across pages. `footer-contact-email.js` HTTP **200**. |
| Header auth (logged out) | **Pass** | “Log in” link present; `header-auth.{css,js}` HTTP **200**. |
| Header auth (logged in) | **Not tested** | No admin credentials used in this automated run. |
| Variation auto-select (CVSS) | **Partial** | `cvss-bridge.js` loads on PDP; after **2s** Strength still “Choose an option” (no auto-select observed). Manual select of **10mg** / **5mg** both showed **Out of stock** — inventory blocks purchase flow. |
| Variable add-to-cart | **Blocked** | Kisspeptin variations **out of stock** (`stock=n` for sampled IDs via WP-CLI). Add-to-cart could not complete in browser. |
| Free-shipping progress (non-empty cart) | **Not tested** | No in-stock purchasable products found in catalog sample (`wc_get_products` returned none in stock). `bph-cart-free-shipping` markup requires items in cart. |

---

## 5. Network / console

### Legacy plugin paths (HTML grep)

Pages: `/`, `/shop/`, `/cart-2/`, `/my-account-2/`, `/product/kisspeptin/`  
**Legacy path references:** **0** on all.

### Storefront assets (spot check)

All requested `biopentra-storefront` URLs returned **HTTP 200** (megamenu, footer contact, header auth, CVSS bridge).

### 404s

Homepage sample of linked `wp-content` CSS/JS/images: **no non-200** in scripted sample. Browser network capture on my-account pass returned **empty** (no failed requests recorded in tool output).

### Console (browser automation)

**No `error`-level messages** on home, shop, product, cart, or my-account. **Warnings only:** jQuery Migrate, Cursor browser dialog shim.

---

## 6. Logs

| Source | Result |
|--------|--------|
| Docker / Apache (`woocommerce-wordpress-1`, last 6h) | **No PHP Fatal** |
| WooCommerce `wp-content/wc-logs/` | **Directory not present** on bind-mounted `wp-content` |
| Historical note | Megamenu `str_replace` fatals on **2026-05-14** (pre-fix); none in recent window |

---

## 7. Summary table

| Area | Result |
|------|--------|
| Styling / templates restored | **Yes** |
| Coming soon placeholder gone | **Yes** |
| Cart page normal | **Yes** (empty cart) |
| Checkout with product in cart | **Not verified** (empty cart redirect; catalog OOS) |
| Storefront modules (guest) | **Pass** (except logged-in header auth) |
| CVSS / add-to-cart / free-shipping bar | **Blocked or partial** due to **inventory**, not coming soon |

---

## 8. Remaining risks

1. **Catalog stock** — Variable products sampled are **out of stock**; commerce QA (add-to-cart, checkout, free-shipping bar) needs at least one **in-stock** SKU or a staging stock update.  
2. **Cloudflare** — Purge edge cache after launch if customers still see old placeholder HTML.  
3. **CVSS auto-select** — Not confirmed on load; may need PDP with in-stock embedded variation data + manual timing check.  
4. **Logged-in header auth** — Owner should spot-check account menu/widgets after login.  
5. **`woocommerce-demo-store`** body class still present — review demo-store banner setting if undesired on production.

---

## 9. Fixes applied in this QA

| Change | Applied? |
|--------|----------|
| `woocommerce_coming_soon` | Already `no` before this run (verified only) |
| Elementor / WP cache flush | **Yes** (operational) |
| Elementor layouts / storefront code | **No** |
| Stock / inventory | **No** |

---

## Sign-off

| Field | Value |
|-------|--------|
| Recommendation | **Storefront QA can proceed** for layout, megamenu, footer, header (guest), and empty cart. **Complete checkout/CVSS/free-shipping QA after inventory** is available. |
| Performed by | Cursor agent (automated) |
