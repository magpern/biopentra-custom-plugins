# Milestone P0 — Production Rollout Playbook

**Status:** **OPERATIONAL FREEZE** — ready for production execution  
**Approval:** Milestone E APPROVED (2026-08-05). Playbook approved in principle; refinements below frozen.  
**Do not deploy** until an explicit production execution prompt.  
**Do not begin Milestone F** as a separate planning track — **P0 is the production rollout.**

**Target:** `https://www.biopentra.eu`  
**Source of truth (dev):** `https://dev.biopentra.eu` — validated baseline Milestone E  
**Acceptance baseline:** `storefront-acceptance` `e09d66c` — `tools/run-dev.sh --milestone-e` → **264 passed / 0 failed / 0 flaky / 306 skipped**

**Principle:** Implementation is complete on dev. P0 only installs release ZIPs, runs idempotent CLIs, flushes caches, and validates. **Never copy the development database to production.**

---

## 0. How to use this playbook

1. Execute sections **in order**. Do not skip preflight, baseline recording, or the GO / NO-GO gate.
2. Record every checkbox result in the execution log (§18).
3. Stop and escalate on any hard fail in §13 Success criteria, or on any **Immediate rollback** trigger in §10.0.
4. Keep a second engineer available for rollback (§10 / §16).

### 0.1 Production change freeze

**During P0, no code commits, plugin updates, content edits, configuration changes, or manual Elementor edits may be made unless explicitly described in this playbook.**

Unexpected production changes invalidate deployment evidence. If an out-of-band change is discovered mid-window: **STOP**, document it in §18, and obtain a fresh GO before continuing.

### Conventions

| Symbol | Meaning |
|---|---|
| `PROD_WP` | Production WordPress working directory (where `wp` / `docker compose` for WP-CLI works) |
| `wp …` | Production WP-CLI. On dockerized hosts: `docker compose run --rm -T wpcli wp …` from `PROD_WP` |
| `STAGING_DIR` | Local/ops directory for ZIPs, CLI copies, and prod backups (not the web root) |
| Flush trio | `wp elementor flush-css` → `wp cache flush` → Cloudflare purge (§8–§9) |

Adapt only the **transport** (`wp` vs `docker compose run … wp`). Do not change CLI script names, option keys, or order.

---

## 1. Production deployment order

Batched final-state rollout (code already contains A–E). Database/Elementor steps still run in milestone order.

```text
P0.0  Preflight + backups + production baseline record (§4)
P0.1  Stage ZIPs + CLI scripts (from git; ZIPs exclude scripts/)
      ── GO / NO-GO GATE (§5) — mandatory; STOP if any item fails ──
P0.2  Install packages (code only)
        → loop-card 1.6.1
        → blocksy-child 1.1.0
        → storefront 0.9.0
P0.3  Elementor A — homepage IA
P0.4  Elementor B — shop + SEO category layouts
P0.5  SEO D3 — migrate + validate page-owned grid meta
P0.6  Elementor E — header/footer TB IDs → chrome + footer CLIs (+ UMC)
P0.7  Flush trio + Cloudflare
P0.8  Automated + manual validation
P0.9  Sign-off / freeze as production baseline
```

| Step | Why this position |
|---|---|
| Code before Elementor | CLIs and front-end assets must exist before layouts run |
| A before B | Home does not depend on shop; shop/SEO share shopping helpers |
| SEO migrate after B layout | `setup-seo-category-v2-cli.php` can seed meta; migrate makes ownership explicit/idempotent |
| E last among Elementor | Header/footer chrome + UMC after commercial pages exist |
| Flush once at end (plus after each Elementor batch if preferred) | Mandatory after all DB writes; optional mid-flight flushes are safe |

**Out of scope for P0:** checkout/payment changes, new features, Milestone F planning, copying content from the dev DB.

---

## 2. Required release ZIPs

Install **exactly** these versions (final E stack). Earlier milestone ZIPs are superseded by these.

| Package | Version | Tag | Download |
|---|---|---|---|
| `biopentra-storefront` | **0.9.0** | `storefront-v0.9.0` | https://github.com/magpern/biopentra-custom-plugins/releases/tag/storefront-v0.9.0 → `biopentra-storefront-0.9.0.zip` (~110976 bytes) |
| `biopentra-loop-card` | **1.6.1** | `v1.6.1` | https://github.com/magpern/biopentra-loop-card/releases/tag/v1.6.1 → `biopentra-loop-card-1.6.1.zip` |
| `biopentra-blocksy-child` (theme) | **1.1.0** | `v1.1.0` | https://github.com/magpern/biopentra-blocksy-child/releases/tag/v1.1.0 → `blocksy-child-1.1.0.zip` |

