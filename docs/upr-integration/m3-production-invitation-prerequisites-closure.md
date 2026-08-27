# M3 production-invitation prerequisites — closure

**Verdict:** `PREREQUISITES IMPLEMENTED — PRODUCTION STILL NO-GO`  
**Date:** 2026-08-27  
**Authority:** [`m3-production-invitation-rollout.md`](m3-production-invitation-rollout.md) (freeze tag below)

This closure records repository prerequisites only. It does **not** authorise production fixture drills, token redaction probes, deployment, email enablement, allowlist population, or customer contact.

## 1. Documentation freeze

| Item | Value |
|------|--------|
| Tag | `m3-production-invitation-rollout-freeze` |
| Peel / merge | `c3904787747a4962e0e4acd7cd3cc57a91494e18` |
| PR | https://github.com/magpern/biopentra-custom-plugins/pull/22 |

## 2. Work packages

### WP-C — host 0.1.5 package pin

| Item | Value |
|------|--------|
| PR | https://github.com/magpern/biopentra-custom-plugins/pull/23 |
| Merge | `0efaae61862061d3b674a63a9f26e327cb205d63` |
| Annotated tag | `0.1.5` → same peel |

### WP-B — private packages

| Item | Value |
|------|--------|
| UPR PR | https://github.com/magpern/universal-product-reviews/pull/19 |
| UPR merge | `fa84dec9c52a2d26f600be7fa3fc023619a97e5a` |
| Host build PR | https://github.com/magpern/biopentra-custom-plugins/pull/24 |
| Host build merge | `7701eac1954fdf9e2f97075062bcde6a368caec1` |
| UPR ZIP SHA-256 | `e7f02bc5bc69c1c7cca2aa20d4dc834d08938515675d4e8658759e246b54d0fa` |
| UPR meta SHA-256 | `0af6348642c30648377204e7eb423b8364ecd7898870d282c8b329631be3adc2` |
| Host ZIP SHA-256 | `2fc1c5130a8fd9d4ad4e106949eab051fad1a39c38f2a71e975aacdeb45f9b8d` |
| Disposable validate | `scripts/validate-m3-pair-packages.sh` — PASS (pin accept + tamper deny; DEV WP untouched) |

No public GitHub Release / public ZIP. Private artifact workflows added (`workflow_dispatch` + `upload-artifact`).

### WP-A — pair transition

| Item | Value |
|------|--------|
| Branch / PR | `feat/m3-upr-coordinated-pair-transition` (this change set) |
| Scripts | `scripts/upr-pair-transition/*` |
| Disposable rehearsal | `rehearse-disposable.sh` — PASS (transition + rollback + suspend-fail abort) |

## 3. Production-ready target pair (packages only — not deployed)

| Component | Ref |
|-----------|-----|
| UPR | `v0.3.0` / `b2abc2defc30fc023601593aa1720cbfdd0a4f3c` |
| Host | `0.1.5` / `0efaae61862061d3b674a63a9f26e327cb205d63` |

## 4. Read-only production inventory

See [`m3-production-readonly-inventory.md`](m3-production-readonly-inventory.md).

Summary: production has **no** UPR/host installed; `DISABLE_WP_CRON=true`; compose service **`cron`** is the enforceable suspend target; `woo_has_product_tabs` missing; no token probe / fixtures / mail performed.

## 5. Worker suspension

**Concretely implementable:** `docker compose stop cron` / `start cron` at `/home/magpern/woocommerce`.  
Not yet production-rehearsed under an execute order → **P1 still open for live cutover**.

## 6. Unresolved P1–P8

| ID | Status |
|----|--------|
| P1 | Tooling + disposable rehearsal done; **production** suspend/resume not rehearsed; no release-dir layout on prod yet |
| P2 | Packages built + checksummed (private); not installed on prod |
| P3 | Approval ledger **template** only — live ledger location/operators not approved |
| P4 | Emergency-pause **synthetic production fixture** not executed (required later; checklist insufficient) |
| P5 | Token URI redaction probe on production **not** executed (forbidden in this initiative) |
| P6 | Operator execute order / dual-control for Phase A+ **not** issued |
| P7 | Pilot allowlist + final-send approvals **not** authorised |
| P8 | Customer contact / invitation email enablement **not** authorised |

## 7. Runtime change proof

| System | Changed by this initiative? |
|--------|------------------------------|
| DEV WordPress runtime / bind-mounts / options | **No** |
| Production WordPress / compose / options / files | **No** (read-only SSH inventory only) |
| Public GitHub Releases | **No** |

## 8. Explicit non-performance statement

Production fixture drill, HTTP token-redaction probe, deployment, email enablement, allowlist population, historical reconciliation backfill, and customer contact were **not** performed.

## 9. Exact next action

A **separately approved** production operational rehearsal, beginning with synthetic `@example.invalid` fixture and redaction proof — **not** customer email.
