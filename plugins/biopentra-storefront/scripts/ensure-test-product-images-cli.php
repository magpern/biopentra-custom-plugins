<?php
/**
 * Ensure identified dev/test WooCommerce products have the shared fixture image.
 *
 * Idempotent. Reuses one media attachment. Does not overwrite explicit images.
 * Not a storefront runtime fallback — development/test data hygiene only.
 *
 * Usage:
 *   cd /opt/biopentra/apps/wordpress
 *   docker compose run --rm -T wpcli wp eval-file \
 *     wp-content/plugins/biopentra-storefront/scripts/ensure-test-product-images-cli.php
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run via WP-CLI eval-file inside WordPress.\n" );
	exit( 1 );
}

if ( ! function_exists( 'wc_get_products' ) ) {
	fwrite( STDERR, "WooCommerce required.\n" );
	exit( 1 );
}

require_once __DIR__ . '/includes/test-product-images.php';

$attachment_id = biopentra_ensure_test_product_fixture_attachment();
if ( $attachment_id <= 0 ) {
	fwrite( STDERR, 'Could not ensure fixture attachment at ' . biopentra_test_product_fixture_file() . "\n" );
	exit( 1 );
}

echo 'fixture_attachment_id=' . $attachment_id . "\n";
echo 'fixture_file=' . biopentra_test_product_fixture_file() . "\n";

$ids = wc_get_products(
	array(
		'limit'  => -1,
		'status' => array( 'publish', 'draft', 'private', 'pending' ),
		'return' => 'ids',
		'type'   => array( 'simple', 'variable', 'external', 'grouped' ),
	)
);

$scanned   = 0;
$matched   = 0;
$assigned  = 0;
$skipped   = 0;
$untouched = 0;

foreach ( $ids as $id ) {
	++$scanned;
	$product = wc_get_product( (int) $id );
	if ( ! $product ) {
		continue;
	}

	if ( ! biopentra_is_test_fixture_product( $product ) ) {
		++$untouched;
		continue;
	}

	++$matched;
	$result = biopentra_assign_test_product_image_if_missing( $product );
	echo wp_json_encode(
		array(
			'id'            => (int) $product->get_id(),
			'slug'          => $product->get_slug(),
			'name'          => $product->get_name(),
			'type'          => $product->get_type(),
			'changed'       => $result['changed'],
			'reason'        => $result['reason'],
			'attachment_id' => $result['attachment_id'],
		)
	) . "\n";

	if ( $result['changed'] ) {
		++$assigned;
	} else {
		++$skipped;
	}
}

// Duplicate-attachment check: exactly one attachment with the fixture meta.
$dup_q = new WP_Query(
	array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 20,
		'fields'         => 'ids',
		'meta_key'       => BIOPENTRA_TEST_PRODUCT_IMAGE_META,
		'meta_value'     => '1',
		'no_found_rows'  => true,
	)
);
$dup_count = count( $dup_q->posts );

echo 'scanned=' . $scanned . "\n";
echo 'matched_test_fixtures=' . $matched . "\n";
echo 'assigned_or_repaired=' . $assigned . "\n";
echo 'unchanged_matched=' . $skipped . "\n";
echo 'legitimate_untouched=' . $untouched . "\n";
echo 'fixture_attachment_copies=' . $dup_count . "\n";

if ( $dup_count !== 1 ) {
	fwrite( STDERR, "Expected exactly one fixture attachment; found {$dup_count}\n" );
	exit( 1 );
}

echo "ENSURE_TEST_PRODUCT_IMAGES_OK\n";
exit( 0 );
