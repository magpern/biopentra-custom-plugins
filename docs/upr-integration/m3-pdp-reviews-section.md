# M3 PDP Reviews Section — freeze specification

**Status:** Frozen for implementation (documentation only at original freeze). **Ownership amended by B3** — see [`m3-pdp-reviews-section-b3-ownership-amendment.md`](m3-pdp-reviews-section-b3-ownership-amendment.md); amendment freeze tag `m3-pdp-reviews-section-freeze-b3`.  
**Original freeze tag:** `m3-pdp-reviews-section-freeze` (unchanged; not moved)  
**Baseline host commit (preserved, unaltered):** `18d0b3d69391a087270d70d532e265406d07ed73`  
**UPR pin (B3 amended):** annotated tag `v0.2.2` / commit `43c9989291a4c7eab7f9fd57603c851486da287a`  
**Host pin (B2 merged):** `biopentra-upr-host` **0.1.3** on main (`8a14ba1ebed69492a7690249fe762c38bc453707`)  
**Scope:** Standard Blocksy product pages on DEV. Elementor product documents are **out of scope** for Phase 1.  
**Production:** No production change, release, or ZIP authorised by this freeze.

This document is the **authoritative** host specification for restoring a real PDP `#reviews` experience while Blocksy product tabs remain disabled. Implementation must satisfy the A1–A20 matrix below. Do not re-litigate architecture here after freeze. **B3 amends ownership/dependency only** — rendering architecture, template exception, sticky-bar lock, Blocksy-only scope, and A1–A20 remain unchanged.

