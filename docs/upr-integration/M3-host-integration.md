# M3 — Host integration of UPR v0.2.0 (Biopentra)

**Status:** Frozen authoritative host specification.  
**Freeze tag:** `upr-m3-host-integration-freeze` (annotated; points at this merge commit).  
**Generic core:** `magpern/universal-product-reviews` version `v0.2.0` @ `4eb1f965d5ab87d8d1e1479257fbb8721f25ce41` — **no host runtime code in UPR**.

**Production:** No production deployment from this document. DEV only until a separate production approval.

---

## 1. Locked decisions

| Decision | Locked value |
|----------|--------------|
| Delivery policy | Hybrid: prefer MPCF `delivered`; fall back to `shipped` |
| `upr_host_confirm_on_shipped` | `true` for initial DEV pilot; record source `delivered` or `shipped_fallback` |
| Support delay tags | `order_issue`, `review_delay` |
| Support suppress tags | `chargeback`, `compliance`, `safety` |
| Support lookup failure | `delay` (never `none` or permanent suppress on outage) |
| Card-rating feature flag | **Off** initially |
| Product-card ratings | Visible only when approved review count ≥ **3** |
| Discontinued | Catalogue-hidden **and** non-published (`draft` / `private` / `trash`); approved reviews remain visible; new submissions close; outstanding tokens/sessions unusable; future invitations stop |
| DEV environment | `WP_ENVIRONMENT_TYPE=development` mandatory |
| DEV invitation replay | Refuse unless `wp_get_environment_type() === 'development'`; prove `LoggingMailTransport` handled the controlled send |
| Product schema | Rank Math sole owner |
| Sticky buy bar | Locked — no DOM, selector, CSS-contract, or JavaScript changes |
| WooCommerce APIs | No `Internal\*` APIs |
| Production deploy | Not authorised by this freeze |

### WP6 completion gate (hard stop)

If WP6 identifies any missing UPR-core discontinued-product behaviour, M3 must not proceed to DEV pilot or host deployment until a separately planned, frozen, implemented, released UPR-core correction is available and the host is pinned to that corrected version.

Catalogue-hidden products are a **mandatory** discontinued-lifecycle test case (not optional).

---

## 2. Repository ownership

| Concern | Authoritative owner |
|---------|---------------------|
| This freeze + tag | `biopentra-custom-plugins` (`docs/upr-integration/`) |
| UPR contracts | `magpern/universal-product-reviews` |
| Fulfillment lifecycle event | `mp-commerce-fulfillment` (UPR-unaware) |
| Delivery / support / DEV mail adapters | `plugins/biopentra-upr-host/` |
| PDP purchase panel + rating summary | `plugins/biopentra-storefront/` |
| Reviews tab polish CSS | `biopentra-blocksy-child` |
| Card ratings (≥3) | `biopentra-loop-card` |
| Sticky buy bar | blocksy-child sticky module — **frozen** |
| Token-path log redaction | `biopentra-cache-infrastructure` / SWAG nginx |

---

## 3. Architecture summary

1. **MPCF** emits `mpcf_fulfillment_state_changed` only after a fully successful `WorkflowService::transition()` (engine OK → save → `record_events()` / audit → commit boundary → immediately before successful `TransitionOutcome`).
2. **`biopentra-upr-host`** listens, confirms UPR delivery (hybrid), implements support allowlists, and provides fail-closed DEV mail verification.
3. Storefront / child / loop-card own UI; Rank Math owns Product JSON-LD; nginx redacts invite tokens from all URI-bearing access/cache logs.

Payload contract for `mpcf_fulfillment_state_changed`:

```php
do_action(
        'mpcf_fulfillment_state_changed',
        array(
                'fulfillment_id' => (int) $fulfillment->id(),
                'order_id'       => (int) $fulfillment->order_id(),
                'from_state'     => (string) $previous_state,
                'to_state'       => (string) $fulfillment->state(),
                'occurred_at'    => (int) $unix_ts,
                'source'         => 'workflow',
        )
);
```

MPCF must remain unaware of UPR. Host listeners catch/log `Throwable`. MPCF guards `do_action` so listener exceptions cannot flip durable success into a failed transition outcome.

---

## 4. Work packages (implementation order)

| WP | Repo | Summary |
|----|------|---------|
| WP0 | `biopentra-custom-plugins` | This freeze + `m3-dev-replay.md` + tag |
| WP1 | `mp-commerce-fulfillment` | Lifecycle action + admin/API/CLI/recovery path matrix |
| WP2–4 | `biopentra-custom-plugins` | Host plugin: delivery, support, DEV mail |
| WP5 | cache / proxy infra | Token redaction (all formats) |
| WP6 | host + evidence | Discontinued verification vs UPR v0.2.0 (**catalogue-hidden mandatory**) |
| WP7 | storefront | PDP summary (WC APIs) + availability UX via named hooks |
| WP8 | `biopentra-loop-card` | Ratings ≥3, flag off by default |
| WP9 | host acceptance | Schema harness vs Rank Math |

---

## 5. Support matching rules

| Signal | Action |
|--------|--------|
| Allowlisted desk state + `wc_related_order` = this order + delay tag (`order_issue` / `review_delay`) | `delay` + `delay_until` (default 14 days) |
| Suppress tags only (`chargeback` / `compliance` / `safety`) | `suppress` |
| Lookup failure / timeout / dependency down | `delay` |
| No matching structured signal / normal contact | `none` |
| Refund / cancel | UPR core only |

Matching uses **only** `wc_related_order` + allowlisted tags/statuses. **No** ticket body or free-text inference.

---

## 6. Discontinued verification matrix (WP6)

Mandatory cases against installed UPR `v0.2.0`:

| Case | Must prove |
|------|------------|
| Catalogue-hidden but still `publish` | Exchange blocked; form/submit closed; tokens/sessions unusable; future sends prevented; approved reviews remain visible |
| `draft` | Same |
| `private` | Same |
| `trash` / deleted as applicable | Same |

If any required outcome is missing through UPR public mechanisms: **do not** reimplement in the host plugin; file a separate UPR-core correction; apply the WP6 completion gate above.

---

## 7. Explicit non-goals

- Host-specific code inside UPR core (except a separately scoped UPR correction if WP6 proves a gap).
- Inferring support intent from free text.
- Sticky buy-bar changes; theme template overrides when hooks + CSS suffice.
- Implementing `upr_product_rating_summary` in UPR.
- GitHub Release, ZIP, or production deployment from M3.

---

## 8. Production prerequisites (separate approval)

- WP6 discontinued gate passed (or host pinned to corrected UPR release).
- DEV pilot metrics reviewed (`delivered` vs `shipped_fallback`, delay/suppress, mail).
- Explicit production approval recorded outside this freeze.

**This freeze does not authorise production deploy.**