### Verify before upload

```bash
# From STAGING_DIR after download
sha256sum biopentra-storefront-0.9.0.zip biopentra-loop-card-1.6.1.zip blocksy-child-1.1.0.zip
# Persist hashes into $STAGING_DIR/backups/p0-*/zip-sha256.txt for the deployment record
# Unzip headers (example for storefront)
python3 - <<'PY'
import zipfile, re
z=zipfile.ZipFile('biopentra-storefront-0.9.0.zip')
php=z.read('biopentra-storefront/biopentra-storefront.php').decode()
assert re.search(r'Version:\s*0\.9\.0', php)
assert "BIOPENTRA_STOREFRONT_VERSION', '0.9.0'" in php.replace('"', "'")
assert any(n.endswith('chrome-v1.css') for n in z.namelist())
assert not any('/scripts/' in n or '.git/' in n for n in z.namelist())
print('storefront ZIP OK')
PY
```

### Rollback ZIPs (download now; keep in `STAGING_DIR/rollback/`)

| If rolling back… | Reinstall |
|---|---|
| Storefront only (post-E chrome) | `storefront-v0.8.0` — https://github.com/magpern/biopentra-custom-plugins/releases/tag/storefront-v0.8.0 |
| Full redesign code stack | Whatever versions production reports **before** P0.2 (record in §4 / §18). Absolute last resort: pre-redesign storefront ≤ `0.5.21` + prior loop-card/child — only if preflight recorded them. |

---

## 3. Required CLI commands

Production ZIPs **exclude** `scripts/`. Stage CLIs from git tag `storefront-v0.9.0` into:

`wp-content/plugins/biopentra-storefront/scripts/` on production **or** run `wp eval-file` with absolute paths to staged copies.

### CLI inventory (must stage)

| Script | Milestone | Role |
|---|---|---|
| `setup-home-page-v2-cli.php` | A | Homepage commercial IA |
| `setup-shop-page-v2-cli.php` | B | Shop commercial IA |
| `setup-seo-category-v2-cli.php` | B | SEO guide page layouts (+ may seed meta) |
| `migrate-seo-grid-meta-cli.php` | D3 | Idempotent `_bp_seo_grid_*` seed |
| `validate-seo-grid-meta-cli.php` | D3 | Hard gate — 0 errors required |
| `rollback-seo-grid-meta-cli.php` | D3 | Rollback helper (stage for recovery) |
| `setup-milestone-e-chrome-cli.php` | E | UMC `manual` + header shortcode + search control |
| `setup-milestone-e-footer-cli.php` | E | Footer mobile spacing |

**Optional / do not run unless production header lacks Information mega:** `cli-update-megamenu.php` (one-shot; verify need first).

### Lookup rules (no hardcoded dev page IDs for A/B/D3)

| Surface | Resolution |
|---|---|
| Home | `page_on_front` (fallback slug `home`) |
| Shop | `woocommerce_shop_page_id` (fallback slug `shop`) |
| SEO guides | Slugs: `growth-hormone-releasing-peptides`, `metabolic-research-peptides`, `lyophilized-research-materials` |
| Header / footer Theme Builder | **Must discover on production** — see §6 (dev IDs 3782/3823 are **not** portable) |

---

## 4. Production baseline (P0.0 — record before any change)

Complete this **before** package install. Persist outputs under `$STAGING_DIR/backups/p0-YYYYMMDD/baseline/` and copy key fields into §18.

### 4.1 Platform snapshot

```bash
B="$STAGING_DIR/backups/p0-$(date +%Y%m%d)/baseline"
mkdir -p "$B"

wp core version > "$B/wordpress-version.txt"
wp plugin get woocommerce --field=version > "$B/woocommerce-version.txt" 2>/dev/null || \
  wp eval 'echo defined("WC_VERSION")?WC_VERSION:"missing";' > "$B/woocommerce-version.txt"
wp eval 'echo PHP_VERSION;' > "$B/php-version.txt"
wp theme list --status=active --format=csv > "$B/active-theme.csv"
wp theme list --format=csv > "$B/all-themes.csv"
```

### 4.2 Package versions (pre-P0)

