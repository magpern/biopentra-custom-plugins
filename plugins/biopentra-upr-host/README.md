# Biopentra UPR Host

Host adapters for [Universal Product Reviews](https://github.com/magpern/universal-product-reviews) on Biopentra.

## Responsibilities

- Hybrid delivery bridge: `mpcf_fulfillment_state_changed` → `upr_order_delivery_confirmed` (`delivered` / `shipped_fallback`)
- Support invitation actions from structured Fluent `wc_related_order` + allowlisted tags (no free-text)
- Branded PDP review availability messaging (display only)
- Fail-closed DEV pilot preflight and verification CLIs
- UPR dependency pin

## Ownership (B2 / UPR v0.2.2+)

| Concern | Owner |
|---------|--------|
| Native product-comment enforcement | **UPR core** (`NativeSubmissionGuard`) |
| Native PDP form eligibility | **UPR core** (`NativePdpForm::should_render()`) |
| Branded unavailable copy / form chrome | **This host** |
| `comments_open` as availability gate | **Forbidden** (neither core nor host) |

See [`docs/upr-integration/b2-upr-v0.2.2-host-integration.md`](../../docs/upr-integration/b2-upr-v0.2.2-host-integration.md).

## Requirements

- WooCommerce
- universal-product-reviews **0.2.2** @ `43c9989…` (DEV pilot pin — see B2 doc)
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
```

## WP6 note

Catalogue-hidden non-reviewable behaviour is enforced in UPR **v0.2.1+**. Do not reimplement revocation in this host plugin.
