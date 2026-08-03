<?php
/**
 * Milestone D3 — migrate SEO guide product inventories to page-owned meta.
 *
 * Idempotent: writes `_bp_seo_grid_products` and `_bp_seo_archive_term` when
 * missing or when --force is set. Seed data matches Milestone B PHP lists.
 *
 * Run:
 *   docker compose run --rm -T wpcli wp eval-file \
 *     wp-content/plugins/biopentra-storefront/scripts/migrate-seo-grid-meta-cli.php
 *
 * Options (env):
 *   BIOPENTRA_SEO_META_FORCE=1  — overwrite existing meta from seed
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BIOPENTRA_STOREFRONT_PATH . 'includes/shopping-v2-helpers.php';

$force = ! empty( getenv( 'BIOPENTRA_SEO_META_FORCE' ) );

/**
 * Canonical seed for migration / production replay (page slug → inventory).
 *
 * Kept in the CLI (not runtime configs) so production can migrate after
 * inventories are removed from shopping-v2-helpers.php.
 *
 * @return array<string, array{product_slugs: string[], wc_archive: string}>
 */
function biopentra_seo_grid_meta_migration_seed() {
	if ( function_exists( 'biopentra_storefront_seo_category_legacy_inventory' ) ) {
		return biopentra_storefront_seo_category_legacy_inventory();
	}

	return array(
		'growth-hormone-releasing-peptides' => array(
			'product_slugs' => array( 'ipamorelin', 'ghrp-2', 'ghrp-6', 'hexarelin', 'sermorelin', 'cjc-1295-no-dac-ipa' ),
			'wc_archive'    => 'growth-performance',
		),
		'metabolic-research-peptides'       => array(
			'product_slugs' => array( 'triple-g', 'tirzepatide', 'mots-c', 'aod9604' ),
			'wc_archive'    => 'weight-management',
		),
		'lyophilized-research-materials'    => array(
			'product_slugs' => array( 'bpc-157', 'tb-500', 'cjc-1295-no-dac-ipa', 'mots-c', 'triple-g', 'kisspeptin' ),
			'wc_archive'    => 'research-peptides',
		),
	);
}

$seed    = biopentra_seo_grid_meta_migration_seed();
$configs = biopentra_storefront_seo_category_configs();
$written = 0;
$skipped = 0;

foreach ( array_keys( $configs ) as $slug ) {
	if ( empty( $seed[ $slug ] ) ) {
		echo "WARN: No seed inventory for {$slug} — skip.\n";
		continue;
	}

	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( ! $page instanceof WP_Post ) {
		echo "WARN: Page slug {$slug} not found — skip.\n";
		continue;
	}

	$page_id      = (int) $page->ID;
	$existing_prods = biopentra_storefront_normalize_seo_grid_products(
		get_post_meta( $page_id, BIOPENTRA_SEO_GRID_PRODUCTS_META, true )
	);
	$existing_term = sanitize_title( (string) get_post_meta( $page_id, BIOPENTRA_SEO_ARCHIVE_TERM_META, true ) );

	$slugs = array_map( 'sanitize_title', $seed[ $slug ]['product_slugs'] );
	$term  = sanitize_title( (string) $seed[ $slug ]['wc_archive'] );

	$did_write = false;

	if ( $force || empty( $existing_prods ) ) {
		update_post_meta( $page_id, BIOPENTRA_SEO_GRID_PRODUCTS_META, $slugs );
		$did_write = true;
	}

	if ( $force || '' === $existing_term ) {
		update_post_meta( $page_id, BIOPENTRA_SEO_ARCHIVE_TERM_META, $term );
		$did_write = true;
	}

	if ( $did_write ) {
		++$written;
		echo "Migrated SEO meta: {$slug} (ID {$page_id}) products=" . count( $slugs ) . " archive={$term}\n";
	} else {
		++$skipped;
		echo "Skip (already set): {$slug} (ID {$page_id})\n";
	}
}

echo "Done. written={$written} skipped={$skipped} force=" . ( $force ? '1' : '0' ) . "\n";
echo "Next: wp eval-file .../validate-seo-grid-meta-cli.php\n";
