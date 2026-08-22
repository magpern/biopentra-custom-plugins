# M9 — Shop Discovery Consolidation — change record

**Status:** **PO-approved / frozen on DEV**
**Storefront:** `0.9.30` / tag **`storefront-v0.9.30`**
**Loop-card:** `1.6.4` / tag **`v1.6.4`**
**Plan:** [MILESTONE_M9_SHOP_DISCOVERY.md](../plans/MILESTONE_M9_SHOP_DISCOVERY.md)
**Plan-freeze commit:** `3d8486a`
**Impl:** `8f7122b` (`0.9.28`) → correctives `902dfb9` (`0.9.29`) → `deecd2c` (`0.9.30`)
**Backup:** [backups/shop-pre-M9.json](backups/shop-pre-M9.json)

## Visual correctives (folded into freeze)

| Version | Scope |
|---|---|
| `0.9.29` | Remove discovery panel chrome; tighten rhythm; desktop hero framing; magnifier affordance |
| `0.9.30` | Desktop search rails to same centered 1240 band as category chips (mobile already approved) |

Path A filter architecture, cards, and Elementor hero image asset unchanged across correctives.

## WP3 architecture decision

**Selected: Path A** — restyle Elementor Pro taxonomy-filter `b3a2918` into the M3 chip language; keep it as the supported filter engine for loop `ed52b7f`.

| Alternative | Why not |
|---|---|
| B — custom chip UI bridging Elementor filter API | Larger surface; unnecessary once Path A restyles cleanly |
| C — custom filtering | Violates “avoid unnecessary custom filtering” |

**`b3a2918` status:** retained (not removed). Customer-facing duplicate archive chips (`b2cats0` / `[biopentra_home_categories]`) removed from the Elementor tree.
**Hidden duplicate filter:** none.
**Category-description:** continues to bind to `b3a2918` (unchanged loop-card sync).

## Elementor ownership (after)

| ID | Role |
|---|---|
| `61f84d4` | Shop hero (frozen) |
| `b2srch0` / `b2srch1` | Search (`[biopentra_shop_search]`) + `bp-shop-m9-search` |
| ~~`b2cats0`~~ | **Removed** |
| `c4b3a91` | Filter row + `bp-shop-m9-filter-row` |
| `b3a2918` | Taxonomy filter chip rail (All + categories; Uncategorized stripped in PHP) |
| `ed52b7f` → `3608` | Product grid (frozen) |
| `f2917e3` | Disclaimer |

## Ops A

Separate DEV ops: `woocommerce_coming_soon=no`, `blog_public=0`. Not part of this plugin release.

## Acceptance

`tools/run-dev.sh --m9-only` → **44 passed / 0 failed**.

## Screenshots

`storefront-acceptance/screenshots/m9/` — `mobile-390-discovery.png`, `mobile-390-discovery-products.png`, `desktop-1440-discovery.png`, `desktop-1440-discovery-products.png`.

## Rollback

1. Restore `shop-pre-M9.json` into page 3755 `_elementor_data`; delete meta `bp_shop_m9_applied`.
2. Redeploy storefront `storefront-v0.9.27` / `0.9.27`.
3. Redeploy loop-card `v1.6.3` / `1.6.3`.
4. Clear Elementor + `wp cache flush` + `tests/http/dev-clear-cache.sh`.

## Production

Not modified. Replay requires explicit PO GO later.
