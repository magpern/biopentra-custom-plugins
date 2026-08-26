# M3 DEV replay checklist

**Status:** Checklist template at freeze; fill evidence during Phase 3 execution.  
**Site:** `https://dev.biopentra.eu`  
**UPR pin:** annotated tag `v0.2.1` @ `e5b9636a42db7aaf0837c7b6034a24b062fd4275` — see [`m3-dev-upr-v0.2.1-pin.md`](m3-dev-upr-v0.2.1-pin.md)

**Boundary:** DEV only. No production host, database, deployment, or configuration changes.

---

## 0. Preconditions

- [ ] Authoritative freeze tag `upr-m3-host-integration-freeze` present on `biopentra-custom-plugins`
- [ ] UPR remains generic (no host commits in UPR tree)
- [ ] `WP_ENVIRONMENT_TYPE=development` set for WordPress **and** WP-CLI containers
- [ ] External cron `scripts/wp-cron.sh` active (`DISABLE_WP_CRON`)

---

## 1. Dependency order

1. [ ] MPCF lifecycle release / bind-mount / activation (`mpcf_fulfillment_state_changed`)
2. [ ] `biopentra-upr-host` bind-mount / activation
3. [ ] Compose env update (`WP_ENVIRONMENT_TYPE=development`)
4. [ ] Nginx / SWAG token redaction reload (DEV only)
5. [ ] Storefront / blocksy-child / loop-card updates
6. [ ] Controlled DEV mail verification CLI
7. [ ] Action Scheduler `upr` group drain via external cron
8. [ ] Schema + sticky regression acceptance

---

## 2. Validation matrix

| Check | Pass criteria | Evidence |
|-------|---------------|----------|
| No production change | Only DEV stack touched | |
| UPR unchanged | Pinned `v0.2.1` @ `e5b9636…` (preflight CLI) | |
| MPCF UPR-unaware | No `upr_*` in MPCF | |
| Lifecycle emit | After full success only | |
| Path matrix | Admin, REST, CLI (if any), recovery/refund → `WorkflowService::transition()` | |
| Listener isolation | Thrown host listener does not fail MPCF success | |
| Hybrid delivery | Sources `delivered` / `shipped_fallback`; idempotent shipped→delivered | |
| Support | Delay/suppress allowlists; free text ignored; lookup failure → `delay` | |
| DEV mail | CLI refuses when env ≠ `development`; LoggingMailTransport proven | |
| Token redaction | Raw token absent from all URI-bearing access/cache logs; no external raw URI | |
| Discontinued | Catalogue-hidden **mandatory** + draft/private/trash | Pass after UPR v0.2.1 + `verify-wp6-dev` |
| PDP UX | Summary + availability hooks; sticky unchanged | |
| Cards | Flag off; ≥3 threshold when enabled | |
| Schema | Exactly one Product; no AggregateRating without approved reviews | |
| AS drain | `upr` jobs process with existing cron | |

---

## 3. WP6 gate

If catalogue-hidden or other discontinued cases fail against UPR public behaviour:

- Record precise evidence.
- Do **not** patch around in the host plugin.
- Block DEV pilot and host deployment until corrected UPR is planned, frozen, implemented, released, and host-pinned.
- Continue independent completed M3 engineering work; note blocker in closure.

---

## 4. Explicit non-actions

- No GitHub Release / ZIP / production version tag for M3 closure.
- No production deploy.
- No sticky buy-bar DOM/selector/JS changes.
