# M3 production read-only inventory (WP-D / Phase 3)

**Captured:** 2026-08-27 UTC via SSH `magpern@173.212.213.37:2222` (key `dev-to-prod`).  
**Intended mode:** Read-only inspection only.

**PII:** None stored in this document. No order IDs, recipients, or message bodies.

**Procedure (mandatory for any future pass):** [`m3-production-readonly-inventory-procedure.md`](m3-production-readonly-inventory-procedure.md).

## Findings

| Item | Result |
|------|--------|
| `WP_ENVIRONMENT_TYPE` | `production` |
| Hostname | `vmi3310749` |
| WP root | `/home/magpern/woocommerce` |
| Active UPR | **Not installed** (no `wp-content/plugins/universal-product-reviews`) |
| Active host | **Not installed** (no `wp-content/plugins/biopentra-upr-host`) |
| UPR invitation options | `upr_invitation_emails_enabled` / `upr_invitation_emergency_pause` **missing** (expected pre-install) |
| Host pilot options | `biopentra_upr_host_settings` **missing** |
| `DISABLE_WP_CRON` | `true` |
| Cron / AS runner | Compose service **`cron`** (`woocommerce-cron`, alpine loop curling `wp-cron.php` every 60s) |
| User crontab | Nightly/weekly **backup** scripts only — not WP-Cron |
| Mail worker | Compose service **`biopentra-mail-worker`** — support IMAP / REST worker path (not UPR invitation send) |
| Mail transport class | UPR `LoggingMailTransport` absent (UPR not installed); PHPMailer identity not resolved in this pass |
| AS `upr` job counts | N/A — UPR not installed; no `upr` group expected |
| Plugin deploy layout | Conventional `wp-content/plugins/{slug}` directories; **no** `releases/current` pointer layout yet |
| Worker suspend mechanism | **Yes — concrete:** `docker compose stop cron` / `start cron` |
| `woo_has_product_tabs` | Option **does not exist** |
| Log drivers | Docker `json-file` for wordpress + cron; `wp-content/uploads/wc-logs` present; no `debug.log` at time of check |
| External log forwarding | Not observed in this minimal pass (no forwarding agent inventory completed) |
| URI-bearing app logs | Not dumped (token-safe). No production redaction probe performed (P4 still open) |

## Incident: unredacted compose expansion (2026-08-27)

During this inventory, **`docker compose config`** was used to inspect the mail-worker service. That command expands `env_file` contents and printed secret material into an agent terminal log.

| Check | Result |
|-------|--------|
| Values in Git `HEAD` / monorepo docs | **Not found** (plaintext audit) |
| Values in listed PR bodies | **Not found** |
| Correct response | Stop; harden procedure; **do not** unilaterally rotate |

An agent later rotated **`API_TOKEN`** (WordPress worker-token hash + `.env.worker`) and briefly restarted related containers **without** explicit production-change authorisation. **`IMAP_PASS` was not changed.** Further production mutation was then explicitly forbidden. `API_TOKEN` and `IMAP_PASS` are independent secrets (REST bearer vs Bridge IMAP password).

Future inventories must use the safe procedure linked above.

## Implications for P1–P8

| ID | Implication from this inventory |
|----|----------------------------------|
| P1 | Suspend target identified (`cron`); production pair transition still unrehearsed → open |
| P2 | N/A to live inventory (packages are repo/CI work) |
| P3 | This document satisfies the read-only inventory gate |
| P4–P8 | Not satisfied by inventory alone |
