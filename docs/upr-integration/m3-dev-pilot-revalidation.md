# M3 DEV pilot revalidation — closure addendum

**Verdict:** **PASS — DEV PILOT ACCEPTED**  
**Date:** 2026-08-26  
**Site:** `https://dev.biopentra.eu` (DEV VPS `169.58.7.116` / `vmi3436559`)  
**Production:** Untouched — no production host, database, bind mount, or configuration change.

---

## UPR pin (generic core)

| Item | Value |
|------|--------|
| Annotated tag | `v0.2.1` |
| Required commit | `e5b9636a42db7aaf0837c7b6034a24b062fd4275` |
| Bind-mount checkout | `/opt/biopentra/dev/universal-product-reviews` @ `e5b9636` (`git describe --tags --exact-match` → `v0.2.1`) |
| UPR closure | [`universal-product-reviews/docs/roadmap/m3-catalog-hidden-correction-closure.md`](https://github.com/magpern/universal-product-reviews/blob/main/docs/roadmap/m3-catalog-hidden-correction-closure.md) |

Prior M3 closure (`m3-closure.md`) recorded **PASS WITH BLOCKED PILOT** on UPR v0.2.0. This addendum closes the pilot block after v0.2.1 pin and DEV revalidation.

---

## Host pin PR

| Item | Reference |
|------|-----------|
| Branch | `fix/m3-upr-v0.2.1-pilot-pin` |
| PR | [#7](https://github.com/magpern/biopentra-custom-plugins/pull/7) |
| Merge commit | `1cd7c9071eff378ce974f2f18cf7fe140ef68d47` |
| Pin doc | [`m3-dev-upr-v0.2.1-pin.md`](m3-dev-upr-v0.2.1-pin.md) |
| Host plugin | `biopentra-upr-host` **0.1.1** |

**Pin mechanism (unchanged):** Docker bind-mount in `/opt/biopentra/apps/wordpress/compose.yml` — no second dependency mechanism added.

**Fail-closed preflight CLIs:**

```bash
cd /opt/biopentra/apps/wordpress
docker compose run --rm wpcli wp biopentra-upr-host verify-pilot-preflight
docker compose run --rm wpcli wp biopentra-upr-host verify-dev-mail
docker compose run --rm wpcli wp biopentra-upr-host verify-wp6-dev
```

---

## DEV replay evidence

### Starting state (pre-replay)

| Component | Before |
|-----------|--------|
| UPR | `0.2.1` checkout @ `1b0d969` (4 commits ahead of tag) |
| Host adapter | `0.1.0` @ `c5834ce` |

### Replay actions

1. Checked out UPR bind-mount to **`e5b9636…`** (exact annotated `v0.2.1` target).
2. Deployed host pin **`1cd7c90`** via existing bind-mount (no container rebuild).
3. Confirmed `WP_ENVIRONMENT_TYPE=development` (web + WP-CLI via compose `WORDPRESS_CONFIG_EXTRA`).

### Post-replay verification

| Check | Result |
|-------|--------|
| `verify-pilot-preflight` | **PASS** — `UPR=0.2.1 @ e5b9636…`, `host=0.1.1`, env=`development` |
| UPR active | `universal-product-reviews` **0.2.1** |
| Host active | `biopentra-upr-host` **0.1.1** |
| MPCF hook | `mpcf_fulfillment_state_changed` listener registered |
| Mail safety | **PASS** — `verify-dev-mail` → `LoggingMailTransport`, recorded=1 |
| AS / cron | `upr` pending actions present; `scripts/wp-cron.sh` executed successfully |

---

## WP6 catalogue-hidden lifecycle (mandatory)

**Command:** `wp biopentra-upr-host verify-wp6-dev`  
**Result:** **8/8 PASS** (isolated fixtures `upr-pilot-wp6-*`, cleaned up after run)

| # | Case | Result |
|---|------|--------|
| 1 | Published + catalog-hidden non-reviewable | PASS |
| 2 | Hidden → no active invitation | PASS |
| 3 | Visible→hidden suppresses token/session/form | PASS |
| 4 | Interleaved hide+submit — no surviving review | PASS |
| 5 | Approved reviews remain after hide | PASS |
| 6 | Restore visibility does not resurrect state | PASS |
| 7 | Draft still non-reviewable | PASS |
| 8 | Visible product remains reviewable | PASS |

Fixtures used test recipient **`pilot-wp6@example.invalid`** only. No customer emails sent.

---

## Delivery and support

### Hybrid delivery (DEV eval fixtures)

| Case | Evidence | Result |
|------|----------|--------|
| `delivered` | `_biopentra_upr_host_delivery_source` = `delivered` | PASS |
| `shipped` + flag **off** | No confirmation meta | PASS |
| `shipped` + flag **on** | Source = `shipped_fallback` | PASS |
| Later `delivered` after shipped fallback | Source upgraded to `delivered`; no duplicate invite rows | PASS |
| Cancellation | Confirmed meta cleared via `woocommerce_order_status_cancelled` | PASS |

### Support adapter

| Check | Result |
|-------|--------|
| Allowlists configured | delay: `order_issue`, `review_delay`; suppress: `chargeback`, `compliance`, `safety` | PASS |
| No structured ticket | `action: none` (not send/suppress) | PASS |
| Free-text inspection | Not implemented in adapter (by design) | PASS |

---

## Storefront, accessibility, schema

| Suite | Command | Result |
|-------|---------|--------|
| Sticky buy bar (D2B) | `storefront-acceptance/tools/run-dev.sh --d2b-only` | **4 passed**, 28 skipped (viewport filters) |
| PDP purchase panel | Playwright `pdp-purchase-panel.spec.ts` @ desktop-1440 | **4 passed** |
| Product JSON-LD | Playwright `upr-product-schema.spec.ts` @ desktop-1440 | **1 passed** — single Product entity; aggregateRating parity |

**Card ratings:** `enable_card_ratings=false`, `card_ratings_min_count=3` (default off).  
**PDP summary:** enabled; native WooCommerce review markup intact per Playwright panel tests.

---

## Token redaction

| Path | Access log |
|------|------------|
| `/upr-review/{token}/` | `[redacted]` — e.g. `HEAD /upr-review/[redacted]/ HTTP/2.0` |
| `/upr-review/form/` | Readable (not redacted) — e.g. `HEAD /upr-review/form/ HTTP/2.0` |

Config: `/opt/biopentra/proxy/config/nginx/site-confs/biopentra-log-redaction.conf`

---

## Explicit non-actions

- No production deployment, configuration, or database change
- No GitHub Release or ZIP for UPR or host plugins
- No real customer email (LoggingMailTransport only; fixture addresses `@example.invalid`)
- No generic-core UPR feature work beyond v0.2.1 pin
- No sticky buy-bar DOM/selector/JS changes

---

## Rollback

1. UPR bind-mount: `git checkout v0.2.0` (or `4eb1f96…`) in `/opt/biopentra/dev/universal-product-reviews`
2. Host adapter: revert to `c5834ce` (pre-pin M3 closure baseline) or disable pin CLIs via checkout
3. `verify-pilot-preflight` will **fail closed** until intentionally re-pinned

---

## Status

**M3 DEV pilot accepted.** Catalogue-hidden lifecycle passes on UPR **v0.2.1** through the actual host integration.

**Production rollout requires separate approval.** Do not promote bind-mount checkout or host pin to production without an approved limited production rollout plan.
