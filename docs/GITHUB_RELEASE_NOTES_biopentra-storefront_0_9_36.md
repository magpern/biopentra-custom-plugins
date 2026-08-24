# biopentra-storefront 0.9.36

**Terms & Conditions restore + Peptide Guide mega-menu link.**

## Highlights

- Idempotent CLI `scripts/fix-terms-and-peptide-guide-links.php` restores the empty Terms & Conditions page from the stranded May 2026 copy (Elementor revision first; baked HTML fallback for production).
- Publishes the Peptide Guide page when it is still a draft, and rewrites Information → Learn → Peptide Guide from `href="#"` to `/peptide-guide/`.
- Mega-menu rebuild CLI now includes Peptide Guide and resolves What Are Peptides via `what-are-peptides-v2`.

## Install / replay

1. Download `biopentra-storefront-0.9.36.zip` from this Release (runtime plugin). The CLI lives in git under `plugins/biopentra-storefront/scripts/` and is **not** in the production ZIP.
2. On the target WordPress host, run:

```bash
wp eval-file wp-content/plugins/biopentra-storefront/scripts/fix-terms-and-peptide-guide-links.php
```

(Use the site’s usual WP-CLI transport, e.g. `docker compose run --rm -T wpcli wp --skip-plugins=ai-multilingual,universal-telegram eval-file …` on this stack.)

3. Clear the full-page HTML cache after the DB write.

## Notes

- Tag: `storefront-v0.9.36`
- Production replay of the CLI still requires an explicit GO beyond this tag.
