# biopentra-storefront 0.9.45 — release notes

**Bugfix — hide “Be the first” when a review cannot be submitted.**

## Highlights

- Product pages no longer show `No reviews yet — Be the first` when Universal
  Product Reviews is inactive, WooCommerce reviews are off, or the visitor is
  not allowed to submit a native review (typical guest / invitation-only case).
- Approved rating summaries still render when a product already has reviews.
- Host adapter `enable_pdp_summary` is honoured again (`Upr_Host_Adapter_Options`;
  legacy `Biopentra_Upr_Host_Options` still recognised).

## Scope

- Storefront PDP rating-summary markup only. Review submission rules remain in
  Universal Product Reviews / the host adapter.
- No database, schema, or settings change.

## Install

1. Download `biopentra-storefront-0.9.45.zip` from this Release.
2. Replace the plugin on the target WordPress host (DEV may bind-mount git;
   production uses this ZIP only when separately authorized).

## Notes

- Tag: `storefront-v0.9.45`
- Rollback baseline: `storefront-v0.9.44`
- Production rollout still requires an explicit GO beyond this tag.
