<?php
/**
 * Milestone A — homepage commercial IA assets.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue homepage v2 layout CSS on the front page only.
 */
function biopentra_storefront_home_v2_enqueue_assets() {
	if ( ! is_front_page() ) {
		return;
	}

	wp_enqueue_style(
		'biopentra-home-v2',
		BIOPENTRA_STOREFRONT_URL . 'assets/css/home-v2.css',
		array( 'biopentra-loop-card' ),
		BIOPENTRA_STOREFRONT_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'biopentra_storefront_home_v2_enqueue_assets', 25 );
