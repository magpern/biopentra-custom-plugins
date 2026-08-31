# MOTION-1: Storefront Motion System

**Status:** **PO-approved / frozen on DEV** (2026-08-31) — `biopentra-storefront` **0.9.40** · **`storefront-v0.9.40`**
**Identifier:** **MOTION-1** (new initiative; not M11; not a reopen of M1–M10 or PDP-1)
**Freeze date:** 2026-08-30
**Amendment:** 2026-08-31 — `/shop` product cards in scope. Decision record: [`MOTION-1_AMENDMENT_2026-08-31_SHOP_CARD_REVEAL.md`](MOTION-1_AMENDMENT_2026-08-31_SHOP_CARD_REVEAL.md). The 2026-08-30 “no shop card reveal” decision is **superseded**, not silently deleted.
**Owner:** `biopentra-storefront` in `biopentra-custom-plugins`
**Production:** not in scope. Tag/ZIP from this freeze is for DEV source-of-truth; production replay still needs a later explicit PO GO.

This document is the authoritative MOTION-1 specification. It supersedes the deferred “Motion & Interaction Polish” placeholder in [`UI-OVERHAUL-CLOSURE.md`](../UI-OVERHAUL-CLOSURE.md) §H for *planning* purposes. Frozen M1–M10 and PDP-1 visual baselines remain frozen.

---

## Freeze-time repository baseline (verified 2026-08-30)

Do not copy older planning notes for versions. Values below were read from the repository and live DEV at freeze.

| Item | Value |
|---|---|
| `origin/main` SHA | `ac60df4574642dba983d1327dd5d756445719c7b` |
| Branch for this work | `feature/motion-1-storefront-motion-system` (from `origin/main`) |
| Plugin header `Version` | `0.9.39` (`plugins/biopentra-storefront/biopentra-storefront.php`) |
| `BIOPENTRA_STOREFRONT_VERSION` | `0.9.39` |
| `readme.txt` latest `= Changelog =` entry | `0.9.39` (M3 B4 PDP reviews `#reviews` corrective) |
| Root `CHANGELOG.md` latest released section | `[0.9.36]` — 2026-08-24 |
| Latest `storefront-v*` git tag on this repo | `storefront-v0.9.36` (0.9.37–0.9.39 exist in plugin header/readme and are **not** tagged) |
| UI overhaul program freeze tag (historical) | `storefront-v0.9.33` (M10). Unchanged by MOTION-1. |
| PDP-1 freeze tag (historical) | `storefront-v0.9.35`. Unchanged by MOTION-1. |
| Live homepage check | `https://dev.biopentra.eu/` — required SDS classes present; `elementor-invisible` count **0** |
| Served DEV bind-mount | `apps/wordpress/compose.yml` → `/opt/biopentra/dev/biopentra-custom-plugins/plugins/biopentra-storefront` |

**Homepage markup (live, freeze-time):**

- Hero: `.bp-home-hero` on `41734c4`. **Never opted in.**
- Category band: `.bp-home-cats-section` / `.bp-m3-cats` (`a1srch0`); inner `<nav class="bp-home-cats">` and `.bp-home-cat-chip`.
- Curated stacks: `.bp-home-products-section` on `be24b65` (Featured), `a3853b0` (Newest), `17b27f7` (Popular).
- Loop items: `.biopentra-loop-card-root` **is also** `.e-loop-item` (same node), children of `.elementor-loop-container`. Stagger `:nth-child()` targets `.bp-home-products-section .elementor-loop-container > .e-loop-item`.
- Editorial: `.bp-m5-trust` (`0b22897`), `.bp-m6-confidence` (`7fe474f`), `.bp-m6-why` (`why4444`), `.bp-m6-faq` (`faqPreview4444`), `.bp-m7-guidance` (`238bcc6`).

Implementation at freeze time was **not** to bump plugin version or add a `storefront-v*` tag. Those landed at PO visual freeze as **0.9.40** / **`storefront-v0.9.40`**.

