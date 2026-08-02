<?php
/**
 * Milestone B — rebuild shop page Elementor IA (commercial-first mobile layout).
 *
 * Run:
 *   cd /opt/biopentra/apps/wordpress
 *   docker compose run --rm -T wpcli wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-shop-page-v2-cli.php
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BIOPENTRA_STOREFRONT_PATH . 'includes/shopping-v2-helpers.php';

/**
 * @param array    $nodes Tree (by ref).
 * @param string   $id    Element id.
 * @param callable $cb    function( array &$node ): void
 * @return bool
 */
function biopentra_shop_v2_walk_patch( array &$nodes, $id, $cb ) {
	foreach ( $nodes as &$node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		if ( isset( $node['id'] ) && $node['id'] === $id ) {
			$cb( $node );
			unset( $node );
			return true;
		}
		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			if ( biopentra_shop_v2_walk_patch( $node['elements'], $id, $cb ) ) {
				unset( $node );
				return true;
			}
		}
	}
	unset( $node );
	return false;
}

$shop_id = (int) get_option( 'woocommerce_shop_page_id' );
if ( ! $shop_id ) {
	$shop_page = get_page_by_path( 'shop', OBJECT, 'page' );
	$shop_id   = $shop_page ? (int) $shop_page->ID : 0;
}

if ( ! $shop_id ) {
	echo "ERROR: Could not resolve shop page.\n";
	return;
}

echo "Shop page ID {$shop_id} (" . get_post_field( 'post_name', $shop_id ) . ")\n";

$raw  = get_post_meta( $shop_id, '_elementor_data', true );
$data = is_string( $raw ) ? json_decode( $raw, true ) : null;

if ( ! is_array( $data ) || count( $data ) < 2 ) {
	echo "ERROR: Shop Elementor data missing or unexpected structure.\n";
	return;
}

$by_id = array();
foreach ( $data as $section ) {
	if ( ! empty( $section['id'] ) ) {
		$by_id[ $section['id'] ] = $section;
	}
}

foreach ( array( '61f84d4', 'eae00bb' ) as $rid ) {
	if ( ! isset( $by_id[ $rid ] ) ) {
		echo "ERROR: Missing expected section id {$rid}.\n";
		return;
	}
}

if ( ! shortcode_exists( 'biopentra_shop_search' ) || ! shortcode_exists( 'biopentra_home_categories' ) ) {
	echo "ERROR: Required shortcodes not registered.\n";
	return;
}

// Compact hero — remove trust badge row, tighten spacing.
$hero = &$by_id['61f84d4'];
$hero['settings']['css_classes'] = trim( ( $hero['settings']['css_classes'] ?? '' ) . ' bp-shop-hero' );

if ( ! empty( $hero['elements'][0]['elements'] ) ) {
	$hero['elements'][0]['elements'] = array_values(
		array_filter(
			$hero['elements'][0]['elements'],
			static function ( $w ) {
				return '1d548e2' !== ( $w['id'] ?? '' );
			}
		)
	);
}

$hero['settings']['padding']        = array(
	'unit'     => 'px',
	'top'      => '24',
	'right'    => '16',
	'bottom'   => '16',
	'left'     => '16',
	'isLinked' => false,
);
$hero['settings']['padding_mobile'] = $hero['settings']['padding'];

$search_section = array(
	'id'       => 'b2srch0',
	'elType'   => 'container',
	'settings' => array(
		'content_width' => 'full',
		'css_classes'   => 'bp-shop-search-section',
		'padding'       => array(
			'unit'     => 'px',
			'top'      => '0',
			'right'    => '16',
			'bottom'   => '4',
			'left'     => '16',
			'isLinked' => false,
		),
	),
	'elements' => array(
		array(
			'id'         => 'b2srch1',
			'elType'     => 'widget',
			'widgetType' => 'shortcode',
			'settings'   => array( 'shortcode' => '[biopentra_shop_search]' ),
			'elements'   => array(),
		),
	),
	'isInner'  => false,
);

