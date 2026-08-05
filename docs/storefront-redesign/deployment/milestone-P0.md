# Milestone P0 — Production Rollout

**Status:** PLAYBOOK READY — **not executed**  
**Full playbook:** [../plans/MILESTONE_P0_PRODUCTION_ROLLOUT.md](../plans/MILESTONE_P0_PRODUCTION_ROLLOUT.md)

This file is the deployment-record stub. Fill it during/after the production window. Do not deploy until explicitly prompted.

## Target packages

| Package | Version | Tag |
|---|---|---|
| `biopentra-storefront` | 0.9.0 | `storefront-v0.9.0` |
| `biopentra-loop-card` | 1.6.1 | `v1.6.1` |
| `biopentra-blocksy-child` | 1.1.0 | `v1.1.0` |

## Order (summary)

1. Preflight + backups  
2. Stage ZIPs + CLIs (ZIPs exclude `scripts/`)  
3. Install packages  
4. Elementor A → B  
5. SEO migrate + validate (0 errors)  
6. Elementor E (production Theme Builder IDs)  
7. Flush trio + Cloudflare  
8. `run-prod.sh` + mobile smoke  
9. Sign-off  

## Execution

See playbook §15–§16. **Production replay has not started.**
