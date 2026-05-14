# Production cutover — `biopentra-storefront` **0.4.0**

This runbook covers a **single coordinated production cutover** where **`biopentra-storefront` 0.4.0** replaces four standalone plugins. **Do not modify production** from this document alone—execute only on the production host during an agreed window. **Do not start new migrations** beyond this scope. **Do not delete** legacy plugin directories; keep them on disk for rollback until a multi-day soak completes.

**Related:** `docs/phase-1-3-production-cutover.md`, `docs/header-auth-migration-notes.md`, `docs/phase-4-header-auth-test-results.md`, `docs/staging-test-phase-4-header-auth.md`, `docs/elementor-information-mega-js-cleanup.md`, `docs/storefront-migration-checklist.md`.

---

## 1. Scope

After cutover, **`biopentra-storefront` 0.4.0** is the canonical source for:

| Legacy plugin | Storefront responsibility |
|---------------|---------------------------|
| **`biopentra-information-megamenu`** | Information mega-menu CSS + JS |
| **`biopentra-footer-contact`** | `[biopentra_footer_email]` shortcode + footer contact assets |
| **`custom-variation-stock-selector`** | Variation auto-select (CVSS bridge + inline script, HPOS declare) |
| **`biopentra-header-auth`** | Header auth shortcode + Elementor widget, WC account/checkout + cart surfaces, Blocksy `ct-ajax-add-to-cart` filter, optional Blocksy palette admin hook |

**Out of scope:** `biopentra-loop-card`, `biopentra-contact-inbox`, `wc-inventory-overview` — unchanged.

---

## 2. Required ZIPs / files

| Artifact | Purpose |
|----------|---------|
| **`builds/zips/biopentra-storefront-0.4.0.zip`** | Built from repo via `./scripts/build-zips.sh`; contains Phases 1–4 modules including `modules/header-auth/`. |
| **Guarded legacy `biopentra-header-auth/biopentra-header-auth.php`** | Repo copy includes an early `return` when `function_exists( 'biopentra_header_auth_register_assets' )` so **same-request** activation or load ordering does not **fatal** if storefront already defined those globals. **Upload this file over production’s legacy plugin root** before or with storefront deploy so rollback and overlap scenarios stay safe. |

No other standalone ZIPs are required for this cutover if you deploy only the two items above plus existing legacy folders (left disabled).

---

## 3. Preconditions

| Check | Notes |
|-------|--------|
| **DB backup** | Full dump (or host snapshot) **before** plugin file changes and **before** any Elementor `_elementor_data` cleanup. |
| **Plugin folder backup** | Tar/ZIP of `wp-content/plugins/` slice: `biopentra-storefront`, all four legacy plugins, plus any prior storefront ZIP you rely on. |
| **Current active plugin list saved** | `./wp plugin list --status=active` (or screenshot / export) for audit. |
| **Storefront ZIP verified** | Unzip to a temp dir: confirm `biopentra-storefront/modules/header-auth/` and other module paths exist; `Version: 0.4.0` in main plugin header. |
| **Guarded legacy `biopentra-header-auth.php` ready** | Same guard as in repo (see §2); deploy **before** toggling plugins if production still runs an older copy without the guard. |
| **Low-traffic window** | Short overlap of duplicate megamenu **CSS** or confused caches is possible; communicate if needed. |
| **Staging sign-off** | Phase 4 browser QA was **conditional** in `docs/phase-4-header-auth-test-results.md`; complete human checks on staging before production if gaps matter to you. |

---

## 4. Production cutover order

**Principle:** `biopentra-storefront` must be **installed and active** before you remove Elementor’s legacy megamenu script tag and **before** deactivating the four legacy plugins, so visitors never lose mega-menu JS, footer shortcode, CVSS, or header-auth behaviour.

1. **Backup** database and plugin folders (see §3).  
2. **Upload / extract** `biopentra-storefront-0.4.0.zip` into `wp-content/plugins/biopentra-storefront/` (overwrite existing tree if upgrading from 0.3.x).  
3. **Upload / overwrite** `biopentra-header-auth/biopentra-header-auth.php` on the server with the **guarded** version from the repo (§2). Do **not** remove the rest of the legacy plugin folder.  
4. **Activate** (or re-activate) **`biopentra-storefront`**. Check `debug.log` for fatals.  
5. **Elementor megamenu JS cleanup** if production still embeds `…/biopentra-information-megamenu/.../information-mega.js` — follow **`docs/elementor-information-mega-js-cleanup.md`** (DB backup first).  
6. **Clear caches and CDN** (page cache, object cache if safe, Elementor/CSS caches, CDN purge for plugin static paths).  
7. **Deactivate**, in any order (all must end inactive for steady state):  
   - `biopentra-information-megamenu`  
   - `biopentra-footer-contact`  
   - `custom-variation-stock-selector`  
   - `biopentra-header-auth`  
