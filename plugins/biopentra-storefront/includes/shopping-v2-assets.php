<?php
/**
 * Milestone B — shop page commercial IA assets.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue shop v2 layout CSS on the WooCommerce shop page only.
 */
function biopentra_storefront_shop_v2_enqueue_assets() {
	if ( ! function_exists( 'wc_get_page_id' ) || ! is_page( (int) wc_get_page_id( 'shop' ) ) ) {
		return;
	}

	wp_enqueue_style(
		'biopentra-shop-v2',
		BIOPENTRA_STOREFRONT_URL . 'assets/css/shop-v2.css',
		array( 'biopentra-bp-tokens', 'biopentra-loop-card' ),
		BIOPENTRA_STOREFRONT_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'biopentra_storefront_shop_v2_enqueue_assets', 25 );

/**
 * Enqueue search results refinement UI styles.
 */
function biopentra_storefront_search_v2_enqueue_assets() {
	if ( ! is_search() || empty( $_GET['post_type'] ) || 'product' !== sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	wp_enqueue_style(
		'biopentra-search-v2',
		BIOPENTRA_STOREFRONT_URL . 'assets/css/search-v2.css',
		array(),
		BIOPENTRA_STOREFRONT_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'biopentra_storefront_search_v2_enqueue_assets', 25 );

/**
 * Enqueue SEO category page layout CSS.
 */
function biopentra_storefront_seo_category_v2_enqueue_assets() {
	if ( ! is_page() ) {
		return;
	}

	$slug    = get_post_field( 'post_name', get_queried_object_id() );
	$configs = function_exists( 'biopentra_storefront_seo_category_configs' ) ? biopentra_storefront_seo_category_configs() : array();

	if ( empty( $configs[ $slug ] ) ) {
		return;
	}

	wp_enqueue_style(
		'biopentra-seo-category-v2',
		BIOPENTRA_STOREFRONT_URL . 'assets/css/seo-category-v2.css',
		array( 'biopentra-bp-tokens', 'biopentra-loop-card' ),
		BIOPENTRA_STOREFRONT_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'biopentra_storefront_seo_category_v2_enqueue_assets', 25 );

/**
 * Body classes for Milestone B layouts.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function biopentra_storefront_shopping_v2_body_classes( $classes ) {
	if ( function_exists( 'wc_get_page_id' ) && is_page( (int) wc_get_page_id( 'shop' ) ) && get_post_meta( (int) wc_get_page_id( 'shop' ), 'bp_shop_v2_applied', true ) ) {
		$classes[] = 'bp-shop-v2';
	}

	if ( is_page() ) {
		$slug    = get_post_field( 'post_name', get_queried_object_id() );
		$configs = function_exists( 'biopentra_storefront_seo_category_configs' ) ? biopentra_storefront_seo_category_configs() : array();
		if ( ! empty( $configs[ $slug ] ) && get_post_meta( get_queried_object_id(), 'bp_seo_category_v2_applied', true ) ) {
			$classes[] = 'bp-seo-category-v2';
		}
	}

	return $classes;
}
add_filter( 'body_class', 'biopentra_storefront_shopping_v2_body_classes' );
