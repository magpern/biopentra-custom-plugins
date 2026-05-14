<?php
/**
 * Plugin Name:       Custom Variation Stock Selector
 * Description:       On variable single-product pages with embedded variation data, auto-select the highest-priced in-stock purchasable variation.
 * Version:           1.0.0
 * Author:            Biopentra
 * Text Domain:       custom-variation-stock-selector
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 8.0
 * WC tested up to:   10.1
 *
 * When a variable product has more variations than woocommerce_ajax_variation_threshold (default 30),
 * WooCommerce does not embed data-product_variations; this plugin does nothing in that mode.
 *
 * @package Custom_Variation_Stock_Selector
 */

defined( 'ABSPATH' ) || exit;

/**
 * Declare compatibility with WooCommerce HPOS (custom order tables).
 */
function cvss_declare_hpos_compatibility() {
	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
		'custom_order_tables',
		__FILE__,
		true
	);
}
add_action( 'before_woocommerce_init', 'cvss_declare_hpos_compatibility' );

/**
 * Enqueue inline script after WooCommerce variation form assets.
 */
function cvss_enqueue_variation_auto_select() {
	wp_register_script(
		'custom-variation-stock-selector',
		'',
		array( 'jquery', 'wc-add-to-cart-variation' ),
		'1.0.0',
		true
	);
	wp_enqueue_script( 'custom-variation-stock-selector' );
	$js = <<<'JS'
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
	wp_add_inline_script(
		'custom-variation-stock-selector',
		$js,
		'after'
	);
}
add_action( 'woocommerce_before_variations_form', 'cvss_enqueue_variation_auto_select' );
