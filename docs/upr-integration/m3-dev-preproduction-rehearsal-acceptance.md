# M3 DEV pre-production invitation-email rehearsal — closure

**Verdict:** **PASS — DEV PRE-PRODUCTION REHEARSAL ACCEPTED**  
**Date:** 2026-08-27  
**Site:** `https://dev.biopentra.eu` (hostname `vmi3436559`)  
**Authority:** freeze tag `m3-dev-preproduction-rehearsal-freeze` → `6d71d607c7d31fe7654b2b76fc5b019533854673`  
**Specification:** [`m3-dev-preproduction-rehearsal.md`](m3-dev-preproduction-rehearsal.md)

**Production:** Untouched — no production host, database, WordPress, proxy/cache, Cloudflare, DNS, real mail transport, Release, ZIP, or customer data access.

---

## Pins exercised

| Component | Value |
|-----------|--------|
| UPR | `0.3.0` / annotated `v0.3.0` / `b2abc2defc30fc023601593aa1720cbfdd0a4f3c` |
| Host | `biopentra-upr-host` **0.1.4** @ custom-plugins including `13c9b4a79aa0cc980b2dc500565c4c2b56a89e4d` |
| Storefront | `0.9.39` (unchanged) |
| Env (CLI + wordpress container) | `development` |
| Mail transport | `UniversalProductReviews\Email\LoggingMailTransport` |
| Tabs theme_mod | `woo_has_product_tabs=no` |

---

## Preflight

- `verify-pilot-preflight` → PASS (`UPR=0.3.0 @ b2abc2d…`, `host=0.1.4`)
- `verify-dev-mail` → PASS (`LoggingMailTransport`)
- Public listeners: `2222` / `80` / `443` only
- Baseline cancelled stale pending UPR AS send/reconcile jobs (44 → 0) before controlled enable
- Invitation emails were **disabled**; pilot allowlist empty; pause off

---

## Proof matrix

| Gate | Result |
|------|--------|
| R1 master disabled blocks schedule/send (`email_disabled`, no sent-state) | **PASS** |
| R2 emergency pause blocks send, revokes tokens/sessions, unpause no retro-send | **PASS** |
| R3 host allowlist denies empty/other order IDs (`not_authorised`) | **PASS** |
| R4 exactly one allowlisted fixture order completed LoggingMailTransport path (`@example.invalid`) | **PASS** |
| R4b non-allowlisted peer did not send | **PASS** |
| R5 host cannot upgrade `email_disabled` / `paused` / `not_authorised` / `outside_scheduling_boundary` | **PASS** |
| R6 LoggingMailTransport only | **PASS** |
| R7 no historical backfill after enable (reconcile `authorisation_denied_skipped=32`) | **PASS** |
| R8 AS pending send probe drained to 0 | **PASS** |
| R9 token path logged as `/upr-review/[redacted]/`; form path readable; raw synthetic token absent from access.log | **PASS** |
| R10 desktop reviews (8), mobile a11y (5), sticky (3), schema (1) | **PASS** |
| R10 `verify-wp6-dev` (8/8) | **PASS** |
| R10 `verify-pilot-preflight` / `verify-dev-mail` / `verify-token-redaction-dev` | **PASS** |

Fixture recipients used **`@example.invalid` only**. Evidence records order IDs / decision codes only — no customer PII.

---

## Safe DEV restore (post-rehearsal)

| Control | Final value |
|---------|-------------|
| `upr_invitation_emails_enabled` | **no** |
| Emergency pause | **no** |
| Pilot authorised | **no** |
| Pilot allowlist | **empty** |
| UPR AS pending/in-progress | **0** (after cancel) |
| Mail transport | `LoggingMailTransport` |
| UPR pin | still `v0.3.0` @ `b2abc2d…` |

Rollback path used matches the freeze §5 (disable master, clear allowlist, cancel UPR jobs). Emergency pause was exercised during R2 and left **off** afterward.

---

## Explicit non-claims

- Does **not** authorise production rollout.
- Does **not** create GitHub Release / ZIP / new version tags.
- Google rich-result eligibility is not claimed.
- Rehearsal temporary PHP helpers under host `scripts/rehearsal-tmp/` were removed and not committed.

---

## Next step

Production rollout remains a **separate approval** gated by the blocked production plan (atomic deploy, live inventory, recorded operator approvals). This closure only accepts the **DEV** pre-production invitation-email rehearsal.
