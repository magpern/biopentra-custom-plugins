<?php
/**
 * Custom variation stock selector: auto-pick highest-priced in-stock purchasable variation.
 *
 * Migrated from custom-variation-stock-selector (legacy plugin retained; do not run both active).
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mirrors legacy `custom-variation-stock-selector.php` without changing inline JS behavior.
 */
class Biopentra_Storefront_Variation_Stock_Selector_Module {

	const SCRIPT_HANDLE = 'biopentra-storefront-custom-variation-stock-selector';

	const SCRIPT_VERSION = '1.0.0';

	const BRIDGE_REL_PATH = 'assets/variation-stock-selector/cvss-bridge.js';

	/**
	 * Inline script body (must match legacy plugin exactly).
	 */
	const INLINE_JS = <<<'JS'
(function ($) {
	$('.variations_form').on('wc_variation_form', function () {
		var form = $(this);
		if (form.data('cvssAutoDone')) {
			return;
		}
		form.data('cvssAutoDone', true);
		window.setTimeout(function () {
			var variationData = form.data('product_variations');
			if (variationData === false || !variationData || !variationData.length) {
				return;
			}
			var params = new URLSearchParams(window.location.search);
			var skipUrl = false;
			params.forEach(function (value, key) {
				if (key.indexOf('attribute_') === 0 && value) {
					skipUrl = true;
				}
			});
			if (skipUrl) {
				return;
			}
			var vidRaw = form.find('input.variation_id,input[name="variation_id"]').val();
			var vid = parseInt(vidRaw, 10);
			if (vidRaw && !isNaN(vid) && vid > 0) {
				return;
			}
			var best = null;
			var bestPrice = null;
			for (var i = 0; i < variationData.length; i++) {
				var v = variationData[i];
				if (v.is_in_stock !== true || v.is_purchasable !== true) {
					continue;
				}
				var dp = parseFloat(v.display_price);
				if (isNaN(dp)) {
					continue;
				}
				if (best === null || dp > bestPrice) {
					best = v;
					bestPrice = dp;
				}
			}
			if (!best || !best.attributes) {
				return;
			}
			form.find('.variations select').each(function () {
				var sel = $(this);
				var key = sel.data('attribute_name') || sel.attr('name');
				if (!key || typeof best.attributes[key] === 'undefined') {
					return;
				}
				sel.val(best.attributes[key]);
			});
			form.trigger('woocommerce_variation_select_change');
			form.trigger('check_variations');
		}, 150);
	});
})(jQuery);
JS;

	/**
	 * @return bool
	 */
	private static function legacy_plugin_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( 'custom-variation-stock-selector/custom-variation-stock-selector.php' );
	}

	/**
	 * Declare HPOS compatibility (same hook and API as legacy).
	 */
	public static function declare_hpos_compatibility() {
		if ( ! class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			return;
		}
		if ( ! defined( 'BIOPENTRA_STOREFRONT_FILE' ) ) {
			return;
		}
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			BIOPENTRA_STOREFRONT_FILE,
			true
		);
	}

	/**
	 * Enqueue bridge + inline script after WooCommerce variation form assets (same hook as legacy).
	 */
	public static function enqueue_variation_auto_select() {
		if ( is_admin() ) {
			return;
		}

		if ( ! defined( 'BIOPENTRA_STOREFRONT_PATH' ) || ! defined( 'BIOPENTRA_STOREFRONT_URL' ) ) {
			return;
		}

		$bridge = BIOPENTRA_STOREFRONT_PATH . self::BRIDGE_REL_PATH;
		if ( ! is_readable( $bridge ) ) {
			return;
		}

		wp_register_script(
			self::SCRIPT_HANDLE,
			BIOPENTRA_STOREFRONT_URL . self::BRIDGE_REL_PATH,
			array( 'jquery', 'wc-add-to-cart-variation' ),
			self::SCRIPT_VERSION,
			true
		);
		wp_enqueue_script( self::SCRIPT_HANDLE );
		wp_add_inline_script(
			self::SCRIPT_HANDLE,
			self::INLINE_JS,
			'after'
		);
	}

	/**
	 * Register hooks (idempotent if called once).
	 */
	public static function init() {
		if ( self::legacy_plugin_active() ) {
			return;
		}

		add_action( 'before_woocommerce_init', array( __CLASS__, 'declare_hpos_compatibility' ) );
		add_action( 'woocommerce_before_variations_form', array( __CLASS__, 'enqueue_variation_auto_select' ) );
	}
}
