# Dev/test product images (fixtures only)

**Not a storefront fallback-image feature.** Dummy/dogfood products created by development tooling often ship without featured images. On homepage Featured / Newest / Popular grids those products render as nearly empty cards, which misleads V1A (and later) visual review of canonical product-card geometry.

## Why

Canonical cards for real catalog products include vial photography. Image-empty test fixtures break spacing, image-budget, and “commercial density” judgment even when V1A CSS and the 3608 card renderer are correct.

## Canonical fixture

| Item | Value |
|---|---|
| File | `assets/fixtures/bp-test-product-fixture.png` (this plugin) |
| Mirror | `storefront-acceptance/fixtures/media/bp-test-product-fixture.png` |
| Geometry | 1024×1024 (matches typical product card 1:1 crop) |
| Label | Marked **TEST FIXTURE / DEV ONLY** in the artwork |
| Media meta | `_biopentra_test_product_fixture=1` |
| Option | `biopentra_test_product_fixture_attachment_id` |

One media-library attachment is reused across all matching test products. Re-running the ensure script must not create duplicates.

## How it is assigned

Helpers (no front-end hooks):

- `scripts/includes/test-product-images.php`
- `scripts/ensure-test-product-images-cli.php`

```bash
cd /opt/biopentra/apps/wordpress
docker compose run --rm -T wpcli wp eval-file \
  wp-content/plugins/biopentra-storefront/scripts/ensure-test-product-images-cli.php
```

Rules:

1. Only products matching strict test/dogfood name/slug/SKU patterns are eligible.
2. Missing featured image → assign the shared fixture attachment.
3. Explicitly assigned images are never overwritten.
4. Variable parents get the fixture; child variations without their own image receive the **same** attachment ID (WooCommerce parent fallback still applies; no duplicate files).

Product creators that know they create fixtures (e.g. `mp-commerce-fulfillment` dogfood/browser seed) should call `biopentra_assign_test_product_image_if_missing()` after `save()` when the storefront helper is present.

## Repairing existing dev data

Run the ensure CLI once on the environment that holds leftover smoke/dogfood products. It is idempotent. Only positively identified fixtures are touched; legitimate catalog products are left unchanged.

## Scope reminder

- Dev/test data only
- No production deploy
- No product-card renderer changes
- No V1A styling changes
