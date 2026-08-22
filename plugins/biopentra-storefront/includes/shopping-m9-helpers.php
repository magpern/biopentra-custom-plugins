<?php
/**
 * M9 — Shop discovery helpers (taxonomy-filter chip rail refinements).
 *
 * WP3 Path A: Elementor taxonomy-filter b3a2918 remains the filter engine.
 * These helpers only refine customer-facing markup (label, hide Uncategorized).
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current request is the WooCommerce shop page (Elementor singular).
 */
function biopentra_storefront_m9_is_shop_page(): bool {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return false;
	}
	$shop_id = (int) wc_get_page_id( 'shop' );
	return $shop_id > 0 && is_page( $shop_id );
}

/**
 * Refine taxonomy-filter HTML on the shop page only:
 * - prepend visible "Shop by category" label
 * - set aria-label on the filter control
 * - remove Uncategorized button from customer-facing markup
 *
 * @param string                 $content Widget HTML.
 * @param \Elementor\Widget_Base $widget  Widget instance.
 * @return string
 */
function biopentra_storefront_m9_refine_taxonomy_filter_html( $content, $widget ) {
	if ( ! biopentra_storefront_m9_is_shop_page() ) {
		return $content;
	}
	if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) || 'taxonomy-filter' !== $widget->get_name() ) {
		return $content;
	}
	if ( ! method_exists( $widget, 'get_id' ) || 'b3a2918' !== $widget->get_id() ) {
		return $content;
	}

	$html = (string) $content;

	// Strip Uncategorized control entirely (not display:none — removes from a11y tree).
	$html = preg_replace(
		'/<button\b[^>]*\bdata-filter=("|\')uncategorized\1[^>]*>.*?<\/button>/is',
		'',
		$html
	);
	if ( ! is_string( $html ) ) {
		return $content;
	}

	$label = esc_html__( 'Shop by category', 'biopentra-storefront' );
	$aria  = esc_attr__( 'Shop by category', 'biopentra-storefront' );

	// Ensure the filter landmark has a category label for AT.
	if ( false !== strpos( $html, 'class="e-filter"' ) && false === strpos( $html, 'aria-label=' ) ) {
		$html = str_replace(
			'class="e-filter"',
			'class="e-filter" aria-label="' . $aria . '"',
			$html
		);
	}

	if ( false === strpos( $html, 'bp-shop-m9-cats__label' ) ) {
		$label_html = '<p class="bp-shop-m9-cats__label">' . $label . '</p>';
		$html       = $label_html . $html;
	}

	return $html;
}
add_filter( 'elementor/widget/render_content', 'biopentra_storefront_m9_refine_taxonomy_filter_html', 20, 2 );
