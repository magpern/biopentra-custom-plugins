# M3 DEV pilot revalidation — closure addendum

**Verdict:** **PASS — DEV PILOT ACCEPTED**  
**Date:** 2026-08-27  
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

Prior M3 closure (`m3-closure.md`) recorded **PASS WITH BLOCKED PILOT** on UPR v0.2.0. This addendum closes the pilot block after v0.2.1 pin and full DEV revalidation (including the four mandatory evidence gaps from the 2026-08-26 review).

---

## Host pin PR

| Item | Reference |
|------|-----------|
| Branch | `fix/m3-upr-v0.2.1-pilot-pin` |
| PR | [#7](https://github.com/magpern/biopentra-custom-plugins/pull/7) |
| Merge commit | `1cd7c9071eff378ce974f2f18cf7fe140ef68d47` |
| Pin doc | [`m3-dev-upr-v0.2.1-pin.md`](m3-dev-upr-v0.2.1-pin.md) |
| Host plugin | `biopentra-upr-host` **0.1.2** (supplement CLIs for support / AS drain / token redaction) |

**Pin mechanism (unchanged):** Docker bind-mount in `/opt/biopentra/apps/wordpress/compose.yml` — no second dependency mechanism added.

**Fail-closed preflight CLIs:**

```bash
cd /opt/biopentra/apps/wordpress
docker compose run --rm wpcli wp biopentra-upr-host verify-pilot-preflight
docker compose run --rm wpcli wp biopentra-upr-host verify-dev-mail
docker compose run --rm wpcli wp biopentra-upr-host verify-wp6-dev
docker compose run --rm wpcli wp biopentra-upr-host verify-support-dev
docker compose run --rm wpcli wp biopentra-upr-host verify-as-drain-dev
```

---

## DEV replay evidence

### Post-replay verification

| Check | Result |
|-------|--------|
| `verify-pilot-preflight` | **PASS** — `UPR=0.2.1 @ e5b9636…`, `host=0.1.2`, env=`development` |
| UPR active | `universal-product-reviews` **0.2.1** |
| Host active | `biopentra-upr-host` **0.1.2** |
| Mail safety | **PASS** — `verify-dev-mail` → `LoggingMailTransport` |

---

## WP6 catalogue-hidden lifecycle (mandatory)

**Command:** `wp biopentra-upr-host verify-wp6-dev`  
**Result:** **8/8 PASS** (isolated fixtures `upr-pilot-wp6-*`, cleaned up after run)

Fixtures used test recipient **`pilot-wp6@example.invalid`** only. No customer emails sent.

---

## Support adapter (mandatory revalidation)

**Command:** `wp biopentra-upr-host verify-support-dev`  
**Result:** **PASS**

| Case | Evidence | Result |
|------|----------|--------|
| Free-text only (message/description contain chargeback/compliance keywords) | `{"action":"none"}` — prose must not match allowlists | PASS |
| Allowlisted suppress tag (`reason_for_contact=chargeback`) | `{"action":"suppress","code":"support_chargeback"}` | PASS |
| Allowlisted delay tag without open ticket | `{"action":"none"}` (delay requires open ticket per adapter) | PASS |
| **Support lookup failure** (DEV filter `biopentra_upr_host_dev_force_support_lookup_failure`) | `{"action":"delay","code":"support_lookup_failure","delay_until":…}` | PASS |

Free-text fields are **not** inspected by the adapter (by design); only structured Fluent Form fields drive invitation actions.

---

## Action Scheduler drain (mandatory revalidation)

**Command:** `wp biopentra-upr-host verify-as-drain-dev`  
**Result:** **PASS**

Controlled probe uses real UPR hook `upr_send_reminder_item` with unique args in group `upr`:

```
Pending upr_send_reminder_item (0) before → 1 after enqueue (action_id=14825)
Pending after drain: 0; completed rows for probe args: 1
After wp-cron.php spawn: pending probe jobs=0
Success: Action Scheduler drain proof passed
```

This proves a **controlled UPR job enqueued, executed to completion via `ActionScheduler_QueueRunner::run()`, and drained via `wp-cron.php` spawn** — not merely that host cron ran.

---

## Storefront, mobile, accessibility, schema

### Desktop (unchanged baseline)

| Suite | Command | Result |
|-------|---------|--------|
| PDP purchase panel | Playwright `pdp-purchase-panel.spec.ts` @ desktop-1440 | **4 passed** |
| Product JSON-LD | Playwright `upr-product-schema.spec.ts` @ desktop-1440 | **1 passed** |

### Mobile + accessibility (mandatory revalidation)

**Project:** `mobile-360` (Playwright)  
**Spec:** `storefront-acceptance/tests/upr-pdp-mobile-a11y.spec.ts` + `pdp-sticky.spec.ts`

| Test | Result |
|------|--------|
| Rating summary links to `#reviews` with accessible name | PASS |
| Axe (WCAG 2.x AA) on `.bp-pdp-rating-summary` — zero serious/critical | PASS |
| Sticky buy bar visible after scroll | PASS |
| D2B sticky suite (`run-dev.sh --d2b-only`) | **4 passed** @ mobile-360 |

**Note on native `#reviews` DOM:** Blocksy theme mod `woo_has_product_tabs=no` (site-wide DEV) removes WooCommerce product tabs from PDP HTML. Approved-review persistence is proven via **WP6 case 5** CLI, not live `#reviews` markup on catalog PDPs. Rating-summary module + invitation UX are the live storefront surface under M3 scope.

**A11y fix deployed:** `blocksy-child` `reviews.css` link contrast (`#174a87`) for rating-summary anchors; version **1.2.13**. Playwright sends `Cache-Control: no-cache` to avoid stale SWAG-cached HTML during acceptance.

---

## Token redaction (mandatory revalidation)

Canonical config: `biopentra-cache-infrastructure/nginx/common/biopentra-log-redaction.conf` → live DEV `proxy/config/nginx/site-confs/biopentra-log-redaction.conf`.

### Log formats verified (DEV SWAG volume)

| Format | File | `$bp_log_request_uri` | Probe result |
|--------|------|----------------------|--------------|
| `bp_combined` | `/opt/biopentra/proxy/config/log/nginx/access.log` | yes | Token path → `[redacted]`; form path readable |
| `bp_cache_dev` | `/opt/biopentra/proxy/config/log/nginx/bp-cache.log` | yes | Token path → `[redacted]`; form path readable |
| Stock `combined` | not used (replaced by `bp_combined` on HTTP+HTTPS server blocks) | n/a | n/a |

**Host probe (2026-08-27):** unique token `m3revald04c44f1203b558884197b90c587a8de`

- Raw token grep across `/opt/biopentra/proxy/config/log/nginx/*.log`: **0 matches**
- Recent access log: `HEAD /upr-review/[redacted]/ HTTP/2.0`
- Recent bp-cache log: `…/upr-review/[redacted]/ status=404 …`
- Form path: `HEAD /upr-review/form/ HTTP/2.0` (not redacted)

**Structural tests:** `python3 -m unittest tests.prod.test_upr_invite_log_redaction` in `biopentra-cache-infrastructure` — **5/5 OK** (all URI-bearing `log_format` definitions use `$bp_log_request_uri`; form path stays readable).

**WP-CLI helper:** `wp biopentra-upr-host verify-token-redaction-dev` (issues HTTP probes; warns if WP container cannot read host log paths — host grep above is authoritative on DEV).

### External logging sinks

**None configured on DEV.** No Cloudflare Logpush, Datadog, Elastic, Fluent Bit, Vector, or other forwarder in `biopentra-cache-infrastructure`, SWAG config, or WordPress `wc-logs` (no `upr-review` token paths found). Access logs are local files under the SWAG volume only. See [`biopentra-cache-infrastructure/docs/upr-invite-log-redaction.md`](https://github.com/magpern/biopentra-cache-infrastructure/blob/main/docs/upr-invite-log-redaction.md).

**Historical note:** `bp-cache.log` line from an earlier pre-redaction probe may still contain raw token `m3testtokenABCDEF1234567890`; all post-redaction probes show `[redacted]`.

---

## Explicit non-actions

- No production deployment, configuration, or database change
- No GitHub Release or ZIP for UPR or host plugins
- No real customer email (LoggingMailTransport only; fixture addresses `@example.invalid`)
- No sticky buy-bar DOM/selector/JS changes

---

## Rollback

1. UPR bind-mount: `git checkout v0.2.0` in `/opt/biopentra/dev/universal-product-reviews`
2. Host adapter: revert host plugin to pre-0.1.2 baseline
3. `verify-pilot-preflight` will **fail closed** until intentionally re-pinned

---

## Status

**M3 DEV pilot accepted.** All four mandatory revalidation gaps closed:

1. Mobile + accessibility evidence (`mobile-360` Playwright + axe on UPR rating summary)
2. Support lookup failure → delay + free-text has no effect (`verify-support-dev`)
3. Action Scheduler controlled UPR job drain proof (`verify-as-drain-dev`)
4. Token redaction across `bp_combined` + `bp_cache_dev` + structural tests + documented absence of external sinks

**Production rollout requires separate approval.**
