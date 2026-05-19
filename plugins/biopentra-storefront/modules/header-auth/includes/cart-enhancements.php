<?php
/**
 * WooCommerce cart page: free-shipping progress + premium layout CSS (cart only).
 *
 * Threshold: constant `BIOPENTRA_HEADER_AUTH_CART_FREE_SHIPPING_THRESHOLD` (default 200, store currency)
 * or filter `biopentra_header_auth_cart_free_shipping_threshold`.
 *
 * Progress subtotal: filter `biopentra_header_auth_cart_free_shipping_subtotal` (default: cart `get_subtotal()`).
 * Progress visibility: filter `biopentra_header_auth_cart_show_free_shipping_progress` (default: true).
 *
 * Accent colours reuse filters `biopentra_header_auth_wc_account_accent` / `_hover` on cart CSS handle.
 *
 * @package Biopentra_Header_Auth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Free shipping progress threshold in store currency (numeric, same units as cart subtotal).
 * Override in wp-config.php before plugins load, or use filter
 * `biopentra_header_auth_cart_free_shipping_threshold`.
 */
if ( ! defined( 'BIOPENTRA_HEADER_AUTH_CART_FREE_SHIPPING_THRESHOLD' ) ) {
	define( 'BIOPENTRA_HEADER_AUTH_CART_FREE_SHIPPING_THRESHOLD', 200.0 );
}

/**
 * @return float
 */
function biopentra_header_auth_get_cart_free_shipping_threshold() {
	$threshold = (float) apply_filters(
		'biopentra_header_auth_cart_free_shipping_threshold',
		(float) BIOPENTRA_HEADER_AUTH_CART_FREE_SHIPPING_THRESHOLD
	);
	return max( 0.01, $threshold );
}

/**
 * Cart subtotal for progress (excludes shipping; matches typical “spend X for free shipping” rules).
 *
 * @return float
 */
function biopentra_header_auth_get_cart_progress_subtotal() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0.0;
	}
	return (float) WC()->cart->get_subtotal();
}

/**
 * Subtotal passed to the free-shipping progress (filterable).
 *
 * @return float
 */
function biopentra_header_auth_get_cart_free_shipping_progress_subtotal() {
	return (float) apply_filters(
		'biopentra_header_auth_cart_free_shipping_subtotal',
		biopentra_header_auth_get_cart_progress_subtotal()
	);
}

/**
 * @return void
 */
function biopentra_header_auth_enqueue_cart_enhancements_assets() {
	if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return;
	}

	$deps = array();
	if ( wp_style_is( 'ct-woocommerce-styles', 'registered' ) || wp_style_is( 'ct-woocommerce-styles', 'enqueued' ) ) {
		$deps[] = 'ct-woocommerce-styles';
	} elseif ( wp_style_is( 'ct-main-styles', 'registered' ) || wp_style_is( 'ct-main-styles', 'enqueued' ) ) {
		$deps[] = 'ct-main-styles';
	} else {
		foreach ( array( 'woocommerce-general', 'woocommerce-layout' ) as $handle ) {
			if ( wp_style_is( $handle, 'registered' ) || wp_style_is( $handle, 'enqueued' ) ) {
				$deps[] = $handle;
			}
		}
	}

	wp_register_style(
		'biopentra-cart-enhancements',
		BIOPENTRA_HEADER_AUTH_URL . 'assets/cart-enhancements.css',
		$deps,
		BIOPENTRA_HEADER_AUTH_VERSION
	);
	wp_enqueue_style( 'biopentra-cart-enhancements' );

	$accent = sanitize_hex_color( (string) apply_filters( 'biopentra_header_auth_wc_account_accent', '#1f5fae' ) ) ?: '#1f5fae';
	$hover  = sanitize_hex_color( (string) apply_filters( 'biopentra_header_auth_wc_account_accent_hover', '#174a87' ) ) ?: '#174a87';
	wp_add_inline_style(
		'biopentra-cart-enhancements',
		'body.woocommerce-cart{--bph-wc-accent:' . esc_attr( $accent ) . ';--bph-wc-accent-hover:' . esc_attr( $hover ) . ';--bph-wc-accent-muted:#3d4f66;}'
	);
}

/**
 * Free shipping progress bar + message (above cart totals).
 *
 * @return void
 */
function biopentra_header_auth_cart_show_free_shipping_progress(): bool {
	return (bool) apply_filters( 'biopentra_header_auth_cart_show_free_shipping_progress', true );
}

/**
 * @return void
 */
function biopentra_header_auth_cart_free_shipping_progress() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}

	if ( ! biopentra_header_auth_cart_show_free_shipping_progress() ) {
		return;
	}

	$threshold = biopentra_header_auth_get_cart_free_shipping_threshold();
	$subtotal  = biopentra_header_auth_get_cart_free_shipping_progress_subtotal();
	$progress  = min( 100.0, max( 0.0, ( $subtotal / $threshold ) * 100.0 ) );
	$remaining = max( 0.0, $threshold - $subtotal );

	$label_id = 'bph-cart-fs-progress-label';
	$track_id = 'bph-cart-fs-progress-track';

	echo '<div class="bph-cart-free-shipping" role="region" aria-labelledby="' . esc_attr( $label_id ) . '">';

	if ( $subtotal >= $threshold ) {
		echo '<p id="' . esc_attr( $label_id ) . '" class="bph-cart-free-shipping__text">' . esc_html__( 'You qualify for free shipping', 'biopentra-header-auth' ) . '</p>';
	} else {
		/* translators: %s: formatted price amount remaining until free shipping */
		$away = wp_kses_post(
			sprintf(
				__( 'Only %s away from free shipping', 'biopentra-header-auth' ),
				wc_price( $remaining )
			)
		);
		echo '<p id="' . esc_attr( $label_id ) . '" class="bph-cart-free-shipping__text">' . $away . '</p>';
	}

	echo '<div id="' . esc_attr( $track_id ) . '" class="bph-cart-free-shipping__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr( (string) (int) round( $progress ) ) . '" aria-label="' . esc_attr__( 'Progress toward free shipping', 'biopentra-header-auth' ) . '">';
	echo '<span class="bph-cart-free-shipping__fill" style="width:' . esc_attr( (string) round( $progress, 2 ) ) . '%"></span>';
	echo '</div></div>';
}
