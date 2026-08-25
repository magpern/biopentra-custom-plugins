# M1 UPR — DEV replay record (executed)

**Date:** 2026-08-26  
**Environment:** DEV WooCommerce stack only — **no production change**  
**Generic freeze tag:** `m1-core-enablement-freeze` (`34e3091`)  
**Host freeze merge:** `biopentra-custom-plugins` PR #2 (`56f27da`)  
**UPR build:** `feat/m1-core-enablement` branch (`ad1cc39`)

---

## 1. Compose bind-mount applied

Added to `wordpress` and `wpcli` services in `/opt/biopentra/apps/wordpress/compose.yml`:

```yaml
- /opt/biopentra/dev/universal-product-reviews:/var/www/html/wp-content/plugins/universal-product-reviews
```

Validated with `docker compose config` and `docker compose up -d wordpress`.

---

## 2. Option snapshot

### Before

| Option | Value |
|--------|-------|
| `woocommerce_enable_reviews` | `no` |
| `woocommerce_enable_review_rating` | `yes` |
| `woocommerce_review_rating_required` | `yes` |
| `woocommerce_review_rating_verification_required` | `no` |
| `woocommerce_review_rating_verification_label` | `yes` |
| `woocommerce_feature_customer_review_request_enabled` | `no` |
| `comment_moderation` | *(empty)* |
| `comment_whitelist` | `1` |

### After replay

| Option | Value |
|--------|-------|
| `woocommerce_enable_reviews` | `yes` |
| `woocommerce_enable_review_rating` | `yes` |
| `woocommerce_review_rating_required` | `yes` |
| `woocommerce_review_rating_verification_required` | `yes` |
| `woocommerce_review_rating_verification_label` | `yes` |
| `woocommerce_feature_customer_review_request_enabled` | `no` |
| `comment_moderation` | *(empty — unchanged)* |
| `comment_whitelist` | `1` *(unchanged)* |

---

## 3. Activation

```bash
wp plugin activate universal-product-reviews
```

Result: **active** (v0.1.0 feature branch).

---

## 4. Validation evidence

| Check | Result |
|-------|--------|
| HPOS `custom_order_tables` compatible | **PASS** — `FeaturesUtil::get_compatible_features_for_plugin()` lists `custom_order_tables` under `compatible` |
| Moderation hold (`1` → `0`) on product review | **PASS** — `ReviewModeration::hold_new_product_reviews()` |
| Preserve spam decision | **PASS** |
| Guest product review blocked | **PASS** — `GuestSubmissionGuard` triggers `wp_die` for guest + `comment_type=review` on product |
| Blog comment approval unchanged | **PASS** — inserted blog comment remains approved (`1`) |
| Global comment settings unchanged | **PASS** — `comment_moderation` empty, `comment_whitelist=1` before/after |
| M2+ artefacts absent | **PASS** — no tables, AS jobs, CLI, tokens, email |

Test product created for validation: **UPR M1 Test Product** (product ID 6694).

---

## 5. Rollback procedure (verified steps)

1. `wp plugin deactivate universal-product-reviews`
2. Remove compose bind-mount lines from `apps/wordpress/compose.yml` (wordpress + wpcli)
3. `docker compose up -d wordpress`
4. Restore options:

```bash
wp option update woocommerce_enable_reviews no
wp option update woocommerce_review_rating_verification_required no
```

5. Confirm plugin inactive and review settings match pre-M1 snapshot.

---

## 6. Exclusions confirmed

- No storefront/theme UI changes
- No production deploy
- No invitation/token/email/scheduler/table features

---

## Related

- [`m1-freeze.md`](m1-freeze.md) — pre-implementation freeze specification
