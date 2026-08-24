# PDP Purchase-Summary Redesign — Forensics & Implementation Plan (Revision 3 — ready for freeze)

## Context

The M1–M10 "BioPentra Storefront UI Overhaul" is closed and frozen on DEV at
`storefront-v0.9.33` (closure record:
`biopentra-custom-plugins/docs/storefront-redesign/UI-OVERHAUL-CLOSURE.md`,
2026-08-23: *"There is no M11 … deliberate redesign requires a new
project/milestone"*). This is that new initiative — internally **PDP-1**, not
M11 — scoped to the upper single-product summary/purchase area only.

Milestone **D2A/D2B** (tag `storefront-v0.8.0` + `biopentra-blocksy-child
v1.1.0`) already did a first CSS-only pass on this area. PDP-1 builds on
D2A/D2B; D2B's sticky bar's DOM/selector contract (locked by
`storefront-acceptance/tests/pdp-sticky.spec.ts`) must keep working
unmodified.

**Revision 3 changes** (final round before freeze): scopes the simple-product
price relocation so it cannot affect variable-product price rendering;
resolves the WP0/WP1 contradiction by moving price relocation into WP0;
replaces an *assumed* AJAX add-to-cart behavior with verified forensics of
Blocksy's actual bespoke fetch-based AJAX mechanism (confirmed live on DEV);
confirms variation SKU-swap is native, unmodified WooCommerce core JS
behavior rather than an assumption; changes the eyebrow fallback to never
surface "Uncategorized" (with the real term ID looked up, not guessed);
replaces the "guard the close callback by re-checking WooCommerce's
condition" idea with a simpler render-state flag; and confirms a real
zero-purchasable-variations fixture product exists on DEV for the structural
proof. No remaining architectural PO gates after this revision (§12).

**Forensics + plan only.** No implementation, no version bumps, no
WordPress/Elementor changes, nothing on production.

---

## 1. Executive assessment

Achievable via **additive WordPress hook callbacks + CSS only**, no
WooCommerce template override, no new JavaScript. One hard platform
constraint (price+availability rendered as one atomic JS blob for variable
products, §2.2) produces one documented, deliberate asymmetry rather than a
literal match to the prompt's schematic hierarchy — reported, not forced,
per the architecture gate.

**Recommendation (unchanged): hook ordering/wrapper output + CSS.**
WooCommerce's own core templates expose purpose-built hooks
(`woocommerce_after_variations_table` / `woocommerce_after_variations_form`
for variable products, `woocommerce_before_add_to_cart_form` /
`woocommerce_after_add_to_cart_form` for simple products) at exactly the DOM
boundaries this redesign needs. No custom template file is required.

---

## 2. Architecture gate — resolved

### 2.1 Real template/hook structure (verified from WooCommerce core source)

**Simple product** (`templates/single-product/add-to-cart/simple.php`,
WC 10.2.0):
```php
echo wc_get_stock_html( $product );                         // stock — before the form entirely
if ( $product->is_in_stock() ) :
  do_action( 'woocommerce_before_add_to_cart_form' );        // ← PANEL OPEN
  <form class="cart"> ... quantity ... button ... </form>
  do_action( 'woocommerce_after_add_to_cart_form' );         // ← PANEL CLOSE
endif;
```
Stock is natively a sibling rendered before the form opens — "stock
outside/before panel, price+qty+ATC inside panel" is the native, zero-risk
structure once wrapped at these two hooks.

**Variable product** (`templates/single-product/add-to-cart/variable.php`,
WC 10.9.0):
```php
do_action( 'woocommerce_before_add_to_cart_form' );
<form class="variations_form cart" data-product_variations="...">
  do_action( 'woocommerce_before_variations_form' );          // CVSS auto-select hooks here (unaffected)
  <table class="variations">...Strength <select> dropdowns...</table>
  do_action( 'woocommerce_after_variations_table' );          // ← PANEL OPEN (variable products), only fires if variations exist
  <div class="single_variation_wrap">
    do_action( 'woocommerce_before_single_variation' );
    do_action( 'woocommerce_single_variation' );               // → empty shell div (§2.2) + qty/ATC block
    do_action( 'woocommerce_after_single_variation' );
  </div>
  do_action( 'woocommerce_after_variations_form' );            // ← PANEL CLOSE (variable products), always fires
</form>
do_action( 'woocommerce_after_add_to_cart_form' );
```
`woocommerce_after_variations_table` fires right after `</table>`, before
`single_variation_wrap` opens; `woocommerce_after_variations_form` fires
right after `single_variation_wrap` closes, still inside `<form>`. Opening
the panel `<div>` at the first and closing at the second wraps
`single_variation_wrap` (price/availability + qty/ATC) while leaving
`<table class="variations">` outside/before it — this is the exact fix for
the DOM-hierarchy defect the gate flagged, using only WooCommerce's own
purpose-built hooks, no template override.

**Existing third-party wrapper already present in this DOM tree, now
documented**: Blocksy's own `WooCommerceAddToCart` class
(`themes/blocksy/inc/components/woocommerce/single/add-to-cart.php`) already
wraps quantity+button in `<div class="ct-cart-actions">`, opened at
`woocommerce_before_add_to_cart_quantity` (priority `PHP_INT_MAX`) and
closed at `woocommerce_after_add_to_cart_button` (priority 100) — entirely
*inside* the boundaries this plan's panel wraps, at different action names,
so there is no priority collision. The panel div becomes an outer wrapper
around Blocksy's existing inner wrapper; this is additive nesting, not a
conflict, and must be included in the DOM map used for the structural proof
(§14 WP0).

### 2.2 The hard constraint: price and availability are one atomic JS-rendered blob (variable products only)

`woocommerce_single_variation()` (WC core) echoes one empty shell div:
```php
echo '<div class="woocommerce-variation single_variation" role="alert" aria-relevant="additions"></div>';
```
`assets/js/frontend/add-to-cart-variation.js:442` populates it atomically:
`form.$singleVariation.html( $template_html )`, sourced from the JS template
`single-product/add-to-cart/variation.php`
(`{{{price_html}}}` then `{{{availability_html}}}` in one string). Price and
availability cannot be split into two different DOM parents via additive
hooks alone without either fragile post-render JS DOM surgery or forking
core JS — both ruled out.

**Resolution**: for variable products, the price+availability bundle renders
as a whole inside the panel, as the first content above quantity+ATC — still
literally satisfies "stock indicator directly before the purchase action"
(it's the first line inside the same bordered panel as the action row).

**Documented asymmetry, not a defect:**
- Simple: stock renders natively before/outside the panel.
- Variable: stock renders natively inside the panel, bundled with price, as
  a hard platform constraint.

**PO has accepted this asymmetry (§12)** — no duplicate `wc_get_stock_html()`
call will be added to simple products purely for visual symmetry.

### 2.3 No custom price/stock JS, no mirrored state

Panel wrapping is pure hook-priority placement of empty `<div>` open/close
tags around content WooCommerce already renders and keeps live via its own,
unmodified JS. `sticky-bar.js`'s selectors match by class/tag, not ancestor
path — unaffected. **To be verified in the structural proof (§14 WP0), not
assumed.**

### 2.4 Balanced markup for the panel-open/close pair — render-state flag, not condition duplication

For variable products, `woocommerce_after_variations_table` only fires
inside `variable.php`'s `else` branch (i.e. only when
`$available_variations` is non-empty or is explicitly `false`); when a
variable product has zero available variations, that branch is skipped
entirely, so the panel-open callback never fires — but
`woocommerce_after_variations_form` (outside the if/else) always fires,
which would emit an orphaned closing `</div>` if the close callback is
unconditional.

**Resolution — a simple render-state flag, not a re-implementation of
WooCommerce's `empty($available_variations) && false !== $available_variations`
condition:**
```php
private $panel_open = false;

public function open_panel_variable() {
    // fires only when the else-branch executed, i.e. variations genuinely rendered
    echo '<div class="bp-pdp-purchase-panel">';
    $this->panel_open = true;
}

public function close_panel_variable() {
    if ( ! $this->panel_open ) {
        return; // nothing was opened — e.g. zero purchasable variations
    }
    echo '</div>';
    $this->panel_open = false; // reset for any subsequent product in the same request (archives/AJAX re-render)
}
```
This tracks *actual* render state rather than re-deriving WooCommerce's own
branching logic in a second place (which would silently drift if WooCommerce
ever changes that condition) — deterministic, single source of truth, and
trivially correct for the zero-variations case.

**Real fixture confirmed for this branch**: product `m21-postrelease-variable`
on DEV has 1 total variation child but **0 available/purchasable
variations** (`wc_get_products()` → `get_available_variations()` returns an
empty array) — this is a live, real product exercising exactly the
zero-purchasable-variations branch, not a hypothetical. The structural proof
(§14 WP0) uses it directly; no fixture creation needed.

---

## 3. Simple-product price relocation — scoped correctly

**Problem in Revision 2**: an unconditional
```php
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
```
removes `woocommerce_template_single_price` globally — including for
variable products, whose top-level price *range* must stay at priority 10
(§4 target composition; it is the pre-selection informational price).
`remove_action`/`add_action` are not conditional by themselves; the
callback itself must check product type before doing anything, and the
`remove_action` must never touch the hook for anything but the current
simple-product render.

**Corrected registration — conditional both at hook-attach time and inside
the callback, using WooCommerce's own product-type accessor:**

```php
add_action( 'woocommerce_single_product_summary', function () {
    global $product;

    if ( ! $product instanceof WC_Product || ! $product->is_type( 'simple' ) ) {
        return; // never touches variable/grouped/external product rendering
    }

    // Remove only for this render, then re-add at the relocated position.
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
    add_action( 'woocommerce_before_add_to_cart_form', 'woocommerce_template_single_price', 5 );
}, 1 ); // fires before woocommerce_template_single_price's own priority-10 callback on the same hook, for this product only
```

Because `woocommerce_single_product_summary` fires once per single-product
page load, in the loop for *that page's* `$product` object, gating on
`$product->is_type('simple')` before calling `remove_action` guarantees
variable (and grouped/external) products never have their priority-10 price
hook touched — the global registration for
`woocommerce_template_single_price` on `woocommerce_single_product_summary`
at priority 10 remains WooCommerce's default for every product type except
the one simple product currently rendering, and is naturally restored to
default on the next page load (hook removal is per-request, not persistent).
This produces exactly one native `.price` element per page render, in
exactly one place, for every product type — never duplicated, never
globally altered.

**Structural-proof requirement**: WP0 (§14) must explicitly verify, on a
real variable product (`tirzepatide`), that its top-level `.price` range
still renders at its native position (before the short description) and is
**not** removed or relocated — this is the regression this scoping fix
exists to prevent, and it must be checked, not assumed correct because the
code "looks" scoped.

---

## 4. Eyebrow / primary category — corrected, with real term data

**Real site data:**
- Rank Math's primary-taxonomy feature is enabled for products
  (`pt_product_primary_taxonomy = 'product_cat'`), but **no product
  currently has a primary category actually set** (zero `%primary%`
  postmeta rows sitewide).
- **The "Uncategorized" `product_cat` term has `term_id = 15`** on this
  site (looked up live via `get_term_by('slug', 'uncategorized',
  'product_cat')`, not assumed), and matches
  `get_option('default_product_cat')` = `'15'` — WordPress/WooCommerce's own
  authoritative pointer to "the auto-assigned fallback category."
- **14 of 26 published products (54%) carry *only* the Uncategorized
  category** — this is a majority-adjacent, real, live scenario, not an
  edge case: without an explicit fallback rule, over half the catalog would
  show "Uncategorized" as the eyebrow.

**Resolution — deterministic source, never surfaces Uncategorized:**
1. **Primary**: `get_post_meta( $product_id, 'rank_math_primary_product_cat', true )`.
   If non-empty, validate it is still an assigned term (`wp_get_post_terms()`)
   **and is not the default/Uncategorized term** (`get_option('default_product_cat')`,
   read once, not hardcoded as `15`) — if valid and not Uncategorized, use
   its name.
2. **Fallback**: from `get_the_terms( $product_id, 'product_cat' )`, filter
   out any term whose `term_id` matches `get_option('default_product_cat')`,
   then take the first remaining term (sorted by `term_id` ascending). This
   never calls `wc_get_product_category_list()` (which would join *all*
   categories, wrong for a single eyebrow).
3. **If no non-default category remains** (today: true for 14/26 products,
   §above) — **omit the eyebrow entirely.** No placeholder text, no
   "Uncategorized" ever shown in the new customer-facing PDP identity area.

`get_option('default_product_cat')` is read once and compared by ID, not by
matching the string "Uncategorized" or a hardcoded `15` — if the site is
ever re-seeded and WordPress assigns a different term ID to the default
category, this logic still resolves correctly without a code change.

---

## 5. Add-to-cart transport — verified, not assumed

**Revision 2 assumed "native WC AJAX add, cart count/fragment update."
Verified instead by reading the actual code path that runs on DEV:**

WooCommerce's own core AJAX add-to-cart (`wc-add-to-cart.js` +
`wc_add_to_cart_params`) is, by default, wired only for shop/archive/loop
"Add to cart" buttons — **not** single-product pages, where WooCommerce
core's default behavior is a plain `<form>` POST causing a full page
reload/redirect.

