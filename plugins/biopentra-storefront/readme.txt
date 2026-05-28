=== Biopentra Storefront ===
Contributors: magpern
Tags: woocommerce, storefront, elementor, header, mega menu, stock display, seo
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.5.17
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Consolidated storefront modules for Biopentra WooCommerce sites.

== Description ==

Biopentra Storefront bundles first-party storefront features into one plugin:

* Information mega-menu
* Footer contact (email shortcode, placeholder noindex)
* Variation stock selector (auto-select in-stock variation)
* Header auth (shortcode, Elementor widget, cart enhancements)
* Checkout payment display (gateway icon presentation)
* Technical SEO (meta, robots, schema, sitemap hygiene)
* Product stock display (configurable labels instead of exact stock counts)

Legacy standalone plugins with the same behavior should stay **deactivated** when this plugin is active (see README.md in the plugin folder).

Production installs use GitHub Releases from `magpern/biopentra-custom-plugins` (tags `storefront-v*`). Development sites typically sync from the monorepo and may disable the built-in GitHub updater.

== Installation ==

1. Upload `biopentra-storefront-{version}.zip` from a GitHub Release (tag `storefront-v*`).
2. Activate the plugin.
3. Deactivate duplicate legacy plugins: biopentra-header-auth, biopentra-footer-contact, biopentra-information-megamenu, custom-variation-stock-selector (when the matching storefront module is in use).

== Frequently Asked Questions ==

= Where do I configure stock labels? =

WooCommerce → Stock display (requires manage_woocommerce). Thresholds, text, and colors; use `{stock}` in the “only left” message for the quantity.

= Does this change inventory or cart behavior? =

No. Stock display only changes frontend availability text via `woocommerce_get_availability`.

== Changelog ==

= 0.5.17 =
* Mini-cart: restore WooCommerce widget cart total/buttons hooks and current remove-link attributes while preserving the custom drawer layout (header-auth module 1.5.18).

= 0.5.16 =
* Mini-cart: cancel delayed drawer restore timers when the user intentionally closes the empty drawer after removing an item (header-auth module 1.5.17).
* Mini-cart: make drawer plus/minus quantity updates persist before refreshing cart fragments.

= 0.5.15 =
* Mini-cart: keep the Elementor/Blocksy cart drawer open after removing an item through WooCommerce AJAX fragments (header-auth module 1.5.15).

= 0.5.14 =
* Checkout payment display: added display-only icons for BTCPay and VCCP card gateways using bundled Bitcoin, Visa, and Mastercard SVG assets.

= 0.5.13 =
* Mini-cart: implemented a soft elevated pill cart trigger with refined spacing, typography, hover/focus elevation, and compact red badge (header-auth module 1.5.13).

= 0.5.12 =
* Mini-cart: added payment/trust logo row below the secure checkout trust text using bundled Bitcoin, USDC, Visa, and Mastercard SVG assets (header-auth module 1.5.12).

= 0.5.11 =
* Checkout v2: restored visible but soft order summary separators between heading, product rows, totals, discounts/shipping, and payment methods (header-auth module 1.5.11).

= 0.5.10 =
* Checkout v2: softened the order summary container with off-white background, lighter border, stronger internal padding, and better row spacing (header-auth module 1.5.10).

= 0.5.9 =
* Mini-cart: removed harsh inherited product-row frame; rows now use a soft bottom-only separator with improved media/content/quantity alignment (header-auth module 1.5.9).

= 0.5.8 =
* Checkout v2: softer order summary and payment cards — increased padding, off-white surfaces, lighter borders (header-auth module 1.5.8).

= 0.5.7 =
* Mini-cart: refined product row layout — larger thumb, accent title, subtitle, bold price, pill qty, circular remove on image.

= 0.5.6 =
* Header auth: premium mini-cart drawer (Elementor side-cart + Blocksy offcanvas). Sticky checkout zone, compact rows, quantity controls, empty/loading states. Module 1.5.6.

= 0.5.5 =
* Technical SEO: defer meta, Open Graph, Twitter, Organization/WebSite, and BreadcrumbList output to Rank Math when active. Robots, sitemaps, and utility filters unchanged.

= 0.5.4 =
* GitHub updater: refresh release cache on WordPress update checks; pick highest storefront-v* semver; normalize v-prefixed versions.

= 0.5.3 =
* Product stock display module: configurable stock labels and colors (WooCommerce → Stock display). Replaces exact quantity on the storefront.

= 0.5.2 =
* GitHub Release updater for production ZIP installs (tag storefront-v*).

= 0.5.1 =
* Production release ZIP automation; excludes in-plugin scripts/ and dev paths. Shop load-more / Elementor label fixes and production cleanup since 0.4.0.

= 0.4.0 =
* Header auth module (Phase 4). Tag storefront-v0.4.0.

== Upgrade Notice ==

= 0.5.7 =
UI-only mini-cart row update. Clear cache after upgrade.

= 0.5.6 =
Adds styled WooCommerce mini-cart drawer assets. Clear site cache after upgrade. Compatible with Elementor menu cart fragments and Blocksy qty AJAX.

= 0.5.5 =
Defers duplicate meta, Open Graph, Twitter, and core JSON-LD to Rank Math when that plugin is active. Safe to install alongside Rank Math on production.

= 0.5.4 =
Fixes GitHub-based plugin update detection. Sites on 0.5.3 should see this release under Dashboard → Updates after WordPress checks for plugin updates.

= 0.5.3 =
Adds WooCommerce → Stock display settings. Review labels after upgrade if you previously relied on WooCommerce exact stock counts on product pages.
