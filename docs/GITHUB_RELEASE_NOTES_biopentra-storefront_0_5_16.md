# biopentra-storefront 0.5.16

**Mini-cart close behavior and quantity persistence** — stop delayed restore timers from reopening the drawer after the user closes it, and keep drawer quantity changes after refresh.

## What Changed

- Keeps the `0.5.15` remove behavior: removing an item refreshes fragments and leaves the drawer open on the empty-cart state.
- Cancels pending drawer restore timers when the user intentionally clicks outside the drawer or otherwise closes it.
- Tightens the restore retry window so it only covers the remove/fragment refresh transition.
- Routes drawer plus/minus quantity changes through the BioPentra quantity endpoint and saves the updated cart session before refreshing fragments, so quantities no longer revert after refresh.

## Compatibility

- Does not modify WooCommerce core, Elementor Pro, Blocksy core, payment gateways, or commerce/business logic plugins.
- Preserves WooCommerce cart fragments and the custom drawer remove AJAX flow.

## Install / Upgrade

1. Download **`biopentra-storefront-0.5.16.zip`** from this release.
2. Upload via **Plugins → Add New → Upload**, or use **Dashboard → Updates** on production.
3. Clear site/cache layers if drawer assets appear stale.

## Rollback

Reinstall **`biopentra-storefront-0.5.15.zip`** from the previous GitHub release, then clear object/site cache if needed.
