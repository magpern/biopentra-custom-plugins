# Loop card hardening plan (standalone plugin)

**Plugin:** `biopentra-loop-card`  
**Current version:** **1.2.4** (`Version:` header and `BIOPENTRA_LOOP_CARD_VER` must stay aligned).  
**Scope:** Hardening and operations only — **not** a merge into `biopentra-storefront`.

---

## 1. Why loop-card stays standalone

After Phase 5 audit (`docs/loop-card-phase-5-audit.md`), **`biopentra-loop-card` remains a separate plugin** because:

- It depends on **Elementor Pro** (Loop Grid, loop document wrapper, shop page builder).
- It uses **high-impact global hooks** (`pre_get_posts` @9999, `woocommerce_get_price_html` @50) that are inappropriate to bundle with storefront chrome (megamenu, header auth, footer, CVSS).
- It includes **site-specific Elementor DB upgrades** (default template post **3608**) and a **CLI shop provisioner** (`includes/setup-shop-page-cli.php`) that are shop ops, not general storefront behaviour.
- **Independent release cadence** — shop grid, overlay ATC, and live search can ship without redeploying `biopentra-storefront` 0.4.x.

**Storefront 0.4.x** consolidates Phases 1–4 only. Loop-card is **out of scope** for that line.

---

## 2. Risk: `pre_get_posts` (priority 9999)

**Callback:** `biopentra_loop_card_force_singular_page_for_elementor_shop`

**Behaviour:** On the WooCommerce shop permalink, when the shop page is Elementor-built, rewrites the **main query** from a product archive to a **singular page** so Elementor `the_content()` runs.

**Risks:**

- Runs very late (**9999**); interacts with other plugins/themes that alter main query.
- Path matching on `REQUEST_URI` must stay aligned with permalinks (trailing slashes, subdirectory installs).
- Elementor preview (`?elementor-preview=`) uses a separate code path — regressions show as “shop shows archive” or “404 / wrong template”.
- **Mitigation:** Do not duplicate this hook in storefront; test shop URL after permalink or Elementor changes; avoid additional `pre_get_posts` on shop without coordination.

---

## 3. Risk: Elementor template upgrade on `init`

**Callback:** `biopentra_loop_card_maybe_upgrade_elementor_template` @ **30**

**Behaviour:** If options `biopentra_loop_card_tpl_v1` / `v2` are unset, rewrites `_elementor_data` on Elementor library post **3608** (filter `biopentra_loop_card_template_post_id`).

**Risks:**

- **Destructive** to manual template edits on first load after option delete.
- Wrong post ID on non-production sites corrupts unrelated templates.
- Clears Elementor CSS/cache for that template.

**Mitigation:**

- Document template ID in README; use filter on staging clones.
- Do **not** delete options in production unless intentionally re-running upgrade.
- Prefer explicit CLI/template export over relying on automatic `init` upgrades when rebuilding shops.
- Future hardening (optional): gate upgrades behind `WP_DEBUG` or a dedicated `wp option` enable flag.

---

## 4. Risk: `woocommerce_get_price_html` filter

**Callback:** `biopentra_loop_card_filter_loop_price_html` @ **50**

**Behaviour:** Replaces price HTML with “from X” logic when `$GLOBALS['biopentra_loop_card_price_scope']` is set around Elementor `woocommerce-product-price` widget (not on single product pages).

**Risks:**

- Global filter — if scope flag leaks (widget error, early exit), **non-loop** prices could show wrong “from” formatting.
- Priority **50** — other plugins may run before/after and conflict.

**Mitigation:** Verify single product, cart, and emails after changes; keep before/after widget hooks paired; staging check PDP price vs loop grid price.

---

## 5. Risk: `get_available_variations()` in loop cards

**Callback:** `biopentra_loop_card_build_payload` → `data-biopentra-product` on loop document wrapper.

**Risks:**

- **Performance:** Each visible card may call `get_available_variations()` (heavy for large variable catalogs).
- **Stale data:** Cached HTML / page cache may serve old variation stock unless cache varies by product or excludes loop HTML.
- **Threshold:** Products above WooCommerce AJAX variation threshold behave differently on PDP vs inline JSON in grid.

**Mitigation:** Load-test shop with full catalog; monitor TTFB on `/shop/`; after stock changes, purge page cache; test variable products with many variations.

---

## 6. Other bundled behaviours (awareness)

| Area | File | Note |
|------|------|------|
| Shop live search | `shop-live-search.js` | REST `wp/v2/search`; shop page only |
| Age Gate | `age-gate-confirm-fix.php` | Inline script on `age-gate` handle |
| Store notice | `store-notice.php` | WC demo store markup/CSS |
| CLI shop setup | `setup-shop-page-cli.php` | **Not** loaded at runtime — run only via `./wp eval-file` |

---

## 7. Version alignment (hardening)

- Plugin header **`Version:`** and **`BIOPENTRA_LOOP_CARD_VER`** must match (currently **1.2.4**).
- Rebuild ZIP after version bumps: `./scripts/build-zips.sh` → `builds/zips/biopentra-loop-card-1.2.4.zip`.

---

## 8. Staging test checklist

- [ ] Shop page renders Elementor layout (not raw WC archive only).
- [ ] Loop grid: image, title, price; “from X” on multi-price variables.
- [ ] Hover/tap overlay; variation pills; OOS styling.
- [ ] AJAX add to cart from grid; cart widget updates.
- [ ] Shop search: `?s=` filters grid; live suggestions (REST).
- [ ] Single product page: normal price (not loop “from” scope).
- [ ] Age Gate: yes/no submit if plugin active.
- [ ] Store notice: styled when WC demo store on.
- [ ] Network: `biopentra-loop-card` CSS/JS once; `ver=1.2.4`; no 404s.
- [ ] Console: no errors on shop interactions.
- [ ] Elementor editor: shop + loop template preview.

---

## 9. Rollback plan

1. **Deploy previous ZIP** (e.g. prior `biopentra-loop-card-{version}.zip` from artifact store).  
2. **Reactivate** plugin if deactivated.  
3. **DB:** Restore `_elementor_data` for shop page or template **3608** only if template upgrade or CLI setup caused breakage.  
4. **Options:** `biopentra_loop_card_tpl_v1` / `v2` control auto-upgrade — document before deleting.  
5. **Caches:** Flush page cache, Elementor CSS, CDN for `loop-card` assets.

---

## 10. Relationship to storefront

| Item | Policy |
|------|--------|
| `biopentra-storefront` 0.4.x | Does **not** include loop-card |
| Phase 5 merge | **Not planned** for 0.4.x line |
| Future merge | Revisit only after audit matrix + gating `init` template upgrades |

See also: `docs/loop-card-phase-5-audit.md`, `docs/storefront-migration-checklist.md` (Phase 5).
