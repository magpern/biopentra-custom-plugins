<?php
/**
 * Milestone A — homepage commercial IA assets + M2 hero image token.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve the homepage hero background image URL (attachment 4520 on current envs).
 *
 * @return string
 */
function biopentra_storefront_home_hero_image_url() {
	$attachment_id = 4520;
	$url           = wp_get_attachment_image_url( $attachment_id, 'full' );
	if ( ! is_string( $url ) || $url === '' ) {
		$url = content_url( 'uploads/2026/05/home-hero.webp' );
	}

	/**
	 * Filter the homepage hero background image URL.
	 *
	 * @param string $url           Image URL.
	 * @param int    $attachment_id Preferred attachment ID.
	 */
	return (string) apply_filters( 'biopentra_storefront_home_hero_image_url', $url, $attachment_id );
}

/**
 * Enqueue homepage v2 layout CSS on the front page only.
 */
function biopentra_storefront_home_v2_enqueue_assets() {
	if ( ! is_front_page() ) {
		return;
	}

	$ver = BIOPENTRA_STOREFRONT_VERSION;
	$css_path = BIOPENTRA_STOREFRONT_PATH . 'assets/css/home-v2.css';
	if ( is_readable( $css_path ) ) {
		$ver .= '.' . (string) filemtime( $css_path );
	}

	wp_enqueue_style(
		'biopentra-home-v2',
		BIOPENTRA_STOREFRONT_URL . 'assets/css/home-v2.css',
		array( 'biopentra-bp-tokens', 'biopentra-loop-card' ),
		$ver
	);

	$hero_url = biopentra_storefront_home_hero_image_url();
	if ( $hero_url !== '' ) {
		$css = sprintf(
			'body.home{--bp-home-hero-image:url("%s");}',
			esc_url( $hero_url )
		);
		wp_add_inline_style( 'biopentra-home-v2', $css );
	}

	// M6 FAQ: ensure all accordion answers start collapsed (Elementor may activate first item).
	$js_path = BIOPENTRA_STOREFRONT_PATH . 'assets/js/m6-faq-init.js';
	if ( is_readable( $js_path ) ) {
		$js_ver = BIOPENTRA_STOREFRONT_VERSION . '.' . (string) filemtime( $js_path );
		wp_enqueue_script(
			'biopentra-m6-faq',
			BIOPENTRA_STOREFRONT_URL . 'assets/js/m6-faq-init.js',
			array( 'jquery', 'elementor-frontend' ),
			$js_ver,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'biopentra_storefront_home_v2_enqueue_assets', 25 );
