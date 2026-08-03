# Milestone C — Validation

**Suite:** `storefront-acceptance` with `--milestone-c`  
**Command:**

```bash
cd /opt/biopentra/dev/storefront-acceptance
tools/run-dev.sh --milestone-c
```

## Spec coverage

| Spec | Scope |
|---|---|
| `baseline.spec.ts` | Regression guard |
| `home-ia.spec.ts` | Home commercial IA + 3608 cards |
| `shop-ia.spec.ts` | Shop grid + search refine |
| `card-interaction.spec.ts` | Stretched link, keyboard, axe, quick-add |
| `canonical-cards.spec.ts` | WC archive, search, related, upsells |

## Key assertions

- `ul.products.biopentra-canonical-wc-loop` on archives and search
- `.biopentra-loop-card-root[data-biopentra-enhanced="1"]` after JS enhance
- No nested anchors (`.biopentra-loop-card-root a a` count = 0)
- Pagination and `.woocommerce-result-count` preserved on archives/search
- Related/upsell sections: desktop-1440 only (Blocksy visibility)

## Report artifacts

After a green run, copy report summary here:

| Field | Value |
|---|---|
| Date | 2026-08-03 |
| Exit code | **0 (PASS)** |
| Tests passed | **230** (180 skipped viewport duplicates) |
| Unexpected / flaky | **0 / 0** |
| Command | `tools/run-dev.sh --milestone-c` |
| Report | `artifacts/playwright-report.json` (mtime 2026-08-03 20:32 +02 — latest green; not re-run for closeout) |
| Baseline release | `biopentra-loop-card` **v1.6.0** @ `5260a84` |

## Manual verification

```bash
curl -s 'https://dev.biopentra.eu/product-category/research-peptides/' \
  | grep -c 'biopentra-loop-card-root'

curl -s 'https://dev.biopentra.eu/product-category/research-peptides/' \
  | grep -c 'ct-media-container'
```

Expect first count > 0, second count = 0 inside product grid.
