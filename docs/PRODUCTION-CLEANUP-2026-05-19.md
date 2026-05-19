# BioPentra Production Storefront Cleanup

**Date:** 2026-05-19  
**Commit:** `chore: production storefront cleanup`

## Summary

Removed **23 QA/smoke products** and **4 utility/QA pages** from the public catalog by moving them to **draft** (orders, customers, and gift-card ledger data unchanged). **9 real peptide products** remain published with proper categories; **Uncategorized** is empty (count 0).

## Phase 1 — Audit (artifacts found)

### Products drafted (23)

| Item | Slug | Former URL | Action |
|------|------|------------|--------|
| Smoke normal / GC / gift card / delivery / MP CP QA products | `smoke-*`, `*-qa-*`, `test-giftcard`, etc. | `/product/smoke-*` | **Draft** |
| Commerce Growth Gift Card QA | `commerce-growth-gift-card-qa` | `/product/commerce-growth-gift-card-qa/` | **Draft** (QA SKU `mp-cg-gift-card-qa`) |
| Browser QA Gift SKU | `browser-qa-gift-sku` | `/product/browser-qa-gift-sku/` | **Draft** |

**Kept published (9):** BPC-157, TB-500, CJC-1295 No DAC + IPA, Melanotan II, Trizepatide, Retatrutide, GHK-CU, MOTS-C, Kisspeptin.

**Kept private (8):** Ipamorelin, GHRP-6, PT-141, Hexarelin, Sermorelin, IGF-1 LR3, AOD9604, GHRP-2 — not in shop catalog.

### Pages drafted (4)

| Page | Slug | Action |
|------|------|--------|
| Gift card balance | `gift-card-balance` | Draft |
| MP CP Block Cart (QA) | `mp-cp-block-cart-qa` | Draft |
| MP CP Block Checkout (QA) | `mp-cp-block-checkout-qa` | Draft |
| Footer preview – EU v2 | `footer-preview-eu-v2` | Draft |

### Categories

| Term | Action |
|------|--------|
| `smoke-bogo` | **Deleted** (0 products) |
| `uncategorized` | **Empty** (0 products); still noindex/disallowed in SEO module |

### Not removed

- WooCommerce orders, customers, gift-card **ledger** / store credit (Commerce Growth unchanged)
- Real peptide products and their content
- Production-ready policy/content pages (FAQ, About, policies)

## Phase 2 — Taxonomy

All **9 published** products already had primary categories (`research-peptides` + specialty). No recategorization required after QA drafts.

## Phase 3 — SEO content

Real products retain existing short descriptions (69–79 chars). Smoke/placeholder products are **draft** and no longer in sitemap or shop.

## Phase 4 — Indexing hygiene

- Existing Technical SEO module: `noindex` on cart, checkout, account, search, uncategorized
- **Added:** catalog guard + product sitemap exclusion for smoke/QA slug patterns (if any republished by mistake)
- Draft QA pages excluded from page sitemap automatically

## Phase 5 — Verification

| Check | Result |
|-------|--------|
| Published products | **9** (real peptides only) |
| Uncategorized count | **0** |
| Product sitemap | **9** URLs (matches catalog) |
| Shop HTML | No smoke/QA strings |
| `seo-audit.php` | Home/shop indexable; cart/checkout/search noindex |

## Remaining manual work

1. **Production gift card product** — create and publish a merchant-facing gift card product when ready (all current gift card SKUs were QA/smoke).
2. **Private peptides** — publish when ready for sale (8 products currently `private`).
3. **Peptide Guide** — still `draft` (`peptide-guide`); publish when content-ready.
4. **News / Services** — review for launch relevance.
5. **Media library** — optional pass to remove orphaned smoke/QA uploads.
6. **Cloudflare** — purge cache if old product URLs still appear.

## Scripts

```bash
./wp eval-file wp-content/plugins/biopentra-storefront/scripts/production-cleanup-audit.php
./wp eval-file wp-content/plugins/biopentra-storefront/scripts/production-cleanup-apply.php
./wp eval-file wp-content/plugins/biopentra-storefront/scripts/seo-audit.php
```
