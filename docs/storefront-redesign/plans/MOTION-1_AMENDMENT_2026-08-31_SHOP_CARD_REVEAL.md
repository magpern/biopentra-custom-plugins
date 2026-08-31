# MOTION-1 amendment — 2026-08-31 — `/shop` card reveal

**Status:** **Frozen** (supersedes the MOTION-1 freeze-time “no shop card motion” decision; does not unfreeze the rest of MOTION-1)
**Date:** 2026-08-31
**Branch:** `feature/motion-1-storefront-motion-system`
**Parent plan:** [MOTION-1_STOREFRONT_MOTION_SYSTEM.md](MOTION-1_STOREFRONT_MOTION_SYSTEM.md)

This is the dated Product Owner decision record. It is not a silent edit of the 2026-08-30 freeze. After this amendment the combined plan is frozen again.

---

## Decision

**MOTION-1 must also animate product cards inside `/shop`’s `.elementor-loop-container`.**

This **supersedes** freeze-time locked decision 3 and the non-goal “No shop … product-card reveal or stagger.” Those statements remain historically true of the 2026-08-30 freeze; they are **not** the current contract.

## In scope

- Homepage MOTION-1 behaviour is **unchanged** (section reveal + curated-grid stagger; hero never opted in).
- `/shop` only (WooCommerce shop page / `is_shop()` / `body.woocommerce-shop`).
- Verified shop loop: Elementor loop-grid `ed52b7f` → `.elementor-element-ed52b7f .elementor-loop-container > .e-loop-item` (same node as `.biopentra-loop-card-root`).
- Shop card motion **must** work for:

  1. initial server-rendered shop results;
  2. category/filter replacement results (`b3a2918` / `e-filter-ed52b7f-product_cat`);
  3. search-result replacement results that remain on `/shop` (`#biopentra-shop-s` / `?s=`);
  4. load-more / infinite-scroll **appended** results, if that path is active.

## Out of scope (unchanged except `/shop`)

Shop, search, SEO, and archive **grids** remain excluded unless specifically opted in later. This amendment is **`/shop` only**.

Still excluded:

- Dedicated search templates that are not the shop page
- SEO landing grids (`.bp-seo-grid-section`)
- WooCommerce category/tag archives (`/product-category/*`, `/product-tag/*`)
- Related / upsell / cross-sell
- `biopentra-loop-card` hover/pulse (do not override)
- Elementor JSON, Blocksy child, loop-card PHP/JS, plugin version / tags

## Technical contract (normative; also copied into the parent plan)

- Enqueue MOTION-1 on `is_front_page()` **or** `is_shop()`. Still skip admin, cart, checkout, wp-login.
- Homepage: one observer on **section bands** and **curated-grid containers**. Never per-card on homepage.
- Shop: **per-card** `IntersectionObserver` on active `ed52b7f` loop items so a long list reveals progressively. Reuse the MOTION-1 observer instance. `unobserve` each card after reveal.
- Visual tokens unchanged: `--bp-motion-distance` 14px, `--bp-motion-duration` 360ms, same ease. Shop cards do **not** use homepage `:nth-child` stagger (IO timing is the stagger).
- Stamp: `data-bp-motion="shop-card"` + `data-bp-motion-state="pending|in"`. Hide CSS remains only `html.bp-motion [data-bp-motion-state="pending"]`.
- In-view at classification → `in` immediately (no flash hidden). Off-screen → `pending` + observe + per-node 25s fallback.
- `biopentra-loop-cards-init` (jQuery `$(document).trigger`, scope = loop widget): prune detached/replaced cards (clear timers, `unobserve`); initialise **only** newly inserted cards in the active shop grid. Visible replacements stay `in`. Do not scan the whole document.
- Do not retain strong references to disconnected nodes after prune.
- Reduced motion / no JS / no IO / init failsafe: products remain visible.

## Plan freeze

After this amendment and its implementation, MOTION-1 is frozen again. Further surfaces (search page, SEO, archives) need a new dated amendment.
