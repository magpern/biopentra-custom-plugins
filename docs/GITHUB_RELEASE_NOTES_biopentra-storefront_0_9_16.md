# biopentra-storefront 0.9.16

**M3 — Homepage Category Discovery** — PO-approved / frozen on `dev.biopentra.eu`.

## Highlights

- Compact Premium Ecommerce category rail under the frozen M2 hero
- Visible “Shop by category” label (homepage only)
- Low-radius chips (~38px / 5px radius after PO refinement)
- Native horizontal scroll with peek affordance on narrow viewports
- Homepage Uncategorized exclusion (`is_front_page()` only — shop/SEO unchanged)
- Structural removal of under-hero homepage search widget (M1 header search retained)
- Idempotent CLI: `scripts/setup-m3-homepage-category-cli.php`

## Install

1. Download `biopentra-storefront-0.9.16.zip` from this Release.
2. Install/update the plugin on the target environment.
3. For homepage Elementor band changes, run the M3 CLI (copy from git if ZIP omits `scripts/`), then flush Elementor CSS + caches.
4. Discover `page_on_front` — do not hardcode development page IDs.

## Notes

- Production replay still requires an **explicit GO**.
- M1 (`0.9.14`) and M2 (`0.9.15`) remain frozen; this release adds M3 only.
- Tag: `storefront-v0.9.16`
