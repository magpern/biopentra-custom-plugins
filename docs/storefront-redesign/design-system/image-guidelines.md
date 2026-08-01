# Image guidelines

Dedicated work package for Milestone D1; referenced from Milestone A/B layout work. Addresses the original business requirement: **oversized imagery slowing commercial discovery**.

Audit evidence: [../audit/metrics.json](../audit/metrics.json) — tall sections, oversized src vs display, home first product at ~3.1 viewports (360×800).

## Hero images

| Surface | Mobile max height | Desktop | Notes |
|---|---|---|---|
| Homepage hero | `--bp-hero-max-height-mobile` (40vh) | `--bp-hero-max-height-desktop` (480px) | Separate mobile/desktop assets optional |
| Shop intro band | 25vh | 320px | Text + optional icon strip, not full bleed photo |
| SEO category pages | No full-bleed hero | — | H1 + intro paragraph only above grid (Milestone B) |

**Rule:** No hero section taller than 40vh on mobile on commercial pages.

## Banner / trust rows

| Current (audit) | Target |
|---|---|
| Shop trust icons ~50px in tall row | Inline compact strip below product grid OR `--bp-space-4` band |
| Full-width decorative banners before products | Move below first product grid or remove from commercial layer |

## Card images

| Property | Standard |
|---|---|
| Aspect ratio | 1:1 (`--bp-card-image-ratio`) |
| Display height (mobile) | ≤ `--bp-card-image-max-h` (160px) |
| Source size | 600×600 WebP (existing) — verify `sizes` attribute |
| Object fit | `cover` centered on product |

### `sizes` guidance (loop card 3608)

```html
<!-- mobile 2-col ~160px, tablet 3-col ~220px, desktop 4-col ~280px -->
sizes="(max-width: 480px) 45vw, (max-width: 768px) 30vw, 22vw"
```

Document exact `sizes` per surface in Milestone C change record.

## PDP gallery

| Property | Mobile | Desktop |
|---|---|---|
| Max gallery height | 50vh | 520px |
| Sticky gallery | Disable sticky on mobile (Milestone D) | Optional |
| Zoom | Preserve WooCommerce/Blocksy zoom | — |

## Responsive crops

- Prefer **center crop** on 1:1 product shots.
- Hero: art-directed crop per breakpoint when using separate assets (`<picture>` or Elementor responsive background).
- Do not rely on CSS `object-position` alone for critical product identity.

## Lazy loading

| Context | Policy |
|---|---|
| LCP candidate (hero, first product row) | `loading="eager"`, `fetchpriority="high"` where supported |
| Below first viewport | `loading="lazy"` (WordPress default OK) |
| First row product cards | Eager load on commercial pages |

## srcset review checklist (per milestone)

1. Record `naturalWidth` vs displayed width in change record (audit script captures outliers >1.8× DPR).
2. Confirm WebP delivery via existing image optimization plugin/CDN.
3. Update `sizes` when grid column count changes.
4. Re-run audit metrics after layout change.

## Section spacing vs image height

Tall sections in audit (e.g. home FAQ ~875px, why section ~926px on mobile) are **editorial** — acceptable below commercial layer. Commercial sections must not exceed:

- Hero: 40vh
- Single product row + heading: ~1.2vh combined on shop/search targets

## Validation

Playwright does not use visual snapshots (flaky lazy-load). Assert instead:

- `firstProductVh` ≤ target per milestone (`fixtures/budgets.json` / dedicated specs)
- Audit script `bigImgs` array empty or documented exceptions
- `tallSections` above fold on commercial pages ≤ 1.5vh before first product (Milestone A target: ≤ 1.0vh)