```bash
wp plugin list --format=csv > "$B/plugins-all.csv"
wp plugin list --status=active --format=csv > "$B/plugins-active.csv"

# Named first-party packages (record even if inactive/missing)
wp plugin get biopentra-storefront --fields=name,status,version --format=csv > "$B/biopentra-storefront.csv" || echo 'missing' > "$B/biopentra-storefront.csv"
wp plugin get biopentra-loop-card --fields=name,status,version --format=csv > "$B/biopentra-loop-card.csv" || echo 'missing' > "$B/biopentra-loop-card.csv"
wp theme list --format=csv | grep -i blocksy > "$B/blocksy-themes.csv" || true
```

Record into §18 at minimum:

| Field | Source |
|---|---|
| WordPress version | `wordpress-version.txt` |
| WooCommerce version | `woocommerce-version.txt` |
| PHP version | `php-version.txt` |
| Active theme (stylesheet + version) | `active-theme.csv` |
| Storefront version (pre) | `biopentra-storefront.csv` |
| Loop-card version (pre) | `biopentra-loop-card.csv` |
| Blocksy child version (pre) | `blocksy-themes.csv` / active theme |
| Active plugin list | `plugins-active.csv` (full export retained) |

### 4.3 Discovery + Elementor / option backups

```bash
# Home / shop / SEO pages
wp option get page_on_front
wp option get woocommerce_shop_page_id
wp post list --post_type=page --name=growth-hormone-releasing-peptides,metabolic-research-peptides,lyophilized-research-materials --fields=ID,post_name,post_status

# Theme Builder candidates — identify active sitewide Header + Footer
wp post list --post_type=elementor_library --fields=ID,post_title,post_status --posts_per_page=50
# Record PROD_HEADER_TB_ID and PROD_FOOTER_TB_ID after Theme Builder confirmation
```

```bash
ROOT="$STAGING_DIR/backups/p0-$(date +%Y%m%d)"
mkdir -p "$ROOT"
HOME_ID=$(wp option get page_on_front | tr -d '\r')
SHOP_ID=$(wp option get woocommerce_shop_page_id | tr -d '\r')

wp post meta get "$HOME_ID" _elementor_data > "$ROOT/home-pre-P0.json"
wp post meta get "$SHOP_ID" _elementor_data > "$ROOT/shop-pre-P0.json"

for SLUG in growth-hormone-releasing-peptides metabolic-research-peptides lyophilized-research-materials; do
  PID=$(wp post list --post_type=page --name="$SLUG" --field=ID | tr -d '\r')
  wp post meta get "$PID" _elementor_data > "$ROOT/seo-${SLUG}-pre-P0.json"
done

wp post meta get "$PROD_HEADER_TB_ID" _elementor_data > "$ROOT/header-tb-pre-P0.json"
wp post meta get "$PROD_FOOTER_TB_ID" _elementor_data > "$ROOT/footer-tb-pre-P0.json"

wp option get umc_settings --format=json > "$ROOT/umc_settings-pre-P0.json"
wp option get cky_options --format=json > "$ROOT/cky_options-pre-P0.json" || true
```

Also:

- Production **database** backup completed and restore-tested (ops procedure for this host).
- Production **filesystem** backup of `wp-content/plugins`, `wp-content/themes`, and relevant uploads Elementor CSS paths (ops procedure).
- Screenshots @360×800 before/: home, shop, one SEO guide, simple PDP, variable PDP, cart → `$ROOT/screenshots/before/`.

Confirm header/footer templates contain expected widgets (logo, nestable menu / mega, header auth or equivalent, menu cart) before GO.

---

## 5. GO / NO-GO CHECK (mandatory before P0.2)

Complete **after** P0.0–P0.1 (baseline, backups, ZIPs staged, CLIs staged, TB IDs identified).  
Complete **before** any package install (P0.2).

### GO / NO-GO CHECK

| ☐ | Item |
|---|---|
| ☐ | Production database backup completed and verified |
| ☐ | Production filesystem backup completed and verified |
| ☐ | Release ZIP hashes verified (§2) |
| ☐ | Rollback ZIPs downloaded and locally available (`STAGING_DIR/rollback/`) |
| ☐ | WP-CLI verified working (`wp core version` succeeds on production) |
| ☐ | Cloudflare access verified (can purge) |
| ☐ | Required CLI scripts staged (§3 inventory) |
| ☐ | Active production Header Theme Builder template identified (`PROD_HEADER_TB_ID`) |
| ☐ | Active production Footer Theme Builder template identified (`PROD_FOOTER_TB_ID`) |
| ☐ | SEO guide pages verified (three slugs present and published or known-good) |
| ☐ | Production baseline recorded (§4 → §18) |
| ☐ | Operator has reviewed rollback procedure (§10) and decision matrix (§10.0) |
| ☐ | Deployment approval recorded (approver name + timestamp in §18) |
| ☐ | Production change freeze acknowledged (§0.1) |

