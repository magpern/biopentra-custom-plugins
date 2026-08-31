<?php
/**
 * MOTION-1 — storefront motion gate (head) + controller (footer) + CSS.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether MOTION-1 assets should load (homepage or WooCommerce shop).
 *
 * @return bool
 */
function biopentra_storefront_motion_should_load() {
	if ( is_admin() ) {
		return false;
	}

	if ( function_exists( 'is_cart' ) && is_cart() ) {
		return false;
	}

	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return false;
	}

	if ( is_front_page() ) {
		return true;
	}

	return function_exists( 'is_shop' ) && is_shop();
}

/**
 * Synchronous head-gate source (no file URL; printed with the src-less handle).
 *
 * @return string
 */
function biopentra_storefront_motion_gate_js() {
	return <<<'JS'
(function(){var m=window.matchMedia&&window.matchMedia("(prefers-reduced-motion: reduce)").matches;var io="IntersectionObserver"in window;window.bpMotion=window.bpMotion||{};if(m||!io){window.bpMotion.allowed=false;return;}document.documentElement.classList.add("bp-motion");window.bpMotion.allowed=true;window.bpMotion.failsafeId=window.setTimeout(function(){var h=document.documentElement;h.classList.remove("bp-motion");h.classList.add("bp-motion-failsafe");if(typeof window.bpMotionCleanup==="function"){window.bpMotionCleanup("failsafe");}},2000);})();
JS;
}

/**
 * Enqueue MOTION-1 CSS, head gate, and footer controller.
 */
function biopentra_storefront_motion_enqueue_assets() {
	if ( ! biopentra_storefront_motion_should_load() ) {
		return;
	}

	$ver = BIOPENTRA_STOREFRONT_VERSION;

	$css_path = BIOPENTRA_STOREFRONT_PATH . 'assets/css/bp-motion.css';
	$css_ver  = $ver;
	if ( is_readable( $css_path ) ) {
		$css_ver .= '.' . (string) filemtime( $css_path );
	}

	wp_enqueue_style(
		'biopentra-motion',
		BIOPENTRA_STOREFRONT_URL . 'assets/css/bp-motion.css',
		array( 'biopentra-bp-tokens' ),
		$css_ver
	);

	wp_register_script(
		'biopentra-motion-gate',
		false,
		array(),
		$ver,
		false
	);
	wp_enqueue_script( 'biopentra-motion-gate' );
	wp_add_inline_script( 'biopentra-motion-gate', biopentra_storefront_motion_gate_js(), 'after' );

	$js_path = BIOPENTRA_STOREFRONT_PATH . 'assets/js/bp-motion.js';
	$js_ver  = $ver;
	if ( is_readable( $js_path ) ) {
		$js_ver .= '.' . (string) filemtime( $js_path );
	}

	wp_enqueue_script(
		'biopentra-motion',
		BIOPENTRA_STOREFRONT_URL . 'assets/js/bp-motion.js',
		array( 'jquery' ),
		$js_ver,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'biopentra_storefront_motion_enqueue_assets', 22 );

/**
 * Defensive noscript: pending-hide must not apply without JavaScript.
 */
function biopentra_storefront_motion_noscript() {
	if ( ! biopentra_storefront_motion_should_load() ) {
		return;
	}
	echo '<noscript><style>html.bp-motion [data-bp-motion-state="pending"]{opacity:1!important;transform:none!important;transition:none!important;will-change:auto!important}</style></noscript>' . "\n";
}
add_action( 'wp_head', 'biopentra_storefront_motion_noscript', 1 );