---

## 1. Goal, non-goals, success criteria

**Goal.** Make the storefront feel slightly more modern through restrained, reusable, homepage-first motion owned by `biopentra-storefront`, plus `/shop` product-card reveal (2026-08-31 amendment), without harming accessibility, performance, SEO, or frozen mobile-first redesign baselines.

**Non-goals**

- Not M11. Not a reopen of M1–M10, PDP-1, header, footer, shop discovery mechanics, or contact.
- No third-party animation library (no GSAP, AOS, Lottie, Elementor Motion FX / entrance animations).
- No Elementor JSON / template mutation and no CLI that stamps motion classes into frozen pages.
- ~~No shop, search, SEO landing, WooCommerce archive, related/upsell, or infinite-scroll **product-card** reveal or stagger.~~ **Superseded 2026-08-31:** `/shop` loop cards (`ed52b7f` `.elementor-loop-container > .e-loop-item`) **are in scope**, including initial SSR, in-shop filter/search replacement, and load-more append. Still **out of scope:** dedicated search templates, SEO landing grids, WooCommerce category/tag archives, related/upsell, and any grid other than `/shop`.
- No hero (`.bp-home-hero`) animation.
- PDP gallery, purchase panel, tabs, reviews, sticky bar: **deferred**.
- Add-to-cart success pop/checkmark: **deferred** (likely loop-card / Woo AJAX-owned).
- No global `html`/`body` `scroll-behavior: smooth`. Do not change [`chrome-v1.js`](../../../plugins/biopentra-storefront/assets/js/chrome-v1.js) header `scrollIntoView({ behavior: 'smooth' })`.
- Do **not** override `biopentra-loop-card` hover lift, image scale, overlay slide, or `biopentra-loop-card-pulse` from storefront CSS. Missing loop-card `prefers-reduced-motion` is a **separately owned follow-up**.
- No production deploy, replay, or release ZIP.

**Success criteria**

- Approved homepage bands fade/rise once (~12–16px, ~300–450ms) when they enter view.
- Curated homepage grids stagger **cards** (~40–60ms, CSS cap at sibling 6).
- Storefront-owned homepage chips/buttons: subtle fine-pointer hover/press; `:focus-visible` remains a static outline (no translate on focus).
- JS disabled, delayed, or failed: all content visible and usable.
- Head-gate success + controller init failure: `html.bp-motion` removed; nothing left hidden.
- After successful init, below-fold motion still runs if the visitor spends **more than two seconds** on the hero.
- `prefers-reduced-motion: reduce`: no MOTION-1 reveal, stagger, or non-essential hover/press movement.
- No CLS from MOTION-1; no horizontal overflow from transforms; hero and first in-view product card never `pending`.
- Eager / `fetchpriority=high` card image behaviour unchanged (`biopentra-loop-card` `includes/card-image-attributes.php` is out of this repo and must not be edited).
- `/shop` `ed52b7f` cards receive MOTION-1 `data-bp-motion="shop-card"` + pending/in as specified in §4.7b. Search-page, SEO, and Woo archive cards still receive **no** `data-bp-motion*` attributes.
- Rollback = dequeue MOTION-1 handles (or revert the feature branch); no Elementor restore.

---

## 2. Current-state findings

**Enqueue.** [`class-biopentra-storefront.php`](../../../plugins/biopentra-storefront/includes/class-biopentra-storefront.php) requires per-surface `*-assets.php`. Tokens: [`bp-tokens-assets.php`](../../../plugins/biopentra-storefront/includes/bp-tokens-assets.php) → [`bp-tokens.css`](../../../plugins/biopentra-storefront/assets/css/bp-tokens.css) (no motion tokens yet). Existing JS is footer IIFE (`wp_enqueue_script( …, true )`). **`wp_add_inline_script()` follows its handle** — a footer handle cannot host the head gate.

