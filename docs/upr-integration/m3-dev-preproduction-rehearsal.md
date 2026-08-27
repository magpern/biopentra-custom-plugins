# M3 — DEV pre-production invitation-email rehearsal (freeze)

**Status:** Frozen authoritative DEV rehearsal plan.  
**Freeze tag:** `m3-dev-preproduction-rehearsal-freeze` (annotated; points at this documentation merge).  
**Site:** `https://dev.biopentra.eu` only.  
**Production:** Explicitly **prohibited**. No production host, database, WordPress, proxy, cron, DNS, Cloudflare, GitHub Release, ZIP, or configuration change is authorised by this document.

**This document freezes the plan only. Do not execute the rehearsal until a separate explicit operator instruction.**

---

## 1. Purpose

Controlled, **limited invitation-email pilot rehearsal** on DEV as the pre-production environment, after:

| Artifact | Reference |
|----------|-----------|
| UPR invitation-email controls | `magpern/universal-product-reviews` PR #18 → merge `b2abc2defc30fc023601593aa1720cbfdd0a4f3c` |
| Annotated UPR release tag | **`v0.3.0`** (peels exactly to that merge) |
| Host pilot send policy + pin | `magpern/biopentra-custom-plugins` PR #18 → merge `13c9b4a79aa0cc980b2dc500565c4c2b56a89e4d` |
| Host version | `biopentra-upr-host` **0.1.4** |
| Prior B4 PDP pilot | [`m3-b4-dev-pilot-acceptance.md`](m3-b4-dev-pilot-acceptance.md) (**PASS — DEV PILOT ACCEPTED**) |

---

## 2. Pins (mandatory)

| Component | Required |
|-----------|----------|
| UPR version | **`0.3.0`** |
| UPR annotated tag | **`v0.3.0`** |
| UPR peeled commit | **`b2abc2defc30fc023601593aa1720cbfdd0a4f3c`** |
| UPR API | `InvitationAuthorisation` + master enable / emergency pause / scheduling boundary |
| Host pin constants | Match the above (`Biopentra_Upr_Host_Upr_Pin`) |
| Host policy | Order-ID allowlist + `pilot_invitation_sending_authorised` (default deny) |

Invitation emails remain **disabled by default** (`upr_invitation_emails_enabled` absent/false).

---

## 3. Operator prerequisites before any controlled send

Before enabling any controlled invitation path on DEV, the operator **must** explicitly:

1. Verify `wp_get_environment_type() === 'development'`.
2. Verify invitation mail uses **`LoggingMailTransport`** (e.g. `wp biopentra-upr-host verify-dev-mail`).
3. Configure a **minimal, temporary DEV-only order-ID allowlist** (fixture order(s) only).
4. Enable the generic UPR master setting **Enable review invitation emails**.
5. Confirm emergency pause is **off**.
6. Record the current **`upr_invitation_scheduling_boundary_at`** (scheduling boundary).
7. Use only **`@example.invalid`** fixture recipients (no real customer addresses).

Recorded operator approval (ticket/ops note) is required before step 4. Do not invent speculative multi-person technical controls.

---

## 4. Rehearsal proof matrix (must all pass)

| # | Proof |
|---|--------|
| R1 | Master invitation emails **disabled** blocks schedule and send (`email_disabled`; no sent-state). |
| R2 | Emergency **pause** blocks send, **revokes** outstanding tokens/sessions, and after **unpause** does **not** retro-send pre-boundary work (`paused` + scheduling boundary). |
| R3 | Host allowlist **denies** non-allowlisted orders (`not_authorised` / `pilot_order_not_allowlisted`). |
| R4 | Exactly **one** allowlisted fixture order can complete the intended controlled LoggingMailTransport path. |
| R5 | Host **cannot** override generic `email_disabled`, `paused`, or `outside_scheduling_boundary` into `allow`. |
| R6 | **No** real mail transport (`WpMailTransport` / `wp_mail` to real inboxes) is used. |
| R7 | **No** historical order is backfilled after enable or unpause. |
| R8 | Scheduler jobs in group `upr` **drain** (controlled probes complete or cancel cleanly). |
| R9 | Token-path log **redaction** remains valid (`/upr-review/{token}/` → `[redacted]`; `/upr-review/form/` readable). |
| R10 | Existing M3 gates remain intact: PDP dedicated `#reviews`, native submission, catalogue-hidden/WP6, mobile/accessibility, sticky-buy-bar, Rank Math Product schema. |

Use targeted DEV runners only (`tools/run-dev-playwright.sh`, host verify CLIs). No full suite by default. No production `run-prod.sh` as part of this rehearsal.

---

## 5. Rollback (DEV only)

1. Disable master invitation emails (`upr_invitation_emails_enabled` off).
2. Enable emergency pause if an immediate stop is required.
3. Remove / empty the DEV pilot order-ID allowlist; set pilot authorisation **false**.
4. Cancel and verify pending Action Scheduler actions in group `upr`.
5. Restore only DEV configuration (bind-mount HEADs / theme_mod) if the rehearsal mutated them — never production.

---

## 6. Evidence (no customer PII)

Capture in a follow-up closure addendum (separate instruction after rehearsal execution):

- Environment type, UPR version/tag/commit, host version.
- Master enable / pause / scheduling-boundary values (timestamps/IDs only).
- Allowlisted **order IDs** (fixture) — never billing emails in evidence tickets.
- Decision audit samples: `email_disabled`, `paused`, `not_authorised`, `outside_scheduling_boundary`, one controlled `allow`.
- Mail transport class name (`LoggingMailTransport`).
- AS pending/completed counts for group `upr` (no payload PII).
- Redaction proof excerpt with tokens already `[redacted]`.
- Targeted Playwright / host CLI exit codes for R10 gates.
- Explicit statement: production untouched.

---

## 7. Explicit non-authorisation

- **No production rollout.**
- **No GitHub Release / Release ZIP** from this freeze.
- **No rehearsal execution** until a separate explicit instruction cites this freeze tag.
- UPR remains generic; host policy stays order-ID allowlist only.

---

## Related

- UPR: `docs/roadmap/m3-invitation-email-controls.md`, tag `v0.3.0`
- Host policy: [`m3-invitation-email-controls-host-policy.md`](m3-invitation-email-controls-host-policy.md)
- B4 PDP acceptance: [`m3-b4-dev-pilot-acceptance.md`](m3-b4-dev-pilot-acceptance.md)
- Tabs-off DEV replay: [`dev-replay-blocksy-product-tabs.md`](dev-replay-blocksy-product-tabs.md)
