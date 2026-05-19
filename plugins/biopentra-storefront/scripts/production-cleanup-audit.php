<?php
/**
 * Audit QA/test/demo storefront artifacts.
 *
 * Usage: ./wp eval-file wp-content/plugins/biopentra-storefront/scripts/production-cleanup-audit.php
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

require_once dirname( __DIR__ ) . '/scripts/production-cleanup-apply.php';

$rows = array();

$types = array( 'product', 'page' );
foreach ( $types as $post_type ) {
	$posts = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => -1,
		)
	);
	foreach ( $posts as $post ) {
		$is_test  = biopentra_cleanup_post_matches_test_artifact( $post );
		$utility  = in_array( $post->post_name, array( 'gift-card-balance', 'mp-cp-block-cart-qa', 'mp-cp-block-checkout-qa', 'footer-preview-eu-v2' ), true );
		$smoke_cat = false;
		if ( 'product' === $post_type ) {
			$cats = wp_get_post_terms( $post->ID, 'product_cat', array( 'fields' => 'slugs' ) );
			$smoke_cat = in_array( 'smoke-bogo', $cats, true ) || in_array( 'uncategorized', $cats, true );
		}
		if ( ! $is_test && ! $utility && ! $smoke_cat ) {
			continue;
		}

		$action = 'keep';
		if ( $is_test || $utility ) {
			$action = biopentra_cleanup_is_real_catalog_product( $post->ID ) ? 'keep (real product)' : 'draft';
		} elseif ( $smoke_cat && 'publish' === $post->post_status ) {
			$action = 'recategorize or draft';
		}

		$rows[] = array(
			'item'     => $post->post_title,
			'type'     => $post_type,
			'url'      => get_permalink( $post ),
			'status'   => $post->post_status,
			'action'   => $action,
			'id'       => $post->ID,
			'slug'     => $post->post_name,
		);
	}
}

$terms = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
	)
);
if ( ! is_wp_error( $terms ) ) {
	foreach ( $terms as $term ) {
		if ( preg_match( '/smoke|qa|test|demo/i', $term->slug . ' ' . $term->name ) ) {
			$rows[] = array(
				'item'   => $term->name,
				'type'   => 'product_cat',
				'url'    => get_term_link( $term ),
				'status' => 'count:' . $term->count,
				'action' => (int) $term->count === 0 ? 'delete term' : 'review products',
				'id'     => $term->term_id,
				'slug'   => $term->slug,
			);
		}
	}
}

echo "=== Production cleanup audit (" . count( $rows ) . " items) ===\n\n";
foreach ( $rows as $row ) {
	printf(
		"- [%s] %s (%s)\n  URL: %s\n  Status: %s\n  Action: %s\n\n",
		$row['type'],
		$row['item'],
		$row['slug'],
		is_wp_error( $row['url'] ) ? 'n/a' : $row['url'],
		$row['status'],
		$row['action']
	);
}

$published_products = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);
echo 'Published products after audit scope: ' . count( $published_products ) . "\n";