**No bundler** in the storefront plugin.

**Existing motion to isolate, not replace:** header drawer WAAPI ([`bp-nav-drawer.js`](../../../plugins/biopentra-storefront/assets/js/bp-nav-drawer.js)); mini-cart transitions; loop-card hover/pulse (out of scope); D2B sticky-bar `IntersectionObserver` (PDP, do not share). Shop infinite scroll uses a **different** observer in loop-card — do not reuse it.

**Shop dynamic grids.** `biopentra-loop-card` `assets/shop-loop-filter.js` replaces/appends loop `ed52b7f` and fires jQuery `biopentra-loop-cards-init` with the loop widget as scope. **2026-08-30 freeze:** MOTION-1 must ignore those cards. **Superseded 2026-08-31:** MOTION-1 must initialise **only newly inserted `/shop` cards** in that widget; do not modify loop-card.

---

## 3. Motion taxonomy

**Allowed**

| Effect | Meaning |
|---|---|
| `reveal` | Section band: opacity 0→1 and `translateY(var(--bp-motion-distance))`→0, once |
| `reveal-stagger` | Curated-grid **cards** only; delays via CSS `:nth-child` |
| `shop-card` | `/shop` loop item; per-card IO; same opacity/translate tokens; **no** `:nth-child` stagger |
| `feedback-hover` / `feedback-press` | Storefront-owned homepage chips/buttons; fine pointer; ≤2px / ≤0.02 scale |
| `feedback-success` | Specified for later; **not implemented** |

**Approved MOTION-1 targets**

- Section reveal: `.bp-home-cats-section` (or inner `.bp-home-cats` if the section node is unsuitable), `.bp-m5-trust`, `.bp-m6-confidence`, `.bp-m6-why`, `.bp-m6-faq`, `.bp-m7-guidance`.
- Card stagger: `.bp-home-products-section` containers; eligible cards `.biopentra-loop-card-root` / `.e-loop-item` **inside those sections only**.
- Shop cards (2026-08-31): `body.woocommerce-shop .elementor-element-ed52b7f .elementor-loop-container > .e-loop-item` only.
- Interaction: `body.home .bp-home-cat-chip`, `body.home .bp-home-products-section .elementor-button`, and similar homepage storefront buttons. Do **not** add a second lift/scale on `.biopentra-loop-card-root`.

**Forbidden**

- `.bp-home-hero`; header; footer; mini-cart; cookie; sticky PDP bar; drawers; FAQ accordion expand (functional).
- ~~Shop `ed52b7f`, search results, SEO `.bp-seo-grid-section`, WC archives, related/upsell per-card motion.~~ **Superseded 2026-08-31 for `/shop` `ed52b7f` only.** Still forbidden: search templates, SEO `.bp-seo-grid-section`, WC archives, related/upsell per-card motion.
- Bounce; translation >16px; zoom >~2%; rotation; parallax; repeat on scroll-back.
- Animating layout properties (`top`/`left`/`width`/`height`).
- CSS hide by SDS class under `html.bp-motion` **without** `data-bp-motion-state="pending"`.
- `:focus-visible { transform }`.
- `scroll-behavior: smooth` on `html`/`body`.

---

## 4. Technical design (normative)

### 4.1 Hooks

- `data-bp-motion="reveal"` — observed section band.
- `data-bp-motion="stagger"` — observed **grid container** (`.bp-home-products-section`).
- `data-bp-motion="shop-card"` — observed **`/shop` loop item** (per-card; 2026-08-31).
- `data-bp-motion-state="pending|in"` — on nodes that hide/reveal: the section band, curated-grid **cards**, or `/shop` `ed52b7f` cards. Never search-page / SEO / Woo-archive cards.
- Hide CSS **only**: `html.bp-motion [data-bp-motion-state="pending"]`.

### 4.2 WordPress enqueue (two handles)

