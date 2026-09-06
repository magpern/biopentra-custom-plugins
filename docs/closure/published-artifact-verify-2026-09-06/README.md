# Published-artifact verification closure — UCB / MPCF / MPCP

**Verdict: PASS**  
**Date:** 2026-09-06 (UTC)  
**Scope:** DEV-only. No production deploy, no production enablement, no real SKU/kit configuration.

This record closes development for the coordinated release of the three published GitHub release ZIPs by proving those artifacts (not feature-branch checkouts) work together on DEV.

## Released plugins

| Plugin | Version | Tag | Release SHA (`main`) | GitHub Release |
|--------|---------|-----|----------------------|----------------|
| universal-commerce-bundles | 0.1.0 | `v0.1.0` | `63046f303e8e6ff0c5e74a5af07853c6d193b780` | https://github.com/magpern/universal-commerce-bundles/releases/tag/v0.1.0 |
| mp-commerce-fulfillment | 1.1.0 | `v1.1.0` | `efe7b49764715f7dc5f1a64a3c1804d8618ad270` | https://github.com/magpern/mp-commerce-fulfillment/releases/tag/v1.1.0 |
| mp-commerce-promotions | 0.6.0 | `v0.6.0` | `57b102a2014b9b03c74bf3759dca779682cece85` | https://github.com/magpern/mp-commerce-promotions/releases/tag/v0.6.0 |

Each tag peels to the same commit as `origin/main` at verification time (`identical ahead=0 behind=0`).

Companion storefront on DEV (not part of this three-plugin release gate): **biopentra-storefront 0.9.43** / `storefront-v0.9.43`.

MU safety guard: host MU-plugin `universal-commerce-bundles-safety-guard.php` (from `biopentra-custom-plugins`), mounted `:ro`.

## Release ZIP verification

Downloaded fresh from GitHub Releases into  
`/opt/biopentra/dev/_published-artifact-verify/20260906T102953Z/zips/`.

| ZIP | SHA-256 |
|-----|---------|
| `universal-commerce-bundles-0.1.0.zip` | `b99b981b3ebee7ddd8088fae2b96053577cb4c25c5e11db0e49d236ca997fd4d` |
| `mp-commerce-fulfillment-1.1.0.zip` | `31d81d08604592b6c6d539f2b6420a5b28fc3d8f339ebe12d82d79ee3fa95826` |
| `mp-commerce-promotions-0.6.0.zip` | `a0d87ea4bf04adc1640dd04336ba98af10545d80be9fb8145c78a6fc1cd31832` |

Checksums match the prior coordinated artifact store (`dev/_release-coord/artifacts/SHA256SUMS.txt`).

- **MPCP:** `scripts/lib/verify-release-zip.py` → **OK** (400 entries; forbidden segments absent).
- **MPCF / UCB:** structural verify (main plugin file + version header; no `.git` / `tests` / `scripts` / `node_modules` / `docs`); production `vendor/` present where expected → **OK**.
- Extracted trees were **byte-identical** to current DEV `_release-artifacts/` copies (0 mismatches).

## Working trees (involved repos)

At verification start, local checkouts were clean but not all on `main`. They were fast-forwarded to `origin/main` before the temp install:

| Repo | Final HEAD | Matches `origin/main` | Dirty |
|------|------------|----------------------|-------|
| universal-commerce-bundles | `63046f3…` | yes | 0 |
| mp-commerce-fulfillment | `efe7b49…` | yes | 0 |
| mp-commerce-promotions | `57b102a…` | yes | 0 |
| biopentra-custom-plugins | `96c79ea…` | yes | 0 |

## Temporary DEV published-artifact install

1. Extracted the three GitHub ZIPs (+ MU guard copy) under  
   `dev/_published-artifact-verify/20260906T102953Z/extract/`.
