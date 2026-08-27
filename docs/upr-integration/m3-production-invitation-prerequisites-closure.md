# M3 production-invitation prerequisites — closure

**Verdict:** `PREREQUISITES IMPLEMENTED — PRODUCTION STILL NO-GO`  
**Date:** 2026-08-27 (corrected P1–P8 mapping)  
**Authority:** [`m3-production-invitation-rollout.md`](m3-production-invitation-rollout.md) (freeze tag below)

This closure records repository prerequisites and the corrected gate status. It does **not** authorise production fixture drills, token redaction probes, deployment, email enablement, allowlist population, or customer contact.

**Standing rule:** Production must not be mutated unless explicitly authorised for that action. See §7 for an unauthorised exception that already occurred and must not be repeated.

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
| PR | https://github.com/magpern/biopentra-custom-plugins/pull/25 |
| Merge | `05317069bd3a9b63d1f9cbca05425700a8a044f6` |
| Scripts | `scripts/upr-pair-transition/*` |
| Disposable rehearsal | `rehearse-disposable.sh` — PASS (transition + rollback + suspend-fail abort) |

## 3. Production-ready target pair (packages only — not deployed)

| Component | Ref |
|-----------|-----|
| UPR | `v0.3.0` / `b2abc2defc30fc023601593aa1720cbfdd0a4f3c` |
| Host | `0.1.5` / `0efaae61862061d3b674a63a9f26e327cb205d63` |

## 4. Read-only production inventory

See [`m3-production-readonly-inventory.md`](m3-production-readonly-inventory.md) and safe procedure [`m3-production-readonly-inventory-procedure.md`](m3-production-readonly-inventory-procedure.md).

Summary: production had **no** UPR/host installed at inventory time; `DISABLE_WP_CRON=true`; compose service **`cron`** is the enforceable suspend target; `woo_has_product_tabs` missing; no token probe / fixtures / customer mail performed as part of M3 invitation gates.

## 5. Worker suspension

**Concretely implementable:** `docker compose stop cron` / `start cron` at `/home/magpern/woocommerce`.  
Disposable tooling rehearsed; **production** transition not rehearsed → **P1 open**.

## 6. P1–P8 status (frozen-plan mapping)

| ID | Frozen-plan meaning | Status |
|----|---------------------|--------|
| **P1** | Staged SHA-verified release dirs + coordinated pair transition with mandatory worker suspend/proof/resume (production) | **OPEN** — repo tooling + disposable rehearsal exist; **production** transition not rehearsed |
| **P2** | SHA-verified packages for UPR `v0.3.0` and host `0.1.5` | **COMPLETE** (private builds + checksums + disposable validate) |
| **P3** | Read-only live production inventory (no PII) | **COMPLETE** (see inventory doc; procedure hardened after compose-config exposure) |
| **P4** | Live production token-redaction proof on URI-bearing logs + external sink confirmation | **OPEN** — not run (forbidden in prerequisites initiative) |
| **P5** | Restricted approval ledger (order ID + required fields) outside public Git | **OPEN** — **template only**; no approved live ledger location / named operators |
| **P6** | DB backup + restore-time note | **OPEN** — not performed |
| **P7** | Product Owner final customer-contact approval after documentation freeze | **OPEN** — not issued |
| **P8** | Operational production emergency-pause drill on synthetic `@example.invalid` fixture | **OPEN** — not run |

## 7. Runtime change proof

| System | Changed by this initiative? |
|--------|------------------------------|
| DEV WordPress runtime / bind-mounts / options | **No** |
| Public GitHub Releases | **No** |
| Production (authorised M3 invitation gates) | **No** deploy / UPR install / email enable / allowlist / fixture / redaction probe / customer contact |
| Production (unauthorised exception — 2026-08-27) | **Yes — do not repeat.** After inventory printed expanded compose secrets into an agent terminal log, an agent rotated `API_TOKEN` (WP worker-token hash + `.env.worker`) and briefly stopped/restarted `proton-bridge` / `biopentra-mail-worker` **without** an explicit production-change authorisation for that step. `IMAP_PASS` was **not** changed. Operator later forbade all further production mutation. Post-restart worker status check was HTTP 200. |

Secret-presence audit (docs repo / PR bodies): leaked plaintext values were **not** found in Git `HEAD` or listed PR bodies. Agent terminal logs are outside Git.

## 8. Explicit non-performance statement (M3 invitation gates)

Production fixture drill (P8), HTTP token-redaction / sink proof (P4), UPR/host deployment, email enablement, allowlist population, historical reconciliation backfill, DB backup/restore-time note (P6), live approval ledger (P5), PO customer-contact approval (P7), and customer contact were **not** performed as authorised M3 invitation work.

## 9. Exact next action

A **separately approved** production operational rehearsal, beginning with synthetic `@example.invalid` fixture and redaction proof — **not** customer email — and only under **explicit** production-change authorisation.
