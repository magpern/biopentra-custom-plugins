# biopentra-storefront 0.9.41

**Bugfix — Milestone E header chrome CLI: honour `BIOPENTRA_E_SKIP_UMC` for the switcher widget.**

## Highlights

- `scripts/setup-milestone-e-chrome-cli.php`: when `BIOPENTRA_E_SKIP_UMC=1`, the script no longer inserts the Elementor `shortcode` widget containing `[universal_multicurrency_switcher]` into the header.
- Previously that env var only skipped the `umc_settings` option write (and its hard `exit(1)`), so a host without the `universal-multicurrency` plugin — e.g. production before that cutover — ended up with a broken/empty shortcode rendered in the site header.
- When skipped the script now prints `Header {id}: UMC switcher widget skipped (SKIP_UMC)`.
- The compact header search control (`data-bp-chrome-search`) is still inserted **unconditionally**; the function stays idempotent and still saves when only the search widget is added.
- Top docblock updated to document the widened `BIOPENTRA_E_SKIP_UMC` behaviour.
- New standalone test `tests/test-setup-milestone-e-chrome.php` (no WordPress/PHPUnit): with `BIOPENTRA_E_SKIP_UMC=1` and a fake header containing a `woocommerce-menu-cart` widget it asserts (a) no `universal_multicurrency_switcher` widget added, (b) `data-bp-chrome-search` added, (c) no non-zero exit, (d) `umc_settings` untouched.

## Scope

- **No runtime plugin behaviour, database, or schema change.** `scripts/` and `tests/` are git-only and excluded from the production ZIP.
- No Elementor JSON is mutated by installing this release; the CLI only mutates `_elementor_data` when an operator runs it.

## Install

1. Download `biopentra-storefront-0.9.41.zip` from this Release.
2. Replace the plugin on the target WordPress host (DEV bind-mounts git; production uses this ZIP only when separately authorized).
3. No WP-CLI replay or cache-bust required.

## Notes

- Tag: `storefront-v0.9.41`
- Rollback baseline: `storefront-v0.9.40`
- Production rollout still requires an explicit GO beyond this tag.
