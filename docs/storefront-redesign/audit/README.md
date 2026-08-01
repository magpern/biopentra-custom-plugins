# Baseline audit (2026-07-31 / 2026-08-01)

Playwright audit of dev.biopentra.eu prior to any storefront-redesign change. Captured across 5 viewports (360×800, 390×844, 430×932, 768×1024, 1440×1000) using an ephemeral `node:22-bookworm-slim` + Playwright/Chromium container (nothing persisted on the host beyond these artifacts).

## Files

- `audit.mjs` — pass 1: ungated pages (home, shop-as-seen-anonymously, the 3 SEO "category" pages, contact) plus one authenticated cart/checkout flow. Logs in as the WordPress admin to bypass WooCommerce Coming Soon mode; strips the admin bar before measuring so results match an anonymous visitor.
- `audit2.mjs` — pass 2: store pages that render only past the Coming Soon gate (shop, the real `/product-category/` archive, one simple + one variable product, cart, checkout), using WooCommerce's private-link share cookie instead of an admin session. Also captures the quick-add overlay states and the full add-to-cart → cart → checkout flow.
- `metrics.json`, `metrics2.json` — per-page/per-viewport measurements: document height in viewport-heights, first-product-card position, horizontal overflow, oversized images, sticky/fixed elements, small touch targets, tall sections.
- `metrics-search.json` — Milestone 0.1 product search audit (`/?s=peptide&post_type=product`); Blocksy native cards, not canonical 3608.
- `audit-search.mjs` — search-only audit script (woo-share cookie bypass).
- `shots/`, `shots2/`, `shots-search/` — full-page PNG screenshots per page × viewport, plus targeted component shots (product card, mobile menu open, quick-add overlay states).

## How the Coming Soon gate was handled

Dev has WooCommerce "Coming Soon" (store pages only) enabled — store pages return the placeholder page to anonymous visitors. For pass 2, `woocommerce_private_link` was temporarily set to `yes` (with explicit user approval) to browse via the existing share-key cookie, then **reverted to `no`** immediately after the capture run completed. No other site setting was changed. This is a one-time manual step for the baseline audit; it is not part of the harness's own bypass (see `storefront-acceptance/tools/wp-coming-soon.sh`, added in Phase 0), which toggles and reverts the same option automatically around each dev test run.

## Baseline numbers referenced by the plan

See `/opt/biopentra/docs/storefront-redesign/README.md` and the approved plan's §3 (page-by-page audit) for the interpreted findings — this directory holds the raw evidence.
