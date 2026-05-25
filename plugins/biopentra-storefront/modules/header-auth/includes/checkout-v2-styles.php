<?php
/**
 * Checkout v2 presentation — order summary & payment refinements.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether checkout v2 styling should load (matches child-theme toggles).
 *
 * @return bool
 */
function biopentra_storefront_is_checkout_v2_active(): bool {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return false;
	}

	if ( is_admin() ) {
		return false;
	}

	$enabled = ( 'yes' === get_option( 'biopentra_checkout_v2_enabled', 'no' ) );

	if ( ! $enabled && function_exists( 'is_page' ) && is_page( 'checkout-v2' ) ) {
		$enabled = true;
	}

	if ( ! $enabled && isset( $_GET['checkout_v2'] ) && '1' === (string) wp_unslash( $_GET['checkout_v2'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$enabled = true;
	}

	return (bool) apply_filters( 'biopentra_checkout_v2_request', $enabled );
}

/**
 * Register checkout v2 order/payment stylesheet.
 */
function biopentra_storefront_register_checkout_v2_styles(): void {
	wp_register_style(
		'biopentra-checkout-v2-order-payment',
		BIOPENTRA_HEADER_AUTH_URL . 'assets/checkout-v2-order-payment.css',
		array(),
		BIOPENTRA_HEADER_AUTH_VERSION
	);
}

/**
 * Enqueue after theme checkout-v2 / Blocksy Woo bundle.
 */
function biopentra_storefront_enqueue_checkout_v2_styles(): void {
	if ( ! biopentra_storefront_is_checkout_v2_active() ) {
		return;
	}

	$deps = array();
	foreach ( array( 'bp-checkout-v2', 'biopentra-wc-account-forms', 'ct-woocommerce-styles' ) as $handle ) {
		if ( wp_style_is( $handle, 'enqueued' ) || wp_style_is( $handle, 'registered' ) ) {
			$deps[] = $handle;
			break;
		}
	}

	wp_deregister_style( 'biopentra-checkout-v2-order-payment' );
	wp_register_style(
		'biopentra-checkout-v2-order-payment',
		BIOPENTRA_HEADER_AUTH_URL . 'assets/checkout-v2-order-payment.css',
		$deps,
		BIOPENTRA_HEADER_AUTH_VERSION
	);
	wp_enqueue_style( 'biopentra-checkout-v2-order-payment' );
}