**But Blocksy's theme adds its own, separate, bespoke AJAX layer for
single-product pages**, confirmed live and active on DEV:

- `themes/blocksy/inc/components/woocommerce/single/add-to-cart.php`
  (`WooCommerceAddToCart` class) adds `ct-ajax-add-to-cart` to the product
  wrapper's classes (`woocommerce_post_class` filter) whenever both:
  - theme mod `has_ajax_add_to_cart` = `'yes'` (its own default; **confirmed
    unset on DEV, so the default applies**), **and**
  - `get_option('woocommerce_cart_redirect_after_add')` = `'no'`
    (**confirmed literally `'no'` on DEV via live query**).
- `themes/blocksy/static/js/frontend/woocommerce/main.js:72-73` wires
  `.ct-ajax-add-to-cart .cart` elements (i.e. the `<form class="cart">` /
  `<form class="variations_form cart">` nested inside an ajax-enabled
  product wrapper) to load and mount
  `add-to-cart-single.js`'s `singleProductAddToCart()`.
- That function intercepts the form submit, builds a `FormData` from it,
  does a `fetch()` POST to the form's action URL with a
  `blocksy_add_to_cart=yes` query param appended (not a normal navigation,
  not a normal page reload), and on success triggers a jQuery
  `added_to_cart` event carrying `fragments` (a `div.widget_shopping_cart_content`
  mini-cart HTML fragment, WooCommerce's own `woocommerce_add_to_cart_fragments`
  filter output) and `cart_hash`. The server side of this exact exchange is
  `WooCommerceAddToCart::do_wc_ajax()`, hooked to `template_redirect` and
  gated on `$_REQUEST['blocksy_add_to_cart']` — a **Blocksy-proprietary AJAX
  endpoint, not WooCommerce core's own `wc-ajax=add_to_cart` handler.**