New [`includes/motion-assets.php`](../../../plugins/biopentra-storefront/includes/motion-assets.php), required from the storefront loader.

1. **Head gate** handle `biopentra-motion-gate`:
   - `wp_register_script( 'biopentra-motion-gate', false, array(), BIOPENTRA_STOREFRONT_VERSION, false );`
   - `wp_enqueue_script( 'biopentra-motion-gate' );`
   - `wp_add_inline_script( 'biopentra-motion-gate', $gate_js, 'after' );`
   - `src = false` (no file). Last argument `false` → `wp_print_head_scripts()` (typically `wp_head` priority 9). Synchronous, non-defer, non-async.
2. **Footer controller** handle `biopentra-motion`:
   - File `assets/js/bp-motion.js`, vanilla IIFE, `$in_footer = true`.
   - **Do not** `wp_add_inline_script` the gate onto this handle.
3. **CSS** handle `biopentra-motion`: `assets/css/bp-motion.css`, dependency `biopentra-bp-tokens`.
4. **`<noscript>`** in `wp_head`: defensive override of pending-hide if `html.bp-motion` were present without script.

Frontend only. Load on `is_front_page()` **or** `is_shop()` (2026-08-31). Skip `is_admin()`, cart, and checkout (`is_cart()`, `is_checkout()`). Do not load on wp-login, product-category archives, SEO landings, or dedicated search templates.

### 4.3 Progressive enhancement — default visible

- Server HTML has **no** `data-bp-motion-state`. Crawlers and no-JS users see full content.
- CSS must **not** hide `.bp-home-products-section` (or any SDS class) merely because `html.bp-motion` is set.
- **In-view** nodes at classification go directly to `in`. **Never** `pending` first (no visible-then-hidden; no LCP hide).
- Hero is never stamped.

### 4.4 Head gate

If `prefers-reduced-motion: reduce` **or** `'IntersectionObserver' not in window`:

- Do **not** add `html.bp-motion`.
- `window.bpMotion = { allowed: false }`.
- Do not start the init timer.

Else:

- `document.documentElement.classList.add('bp-motion')`.
- `window.bpMotion = { allowed: true, failsafeId: … }`.
- Start **one init timer** (`--bp-motion-init-failsafe-ms`, **2000**). On fire: remove `html.bp-motion`, add `html.bp-motion-failsafe`, run global cleanup (§4.8).

### 4.5 Two-level recovery

**Level 1 — init timeout (2s).** Protects **only** the interval before the footer controller **completes successful initialization**.

The controller may `clearTimeout(window.bpMotion.failsafeId)` **only after all three** succeed in the same init turn:

1. Classified every eligible **initial** node for this view (homepage sections/grids, and/or `/shop` `ed52b7f` cards).
2. Configured the single `IntersectionObserver` and `observe()`’d every off-screen homepage section, every off-screen curated-grid **container**, and every off-screen **shop card**.
3. Registered a per-node fallback for **every** node set to `pending`.

- Do **not** clear the init timer merely because the footer file started.
- Do **not** keep the init timer until the user has scrolled the whole page.
- If init throws or is incomplete: leave the init timer running; on fire, remove `html.bp-motion`.

**Level 2 — per-node fallback (25000ms).** Token `--bp-motion-node-failsafe-ms: 25000` (within the required 20–30s band). Every `pending` node gets its own timer (WeakMap). If still `pending` at expiry: force `in` and clean up that node (§4.8). This recovers stuck pending without cancelling motion for a visitor still reading the hero.

### 4.6 Classification (footer controller)

Requires `window.bpMotion.allowed` and `html.bp-motion` still present.

**Section bands** (`.bp-home-cats-section`, `.bp-m5-trust`, `.bp-m6-confidence`, `.bp-m6-why`, `.bp-m6-faq`, `.bp-m7-guidance`):

- Intersecting (generous “already in view” test): `data-bp-motion="reveal"`, state `in`. Never pending.
- Off-screen: state `pending` on the section; `observe(section)`; start that section’s per-node timer.

