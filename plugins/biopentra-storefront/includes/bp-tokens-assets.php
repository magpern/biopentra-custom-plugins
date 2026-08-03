<?php
/**
 * Milestone D1 — enqueue Storefront Design System tokens globally.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register and enqueue SDS token stylesheet on the frontend.
 */
function biopentra_storefront_enqueue_bp_tokens() {
	wp_register_style(
		'biopentra-bp-tokens',
		BIOPENTRA_STOREFRONT_URL . 'assets/css/bp-tokens.css',
		array(),
		BIOPENTRA_STOREFRONT_VERSION
	);
	wp_enqueue_style( 'biopentra-bp-tokens' );
}
add_action( 'wp_enqueue_scripts', 'biopentra_storefront_enqueue_bp_tokens', 5 );
