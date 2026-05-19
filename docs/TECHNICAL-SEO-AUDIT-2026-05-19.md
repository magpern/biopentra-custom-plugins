# BioPentra Technical SEO Audit & Remediation

**Site:** https://www.biopentra.eu  
**Date:** 2026-05-19  
**Scope:** Production indexing readiness (no pricing, checkout logic, or Commerce Growth changes)

---

## Executive summary

The storefront was effectively **de-indexed** (`blog_public = 0`), which disabled WordPress sitemaps and applied `noindex, nofollow` site-wide. That single setting was the **critical** blocker for production SEO.

A new **Technical SEO** module in `biopentra-storefront` (v0.5.0) adds meta defaults, Open Graph, breadcrumb/FAQ JSON-LD, robots/sitemap hygiene, utility-page `noindex`, and uncategorized archive suppression. `blog_public` was set to `1` to enable core sitemaps.

**Estimated SEO readiness:** ~45% before → ~72% after automated fixes (manual content and QA cleanup still required for 90%+).

---

## Phase 1 — Audit findings

### Critical

| Issue | SEO impact | Recommendation | Affected |
|-------|------------|----------------|----------|
| `blog_public = 0` (“Discourage search engines”) | Entire site `noindex`; `/wp-sitemap.xml` returned 404 | Set `blog_public` to `1` for production | WordPress **Settings → Reading**; `wp option blog_public` |
| WordPress sitemap unavailable | No URL discovery for crawlers | Enabled via `blog_public`; verify `/sitemap.xml` → `/wp-sitemap.xml` | Core `wp-sitemaps` |

### High

| Issue | SEO impact | Recommendation | Affected |
|-------|------------|----------------|----------|
| No dedicated SEO plugin | No per-page SEO UI, sitemap tuning, or redirect manager | Consider Rank Math or Yoast after launch (optional) | Site-wide |
| Missing meta descriptions (most URLs) | Weak SERP snippets, lower CTR | Add excerpts/short descriptions; module now provides fallbacks | Products (22/32 no excerpt), FAQ, search |
| Cart/checkout/account/search indexable when site public | Crawl budget waste, thin/duplicate URLs | `noindex` via Technical SEO module | WooCommerce pages `cart-2`, `checkout-2`, `my-account-2`, `?s=` |
| **22 products in “Uncategorized”** | Poor topical clustering; weak category landing pages | Reassign products to `research-peptides`, etc. | `product_cat` term ID 15 |
| Uncategorized archive publicly reachable | Low-value indexed URLs | `noindex` + `robots.txt` Disallow + exclude from product_cat sitemap | `/product-category/uncategorized/` |
| QA pages published | Test URLs may leak into index | `noindex` + exclude from page sitemap; draft or password-protect | `mp-cp-block-cart-qa`, `mp-cp-block-checkout-qa`, `gift-card-balance` |
| Checkout URL serves cart (empty cart redirect) | Duplicate title/canonical with cart | Expected Woo behavior; both URLs `noindex` | `checkout-2` → cart content |
| Author/user sitemap exposed | Low-value author URLs | Disabled user sitemap provider | `wp-sitemap-users-1.xml` (fixed in module) |

### Medium

| Issue | SEO impact | Recommendation | Affected |
|-------|------------|----------------|----------|
| Duplicate title “Cart – BioPentra” | Cart vs checkout confusion | Resolved for SEO via `noindex` on both; fix checkout redirect separately if UX issue | Cart/checkout |
| Limited Open Graph images | Poor social previews | Module outputs `og:image` from featured image/logo | Theme/products |
| FAQ meta description empty in content | Weak FAQ SERP | Default FAQ description added in module | Page `faq` |
| Breadcrumb JSON-LD not unified | Missed rich result eligibility | `BreadcrumbList` JSON-LD added | Module |
| `robots.txt` missing explicit sitemap on CDN edge | Crawlers may miss sitemap hint | Purge Cloudflare cache for `/robots.txt`; WP filter adds rules | Cloudflare + WooCommerce `robots.txt` filter |
| Elementor + many CSS/JS handles | LCP/INP risk (Core Web Vitals) | Asset cleanup, critical CSS, lazy load (manual) | Elementor, Blocksy, Age Gate, Fluent Form |
| Smoke/test products in catalog | Quality/trust signals | Unpublish or noindex smoke SKUs before launch | Products matching `smoke-*` |