2. Remounted `apps/wordpress/compose.yml` wordpress + wpcli volumes from `_release-artifacts/…` → that extract path (MU guard remained `:ro`).
3. `docker compose up -d --force-recreate wordpress`.
4. Confirmed via `/proc/1/mountinfo` that live mounts pointed at the published extract paths.
5. Confirmed published trees had **no** `scripts/` or `tests/` directories (ZIP-only).

### Active-version proof (during temp install)

- UCB **0.1.0**, MPCF **1.1.0**, MPCP **0.6.0**, storefront **0.9.43** active.
- Single directory per slug (no duplicate trees).
- No retired bundle/pricing plugin slugs loaded.
- MU guard present; `ucb_runtime_ready` fired with UCB active.
- MU guard file visible in web PHP, WP-CLI, and cron-style CLI contexts.

## Acceptance results

| Gate | Result | Evidence |
|------|--------|----------|
| Bulk pricing fixtures + cart (anchors/custom qty, coupon coexist, UMC CLI) | **PASS** (12 ok; SEK CLI range SKIP by design) | `BULK_ACCEPTANCE.txt` |
| Bulk promotion arbitration | **PASS** (5 ok) | `BULK_ACCEPTANCE.txt` |
| Coordinated UCB/MPCF/MPCP suite (bulk regression, kit cart/order, MPCF 3 pick rows / no parent, stock −1/−2/−1, mixed cart, Store API ≈ classic) | **PASS** (47 ok, 0 fail) | `COORD_ACCEPTANCE.txt` |
| MU guard fail-closed (fresh process: UCB off → kit blocked; UCB on → purchasable) | **PASS** | `GUARD_PROOF_FRESH.txt` |
| Kit refund/restock + MPCF intake idempotency + revalidate hooks | **PASS** (15 ok) | `REFUND_CRON.txt` |
| Control + fixture HTTP; SEK PDP markup | **PASS** (HTTP 200; `pdp-bulk` + SEK/kr present) | `HTTP_CONTROL_SEK.txt` |
| Playwright desktop-1440 bulk suite (incl. UMC SEK PDP→cart→checkout; control regression; axe) | **PASS** (6 passed, 1 skipped = sticky gate which is mobile-390-only) | `PLAYWRIGHT_DESKTOP1440.txt` |
| Log inspection (30m PHP Fatal/Parse; debug.log) | **PASS** (no new fatals; no debug.log) | `LOG_INSPECTION.txt` |

Note: coordinated suite observed 20 recent failed Action Scheduler actions (pre-existing count spot-check; not attributed to this artifact install).

## Restoration

- Restored `compose.yml` to canonical `_release-artifacts/` mounts.
- Recreated wordpress container; `/proc` mounts confirm `_release-artifacts/…`.
- Removed temporary extract tree; retained zips + evidence under  
  `dev/_published-artifact-verify/20260906T102953Z/` (and this docs copy).
- Active versions after restore: UCB 0.1.0 / MPCF 1.1.0 / MPCP 0.6.0 / storefront 0.9.43.
- Fixtures remain **hidden** (`bulk-pricing-fixture`, `ucb-fixture-*`). **No non-fixture kits** and no real SKU/kit enablement.

## Explicit non-actions

- No production deployment.
- No production plugin enablement beyond any prior separate UCB-only install (out of scope here).
- No real catalog SKU or customer-facing kit enabled.
- No new Git tags/releases created for this verification.
- No pricing-rule or production infrastructure changes.

## Remaining rollout-only work

Development for this coordinated three-plugin published set may be formally closed on DEV.

Rollout-only (explicit PO GO required, not done here):

1. Production install of MPCF **1.1.0** and MPCP **0.6.0** (alongside UCB **0.1.0** if not already).
2. Optional production install of storefront **0.9.43** (bulk PDP UI).
3. Coordinated production acceptance.
4. Only then: enable any real bulk SKU or customer-facing kit.

## Evidence index

See sibling files in this directory and `preflight/`.