- If `formUrl` is somehow not a string (defensive fallback in the JS
  itself), it falls back to `form.submit()` — a real page-navigating POST.

**Confirmed current DEV behavior, both product types**: single-product Add
to Cart uses **Blocksy's own bespoke fetch-based AJAX flow**
(`add-to-cart-single.js` + `do_wc_ajax()`), updating the mini-cart via the
`added_to_cart` event and `fragments`/`cart_hash` payload — not a full page
reload, and not WooCommerce's separate core AJAX mechanism used on archive
pages.

**Acceptance criterion, rewritten as instructed:**

> Add to cart succeeds with the same transport/navigation/cart-state
> behavior that existed before PDP-1 — currently: Blocksy's fetch-based AJAX
> (`.ct-ajax-add-to-cart .cart` → `singleProductAddToCart()` →
> `blocksy_add_to_cart` request → `added_to_cart` event with mini-cart
> fragment + cart hash), for both simple and variable products, with no page
> reload. If `has_ajax_add_to_cart` or `woocommerce_cart_redirect_after_add`
> ever change, the underlying `<form>` still POSTs correctly (native
> fallback, untouched by this plan) — PDP-1 must not assume AJAX is
> permanent, only preserve whatever the live settings currently produce.

This mechanism depends only on the `.product` wrapper's classes and the
`<form>`'s presence/action/method — none of which this plan's panel-wrapping
(§2) touches. Still to be verified directly in the structural proof (§14
WP0), not assumed safe merely because the selectors look unrelated.

---

## 6. Variation SKU swap — confirmed native, not assumed

