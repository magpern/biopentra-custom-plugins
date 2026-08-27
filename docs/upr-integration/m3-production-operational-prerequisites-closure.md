# M3 production operational prerequisites — closure addendum (WP-DOC freeze)

**Verdict:** `PREREQUISITE DESIGN AND TOOLING COMPLETE — PRODUCTION STILL NO-GO`  
**Date:** 2026-08-27  
**Freeze tag:** `m3-production-operational-prerequisites-freeze` (annotated; peels to the WP-DOC tooling merge).  
**P8 amendment:** post-freeze correction — P8 uses ordinary synthetic mail + test mailbox; **no** UPR mint-without-email API (this amendment does not move the freeze tag).  
**Authority:** [`m3-production-invitation-rollout.md`](m3-production-invitation-rollout.md)  
**Related:** [`m3-production-invitation-prerequisites-closure.md`](m3-production-invitation-prerequisites-closure.md)

This addendum records the corrected package pair, activation/rollback order, offline package SHAs, and P8 testability (amended: ordinary synthetic-mail path; no UPR mint API). **Does not authorise** production deploy, email enablement, allowlist, fixture drill, redaction probe, backup, cron stop, or customer contact. **No production access or change occurred** for this freeze.

## Exact target packages

| Component | Pin |
|-----------|-----|
| UPR | annotated `v0.3.0` → `b2abc2defc30fc023601593aa1720cbfdd0a4f3c` |
| Host | annotated `upr-host-adapter` `v0.1.1` → `5bc67c7dd7946178b75e1b7d91d32d1e2074296e` |
| Former | `biopentra-upr-host` `0.1.5` — **SUPERSEDED** (never a production target) |

## Offline-validated package SHA-256 (rebuild from immutable tags, 2026-08-27)

| Artifact | SHA-256 |
|----------|---------|
| `upr-host-adapter-0.1.1.zip` | `654328c1f8e5605e14b00c4f973bd22ef1a31c86fdbda63bb1a19111b94b16d9` |
| `universal-product-reviews-0.3.0.zip` | `43beb998579743caf0b2b32dc5fe26470804cd21131ed678b983d19d06e9b8c6` |
| `universal-product-reviews-0.3.0.release.meta.json` | `0af6348642c30648377204e7eb423b8364ecd7898870d282c8b329631be3adc2` |

Prior prerequisites-closure UPR zip `e7f02bc5…` (0.1.5-era build) is superseded for this freeze by the rebuild above; **meta SHA unchanged**. No public GitHub Release.

Disposable validate PASS: `scripts/validate-m3-pair-packages.sh`. Disposable pair rehearsal PASS: `scripts/upr-pair-transition/rehearse-disposable.sh`.

## Corrected activation and rollback order

**Staging ≠ WordPress activation.** Host declares `Requires Plugins: universal-product-reviews`.

**Forward:**

1. Verify emails disabled, pilot authorisation false, allowlist empty  
2. Stage and checksum-verify both packages  
3. Suspend production cron (`docker compose stop cron` at `/home/magpern/woocommerce`)  
4. Prove `upr_send_active=0`  
5. Place both plugin release pointers (filesystem)  
6. Activate **UPR first**  
7. Activate **UPR Host Adapter second**  
8. Verify exact pins, UPR package metadata, host compatibility, emails disabled, deny-by-default pilot policy  
9. Resume cron only after verification succeeds  

**Rollback-to-absent:**

1. Disable emails / confirm disabled  
2. Suspend cron and prove no UPR send action is running  
3. Deactivate **UPR Host Adapter first**  
4. Deactivate **UPR second**  
5. Remove/restore both release pointers to the pre-install **absent** state  
6. Verify both plugins absent and no UPR send work remains  
7. Resume cron  

Do not describe host-first activation as valid. Do not claim a two-plugin atomic switch. Mail-worker is omitted from the mandatory UPR suspend list.

## P8 testability (amended)

**Do not** add a public “mint without email” API to generic UPR. That would mint review invitations outside the normal controlled workflow and weaken the product boundary.

P8 uses the **ordinary post-boundary invitation path** later:

1. One isolated synthetic order (no impact on real customers, fulfilment, or reporting).
2. A dedicated **operator-controlled test mailbox**, recorded only in the restricted approval ledger (never in public Git).
3. Explicit approval for that synthetic send (ledger row; not P7 real-customer contact).
4. Normal sequence: host allowlist → master-enable → delivery event → token creation → emergency pause → revoke + pending cancel → unpause no retro-send → deterministic cleanup.

`@example.invalid` remains fine for **non-mail** fixtures, but it **may be rejected before UPR creates a token** and **cannot be relied on** for this proof.

| Incorrect earlier finding | Corrected |
|---------------------------|-----------|
| P8 blocked on new UPR-core fixture/mint capability | **Rejected** — do not add such an API |
| P8 dependency | **Operational:** synthetic-mail decision + approved test mailbox + ledger approval |

P8 remains **OPEN**, blocked on that operational decision — not on UPR product development.

## Remaining production gates

| ID | Status |
|----|--------|
| P1 | **OPEN** — production pair transition not rehearsed |
| P2 | **COMPLETE** — `v0.1.1` pair offline-validated |
| P3 | **COMPLETE** |
| P4 | **OPEN** — production token-redaction / sink proof not run |
| P5 | **OPEN** — ledger template only; no approved live location/operators |
| P6 | **OPEN** — production DB backup + restore-time note not done |
| P7 | **OPEN** — Product Owner **real** customer-contact approval not issued (separate from synthetic-mail) |
| P8 | **OPEN** — blocked on operational synthetic-mail decision + test mailbox |

## Exact next step

**Park production work.** Return to product development. Resume production operational gates only under a separate execute order after the synthetic-mail / test-mailbox decision is recorded for P8 (and P1/P4–P6 as required). Do not invent UPR invite-mint APIs or use internal/SQL token creation for production proof.
