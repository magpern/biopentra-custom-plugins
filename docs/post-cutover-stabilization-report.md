# Post-cutover stabilization report

**Date:** 2026-05-15  
**Site:** `https://www.biopentra.eu`  
**Storefront:** `biopentra-storefront` **0.4.0** (active)  
**Environment:** `/home/magpern/woocommerce` (Docker, WordPress `wordpress:php8.3-apache`, MariaDB 11.4)

---

## 1. Cache purge

| Action | Result |
|--------|--------|
| Elementor CSS | `./wp elementor flush-css --regenerate` — **Success** (Elementor reported CSS cache flushed) |
| WordPress object cache | `./wp cache flush` — **Success** |
| Rewrite rules | `./wp rewrite flush` — **Success** |
| WooCommerce transients | `./wp wc tool run clear_transients --user=bp_manager` — **Success** (first attempt without `--user` returned HTTP 401; retried with administrator user) |
| `WP_CACHE` / page-cache plugin | `wp-config.php` has **no** `WP_CACHE` constant; no dedicated page-cache plugin surfaced in active-plugin review for this step |
| Server reverse-proxy | **Nginx not in path** for this stack; Apache runs inside the `wordpress` container |
| CDN (Cloudflare) | Response headers show **Cloudflare** in front of origin (`server: cloudflare`, `cf-cache-status`). **No API/dashboard purge was run** from this environment — recommend a **manual “Purge Everything”** (or targeted HTML/CSS purge) if editors still see stale markup after Elementor flush |

---

## 2. Storefront verification (automated + light browser)

### HTTP / assets

- Home, `/shop/`, `/cart/` (301 to canonical cart page), `/checkout/` (301 to checkout page), `/my-account/` — **200** after redirects where applicable.
- Storefront static assets (megamenu JS/CSS, footer contact JS, header-auth JS/CSS, `cvss-bridge.js`) — **200** when requested with full URLs.

### Behaviour (2026-05-15)

| Area | Result |
|------|--------|
| Information megamenu | **Present** in DOM/accessibility tree on home (Information column with Learn / Quality & Trust / Policies sections). Menu toggle available. |
| Footer email | **“Send email to Biopentra”** control present in footer on sampled pages. |
| Header auth | **“Log in”** link present; storefront header-auth assets load **200**. |
| My Account | `/my-account/` redirects to WooCommerce page **`/my-account-2/`**; login/register form renders. |
| Cart | `/cart/` → **`/cart-2/`** (WooCommerce cart page ID **82**). Page body is a **“Great things are on the horizon”** placeholder, not a standard empty-cart UI — **treat as content/template configuration**, not as proof of storefront regression. |
| Checkout | `/checkout/` → **`/checkout-2/`** (checkout page ID **83**); not exercised end-to-end with payment in this run. |
| Cart free-shipping progress | **Not verified** (cart page content is placeholder; needs owner QA with real cart + shipping rules). |
| Variation auto-select (CVSS) | Legacy plugin removed; storefront **`cvss-bridge.js`** returns **200**. **Functional auto-select not re-tested** on a live variable PDP in this session (see product template note below). |
| Variable product add-to-cart | Sample URL `/product/kisspeptin/` HTML includes **“Great things are on the horizon”** heading and **does not** show a normal single-product layout in the fetched HTML — **likely wrong template / Elementor assignment for that product**, not isolated to storefront modules. **Owner should assign correct single-product template** and re-test add-to-cart. |
| 404s on storefront module assets checked | **None** for the URLs exercised. |
| Browser console | **No errors** observed in the automated browser pass; only **warnings** (jQuery Migrate notice, Cursor browser dialog shim). **Not** a substitute for DevTools on a logged-in checkout path. |

---

## 3. Removed plugin path verification (HTML)

`curl` of HTML for `/`, `/shop/`, `/cart-2/`, `/checkout-2/`, `/my-account-2/`, and `/product/kisspeptin/` was searched for:

- `biopentra-information-megamenu`
- `biopentra-footer-contact`
- `biopentra-header-auth`
- `custom-variation-stock-selector`

**Result:** **zero** matches on all sampled pages. References use **`wp-content/plugins/biopentra-storefront/...`** only.

