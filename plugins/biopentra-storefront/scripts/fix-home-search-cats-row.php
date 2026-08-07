<?php
/**
 * Fix — homepage quick-search row: unboxed + stacked instead of same-row.
 *
 * The homepage (post 4444) has two separate top-level Elementor containers
 * directly under the hero: `a1srch0` (search input shortcode) and `a1cats0`
 * (category chip rail shortcode). Both explicitly set `content_width: full`
 * (edge-to-edge), unlike every other homepage section (e.g. the Featured
 * Products containers), which are boxed to `boxed_width: 1240`. That's why
 * the search field and chip rail hug the left edge instead of sitting in
 * the same centered column as the rest of the page. They're also two
 * separate stacked containers, so there's no way for them to share a row.
 *
 * Fix (idempotent):
 *  - Move the categories shortcode widget (`a1cats1`) into the search
 *    container (`a1srch0`), before the search widget (`a1srch3`) — chips
 *    on the left, search field on the right.
 *  - Remove the now-empty `a1cats0` container.
 *  - Box `a1srch0` to the same `boxed_width: 1240` as the rest of the page,
 *    make it a flex row with wrap on desktop, and fall back to a stacked
 *    column on tablet/mobile (search above chips, matching current mobile
 *    behaviour).
 *
 * Usage:
 *   wp eval-file wp-content/plugins/biopentra-storefront/scripts/fix-home-search-cats-row.php
 *
 * Env:
 *   BIOPENTRA_HOME_POST_ID — defaults to the current `page_on_front`
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$home_id = (int) ( getenv( 'BIOPENTRA_HOME_POST_ID' ) ?: get_option( 'page_on_front' ) );
if ( ! $home_id ) {
	echo "ERROR: could not resolve homepage post id\n";
	exit( 1 );
}

$raw = get_post_meta( $home_id, '_elementor_data', true );
if ( empty( $raw ) ) {
	echo "ERROR: No _elementor_data for homepage {$home_id}\n";
	exit( 1 );
}

$data = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
if ( ! is_array( $data ) ) {
	echo "ERROR: could not decode Elementor JSON for {$home_id}\n";
	exit( 1 );
}

$search_container = null;
$cats_container    = null;
$cats_index         = null;

foreach ( $data as $i => &$node ) {
	if ( ! is_array( $node ) || ( $node['elType'] ?? '' ) !== 'container' ) {
		continue;
	}
	$classes = $node['settings']['css_classes'] ?? '';
	if ( false !== strpos( $classes, 'bp-home-search-section' ) ) {
		$search_container = &$node;
	}
	if ( false !== strpos( $classes, 'bp-home-cats-section' ) ) {
		$cats_container = $node;
		$cats_index     = $i;
	}
}
unset( $node );

if ( ! $search_container ) {
	echo "ERROR: bp-home-search-section container not found\n";
	exit( 1 );
}

$already_merged = false;
foreach ( $search_container['elements'] as $child ) {
	if ( ( $child['id'] ?? '' ) === 'a1cats1' ) {
		$already_merged = true;
		break;
	}
}

if ( $already_merged ) {
	echo "Homepage {$home_id}: search + categories already merged — checking layout settings only\n";
} elseif ( null === $cats_container ) {
	echo "Homepage {$home_id}: bp-home-cats-section not found and not already merged — nothing to move, aborting\n";
	exit( 1 );
} else {
	$cats_widget = null;
	foreach ( $cats_container['elements'] as $child ) {
		if ( ( $child['id'] ?? '' ) === 'a1cats1' ) {
			$cats_widget = $child;
			break;
		}
	}
	if ( ! $cats_widget ) {
		echo "ERROR: categories shortcode widget a1cats1 not found inside bp-home-cats-section\n";
		exit( 1 );
	}
	array_unshift( $search_container['elements'], $cats_widget );
	array_splice( $data, $cats_index, 1 );
	echo "Homepage {$home_id}: moved categories widget into search container, removed empty cats container\n";
}

$search_container['settings']['content_width'] = 'boxed';
$search_container['settings']['boxed_width']    = array(
	'unit'  => 'px',
	'size'  => 1240,
	'sizes' => array(),
);
$search_container['settings']['flex_direction']         = 'row';
$search_container['settings']['flex_direction_tablet']  = 'column';
$search_container['settings']['flex_direction_mobile']  = 'column';
$search_container['settings']['flex_wrap']              = 'wrap';
$search_container['settings']['align_items']            = 'center';
$search_container['settings']['align_items_tablet']     = 'stretch';
$search_container['settings']['align_items_mobile']     = 'stretch';
$search_container['settings']['flex_gap']               = array(
	'column'   => '16',
	'row'      => '12',
	'isLinked' => false,
	'unit'     => 'px',
	'size'     => 16,
);

$encoded = wp_json_encode( $data );
if ( false === $encoded ) {
	echo "ERROR: json_encode failed\n";
	exit( 1 );
}
update_post_meta( $home_id, '_elementor_data', wp_slash( $encoded ) );
delete_post_meta( $home_id, '_elementor_element_cache' );
delete_post_meta( $home_id, '_elementor_css' );
if ( class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}

echo "Homepage {$home_id}: search+categories row boxed to 1240px, row on desktop, column on tablet/mobile\n";
echo "OK\n";
