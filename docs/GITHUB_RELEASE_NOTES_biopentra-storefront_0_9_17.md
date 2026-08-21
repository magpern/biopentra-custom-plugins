# biopentra-storefront 0.9.17

**M4 — Premium Product Cards + Homepage Product Sections** — PO-approved / frozen on `dev.biopentra.eu`.

## Highlights

- Homepage Featured / Newest / Popular section chrome: Premium teal headings and CTAs
- Compact product-stack rhythm (M3→Featured handoff + grid→CTA + CTA→next section)
- Idempotent CLI: `scripts/setup-m4-featured-spacing-cli.php` (section shell padding ownership)
- Card visual refinement ships in companion plugin **`biopentra-loop-card` 1.6.2** (soft/rounded/elevated shell preserved)

## Install

1. Download `biopentra-storefront-0.9.17.zip` from this Release.
2. Also install **`biopentra-loop-card` 1.6.2** (tag `v1.6.2`) — required for M4 card hierarchy.
3. Install/update on the target environment.
4. Optional: run `scripts/setup-m4-featured-spacing-cli.php` if Elementor section padding drifted, then flush Elementor CSS + caches.
5. Discover `page_on_front` — do not hardcode development page IDs.

## Notes

- Production replay still requires an **explicit GO**.
- M1–M3 remain frozen (`0.9.14`–`0.9.16`); this release adds M4 storefront section chrome only.
- Tag: `storefront-v0.9.17`