8. **Clear caches and CDN** again.  
9. **Verify** using §5 (logged-out and logged-in passes, Network, HPOS notice).  
10. **Monitor** for several days before considering folder removal from **deploy** artifacts (optional; Git may retain history).

**Do not** leave **`custom-variation-stock-selector`** and storefront CVSS both **active**. **Do not** rely on legacy + storefront header-auth both **hooking** long-term; after cutover, legacy header-auth should stay **inactive** while storefront serves Phase 4.

---

## 5. Verification checklist

| # | Area | Pass criteria |
|---|------|----------------|
| 1 | Header auth (logged out) | “Log in” / account link correct; assets from `…/biopentra-storefront/modules/header-auth/…` |
| 2 | Header auth (logged in) | Trigger label + dropdown |
| 3 | Dropdown | Open/close, outside click, **Escape** closes |
| 4 | My Account | Loads; **Downloads** hidden from menu; styling OK |
| 5 | Checkout | Loads (watch **Coming soon** / redirects); styling OK |
| 6 | Cart | With **non-empty** cart: free-shipping progress block once (no duplicate bars) |
| 7 | Variations (CVSS) | Default selection, URL `attribute_*`, OOS rules per Phase 3 docs |
| 8 | Footer email | `[biopentra_footer_email]` and JS behaviour |
| 9 | Megamenu | Desktop + mobile; single storefront JS/CSS URLs after Elementor cleanup |
| 10 | Console | No new uncaught errors tied to storefront assets |
| 11 | Network | No **404** on storefront module assets; no stale legacy megamenu **JS** URL after cleanup |
| 12 | HPOS | WooCommerce shows **no** compatibility warning for **`biopentra-storefront`** (CVSS declares `custom_order_tables` on `before_woocommerce_init`) |

---

## 6. Rollback

1. **Reactivate** the four legacy plugins (files still on disk).  
2. **Deactivate `biopentra-storefront`** only if you must roll back **all** consolidated features; otherwise you may keep storefront for Phases 1–3 and only reactivate **`biopentra-header-auth`** if debugging Phase 4 in isolation (storefront header-auth module then no-ops when legacy is active—still avoid duplicate shortcode registration by keeping only one **active**).  
3. **Guarded `biopentra-header-auth.php`:** With the repo guard deployed, activating legacy after storefront has loaded the same global functions in edge requests should **not** fatally redeclare; confirm on staging first.  
4. **Restore DB backup** only if Elementor meta was changed during megamenu cleanup and must be undone.  
5. **Clear caches/CDN** after rollback.

---

## 7. Warnings

1. **Phase 4 browser QA** was **conditional**, not complete (`docs/phase-4-header-auth-test-results.md`)—plan a full logged-in / Elementor / checkout / cart pass on **staging** before production if risk tolerance is low.  
2. **Checkout** and **non-empty cart** free-shipping UI were **not** fully validated via CLI-only QA; validate in a browser with a real cart.  
3. **WooCommerce Coming soon** can hide normal product/checkout templates—understand mode before blaming cutover.  
4. **Do not delete** old plugin folders yet; monitor **several days** before optional deploy cleanup.  
5. **Do not start new migrations** in the same window.

---

## Build command

From repository root:

```bash
./scripts/build-zips.sh
```

Confirm **`builds/zips/biopentra-storefront-0.4.0.zip`** exists (output is gitignored until you copy artifacts elsewhere).

---

## Final cutover order (summary)

**Back up DB + plugins → deploy guarded `biopentra-header-auth.php` → deploy/extract `biopentra-storefront-0.4.0.zip` → activate storefront → Elementor megamenu legacy JS cleanup (if needed, after DB backup) → purge caches/CDN → deactivate the four legacy plugins → purge caches/CDN → run §5 checklist → monitor.**
