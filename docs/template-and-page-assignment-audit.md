# WooCommerce template and page assignment audit

**Date:** 2026-05-15  
**Environment:** `/home/magpern/woocommerce`  
**Site:** `https://www.biopentra.eu`

---

## Executive summary

| Item | Finding |
|------|---------|
| **Root cause of “Great things are on the horizon”** | **WooCommerce “Launch your store” / Coming soon** was enabled: `woocommerce_coming_soon` = `yes`, with **`woocommerce_store_pages_only` = `yes`** (store-only mode). For guests, WooCommerce replaces **store-related** views (cart, product, etc.) with the core **coming-soon** block pattern — not wrong Elementor page assignment or Blocksy hiding the cart. |
| **Minimal fix applied** | `wp option update woocommerce_coming_soon no` — store pages now render real WooCommerce + theme/Elementor templates for anonymous visitors. |
| **Elementor / WC page wiring** | WooCommerce page IDs are consistent; cart/checkout/my account use **Elementor** (`_elementor_edit_mode` = `builder`, `_wp_page_template` = `elementor_header_footer` where checked). No template redesign performed. |

---

## 1. Placeholder copy — where it comes from

The string **“Great things are on the horizon”** is defined in **WooCommerce core** block patterns, e.g.:

- `wp-content/plugins/woocommerce/patterns/coming-soon-store-only.php`
- `wp-content/plugins/woocommerce/patterns/page-coming-soon-with-header-footer.php`

It is **not** from Elementor Theme Builder conditions being “too broad” in the usual sense; it is injected when **Coming soon** logic decides the current request should show the coming-soon experience (`Automattic\WooCommerce\Internal\ComingSoon\ComingSoonRequestHandler` + `ComingSoonHelper`).

### Relevant options (before fix)

| Option | Value | Effect |
|--------|-------|--------|
| `woocommerce_coming_soon` | `yes` | Coming soon **active** (unless site is considered “live” by this flag). |
| `woocommerce_store_pages_only` | `yes` | **Store pages only** mode: only WooCommerce “store” contexts get the placeholder for guests (not necessarily the whole marketing homepage). |
| `woocommerce_private_link` | `no` | Private share link bypass was off. |

With **`woocommerce_coming_soon` = `no`**, `ComingSoonHelper::is_site_live()` is true and the coming-soon template path is **not** used.

### What was ruled out (for this incident)

- **Elementor single-product / cart “wrong page”** as the *primary* cause — assignments were fine; content was overridden by WC coming soon.
- **WordPress core maintenance** (`wp maintenance_mode`) — not used for this symptom.
- **Blocksy-specific “maintenance”** — not required to explain the placeholder; Blocksy + Elementor wrappers still applied **after** coming soon was disabled.

---

## 2. WooCommerce page assignments

Verified via WP-CLI (`wp option get` / `wp post get`).

| Role | Page ID | Slug | Status | Notes |
|------|---------|------|--------|--------|
| **Cart** | 82 | `cart-2` | publish | `body` includes `woocommerce-cart`, Elementor full width. |
| **Checkout** | 83 | `checkout-2` | publish | **302 to cart** when the cart is empty (expected WooCommerce behaviour). |
| **My account** | 84 | `my-account-2` | publish | Standard WC my-account slug pattern. |
| **Shop** | 3755 | `shop` | publish | Correctly assigned as shop archive base. |

**Elementor on WC utility pages (sampled)**

| Page ID | `_elementor_edit_mode` | `_wp_page_template` |
|---------|------------------------|---------------------|
| 82 (Cart) | `builder` | `elementor_header_footer` |

Checkout (83) post meta queries returned empty in CLI (may be default template or meta not set the same way); checkout **redirect** when cart is empty prevented full HTML inspection of the checkout form in this run without adding items to the cart.

---

## 3. Product template rendering (variable product)

**Sample:** Product ID **3705**, URL `/product/kisspeptin/`.

**After** disabling coming soon (guest HTML):

- **No** “Great things are on the horizon” in HTML.
- **`variations_form`** present (count ≥ 1 in `curl` grep).
- **`woocommerce-variation`** / **`single_add_to_cart`** markers present.
- Storefront **CVSS** asset path present: `biopentra-storefront/.../cvss-bridge` in page source — suitable for CVSS initialisation on variable PDPs.

`_elementor_edit_mode` / `_wp_page_template` for the product post returned empty via CLI in this session (product may use theme default + Elementor Pro **Theme Builder** conditions rather than per-post Elementor data).

---

## 4. “Coming Soon — Brand” Elementor template vs WooCommerce coming soon

`wp post list --post_type=elementor_library` includes a template titled **“Coming Soon — Brand”** (`coming-soon-brand`, ID **3423**). That is a **normal Elementor library** template and is **separate** from WooCommerce’s **Launch your store** coming soon system documented above. If it is ever assigned with a **very broad** display condition, it could still affect layouts — **not** observed as the cause of the WC pattern text on cart/product in this audit.

---

## 5. Required fixes (recommended order)

1. **Done (this audit):** Set **`woocommerce_coming_soon`** to **`no`** for a **public** store so cart, checkout, and PDPs render for guests.  
2. **Optional cleanup:** If you no longer use Launch-your-store workflows, leave `woocommerce_store_pages_only` as-is (`yes` is harmless when coming soon is off); or set to `no` for tidiness in **WooCommerce → Settings** if the UI exposes it.  
3. **QA:** Re-run **checkout** with items in cart (checkout redirects to cart when empty). Confirm **free-shipping** bar and **CVSS** behaviour on a variable PDP in a real browser (logged out + logged in).  
4. **Cloudflare:** After option changes, purge edge cache if HTML still looks stale.

---

## 6. Rollback considerations

| Action | Rollback |
|--------|----------|
| Disable coming soon | `wp option update woocommerce_coming_soon yes` — restores guest-facing placeholder on store pages (when `woocommerce_store_pages_only` is `yes`) or site-wide mode per WC rules. |
| **Caution:** Re-enabling coming soon on a **live** paying store will **hide** the real cart/checkout/product experience from most visitors — only use if you intentionally return to pre-launch. |

---

## 7. Verification after fix (2026-05-15)

| Check | Result |
|-------|--------|
| Cart page (`/cart-2/`) | `woocommerce-cart`, `cart-empty` / WooCommerce markup; **no** “Great things…” string. |
| Product (`/product/kisspeptin/`) | `variations_form`, variation add-to-cart markers; **no** placeholder heading. |
| Storefront megamenu asset | HTTP **200** (spot URL). |
| Checkout URL | **302 → cart** when cart empty (expected). |

Browser console was not re-driven exhaustively in this pass; recommend a short **logged-out** DevTools pass on cart → add item → checkout.

---

## 8. Remaining risks

- **WooCommerce demo store** class appears in `body` (`woocommerce-demo-store`) — unrelated to coming soon; review if the admin “coming soon / demo” banner should be off for production UX.  
- **Checkout** full flow not exercised without line items.  
- **Elementor Theme Builder** rules for **single product** were not fully dumped from `_elementor_conditions` meta in this document — schedule if you need a full condition matrix.

---

## Sign-off

| Field | Value |
|-------|--------|
| Root cause | WooCommerce **Launch your store** / **coming soon** options |
| Fix applied in DB | `woocommerce_coming_soon` → `no` |
| Repo code changes | None (documentation only in Git) |
