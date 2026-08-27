# M3 coordinated UPR+host pair transition (WP-A)

**Status:** Repository tooling + disposable rehearsal. **Production cutover NO-GO** until P1–P8 and a separate execute order.  
**Does not:** enable emails, populate allowlists, run write reconciliation, deploy to DEV WordPress, or mutate production.

**Target pair:** UPR `v0.3.0` / `b2abc2defc30fc023601593aa1720cbfdd0a4f3c` + `upr-host-adapter` `v0.1.1` / `5bc67c7dd7946178b75e1b7d91d32d1e2074296e`.  
**Superseded:** `biopentra-upr-host` **0.1.5** (tooling refuses that identity).

## Layout

Scripts: `scripts/upr-pair-transition/`

| Script | Role |
|--------|------|
| `stage-release.sh` | SHA-verify ZIP → `PAIR_ROOT/releases/{slug}/{version}/` + per-tree `.SHA256SUMS` |
| `pair-transition.sh` | Fixed forward: suspend → (maint) → **place both** → **activate UPR** → **activate host** → verify → emails still off → (end maint) → resume |
| `pair-rollback.sh` | Fixed rollback: suspend → (maint) → **deactivate host** → **deactivate UPR** → restore previous/absent pointers → verify → emails still off → resume |
| `rehearse-disposable.sh` | Non-WordPress rehearsal of transition + rollback-to-absent + suspend-fail abort + superseded-identity refuse |
| `config.example.env` | Operator config template (worker + activate/deactivate command slots) |
| `../validate-m3-pair-packages.sh` | Disposable ZIP + pin accept/tamper-deny for the exact target pair |

Pointers: `PAIR_ROOT/pointers/{slug}/current|previous`. Live plugins are symlinks under `PLUGINS_LINK_ROOT`.

Defaults: `HOST_SLUG=upr-host-adapter`, `HOST_VERSION=0.1.1`. Superseded `biopentra-upr-host` / `0.1.5` is **refused**.

**Staging ≠ activation.** Filesystem placement of both trees happens before any `wp plugin activate`. Host `Requires Plugins: universal-product-reviews` → UPR must activate first.

## Mandatory worker control

Unset or failing `WORKER_SUSPEND_CMD` / `WORKER_PROOF_CMD` / `WORKER_RESUME_CMD` **aborts before any pointer switch**.

Proof command must print `upr_send_active=0` and exit 0.

Activate slots (`ACTIVATE_UPR_CMD`, `ACTIVATE_HOST_CMD`) and deactivate slots (`DEACTIVATE_HOST_CMD`, `DEACTIVATE_UPR_CMD`) are required for real WordPress cutover; disposable rehearsal uses safety-file stubs.

### Production draft literals (inventory 2026-08-27 — not production-rehearsed)

Compose root: `/home/magpern/woocommerce`.

| Slot | Draft command |
|------|----------------|
| Suspend | `cd /home/magpern/woocommerce && docker compose stop cron` |
| Resume | `cd /home/magpern/woocommerce && docker compose start cron` |
| Proof | `./wp eval` counting pending/in-progress `upr_send_*` / `upr_schedule_order_items` in AS group `upr` → `upr_send_active=0` |

`cron` service hits `http://wordpress/wp-cron.php?doing_wp_cron` every 60s while `DISABLE_WP_CRON=true`. Stopping `cron` is the enforceable Action Scheduler / WP-Cron suspend mechanism.

`biopentra-mail-worker` is the **support IMAP** importer, not the UPR invitation send path (UPR → `wp_mail`). It is **omitted** from the mandatory UPR suspend list.

User crontab contains backup jobs only (not WP-Cron).

**Until these literals are operator-locked and rehearsed on production under a separate execute order, P1 remains unmet for live cutover.**

## Forward order (normative)

1. Emails off / pilot false / allowlist empty  
2. Stage + checksum both packages  
3. Suspend cron + proof `upr_send_active=0`  
4. Place both pointers (filesystem)  
5. Activate **UPR first**  
6. Activate **host second**  
7. Verify pins + meta + emails off + pilot deny  
8. Resume cron  

## Rollback-to-absent (normative)

1. Emails off  
2. Suspend + proof  
3. Deactivate **host first**  
4. Deactivate **UPR second**  
5. Remove pointers to **absent**  
6. Verify absent  
7. Resume cron  

## Disposable rehearsal

```bash
# With WP-B packages available (v0.1.1 pair):
bash scripts/upr-pair-transition/rehearse-disposable.sh
```

Does not modify `/opt/biopentra` DEV WordPress runtime.

## Forbidden

- Enabling `upr_invitation_emails_enabled`
- Populating pilot allowlist / authorising pilot send
- Write reconciliation / customer contact
- Activating host before UPR
- Improvising rollback order (must be host deactivate → UPR deactivate → absent/previous)
- Deploying superseded `biopentra-upr-host` / `0.1.5`
- Public GitHub Release of packages