Read directly from `assets/js/frontend/add-to-cart-variation.js:340-362`
(`VariationForm.prototype.onFoundVariation`), the exact, unmodified
WooCommerce core file loaded on DEV (no biopentra/Blocksy fork of this file
found anywhere in either repo):
```js
$sku = form.$product.find( '.product_meta' ).find( '.sku' ),
...
if ( variation.sku ) {
    $sku.wc_set_content( variation.sku );
} else {
    $sku.wc_reset_content();
}
```
This confirms **variation-specific SKU swap in `.product_meta .sku` is
native, currently-active WooCommerce core behavior**, not a customization,
and not something this plan introduces. Since the panel wrapper (§2) never
moves `.product_meta` (it stays in its own native position, outside the
panel, per the target composition) and never touches this JS, this behavior
is preserved automatically — it is confirmed via source-level forensics of
the exact unmodified core file WooCommerce loads on this site.

**Caveat, stated honestly**: this is source-code confirmation, not a live
browser/DOM observation (no browser-driving tool was available during
planning). **The structural proof (§14 WP0) must include an actual
Playwright/browser check** — select a variation with a distinct SKU on
`tirzepatide`, confirm `.product_meta .sku` updates — as the final
confirmation before this becomes a locked acceptance criterion. Per the
instruction: if that live check contradicts the source reading (e.g. some
other layer suppresses it), PDP-1 does **not** introduce SKU-swapping as a
new feature — it only preserves whatever the live check actually shows.

---

## 7. Locked rule: DOM order = visual order, no CSS `order`

Unchanged from Revision 2. No part of this milestone uses CSS `order`,
flex/grid reordering, or any other visual-only reflow to fake hierarchy.
Every position in the target composition (§8) is achieved by controlling
*where in the actual markup* the element renders (§2–§6), never by CSS
repositioning.

---

## 8. Recommended target composition

**Variable product** (e.g. `tirzepatide`):
```
[ eyebrow: primary/first non-Uncategorized category, or omitted ]
[ H1 product title ]
[ price range — native .price, unmoved, informational ]
[ short description — native excerpt, unmodified ]
[ variation selector: Strength — native <table class="variations"> ]
┌─ purchase panel (bordered, subtle) ───────────────────────────┐
│ [ price + stock/availability — native, bundled, one JS blob ] │
│ [ quantity ] [        Add to cart        ]                    │
│ [ trust ] [ trust ] [ trust ]                                  │
└─────────────────────────────────────────────────────────────┘
[ product meta: SKU · Category · Tags — native, compact ]
```

**Simple product** (e.g. `bacteriostatic-water`):
```
[ eyebrow: primary/first non-Uncategorized category, or omitted ]
[ H1 product title ]
[ short description — native excerpt, unmodified ]
[ stock/availability — native, renders before the form regardless ]
┌─ purchase panel (bordered, subtle) ───────────────────────────┐
│ [ price — native, relocated per §3, simple products only ]    │
│ [ quantity ] [        Add to cart        ]                    │
│ [ trust ] [ trust ] [ trust ]                                  │
└─────────────────────────────────────────────────────────────┘
[ product meta: SKU · Category · Tags — native, compact ]
```

---

## 9. Implementation architecture

**1. Fully native, untouched:** title, short description, `.variations`
table, `.woocommerce-variation-price`/`.woocommerce-variation-availability`
(bundled), `.quantity .qty`, `.single_add_to_cart_button`, `.product_meta`,
Blocksy's `.ct-cart-actions` inner wrapper, Blocksy's AJAX add-to-cart
transport (§5), variation SKU-swap JS (§6). All commerce logic untouched.

**2. Type-scoped hook relocation:** simple-product price move (§3),
gated on `$product->is_type('simple')` before any `remove_action` call.

**3. New wrapper/output (new, narrowly-scoped PHP module)** — proposed
`biopentra-storefront/modules/pdp-purchase-panel/class-pdp-purchase-panel-module.php`:
- Eyebrow (§4).
- Panel open/close:
  - Simple: `woocommerce_before_add_to_cart_form` (open) /
    `woocommerce_after_add_to_cart_form` (close) — always balanced, no
    conditional branch to track (simple.php has no analogous empty-state
    skip).
  - Variable: `woocommerce_after_variations_table` (open) /
    `woocommerce_after_variations_form` (close), balanced via the
    render-state flag (§2.4), not condition duplication.
- Trust row, appended just before panel-close, both branches.
- Price relocation for simple products only (§3).

No changes to `biopentra-blocksy-child` PHP for structure — it remains
CSS/enqueue-only, consistent with its D2A/D2B role.

**4. CSS-only** — unchanged from Revision 2: new
`biopentra-blocksy-child/assets/pdp/purchase-panel.css`, tokens-only, no
hardcoded values, no CSS `order` (§7).

**5. JavaScript: none required**, confirmed against both the panel-wrapping
mechanics (§2.3) and the AJAX add-to-cart transport (§5) — the panel does
not sit between the `.product` wrapper and the `<form>`, so Blocksy's
`.ct-ajax-add-to-cart .cart` selector is unaffected. Final confirmation is a
structural-proof (§14 WP0) task, not a planning-time assumption.

---

## 10. Responsive forensics (unchanged from Revision 2, verified against real compiled theme CSS)

Real two-column split point: **`min-width: 1000px`**, set by Blocksy parent
theme (`themes/blocksy/static/bundle/woocommerce.min.css`), not by any
biopentra CSS. `--product-gallery-width` defaults to 50% (confirmed unset).
Sticky gallery/summary confirmed **off** (`has_product_sticky_gallery`
unset, Blocksy default `'no'`). No "768–1023 layout gap" exists — that
Revision 1 claim was checked against real CSS and is retracted. **No D2A
breakpoint repair is in scope or warranted (PO-confirmed, §12).**

