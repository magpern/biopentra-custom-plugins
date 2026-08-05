# Milestone P0 — Production Rollout

**Status:** **OPERATIONAL FREEZE** — playbook ready; **not executed**  
**Full playbook:** [../plans/MILESTONE_P0_PRODUCTION_ROLLOUT.md](../plans/MILESTONE_P0_PRODUCTION_ROLLOUT.md)

This file is the deployment-record stub. Fill it during/after the production window. Do not deploy until explicitly prompted.

## Target packages

| Package | Version | Tag |
|---|---|---|
| `biopentra-storefront` | 0.9.0 | `storefront-v0.9.0` |
| `biopentra-loop-card` | 1.6.1 | `v1.6.1` |
| `biopentra-blocksy-child` | 1.1.0 | `v1.1.0` |

## Order (summary — unchanged)

1. Preflight + baseline + backups  
2. Stage ZIPs + CLIs  
3. **GO / NO-GO** (mandatory stop gate)  
4. Install packages  
5. Elementor A → B  
6. SEO migrate + validate (0 errors)  
7. Elementor E (production Theme Builder IDs)  
8. Flush trio + Cloudflare  
9. `run-prod.sh` + mobile smoke  
10. Sign-off  

## Execution

See playbook §§0–20 (change freeze, baseline, GO/NO-GO, decision matrix, timeline). **Production replay has not started.**
