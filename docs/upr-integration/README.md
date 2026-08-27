# UPR host integration documentation

Host-side documentation for [Universal Product Reviews](https://github.com/magpern/universal-product-reviews) integration on the Biopentra DEV VPS.

**Boundary:** Generic plugin contracts and portable specifications live in the UPR repository. This directory contains **host-specific** DEV replay, compose, and evidence only.

| Document | Purpose |
|----------|---------|
| [`m1-freeze.md`](m1-freeze.md) | M1 plan freeze — DEV bind-mount plan, option snapshots, validation/rollback templates |
| [`m1-dev-replay.md`](m1-dev-replay.md) | Executed M1 DEV replay record (Phase 2 implementation) |
| [`M3-host-integration.md`](M3-host-integration.md) | **Authoritative** M3 host integration freeze (UPR v0.2.0) |
| [`m3-dev-replay.md`](m3-dev-replay.md) | M3 DEV replay checklist / evidence |
| [`m3-dev-upr-v0.2.1-pin.md`](m3-dev-upr-v0.2.1-pin.md) | DEV UPR v0.2.1 bind-mount pin + preflight |
| [`m3-wp6-discontinued-verification.md`](m3-wp6-discontinued-verification.md) | WP6 discontinued verification evidence (v0.2.0 baseline) |
| [`upr-core-catalogue-hidden-correction.md`](upr-core-catalogue-hidden-correction.md) | Separate UPR-core correction proposal (pilot blocker) |
| [`m3-closure.md`](m3-closure.md) | M3 execution closure report |
| [`m3-dev-pilot-revalidation.md`](m3-dev-pilot-revalidation.md) | DEV pilot revalidation addendum (UPR v0.2.1 pin evidence) |
| [`m3-pdp-reviews-section.md`](m3-pdp-reviews-section.md) | **Authoritative** M3 PDP `#reviews` section freeze (tabs remain off; A1–A20) |

**No production changes** are authorised from these documents without separate production replay approval.
