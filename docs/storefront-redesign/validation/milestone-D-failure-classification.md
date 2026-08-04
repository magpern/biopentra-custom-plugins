# Milestone D — Failure classification (stabilization)

**Date:** 2026-08-04  
**Context:** Final green-run pass before tags.

| Failure | Classification | Action |
|---|---|---|
| `baseline` `prod-variable` scroll height (mobile/tablet) | **Expected behavioural change** | D2A unhides related/upsells previously `ct-hidden-sm/md`. Recaptured `fixtures/budgets.json`. |
| `pdp-sticky` keyboard focus (flaky) | **Test defect + implementation race** | Variation `syncAtc()` toggled `disabled` and dropped focus; IO hide flicker. Fixed: mutate disabled only when changed; debounce hide 150ms; harden test with `expect.poll` + overlay re-dismiss. |
| Coming Soon gated pages mid-suite | **Test harness flake (not D regression)** | Share-key races historically; not reproduced after budget/sticky fixes in targeted re-runs. Full suite must be green with no accepted flakes. |

No legitimate product regressions identified for Milestone D scope.
