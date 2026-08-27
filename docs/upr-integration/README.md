# UPR host integration documentation

Host-side documentation for [Universal Product Reviews](https://github.com/magpern/universal-product-reviews) integration on the Biopentra DEV VPS.

**Boundary:** Generic plugin contracts and portable specifications live in the UPR repository. This directory contains **host-specific** DEV replay, compose, and evidence only.

| Document | Purpose |
|----------|---------|
| [`m1-freeze.md`](m1-freeze.md) | M1 plan freeze — DEV bind-mount plan, option snapshots, validation/rollback templates |
| [`m1-dev-replay.md`](m1-dev-replay.md) | Executed M1 DEV replay record (Phase 2 implementation) |
| [`M3-host-integration.md`](M3-host-integration.md) | **Authoritative** M3 host integration freeze (UPR v0.2.0) |
| [`m3-dev-replay.md`](m3-dev-replay.md) | M3 DEV replay checklist / evidence |
| [`dev-replay-blocksy-product-tabs.md`](dev-replay-blocksy-product-tabs.md) | DEV-only Blocksy `woo_has_product_tabs=no` replay (B4 tabs-off baseline) |
| [`m3-b4-dev-pilot-acceptance.md`](m3-b4-dev-pilot-acceptance.md) | B4 DEV pilot acceptance closure (**PASS — DEV PILOT ACCEPTED**) |
| [`m3-dev-upr-v0.2.1-pin.md`](m3-dev-upr-v0.2.1-pin.md) | DEV UPR v0.2.1 bind-mount pin + preflight |
| [`m3-wp6-discontinued-verification.md`](m3-wp6-discontinued-verification.md) | WP6 discontinued verification evidence (v0.2.0 baseline) |
| [`upr-core-catalogue-hidden-correction.md`](upr-core-catalogue-hidden-correction.md) | Separate UPR-core correction proposal (pilot blocker) |
| [`m3-closure.md`](m3-closure.md) | M3 execution closure report |
| [`m3-dev-pilot-revalidation.md`](m3-dev-pilot-revalidation.md) | DEV pilot revalidation addendum (UPR v0.2.1 pin evidence) |
| [`m3-pdp-reviews-section.md`](m3-pdp-reviews-section.md) | **Authoritative** M3 PDP `#reviews` section freeze (tabs remain off; A1–A20; **B3 ownership amended**) |
| [`m3-pdp-reviews-section-b3-ownership-amendment.md`](m3-pdp-reviews-section-b3-ownership-amendment.md) | B3 ownership amendment record (UPR v0.2.2 + B2 host; PR #10 superseded) |
| [`b2-upr-v0.2.2-host-integration.md`](b2-upr-v0.2.2-host-integration.md) | B2 host pin to UPR v0.2.2 — display helper + UX only; core owns native enforcement |
| [`b2-dev-upr-v0.2.2-pin.md`](b2-dev-upr-v0.2.2-pin.md) | DEV bind-mount pin ops for UPR v0.2.2 (operator step; not in B2 PR deploy) |
| [`m3-invitation-email-controls-host-policy.md`](m3-invitation-email-controls-host-policy.md) | **Authoritative** host pilot send-policy freeze (order-ID allowlist; depends on UPR invitation-email controls) |
| [`m3-dev-preproduction-rehearsal.md`](m3-dev-preproduction-rehearsal.md) | **Authoritative** DEV pre-production invitation-email rehearsal freeze (UPR `v0.3.0`; no production) |
| [`m3-dev-preproduction-rehearsal-acceptance.md`](m3-dev-preproduction-rehearsal-acceptance.md) | DEV pre-production rehearsal closure (**PASS**) |
| [`m3-production-invitation-rollout.md`](m3-production-invitation-rollout.md) | **Authoritative** production invitation-email rollout plan (**NO-GO / BLOCKED**; freeze only) |
| [`m3-host-0.1.5-package-pin.md`](m3-host-0.1.5-package-pin.md) | Host **0.1.5** packaged UPR pin (`release.meta.json`; no `.git`) |
| [`m3-private-package-build.md`](m3-private-package-build.md) | Private SHA-verified UPR `v0.3.0` + host `0.1.5` package build (no public Release) |
| [`m3-coordinated-pair-transition.md`](m3-coordinated-pair-transition.md) | WP-A coordinated pair transition tooling + disposable rehearsal |
| [`m3-production-readonly-inventory.md`](m3-production-readonly-inventory.md) | Read-only production inventory (2026-08-27) |
| [`m3-production-readonly-inventory-procedure.md`](m3-production-readonly-inventory-procedure.md) | Safe read-only inventory procedure (forbid unredacted `docker compose config`) |
| [`m3-approval-ledger.template.md`](m3-approval-ledger.template.md) | Restricted approval-ledger **template** (no live PII) |
| [`m3-production-invitation-prerequisites-closure.md`](m3-production-invitation-prerequisites-closure.md) | Prerequisites closure — **PRODUCTION STILL NO-GO** |
| [`upr-host-adapter-extraction.md`](upr-host-adapter-extraction.md) | **Authoritative** extraction of host adapter to `magpern/upr-host-adapter` (supersedes embedded `0.1.5`) |

**No production changes** are authorised from these documents without separate production replay approval.
