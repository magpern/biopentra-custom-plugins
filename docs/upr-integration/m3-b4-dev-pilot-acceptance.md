# M3 B4 DEV pilot acceptance — closure addendum

**Verdict:** **PASS — DEV PILOT ACCEPTED**  
**Date:** 2026-08-27  
**Site:** `https://dev.biopentra.eu` (DEV VPS only)  
**Production:** Untouched — no production host, database, bind mount, compose, SWAG, release, tag, ZIP, or configuration change. Production rollout remains a **separate approval**.

---

## Scope

Closes the B4 dedicated PDP `#reviews` DEV pilot after correcting Blocksy product-tabs configuration drift (`woo_has_product_tabs`) and replaying the focused acceptance matrix.

Authoritative freeze: [`m3-pdp-reviews-section.md`](m3-pdp-reviews-section.md) (B3-amended).  
Tabs-off replay owner: [`dev-replay-blocksy-product-tabs.md`](dev-replay-blocksy-product-tabs.md).

---

## Configuration owner (tabs-off correction)

| Item | Value |
|------|--------|
| Repository | `magpern/biopentra-custom-plugins` |
| Branch | `fix/m3-b4-disable-product-tabs-dev` |
| Corrective PR | [#15](https://github.com/magpern/biopentra-custom-plugins/pull/15) |
| Merge SHA | `6ac006e87dbaf1ec8339d3ecb94f5ea98ee4e050` |
| Mechanism | `docs/upr-integration/bin/dev-replay-blocksy-product-tabs.sh` (`status` / `apply` / `verify` / `rollback`) |
| Storage | WordPress **theme_mod** on stylesheet `blocksy-child` (not `wp_options.woo_has_product_tabs`) |

### DEV before / after

| Check | Value |
|-------|--------|
| Before apply | `woo_has_product_tabs=<unset>` (Blocksy default **yes**) |
| After apply | `woo_has_product_tabs=no` |
| Verify | `docs/upr-integration/bin/dev-replay-blocksy-product-tabs.sh verify` → `VERIFY_OK` |
| WP-CLI | `/opt/biopentra/scripts/dev-wp wp theme mod get woo_has_product_tabs` → `no` |
| Note | `wp option get woo_has_product_tabs` correctly errors (theme_mod, not option) |

### Rollback

```bash
docs/upr-integration/bin/dev-replay-blocksy-product-tabs.sh rollback
# restores recorded previous value (<unset> for this pilot)
```

State file (gitignored): `docs/upr-integration/.dev-replay-state/woo_has_product_tabs.prev`

---

## Pins used for this pilot

| Component | Ref |
|-----------|-----|
| UPR core | `v0.2.2` @ `43c9989291a4c7eab7f9fd57603c851486da287a` |
| Host / storefront | `biopentra-custom-plugins` @ `6ac006e…` (includes PR #15); storefront **0.9.39**; host **0.1.3** |
| Blocksy child | `ee88189bab40dcff68fbf107276735f2af4a75e1` (**1.2.13**) |
| Acceptance | `storefront-acceptance` @ `2b2ace4833c821393cee18ef08eb54084e8b0542` |
| Env | `development` |

Bind-mount HEADs before pilot recorded under `/tmp/b4-dev-pilot-before/` and `/tmp/b4-ROLLBACK.md` (restore only if a later operator needs pre-pilot trees).

---

## DEV replay evidence (DOM)

After tabs-off apply + cache flush, standard PDP `/product/tirzepatide/` (coming-soon share cookie):

| Assertion | Result |
|-----------|--------|
| Exactly one `id="reviews"` | **1** |
| Inside `[data-bp-reviews-section="1"]` | **yes** |
| No Description / Additional / Reviews tab panels | **0** each |
| No `woocommerce-tabs wc-tabs` chrome | **0** |
| Sticky buy bar (`data-bp-sticky-bar`) present | **1** |

Label: `DOM_OK`.

---

## Targeted acceptance matrix

Runner: `cd /opt/biopentra/dev/storefront-acceptance && bash tools/run-dev-playwright.sh …`  
Results: `/tmp/b4-pilot-results2/` · aggregate **`PASS`**

| # | Command | Result |
|---|---------|--------|
| 1 | `--project=desktop-1440 tests/upr-pdp-reviews-section.spec.ts` | **8 passed** (incl. A1 single `#reviews`, A18 tabs absent) |
| 2 | `--project=mobile-360 tests/upr-pdp-reviews-section.spec.ts` | **2 passed** |
| 3 | `--project=desktop-1440 tests/upr-pdp-reviews-a7.spec.ts` | **4 passed** + fixture cleanup OK |
| 4 | `--project=mobile-360 tests/upr-pdp-mobile-a11y.spec.ts` | **5 passed** |
| 5 | `--project=mobile-360 tests/pdp-sticky.spec.ts` | **3 passed** |
| 6 | `--project=desktop-1440 tests/upr-product-schema.spec.ts` | **1 passed** |

### A7 / catalogue-hidden (from A7 matrix)

| Case | Result |
|------|--------|
| A7b logged-in non-purchaser — no form; native POST rejected | PASS |
| A7c verified purchaser — form; pending moderation | PASS |
| A7d catalogue-hidden — list visible; no form; POST rejected | PASS |
| Form eligibility independence / single `#reviews` | PASS |

Fixtures: `@example.invalid` only; teardown proved clean. Unrelated `screenshots/m9/*` dirt left untouched.

### Host CLIs

| Command | Result |
|---------|--------|
| `wp biopentra-upr-host verify-pilot-preflight` | **PASS** — `env=development`, `UPR=0.2.2 @ 43c9989…`, `host=0.1.3` |
| `wp biopentra-upr-host verify-wp6-dev` | **8/8 PASS** |

`verify-dev-mail` was not re-run for this addendum; mail-safety baseline remains the prior approved LoggingMailTransport DEV proof (unchanged by tabs-off).

---

## One-global-`#reviews` proof

- Desktop A1 + A18 passed after tabs-off correction.
- DOM scrape: single `#reviews.woocommerce-Reviews` inside dedicated section; no WC tab panels.
- Purchase-panel summary / focus helpers continue to target that sole anchor (A2, A11).
- Sticky-buy-bar selectors unchanged (sticky suite PASS).

---

## Explicit no-production confirmation

- No production WordPress, database, Redis, SWAG, Cloudflare, or bind-mount changes.
- No releases, tags, ZIPs, or production rollout.
- UPR generic core not altered.
- Corrective PR #15 and this closure are documentation / DEV-replay artifacts only (plus the already-merged storefront/acceptance baselines for B4).

---

## Rollback point

1. Tabs setting: `dev-replay-blocksy-product-tabs.sh rollback` → restores `<unset>` (Blocksy default yes).  
2. Working-tree HEADs: `/tmp/b4-ROLLBACK.md` (optional operator restore of pre-pilot checkouts).

---

## Verdict

**PASS — DEV PILOT ACCEPTED**

Production rollout of B4 remains a **separate approval** and is not authorised by this document.
