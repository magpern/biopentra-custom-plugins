<?php
/**
 * V1A trial — reversible homepage visual system (real components only).
 *
 * Iteration 2: applies trial fonts/colors/geometry to the existing homepage.
 * Does NOT inject demo/specimen markup. Does NOT freeze SDS v2.
 *
 * Disable: add_filter( 'biopentra_v1a_specimen_enabled', '__return_false' );
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the V1A homepage trial is active.
 *
 * @return bool
 */
function biopentra_v1a_specimen_enabled() {
	return (bool) apply_filters( 'biopentra_v1a_specimen_enabled', true );
}

/**
 * Front-page only trial context.
 *
 * @return bool
 */
function biopentra_v1a_specimen_should_render() {
	return biopentra_v1a_specimen_enabled() && is_front_page();
}

/**
 * Asset version for cache bust without bumping plugin release version.
 *
 * @param string $relative Path under plugin root.
 * @return string
 */
function biopentra_v1a_asset_ver( $relative ) {
	$path = BIOPENTRA_STOREFRONT_PATH . ltrim( $relative, '/' );
	return is_readable( $path ) ? (string) filemtime( $path ) : BIOPENTRA_STOREFRONT_VERSION;
}

/**
 * Enqueue V1A fonts + trial CSS on the homepage only.
 */
function biopentra_v1a_specimen_enqueue() {
	if ( ! biopentra_v1a_specimen_should_render() ) {
		return;
	}

	wp_enqueue_style(
		'biopentra-v1a-fonts',
		BIOPENTRA_STOREFRONT_URL . 'assets/css/bp-v1a-fonts.css',
		array(),
		biopentra_v1a_asset_ver( 'assets/css/bp-v1a-fonts.css' )
	);

	wp_enqueue_style(
		'biopentra-v1a-specimen',
		BIOPENTRA_STOREFRONT_URL . 'assets/css/bp-v1a-specimen.css',
		array( 'biopentra-v1a-fonts', 'biopentra-bp-tokens', 'biopentra-home-v2', 'biopentra-loop-card' ),
		biopentra_v1a_asset_ver( 'assets/css/bp-v1a-specimen.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'biopentra_v1a_specimen_enqueue', 30 );

/**
 * Preload critical woff2 to reduce FOIT/CLS.
 */
function biopentra_v1a_specimen_preload_fonts() {
	if ( ! biopentra_v1a_specimen_should_render() ) {
		return;
	}

	$preload = array(
		'assets/fonts/barlow/barlow-latin-400-normal.woff2',
		'assets/fonts/barlow-condensed/barlow-condensed-latin-700-normal.woff2',
	);
	foreach ( $preload as $rel ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( BIOPENTRA_STOREFRONT_URL . $rel )
		);
	}
}
add_action( 'wp_head', 'biopentra_v1a_specimen_preload_fonts', 4 );

/**
 * Body class for scoped trial chrome (homepage only).
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function biopentra_v1a_specimen_body_class( $classes ) {
	if ( biopentra_v1a_specimen_should_render() ) {
		$classes[] = 'bp-v1a-trial';
	}
	return $classes;
}
add_filter( 'body_class', 'biopentra_v1a_specimen_body_class' );
