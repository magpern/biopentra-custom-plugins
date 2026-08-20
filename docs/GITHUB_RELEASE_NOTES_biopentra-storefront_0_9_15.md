# biopentra-storefront 0.9.15

M2 — Premium Ecommerce homepage hero on `dev.biopentra.eu` (PO-approved / frozen).

## Highlights

- Broadsheet palette on the homepage hero only: dark teal band `#0a303e`, cyan primary CTA `#0088b0`, light cyan accent, paper context `#f3f2f2`
- Mobile: compact **text-over-image** (chosen against asset 4520)
- Desktop ≥1025: left copy + right media split; copy aligned to the same **1240px** content rail as search/chips
- Preferred hero density ~28vh; hard maximum **40vh**
- Environment-relative CTA URLs; idempotent `setup-m2-homepage-hero-cli.php`
- Hero visual CSS migrated from Elementor page `custom_css` into `home-v2.css`

## Apply after install (production replay — not in this release cycle)

Production ZIP excludes `scripts/`. Copy the CLI from git for replay:

```bash
wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-m2-homepage-hero-cli.php
wp elementor flush-css && wp cache flush
```

Discover production homepage ID — do **not** assume dev page **4444**.

## Rollback

Install `storefront-v0.9.14`; restore homepage Elementor JSON + page settings from the M2 pre-change backup documented in the redesign deployment playbook.
