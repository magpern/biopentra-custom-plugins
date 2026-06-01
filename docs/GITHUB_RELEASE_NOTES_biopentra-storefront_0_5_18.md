# biopentra-storefront 0.5.18

## Summary

Fix duplicate FAQPage structured data on the FAQ page by consolidating Elementor accordion Q&A into a single JSON-LD node.

## Changes

- Builds one consolidated `FAQPage` schema from Elementor accordion/toggle/nested-accordion widgets on `/faq/`.
- Strips per-widget Elementor FAQ JSON-LD on the FAQ page so only the consolidated schema remains.
- Keeps the existing heading-based FAQ schema fallback for non-Elementor pages.
- Adds `scripts/disable-faq-page-elementor-faq-schema.php` to disable Elementor per-accordion FAQ Schema in page metadata (dry-run by default).

## Notes

Production fix also required a one-time WP-CLI run to clear `faq_schema=yes` on seven FAQ page accordion widgets. Product schema and other technical SEO behavior are unchanged.
