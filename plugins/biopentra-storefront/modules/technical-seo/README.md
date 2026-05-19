# Technical SEO module

Part of **Biopentra Storefront** v0.5.0+.

## Responsibilities

- Meta description fallbacks (excerpt, product short description, templates)
- Open Graph and Twitter Card defaults
- `noindex` for cart, checkout, account, search, utility pages, uncategorized category
- `robots.txt` enhancements (sitemap hint, disallow search + uncategorized)
- Sitemap exclusions (utility pages, default product category, users)
- JSON-LD: `Organization`, `WebSite`, `BreadcrumbList`, `FAQPage` (when parseable)
- Attachment `alt` fallback from media title

## Audit

```bash
./wp eval-file wp-content/plugins/biopentra-storefront/scripts/seo-audit.php
```

## Custom meta description

Post meta key: `_biopentra_meta_description`
