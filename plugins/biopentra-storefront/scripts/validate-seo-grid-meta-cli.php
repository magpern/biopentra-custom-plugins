<?php
/**
 * Milestone D3 — validate page-owned SEO guide grid meta.
 *
 * Checks:
 *   - Each configured SEO page has `_bp_seo_grid_products` and `_bp_seo_archive_term`
 *   - Product slugs resolve to published products
 *   - Archive term resolves to a product_cat
 *   - Warns on missing/invalid references
 *
 * Exit code 1 when any page fails hard validation (empty products or missing page).
 *
 * Run:
 *   docker compose run --rm -T wpcli wp eval-file \
 *     wp-content/plugins/biopentra-storefront/scripts/validate-seo-grid-meta-cli.php
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BIOPENTRA_STOREFRONT_PATH . 'includes/shopping-v2-helpers.php';

$configs = biopentra_storefront_seo_category_configs();
$errors  = 0;
$warns   = 0;

foreach ( array_keys( $configs ) as $slug ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( ! $page instanceof WP_Post ) {
		echo "ERROR: Page not found: {$slug}\n";
		++$errors;
		continue;
	}

	$page_id = (int) $page->ID;
	$meta_products = biopentra_storefront_normalize_seo_grid_products(
		get_post_meta( $page_id, BIOPENTRA_SEO_GRID_PRODUCTS_META, true )
	);
	$meta_term = sanitize_title( (string) get_post_meta( $page_id, BIOPENTRA_SEO_ARCHIVE_TERM_META, true ) );

	$source = 'meta';
	if ( empty( $meta_products ) ) {
		$resolved = biopentra_storefront_get_seo_grid_product_slugs( $page_id );
		if ( ! empty( $resolved ) ) {
			$source = 'legacy-fallback';
			echo "WARN: {$slug} — grid products from deprecated PHP fallback (meta empty).\n";
			++$warns;
			$meta_products = $resolved;
		} else {
			echo "ERROR: {$slug} — no product slugs in meta or fallback.\n";
			++$errors;
			continue;
		}
	}

	if ( '' === $meta_term ) {
		$resolved_term = biopentra_storefront_get_seo_archive_term( $page_id );
		if ( '' !== $resolved_term ) {
			$source = ( 'meta' === $source ) ? 'meta+legacy-term' : 'legacy-fallback';
			echo "WARN: {$slug} — archive term from deprecated PHP fallback (meta empty).\n";
			++$warns;
			$meta_term = $resolved_term;
		} else {
			echo "ERROR: {$slug} — no archive term in meta or fallback.\n";
			++$errors;
			continue;
		}
	}

	$missing   = array();
	$unpublished = array();
	$ids       = array();
	foreach ( $meta_products as $product_slug ) {
		$post = get_page_by_path( $product_slug, OBJECT, 'product' );
		if ( ! $post instanceof WP_Post ) {
			$missing[] = $product_slug;
			continue;
		}
		if ( 'publish' !== $post->post_status ) {
			$unpublished[] = $product_slug . '(' . $post->post_status . ')';
			continue;
		}
		$ids[] = (int) $post->ID;
	}

	if ( ! empty( $missing ) ) {
		echo "ERROR: {$slug} — missing products: " . implode( ', ', $missing ) . "\n";
		++$errors;
	}

	if ( ! empty( $unpublished ) ) {
		echo "WARN: {$slug} — unpublished (excluded from public grids): " . implode( ', ', $unpublished ) . "\n";
		++$warns;
	}

	if ( empty( $ids ) ) {
		echo "ERROR: {$slug} — zero published products resolve from inventory.\n";
		++$errors;
	}

	$term = get_term_by( 'slug', $meta_term, 'product_cat' );
	if ( ! $term instanceof WP_Term ) {
		echo "ERROR: {$slug} — invalid archive term: {$meta_term}\n";
		++$errors;
	}

	$ok_count = count( $ids );
	echo "OK: {$slug} (ID {$page_id}) source={$source} published={$ok_count} archive={$meta_term}\n";
}

echo "Validation complete. errors={$errors} warnings={$warns}\n";

if ( $errors > 0 ) {
	fwrite( STDERR, "SEO grid meta validation FAILED.\n" );
	exit( 1 );
}

echo "SEO grid meta validation PASSED.\n";