All eight requested test viewports fall cleanly on one side of the real
1000px boundary: 360/390/430/768 single-column, 1024/1025/1440/1680
two-column.

---

## 11. Short description and trust indicators (unchanged, PO-confirmed, §12)

- **No line-clamp.** 15/26 (58%) products have empty short descriptions
  (majority case); of the 11 with content, lengths are short (median 87,
  p90 122 chars); one outlier (Gift Card, 440 chars) is a special,
  separately-handled product, not a systemic problem. Empty case already
  handled natively — `short-description.php` returns before echoing
  anything when the excerpt is empty, so there is no dangling wrapper to
  hide.
- **Trust copy, locked**: "EU fulfillment" / "Secure checkout" /
  "COA-backed quality" — direct adaptations of the live checkout-v2 trust
  strip (`inc/checkout-v2/class-checkout-v2.php:202-208`), no unadapted
  mockup copy ("Fast delivery," "Purity verified," "SSL encrypted," etc.).
- **Icons**: text-only initially — no reusable coded icon/SVG system exists
  for native PHP-rendered contexts (the only existing trust-icon precedent,
  `--bp-trust-icon-max-h`, is Elementor-uploaded images on home/shop, not
  applicable to native WooCommerce output). Not a PO gate; a follow-up
  choice if desired later.

---

## 12. Genuine unresolved architectural/product decisions: none remaining

Every item that was previously a PO gate has been resolved and accepted:

- Simple-vs-variable stock asymmetry (§2.2) — **accepted**, no duplicate
  stock output will be added for visual symmetry.
- Trust copy (§11) — **locked**.
- Text-only trust row initially (§11) — **accepted**.
- No short-description clamp (§11) — **accepted**.
- No D2A breakpoint repair (§10) — **accepted**, confirmed unnecessary by
  forensics.
- Primary-category eyebrow with non-Uncategorized fallback (§4) —
  **accepted**.
- No CSS order/reordering (§7) — **locked rule**.
- No WooCommerce template override (§1, §9) — **locked**.

**Remaining items are execution-time verifications, not decisions**: the
structural proof (§14 WP0) must empirically confirm (not merely reason from
source) that Add to Cart transport (§5), variation SKU swap (§6), and the
zero-purchasable-variations render-state flag (§2.4) all behave as forensics
indicate, on the real DEV site, before CSS work begins. If any live check
contradicts this plan's forensics, WP0 is the checkpoint to catch it —
report the discrepancy and adjust rather than forcing the plan as written,
consistent with how this plan itself was produced.

Visual/spacing/typography details (panel max-width on ultra-wide, exact
padding, icon adoption later) remain implementation choices for PO visual
review during WP3–WP4 (§14), not architecture gates.

---

## 13. Accessibility