**If any item is not satisfied: STOP. Do not begin deployment (do not start P0.2).**

Only when every box is checked may the operator proceed to package install.

---

## 6. Elementor replay sequence

### 6.1 Apply Elementor (after GO and §2 packages installed)

```bash
# A — Homepage
wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-home-page-v2-cli.php
# Expect: success / idempotent OK messages; no ERROR

# B — Shop
wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-shop-page-v2-cli.php

# B — SEO category pages
wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-seo-category-v2-cli.php
# WARN if a slug is missing on production — stop and reconcile pages before continuing
```

### 6.2 Apply Elementor E (after §7 SEO migrate)

```bash
export BIOPENTRA_E_HEADER_POST_ID="$PROD_HEADER_TB_ID"
export BIOPENTRA_E_FOOTER_POST_ID="$PROD_FOOTER_TB_ID"

wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-milestone-e-chrome-cli.php
# Sets umc_settings.display.placement=manual
# Inserts [universal_multicurrency_switcher] + search control if missing

wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-milestone-e-footer-cli.php
# Applies mobile padding/gap map by Elementor element ids from the frozen footer structure
# If footer structure differs from the redesign footer, STOP — do not force; reconcile template first
```

**Idempotency:** Re-running A/B/E CLIs is safe when markers/settings already match. Prefer re-run over manual editor edits.

---

## 7. SEO content migration sequence

**Hard gate (from Milestone D):** Do not consider B/C/D production-complete without D3 meta migration + validation.

```bash
# 1) Migrate (idempotent)
wp eval-file wp-content/plugins/biopentra-storefront/scripts/migrate-seo-grid-meta-cli.php
# Expect written≥0 / skipped for already-migrated pages

# Force overwrite only if intentional:
# BIOPENTRA_SEO_META_FORCE=1 wp eval-file …/migrate-seo-grid-meta-cli.php

# 2) Validate — HARD GATE
wp eval-file wp-content/plugins/biopentra-storefront/scripts/validate-seo-grid-meta-cli.php
# Require: 0 errors
# Warnings for unpublished/private seed slugs are acceptable if documented (same as dev)
```

### Meta keys

| Key | Purpose |
|---|---|
| `_bp_seo_grid_products` | Ordered product slugs for curated grid |
| `_bp_seo_archive_term` | WC `product_cat` slug for “Browse all” |

### Seed pages / archives (must exist on production)

| Page slug | Archive term slug |
|---|---|
| `growth-hormone-releasing-peptides` | `growth-performance` |
| `metabolic-research-peptides` | `weight-management` |
| `lyophilized-research-materials` | `research-peptides` |

If validate fails with **missing pages** or **zero published products**, fix catalog/content ownership **before** continuing to E or declaring success.

---

## 8. Cache flush order

After **all** DB/Elementor/option writes (or after each Elementor batch):

```bash
# 1) Elementor CSS
wp elementor flush-css

# 2) Object cache (Redis/etc.)
wp cache flush

# 3) Optional: delete per-post Elementor CSS meta if a page still serves stale CSS
# wp post meta delete <ID> _elementor_css
# wp post meta delete <ID> _elementor_element_cache

# 4) Cloudflare — see §9 (always last)
```

**Order rule:** Elementor flush → WP object cache → Cloudflare. Never Cloudflare-only after Elementor changes.

---

## 9. Cloudflare considerations

Production zone uses Cloudflare (orange cloud). SSL mode must remain **Full (strict)**.

| Action | Detail |
|---|---|
| Purge after P0 | Purge at least: `/`, `/shop/`, `/product-category/*` sample, one PDP, and **all CSS** under `/wp-content/uploads/elementor/css/` and plugin/theme CSS URLs |
| Prefer | “Purge Everything” once at end of P0 if traffic allows (simplest, safest for Elementor CSS) |
| Cache rules | Do not disable HTML caching permanently; purge is enough |
| Verify | Hard-refresh logged-out mobile: `chrome-v1.css?ver=0.9.0` loads; Elementor CSS timestamps refresh |
| Real-IP | Unchanged; do not edit nginx/CF IP restore as part of P0 |

