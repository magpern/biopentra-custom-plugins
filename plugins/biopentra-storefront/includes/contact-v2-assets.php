<?php
/**
 * M10 — Contact page assets.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue contact-v2 CSS/JS on the Contact page only.
 */
function biopentra_storefront_contact_v2_enqueue_assets() {
	if ( ! biopentra_storefront_is_contact_page() ) {
		return;
	}

	$ver      = BIOPENTRA_STOREFRONT_VERSION;
	$css_path = BIOPENTRA_STOREFRONT_PATH . 'assets/css/contact-v2.css';
	if ( is_readable( $css_path ) ) {
		$ver .= '.' . (string) filemtime( $css_path );
	}

	wp_enqueue_style(
		'biopentra-contact-v2',
		BIOPENTRA_STOREFRONT_URL . 'assets/css/contact-v2.css',
		array( 'biopentra-bp-tokens', 'biopentra-chrome-v1' ),
		$ver
	);

	$js_path = BIOPENTRA_STOREFRONT_PATH . 'assets/js/contact-v2.js';
	$js_ver  = BIOPENTRA_STOREFRONT_VERSION;
	if ( is_readable( $js_path ) ) {
		$js_ver .= '.' . (string) filemtime( $js_path );
	}

	wp_enqueue_script(
		'biopentra-contact-v2',
		BIOPENTRA_STOREFRONT_URL . 'assets/js/contact-v2.js',
		array(),
		$js_ver,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'biopentra_storefront_contact_v2_enqueue_assets', 26 );