**B4 gate:** PDP section implementation may begin only after both **UPR `v0.2.2`** and **merged B2 host integration** are present (satisfied on main after PR #11). No DEV bind-mount/deploy is implied by this amendment.

---

## 1. Root cause (confirmed)

1. The purchase-panel rating summary ([`biopentra-storefront` `Pdp_Rating_Summary_Module`](../../plugins/biopentra-storefront/modules/pdp-rating-summary/class-pdp-rating-summary-module.php)) links to `{permalink}#reviews`.
2. Blocksy theme mod `woo_has_product_tabs=no` empties the **`woocommerce_product_tabs` filter** at **filter priority 99**, so no Reviews (or Description / Additional) tab markup is emitted.
3. Native WooCommerce `#reviews` markup normally appears only inside the reviews tab callback (`comments_template` → `single-product-reviews.php`). With tabs disabled, **`#reviews` is absent** from catalog PDP HTML.
4. Separately, host UX (`biopentra-upr-host`) sets `comments_open` to false when UPR availability says `can_submit=false`. Stock WooCommerce `single-product-reviews.php` returns early when `comments_open()` is false, which would suppress the **approved review list** as well as the form — even if tabs were re-enabled.

**Customer impact:** Summary links are dead anchors; approved reviews persist in UPR/WooCommerce data but are not reachable as a visible PDP section.

---

## 2. Filter vs action priority (corrected)

These are **two different WordPress hook namespaces**. Do not conflate them.

| Mechanism | Hook | Priority | Effect when tabs are off |
|-----------|------|----------|--------------------------|
| Blocksy empties tab list | Filter `woocommerce_product_tabs` | **99** | Returns `[]` |
| WooCommerce still invokes tabs renderer | Action `woocommerce_after_single_product_summary` → `woocommerce_output_product_data_tabs` | **10** | Runs, reads empty tabs, emits **no** tab markup (no-op) |
| Blocksy tabs:before | Action `woocommerce_after_single_product_summary` | **9** | Fires flanking action |
| Blocksy tabs:after | Action `woocommerce_after_single_product_summary` | **11** | Fires flanking action |
| **New dedicated reviews section** | Action `woocommerce_after_single_product_summary` | **12** | After no-op tabs (10) and tabs:after (11) |

Filter priority **99 is not** an `after_single_product_summary` priority. Action priority **12** is chosen solely for action ordering relative to 9 / 10 / 11.

---

## 3. Frozen rendering architecture

### 3.1 Decision

- Keep Blocksy product tabs **disabled** (no Description / Additional / Reviews tab chrome).
- Add a **dedicated** PDP reviews section for standard Blocksy PDPs.
- Hook: `woocommerce_after_single_product_summary` at **action priority 12**.
- Render **native WooCommerce review markup** (`#reviews.woocommerce-Reviews`, `.commentlist`, `.woocommerce-Reviews-title`) via a **narrow plugin-owned template exception** (see §4).
- Remove host UX use of `comments_open=false` for submission gating (**done in B2** — host must not reintroduce it).
- Gate the native PDP form with UPR display-only `NativePdpForm::should_render()` (host may thin-wrap as `can_submit_for_product()` for storefront callers; no second rule engine) (§5).
- Enforce logged-in native POST rejection in **UPR core** (`NativeSubmissionGuard`) when availability `can_submit=false` (§5). Host must not register a competing `preprocess_comment` guard.

### 3.2 Markup contract

```html
<section class="bp-pdp-reviews-section" data-bp-reviews-section="1">
  <div id="reviews" class="woocommerce-Reviews" tabindex="-1">
    <div id="comments">
      <h2 class="woocommerce-Reviews-title">…</h2>
      <ol class="commentlist">…</ol>
      <!-- or .woocommerce-noreviews when empty -->
    </div>
    <!-- #review_form_wrapper ONLY when NativePdpForm::should_render() / host thin wrapper is true -->
    <p class="biopentra-upr-host-review-unavailable" role="status">…</p>
    <!-- when submission is unavailable -->
  </div>
</section>
```

**Accessibility (frozen):** `#reviews` must have `tabindex="-1"`. After summary-link activation or deep-link `#reviews`, programmatic focus must move to `#reviews` (scroll without focus fails A11). Sticky-buy-bar DOM / selectors / JS / CSS must not change.

### 3.3 Rejected alternatives (frozen)

| Alternative | Why rejected |
|-------------|--------------|
| Re-enable Blocksy tabs globally | Restores removed PDP tab chrome; Description/Additional regression |
| Inject only a Reviews tab via `woocommerce_product_tabs` @ 100 | Still tab UI; not a dedicated section |
| Fake / custom review list markup | Breaks native WC semantics, CSS, schema parity |
| Theme template override in `blocksy-child` | Theme-locatable; accidental takeover risk; policy last resort |
| Rely on `comments_open=false` | Proven to hide approved reviews via WC early-return |

---

## 4. Template-fork exception (approved narrow exception)

### 4.1 Why hooks are insufficient

| Mechanism | Limitation |
|-----------|------------|
| Stock `comments_template()` / WC `single-product-reviews.php` | Early `if ( ! comments_open() ) return;` omits entire `#reviews` including the list |
| Host `comments_open → false` when `!can_submit` | Same early-return (current broken path) |
| `woocommerce_product_review_comment_form_args` | Can clear `title_reply`; cannot omit `#review_form_wrapper` or skip `comment_form()` |
| `woocommerce_before_single_product_reviews` | Additive only; cannot suppress the form block |
| Output-buffer and strip form | Fragile; still inherits `comments_open` early-return |
| Hand-rolled list markup | Violates native semantics |

**Conclusion:** A surgical fork of `single-product-reviews.php` that (1) drops the `comments_open` early-return, (2) always renders the approved-review list, (3) wraps the form in `can_submit_for_product()`, is the minimal way to preserve native markup. This is an **explicit narrow exception** to “prefer hooks.”

### 4.2 Ownership and load rule

- **Owner:** `biopentra-storefront` plugin.
- **Path:** `plugins/biopentra-storefront/templates/woocommerce/single-product-reviews.php`
- **Load:** **Direct-load** from the module (e.g. `load_template( BIOPENTRA_STOREFRONT_PATH . 'templates/woocommerce/single-product-reviews.php', false )` or equivalent include).
- **Do not** use theme-locatable `wc_get_template()` / `wc_locate_template()` for this file — theme paths win over `$default_path` and allow accidental takeover.
- **Do not** place a competing `blocksy-child/woocommerce/single-product-reviews.php`.

### 4.3 Upstream version and rebase policy

- File header must record: `Based on WooCommerce templates/single-product-reviews.php @version <exact DEV WC template version at implement time>` (known stock reference at freeze planning: **9.7.0**; confirm against live DEV WooCommerce at implementation).
- Surgical deltas only: remove `comments_open` early-return; gate form with UPR `NativePdpForm::should_render()` (via host thin wrapper if desired); keep native list / title / hooks (`woocommerce_before_single_product_reviews`, `woocommerce_product_review_list_args`, etc.).
- On WooCommerce upgrades: **diff** core template vs fork; re-apply only those deltas. No silent drift.
- Changelog entry required whenever the fork is rebased.

---

## 5. Native PDP display and submission security contract

**M2 guest submission remains exclusively on `/upr-review/form/`.** Native PDP must never become the guest invitation submit path.

### 5.1 Display helper — UPR `NativePdpForm::should_render()` (B3 amended)

**Owner:** UPR core (`v0.2.2+`).  
**Purpose:** decide whether native PDP `#review_form_wrapper` is rendered.  
**Not** an authorization oracle. **Not** a new authorization rule set.

Host storefront/template code must call:

```php
\UniversalProductReviews\Submission\NativePdpForm::should_render( $product_id );
```

or the host thin fail-closed wrapper `Biopentra_Upr_Host_Review_Availability_Ux::can_submit_for_product()` which **only** delegates to that API (no second rule engine).

Behaviour (owned by UPR): guests always false (including M2 form sessions); availability `can_submit=false` → false; logged-in allowed only when UPR availability permits.

### 5.2 Actor matrix

| Actor | `#reviews` list | Native PDP form | Direct native POST | M2 `/upr-review/form/` |
|-------|-----------------|-----------------|--------------------|-------------------------|
| Guest, no session | Visible (approved / empty state) | Absent; unavailable message | Rejected — UPR `GuestSubmissionGuard` 403 | N/A without invite |
| Guest, M2 session | Visible | **Absent** (display forces false) | Rejected unless UPR session **and** request-local `GuestSubmitAuthorization::arm()` (M2 handler only) | **Only** allowed guest submit path |
| Logged-in non-purchaser (verification required) | Visible | Absent | Rejected — **UPR `NativeSubmissionGuard`** when availability `can_submit=false` | N/A |
| Logged-in verified purchaser, product reviewable | Visible | Present | Allowed (UPR moderation hold still applies) | N/A |
| Catalog-hidden / discontinued (`product_not_reviewable`) | **Approved reviews remain visible** | Absent for **all** identities | Rejected — UPR `NativeSubmissionGuard` (logged-in) / UPR guest guard (guest); M2 submit already checks `ProductReviewability` | Must not accept for non-reviewable product |

### 5.3 Native submission enforcement (B3 amended — UPR core)

- **Owner:** UPR `NativeSubmissionGuard` (`preprocess_comment` @15) on UPR **`v0.2.2`**.
- When `upr_product_review_availability` reports `can_submit=false` → `wp_die` 403 for in-scope product reviews (all identities that reach the filter after guest guard).
- Host **must not** register a competing `preprocess_comment` submission guard.
- Must not weaken or bypass M2 armed guest submit (M2 does not use native PDP POST).

### 5.4 Host UX (B3 amended)

- Host **must not** set `comments_open` to false for availability/submission gating.
- Host owns branded unavailable messaging on `woocommerce_before_single_product_reviews` (including `product_not_reviewable` copy).
- Host owns UPR pin / preflight / DEV integration verification CLIs.
---

## 6. Repository ownership and branches

| Concern | Repository | Branch / status | Primary files |
|---------|------------|-----------------|---------------|
| UPR native enforcement + display helper | `universal-product-reviews` | **Shipped** `v0.2.2` @ `43c9989…` | `NativeSubmissionGuard`, `NativePdpForm` |
| Host pin, branded UX, DEV verification (no `comments_open` / no host `preprocess_comment`) | `biopentra-custom-plugins` (`biopentra-upr-host`) | **Merged B2** PR #11 → main `8a14ba1…` (host 0.1.3) | `class-review-availability-ux.php`, `class-upr-pin.php`, `verify-native-submit-dev` |
| Dedicated section + template fork | `biopentra-custom-plugins` (`biopentra-storefront`) | `fix/m3-pdp-reviews-section-storefront` (**B4**) | `modules/pdp-reviews-section/…`, `templates/woocommerce/single-product-reviews.php` |
| CSS / focus styles | `biopentra-blocksy-child` | `fix/m3-pdp-reviews-section-css` (**B4**) | `assets/pdp/reviews.css` (preserve contrast fix), `functions.php` version bump |
| Acceptance tests | `storefront-acceptance` | `test/m3-pdp-reviews-section` (**B4**) | `tests/upr-pdp-reviews-section.spec.ts`, extend `upr-pdp-mobile-a11y.spec.ts`, schema fixture path |
| Closure evidence | `biopentra-custom-plugins` | follow-up docs | `docs/upr-integration/m3-dev-pilot-revalidation.md` |

**Freeze baseline:** implementation branches must start from host baseline `18d0b3d` (or a later main that contains it) without rewriting that commit. B4 must start from a main that includes B2 (`8a14ba1…`) and pins UPR `v0.2.2`.

**Phase 1 PDP scope:** standard Blocksy product templates that fire `woocommerce_after_single_product_summary`. Elementor product documents are explicitly out of scope.

---

## 7. Acceptance matrix A1–A20 (frozen)

Fixture policy: `@example.invalid` only; controlled fixtures; primary Blocksy PDP; no customer PII.

| # | Requirement | Test type | Spec / command | Repo | Pass condition |
|---|-------------|-----------|----------------|------|----------------|
| A1 | `#reviews` exists on normal PDP | Playwright DOM | `upr-pdp-reviews-section.spec.ts` @ desktop-1440 + mobile-360 | storefront-acceptance | Exactly one `#reviews.woocommerce-Reviews` |
| A2 | Summary link reaches **and** visibly lands | Playwright behaviour | click `.bp-pdp-rating-summary a[href*="#reviews"]` | storefront-acceptance | **Both** required: (1) URL hash `#reviews`; (2) `#reviews` visible **and** in viewport. Hash-only or visibility-only = fail |
| A3 | Deep link `#reviews` | Playwright navigation | `goto …/#reviews` | storefront-acceptance | Same hash + visible/in-viewport as A2; no tab UI |
| A4 | Approved reviews for exact product | Playwright + fixture | fixture with 1 approved review | storefront-acceptance | `.commentlist > li.review` count = 1; matches fixture |
| A5 | Empty state | Playwright | zero-review product | storefront-acceptance | `#reviews` present; `.woocommerce-noreviews` visible |
| A6 | M2 guest form path | HTTP / existing UPR coverage | fixture invite → `/upr-review/form/` | UPR (no core change) | Form GET OK; native PDP has no guest form |
| A7a | Guest direct native POST rejected | WP-CLI / integration | guest `preprocess_comment` without arm | UPR (+ host verify CLI) | 403; no comment |
| A7b | Logged-in non-purchaser: no form + POST rejected | Playwright + WP-CLI | non-buyer fixture | UPR + host + acceptance | `#review_form_wrapper` absent; unavailable message; POST rejected; no comment |
| A7c | Logged-in verified purchaser: form only if reviewable | Playwright | verified buyer on reviewable product | acceptance | `#review_form_wrapper` present iff product reviewable |
| A7d | Catalog-hidden: no form for guest **or** logged-in (incl. verified) | Playwright + WP-CLI | hidden fixture with approved review | acceptance + UPR/host | List visible; form absent for both identities; POST rejected for both |
| A8 | Hidden: approved reviews visible, submit closed | WP6 + A7d | `verify-wp6-dev` + Playwright | host + acceptance | A7d + WP6 **8/8** |
| A9 | Mobile 360px | Playwright | `mobile-360` project | acceptance | A1–A5 pass |
| A10 | Desktop 1440px | Playwright | `desktop-1440` project | acceptance | A1–A5 pass |
| A11 | Keyboard + programmatic focus | Playwright a11y | Tab → Activate summary link; also `goto #reviews` | acceptance | Focus-visible on link; after nav `document.activeElement` is `#reviews` (or descendant); `#reviews` has `tabindex="-1"`. Scroll-without-focus = fail |
| A12 | Headings + axe on section | Playwright + axe | `#reviews` + summary | acceptance | `.woocommerce-Reviews-title` (h2); zero serious/critical |
| A13 | Summary link contrast | axe | `upr-pdp-mobile-a11y.spec.ts` | acceptance | Zero serious/critical |
| A14 | Sticky buy bar preserved | Playwright | `pdp-sticky.spec.ts` @ mobile-360 | acceptance | Existing sticky tests pass unchanged |
| A15 | Purchase panel regression | Playwright | `pdp-purchase-panel.spec.ts` @ desktop-1440 | acceptance | 4/4 |
| A16 | Exactly one Product schema entity | Playwright JSON-LD | `upr-product-schema.spec.ts` | acceptance | `products.length === 1` (Rank Math sole owner) |
| A17 | Schema review/rating parity | Playwright JSON-LD | same | acceptance | DOM approved count ↔ `aggregateRating`; no orphan reviews when count 0 |
| A18 | No global product-tab regression | Playwright DOM | same PDP | acceptance | No Description/Additional tab panels; `data-bp-reviews-section` present |
| A19 | WP6 catalogue-hidden lifecycle | WP-CLI | `wp biopentra-upr-host verify-wp6-dev` | host | 8/8 |
| A20 | UPR pin preflight | WP-CLI | `wp biopentra-upr-host verify-pilot-preflight` | host | PASS on UPR **v0.2.2** @ `43c9989…` |

### Targeted validation only

Do **not** run the full storefront-acceptance suite for this change. Use only:

```bash
# Host (DEV) — requires bind-mount to UPR v0.2.2 (operator step; not this amendment)
cd /opt/biopentra/apps/wordpress
docker compose run --rm wpcli wp biopentra-upr-host verify-wp6-dev
docker compose run --rm wpcli wp biopentra-upr-host verify-pilot-preflight
docker compose run --rm wpcli wp biopentra-upr-host verify-native-submit-dev

# Playwright (targeted)
bash tools/run-dev-playwright.sh --project=mobile-360 \
  tests/upr-pdp-reviews-section.spec.ts tests/upr-pdp-mobile-a11y.spec.ts tests/pdp-sticky.spec.ts
bash tools/run-dev-playwright.sh --project=desktop-1440 \
  tests/upr-pdp-reviews-section.spec.ts tests/upr-product-schema.spec.ts tests/pdp-purchase-panel.spec.ts
```

---

## 8. Implementation sequence

1. **~~Host availability / native-post boundary~~** — **Done via B1 (UPR `v0.2.2`) + B2 (host PR #11).** Former branch `fix/m3-pdp-reviews-availability-host` (PR #10) was **superseded** (not merged).
2. **Storefront reviews section / template (B4)** — branch `fix/m3-pdp-reviews-section-storefront`: module @ action priority 12; direct-load template fork; hash-focus behaviour; targeted A1–A5, A11, A18. **Blocked until UPR `v0.2.2` and merged B2** (both satisfied on docs main after B2 merge; DEV bind-mount still operator-controlled).
3. **Blocksy child CSS** — branch `fix/m3-pdp-reviews-section-css`: section spacing + `#reviews` focus styles; preserve rating-summary contrast fix; no sticky-bar changes; targeted A12–A13.
4. **Acceptance tests** — branch `test/m3-pdp-reviews-section`: implement A1–A18 matrix in Playwright / helpers.
5. **DEV validation and closure evidence** — run targeted matrix only; update `m3-dev-pilot-revalidation.md`; do not mark pilot accepted until A1–A20 pass.

---

## 9. Explicit non-goals

- No further UPR generic-core changes required for B4 display (remain on **`v0.2.2`** / `43c9989…` unless a new defect appears)
- No production deployment, configuration, database, release, or ZIP
- No re-enabling Blocksy Description / Additional Information tabs
- No sticky-buy-bar DOM, selectors, JavaScript, or CSS contract changes
- No reopening native **guest** comment submission on the PDP
- No duplicate review markup or secondary Product schema entities (Rank Math remains sole Product schema owner)
- No Elementor product-document support in Phase 1
- No full storefront-acceptance suite as part of this change
- No customer PII in fixtures, logs, or evidence
- No host `comments_open` availability gate; no host competing `preprocess_comment` submission guard

---

## 10. Rollback boundaries

| Layer | Rollback |
|-------|----------|
| Host (B2) | Revert host 0.1.3 pin/UX; do **not** restore host `comments_open` / host preprocess as security |
| Storefront section (B4) | Unregister module / remove action priority 12 hook; summary links become dead again; no tab chrome returns |
| Template fork | Delete plugin template; section stops rendering native list/form |
| Child CSS | Revert `reviews.css` / version bump |
| Acceptance | Revert specs |
| UPR | Pin back only with deliberate host pin change; security lives in `v0.2.2` |
| Production | Untouched — nothing to roll back from this freeze |

---

## 11. Freeze record

| Item | Value |
|------|--------|
| Document | `docs/upr-integration/m3-pdp-reviews-section.md` |
| Original freeze branch | `docs/m3-pdp-reviews-section-freeze` |
| Original annotated tag | `m3-pdp-reviews-section-freeze` (unchanged) |
| B3 ownership amendment | [`m3-pdp-reviews-section-b3-ownership-amendment.md`](m3-pdp-reviews-section-b3-ownership-amendment.md) |
| B3 amendment freeze tag | `m3-pdp-reviews-section-freeze-b3` |
| Host baseline preserved | `18d0b3d69391a087270d70d532e265406d07ed73` |
| UPR pin (amended) | `v0.2.2` / `43c9989291a4c7eab7f9fd57603c851486da287a` |

**Next step after B3:** B4 PDP section implementation against this document’s A1–A20 matrix (storefront template fork + CSS + acceptance), after controlled DEV UPR bind-mount to `v0.2.2` when replaying.