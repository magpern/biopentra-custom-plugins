# M3 closure report — UPR host integration

**Verdict:** PASS WITH BLOCKED PILOT  
**Date:** 2026-08-26  
**Freeze tag:** `upr-m3-host-integration-freeze` → `4580ac548a3e0d64e0e7ff00b31f71dd39e3e22f`  
**UPR pin:** `v0.2.0` @ `4eb1f965d5ab87d8d1e1479257fbb8721f25ce41` (unchanged; no host code in UPR)

## Verdict rationale

All M3 engineering deliverables merged and DEV-replayed. **WP6 catalogue-hidden discontinued behaviour fails against UPR v0.2.0 public core.** Per freeze hard gate: **DEV pilot and host deployment must not proceed** until a separate UPR-core correction is planned, frozen, implemented, released, and the host is pinned to that version. See [`m3-wp6-discontinued-verification.md`](m3-wp6-discontinued-verification.md) and [`upr-core-catalogue-hidden-correction.md`](upr-core-catalogue-hidden-correction.md).

## Repositories / PRs / merges

| Repo | Branch / PR | Merge commit |
|------|-------------|--------------|
| `biopentra-custom-plugins` | [#4](https://github.com/magpern/biopentra-custom-plugins/pull/4) docs freeze | `4580ac5` + tag `upr-m3-host-integration-freeze` |
| `biopentra-custom-plugins` | [#5](https://github.com/magpern/biopentra-custom-plugins/pull/5) host + storefront | `8f46d87` |
| `mp-commerce-fulfillment` | [#12](https://github.com/magpern/mp-commerce-fulfillment/pull/12) lifecycle action | `3d2e583` |
| `biopentra-blocksy-child` | [#1](https://github.com/magpern/biopentra-blocksy-child/pull/1) reviews CSS | `cfb3204` |
| `biopentra-loop-card` | [#1](https://github.com/magpern/biopentra-loop-card/pull/1) card ratings | `ee69143` |
| `biopentra-cache-infrastructure` | [#1](https://github.com/magpern/biopentra-cache-infrastructure/pull/1) token redaction | `f7766b1` |
| `storefront-acceptance` | [#1](https://github.com/magpern/storefront-acceptance/pull/1) schema suite | `c105423` |

## Work completed (per repo)

- **MPCF:** `mpcf_fulfillment_state_changed` after save + `record_events()`; listener isolation; path matrix (admin/REST/wave/refund); CLI has no writer; CI green.
- **biopentra-upr-host:** hybrid delivery (`delivered` / `shipped_fallback`); support allowlists; fail-closed `wp biopentra-upr-host verify-dev-mail`; availability UX hooks; admin settings.
- **storefront 0.9.37:** PDP rating summary via WC APIs → `#reviews`.
- **blocksy-child:** scoped reviews CSS; sticky bar untouched.
- **loop-card:** ratings flag default **off**; ≥3 approved when enabled.
- **cache-infra / SWAG DEV:** URI redaction `/upr-review/{token}/` → `[redacted]` in `bp_combined` + `bp_cache*`; `/upr-review/form/` readable.
- **storefront-acceptance:** Product JSON-LD single-entity suite.

## DEV replay evidence

| Check | Result |
|-------|--------|
| Production untouched | Confirmed — only `dev.biopentra.eu` / VPS DEV stack |
| `WP_ENVIRONMENT_TYPE` | `development` (compose `WORDPRESS_CONFIG_EXTRA`) |
| UPR version | `0.2.0` active; generic, unmodified |
| MPCF hook present | `MPCF_HOOK_OK` on bind-mounted git checkout |
| Host plugin | `biopentra-upr-host` 0.1.0 activated |
| DEV mail CLI | **PASS** — LoggingMailTransport recorded=1 |
| Token redaction | Raw test token absent after reload; `upr-review/[redacted]` in access + bp-cache logs; form path readable |
| WP6 catalogue-hidden | **FAIL** — UPR-core gap; pilot blocked |
| Sticky / schema / AS | Sticky not modified; schema suite merged; AS/cron unchanged (`DISABLE_WP_CRON` + `wp-cron.sh`) |

### Configuration before → after (DEV)

- Compose: `WP_ENVIRONMENT_TYPE=development`; MPCF mount → git checkout; added `biopentra-upr-host` bind-mount.
- SWAG: deployed `biopentra-log-redaction.conf` + updated `bp_cache*` / `default.conf` access logs; `nginx -t` OK; DEV reload performed.

## Explicit non-actions

- No production deploy / config / DB change
- No GitHub Release / ZIP / UPR version tag for M3
- No host work inside UPR core
- No sticky buy-bar DOM/selector/JS changes

## Known limitations

1. **Pilot blocked** on catalogue-hidden UPR-core correction (`v0.2.1` proposal).
2. UPR `delay` invitation rows may not auto-promote to sendable without a future UPR promotion path (core runtime gap separate from WP6; support adapter still returns contract-correct `delay`).
3. Satellite CI (blocksy-child / loop-card) failed to start jobs due to **GitHub billing/spending limit**; merges proceeded after local build validation. MPCF CI was fully green.

## Production prerequisites (separate approval required)

1. UPR-core catalogue-hidden correction released; host pinned.
2. DEV pilot metrics after gate clears.
3. Explicit production approval — **this closure does not authorise production deploy.**

## Exact next step

Plan, freeze, implement, and release the UPR-core catalogue-hidden correction ([proposal](upr-core-catalogue-hidden-correction.md)); pin host to that release; then re-open DEV pilot under the M3 freeze.
