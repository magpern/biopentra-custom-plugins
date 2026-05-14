# Combined production cutover — Storefront Phases 1 to 3

This plan covers a **single coordinated production cutover** where **`biopentra-storefront`** replaces the standalone plugins for the Information mega-menu, footer contact email, and the custom variation stock selector (CVSS). **Phase 4** (header auth consolidation) is **out of scope** — do **not** migrate or deactivate `biopentra-header-auth` as part of this window.

**Related references:** `docs/storefront-migration-checklist.md`, `docs/phase-1-2-production-cutover.md`, `docs/phase-3-variation-stock-selector-test-results.md`, `docs/elementor-information-mega-js-cleanup.md`, `docs/information-mega-js-cutover-plan.md`.

---

## 1. Scope

After cutover, **`biopentra-storefront`** is the canonical source for:

| Legacy plugin | Storefront responsibility |
|---------------|---------------------------|
| **`biopentra-information-megamenu`** | Information mega-menu CSS + JS (`assets/information-megamenu/`) |
| **`biopentra-footer-contact`** | Footer email shortcode + assets (`[biopentra_footer_email]`, `assets/footer-contact/`) |
| **`custom-variation-stock-selector`** | Variation auto-select (bridge + inline script on `woocommerce_before_variations_form`; HPOS compatibility declaration) |

**Explicit exclusions for this cutover**

- **`biopentra-header-auth`** — unchanged; no install/deactivate/reconfigure as part of Phases 1–3.
- **Legacy plugin directories** — remain on disk after deactivation; **do not delete** folders until production has been stable for an agreed soak period and backups are verified.

---

## 2. Preconditions

Complete **before** changing production plugin state or Elementor post meta.

| Check | Notes |
|-------|--------|
| **ZIP built** | Run `./scripts/build-zips.sh` from the repo root. Confirm **`builds/zips/biopentra-storefront-0.3.0.zip`** exists and contains the expected `biopentra-storefront/` tree (modules, `assets/information-megamenu/`, `assets/footer-contact/`, `assets/variation-stock-selector/cvss-bridge.js`, main plugin file). |
| **DB backup** | Full database dump (or host snapshot) **immediately before** any Elementor `_elementor_data` / cache cleanup. Store off-site with retention. |
| **Plugin folder backup** | Tar or ZIP of the production `wp-content/plugins/` slice: at minimum current **`biopentra-storefront`** (if present), **`biopentra-information-megamenu`**, **`biopentra-footer-contact`**, **`custom-variation-stock-selector`**. |
| **Product-page QA reviewed** | Staging (or equivalent) sign-off on variable products: auto-select rules, URL `attribute_*` behaviour, out-of-stock handling, add-to-cart. See **`docs/phase-3-variation-stock-selector-test-results.md`**. |
| **Coming Soon mode understood** | If WooCommerce **Coming soon** is enabled, product pages may render the coming-soon template instead of the variable add-to-cart form, so **CVSS assets will not enqueue** and variation behaviour cannot be verified on those URLs. Plan verification for **store launched** or **Coming soon disabled** for the product checks you care about. |
| **Rollback plugins available** | Prior known-good ZIPs or copies of all four plugin folders above, plus the **pre-cutover DB dump**, so rollback does not depend on rebuilding from memory. |
| **Maintenance window (recommended)** | Short overlap where legacy + storefront are both active can cause duplicate megamenu **CSS** or shortcode registration quirks; keep overlap **minimal** and follow the order in section 3. |

---

## 3. Production order

**Principle:** `biopentra-storefront` must be **installed and active** before you remove the Elementor-injected legacy megamenu script, and before you deactivate **`biopentra-information-megamenu`**, **`biopentra-footer-contact`**, or **`custom-variation-stock-selector`**, so visitors never lose mega-menu JS, footer shortcode handling, or variation auto-select.

1. **Upload and install** `builds/zips/biopentra-storefront-0.3.0.zip` (WordPress **Plugins → Add New → Upload**, or your deployment pipeline). Confirm extracted files on disk under `wp-content/plugins/biopentra-storefront/`.

2. **Activate `biopentra-storefront`**. Check `debug.log` for PHP fatals. Optionally spot-check Network for megamenu and footer assets from storefront URLs.

3. **Run Elementor megamenu JS cleanup if needed**  
   If production still embeds a raw `<script src="…/biopentra-information-megamenu/.../information-mega.js">` in Elementor data, remove or replace it **after** the DB backup, following **`docs/elementor-information-mega-js-cleanup.md`** (or the cutover plan in **`docs/information-mega-js-cutover-plan.md`** / repo helper scripts if your runbook uses them). Regenerate or clear Elementor CSS/file cache if your process requires it.

