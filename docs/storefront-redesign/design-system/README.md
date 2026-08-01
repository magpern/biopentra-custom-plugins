# Storefront Design System (SDS)

Lightweight working standards for the Biopentra mobile-first storefront redesign. This is a **implementation spec**, not a branding exercise. CSS lands in `biopentra-storefront/assets/css/bp-tokens.css` during Milestones C/D; until then, this directory is the source of truth.

**Status:** Milestone 0.2 — pending product-owner approval before Milestone A (Homepage).

## Principles

1. **Commercial intent first** — spacing and typography prioritize product discovery on mobile.
2. **One card everywhere** — all listing surfaces use the same card tokens (Milestone C).
3. **Mobile-first** — base styles target 360px; scale up at defined breakpoints only.
4. **Preserve content** — editorial/SEO blocks use disclosure spacing, not removal.
5. **Touch-safe** — interactive targets ≥ 44×44px on mobile.

## Files

| File | Purpose |
|---|---|
| [README.md](README.md) | This spec |
| [tokens.css](tokens.css) | CSS custom properties (canonical when enqueued) |
| [image-guidelines.md](image-guidelines.md) | Hero, card, PDP image discipline |

## Spacing scale

4px base grid. Use `--bp-space-*` tokens only in new/touched CSS.

| Token | Value | Typical use |
|---|---|---|
| `--bp-space-1` | 4px | Icon gaps, tight inline spacing |
| `--bp-space-2` | 8px | Chip padding, meta rows |
| `--bp-space-3` | 12px | Card inner padding (compact) |
| `--bp-space-4` | 16px | Section padding mobile, grid gaps |
| `--bp-space-5` | 24px | Between commercial blocks |
| `--bp-space-6` | 32px | Section padding tablet+ |
| `--bp-space-7` | 48px | Editorial section separation |
| `--bp-space-8` | 64px | Major section breaks (desktop) |

**Commercial vs editorial:** product grids, search, and category rails use `--bp-space-4`–`--bp-space-5`. FAQ, compliance, and long-form SEO use `--bp-space-6`–`--bp-space-8`.

## Typography

Mobile-first sizes; desktop bumps at `768px` and `1024px`.

| Role | Mobile | Tablet (768+) | Desktop (1024+) | Token |
|---|---|---|---|---|
| Page H1 | 28px / 1.2 | 32px | 36px | `--bp-text-h1-*` |
| Section H2 | 22px / 1.25 | 24px | 28px | `--bp-text-h2-*` |
| Body | 16px / 1.5 | 16px | 17px | `--bp-text-body-*` |
| Product price | 18px / 1.2 semibold | 20px | 20px | `--bp-text-price-*` |
| Meta / stock | 13px / 1.4 | 14px | 14px | `--bp-text-meta-*` |
| Chip / button label | 14px / 1.2 medium | 14px | 15px | `--bp-text-label-*` |

Font families inherit from Blocksy/Elementor theme settings — SDS defines **size, weight, line-height** only.

## Buttons

| Variant | Use | Min touch (mobile) |
|---|---|---|
| Primary | Add to cart, checkout CTAs | 44×44px |
| Secondary | View product, outline actions | 44×44px |
| Ghost | Tertiary links styled as buttons | 44×44px |
| Chip | Category shortcuts, filters | 44px height, horizontal padding `--bp-space-3` |
| Icon | Header cart, menu (Milestone E) | 44×44px hit area |

Border radius: `--bp-radius-button` (8px). Primary uses `--bp-color-primary`; chips use `--bp-radius-chip` (9999px pill).

## Cards (canonical product card)

Applies to Elementor loop template **3608** + `biopentra-loop-card`:

| Property | Token | Value |
|---|---|---|
| Border radius | `--bp-radius-card` | 12px |
| Shadow | `--bp-shadow-card` | `0 1px 3px rgba(0,0,0,.08)` |
| Image ratio | `--bp-card-image-ratio` | 1 / 1 |
| Image max height (mobile) | `--bp-card-image-max-h` | 160px |
| Padding | `--bp-space-3` | 12px |
| Stretch link | whole card except `+` quick-add | see loop-card.js |

## Breakpoints

**New/touched CSS only** — do not mass-migrate legacy files.

| Name | Min width | Token |
|---|---|---|
| Mobile (default) | 0 | — |
| Mobile large | 480px | `--bp-bp-sm` |
| Tablet | 768px | `--bp-bp-md` |
| Desktop | 1024px | `--bp-bp-lg` |

Playwright projects mirror audit viewports: 360, 390, 430, 768, 1440.

## Touch targets

Minimum **44×44px** interactive area on mobile (WCAG 2.5.8 aligned). Visual icon may be smaller if padding expands hit area. Document exceptions in change records.

## Border radius

| Token | Value | Use |
|---|---|---|
| `--bp-radius-sm` | 4px | Inputs, small badges |
| `--bp-radius-button` | 8px | Buttons |
| `--bp-radius-card` | 12px | Product cards |
| `--bp-radius-chip` | 9999px | Category chips |
| `--bp-radius-modal` | 16px | Drawers, overlays |

## Shadows

| Token | Value | Use |
|---|---|---|
| `--bp-shadow-card` | `0 1px 3px rgba(0,0,0,.08)` | Product cards |
| `--bp-shadow-elevated` | `0 4px 12px rgba(0,0,0,.12)` | Sticky bar, drawers |
| `--bp-shadow-none` | `none` | Flat chips |

## Z-index stack

Plan before Milestone D sticky bar and Milestone E cookie banner:

| Layer | Token | Value | Owner |
|---|---|---|---|
| Base content | — | auto | — |
| Sticky purchase bar | `--bp-z-sticky-bar` | 100 | blocksy-child (D) |
| Header / mini-cart | `--bp-z-header` | 200 | storefront header |
| Cookie consent | `--bp-z-cookie` | 300 | CookieYes (E) |
| Modal / overlay | `--bp-z-overlay` | 400 | quick-add, age gate |

## Section spacing

| Context | Mobile gap | Desktop gap |
|---|---|---|
| Hero → search → categories → products | `--bp-space-4` (16px) | `--bp-space-5` (24px) |
| Between product grid rows | `--bp-space-4` | `--bp-space-5` |
| Product grids → editorial | `--bp-space-7` (48px) | `--bp-space-8` (64px) |
| Accordion / FAQ blocks | `--bp-space-6` internal | `--bp-space-7` |

## Responsive behavior

1. **DOM order = visual order** — no CSS `order` to fake commercial hierarchy.
2. **Category rail** — horizontal scroll on mobile; wrap grid at `768px+`.
3. **Product grids** — 2 columns mobile (360–430), 3 at tablet, 4+ at desktop (Elementor loop settings + SDS gaps).
4. **Search field** — full width mobile; max-width 640px centered on desktop homepage.
5. **Sticky purchase bar** — `position: fixed; bottom: 0` mobile only; hidden desktop unless specified in D.

## Existing namespaces (migration)

Until Milestone C/D enqueues `bp-tokens.css`, these remain active:

- `--bp-loop-card-*` (biopentra-loop-card)
- Blocksy theme variables (untouched)

New work references `--bp-*` SDS tokens; migrate legacy aliases in Milestone C change record.

## Approval checklist

Before Milestone A:

- [ ] Spacing scale approved
- [ ] Typography scale approved
- [ ] Card image ratio (1:1) approved
- [ ] Hero height cap (~40vh mobile) approved — see [image-guidelines.md](image-guidelines.md)
- [ ] Z-index stack approved
- [ ] Breakpoints 480/768/1024 confirmed
