# M3 coordinated UPR+host pair transition (WP-A)

**Status:** Repository tooling + disposable rehearsal. **Production cutover NO-GO** until P1–P8 and a separate execute order.  
**Does not:** enable emails, populate allowlists, run write reconciliation, deploy to DEV WordPress, or mutate production.

## Layout

Scripts: `scripts/upr-pair-transition/`

| Script | Role |
|--------|------|
| `stage-release.sh` | SHA-verify ZIP → `PAIR_ROOT/releases/{slug}/{version}/` + per-tree `.SHA256SUMS` |
| `pair-transition.sh` | Fixed deploy order: suspend → (maint) → **host** → **UPR** → verify → emails still off → (end maint) → resume |
| `pair-rollback.sh` | Fixed rollback: suspend → (maint) → **UPR previous** → **host previous** → verify → emails still off → resume |
| `rehearse-disposable.sh` | Non-WordPress rehearsal of transition + rollback + suspend-fail abort |
| `config.example.env` | Operator config template (worker command slots) |

Pointers: `PAIR_ROOT/pointers/{slug}/current|previous`. Live plugins are symlinks under `PLUGINS_LINK_ROOT`.

## Mandatory worker control

Unset or failing `WORKER_SUSPEND_CMD` / `WORKER_PROOF_CMD` / `WORKER_RESUME_CMD` **aborts before any pointer switch**.

Proof command must print `upr_send_active=0` and exit 0.

### Production draft literals (inventory 2026-08-27 — not production-rehearsed)

Compose root: `/home/magpern/woocommerce`.

| Slot | Draft command |
|------|----------------|
| Suspend | `cd /home/magpern/woocommerce && docker compose stop cron` |
| Resume | `cd /home/magpern/woocommerce && docker compose start cron` |
| Proof | `./wp eval` counting pending/in-progress `upr_send_*` / `upr_schedule_order_items` in AS group `upr` → `upr_send_active=0` |

`cron` service hits `http://wordpress/wp-cron.php?doing_wp_cron` every 60s while `DISABLE_WP_CRON=true`. Stopping `cron` is the enforceable Action Scheduler / WP-Cron suspend mechanism.

`biopentra-mail-worker` is the **support IMAP** importer (Fluent/support path), not the UPR invitation send path (UPR → `wp_mail`). It is **not** in the mandatory UPR suspend list unless a later inventory proves invitation traffic can leave via that worker independently.

User crontab contains backup jobs only (not WP-Cron).

**Until these literals are operator-locked and rehearsed on production under a separate execute order, P1 remains unmet for live cutover** — repository WP-A is implementable; production rehearsal is not part of this initiative.

## Disposable rehearsal

```bash
# With WP-B packages available:
bash scripts/upr-pair-transition/rehearse-disposable.sh
```

Does not modify `/opt/biopentra` DEV WordPress runtime.

## Forbidden

- Enabling `upr_invitation_emails_enabled`
- Populating pilot allowlist / authorising pilot send
- Write reconciliation / customer contact
- Improvising rollback order (must be UPR then host)
- Public GitHub Release of packages