$categories_section = array(
	'id'       => 'b2cats0',
	'elType'   => 'container',
	'settings' => array(
		'content_width' => 'full',
		'css_classes'   => 'bp-shop-cats-section',
		'padding'       => array(
			'unit'     => 'px',
			'top'      => '0',
			'right'    => '16',
			'bottom'   => '8',
			'left'     => '16',
			'isLinked' => false,
		),
	),
	'elements' => array(
		array(
			'id'         => 'b2cats1',
			'elType'     => 'widget',
			'widgetType' => 'shortcode',
			'settings'   => array( 'shortcode' => '[biopentra_home_categories]' ),
			'elements'   => array(),
		),
	),
	'isInner'  => false,
);

// Product section: taxonomy filter → loop → compliance disclaimer.
$products = &$by_id['eae00bb'];
$products['settings']['css_classes'] = trim( ( $products['settings']['css_classes'] ?? '' ) . ' bp-shop-products-section' );

$product_inner = &$products['elements'][0];
$loop_el       = null;
$disclaimer    = null;
$filter_row    = null;

foreach ( $product_inner['elements'] as $el ) {
	if ( ! is_array( $el ) ) {
		continue;
	}
	if ( 'ed52b7f' === ( $el['id'] ?? '' ) ) {
		$loop_el = $el;
	}
	if ( 'f2917e3' === ( $el['id'] ?? '' ) ) {
		$disclaimer = $el;
	}
	if ( 'c4b3a91' === ( $el['id'] ?? '' ) ) {
		$filter_row = $el;
	}
}

if ( ! is_array( $loop_el ) ) {
	echo "ERROR: loop-grid ed52b7f not found.\n";
	return;
}

if ( is_array( $filter_row ) && ! empty( $filter_row['elements'] ) ) {
	$filter_row['elements'] = array_values(
		array_filter(
			$filter_row['elements'],
			static function ( $w ) {
				return 'a2917d2' !== ( $w['id'] ?? '' );
			}
		)
	);
} else {
	$filter_row = array(
		'id'       => 'c4b3a91',
		'elType'   => 'container',
		'settings' => array(
			'content_width'         => 'full',
			'flex_direction'        => 'row',
			'flex_direction_mobile' => 'column',
		),
		'elements' => array(
			array(
				'id'         => 'b3a2918',
				'elType'     => 'widget',
				'widgetType' => 'taxonomy-filter',
				'settings'   => array(
					'selected_element' => 'ed52b7f',
					'taxonomy'         => 'product_cat',
					'direction'        => 'horizontal',
				),
				'elements'   => array(),
			),
		),
		'isInner'  => true,
	);
}

if ( ! is_array( $disclaimer ) ) {
	$disclaimer = array(
		'id'         => 'f2917e3',
		'elType'     => 'widget',
		'widgetType' => 'text-editor',
		'settings'   => array(
			'editor' => '<p><strong>Important:</strong> All products listed on this site are intended strictly for in vitro laboratory research use only.</p>',
		),
		'elements'   => array(),
	);
}

if ( function_exists( 'biopentra_loop_card_shop_pagination_settings' ) ) {
	$loop_el['settings'] = array_merge( $loop_el['settings'] ?? array(), biopentra_loop_card_shop_pagination_settings() );
}

$product_inner['elements'] = array( $filter_row, $loop_el, $disclaimer );

$new_data = array(
	$hero,
	$search_section,
	$categories_section,
	$products,
);

update_post_meta( $shop_id, '_elementor_data', wp_slash( wp_json_encode( $new_data ) ) );
update_post_meta( $shop_id, '_wp_page_template', 'elementor_header_footer' );
update_post_meta( $shop_id, 'bp_shop_v2_applied', '1' );

delete_post_meta( $shop_id, '_elementor_css' );
delete_post_meta( $shop_id, '_elementor_element_cache' );

if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}

if ( function_exists( 'wp_cache_flush' ) ) {
	wp_cache_flush();
}

echo "Shop v2 applied.\n";
echo "Order: hero → search → category chips → filter → product grid → compliance disclaimer → category descriptions (PHP after grid).\n";
echo "Run flush trio: wp elementor flush-css && wp cache flush\n";
