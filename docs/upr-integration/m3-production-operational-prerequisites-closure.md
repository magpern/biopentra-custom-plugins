# M3 production operational prerequisites — closure addendum (WP-DOC freeze)

**Verdict:** `PREREQUISITE DESIGN AND TOOLING COMPLETE — PRODUCTION STILL NO-GO`  
**Date:** 2026-08-27  
**Freeze tag:** `m3-production-operational-prerequisites-freeze` (annotated; peels to this documentation/tooling merge).  
**Authority:** [`m3-production-invitation-rollout.md`](m3-production-invitation-rollout.md)  
**Related:** [`m3-production-invitation-prerequisites-closure.md`](m3-production-invitation-prerequisites-closure.md)

This addendum records the corrected package pair, activation/rollback order, offline package SHAs, and P8 testability result. **Does not authorise** production deploy, email enablement, allowlist, fixture drill, redaction probe, backup, cron stop, or customer contact. **No production access or change occurred** for this freeze.

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

## P8 public-interface finding

Inspected UPR `v0.3.0` and `upr-host-adapter` `v0.1.1` source/docs.

| Surface | Result |
|---------|--------|
| Public UPR WP-CLI | `wp upr reconcile-invitations`, `wp upr db-upgrade`, `wp upr invitation-controls` only |
| Public mint of outstanding invite token/session without email | **None** |
| `TokenService::issue_invite` | **Internal** (InvitationMailer + tests) — not a documented public production API |
| Host `verify-*-dev` CLIs | DEV-gated; refuse non-development; use internals — **not** a production public path |

**Conclusion:** No supported, production-safe public API/CLI exists to create an outstanding synthetic invitation token/session without sending email. P8 **cannot** be frozen or executed by inventing internal/SQL paths.

**Development dependency:** A separately planned **generic UPR developer/test-fixture capability** is required before the production pause drill can be frozen or executed. P8 remains **OPEN** with this dependency recorded.

## Remaining production gates

| ID | Status |
|----|--------|
| P1 | **OPEN** — production pair transition not rehearsed |
| P2 | **COMPLETE** — `v0.1.1` pair offline-validated |
| P3 | **COMPLETE** |
| P4 | **OPEN** — production token-redaction / sink proof not run |
| P5 | **OPEN** — ledger template only; no approved live location/operators |
| P6 | **OPEN** — production DB backup + restore-time note not done |
| P7 | **OPEN** — Product Owner customer-contact approval not issued (entirely separate) |
| P8 | **OPEN** — blocked on UPR generic fixture capability |

## Exact next step

Plan and ship a **generic UPR developer/test-fixture capability** (separate initiative) so P8 has a supported public path. Do **not** treat a dry checklist, DEV results, or internal `TokenService::issue_invite` as production P8 proof. After that capability exists, seek a **separately approved** production operational rehearsal (still no customer contact without P7).
