# biopentra-storefront 0.5.20

## Summary

Add crypto payment guide UX: cart banner, checkout link, reproducible Elementor page import bundle, and mega-menu Learn link CLI patch.

## Changes

- **Crypto payment guide module:** Cart informational banner and compact checkout link to `/how-to-pay-with-crypto/` (`biopentra_crypto_guide_enabled`, optional `biopentra_crypto_guide_page_id`). Does not duplicate the VCCP checkout notice.
- **Content bundle:** `content/crypto-payment-guide/` with import script, extracted walkthrough media, and Elementor page data for reproducible imports.
- **Mega-menu CLI:** `BIOPENTRA_MEGA_CRYPTO_GUIDE_LINK=1` appends the Learn-column link idempotently.
- **Checkout pause banner copy:** Extended default info-banner text with card-to-crypto settlement guidance.

## Notes

Deploy the guide page separately via `import-crypto-payment-guide.php` and run the mega-menu CLI on each environment. See `docs/deployments/crypto-payment-guide.md` in the dev VPS repo for production IDs and rollback steps.
