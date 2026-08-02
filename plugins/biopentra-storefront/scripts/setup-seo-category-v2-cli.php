<?php
/**
 * Milestone B — rebuild SEO category Elementor pages with product grids above long-form content.
 *
 * Run:
 *   docker compose run --rm -T wpcli wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-seo-category-v2-cli.php
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BIOPENTRA_STOREFRONT_PATH . 'includes/shopping-v2-helpers.php';

/**
 * Build a loop-grid widget for SEO category pages.
 *
 * @param string $widget_id Elementor widget id.
 * @return array
 */
function biopentra_seo_category_v2_build_loop_grid( $widget_id ) {
	return array(
		'id'         => $widget_id,
		'elType'     => 'widget',
		'widgetType' => 'loop-grid',
		'settings'   => array(
			'_skin'               => 'product',
			'template_id'         => '3608',
			'columns'               => 4,
			'columns_tablet'        => 3,
			'columns_mobile'        => 2,
			'posts_per_page'        => 8,
			'equal_height'          => 'yes',
			'query_post_type'       => 'product',
			'query_orderby'         => 'post_date',
			'query_order'           => 'desc',
			'pagination_type'       => '',
			'nothing_found_message_text' => __( 'No products matched this category guide.', 'biopentra-storefront' ),
		),
		'elements'   => array(),
	);
}

/**
 * @param string $shortcode Shortcode string.
 * @param string $widget_id Widget id.
 * @return array
 */
function biopentra_seo_category_v2_shortcode_widget( $shortcode, $widget_id ) {
	return array(
		'id'         => $widget_id,
		'elType'     => 'widget',
		'widgetType' => 'shortcode',
		'settings'   => array( 'shortcode' => $shortcode ),
		'elements'   => array(),
	);
}

$configs = biopentra_storefront_seo_category_configs();

foreach ( $configs as $slug => $config ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( ! $page instanceof WP_Post ) {
		echo "WARN: Page slug {$slug} not found — skip.\n";
		continue;
	}

	$page_id = (int) $page->ID;
	$raw     = get_post_meta( $page_id, '_elementor_data', true );
	$data    = is_string( $raw ) ? json_decode( $raw, true ) : null;

	if ( ! is_array( $data ) || empty( $data[0]['elements'] ) ) {
		echo "ERROR: Unexpected Elementor data on page {$page_id} ({$slug}).\n";
		continue;
	}

	$root = &$data[0];
	$root['settings']['css_classes'] = trim( ( $root['settings']['css_classes'] ?? '' ) . ' ' . $config['root_class'] );

	$hero = null;
	$body = null;
	$rest = array();

	foreach ( $root['elements'] as $child ) {
		if ( ! is_array( $child ) ) {
			continue;
		}
		$cid = $child['id'] ?? '';
		$cls = $child['settings']['css_classes'] ?? '';
		if ( $cid === $config['hero_class'] || $cls === $config['hero_class'] || false !== strpos( $cls, $config['hero_class'] ) ) {
			$hero = $child;
			$hero['settings']['css_classes'] = trim( $cls . ' bp-seo-hero' );
			continue;
		}
		if ( $cid === $config['body_class'] || $cls === $config['body_class'] || false !== strpos( $cls, $config['body_class'] ) ) {
			$body = $child;
			$body['settings']['css_classes'] = trim( $cls . ' bp-seo-body' );
			continue;
		}
		$rest[] = $child;
	}

	if ( ! is_array( $hero ) || ! is_array( $body ) ) {
		// Fallback: first container = hero, second = body.
		if ( count( $root['elements'] ) >= 2 ) {
			$hero = $root['elements'][0];
			$body = $root['elements'][1];
			$hero['settings']['css_classes'] = trim( ( $hero['settings']['css_classes'] ?? '' ) . ' bp-seo-hero' );
			$body['settings']['css_classes'] = trim( ( $body['settings']['css_classes'] ?? '' ) . ' bp-seo-body' );
			$rest = array_slice( $root['elements'], 2 );
		} else {
			echo "ERROR: Could not locate hero/body on {$slug}.\n";
			continue;
		}
	}

	$archive_shortcode = sprintf( '[biopentra_wc_archive_link slug="%s"]', esc_attr( $config['wc_archive'] ) );

	$search_section = array(
		'id'       => 'b2sr' . substr( md5( $slug ), 0, 4 ),
		'elType'   => 'container',
		'settings' => array(
			'content_width' => 'full',
			'css_classes'   => 'bp-seo-search-section',
		),
		'elements' => array(
			biopentra_seo_category_v2_shortcode_widget( '[biopentra_home_search]', 'b2ss' . substr( md5( $slug ), 0, 3 ) ),
		),
		'isInner'  => true,
	);

	$chips_section = array(
		'id'       => 'b2ct' . substr( md5( $slug ), 0, 4 ),
		'elType'   => 'container',
		'settings' => array(
			'content_width' => 'full',
			'css_classes'   => 'bp-seo-cats-section',
		),
		'elements' => array(
			biopentra_seo_category_v2_shortcode_widget( '[biopentra_home_categories]', 'b2cc' . substr( md5( $slug ), 0, 3 ) ),
		),
		'isInner'  => true,
	);

	$grid_section = array(
		'id'       => 'b2gr' . substr( md5( $slug ), 0, 4 ),
		'elType'   => 'container',
		'settings' => array(
			'content_width' => 'full',
			'css_classes'   => 'bp-seo-grid-section',
		),
		'elements' => array(
			biopentra_seo_category_v2_build_loop_grid( $config['grid_widget_id'] ),
			biopentra_seo_category_v2_shortcode_widget( $archive_shortcode, 'b2lk' . substr( md5( $slug ), 0, 3 ) ),
		),
		'isInner'  => true,
	);

	$root['elements'] = array_merge(
		array( $hero, $search_section, $chips_section, $grid_section, $body ),
		$rest
	);

	update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	update_post_meta( $page_id, 'bp_seo_category_v2_applied', '1' );
	delete_post_meta( $page_id, '_elementor_css' );
	delete_post_meta( $page_id, '_elementor_element_cache' );

	echo "SEO category v2 applied: {$slug} (ID {$page_id})\n";
}

if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}

if ( function_exists( 'wp_cache_flush' ) ) {
	wp_cache_flush();
}

echo "Done. Run flush trio: wp elementor flush-css && wp cache flush\n";
