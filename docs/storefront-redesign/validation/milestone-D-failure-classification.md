# Milestone D — Failure classification (stabilization)

**Date:** 2026-08-04  
**Context:** Final green-run pass before tags.

| Failure | Classification | Action |
|---|---|---|
| `baseline` `prod-variable` scroll height (mobile/tablet) | **Expected behavioural change** | D2A unhides related/upsells previously `ct-hidden-sm/md`. Recaptured `fixtures/budgets.json`. |
| `pdp-sticky` keyboard focus (flaky) | **Test defect + implementation race** | Variation `syncAtc()` toggled `disabled` and dropped focus; IO hide flicker. Fixed: mutate disabled only when changed; debounce hide 150ms; harden test with `expect.poll` + overlay re-dismiss. Verified 3× with `--retries=0`. |
| `card-interaction` beforeEach enhance timeout (mobile-390) | **Test harness flake** | `beforeEach` used cookie+goto without Coming Soon retry; long multi-viewport runs hit placeholder. Fixed: `gotoWithComingSoonBypass` + reload/retry wait for enhanced cards. |
| Coming Soon gated pages mid-suite | **Test harness flake (not D regression)** | Share-key races historically; mitigated by goto helper retries. |

No legitimate product regressions identified for Milestone D scope.
