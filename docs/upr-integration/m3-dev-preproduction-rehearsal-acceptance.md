# M3 DEV pre-production invitation-email rehearsal — closure

**Verdict:** **PASS — DEV PRE-PRODUCTION REHEARSAL ACCEPTED**  
**Date:** 2026-08-27  
**Site:** `https://dev.biopentra.eu` (hostname `vmi3436559`)  
**Authority:** freeze tag `m3-dev-preproduction-rehearsal-freeze` → `6d71d607c7d31fe7654b2b76fc5b019533854673`  
**Specification:** [`m3-dev-preproduction-rehearsal.md`](m3-dev-preproduction-rehearsal.md)  
**R9 addendum:** 2026-08-27 — full URI-bearing DEV log coverage (`access.log` + `bp-cache.log`) and external-sink confirmation (this document).

**Production:** Untouched — no production host, database, WordPress, proxy/cache, Cloudflare, DNS, real mail transport, Release, ZIP, or customer data access.

---

## Pins exercised

| Component | Value |
|-----------|--------|
| UPR | `0.3.0` / annotated `v0.3.0` / `b2abc2defc30fc023601593aa1720cbfdd0a4f3c` |
| Host | `biopentra-upr-host` **0.1.4** @ custom-plugins including `13c9b4a79aa0cc980b2dc500565c4c2b56a89e4d` |
| Storefront | `0.9.39` (unchanged) |
| Env (CLI + wordpress container) | `development` |
| Mail transport | `UniversalProductReviews\Email\LoggingMailTransport` |
| Tabs theme_mod | `woo_has_product_tabs=no` |

---

## Preflight

- `verify-pilot-preflight` → PASS (`UPR=0.3.0 @ b2abc2d…`, `host=0.1.4`)
- `verify-dev-mail` → PASS (`LoggingMailTransport`)
- Public listeners: `2222` / `80` / `443` only
- Baseline cancelled stale pending UPR AS send/reconcile jobs (44 → 0) before controlled enable
- Invitation emails were **disabled**; pilot allowlist empty; pause off

---

## Proof matrix

| Gate | Result |
|------|--------|
| R1 master disabled blocks schedule/send (`email_disabled`, no sent-state) | **PASS** |
| R2 emergency pause blocks send, revokes tokens/sessions, unpause no retro-send | **PASS** |
| R3 host allowlist denies empty/other order IDs (`not_authorised`) | **PASS** |
| R4 exactly one allowlisted fixture order completed LoggingMailTransport path (`@example.invalid`) | **PASS** |
| R4b non-allowlisted peer did not send | **PASS** |
| R5 host cannot upgrade `email_disabled` / `paused` / `not_authorised` / `outside_scheduling_boundary` | **PASS** |
| R6 LoggingMailTransport only | **PASS** |
| R7 no historical backfill after enable (reconcile `authorisation_denied_skipped=32`) | **PASS** |
| R8 AS pending send probe drained to 0 | **PASS** |
| R9 token redaction on **all** active URI-bearing DEV access logs (`access.log` / `bp_combined` **and** `bp-cache.log` / `bp_cache_dev`); form path readable; no external log sink | **PASS** (see § R9 below) |
| R10 desktop reviews (8), mobile a11y (5), sticky (3), schema (1) | **PASS** |
| R10 `verify-wp6-dev` (8/8) | **PASS** |
| R10 `verify-pilot-preflight` / `verify-dev-mail` | **PASS** |
| R10 `verify-token-redaction-dev` | **PASS with caveat** — see § R9 (WP context could not read SWAG files; host-path proof completed) |

Fixture recipients used **`@example.invalid` only**. Evidence records order IDs / decision codes / token **SHA-256** only — no customer PII; raw probe tokens are not recorded here.

---

## R9 — Token redaction (full DEV log coverage)

### What `verify-token-redaction-dev` is designed to check

CLI: `wp biopentra-upr-host verify-token-redaction-dev`  
Source: `plugins/biopentra-upr-host/cli/class-verify-token-redaction-dev-command.php`

| Check | Intended behaviour |
|-------|--------------------|
| Environment gate | Refuses unless `wp_get_environment_type() === 'development'` |
| Probe | `HEAD` `/upr-review/{token}/` and `/upr-review/form/` |
| Log files | **`access.log`** and **`bp-cache.log`** under SWAG log root (`BIOPENTRA_SWAG_LOG_ROOT` or `/opt/biopentra/proxy/config/log/nginx`) |
| Pass criteria per readable file | Raw probe token **absent** from recent tail; if `upr-review` appears, `[redacted]` marker present; warns if form path missing from tail |