4. **Clear caches and CDN**  
   Full page cache, object cache (if safe to flush), opcode cache (if applicable), Elementor caches, and CDN purge for HTML and plugin static assets (include paths for `information-mega`, `footer-contact`, and `variation-stock-selector` if your CDN supports path or tag purge).

5. **Deactivate legacy plugins (order flexible; all must end inactive)**  
   - `biopentra-information-megamenu`  
   - `biopentra-footer-contact`  
   - `custom-variation-stock-selector`  

   **Do not** leave **`custom-variation-stock-selector`** and storefront CVSS both **active**; the storefront module intentionally skips when the legacy plugin is active, which is useful for debugging but is **not** a steady-state configuration.

6. **Verify frontend** (see section 4).

---

## 4. Verification

Run logged-out and (where relevant) logged-in checks on production URLs after cache propagation.

| Area | What to confirm |
|------|------------------|
| **Menus** | Information mega-menu opens and navigates on desktop and mobile; **one** megamenu CSS and **one** megamenu JS from `biopentra-storefront/.../information-megamenu/`. |
| **Footer email** | `[biopentra_footer_email]` renders wherever used (footer template, shortcodes); footer contact JS/CSS load from storefront paths as designed. |
| **Variation auto-select** | On a **normal** variable product template (not blocked by Coming soon or a layout that omits the variations form), default visit selects the **highest-priced in-stock purchasable** variation when no `attribute_*` query params apply; URL with `attribute_*` is not overridden; out-of-stock variations are not chosen. |
| **Add to cart** | Selected variation adds to cart successfully; no duplicate line items from a **single** intentional add (full browser pass recommended). |
| **Scripts** | No duplicate execution of megamenu or CVSS logic beyond acceptable WordPress enqueue patterns; **no** legacy requests to `biopentra-information-megamenu/.../information-mega.js` after cleanup. |
| **Console** | No new uncaught errors tied to storefront megamenu, footer contact, or variation scripts. |
| **404s** | No failed requests for storefront asset URLs (including `cvss-bridge.js`). |
| **HPOS** | WooCommerce **HPOS** compatibility: no compatibility warning for **`biopentra-storefront`** (storefront declares `custom_order_tables` compatibility on `before_woocommerce_init`). |

---

## 5. Rollback

If production behaviour is unacceptable after cutover:

1. **Reactivate** (in any order, but verify each surface):  
   - `biopentra-information-megamenu`  
   - `biopentra-footer-contact`  
   - `custom-variation-stock-selector`  

2. **Deactivate `biopentra-storefront`**.

3. **Restore the DB backup** only if Elementor meta was changed during megamenu cleanup and must be reverted; shortcode strings in normal post content are unchanged by Phases 1–3, but `_elementor_data` may have been edited.

4. **Clear caches and CDN** again so clients pick up legacy plugin URLs and HTML.

**Partial rollback** (only one legacy plugin reactivated) can leave inconsistent behaviour (for example, megamenu back on legacy but footer still on storefront). Prefer the **full** rollback pair above unless you are debugging a single subsystem.

---

## 6. Warnings

1. **Do not delete** legacy plugin folders on production until **multi-day stability**, backups are verified, Elementor no longer references legacy megamenu script URLs, and stakeholders agree on removal.

2. **Coming Soon mode** can prevent the variable product template (and thus **CVSS**) from loading; treat Coming soon as a first-class risk in QA and go-live checklists.

3. **Full browser add-to-cart and console checks** should be performed **before** cutover on staging and **after** cutover on production; CLI-only checks are insufficient for timing and theme interaction issues.

4. **Do not start Phase 4** until Phases 1–3 are stable in production and rollback artifacts are no longer needed for day-to-day operations.

5. **Header auth** is intentionally untouched by this document; any future Phase 4 plan is separate.

---

## Build command

From repository root:

```bash
./scripts/build-zips.sh
```

Confirm output includes **`biopentra-storefront-0.3.0.zip`** (version follows the `Version:` header in `plugins/biopentra-storefront/biopentra-storefront.php`).

---

## Final production cutover order (summary)

**Build ZIP → DB backup → plugin folder backup → upload/install `biopentra-storefront-0.3.0.zip` → activate `biopentra-storefront` → Elementor megamenu legacy JS cleanup (if needed, after DB backup) → purge caches/CDN → deactivate `biopentra-information-megamenu`, `biopentra-footer-contact`, and `custom-variation-stock-selector` → verify menus, footer email, variation auto-select, add-to-cart, scripts, console, 404s, HPOS → monitor.**
