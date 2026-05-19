<?php
/**
 * Production storefront cleanup — draft QA/smoke artifacts (idempotent).
 *
 * Usage: ./wp eval-file wp-content/plugins/biopentra-storefront/scripts/production-cleanup-apply.php
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

/**
 * @return string[]
 */
function biopentra_cleanup_test_patterns() {
	return array( 'smoke', 'qa', 'test', 'demo', 'mp-cp', 'mp cp', 'scheduled delivery', 'delivery security', 'browser qa', 'commerce growth gift' );
}

/**
 * @param WP_Post $post Post object.
 */
function biopentra_cleanup_post_matches_test_artifact( $post ) {
	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	$sku = get_post_meta( $post->ID, '_sku', true );
	$hay = strtolower(
		implode(
			' ',
			array(
				$post->post_title,
				$post->post_name,
				(string) $sku,
				wp_strip_all_tags( (string) $post->post_content ),
			)
		)
	);

	foreach ( biopentra_cleanup_test_patterns() as $pattern ) {
		if ( str_contains( $hay, $pattern ) ) {
			return true;
		}
	}

	return false;
}

/**
 * @param int $post_id Product ID.
 */
function biopentra_cleanup_is_real_catalog_product( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post || 'product' !== $post->post_type ) {
		return false;
	}

	return ! biopentra_cleanup_post_matches_test_artifact( $post );
}

$utility_page_slugs = array(
	'gift-card-balance',
	'mp-cp-block-cart-qa',
	'mp-cp-block-checkout-qa',
	'footer-preview-eu-v2',
);

$results = array(
	'products_drafted'  => array(),
	'pages_drafted'     => array(),
	'terms_removed'     => array(),
	'skipped_real'      => array(),
);

$product_ids = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => array( 'publish', 'private' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $product_ids as $product_id ) {
	$post = get_post( $product_id );
	if ( ! $post instanceof WP_Post ) {
		continue;
	}

	if ( biopentra_cleanup_is_real_catalog_product( $product_id ) ) {
		$results['skipped_real'][] = $product_id;
		continue;
	}

	if ( 'publish' !== $post->post_status && 'private' !== $post->post_status ) {
		continue;
	}

	wp_update_post(
		array(
			'ID'          => $product_id,
			'post_status' => 'draft',
		)
	);
	$results['products_drafted'][] = $product_id;
}

foreach ( $utility_page_slugs as $slug ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( ! $page instanceof WP_Post || 'publish' !== $page->post_status ) {
		continue;
	}
	wp_update_post(
		array(
			'ID'          => $page->ID,
			'post_status' => 'draft',
		)
	);
	$results['pages_drafted'][] = $page->ID;
}

$smoke_cat = get_term_by( 'slug', 'smoke-bogo', 'product_cat' );
if ( $smoke_cat instanceof WP_Term && (int) $smoke_cat->count === 0 ) {
	wp_delete_term( (int) $smoke_cat->term_id, 'product_cat' );
	$results['terms_removed'][] = 'smoke-bogo';
}

// Ensure published catalog products are not in Uncategorized.
$uncat_id = (int) get_option( 'default_product_cat', 0 );
if ( $uncat_id > 0 ) {
	foreach ( get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	) as $product_id ) {
		if ( ! biopentra_cleanup_is_real_catalog_product( $product_id ) ) {
			continue;
		}
		$terms = wp_get_object_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			$research = get_term_by( 'slug', 'research-peptides', 'product_cat' );
			if ( $research instanceof WP_Term ) {
				wp_set_object_terms( $product_id, array( (int) $research->term_id ), 'product_cat' );
			}
			continue;
		}
		if ( 1 === count( $terms ) && in_array( $uncat_id, $terms, true ) ) {
			$research = get_term_by( 'slug', 'research-peptides', 'product_cat' );
			if ( $research instanceof WP_Term ) {
				wp_set_object_terms( $product_id, array( (int) $research->term_id ), 'product_cat', false );
			}
		}
	}
}

if ( function_exists( 'wc_delete_product_transients' ) ) {
	wc_delete_product_transients();
}
wp_cache_flush();

echo wp_json_encode( $results, JSON_PRETTY_PRINT ) . "\n";
echo 'products_drafted: ' . count( $results['products_drafted'] ) . "\n";
echo 'pages_drafted: ' . count( $results['pages_drafted'] ) . "\n";
echo 'real_products_kept: ' . count( $results['skipped_real'] ) . "\n";