**Curated grids** — §4.7.

**Shop cards** — §4.7b (only when `body.woocommerce-shop`).

**Force-visible paths:** no JS; reduced-motion at gate; no `IntersectionObserver`; delayed controller (nothing pending yet); init throw (head timer); per-node timer; `<noscript>` CSS.

**Reduced-motion later:** `matchMedia` listener → global cleanup; remaining pending become visible.

### 4.7 Curated-grid stagger

A container `pending`/`in` cannot stagger cards. Card-level state + CSS delays.

- **One** `IntersectionObserver` observes `.bp-home-products-section` **containers** only on the homepage. **Never** observe individual **homepage** cards.
- **Off-screen grid:** mark **only that grid’s** `.biopentra-loop-card-root` / `.e-loop-item` cards `pending`; per-node timer on **each**; `observe(container)`; `data-bp-motion="stagger"` on the container.
- **Grid intersects:** set those pending cards to `in` in **one operation**; `unobserve(container)`; clear those cards’ timers. CSS `:nth-child` delays stagger the transitions.
- **Grid already in view:** set all eligible cards directly to `in`. Never pending. `transition-delay: 0` / no entrance animation.
- ~~Shop/search/archive/SEO cards: **unstamped**.~~ Search/archive/SEO remain unstamped. `/shop` `ed52b7f` cards: §4.7b.

### 4.7b `/shop` per-card reveal (2026-08-31 amendment)

Deliberately **not** the homepage container-stagger path. A long shop list must reveal progressively.

- Surface: `body.woocommerce-shop` only. Widget: `.elementor-element-ed52b7f`. Cards: `.elementor-loop-container > .e-loop-item`.
- Reuse the MOTION-1 `IntersectionObserver` instance. **Observe each eligible card**, not the container.
- In-view at classify (initial SSR, filter replacement, or append): `data-bp-motion="shop-card"`, state `in`. Never pending first.
- Off-screen: `shop-card` + `pending`; `observe(card)`; per-node 25s timer; `unobserve` on reveal.
- Same hide CSS and tokens as homepage cards. **No** `:nth-child` stagger on shop.
- Skip cards already `in` or `pending` (idempotent; no replay).
- `biopentra-loop-cards-init`: prune disconnected pending shop cards (clear timer, `unobserve`, drop refs); classify **only** cards in the event scope / active `ed52b7f` widget. Do not `querySelectorAll` the whole document.
- Load-more append: existing `in` cards stay `in` and stay unobserved. New below-fold cards pending until they enter view.
- Do not share loop-card’s infinite-scroll observer. Do not modify `biopentra-loop-card`.

**Explicit CSS delays** on `.bp-home-products-section .elementor-loop-container > .e-loop-item` when `data-bp-motion-state="in"` **and** the card **was** pending (implementation: only pending→in uses delay; in-at-init uses delay 0). With `--bp-motion-stagger: 50ms`:

- `:nth-child(1)` → `0ms`
- `:nth-child(2)` → `50ms`
- `:nth-child(3)` → `100ms`
- `:nth-child(4)` → `150ms`
- `:nth-child(5)` → `200ms`
- `:nth-child(6)` → `250ms`
- `:nth-child(n + 7)` → `250ms` (cap; later cards do not wait longer)

CSS cannot compute `min(index, cap)` from a custom property. Do not pretend it can.

Reveal: `opacity` + `transform` only. `will-change: opacity, transform` **only while pending**, then remove. Distance `--bp-motion-distance: 14px`. Duration `--bp-motion-duration: 360ms`. Ease `cubic-bezier(0.22, 0.72, 0.2, 1)`.

No repeat on scroll-back (`unobserve` after reveal).

### 4.8 Cleanup

Track: one observer; observed homepage sections/containers **and** shop cards; WeakMap pending node → timeout id. Prune disconnected shop cards on `biopentra-loop-cards-init`.

