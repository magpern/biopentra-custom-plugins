# UPR-core correction proposal — catalogue-hidden discontinued products

**Status:** Proposed (blocks M3 DEV pilot / host deploy per WP6 gate)  
**Against:** `universal-product-reviews` `v0.2.0` @ `4eb1f965d5ab87d8d1e1479257fbb8721f25ce41`  
**Evidence:** [`m3-wp6-discontinued-verification.md`](m3-wp6-discontinued-verification.md)

## Problem

Catalogue-hidden products that remain `publish` are still reviewable in UPR v0.2.0. Core only checks `post_status === publish`. Visibility changes do not revoke tokens or suppress invitations.

Biopentra M3 host policy requires catalogue-hidden to behave as discontinued (close submissions, revoke tokens/sessions, stop future invites; keep approved reviews visible).

## Smallest correction (UPR core)

1. `ProductReviewability::is_reviewable()` — treat WC catalogue visibility `hidden` as not reviewable by default (still filterable via `upr_product_is_reviewable`).
2. On catalogue visibility change to hidden, call `SuppressionService::suppress_product_not_reviewable()`.
3. Default `ReviewAvailability` consults `ProductReviewability` so `can_submit` is false when not reviewable.
4. Integration tests: catalogue-hidden mandatory matrix (exchange, form, submit, revoke, future-send).

## Out of scope for host

Do **not** reimplement this lifecycle in `biopentra-upr-host`.

## Release / pin

Ship as UPR patch release (suggested `v0.2.1`). M3 host pins that version before DEV pilot.
