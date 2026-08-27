# M3 production-invitation prerequisites — closure

**Verdict:** `PREREQUISITE DESIGN AND TOOLING COMPLETE — PRODUCTION STILL NO-GO`  
**Date:** 2026-08-27 (corrected target pair + P1–P8 mapping)  
**Authority:** [`m3-production-invitation-rollout.md`](m3-production-invitation-rollout.md)  
**Operational addendum:** [`m3-production-operational-prerequisites-closure.md`](m3-production-operational-prerequisites-closure.md)

This closure records repository prerequisites and gate status. It does **not** authorise production fixture drills, token redaction probes, deployment, email enablement, allowlist population, or customer contact.

**Standing rule:** Production must not be mutated unless explicitly authorised for that action. See §7 for an unauthorised exception that already occurred and must not be repeated.

## Correction (2026-08-27 WP-DOC freeze)

| Former | Corrected |
|--------|-----------|
| Production host package `biopentra-upr-host` **0.1.5** | **SUPERSEDED** |
| Production host target | **`upr-host-adapter` `v0.1.1`** / `5bc67c7dd7946178b75e1b7d91d32d1e2074296e` |
| UPR core | Unchanged: `v0.3.0` / `b2abc2defc30fc023601593aa1720cbfdd0a4f3c` |
| P2 | **COMPLETE** for offline-validated `v0.1.1` pair (new ZIP SHAs; meta SHA unchanged) |
| P8 | **OPEN** — blocked on operational synthetic-mail decision + test mailbox (no UPR mint API) |
| Activation order | Stage ≠ activate; **UPR first**, host second; rollback host→UPR→absent |

Freeze tag for this correction: `m3-production-operational-prerequisites-freeze`.

## 1. Documentation freeze (original invitation plan)

| Item | Value |
|------|--------|
| Tag | `m3-production-invitation-rollout-freeze` |
| Peel / merge | `c3904787747a4962e0e4acd7cd3cc57a91494e18` |
| PR | https://github.com/magpern/biopentra-custom-plugins/pull/22 |

## 2. Historical work packages (embedded 0.1.5 path — superseded for prod packaging)

### WP-C — host 0.1.5 package pin (historical)

| Item | Value |
|------|--------|
| PR | https://github.com/magpern/biopentra-custom-plugins/pull/23 |
| Merge | `0efaae61862061d3b674a63a9f26e327cb205d63` |
| Annotated tag | `0.1.5` |
| Production status | **SUPERSEDED** by `upr-host-adapter` `v0.1.1` |

### WP-B — private packages (historical 0.1.5-era build)

| Item | Value |
|------|--------|
| UPR PR | https://github.com/magpern/universal-product-reviews/pull/19 |
| UPR ZIP SHA-256 (0.1.5-era) | `e7f02bc5bc69c1c7cca2aa20d4dc834d08938515675d4e8658759e246b54d0fa` — **superseded** by 2026-08-27 rebuild |
| UPR meta SHA-256 | `0af6348642c30648377204e7eb423b8364ecd7898870d282c8b329631be3adc2` — **unchanged** |
| Host ZIP SHA-256 (0.1.5) | `2fc1c5130a8fd9d4ad4e106949eab051fad1a39c38f2a71e975aacdeb45f9b8d` — **not** a production candidate |

### WP-B — corrected pair (offline validate 2026-08-27)

| Artifact | SHA-256 |
|----------|---------|
| `upr-host-adapter-0.1.1.zip` | `654328c1f8e5605e14b00c4f973bd22ef1a31c86fdbda63bb1a19111b94b16d9` |
| `universal-product-reviews-0.3.0.zip` | `43beb998579743caf0b2b32dc5fe26470804cd21131ed678b983d19d06e9b8c6` |
| `universal-product-reviews-0.3.0.release.meta.json` | `0af6348642c30648377204e7eb423b8364ecd7898870d282c8b329631be3adc2` |

Disposable validate: `scripts/validate-m3-pair-packages.sh` — PASS. Host builder: `upr-host-adapter` `scripts/build-release-package.sh v0.1.1`. No public GitHub Release.

### WP-A — pair transition

Scripts under `scripts/upr-pair-transition/` updated for stage≠activate, UPR-then-host activation, host-then-UPR deactivation, rollback-to-absent, and refuse superseded identity. Disposable rehearsal PASS.

## 3. Production-ready target pair (packages only — not deployed)

| Component | Ref |
|-----------|-----|
| UPR | `v0.3.0` / `b2abc2defc30fc023601593aa1720cbfdd0a4f3c` |
| Host | `upr-host-adapter` `v0.1.1` / `5bc67c7dd7946178b75e1b7d91d32d1e2074296e` |
| Former | `biopentra-upr-host` `0.1.5` — **SUPERSEDED** |

## 4. Read-only production inventory

See [`m3-production-readonly-inventory.md`](m3-production-readonly-inventory.md). P3 COMPLETE. No UPR/host installed at inventory; `cron` is suspend target; mail-worker omitted.

## 5. Worker suspension

**Concretely implementable:** `docker compose stop cron` / `start cron` at `/home/magpern/woocommerce`. Production transition not rehearsed → **P1 OPEN**.

## 6. P1–P8 status

| ID | Status |
|----|--------|
| **P1** | **OPEN** — production transition not rehearsed |
| **P2** | **COMPLETE** — `v0.1.1` pair offline-validated |
| **P3** | **COMPLETE** |
| **P4** | **OPEN** |
| **P5** | **OPEN** — template only |
| **P6** | **OPEN** |
| **P7** | **OPEN** — separate customer-contact approval |
| **P8** | **OPEN** — ordinary post-boundary synthetic mail to approved test mailbox; blocked on operational mailbox/approval decision; **do not** add UPR mint-without-email API |

## 7. Runtime change proof

| System | Changed by this WP-DOC freeze? |
|--------|------------------------------|
| DEV WordPress invitation controls | **No** (emails remain disabled; pause off) |
| Public GitHub Releases | **No** |
| Production | **No** |

Unauthorised prior exception (API_TOKEN rotation during inventory) remains recorded historically and must not be repeated.

## 8. Exact next action

1. Keep production **NO-GO**.  
2. **Park production work** and return to product development.  
3. When resuming: decide/record the **operational synthetic-mail test mailbox** (ledger-only) required for P8; do **not** add a UPR public mint-without-email API.  
4. Separately approved production operational rehearsal only under explicit production-change authorisation — real customer contact still requires P7 (distinct from synthetic-mail approval).
