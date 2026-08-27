# M3 production read-only inventory (WP-D / Phase 3)

**Captured:** 2026-08-27 UTC via SSH `magpern@173.212.213.37:2222` (key `dev-to-prod`).  
**Mode:** Read-only inspection only. No settings writes, no fixtures, no token HTTP probe, no mail, no Action Scheduler enqueue, no filesystem deploy.

**PII:** None stored. No order IDs, recipients, or message bodies.

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
| Mail worker | Compose service **`biopentra-mail-worker`** — support IMAP → Fluent/support API (not UPR invitation send path) |
| Mail transport class | UPR `LoggingMailTransport` absent (UPR not installed); PHPMailer identity not resolved in this pass |
| AS `upr` job counts | N/A — UPR not installed; no `upr` group expected |
| Plugin deploy layout | Conventional `wp-content/plugins/{slug}` directories; **no** `releases/current` pointer layout yet |
| Worker suspend mechanism | **Yes — concrete:** `docker compose stop cron` / `start cron` |
| `woo_has_product_tabs` | Option **does not exist** |
| Log drivers | Docker `json-file` for wordpress + cron; `wp-content/uploads/wc-logs` present; no `debug.log` at time of check |
| External log forwarding | Not observed in this minimal pass (no forwarding agent inventory completed) |
| URI-bearing app logs | Not dumped (token-safe). No production redaction probe performed |

## Implications for P1–P8

- Worker suspension is **concretely implementable** via compose `cron`.
- Packages + host 0.1.5 pin + pair scripts exist in git; production still lacks UPR/host and release-dir layout.
- Emergency-pause synthetic fixture, token redaction probe, and customer pilot remain **later** gates — not executed here.
