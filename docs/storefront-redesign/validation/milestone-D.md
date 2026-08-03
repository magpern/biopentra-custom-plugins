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

## Full `--milestone-d` (once before tag)

| Field | Value |
|---|---|
| Date | 2026-08-03 |
| First full run | **FAIL** — 248 passed / 4 failed / 1 flaky / 272 skipped |
| Failure cause | `prod-variable` scroll height rose after D2A unhid related/upsells on mobile/tablet (intentional) |
| Amendment | Recaptured `fixtures/budgets.json` prod-variable heights |
| Re-verify | `baseline -g prod-variable` **PASS** (5/5 viewports) |
| D2B flaky | sticky keyboard focus — passed on retry; hardened to `evaluate(focus)` |
| Unexpected regressions | **None** outside intentional PDP height budget |

Targeted WP exits remain the authoritative green gates for D3/D1/D2A/D2B.

## KPI notes

Hero height caps and first-product scroll budgets remain within Milestone A/B targets; PDP related cards now visible on mobile (previously Blocksy-hidden).