Unchanged: eyebrow is a `<p>`, not a heading; H1 remains the only heading.
Panel wrapper is a plain `<div>` (no `role="region"` — reserved for the
sticky bar's genuinely detached mini-UI case). Trust list: plain `<ul>`, no
`aria-hidden`. Native controls keep existing focus-visible behavior;
verify new panel background/border doesn't wash out existing outlines.
Stock color-coding keeps its existing text as the accessible name — the
availability markup itself is untouched, only its container moves.

---

## 14. Work packages (structural proof carries all relocation logic; CSS strictly follows)

### WP0 — Structural proof (blocking gate, no CSS, no visual output)
- **Objective**: prove the full §2–§6 architecture — including simple-price
  relocation — is safe on real products *before* any visual work begins.
  This absorbs Revision 2's separate WP1 relocation step; there is no
  remaining WP/WP0 contradiction.
- **Files**: module skeleton
  (`biopentra-storefront/modules/pdp-purchase-panel/class-pdp-purchase-panel-module.php`)
  implementing: panel open/close for both product types (§2.1, §2.4,
  render-state flag, no styling), simple-product price relocation scoped to
  `is_type('simple')` (§3). No eyebrow, no trust row yet (those are WP1/WP2
  — pure additions, lower risk, don't need to gate CSS start).
- **Dependencies**: none.
- **Validation — run against real fixtures, empirically, not by source
  reading alone:**
  - `tirzepatide` (variable, 2 available variations) and
    `bacteriostatic-water` (simple) for the primary structural/behavioral
    checks below.
  - `m21-postrelease-variable` (variable, 0 available variations — real,
    live fixture, confirmed via `wc_get_products()`) for the
    zero-purchasable-variations branch: confirm the "out of stock and
    unavailable" message renders, confirm **no** orphaned
    `.bp-pdp-purchase-panel` closing tag/malformed HTML results (render-state
    flag, §2.4).
  - Checks, on `tirzepatide` and `bacteriostatic-water`: intended DOM
    hierarchy (variations table outside panel; relocated price, stock as
    applicable, qty/ATC all inside panel); valid unbroken form structure;
    native variation switching (`found_variation` fires, price/availability
    update inside the wrapper); variable-product top-level `.price` range
    still renders natively and is untouched (§3's regression check);
    relocated simple-product price appears exactly once, never duplicated;
    quantity min/max/step still updates on variation change; **Add to Cart
    exercised live in a real browser session, confirming Blocksy's
    fetch-based AJAX flow (§5) still fires — `added_to_cart` event,
    mini-cart fragment update, no page reload** — not inferred from
    selectors alone; **variation SKU swap in `.product_meta .sku` exercised
    live** (§6) — if it does not occur live despite the source-level
    confirmation, do not add SKU-swapping as a new PDP-1 feature, only
    report the discrepancy; UMC currency conversion still reflected
    correctly (`?currency=` spot check); D2B sticky-bar still
    shows/hides/syncs correctly.
- **Stopping point**: all of the above pass, on all three fixture products,
  with the wrapper divs and price relocation in place and *zero visual
  styling/polish* — structural output only, no CSS applied yet. Do not
  proceed to WP1 until this is true. Any discrepancy between this plan's
  forensics and live behavior is resolved here, before CSS starts.

### WP1 — Eyebrow (identity output only — price relocation already covered in WP0)
- **Objective**: add the category eyebrow (§4) — the only remaining
  identity-output piece not already covered by WP0.
- **Files**: same module.
- **Dependencies**: WP0.
- **Validation**: on `tirzepatide`/`bacteriostatic-water` (both carry only
  the Uncategorized category per the catalog data in §4 unless reassigned)
  confirm the eyebrow is correctly **omitted**; separately confirm, on any
  product that does carry a real category, that exactly one category name
  appears (never a comma list, never "Uncategorized").
- **Stopping point**: eyebrow logic verified correct on both the
  Uncategorized-only majority case and a real-category case.

### WP2 — Trust row
- **Objective**: add the locked 3-item trust `<ul>` (§11) inside the panel,
  after the qty/ATC row.
- **Files**: same module.
- **Dependencies**: WP0.
- **Validation**: visual + a11y; copy matches §11 exactly.
- **Stopping point**: trust row present, correct copy, no styling beyond
  plain list yet.

### WP3 — CSS: panel visual treatment
- **Objective**: border/radius/shadow/padding/background, desktop one-row
  quantity+ATC.
- **Files**: new `biopentra-blocksy-child/assets/pdp/purchase-panel.css`;
  one new `wp_enqueue_style` call alongside the existing
  `is_product()`-gated enqueue in `blocksy-child/functions.php`.
- **Dependencies**: WP0–WP2.
- **Validation**: visual across the full viewport matrix (§10); confirm
  tokens-only, no CSS `order` (§7).
- **Stopping point**: panel visually matches target composition (§8) on all
  breakpoints; PO visual sign-off checkpoint.

### WP4 — CSS: eyebrow/meta polish
- **Objective**: remaining visual details.
- **Files**: `purchase-panel.css` (extends WP3).
- **Dependencies**: WP1, WP3.
- **Stopping point**: full visual hierarchy matches target on both product
  types.

### WP5 — Acceptance test expansion
- **Objective**: automate WP0's structural/behavioral checks plus the
  broader viewport/a11y/multicurrency matrix.
- **Files**: `storefront-acceptance/tests/pdp-purchase-panel.spec.ts` (new),
  possible extension of `pdp-layout.spec.ts`, new `run-dev.sh` flag,
  `budgets.json` baseline capture.
- **Structural assertions**: variable product — `.variations` table is not
  a descendant of `.bp-pdp-purchase-panel`, `.single_variation_wrap` is;
  simple product — relocated `.price` and `.single_add_to_cart_button` are
  descendants of the panel, top-level `.stock` is not; zero-variation
  product (`m21-postrelease-variable` or equivalent) — no malformed/orphaned
  panel markup.
- **Dependencies**: WP0–WP4 deployed to DEV.
- **Stopping point**: full suite green across the viewport matrix; manual
  PO visual captures taken.

### WP6 — Docs/closure (placeholder only, not executed by this plan)

---

## 15. Risks and regression surfaces

- **Highest risk, now precisely scoped and empirically checked in WP0**:
  Blocksy's `.ct-ajax-add-to-cart .cart` AJAX wiring (§5) and core's
  variation-SKU-swap JS (§6) both depend on selectors/ancestry this plan
  does not change — but must be verified live, not assumed from reading
  source.
- **Simple-price relocation scope** (§3): the `is_type('simple')` guard is
  the load-bearing correctness check — WP0 must explicitly confirm variable
  products' top-level price range is untouched.
- **Orphaned closing div** (§2.4): resolved via render-state flag; WP0
  exercises the real zero-variations fixture.
- **`.product_meta` top border** (`layout.css:84-90`) — confirm the new
  panel's bottom spacing plus this existing top-border still reads as one
  restrained divider once the DOM between them changes.
- **Gift-card products** (`mp-commerce-promotions`) — verify their
  filtered price HTML/ATC text/purchasability still render sensibly once
  moved into the panel.
- **UMC price-hash caching** — cache-key concern only, unaffected by DOM
  relocation; this plan introduces zero new price-filtering, only relocates
  existing native calls under the corrected, type-scoped guard (§3).

---

## 16. Rollback strategy

Unchanged: all changes are additive (new module, new stylesheet, no
template overrides). Deactivating the `pdp-purchase-panel` module (one
`require_once` line) reverts eyebrow/panel/trust-row/price-relocation
instantly to native WooCommerce output, including restoring
`woocommerce_template_single_price` to its default priority-10 registration
for simple products (hook removal is per-request; nothing persists). D2A/D2B
remain fully independent.

---

## 17. Deliverable cross-reference

Executive assessment §1 · Architecture gate/forensic map §2 · Simple-price
scoping §3 · Eyebrow/category §4 · Add-to-cart transport forensics §5 ·
SKU-swap forensics §6 · DOM-order rule §7 · Target composition §8 ·
Implementation architecture §9 · Responsive strategy §10 · Description/trust
§11 · PO decisions (none remaining) §12 · Accessibility §13 · Work packages
§14 · Risks §15 · Rollback §16 · Final recommendation §1.

---

## Addendum A — Blocksy price-ownership correction (2026-08-24, during WP0)

**Status: approved and implemented.** This amends §3's mechanism only.
Nothing else in this plan changes.

**Original assumption (§3, as frozen):** the native single price on
`woocommerce_single_product_summary` could be relocated with a plain
`remove_action( 'woocommerce_single_product_summary',
'woocommerce_template_single_price', 10 )` followed by re-adding it on
`woocommerce_before_add_to_cart_form`.

**Live contradiction, found during WP0's structural proof:** on real DEV
(`bacteriostatic-water`), `remove_action()` returned `false` and the price
rendered **twice** — once at its original position, once inside the new
panel. Root cause, read directly from
`themes/blocksy/inc/components/woocommerce/single/single-modifications.php:150-177`:
Blocksy itself hooks `wp` at priority `9000000000` (i.e. after everything
else) and removes `woocommerce_template_single_price` (and title/rating/
excerpt/add_to_cart/meta/sharing) from `woocommerce_single_product_summary`
first, then re-renders all of them itself via
`blocksy_manager()->woocommerce->single->render_layout()` — a theme-owned,
ordered-layer renderer, driven by `blocksy_get_woo_single_layout_defaults()`
and the (unset, defaulted) `woo_single_layout` theme mod. By the time PDP-1's
own `woocommerce_single_product_summary` callback runs, WooCommerce's native
price hook is already gone; there is nothing left to remove, so the
`add_action()` produced a pure duplicate instead of a relocation.

**Blocksy ownership discovered:** for the *outer* single-product summary
(title/price-range/excerpt), Blocksy — not WooCommerce's classic hook
chain — is the actual rendering authority on this theme, active whenever
`blocksy_get_product_view_type()` is `default-gallery` or `stacked-gallery`
(confirmed live: DEV is `default-gallery`). The *inner* add-to-cart form
(`templates/single-product/add-to-cart/{simple,variable}.php`, including all
of §2's panel-wrapping hooks) is unaffected — Blocksy still calls
`woocommerce_template_single_add_to_cart()` directly as one of its own
layers, and that function still executes the native template file with all
its native `do_action()` calls intact. §2's architecture, and the entire
variable-product mechanism, needed no changes.

**Approved corrected mechanism:** `render_layout()` applies a genuine,
theme-provided extension seam before iterating its layers:
```php
$args['layout'] = apply_filters(
    'blocksy:woocommerce:product-single:layout',
    $args['layout']
);
```
Each layer is `[ 'id' => ..., 'enabled' => bool, ... ]`; the price layer's
id is `product_price` (confirmed via
`inc/components/woocommerce/common/layer-defaults.php`). PDP-1 hooks this
filter and, only when `is_product()` and the current `$product` is
`is_type('simple')`, sets that one layer's `enabled` to `false` for the
current render's in-memory array — the persisted `woo_single_layout` theme
mod is never written to, so nothing is changed for any other visitor,
render, or product type. Variable products are explicitly excluded in the
same conditional, so their top-level price range is untouched. Because the
filter only ever fires inside `render_layout()`'s single-product-summary
call path, it structurally cannot reach shop/archive/loop cards (those use
a separate `blocksy_woo_card_options_layers:defaults` layer set and a
different render path entirely).

With the outer layer suppressed, PDP-1 calls WooCommerce's own
`woocommerce_template_single_price()` exactly once, on
`woocommerce_before_add_to_cart_form` (inside the already-open purchase
panel). WooCommerce remains the sole price authority — PDP-1 changes only
*where* that one native call happens, holds no price value/state of its own,
and performs no CSS-hiding of a duplicate.

**Why this seam is treated as reasonably stable, not a private internal:**
`blocksy:woocommerce:product-single:layout` is a plain `apply_filters()`
call in the theme's own primary single-product render path (not a
prefixed/underscore "private" name, not reached via reflection or a
non-public method) that exists specifically so the returned layer array can
be altered before rendering — the same pattern (`enabled` toggles on an
ordered layer list) is also how Blocksy's own Customizer options and
`blocksy:woocommerce:product:custom:layer` extension point work, i.e. this
*is* the theme's intended mechanism for this exact kind of change, not an
incidental hook this plan is leaning on opportunistically.

**Verification (WP0 rerun after the amendment):**
- Simple (`bacteriostatic-water`): exactly one native `.price` in the
  summary, located inside `.bp-pdp-purchase-panel`; no outer Blocksy price
  duplicate. Live Add to cart confirmed via Playwright — Blocksy's
  fetch-based AJAX (`added_to_cart` event) fires, no page navigation, zero
  console errors.
- Variable (`tirzepatide`): top-level price range renders exactly once,
  natively, outside the panel (unchanged — never touched by this
  mechanism, since it's gated on `is_type('simple')`); variations table
  renders before the panel opens; live variation switching confirmed via
  Playwright — bundled price/availability update inside the panel,
  `.product_meta .sku` updates, Add to cart via AJAX succeeds, no console
  errors.
- Zero-purchasable-variations (`m21-postrelease-variable`): no orphaned
  panel markup (render-state flag, §2.4, unaffected by this amendment).
- D2B sticky bar (`[data-bp-sticky-bar]`): present and unmodified on both
  product types.
- Shop archive (`/shop/`): loop-card prices unaffected (12 prices rendered
  normally) — confirms the filter does not leak into archive/loop rendering.

**Evidence / commit reference:** implemented in
`plugins/biopentra-storefront/modules/pdp-purchase-panel/class-pdp-purchase-panel-module.php`
(`suppress_blocksy_simple_price_layer()` / `maybe_render_simple_price()`),
commit to follow this addendum in the same push. WP0 behavioral proof
automated in `storefront-acceptance/tests/pdp-purchase-panel.spec.ts`.

---

## Addendum B — WP3/WP4 visual-implementation PO decisions (2026-08-24)

Four PO decisions made during live visual review of WP3/WP4, each recorded
here because it either overrides earlier-locked plan language or is a
deliberate exception to a stated boundary. None reopen the WP0 architecture.

**B1 — Trust row copy overridden.** §11's originally locked single-line
copy ("EU fulfillment" / "Secure checkout" / "COA-backed quality") is
replaced, by explicit PO direction during visual review, with an icon +
two-line format: "EU Warehouse / Fast delivery", "Secure Payment / SSL
encrypted", "Lab Quality / Purity verified" (truck/lock/shield-check inline
SVG icons, no new icon system — three one-off inline SVGs in the trust-row
partial only). This is a deliberate, explicit override of §11/§12's "locked"
status for this one item; nothing else in §11 changes (still text+icon only,
still subordinate to price/qty/ATC).

**B2 — Gallery hover-zoom removed.** Explicit PO direction: disable the
desktop hover-to-zoom effect on the product gallery image. Forensics: Blocksy's
`static/js/frontend/woocommerce/single-product-gallery.js` initializes
`jquery.zoom` with `isZoomEnabled` hardcoded `true` on regular page loads —
the `has_product_single_zoom` theme mod is only read inside the Customizer
preview (`wp.customize(...)`), never on the live front-end, so there is no
runtime PHP/theme-mod toggle to flip. Implemented as a scoped CSS suppression
of the plugin's injected `.zoomImg` overlay
(`biopentra-blocksy-child/assets/pdp/purchase-panel.css`) — no new JS, no
Blocksy core edit, the separate click-to-lightbox feature (gated by
`has_product_single_lightbox`, off by default) is untouched. This is a
deliberate, explicit exception to this plan's "gallery stays frozen"
boundary (§1, and the WP1-continuation task's own boundary list), scoped to
exactly this one hover behavior — no other gallery change was made.

**B3 — Variation availability restyled in place, not relocated.** PO
initially asked for variable-product availability ("In stock") to render
outside/above the purchase panel, matching a reference screenshot. Confirmed
this would require new JS: WooCommerce's `add-to-cart-variation.js` renders
price_html and availability_html together as one atomic blob per §2.2 — the
only way to physically separate them in the DOM is to intercept
`found_variation`/`show_variation` and move the rendered node after each
variation change, which is new custom variation-state JS, explicitly
prohibited by this task's WP0-preservation rules. **PO accepted the
technical constraint** and chose the CSS-only alternative: availability
stays bundled inside the panel (per §2.2's original, unchanged asymmetry)
but is restyled with a green filled-circle checkmark and accent color so it
visually reads as a confirmation, without any DOM relocation. §2.2's
documented asymmetry stands exactly as originally specified.

**B4 — Metadata (SKU/Category/Tags) label alignment.** WP4 polish: native
`product_meta` labels ("Category:"/"Categories:"/"Tag:"/"Tags:") are wrapped
in a `<span class="bp-pdp-meta-label">` via a `gettext`/`ngettext` filter
scoped to `is_product()`, matching only those four exact strings — not a
template override (`templates/single-product/meta.php` is unmodified; only
the already-translatable label strings it echoes are filtered) and not DOM
surgery JS (pure PHP string filter + CSS). **"SKU:" is deliberately excluded**
from this filter: `meta.php` echoes it via `esc_html_e()`, which re-escapes
whatever the filter returns, turning an injected `<span>` into literal
visible `&lt;span&gt;` text (caught and reverted during this same
implementation pass — see commit history). SKU gets a CSS-only label
treatment instead, styling `.sku_wrapper`'s own (unwrapped) leading text via
inheritance, with its existing nested `.sku` value span restyled back to
normal value treatment — visually consistent with the other two rows though
not pinned to the identical fixed-width column.

**Evidence:** `biopentra-blocksy-child/assets/pdp/purchase-panel.css`
(trust row, zoom suppression, availability checkmark, metadata alignment,
bold variation attribute label, Clear-link removal via
`woocommerce_reset_variations_link` filter). Verified via the same WP0
Playwright suite (all 4 tests green) plus the existing D2A/`pdp-layout.spec.ts`
and D2B/`pdp-sticky.spec.ts` suites (12/12 applicable tests green across the
full viewport matrix) after these changes landed. `biopentra-storefront`
bumped to 0.9.34; `biopentra-blocksy-child` bumped to 1.1.4 (four
consecutive patch bumps during this session, each solely to cache-bust the
enqueued `purchase-panel.css`/`functions.php` after a fix).

---

## Closure addendum (2026-08-24) — PO-APPROVED / FROZEN ON DEV

**Status: CLOSED.** This milestone is retitled at closure, reflecting its
final delivered scope: **"Expanded Product Page Redesign"**, superseding the
name "Purchase Summary Redesign" used above. Nothing in the plan body or
Addenda A/B above is rewritten; this section only records what closed.

Beyond WP0–WP5 as planned, PO additionally authorized during implementation:
simple-product stock relocation into the panel (matching variable's bundled
treatment, final commit `466d0c0`), the full gallery/thumbnail feature and
its `flexy.js` thumbnail-clipping root-cause fix, and tabs
(Description/Additional Information) styling — all landed in
`biopentra-blocksy-child` through `1.2.9`. Full inventory, final
versions/tags, validation results, and rollback procedure:
[`docs/storefront-redesign/changes/pdp-1-product-page-redesign.md`](../changes/pdp-1-product-page-redesign.md).

**Final baseline:** `biopentra-custom-plugins` `storefront-v0.9.35` /
`biopentra-blocksy-child` `v1.2.9`. **Rollback baseline:**
`storefront-v0.9.33` / `1.1.0`. No M11/PDP-2/Motion & Interaction Polish
work is opened by this closure.