If a surface still shows old layout after purge: re-run `wp elementor flush-css`, delete that page’s `_elementor_css`, flush object cache, purge CF URL again.

---

## 10. Rollback strategy

Execute **bottom-up** until the site is stable. Prefer the smallest layer that restores commerce.  
Operators **must not improvise** — use §10.0 first, then §10.1 / §10.2.

### 10.0 Rollback decision matrix (mandatory)

| Condition | Action |
|---|---|
| Homepage unusable (blank, fatal, cannot reach products) | **Immediate rollback** (§10.1 then §10.2 if needed) |
| Shop cannot list products | **Immediate rollback** |
| Add-to-cart broken | **Immediate rollback** |
| Checkout broken / payment step unreachable | **Immediate rollback** |
| Major PHP fatal (white screen, repeated 500s) | **Immediate rollback** |
| SEO validate returns errors after migrate and cannot be fixed within timeline without risking commerce | **Immediate rollback** of SEO meta + affected Elementor; escalate |
| Commerce intact; cosmetic layout defect on one surface | **Continue**; document in §18; fix-forward or soft-restore that surface after sign-off decision |
| Minor CSS / spacing issue | **Continue**; document |
| Cookie / banner overlap only | **Continue** if add-to-cart, shop, and checkout remain usable; document |
| Timeline significantly exceeded (§14) without clear recovery path | **Pause** for deployment review before continuing or rolling back |

“Immediate rollback” means: stop further CLIs, execute §10.1, verify commerce, then §10.2 if still broken, then §16 recovery close-out.

### 10.1 Immediate soft rollback (Elementor / options only)

```bash
B="$STAGING_DIR/backups/p0-YYYYMMDD"   # path from §4.3

# Restore pages
HOME_ID=$(wp option get page_on_front | tr -d '\r')
SHOP_ID=$(wp option get woocommerce_shop_page_id | tr -d '\r')
wp post meta update "$HOME_ID" _elementor_data "$(cat "$B/home-pre-P0.json")"
wp post meta update "$SHOP_ID" _elementor_data "$(cat "$B/shop-pre-P0.json")"
# …repeat for each seo-*-pre-P0.json and header/footer TB backups

# UMC
wp option update umc_settings --format=json < "$B/umc_settings-pre-P0.json"

# SEO meta
wp eval-file wp-content/plugins/biopentra-storefront/scripts/rollback-seo-grid-meta-cli.php

wp elementor flush-css && wp cache flush
# Cloudflare purge everything
```

### 10.2 Code rollback

| Symptom | Action |
|---|---|
| Chrome / z-index / search only | Reinstall `biopentra-storefront-0.8.0.zip` |
| Cards / archives / related | Reinstall prior loop-card ZIP recorded in §4 baseline |
| Sticky PDP | Reinstall prior child theme ZIP recorded in §4 baseline |
| Full abort | Restore all three preflight package versions + §10.1 |

Activate after ZIP install:

```bash
wp plugin activate biopentra-storefront biopentra-loop-card
wp theme activate blocksy-child   # confirm child slug on production
wp elementor flush-css && wp cache flush
```

### 10.3 What not to do

- Do not import the dev database.
- Do not “fix” production Elementor by hand without a new backup.
- Do not leave UMC at `manual` with no header switcher (restore placement or restore header).

---

## 11. Production validation checklist

### 11.1 Automated

From a machine with Docker + checkout of `storefront-acceptance` @ `e09d66c` or newer:

```bash
cd /path/to/storefront-acceptance

# Full production suite (read-only HTTP; no Coming Soon bypass)
bash tools/run-prod.sh

# Milestone-E equivalent filter (until run-prod.sh gains --milestone-e):
docker run --rm \
  -v "$PWD":/work -w /work \
  -e WP_BASE_URL=https://www.biopentra.eu \
  -e HOME=/tmp \
  mcr.microsoft.com/playwright:v1.51.0-jammy \
  bash -lc 'npm install && npx playwright test \
    tests/baseline.spec.ts tests/home-ia.spec.ts tests/shop-ia.spec.ts \
    tests/card-interaction.spec.ts tests/canonical-cards.spec.ts \
    tests/seo-meta.spec.ts tests/image-budget.spec.ts \
    tests/pdp-layout.spec.ts tests/pdp-sticky.spec.ts \
    tests/chrome-fixed-ui.spec.ts tests/chrome-header.spec.ts'
```

