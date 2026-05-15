# Final storefront consolidation status

**Date:** 2026-05-15  
**Site:** `https://www.biopentra.eu`  
**Release tag:** `storefront-v0.4.0`  
**Project root:** `/home/magpern/woocommerce`

---

## Current status: **Stable (monitoring)**

Storefront consolidation (Phases 1–4) is **complete**. Legacy standalone storefront plugins are **removed from production**. Elementor CSS for main pages is **regenerated** and **verified** (0 post-CSS 404s in final crawl). **No further plugin consolidation** during the monitoring window below.

---

## Final active Biopentra-related plugins

| Plugin | Status | Version | Role |
|--------|--------|---------|------|
| `biopentra-storefront` | Active | **0.4.0** | Megamenu, footer contact, header auth, CVSS |
| `biopentra-loop-card` | Active | 1.0.0* | Shop loop cards, live search (standalone) |
| `wc-inventory-overview` | Active | 1.17.0 | Inventory (out of consolidation scope) |
| `biopentra-fluentform-contact-orders` | Must-use | 1.0.0 | FluentForm order linkage |

\*Version per `wp plugin list` at final check; repo may track newer header version.

**On disk, not active:** `biopentra-contact-inbox` (intentionally separate).

---

## Removed legacy storefront plugins (production)

Deleted from `wp-content/plugins/` (superseded by `biopentra-storefront`):

| Legacy plugin | Storefront module |
|---------------|-------------------|
| `biopentra-information-megamenu` | Information megamenu |
| `biopentra-footer-contact` | Footer contact |
| `custom-variation-stock-selector` | Variation stock selector |
| `biopentra-header-auth` | Header auth |

**Not merged / still standalone:** `biopentra-loop-card`

---

## Elementor CSS incident (summary)

| Item | Detail |
|------|--------|
| **Symptom** | SiteHome styled; Contact, About, FAQ, Shop, etc. unstyled / full-width |
| **Cause** | Missing `wp-content/uploads/elementor/css/post-{ID}.css` (404) while HTML still enqueued them |
| **Contributing factor** | WP-CLI (`wordpress:cli`) default UID **82** could not write to `uploads/elementor/css` (Apache uses UID **33**) |
| **Fix** | `wpcli` `user: "33:33"` in `docker-compose.yml`; bulk Elementor CSS regeneration; directory `775` + `www-data` ownership |
| **Verification** | 18 URLs, **70** post-CSS checks, **0** failures ([restoration report](elementor-css-restoration-report.md)) |

---

## Final stabilization QA (2026-05-15)

### Cache purge (origin)

| Action | Result |
|--------|--------|
| `./wp elementor flush-css --regenerate` | Done (earlier passes) |
| `./wp cache flush` | Done |
| WooCommerce transients | Cleared in prior passes |

### Cloudflare purge

| Action | Result |
|--------|--------|
| **Purge Everything** or `wp-content/uploads/elementor/css/*` | **Not executed from this environment** (no Cloudflare API credentials). **Owner action required** in Cloudflare dashboard. |

### Guest HTTP verification (curl, no auth cookie)

| Page | Admin bar | Coming-soon placeholder | Elementor markup | Elementor post-CSS 404 | Legacy plugin URLs |
|------|-----------|---------------------------|------------------|------------------------|-------------------|
| Home | 0 | 0 | Yes | **None** | None |
| Contact | 0 | 0 | Yes | **None** | None |
| About | 0 | 0 | Yes | **None** | None |
| FAQ | 0 | 0 | Yes | **None** | None |
| Shop | 0 | 0 | Yes | **None** | None |
| Cart | 0 | 0 | Yes | **None** | None |
| My Account | 0 | 0 | Yes | **None** | None |
| Product (Kisspeptin) | 0 | 0 | Yes | **None** | None |
| Checkout | 0 | 0 | Yes | **None** | None |

Storefront asset references present on all pages checked (`biopentra-storefront` in HTML).

### Browser pass (automated)

- Session was **logged in** (`bp_manager`) for Contact/home snapshots — layout and Elementor content **appear correct** (hero sections, forms, footer email control, account menu).
- **No console errors** on storefront pages in this pass (jQuery Migrate warnings only).
- **Owner should repeat** quick incognito check after Cloudflare purge.

### Known non-blockers

- Catalog **stock**: many variations out of stock — blocks full add-to-cart/checkout QA, unrelated to consolidation/CSS.
- `woocommerce_coming_soon` = **no** (Launch your store disabled).
- Elementor font-icon PHP warnings on Contact (non-fatal, separate).

---

## Monitoring window: **48–72 hours** (from 2026-05-15)

Watch daily:

| Signal | Command / location |
|--------|-------------------|
| PHP fatals | `docker logs woocommerce-wordpress-1 --since 24h \| grep -i 'PHP Fatal'` |
| Elementor CSS 404 | Crawl or spot-check `post-*.css` URLs on Contact/Shop/Cart |
| Cart/checkout | Guest add-to-cart when stock available |
| WooCommerce logs | `wp-content/wc-logs/` (if enabled) |

**Do not** during this window:

- Merge additional plugins into storefront
- Delete more plugins without a new change plan
- Major Elementor template rewrites without CSS regen + Cloudflare purge

---

## Do-not-touch list (unless incident)

| Item | Reason |
|------|--------|
| `biopentra-storefront` | Production consolidated plugin |
| `biopentra-loop-card` | Standalone by design |
| `biopentra-contact-inbox` | Out of scope |
| `wc-inventory-overview` | Out of scope |
| `wp-content/uploads/elementor/css/` permissions | UID 33 write path is critical |
| `docker-compose.yml` `wpcli.user: "33:33"` | Prevents CSS regen failures |
| Legacy plugin folders in **git monorepo** | Historical source; production already removed |

---

## Recovery quick reference

```bash
cd /home/magpern/woocommerce
./wp elementor flush-css --regenerate
./wp cache flush
# Then Cloudflare purge (manual)
```

See: `docs/elementor-css-generation-hardening.md`

---

## Related documentation

- `docs/storefront-legacy-removal-checklist.md`
- `docs/legacy-plugin-removal-results.md`
- `docs/elementor-css-restoration-report.md`
- `docs/post-coming-soon-fix-qa.md`
- `docs/urgent-styling-loss-investigation.md`
- `deploy/docker-compose.yml` (wpcli UID fix)

---

## Sign-off

| Question | Answer |
|----------|--------|
| Consolidation complete? | **Yes** (0.4.0 live) |
| Legacy removed? | **Yes** (production) |
| Styling/CSS layer stable? | **Yes** (pending Cloudflare purge for edge) |
| Safe to defer more consolidation? | **Yes** — use monitoring window |