---

## 4. Log review summary

### Docker / Apache (`woocommerce-wordpress-1`)

- Scanned recent container logs for **PHP Fatal** / critical patterns.
- **Finding:** Multiple **PHP Fatal** entries on **2026-05-14** (~21:44–21:46 UTC) for `str_replace(): Argument #4 ($count) could not be passed by reference` in `biopentra-storefront` megamenu `filter_script_defer` (PHP 8.3). **Current deployed file** uses `preg_replace` instead of the invalid `str_replace` usage; treat log lines as **historical incidents before the fix**, and keep monitoring for **new** timestamps after deploy.
- **Last 24h window as queried:** no additional fatal pattern beyond that cluster in the tail reviewed (cluster ends May 14 evening).

### WooCommerce file logs

- **`wp-content/wc-logs/`** is **not present** on the bind-mounted `wp-content` tree from the host, and **not listed** under `/var/www/html/wp-content/` inside the running container at check time — WooCommerce may be logging elsewhere, logging disabled, or logs rotated/purged. **Recommendation:** confirm **WooCommerce → Status → Logs** and filesystem permissions if file logging is required.

### nginx

- **Not applicable** (Apache in container).

### Browser console

- See §2; full checkout/cart flows with payment gateways were **not** driven in automation.

---

## 5. `docker-compose.yml` / `./wp` tooling

### Problem

- `./wp` runs `docker compose run ... wpcli` and failed with **“no configuration file provided: not found”** because **`/home/magpern/woocommerce/docker-compose.yml` was missing on disk**, while containers were still running from an earlier compose deployment (`docker compose ls` still referenced the missing path).

### Resolution (2026-05-15)

- **`docker-compose.yml` was recreated** under `/home/magpern/woocommerce/` by reconciling **running container inspect** (images, ports `80:80`, env from `.env` / `.env.worker`, bind mounts, named volumes `woocommerce_wp_data` / `woocommerce_db_data`, external networks `woocommerce_default` and `woocommerce_bridge-net`) with the existing **`wp`** wrapper script.
- **`wpcli` service** includes **`PHP_MEMORY_LIMIT=1G`** and **`WP_CLI_PHP_ARGS=-d memory_limit=1G`** so heavy-plugin bootstraps are less likely to OOM during CLI (previously observed with default 128M in some one-off runs).
- **Validation:** `docker compose config -q` succeeds; **`./wp --info`** succeeds.

### Notes

- `docker compose` prints a **non-fatal warning** about orphan **`proton-bridge`** (external to this compose file). Safe to ignore, or add `proton-bridge` as an `external: true` service stub if you want a clean `config` output.
- **`proton-bridge` secrets and DB passwords are not documented here** — they remain in `.env` / `.env.worker` only.
- The WooCommerce project directory is **not** the same git repo as `custom-wordpress-plugins`; **back up** `docker-compose.yml` (e.g. password manager + redundant copy on disk) so it cannot disappear unnoticed again.

---

## 6. Remaining risks

1. **Cloudflare cache** not purged from this session — edge may still serve older HTML/CSS until purged or TTL expires.  
2. **Cart and single-product templates** may be mis-assigned (placeholder “coming soon” content on cart page ID 82 and sample product) — **commerce QA blocked** until templates are corrected.  
3. **`wc-logs` absent** — reduced visibility into payment/shipping warnings until logging is confirmed.  
4. **Historical megamenu PHP fatals** in Docker logs — confirm production code revision matches the `preg_replace` fix and watch for **new** fatals after this stabilization date.  
5. **Checkout / gateway / free-shipping bar** — requires authenticated or guest session tests beyond this report.

---

## 7. Recommendation

**Status: stable enough to keep live storefront, with continued monitoring.**

- Keep **`biopentra-storefront` 0.4.0** active; maintain **24–72h** log checks and a **manual Cloudflare purge** after any template/CSS change.  
- **Owner QA:** fix cart/product Elementor templates, then re-run **checkout**, **variable PDP**, and **free-shipping progress** checks in a real browser session.

---

## Sign-off

| Field | Value |
|-------|--------|
| Performed by | Cursor agent (automated checks) |
| Review | Pending site owner |
