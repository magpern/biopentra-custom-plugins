# WP6 — Discontinued lifecycle verification vs UPR v0.2.0

**Date:** 2026-08-26  
**UPR pin:** `v0.2.0` @ `4eb1f965d5ab87d8d1e1479257fbb8721f25ce41`  
**Host policy:** Discontinued includes catalogue-hidden **and** non-published states.

## Method

Code inspection of `ProductReviewability`, `SuppressionService`, token exchange, form session, and submit paths in the pinned UPR tree (no host patches applied to UPR).

## Results

| Case | Exchange blocked | Form/submit closed | Tokens/sessions unusable | Future sends stopped | Approved reviews visible | Result |
|------|------------------|--------------------|--------------------------|----------------------|--------------------------|--------|
| `draft` / leave `publish` | Yes (`upr_product_is_reviewable` default false) | Submit rejects; session invalidate on read | `revoke_for_item` via suppress | Suppress + unschedule | Yes (comments remain) | **PASS** |
| `private` | Yes | Yes | Yes | Yes | Yes | **PASS** |
| `trash` | Yes | Yes | Yes | Yes | Yes | **PASS** |
| **Catalogue-hidden but still `publish`** | **No** — default reviewability is `post_status === publish` only; no `catalog_visibility` check | Availability filter does not consult reviewability | No auto-revoke on visibility change | Invitations still schedule | Yes | **FAIL (UPR-core gap)** |

## Evidence (catalogue-hidden)

- `src/Invitations/ProductReviewability.php` — default = publish status only; filter `upr_product_is_reviewable` exists but core does not treat `catalog_visibility=hidden` as not reviewable.
- `SuppressionService::on_product_status` — listens to `transition_post_status` only; catalogue visibility meta changes do not suppress/revoke.
- M2 prose claimed hidden products are not reviewable; tests model “discontinued” as `draft`, not catalogue-hidden.

## Gate application

Per M3 freeze hard stop: **DEV pilot and host deployment are blocked** until a separate UPR-core correction is planned, frozen, implemented, released, and the host is pinned to that corrected version.

**Do not** reimplement catalogue-hidden revocation inside `biopentra-upr-host`.

## Separate UPR-core correction proposal (smallest)

1. Extend `ProductReviewability::is_reviewable()` default to treat WooCommerce catalogue visibility `hidden` (and host-defined discontinued) as not reviewable while remaining `publish`.
2. Hook catalogue visibility changes to call `SuppressionService::suppress_product_not_reviewable()` (same as status transitions).
3. Ensure `ReviewAvailability` default consults reviewability so PDP `can_submit` closes for catalogue-hidden products.
4. Add integration tests for catalogue-hidden mandatory matrix.
5. Release as UPR patch (e.g. `v0.2.1`); host pins that tag before pilot.