| Event | Actions |
|---|---|
| Reveal (observer / bulk `in`) | Set nodes `in`; `clearTimeout` + delete map; `unobserve` that section, container, or **shop card** |
| Per-node timer | Set that node `in`; clear its timer. Section: `unobserve` if observed. Homepage card: if **all** eligible cards in that grid are `in`, `unobserve(container)`. Shop card: `unobserve(card)` |
| Reduced-motion change | Clear all per-node timers; `disconnect()` observer; force remaining pending visible (remove `html.bp-motion` and/or set `in`) |
| Head-gate init failsafe | Remove `html.bp-motion`; add `html.bp-motion-failsafe`; clear all per-node timers; `disconnect()` observer |
| `pagehide` | Clear all per-node timers; `disconnect()`; `clearTimeout` init id if still armed |

Idempotent `init`: skip `in`; do not double-observe; do not stack timers.

`biopentra-loop-cards-init`: **2026-08-30:** do not stamp shop/search cards. **2026-08-31:** stamp **new `/shop` `ed52b7f` cards only** (jQuery event + native listener). Search-page/SEO/archive: still do not stamp.

### 4.9 Reduced motion (MOTION-1-owned only)

`@media (prefers-reduced-motion: reduce)` on MOTION-1 rules: no reveal movement, no stagger delay, pending-hide cancelled, storefront hover/press transforms off. Loop-card motion unchanged.

### 4.10 Isolation

- Do not enable Blocksy or Elementor animation settings.
- Do not observe header or PDP sticky bar.
- Do not share the shop infinite-scroll observer.

### 4.11 Tokens (add to `bp-tokens.css`)

```css
--bp-motion-distance: 14px;
--bp-motion-duration: 360ms;
--bp-motion-ease: cubic-bezier(0.22, 0.72, 0.2, 1);
--bp-motion-stagger: 50ms;
--bp-motion-init-failsafe-ms: 2000;
--bp-motion-node-failsafe-ms: 25000;
```

Stagger cap **6** is encoded only as `:nth-child` rules, not as a CSS `min()`.

---

## 5. Explicit decisions (locked)

1. MOTION-1 is a new initiative (not M11).
2. Homepage-first: section reveal + curated **card** stagger (unchanged by the 2026-08-31 amendment).
3. ~~No hero; no shop/search/SEO/archive/infinite-scroll card motion.~~ **Superseded 2026-08-31:** no hero; **`/shop` `ed52b7f` cards are in scope** (SSR + filter/search replacement + load-more append). Search templates, SEO, WC archives, related/upsell remain out.
4. PDP and ATC success feedback deferred.
5. Two-handle head gate + footer controller.
6. Runtime in-view classification; pending only off-screen.
7. Init timeout vs per-node 25s fallback as specified.
8. Explicit `:nth-child(1–6)` + `:nth-child(n + 7)` stagger.
9. No loop-card reduced-motion override from storefront.
10. No global smooth scrolling; no Elementor JSON mutation.
11. No plugin version bump, tag, or production replay in this implementation pass.
12. Contact/SEO **section** reveals: follow-up, not MOTION-1.

---

## 6. Implementation sequence

| WP | Work | Stop |
|---|---|---|
| WP1 | Tokens + `bp-motion.css` (inert without pending) | CSS review; no visual change without JS stamp |
| WP2 | `motion-assets.php` two-handle enqueue; gate + empty-safe controller | View-source: gate in head, controller in footer; JS off unchanged |
| WP3 | Homepage classification, observer, stagger, two-level recovery | Hero/LCP never pending; below-fold still animates after >2s on hero |
| WP4 | Homepage chip/button hover-press (not loop-card) | Keyboard focus does not translate |
| WP5 | `/shop` per-card reveal on `ed52b7f` + `biopentra-loop-cards-init` (amendment) | Filter + load-more still work; no flash on visible replacements |
| WP6 | Failsafe/reduced-motion/`pagehide`/`noscript` | Matrix in §7 |
| WP7 | Acceptance + change record | No version tag |