**Pass bar:** 0 failed, 0 flaky. Investigate any production-only skips caused by missing fixtures (e.g. product slug differences) before sign-off.

### 11.2 WP-CLI post-checks

```bash
wp plugin list --status=active | grep -E 'biopentra-storefront|biopentra-loop-card'
wp theme list | grep -E 'blocksy'
wp eval 'echo BIOPENTRA_STOREFRONT_VERSION;'
wp option get umc_settings --format=json | python3 -c "import sys,json; u=json.load(sys.stdin); print(u['display']['placement'])"
# Expect: manual

wp eval-file wp-content/plugins/biopentra-storefront/scripts/validate-seo-grid-meta-cli.php
```

### 11.3 Spot HTML checks (logged-out)

| URL | Expect |
|---|---|
| `/` | `#biopentra-shop-s`, category chips, `.biopentra-loop-card-root`, editorial below grids |
| `/shop/` | Search + chips above products |
| SEO guide slugs | Product grid above long-form; archive link |
| `/product-category/research-peptides/` | Canonical loop cards (not Blocksy-only grid) |
| `/?s=peptide&post_type=product` | Canonical cards + `#biopentra-search-refine` |
| Variable PDP | Sticky bar on mobile after scroll; ATC usable after cookie accept |
| Any page | Exactly one `.umc-switcher`; **zero** `.umc-switcher--floating-bottom` |
| Any page | `chrome-v1.css` enqueued |

---

## 12. Mobile smoke-test checklist

Viewports: **360 / 390 / 430** (primary), spot **768 / 1440**. Logged-out. Cookie banner: accept once, retest revisit control.

| # | Check | 360 | 390 | 430 |
|---|---|---|---|---|
| 1 | Home: first product ≤ ~1 viewport | ☐ | ☐ | ☐ |
| 2 | Home: search focuses via header search control | ☐ | ☐ | ☐ |
| 3 | Menu toggle ≥ 44×44; opens/closes; Escape works | ☐ | ☐ | ☐ |
| 4 | Account + cart controls usable; mini-cart overlay OK | ☐ | ☐ | ☐ |
| 5 | Currency switcher in header works; no bottom floater | ☐ | ☐ | ☐ |
| 6 | Shop: search + chips above products | ☐ | ☐ | ☐ |
| 7 | Search results: refine UI; cards clickable / quick-add | ☐ | ☐ | ☐ |
| 8 | WC archive: canonical cards; no horizontal overflow | ☐ | ☐ | ☐ |
| 9 | Simple PDP: gallery, ATC, related cards | ☐ | ☐ | ☐ |
| 10 | Variable PDP: sticky ATC after scroll; sync with form | ☐ | ☐ | ☐ |
| 11 | Cookie banner under sticky; preferences open above | ☐ | ☐ | ☐ |
| 12 | Footer compact; legal/research links crawlable | ☐ | ☐ | ☐ |
| 13 | Cart + checkout **visual only** — payment flow unchanged | ☐ | ☐ | ☐ |
| 14 | No horizontal overflow on home/shop/PDP | ☐ | ☐ | ☐ |

---

## 13. Success criteria

P0 is **successful** only when all are true:

1. Active packages: storefront **0.9.0**, loop-card **1.6.1**, child **1.1.0**.
2. `umc_settings.display.placement` = **`manual`**; one header switcher; no floating-bottom UMC.
3. Homepage / shop / SEO Elementor IA applied (search + chips + grids; SEO grid above body).
4. SEO validate CLI: **0 errors**.
5. Header/footer E chrome applied on **production** Theme Builder IDs.
6. Flush trio + Cloudflare purge completed after final writes.
7. Automated suite: **0 failed / 0 flaky** against `https://www.biopentra.eu`.
8. Mobile smoke checklist (§12) complete with no Immediate-rollback conditions (§10.0).
9. Checkout/payment behaviour unchanged (smoke only).
10. Execution log (§18) filled including §4 baseline; backups retained ≥ 30 days.
11. GO / NO-GO (§5) was completed with all boxes checked before P0.2.
12. **No** open Immediate-rollback conditions from §10.0.

---

## 14. Estimated deployment time and timeline checkpoints

### 14.1 Phase estimates

