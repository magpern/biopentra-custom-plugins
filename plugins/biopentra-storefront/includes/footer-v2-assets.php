<?php
/**
 * M8 — Global footer redesign CSS enqueue.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue footer-v2 CSS globally, after chrome-v1/SDS tokens.
 */
function biopentra_storefront_enqueue_footer_v2() {
	$ver      = BIOPENTRA_STOREFRONT_VERSION;
	$css_path = BIOPENTRA_STOREFRONT_PATH . 'assets/css/footer-v2.css';
	if ( is_readable( $css_path ) ) {
		$ver .= '.' . (string) filemtime( $css_path );
	}

	wp_register_style(
		'biopentra-footer-v2',
		BIOPENTRA_STOREFRONT_URL . 'assets/css/footer-v2.css',
		array( 'biopentra-bp-tokens', 'biopentra-chrome-v1' ),
		$ver
	);
	wp_enqueue_style( 'biopentra-footer-v2' );
}
add_action( 'wp_enqueue_scripts', 'biopentra_storefront_enqueue_footer_v2', 21 );
