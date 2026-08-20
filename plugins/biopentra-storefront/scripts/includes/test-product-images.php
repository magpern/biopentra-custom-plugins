<?php
/**
 * Dev/test product image fixtures — assignment helpers only (no storefront fallback).
 *
 * Used by ensure-test-product-images-cli.php and optionally by dogfood/seed scripts.
 * Not a production catalog feature; do not hook into product image getters.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Attachment post meta marking the canonical shared test-product image. */
const BIOPENTRA_TEST_PRODUCT_IMAGE_META = '_biopentra_test_product_fixture';

/** Option storing the reusable attachment ID (idempotent lookup). */
const BIOPENTRA_TEST_PRODUCT_IMAGE_OPTION = 'biopentra_test_product_fixture_attachment_id';

/**
 * Absolute path to the canonical 1:1 test product PNG in this plugin.
 *
 * @return string
 */
function biopentra_test_product_fixture_file() {
	$base = defined( 'BIOPENTRA_STOREFRONT_PATH' )
		? BIOPENTRA_STOREFRONT_PATH
		: dirname( __DIR__, 2 ) . '/';
	return $base . 'assets/fixtures/bp-test-product-fixture.png';
}

/**
 * Whether a product is positively identifiable as a development/test fixture.
 *
 * Strict patterns only — never assign imagery to ambiguous catalog products.
 *
 * @param WC_Product|null $product Product.
 * @return bool
 */
function biopentra_is_test_fixture_product( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return false;
	}

	$name = (string) $product->get_name();
	$slug = (string) $product->get_slug();
	$sku  = (string) $product->get_sku();
	$hay  = $name . ' ' . $slug . ' ' . $sku;

	$patterns = array(
		'/\bM\d+[A-Z]?\b/i', // M8, M21, M3E, …
		'/dogfood/i',
		'/smoke\s+(test\s+)?(product|widget)/i',
		'/acceptance\s+widget/i',
		'/post[-\s]?release/i',
		'/postrelease/i',
		'/validation\s+fixture/i',
		'/browser\s+test\s+widget/i',
		'/^m\d+[a-z]?-/i', // slugs like m8-dogfood-widget, m3e-pending, m21-postrelease-variable
		'/^p\d+-acceptance/i',
		'/M\d+-DOGFOOD/i',
		'/M\d+-SMOKE/i',
		'/P\d+-ACCEPT/i',
		'/M\d+-TEST/i',
		'/BROWSER-TEST-WIDGET/i',
	);

	foreach ( $patterns as $pattern ) {
		if ( preg_match( $pattern, $hay ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Ensure a single media-library attachment exists for the test product fixture.
 *
 * @return int Attachment ID, or 0 on failure.
 */
function biopentra_ensure_test_product_fixture_attachment() {
	$existing = (int) get_option( BIOPENTRA_TEST_PRODUCT_IMAGE_OPTION, 0 );
	if ( $existing > 0 && wp_attachment_is_image( $existing ) ) {
		return $existing;
	}

	$q = new WP_Query(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => BIOPENTRA_TEST_PRODUCT_IMAGE_META,
			'meta_value'     => '1',
			'no_found_rows'  => true,
		)
	);
	if ( ! empty( $q->posts ) ) {
		$id = (int) $q->posts[0];
		update_option( BIOPENTRA_TEST_PRODUCT_IMAGE_OPTION, $id, false );
		return $id;
	}

	$file = biopentra_test_product_fixture_file();
	if ( ! is_readable( $file ) ) {
		return 0;
	}

	if ( ! function_exists( 'media_handle_sideload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	$tmp = wp_tempnam( 'bp-test-product-fixture.png' );
	if ( ! $tmp || ! copy( $file, $tmp ) ) {
		return 0;
	}

	$file_array = array(
		'name'     => 'bp-test-product-fixture.png',
		'tmp_name' => $tmp,
	);

	$attachment_id = media_handle_sideload(
		$file_array,
		0,
		'BioPentra test product fixture (dev only)'
	);

	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		return 0;
	}

	$attachment_id = (int) $attachment_id;
	update_post_meta( $attachment_id, BIOPENTRA_TEST_PRODUCT_IMAGE_META, '1' );
	wp_update_post(
		array(
			'ID'         => $attachment_id,
			'post_title' => 'bp-test-product-fixture',
		)
	);
	update_option( BIOPENTRA_TEST_PRODUCT_IMAGE_OPTION, $attachment_id, false );

	return $attachment_id;
}

/**
 * Assign the shared fixture image to a product if it has no featured image.
 *
 * Does not overwrite an explicitly assigned image. For variable parents, also
 * assigns the same attachment ID to child variations that lack their own image
 * (no duplicate media files).
 *
 * @param int|WC_Product $product Product or ID.
 * @return array{changed:bool,attachment_id:int,reason:string}
 */
function biopentra_assign_test_product_image_if_missing( $product ) {
	if ( is_numeric( $product ) ) {
		$product = wc_get_product( (int) $product );
	}
	if ( ! $product instanceof WC_Product ) {
		return array(
			'changed'       => false,
			'attachment_id' => 0,
			'reason'        => 'not_a_product',
		);
	}

	if ( ! biopentra_is_test_fixture_product( $product ) ) {
		return array(
			'changed'       => false,
			'attachment_id' => 0,
			'reason'        => 'not_test_fixture',
		);
	}

	$attachment_id = biopentra_ensure_test_product_fixture_attachment();
	if ( $attachment_id <= 0 ) {
		return array(
			'changed'       => false,
			'attachment_id' => 0,
			'reason'        => 'fixture_missing',
		);
	}

	$changed = false;
	$current = (int) $product->get_image_id();
	if ( $current <= 0 ) {
		$product->set_image_id( $attachment_id );
		$product->save();
		$changed = true;
	} elseif ( $current !== $attachment_id ) {
		return array(
			'changed'       => false,
			'attachment_id' => $current,
			'reason'        => 'explicit_image_kept',
		);
	}

	if ( $product->is_type( 'variable' ) ) {
		foreach ( $product->get_children() as $child_id ) {
			$child = wc_get_product( (int) $child_id );
			if ( ! $child instanceof WC_Product ) {
				continue;
			}
			$child_img = (int) $child->get_image_id();
			if ( $child_img <= 0 ) {
				$child->set_image_id( $attachment_id );
				$child->save();
				$changed = true;
			}
		}
	}

	return array(
		'changed'       => $changed,
		'attachment_id' => $attachment_id,
		'reason'        => $changed ? 'assigned' : 'already_set',
	);
}
