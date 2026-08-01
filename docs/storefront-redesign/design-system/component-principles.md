# Component principles

High-level rules for every storefront surface. Implementation details live in [README.md](README.md) and [tokens.css](tokens.css).

## Principles

1. **Commercial intent first** — On home, shop, category, search, and archive pages, visitors reach products immediately. Editorial content supports the purchase; it does not delay it.

2. **Products before editorial content** — Product grids, search, and category shortcuts appear above long-form copy, trust essays, and FAQ. Nothing is removed; it is repositioned.

3. **One canonical component** — One product card renderer (Elementor loop template 3608 + `biopentra-loop-card`) on every listing surface. No page-specific card markup.

4. **Mobile-first** — Design and DOM order target 360px width first; scale up at 480 / 768 / 1024 only.

5. **No duplicated implementations** — If a pattern exists (card, search, category chip), reuse it. Do not fork per page.

6. **Accessibility first** — Semantic headings, keyboard paths, visible focus, screen-reader order matches visual order. Minimum 44×44px touch targets on interactive controls.

7. **Touch-first** — Primary actions and navigation must be usable without precision pointer input.

8. **DOM order equals visual order** — Never use CSS `order` or off-screen tricks to fake hierarchy. Crawlers and assistive tech read what shoppers see.

9. **Reusable before custom** — Prefer SDS tokens, shared CSS, and CLI-driven Elementor updates over one-off editor changes.

10. **Performance before decoration** — Reduce hero weight, lazy-load below the fold, right-size images before adding visual ornament.