| Phase | Estimate |
|---|---|
| Preflight, baseline, backups, discovery, GO/NO-GO | 45–75 min |
| Package install + activate | 15–30 min |
| Elementor A + B CLIs | 20–40 min |
| SEO migrate + validate | 10–20 min |
| Elementor E | 20–45 min |
| Flush + Cloudflare | 10–15 min |
| Automated Playwright | 45–75 min |
| Manual mobile smoke | 45–90 min |
| **Total (clean run)** | **~3.5–6.5 hours** |
| Buffer for template mismatch / CF / catalog gaps | +1–3 hours |

Schedule a **half-day** maintenance window with a named rollback owner.

### 14.2 Timeline checkpoints (from window start T+0)

| Checkpoint | Target | Meaning |
|---|---|---|
| T+00:30 | Preflight + baseline complete | §4 done; discovery IDs recorded |
| T+00:45 | GO / NO-GO passed | §5 all boxes checked |
| T+01:15 | Packages installed | P0.2 complete; versions verified |
| T+02:00 | Elementor A+B complete | P0.3–P0.4 done |
| T+02:30 | SEO migration validated | P0.5 validate CLI 0 errors |
| T+03:00 | Elementor E + flush + CF | P0.6–P0.7 done |
| T+03:45 | Automated validation complete | Playwright 0 failed / 0 flaky |
| T+04:30 | Manual mobile validation complete | §12 done |
| T+05:00 | Final sign-off | §13 + §18 complete |

**Significant deviation** from this timeline (roughly **>60 minutes behind** a checkpoint without a clear recovery path) **must trigger a deployment review** before continuing. The review decides: continue with a revised ETA, pause, or execute §10 rollback. Do not silently extend the window.

---

## 15. Downtime expectations

| Mode | Expectation |
|---|---|
| Preferred | **No planned downtime.** ZIP installs and CLIs are online operations. |
| Brief inconsistency | 5–20 minutes while Elementor CSS regenerates / CF purge propagates — some visitors may see mixed old/new chrome. |
| Optional maintenance | Enable WooCommerce Coming Soon or a maintenance plugin only if production policy requires a frozen catalog during CLIs. |
| Checkout | Do **not** pause payments unless an Immediate-rollback condition (§10.0) appears mid-flight. |

Communicate: “Storefront layout update in progress; checkout remains available.”

---

## 16. Recovery procedure

### Sev-1 / Immediate-rollback conditions (§10.0)

1. **Stop** further CLIs.
2. Announce incident; freeze deploys (reinforce §0.1).
3. Run §10.1 soft rollback (Elementor + UMC + SEO meta).
4. If still broken → §10.2 reinstall preflight package versions from §4 baseline.
5. Flush trio + Cloudflare purge everything.
6. Verify: home loads, `/shop/` lists products, add-to-cart works, checkout loads.
7. Capture logs (`wp`, PHP, nginx) and Elementor errors; open incident note.
8. Do not re-attempt P0 until root cause is classified (template ID mismatch, missing page slug, ZIP extract failure, etc.).

### Continue / document conditions (§10.0 cosmetic)

1. Keep new packages if commerce works.
2. Restore only the affected `_elementor_data` backup **or** fix forward with a new idempotent CLI patch (new change record) — only after sign-off decision if still inside the window.
3. Flush trio + targeted CF purge.
4. Re-run the relevant Playwright project/spec only, then full prod suite before sign-off.

### Post-recovery

- Update §18 with timestamps and actions.
- Retain failed-state screenshots.
- If storefront ZIP was rolled back from 0.9.0 → 0.8.0, document that E chrome is not on production.

---

## 17. Step-by-step execution script (copy/paste outline)

