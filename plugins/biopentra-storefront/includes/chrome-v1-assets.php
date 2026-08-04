<?php
/**
 * Milestone E — global chrome CSS/JS (z-index contract, touch targets, header search).
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue chrome assets after SDS tokens.
 */
function biopentra_storefront_enqueue_chrome_v1() {
	wp_register_style(
		'biopentra-chrome-v1',
		BIOPENTRA_STOREFRONT_URL . 'assets/css/chrome-v1.css',
		array( 'biopentra-bp-tokens' ),
		BIOPENTRA_STOREFRONT_VERSION
	);
	wp_enqueue_style( 'biopentra-chrome-v1' );

	wp_register_script(
		'biopentra-chrome-v1',
		BIOPENTRA_STOREFRONT_URL . 'assets/js/chrome-v1.js',
		array(),
		BIOPENTRA_STOREFRONT_VERSION,
		true
	);

	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	if ( ! is_string( $shop_url ) || '' === $shop_url ) {
		$shop_url = home_url( '/' );
	}

	wp_localize_script(
		'biopentra-chrome-v1',
		'biopentraChrome',
		array(
			'shopSearchUrl' => esc_url_raw( trailingslashit( $shop_url ) . '#biopentra-shop-s' ),
		)
	);
	wp_enqueue_script( 'biopentra-chrome-v1' );
}
add_action( 'wp_enqueue_scripts', 'biopentra_storefront_enqueue_chrome_v1', 20 );
