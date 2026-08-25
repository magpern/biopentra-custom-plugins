# M1 UPR — host DEV freeze record

**Status:** Frozen specification for DEV integration (documentation only at freeze).  
**Generic spec:** `magpern/universal-product-reviews` tag `m1-core-enablement-freeze` → [`docs/milestones/M1-core-enablement.md`](https://github.com/magpern/universal-product-reviews/blob/main/docs/milestones/M1-core-enablement.md)

**Production:** No production change in M1. DEV only.

---

## 1. Scope

### In scope (DEV)

- Compose bind-mount for UPR plugin checkout
- Plugin activation on DEV
- WooCommerce review settings replay (checklist below)
- Validation and rollback procedures
- Evidence placeholders for Phase 2 implementation

### Out of scope (M1)

- Production deployment or credentials
- Storefront/theme review UI (M3)
- Invitations, tokens, email, Action Scheduler, custom tables (M2+)
- Global WordPress `comment_moderation` / `comment_whitelist` changes

---

## 2. DEV stack coordinates

| Component | Version (DEV) |
|-----------|---------------|
| Site | DEV WooCommerce stack |
| WordPress | 7.0.2 |
| PHP | 8.4 |
| WooCommerce | 11.0.1 |

Align generic plugin integration CI mandatory leg with these coordinates.

---

## 3. Compose bind-mount plan (not applied at freeze)

Add to **both** `wordpress` and `wpcli` services in `/opt/biopentra/apps/wordpress/compose.yml`:

```yaml
- /opt/biopentra/dev/universal-product-reviews:/var/www/html/wp-content/plugins/universal-product-reviews
```

**Apply in Phase 2 only:**

```bash
cd /opt/biopentra/apps/wordpress
docker compose config
docker compose up -d wordpress
```

---

## 4. Before-state option snapshot (captured at freeze)

Record date: 2026-08-26 (DEV)

| Option | Before value |
|--------|--------------|
| `woocommerce_enable_reviews` | `no` |
| `woocommerce_enable_review_rating` | `yes` |
| `woocommerce_review_rating_required` | `yes` |
| `woocommerce_review_rating_verification_required` | `no` |
| `woocommerce_review_rating_verification_label` | `yes` |
| `woocommerce_feature_customer_review_request_enabled` | `no` |
| `comment_moderation` | *(empty / unset)* |
| `comment_whitelist` | `1` |

---

## 5. Target WooCommerce settings (Phase 2 replay)

| Option | Target |
|--------|--------|
| `woocommerce_enable_reviews` | `yes` |
| `woocommerce_enable_review_rating` | `yes` |
| `woocommerce_review_rating_required` | `yes` |
| `woocommerce_review_rating_verification_required` | `yes` |
| `woocommerce_review_rating_verification_label` | `yes` *(preserve or host choice)* |
| `woocommerce_feature_customer_review_request_enabled` | `no` |

**Do not change:** `comment_moderation`, `comment_whitelist`.

### WP-CLI replay commands (DEV)

```bash
cd /opt/biopentra/apps/wordpress

docker compose run --rm wpcli wp option update woocommerce_enable_reviews yes
docker compose run --rm wpcli wp option update woocommerce_enable_review_rating yes
docker compose run --rm wpcli wp option update woocommerce_review_rating_required yes
docker compose run --rm wpcli wp option update woocommerce_review_rating_verification_required yes
# verification_label: preserve current or set explicitly
docker compose run --rm wpcli wp option update woocommerce_feature_customer_review_request_enabled no
```

---

## 6. Activation (Phase 2)

```bash
cd /opt/biopentra/apps/wordpress
docker compose run --rm wpcli wp plugin activate universal-product-reviews
```

Use feature branch build `feat/m1-core-enablement` until merged to UPR `main`.

---

## 7. Validation checklist (Phase 2 evidence)

- [ ] UPR active on DEV
- [ ] Target WooCommerce options match §5
- [ ] Global comment options unchanged from §4
- [ ] Logged-in verified purchaser review → **Pending**
- [ ] Guest product-review submission → **rejected**
- [ ] Blog comment behaviour unchanged
- [ ] HPOS compatibility visible in WooCommerce → Settings → Advanced → Features
- [ ] No M2+ artefacts (tables, AS jobs, CLI, tokens, email)

Evidence recorded in [`m1-dev-replay.md`](m1-dev-replay.md) after execution.

---

## 8. Rollback (DEV)

1. `docker compose run --rm wpcli wp plugin deactivate universal-product-reviews`
2. Remove compose bind-mount volume lines; `docker compose up -d wordpress`
3. Restore options from §4 snapshot:

```bash
docker compose run --rm wpcli wp option update woocommerce_enable_reviews no
docker compose run --rm wpcli wp option update woocommerce_review_rating_verification_required no
# restore comment_moderation / comment_whitelist if changed (M1 must not change them)
```

4. Confirm blog comment behaviour matches pre-M1 baseline.

---

## 9. Evidence placeholders

| Field | Value |
|-------|-------|
| Generic freeze tag | `m1-core-enablement-freeze` |
| Host freeze merge commit | *(record after PR merge)* |
| Implementation merge commit | *(Phase 2)* |
| DEV validation date | *(Phase 2)* |
| Rollback tested | *(Phase 3)* |

---

## 10. Explicit exclusions

- No production infrastructure change
- No storefront/theme CSS or PDP messaging (M3)
- No Rank Math / schema changes
- No fulfillment or support adapter wiring (M2+)
