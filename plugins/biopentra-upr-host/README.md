# Biopentra UPR Host

Host adapters for [Universal Product Reviews](https://github.com/magpern/universal-product-reviews) on Biopentra.

## Responsibilities

- Hybrid delivery bridge: `mpcf_fulfillment_state_changed` → `upr_order_delivery_confirmed` (`delivered` / `shipped_fallback`)
- Support invitation actions from structured Fluent `wc_related_order` + allowlisted tags (no free-text)
- Temporary pilot invitation send policy (`upr_invitation_send_authorisation`) — order-ID allowlist only
- Branded PDP review availability messaging (display only)
- Fail-closed DEV pilot preflight and verification CLIs
- UPR dependency pin via generic packaged `release.meta.json` (no `.git`)

## Ownership (B2 / UPR v0.2.2+)

| Concern | Owner |
|---------|--------|
| Native product-comment enforcement | **UPR core** (`NativeSubmissionGuard`) |
| Native PDP form eligibility | **UPR core** (`NativePdpForm::should_render()`) |
| Branded unavailable copy / form chrome | **This host** |
| Master invitation-email enable / emergency pause | **UPR core** |
| Pilot order-ID allowlist send policy | **This host** (requires UPR `v0.3.0` contract) |
| `comments_open` as availability gate | **Forbidden** (neither core nor host) |

See [`docs/upr-integration/b2-upr-v0.2.2-host-integration.md`](../../docs/upr-integration/b2-upr-v0.2.2-host-integration.md) and [`docs/upr-integration/m3-invitation-email-controls-host-policy.md`](../../docs/upr-integration/m3-invitation-email-controls-host-policy.md).

## Requirements

- WooCommerce
- universal-product-reviews **0.3.0** @ `b2abc2defc30fc023601593aa1720cbfdd0a4f3c` (annotated tag `v0.3.0`) — includes `InvitationAuthorisation` and packaged `release.meta.json`
- mp-commerce-fulfillment with `mpcf_fulfillment_state_changed` (for delivery)

## DEV verification CLIs

```bash
wp biopentra-upr-host verify-pilot-preflight   # UPR pin + env gate
wp biopentra-upr-host verify-native-submit-dev # UPR enforcement + display helper (DEV only)
wp biopentra-upr-host verify-dev-mail            # LoggingMailTransport only
wp biopentra-upr-host verify-wp6-dev            # catalogue-hidden lifecycle matrix
```

All refuse when `wp_get_environment_type() !== 'development'`.

Static policy (no site):

```bash
bash scripts/b2-policy-check.sh
bash scripts/m3-pilot-policy-check.sh
bash scripts/m3-package-pin-check.sh
```

## UPR package pin (0.1.5+)

Host verifies **only** `release.meta.json` next to the UPR bootstrap (schema `universal-product-reviews.package-meta/v1`). It does **not** read `.git`.

Production packages must embed that file at build time (see UPR `docs/package-meta.md` / WP-B build scripts).

**DEV checkout:** write the same metadata from a verified Git HEAD (do not weaken fail-closed behaviour):

```bash
# from a disposable helper once UPR packaging scripts land, or manually:
printf '%s\n' '{
  "schema": "universal-product-reviews.package-meta/v1",
  "version": "0.3.0",
  "tag": "v0.3.0",
  "commit": "b2abc2defc30fc023601593aa1720cbfdd0a4f3c"
}' > /path/to/universal-product-reviews/release.meta.json
```

Host **0.1.4** is not a production package target.

Catalogue-hidden non-reviewable behaviour is enforced in UPR **v0.2.1+**. Do not reimplement revocation in this host plugin.
