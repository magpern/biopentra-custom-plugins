<?php
/**
 * Home page v2 helpers — search markup and category chips (Milestone A / M3).
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product search form for the homepage (submits to site search with post_type=product).
 *
 * Still used on SEO guide pages. Homepage Elementor instance removed in M3.
 *
 * @return string HTML or empty when WooCommerce is unavailable.
 */
function biopentra_storefront_get_home_search_form_html() {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return '';
	}

	$action = home_url( '/' );

	return sprintf(
		'<form class="biopentra-shop-search bp-home-search" role="search" method="get" action="%s">
	<label class="screen-reader-text" for="biopentra-shop-s">%s</label>
	<input id="biopentra-shop-s" class="bp-home-search__input" type="search" name="s" value="" placeholder="%s" autocomplete="off" enterkeyhint="search" aria-describedby="biopentra-shop-s-hint" />
	<span id="biopentra-shop-s-hint" class="screen-reader-text">%s</span>
	<input type="hidden" name="post_type" value="product" />
</form>',
		esc_url( $action ),
		esc_attr__( 'Search products', 'biopentra-storefront' ),
		esc_attr__( 'Search peptides and research compounds…', 'biopentra-storefront' ),
		esc_html__( 'Press Enter to view matching products.', 'biopentra-storefront' )
	);
}

/**
 * Touch-friendly category shortcut chips linking to WooCommerce archive URLs.
 *
 * Homepage (front page) only: exclude Uncategorized and show a visible
 * "Shop by category" label. Shop / SEO shortcode instances stay unchanged.
 *
 * @return string HTML nav markup.
 */
function biopentra_storefront_build_home_category_chips_html() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
			'number'     => 8,
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '';
	}

	$is_home = function_exists( 'is_front_page' ) && is_front_page();

	if ( $is_home ) {
		$terms = array_values(
			array_filter(
				$terms,
				static function ( $term ) {
					return isset( $term->slug ) && 'uncategorized' !== $term->slug;
				}
			)
		);
		if ( empty( $terms ) ) {
			return '';
		}
	}

	$label_text = __( 'Shop by category', 'biopentra-storefront' );
	$label_attr = esc_attr( $label_text );
	$html       = '<nav class="bp-home-cats" aria-label="' . $label_attr . '">';

	if ( $is_home ) {
		$html .= '<p class="bp-home-cats__label">' . esc_html( $label_text ) . '</p>';
	}

	$html .= '<div class="bp-home-cats__rail"><div class="bp-home-cats__scroll">';

	foreach ( $terms as $term ) {
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			continue;
		}
		$html .= sprintf(
			'<a class="bp-home-cat-chip" href="%s">%s</a>',
			esc_url( $link ),
			esc_html( $term->name )
		);
	}

	$html .= '</div></div></nav>';

	return $html;
}

/**
 * Register homepage shortcodes (Elementor HTML widget strips form tags).
 */
function biopentra_storefront_register_home_v2_shortcodes() {
	add_shortcode(
		'biopentra_home_search',
		static function () {
			return biopentra_storefront_get_home_search_form_html();
		}
	);

	add_shortcode(
		'biopentra_home_categories',
		static function () {
			return biopentra_storefront_build_home_category_chips_html();
		}
	);
}
add_action( 'init', 'biopentra_storefront_register_home_v2_shortcodes' );