### Low

| Issue | SEO impact | Recommendation | Affected |
|-------|------------|----------------|----------|
| `sitemap.xml` 301 to `wp-sitemap.xml` | Normal WP behavior | Submit `https://www.biopentra.eu/sitemap.xml` in GSC | Core |
| Cloudflare AI bot blocks in robots | No impact on Google Search | Informational only | Cloudflare Managed robots |
| Footer preview page published | Thin duplicate content | Draft `footer-preview-eu-v2` | Pages |
| News archive / blog thin | Low priority for commerce site | `noindex` blog if unused | Page `news` |
| Image alt gaps on decorative assets | Accessibility + image search | Module alt fallback from attachment title | Media library |

---

## Phase 2 — Prioritized remediation matrix

See tables above. Items marked **fixed in code/ops** below.

---

## Phase 3 — Safe fixes implemented

| Fix | Status |
|-----|--------|
| `blog_public = 1` | Done (WP-CLI) |
| Technical SEO module v0.5.0 | Done |
| Meta description fallbacks (home, shop, products, FAQ) | Done |
| Open Graph + Twitter Card defaults | Done |
| `noindex` cart, checkout, account, search, utility pages | Done |
| Uncategorized archive `noindex` + robots Disallow + sitemap exclude | Done |
| QA/utility pages excluded from page sitemap | Done |
| User/author sitemap disabled | Done |
| BreadcrumbList JSON-LD | Done |
| FAQPage JSON-LD (when ≥2 Q/A parsed from content) | Done |
| Image `alt` fallback from attachment title | Done |
| Canonical correction on noindex singular pages | Done |
| Audit script `scripts/seo-audit.php` | Done |

**Not changed (per requirements):** pricing, checkout logic, Commerce Growth, permalinks, mass content rewrites.

---

## Phase 4 — Validation

| Check | Result |
|-------|--------|
| `blog_public` | `1` |
| `/wp-sitemap.xml` | **200** — index with pages, products, product_cat, product_tag |
| `/sitemap.xml` | **301** → wp-sitemap (expected) |
| Home robots | `max-image-preview:large` (indexable) |
| Cart robots | `noindex, nofollow` |
| Home meta description | Present |
| Home `og:image` | Present |
| Home JSON-LD | Organization + WebSite |
| Shop JSON-LD | BreadcrumbList |
| Product JSON-LD | WooCommerce Product + BreadcrumbList |

### Manual follow-up (recommended)

1. **Google Search Console:** Verify property; submit `https://www.biopentra.eu/sitemap.xml`.
2. **Rich Results Test:** Test home, one product, `/faq/`.
3. **Cloudflare:** Purge cache for `/robots.txt` so Sitemap/Disallow rules appear at edge.
4. **Content:** Reassign 22 products from Uncategorized; add real short descriptions for hero SKUs.
5. **QA pages:** Draft `mp-cp-block-*-qa` and `gift-card-balance` if not needed publicly.
6. **Performance:** Run PageSpeed Insights / Lighthouse on home + product + shop (Elementor asset diet).
7. **Optional:** Install Rank Math or Yoast for redirects, focus keywords, and Search Console integration.

---

## Files / plugins affected

- `wp-content/plugins/biopentra-storefront/` (v0.5.0)
  - `modules/technical-seo/class-technical-seo-module.php`
  - `includes/class-biopentra-storefront.php`
  - `scripts/seo-audit.php`
- WordPress option: `blog_public`
- WooCommerce: product categories, system pages
- Theme: Blocksy (schema microdata, breadcrumbs UI)
- Infrastructure: Cloudflare (`robots.txt` caching)

---

## SEO score estimate

| Area | Before | After |
|------|--------|-------|
| Indexability | 0/10 | 8/10 |
| Sitemaps | 0/10 | 8/10 |
| On-page meta | 2/10 | 6/10 |
| Structured data | 4/10 | 7/10 |
| Crawl hygiene | 3/10 | 8/10 |
| Performance (CWV) | 5/10 | 5/10 (unchanged) |
| **Overall** | **~45%** | **~72%** |
