# Milestone D — Validation

**Suite:** `storefront-acceptance` with `--milestone-d`  
**Command:**

```bash
cd /opt/biopentra/dev/storefront-acceptance
tools/run-dev.sh --milestone-d
```

## Spec coverage

| Spec | WP |
|---|---|
| `baseline.spec.ts` | Regression |
| `home-ia.spec.ts` | A |
| `shop-ia.spec.ts` | B |
| `card-interaction.spec.ts` | C |
| `canonical-cards.spec.ts` | C |
| `seo-meta.spec.ts` | D3 |
| `image-budget.spec.ts` | D1 |
| `pdp-layout.spec.ts` | D2A |
| `pdp-sticky.spec.ts` | D2B |

## Targeted WP exits (during implementation)

| Flag | Result |
|---|---|
| `--d3-only` | PASS (23 passed / 0 unexpected) |
| `image-budget` mobile-360 | PASS (6/6) |
| `pdp-layout` + `pdp-sticky` | PASS (D2A green; D2B green after focus harden) |

## Full `--milestone-d` (stabilization → release)

| Field | Value |
|---|---|
| First full run (2026-08-03) | **FAIL** — 248 passed / 4 failed / 1 flaky / 272 skipped |
| Failure cause | `prod-variable` scroll height rose after D2A unhid related/upsells (intentional) |
| Amendment | Recaptured `fixtures/budgets.json`; sticky focus + card-interaction + baseline harness harden |
| Classification | See [milestone-D-failure-classification.md](milestone-D-failure-classification.md) |
| **Final green run (accepted)** | **2026-08-04** — **253 passed / 0 failed / 0 flaky / 272 skipped** |
| Acceptance commit (baseline harden) | `1af21d1` on `storefront-acceptance` `main` |
| Unexpected regressions | **None** |

## Release closure

Tags and ZIPs published 2026-08-04. **Production replay has not occurred.**

See [deployment/milestone-D.md](../deployment/milestone-D.md).

## KPI notes

Hero height caps and first-product scroll budgets remain within Milestone A/B targets; PDP related cards now visible on mobile (previously Blocksy-hidden).