**Rehearsal run caveat:** during the first R10 invocation the CLI warned that both log files were **not readable from the WP container context**, skipped them, and still printed Success for “readable” files (empty set). That alone is **not** sufficient evidence. Host-path verification below is authoritative for this closure.

### Live DEV URI-bearing log inventory

Active `log_format` definitions under `/opt/biopentra/proxy/config/nginx/site-confs/` (excluding `*.disabled`):

| Format | File | URI field | Wired by live `access_log`? |
|--------|------|-----------|------------------------------|
| `bp_combined` | `biopentra-log-redaction.conf` | `$bp_log_request_uri` | **Yes** → `/config/log/nginx/access.log` (HTTP + HTTPS server in `default.conf`) |
| `bp_cache_dev` | `bp-cache-dev-testing.conf` | `$bp_log_request_uri` | **Yes** → `/config/log/nginx/bp-cache.log` (HTTPS server in `default.conf`) |
| `bp_cache` | `bp-cache-policy.conf` | `$bp_log_request_uri` | Defined (F2 base); **not** selected by current `access_log` (superseded by `bp_cache_dev`) |

Shared map (logging only): `map $request_uri $bp_log_request_uri` redacts `/upr-review/{token}/` → `/upr-review/[redacted]/`; keeps `/upr-review/form/` readable.

**Note:** SWAG `nginx.conf` still has http-level `access_log ... access.log;` (stock, no format). Server-level `access_log ... bp_combined` in `default.conf` **replaces** that inheritance for the public site servers (documented in-file). No other site-conf `access_log` targets exist for the public HTTPS vhost.

### Host-path probe evidence (authoritative)

Synthetic `HEAD` probes to invite + form paths (token not stored; `sha256=6339edaf808750328e6483f814176598985de9f8746b185b333b922d358924c5`).

| Log file | Format | Raw token hits | Invite path logged as | Form path |
|----------|--------|----------------|-----------------------|-----------|
| `access.log` | `bp_combined` | **0** | `HEAD /upr-review/[redacted]/` | `HEAD /upr-review/form/` present |
| `bp-cache.log` | `bp_cache_dev` | **0** | `HEAD https://dev.biopentra.eu/upr-review/[redacted]/` | `.../upr-review/form/` present |

Example lines (already redacted; no bearer secret):

```text
# access.log
… "HEAD /upr-review/[redacted]/ HTTP/2.0" 404 …
… "HEAD /upr-review/form/ HTTP/2.0" 403 …

# bp-cache.log
… HEAD https://dev.biopentra.eu/upr-review/[redacted]/ status=404 …
… HEAD https://dev.biopentra.eu/upr-review/form/ status=403 …
```

### External logging sinks (DEV)

Config-only scan of DEV SWAG nginx tree (`nginx.conf`, `site-confs/`, `proxy-confs/`; excluding log bodies and `*.disabled`): **no** Cloudflare Logpush, Datadog, Fluent Bit, Vector, Elastic/OpenSearch, Loki, Splunk, Filebeat, Promtail, or rsyslog forwarder configuration found.

Access/cache logs remain **local files** under the SWAG volume (`/opt/biopentra/proxy/config/log/nginx/`: `access.log*`, `bp-cache.log*`, `error.log*`). Cloudflare sits as orange-cloud CDN/proxy for TLS; it is **not** configured here as a log shipping sink for request URIs.

**R9 verdict:** **PASS** for every configured URI-bearing DEV access log format that is actively written (`bp_combined` → `access.log`, `bp_cache_dev` → `bp-cache.log`). Unused-but-defined `bp_cache` also uses `$bp_log_request_uri` (structural). No external URI-bearing sink found on DEV SWAG.

---

## Safe DEV restore (post-rehearsal)

| Control | Final value |
|---------|-------------|
| `upr_invitation_emails_enabled` | **no** |
| Emergency pause | **no** |
| Pilot authorised | **no** |
| Pilot allowlist | **empty** |
| UPR AS pending/in-progress | **0** (after cancel) |
| Mail transport | `LoggingMailTransport` |
| UPR pin | still `v0.3.0` @ `b2abc2d…` |

Rollback path used matches the freeze §5 (disable master, clear allowlist, cancel UPR jobs). Emergency pause was exercised during R2 and left **off** afterward.

---

## Explicit non-claims

- Does **not** authorise production rollout.
- Does **not** create GitHub Release / ZIP / new version tags.
- Google rich-result eligibility is not claimed.
- Rehearsal temporary PHP helpers under host `scripts/rehearsal-tmp/` were removed and not committed.

---

## Next step

Production rollout remains a **separate approval** gated by the blocked production plan (atomic deploy, live inventory, recorded operator approvals). This closure only accepts the **DEV** pre-production invitation-email rehearsal.