---

## 7. Test and acceptance matrix

Viewports **360, 390, 430, 768, 1440**. Use `storefront-acceptance` conventions (`tests/*.spec.ts`, `tools/run-dev-playwright.sh` for a targeted file, `tools/run-dev.sh` for existing milestone flags). **Do not invent a runner flag** unless adding one is required by that harness; prefer invoking the spec path.

Coverage:

1. Homepage slow scroll, fast scroll, scroll-back: sections once; cards stagger; no stuck hidden; **below-fold still moves after >2s on the hero**.
2. `/shop`: first-viewport cards never pending; below-fold reveal once; no repeat on scroll-back; filter/search replacement initialises new cards only; load-more appends initialise once. Search templates / SEO / WC archives remain unstamped. M9 filter + infinite-scroll stay functional.
3. JS disabled; controller blocked **before** init completes (head timer reveals all); hang **after** init (per-node timers; other pending keep motion until they fire or intersect).
4. `prefers-reduced-motion: reduce`; keyboard and visible focus; no focus-position movement; no horizontal overflow.
5. Hero and first in-view product card never pending; LCP image attrs unchanged; no MOTION-1 CLS.
6. Sample existing M4 / M9 / header overflow regression.

Screenshots/recordings: `docs/storefront-redesign/validation/motion-1/` (created at implementation).

**Served-checkout note:** live `dev.biopentra.eu` bind-mounts the `main` plugin path. Implementation on this feature branch/worktree is **not** live until that path serves the branch. Acceptance of MOTION-1 behaviour must use a method that loads this branch’s assets (temporary overlay for PO review, or Playwright injection of branch CSS/JS against live DOM). Do not switch the served `main` checkout to this branch as a side effect of development.

---

## 8. Accessibility, performance, JS failure, regression

- Motion is never required for meaning or completeness.
- Touch targets 44px unchanged.
- One observer instance; compositor-only reveal; homepage observes containers; `/shop` observes cards (2026-08-31).
- SSR readable without JS.
- Frozen M2–M7, M9, PDP-1, header drawer must not regress.

---

## 9. Rollback

1. Dequeue / stop requiring `motion-assets.php`, or revert the feature-branch commits.
2. No Elementor JSON backup is required (runtime stamps only).
3. Prior Release ZIP remains `storefront-v0.9.36` (tagged) or the 0.9.39 tree currently on `main` for DEV bind-mount rollback.

---

## 10. Deferred / follow-ups

- `biopentra-loop-card` `prefers-reduced-motion` for hover translate/scale, overlay transform, and pulse keyframes.
- PDP motion; ATC success pop.
- Contact / SEO landing **section** reveals.
- Production replay (separate PO GO). Plugin version bump + `storefront-v0.9.40` completed at PO visual freeze.

---

## 11. File-change manifest (implementation)

- [`plugins/biopentra-storefront/includes/class-biopentra-storefront.php`](../../../plugins/biopentra-storefront/includes/class-biopentra-storefront.php)
- new `plugins/biopentra-storefront/includes/motion-assets.php`
- [`plugins/biopentra-storefront/assets/css/bp-tokens.css`](../../../plugins/biopentra-storefront/assets/css/bp-tokens.css)
- new `plugins/biopentra-storefront/assets/css/bp-motion.css`
- new `plugins/biopentra-storefront/assets/js/bp-motion.js`
- later: change record under `docs/storefront-redesign/changes/`; validation under `docs/storefront-redesign/validation/motion-1/`
- later: acceptance spec via `storefront-acceptance` conventions (sibling harness; not a `storefront-v*` release)

**Do not modify:** `biopentra-loop-card`, `biopentra-blocksy-child`, Elementor JSON, plugin version/header, `readme.txt` Stable tag, GitHub release tags.
