# Elementor CSS restoration report (main pages)

**Date:** 2026-05-15  
**Site:** `https://www.biopentra.eu`  
**Context:** After fixing WP-CLI UID (`33:33`) and shop/cart CSS, users still reported unstyled **Contact**, **About**, and **FAQ**.

---

## Summary

| Page | ID | `post-{ID}.css` on disk | HTTP | HTML references CSS | Status |
|------|-----|-------------------------|------|---------------------|--------|
| **Contact** | 3410 | Yes | **200** | Yes (`post-3410.css`) | **Restored** |
| **About** | 3825 | Yes | **200** | Yes (`post-3825.css`) | **Restored** |
| **FAQ** | 3826 | Yes | **200** | Yes (`post-3826.css`) | **Restored** |

**Remaining broken pages (Elementor post CSS 404):** **None** in the crawl below (70 asset checks, **0** failures).

---

## Root cause (this pass)

Same as incident: per-page Elementor files under `wp-content/uploads/elementor/css/` were **missing** while HTML still enqueued `post-{page_id}.css` → **404** → unstyled Elementor layouts.

SiteHome/Shop/Cart had been fixed earlier; **Contact/About/FAQ** and other builder pages had never been regenerated after the UID fix.

---

## Actions taken

### 1. Bulk regeneration (all Elementor builder pages)

```bash
cd /home/magpern/woocommerce
./wp eval '
foreach ( get_posts( ... _elementor_edit_mode = builder ... ) as $p ) {
  ( new \Elementor\Core\Files\CSS\Post( $p->ID ) )->update();
}
'
```

**22 pages/templates** regenerated successfully (including Contact, About, FAQ, policies, shop, header/footer templates).

Additional: **Peptide Guide** (4293) regenerated separately (`ok-4293`).

### 2. Cache purge

- `./wp elementor flush-css --regenerate` — Success  
- `./wp cache flush` — Success  
- **Cloudflare:** not purged via API — **manual purge recommended** (see below)

### 3. Files created on disk (sample)

After regeneration, `wp-content/uploads/elementor/css/` includes among others:

- `post-3410.css` (Contact)
- `post-3825.css` (About)
- `post-3826.css` (FAQ)
- `post-3317.css` (Services)
- `post-3472.css` (Privacy)
- `post-3827.css` … `post-3830.css` (policies)
- `post-4233.css`, `post-4144.css`, `post-4293.css` (content pages)
- `post-3755.css`, `post-3483.css`, `post-82.css` (shop/home/cart)

**21+** `post-*.css` files present (plus `base-desktop.css`, `loop-3608.css`).

---

## Crawl verification (main URLs)

Automated check: fetch HTML → extract `elementor/css/post-*.css` → HTTP GET each → expect **200**.

| URL | Result |
|-----|--------|
| `/` (SiteHome) | OK (4 post-css links) |
| `/contact/` | OK |
| `/about/` | OK |
| `/faq/` | OK |
| `/shop/` | OK |
| `/cart-2/` | OK |
| `/checkout-2/` | OK |
| `/my-account-2/` | OK (3 links) |
| `/services/` | OK |
| `/privacy-policy/` | OK |
| `/shipping-policy/` | OK |
| `/refund-policy/` | OK |
| `/terms-and-conditions/` | OK |
| `/cookie-policy/` | OK |
| `/research-use-disclaimer/` | OK |
| `/peptide-guide/` | OK (3 links) |
| `/what-are-peptides-v2/` | OK |
| `/storage-handling/` | OK |

**TOTAL:** 70 Elementor post-CSS URL checks, **0** failures.

### Layout sanity (HTML)

Elementor widget markup present on restored pages (e.g. `elementor-element` counts): Contact ~91, About ~92, FAQ ~89 — indicates full Elementor body content, not placeholder/coming-soon.

---

## Main menu (WP “Main Menu”, term 34)

| Item | URL | Page ID |
|------|-----|---------|
| Home | `/` | 3483 |
| Shop | `/shop/` | 3755 |
| Services | `/services/` | 3317 |
| News | `/news/` | 557 |
| Contact | `/contact/` | 3410 |

Footer/quick links also include About, FAQ, policies — all covered in crawl above.

**Note:** `/news/` (557) was not in the Elementor builder page list; verify separately if reported broken (may use theme/default template).

---

## Cloudflare manual purge reminder

After bulk CSS regeneration, purge edge cache so guests do not keep stale HTML/CSS:

1. Cloudflare → **Caching** → **Purge Everything** (or custom purge `www.biopentra.eu/wp-content/uploads/elementor/css/*`)
2. Hard-refresh or test in incognito
3. Compare guest vs logged-in admin if issues persist

---

## Recovery command (future)

```bash
cd /home/magpern/woocommerce
./wp elementor flush-css --regenerate
# Or single page, e.g. Contact:
./wp eval '( new \Elementor\Core\Files\CSS\Post(3410) )->update();'
curl -sS -o /dev/null -w '%{http_code}\n' \
  'https://www.biopentra.eu/wp-content/uploads/elementor/css/post-3410.css'
```

See also: `docs/elementor-css-generation-hardening.md`.

---

## Sign-off

| Question | Answer |
|----------|--------|
| Contact restored? | **Yes** |
| About restored? | **Yes** |
| FAQ restored? | **Yes** |
| Remaining post-*.css 404s in crawl? | **None** |
| Production stable (CSS layer)? | **Yes**, pending optional Cloudflare purge |
