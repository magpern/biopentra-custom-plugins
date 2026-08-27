# M3 — Invitation email controls: host pilot send policy

**Status:** Frozen authoritative host specification for the temporary production-pilot invitation send policy.  
**Freeze branch:** `docs/m3-invitation-email-controls-freeze`  
**Generic freeze:** `magpern/universal-product-reviews` → [`docs/roadmap/m3-invitation-email-controls.md`](https://github.com/magpern/universal-product-reviews/blob/main/docs/roadmap/m3-invitation-email-controls.md) (tag `upr-invitation-email-controls-freeze` after UPR docs merge).  
**Implementation lives in:** `plugins/biopentra-upr-host/` only.  
**Depends on:** UPR generic contract `upr_invitation_send_authorisation` (target UPR minor `v0.3.0`). Host must not merge or activate against production until that UPR release is available and pinned.

**Production / DEV rollout:** Not authorised by this document. No bind-mount, plugin activation, WordPress setting mutation, real email, GitHub Release, ZIP, or production deploy is part of this work package.

---

## 1. Ownership boundary (locked)

| Concern | Owner |
|---------|-------|
| Master “Enable review invitation emails” | **UPR core** |
| “Emergency pause invitations” + token/session revocation | **UPR core** |
| Invitation state machine, scheduling, send claims, mail transport decision | **UPR core** |
| Native submission guards / M2 guest auth | **UPR core** |
| Temporary pilot order-ID allowlist + “pilot sending authorised” | **`biopentra-upr-host`** |
| Host UI/CLI for pilot policy only | **`biopentra-upr-host`** |

The host adapter **must not** duplicate UPR master controls, emergency pause, native submission guard, or invitation state machine.

The allowlist is a **temporary limited-rollout control**, not a replacement for generic UPR settings. Operators must still use UPR enable + pause correctly.

---

## 2. Generic contract (host implements)

UPR filter (normative; defined in UPR freeze):

```php
apply_filters( 'upr_invitation_send_authorisation', $decision, $context );
```

Context (generic IDs only):

```text
order_id
order_item_id
product_id
operation: schedule | initial_send | reminder_send
```

Decision vocabulary:

```text
allow | email_disabled | paused | not_authorised
```

### Host rules

1. Register the filter **only when** the UPR authorisation API/contract is available (e.g. `InvitationAuthorisation` class exists **or** documented filter is known present on a pinned UPR ≥ contract version). If unavailable: **fail closed** (do not claim allow), and show an **administrator-visible** dependency/status message on the UPR Host settings page.
2. When UPR already decided `email_disabled` or `paused`, the host filter is not required to run (UPR skips host on core deny). Host code must still never attempt to upgrade those decisions.
3. When provisional decision is `allow`, host returns:
   - `allow` only if **both** pilot authorisation is true **and** `order_id` is on the allowlist;
   - otherwise `not_authorised` with a stable `reason_code` (e.g. `pilot_not_authorised`, `pilot_order_not_allowlisted`).
4. Do not store or log raw email addresses merely to define the cohort — **order IDs only**.
5. Changing the allowlist must **not** automatically set authorisation true.

---

## 3. Exact host settings

Stored in existing options array `biopentra_upr_host_settings` (see `Biopentra_Upr_Host_Options`), additive keys:

| UI label | Array key | Type | Default |
|----------|-----------|------|---------|
| Pilot invitation sending authorised | `pilot_invitation_sending_authorised` | bool | **false** |
| Pilot order-ID allowlist | `pilot_order_id_allowlist` | list&lt;int&gt; | **empty** (empty denies) |

Capability: **`manage_woocommerce`** (existing UPR Host admin page).

### Fail-closed matrix

| Authorised | Allowlist | Host decision for otherwise-allowed UPR work |
|------------|-----------|-----------------------------------------------|
| false | any | `not_authorised` |
| true | empty | `not_authorised` |
| true | order ID present | `allow` |
| true | order ID absent | `not_authorised` |

Sanitization: allowlist is positive integers only; duplicates removed; invalid tokens dropped. Saving allowlist alone never flips `pilot_invitation_sending_authorised` to true.

UI copy must state clearly that this is a temporary limited-rollout control and does not replace UPR’s “Enable review invitation emails” or “Emergency pause invitations”.

---

## 4. Precedence (operator mental model)

```text
UPR emergency pause
  > UPR invitation emails enabled
    > Host pilot authorised + allowlisted order ID
```

Host policy can only further restrict. It cannot send when UPR is disabled or paused.

---

## 5. Implementation sketch (host)

Suggested files under `plugins/biopentra-upr-host/`:

| Piece | Location |
|-------|----------|
| Option defaults + getters | `includes/class-options.php` |
| Sanitize + admin fields + dependency notice | `includes/class-admin-settings.php` |
| Filter registration | new `includes/class-invitation-send-policy.php` registered from `Biopentra_Upr_Host_Plugin::init()` |
| Optional CLI status | `cli/` verify or status command (order IDs + flags only; no emails) |
| Policy script | extend `scripts/` static checks for defaults fail-closed and no duplication of UPR master option keys |

Bootstrap: if UPR contract missing, skip filter registration (or register a hard deny if a stub is required — prefer skip + admin notice; UPR core already fail-closes when master disabled). Host must not fatal.

**Do not** add production deployment/replay logic in this work package.

---

## 6. Test / policy-check matrix

| # | Check |
|---|-------|
| H1 | Defaults: authorisation false, empty allowlist → deny |
| H2 | Empty allowlist with authorisation true → deny |
| H3 | Authorisation false with non-empty allowlist → deny |
| H4 | Authorisation true + allowlisted `order_id` → host returns `allow` |
| H5 | Non-allowlisted order → `not_authorised` |
| H6 | Documentation/policy: host does not define/override UPR master enable or emergency pause options |
| H7 | When UPR contract/API unavailable → fail closed + admin-visible dependency message |
| H8 | Static assertion that host never upgrades `email_disabled`/`paused` (code review + unit if filter wrapper exists) |

Prefer focused PHPUnit or shell policy checks consistent with `scripts/b2-policy-check.sh` style. No DEV deploy required for validation of this PR.

---

## 7. Branch / PR rules

- Branch: `feat/m3-upr-pilot-send-policy`
- PR must **explicitly depend** on the UPR implementation PR / future `v0.3.0` contract.
- **Do not merge** the host PR until the required UPR release is available.
- Do not bind-mount, activate, or deploy the host plugin as part of this package.
- Do not bump UPR pin to `v0.3.0` in the same PR unless that release already exists; if pin update is deferred, document the dependency clearly in the PR body.

---

## 8. Rollback and non-goals

### Rollback

- Leave pilot authorisation false / clear allowlist (fail closed), or revert host PR.
- UPR pause/disable remain the operational emergency controls.

### Non-goals

- Duplicating UPR enable/pause UI in the host plugin.
- Production rollout, DEV activation, real email, Release/ZIP.
- Storing customer emails as the cohort definition.
- Replay/deployment scripts for production pilot execution.

---

## 9. Explicit non-authorisation

**No production rollout is authorised by this freeze.**  
**No DEV deployment, real email send, GitHub Release, ZIP packaging, or UPR release tagging is authorised in this documentation or host implementation PR phase.**

---

## Related

- UPR freeze: `docs/roadmap/m3-invitation-email-controls.md` in `universal-product-reviews`
- [`M3-host-integration.md`](M3-host-integration.md)
- [`b2-upr-v0.2.2-host-integration.md`](b2-upr-v0.2.2-host-integration.md)
- Host plugin: `plugins/biopentra-upr-host/`
