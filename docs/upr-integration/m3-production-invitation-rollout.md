# M3 production invitation-email rollout (authoritative freeze)

**Status:** **NO-GO / BLOCKED** — planning freeze only.  
**Original freeze tag:** `m3-production-invitation-rollout-freeze`.  
**Operational prerequisites freeze tag:** `m3-production-operational-prerequisites-freeze` (docs/tooling correction; peels to that merge).  
**Does not authorise** production invitation email, allowlist population, historical reconciliation backfill, deployment, or customer contact.  
**P1–P8** must be complete before any production pilot execution.  
**Production is not changed by this freeze.** Only a later, separately approved execution order may perform Phase A onward.

**Corrected target pair (2026-08-27 WP-DOC freeze):**

| Component | Ref | Note |
|-----------|-----|------|
| UPR | annotated `v0.3.0` → `b2abc2defc30fc023601593aa1720cbfdd0a4f3c` | Unchanged |
| Host | standalone [`upr-host-adapter`](https://github.com/magpern/upr-host-adapter) annotated `v0.1.1` → `5bc67c7dd7946178b75e1b7d91d32d1e2074296e` | **Sole** production host package target |
| Former host | `biopentra-upr-host` **0.1.5** | **SUPERSEDED** — not a production package candidate |

See [`upr-host-adapter-ownership-addendum.md`](upr-host-adapter-ownership-addendum.md) and [`m3-production-operational-prerequisites-closure.md`](m3-production-operational-prerequisites-closure.md).

---

## 1. Executive recommendation and go/no-go prerequisites

**Recommendation: NO-GO.** Do not deploy, enable, or send production invitation emails.

| ID | Prerequisite | Status (this freeze) |
|----|--------------|----------------------|
| P1 | Staged SHA-verified release directories + coordinated pair transition with mandatory worker suspend/proof/resume. Staging ≠ activation. Unenforceable suspend → **NO-GO** | **OPEN** |
| P2 | SHA-verified packages for UPR **`v0.3.0`** and **`upr-host-adapter` `v0.1.1` only** | **COMPLETE** |
| P3 | Read-only live production inventory — no PII | **COMPLETE** |
| P4 | Live production token-redaction proof + external sink confirmation | **OPEN** |
| P5 | Restricted approval ledger outside public Git | **OPEN** |
| P6 | DB backup + restore-time note | **OPEN** |
| P7 | Product Owner final customer-contact approval | **OPEN** (separate) |
| P8 | Operational synthetic `@example.invalid` emergency-pause drill | **OPEN** — blocked on missing UPR public test-fixture capability |

Until P1–P8: emails disabled, no allowlist, no production contact.

---

## 2. Confirmed current-state findings

| Fact | Evidence |
|------|----------|
| DEV ≠ production | DEV `dev.biopentra.eu`; prod `/home/magpern/woocommerce` |
| Prod inventory (P3) | [`m3-production-readonly-inventory.md`](m3-production-readonly-inventory.md) — UPR/host **absent** at inventory |
| AS/WP-Cron runner | Compose service **`cron`**; `DISABLE_WP_CRON=true` |
| Mail worker | `biopentra-mail-worker` = support IMAP — **omit** from UPR suspend list |
| Host Requires Plugins | `universal-product-reviews` — activate UPR before host |
| UPR public CLI | `wp upr reconcile-invitations`, `wp upr db-upgrade`, `wp upr invitation-controls` only |
| Invite mint without email | **No public API/CLI** (`TokenService::issue_invite` is internal) |

---

## 3. Software pins and offline package SHAs

| Artifact | Pin |
|----------|-----|
| UPR | `v0.3.0` / `b2abc2defc30fc023601593aa1720cbfdd0a4f3c` |
| Host | `upr-host-adapter` `v0.1.1` / `5bc67c7dd7946178b75e1b7d91d32d1e2074296e` |
| Former 0.1.5 | **SUPERSEDED** |

| Package artifact | SHA-256 (offline-validated rebuild 2026-08-27) |
|------------------|-----------------------------------------------|
| `upr-host-adapter-0.1.1.zip` | `654328c1f8e5605e14b00c4f973bd22ef1a31c86fdbda63bb1a19111b94b16d9` |
| `universal-product-reviews-0.3.0.zip` | `43beb998579743caf0b2b32dc5fe26470804cd21131ed678b983d19d06e9b8c6` |
| `universal-product-reviews-0.3.0.release.meta.json` | `0af6348642c30648377204e7eb423b8364ecd7898870d282c8b329631be3adc2` |

Prior UPR zip `e7f02bc5…` (0.1.5-era) superseded for this freeze; meta SHA unchanged.

---

## 4. Scope and non-goals

**In scope (after GO):** Deploy the target pair via §6; emails disabled through deploy; later pilot gates only after P1–P8.

**Non-goals:** Deploying **0.1.5**; host-first activation; atomic two-plugin flip; optional worker suspend; DEV CLIs on production; inventing invite mint via internals/SQL; customer contact without P7; dry-checklist P8 “proof”.

---

## 5. Coordinated pair transition (staging ≠ activation)

Working directory: `/home/magpern/woocommerce`.

| Slot | Command |
|------|---------|
| Suspend | `docker compose stop cron` |
| Resume | `docker compose start cron` |
| Proof | AS count → `upr_send_active=0` |

### Forward (fixed)

1. Verify emails disabled, pilot authorisation false, allowlist empty  
2. Stage and checksum-verify both packages  
3. Suspend cron  
4. Prove `upr_send_active=0`  
5. Place both plugin release pointers (filesystem)  
6. Activate **UPR first**  
7. Activate **UPR Host Adapter second**  
8. Verify exact pins, UPR package metadata, host compatibility, emails disabled, deny-by-default pilot policy  
9. Resume cron only after verification succeeds  

### Rollback-to-absent (fixed)

1. Disable emails / confirm disabled  
2. Suspend cron and prove no UPR send action is running  
3. Deactivate **UPR Host Adapter first**  
4. Deactivate **UPR second**  
5. Remove/restore both release pointers to pre-install **absent**  
6. Verify both plugins absent and no UPR send work remains  
7. Resume cron  

Tooling: [`m3-coordinated-pair-transition.md`](m3-coordinated-pair-transition.md) / `scripts/upr-pair-transition/`.

**Migrations:** DB backup (P6) before first UPR `v0.3.0` activate; migrator must not enable emails.

---

## 6. Pilot gates (after operational GO)

| Gate | Action |
|------|--------|
| G-A | Phase A deploy accepted (pins exact; emails off) |
| G-B | Ledger one real order + contact authorisation + expiry |
| G-C | P8 operational pause drill — **blocked** until UPR generic developer/test-fixture capability exists |
| G-D–G-H | Allowlist / enable / one send / transport proof / expansion — only after G-C and P7 as applicable |

### P8 public-interface result

No supported production-safe public API/CLI exists to create an outstanding synthetic invitation token/session without sending email. P8 cannot be frozen or executed until a **separate generic UPR developer/test-fixture capability** is planned and shipped. Do not invent or use internal classes, direct SQL, or undocumented option/table manipulation.

---

## 7. Work packages

| WP | Status |
|----|--------|
| WP-B packages for `v0.1.1` pair | **COMPLETE** (offline) |
| WP-A pair tooling (activate UPR then host; refuse 0.1.5) | **COMPLETE** in repo; production rehearsal still P1 |
| WP-D inventory | **COMPLETE** |
| WP-E / WP-F / WP-P8 / WP-G / WP-H | Open / blocked as above |

Former WP-C embedded **0.1.5** pin is historical/superseded.

---

## 8. Recommendation

Approve this corrected plan for **documentation/tooling freeze**. **Do not execute** production until P1/P4–P8 and a separate Product Owner execute order. Keep P7 customer-contact approval entirely separate. Exact next development dependency: **generic UPR fixture capability for P8**.

## Amendment log

| # | Correction |
|---|------------|
| 1 | Host target = `upr-host-adapter` `v0.1.1`; `0.1.5` SUPERSEDED |
| 2 | Stage ≠ activate; UPR activate first; host second |
| 3 | Rollback-to-absent: deactivate host → UPR → remove pointers |
| 4 | Worker = compose `cron` only; mail-worker omitted |
| 5 | P2 COMPLETE for offline-validated `v0.1.1` pair SHAs |
| 6 | P8 OPEN — no public invite-mint path; UPR fixture capability required |
