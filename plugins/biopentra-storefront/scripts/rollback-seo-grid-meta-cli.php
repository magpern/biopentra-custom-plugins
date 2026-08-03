<?php
/**
 * Milestone D3 — rollback page-owned SEO guide grid meta.
 *
 * Deletes `_bp_seo_grid_products` and `_bp_seo_archive_term` so runtime
 * falls back to deprecated PHP inventories (one-release landing path).
 *
 * Run:
 *   docker compose run --rm -T wpcli wp eval-file \
 *     wp-content/plugins/biopentra-storefront/scripts/rollback-seo-grid-meta-cli.php
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BIOPENTRA_STOREFRONT_PATH . 'includes/shopping-v2-helpers.php';

$configs = biopentra_storefront_seo_category_configs();
$deleted = 0;

foreach ( array_keys( $configs ) as $slug ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( ! $page instanceof WP_Post ) {
		echo "WARN: Page slug {$slug} not found — skip.\n";
		continue;
	}

	$page_id = (int) $page->ID;
	delete_post_meta( $page_id, BIOPENTRA_SEO_GRID_PRODUCTS_META );
	delete_post_meta( $page_id, BIOPENTRA_SEO_ARCHIVE_TERM_META );
	++$deleted;
	echo "Rolled back SEO meta: {$slug} (ID {$page_id})\n";
}

echo "Done. deleted_pages={$deleted}\n";
echo "Runtime will use biopentra_storefront_seo_category_legacy_inventory() until meta is re-migrated.\n";