```bash
# === P0.0 Preflight + baseline (§4) ===
# Record: date, engineer, PROD_WP path
# §4.1–4.2 platform + package baseline exports
# §4.3 discover PROD_HEADER_TB_ID / PROD_FOOTER_TB_ID; Elementor + umc backups
# Screenshots before/
# DB + filesystem backups (ops)

# === P0.1 Stage ZIPs + CLIs (§2–§3) ===
# Download release + rollback ZIPs; verify hashes
# Stage CLI *.php into plugin scripts/ or STAGING_DIR/cli

# === GO / NO-GO (§5) — STOP if incomplete ===
# All checkboxes must be checked before P0.2

# === P0.2 Install code ===
# Upload/install ZIPs via WP admin or wp plugin install --force / theme
wp plugin activate biopentra-storefront biopentra-loop-card
wp theme activate <blocksy-child-stylesheet>
wp eval 'echo defined("BIOPENTRA_STOREFRONT_VERSION")?BIOPENTRA_STOREFRONT_VERSION:"missing";'

# === P0.3–P0.4 Elementor A/B (§6.1) ===
wp eval-file …/setup-home-page-v2-cli.php
wp eval-file …/setup-shop-page-v2-cli.php
wp eval-file …/setup-seo-category-v2-cli.php

# === P0.5 SEO (§7) ===
wp eval-file …/migrate-seo-grid-meta-cli.php
wp eval-file …/validate-seo-grid-meta-cli.php   # MUST pass

# === P0.6 Elementor E (§6.2) ===
BIOPENTRA_E_HEADER_POST_ID=… BIOPENTRA_E_FOOTER_POST_ID=… \
  wp eval-file …/setup-milestone-e-chrome-cli.php
BIOPENTRA_E_FOOTER_POST_ID=… \
  wp eval-file …/setup-milestone-e-footer-cli.php

# === P0.7 Caches (§8–§9) ===
wp elementor flush-css
wp cache flush
# Cloudflare: Purge Everything

# === P0.8 Validate (§11–§12) ===
# run-prod.sh + mobile smoke
# Apply §10.0 if any Immediate-rollback condition appears

# === P0.9 Sign-off ===
# Fill §18; declare P0 complete only if §13 satisfied
```

---

## 18. Execution log (fill during deploy)

| Field | Value |
|---|---|
| Date / time start (UTC) | |
| Engineer | |
| Approver | |
| Deployment approval timestamp | |
| `PROD_WP` | |
| Change freeze acknowledged (§0.1) | ☐ |
| **Baseline — WordPress** | |
| **Baseline — WooCommerce** | |
| **Baseline — PHP** | |
| **Baseline — active theme** | |
| **Baseline — storefront (pre)** | |
| **Baseline — loop-card (pre)** | |
| **Baseline — blocksy-child (pre)** | |
| **Baseline — active plugins export path** | |
| DB backup verified | ☐ path: |
| Filesystem backup verified | ☐ path: |
| ZIP sha256 file path | |
| Rollback ZIPs path | |
| `PROD_HEADER_TB_ID` | |
| `PROD_FOOTER_TB_ID` | |
| Backup directory | |
| GO / NO-GO (§5) all checked | ☐ |
| Timeline checkpoint notes (§14.2) | |
| A CLI result | |
| B shop CLI result | |
| B SEO CLI result | |
| SEO migrate result | |
| SEO validate result | |
| E chrome CLI result | |
| E footer CLI result | |
| UMC placement after | |
| CF purge ticket/time | |
| Playwright command + result | |
| Mobile smoke result | |
| §10.0 conditions hit | |
| Rollback used? | |
| Sign-off | ☐ Success / ☐ Aborted |

---

## 19. References

| Doc | Use |
|---|---|
| [deployment/milestone-A-home.md](../deployment/milestone-A-home.md) | Home CLI + QA |
| [deployment/milestone-B-commercial-shopping.md](../deployment/milestone-B-commercial-shopping.md) | Shop/SEO CLI |
| [deployment/milestone-C-canonical-cards.md](../deployment/milestone-C-canonical-cards.md) | Loop-card expectations |
| [deployment/milestone-D.md](../deployment/milestone-D.md) | D package set / SEO gate |
| [deployment/milestone-E.md](../deployment/milestone-E.md) | E chrome/footer/UMC |
| [deployment/milestone-P0.md](../deployment/milestone-P0.md) | Deployment-record stub |
| [changes/milestone-D3-seo-content-ownership.md](../changes/milestone-D3-seo-content-ownership.md) | Meta keys + migrate/validate |
| [changes/milestone-E2-fixed-ui.md](../changes/milestone-E2-fixed-ui.md) | UMC + z-index |
| [validation/milestone-E.md](../validation/milestone-E.md) | Dev acceptance baseline |
| [README.md](../README.md) | Flush trio + no DB copy rule |

---

## 20. Explicit non-goals

- Do not start deployment until an explicit production execution prompt.
- Do not begin a separate “Milestone F” document set — **P0 replaces F for production rollout**.
- Do not change checkout, gateways, or cart AJAX.
- Do not invent new Elementor layouts on production outside these CLIs.
- Do not change package versions or deployment order in this playbook without a new frozen revision.
