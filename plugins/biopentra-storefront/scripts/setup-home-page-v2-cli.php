<?php
/**
 * Milestone A — rebuild homepage Elementor IA for commercial-first mobile layout.
 *
 * Run:
 *   cd /opt/biopentra/apps/wordpress
 *   docker compose run --rm -T wpcli wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-home-page-v2-cli.php
 *
 * Backup _elementor_data before running (see docs/storefront-redesign/changes/backups/).
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BIOPENTRA_STOREFRONT_PATH . 'includes/home-v2-helpers.php';

/**
 * @param array    $nodes Tree (by ref).
 * @param string   $id    Element id.
 * @param callable $cb    function( array &$node ): void
 * @return bool
 */
function biopentra_home_v2_walk_patch( array &$nodes, $id, $cb ) {
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
			if ( biopentra_home_v2_walk_patch( $node['elements'], $id, $cb ) ) {
				unset( $node );
				return true;
			}
		}
	}
	unset( $node );
	return false;
}

/**
 * @param array $node Subtree (by ref).
 */
function biopentra_home_v2_regenerate_ids( array &$node ) {
	$node['id'] = substr( dechex( random_int( 0, 0xfffffff ) ), 0, 7 );
	if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
		foreach ( $node['elements'] as &$child ) {
			biopentra_home_v2_regenerate_ids( $child );
		}
		unset( $child );
	}
}

/**
 * @param array $section Top-level section.
 * @return array Deep clone with new element ids.
 */
function biopentra_home_v2_clone_section( array $section ) {
	$clone = json_decode( wp_json_encode( $section ), true );
	if ( ! is_array( $clone ) ) {
		return $section;
	}
	biopentra_home_v2_regenerate_ids( $clone );
	return $clone;
}

/**
 * @param array  $section      Section subtree (by ref).
 * @param string $heading      Section H2 text.
 * @param array  $loop_patch   Settings merged into loop-grid widget.
 * @param bool   $drop_intro   Remove intro text-editor before grid.
 */
function biopentra_home_v2_configure_product_section( array &$section, $heading, array $loop_patch, $drop_intro = false ) {
	$heading_set = false;
	$walk        = static function ( array &$nodes ) use ( &$walk, $heading, $loop_patch, $drop_intro, &$heading_set ) {
		$filtered = array();
		foreach ( $nodes as &$node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}
			if ( $drop_intro && ( $node['widgetType'] ?? '' ) === 'text-editor' ) {
				continue;
			}
			if ( ! $heading_set && ( $node['widgetType'] ?? '' ) === 'heading' ) {
				$node['settings']['title']       = $heading;
				$node['settings']['header_size'] = 'h2';
				$heading_set                     = true;
			}
			if ( ( $node['widgetType'] ?? '' ) === 'loop-grid' ) {
				$node['settings'] = array_merge( $node['settings'] ?? array(), $loop_patch );
			}
			if ( ! empty( $node['elements'] ) ) {
				$walk( $node['elements'] );
			}
			$filtered[] = $node;
		}
		unset( $node );
		$nodes = $filtered;
	};
	$walk( $section['elements'] );
}

$home_id = (int) get_option( 'page_on_front' );
if ( ! $home_id ) {
	$home_page = get_page_by_path( 'home', OBJECT, 'page' );
	$home_id   = $home_page ? (int) $home_page->ID : 0;
}

if ( ! $home_id ) {
	echo "ERROR: Could not resolve front page.\n";
	return;
}

echo "Home page ID {$home_id} (" . get_post_field( 'post_name', $home_id ) . ")\n";

$raw  = get_post_meta( $home_id, '_elementor_data', true );
$data = is_string( $raw ) ? json_decode( $raw, true ) : null;

if ( ! is_array( $data ) || empty( $data ) ) {
	echo "ERROR: No Elementor data on home page.\n";
	return;
}

$by_id = array();
foreach ( $data as $section ) {
	if ( ! empty( $section['id'] ) ) {
		$by_id[ $section['id'] ] = $section;
	}
}

$required = array( '41734c4', 'be24b65', '7fe474f', 'why4444', '0b22897', 'faqPreview4444', '238bcc6' );
foreach ( $required as $rid ) {
	if ( ! isset( $by_id[ $rid ] ) ) {
		echo "ERROR: Missing expected section id {$rid}.\n";
		return;
	}
}

// --- Compact hero ---
$hero = &$by_id['41734c4'];
$hero['settings']['min_height']         = array(
	'unit'  => 'px',
	'size'  => 200,
	'sizes' => array(),
);
$hero['settings']['min_height_tablet']  = array(
	'unit'  => 'px',
	'size'  => 240,
	'sizes' => array(),
);
$hero['settings']['min_height_mobile']  = array(
	'unit'  => 'px',
	'size'  => 180,
	'sizes' => array(),
);
$hero['settings']['padding']            = array(
	'unit'     => 'px',
	'top'      => '32',
	'right'    => '16',
	'bottom'   => '24',
	'left'     => '16',
	'isLinked' => false,
);
$hero['settings']['padding_mobile']     = array(
	'unit'     => 'px',
	'top'      => '24',
	'right'    => '16',
	'bottom'   => '16',
	'left'     => '16',
	'isLinked' => false,
);
$hero['settings']['css_classes']        = trim( ( $hero['settings']['css_classes'] ?? '' ) . ' bp-home-hero' );

if ( ! empty( $hero['elements'][0]['elements'] ) ) {
	$hero['elements'][0]['elements'] = array_values(
		array_filter(
			$hero['elements'][0]['elements'],
			static function ( $w ) {
				return ! in_array( $w['id'] ?? '', array( '9d79260', '52085d0', '048a31e' ), true );
			}
		)
	);
}

biopentra_home_v2_walk_patch(
	$hero['elements'],
	'3fb1a1d',
	static function ( array &$node ) {
		if ( ( $node['widgetType'] ?? '' ) === 'text-editor' ) {
			$node['settings']['editor'] = '';
		}
	}
);

// --- Search section ---
if ( ! shortcode_exists( 'biopentra_home_search' ) ) {
	echo "ERROR: Shortcode biopentra_home_search not registered — load storefront plugin first.\n";
	return;
}

$search_section = array(
	'id'       => 'a1srch0',
	'elType'   => 'container',
	'settings' => array(
		'content_width' => 'full',
		'padding'       => array(
			'unit'     => 'px',
			'top'      => '0',
			'right'    => '16',
			'bottom'   => '4',
			'left'     => '16',
			'isLinked' => false,
		),
		'css_classes'   => 'bp-home-search-section',
	),
	'elements' => array(
		array(
			'id'         => 'a1srch3',
			'elType'     => 'widget',
			'widgetType' => 'shortcode',
			'settings'   => array(
				'shortcode' => '[biopentra_home_search]',
			),
			'elements'   => array(),
		),
	),
	'isInner'  => false,
);

// --- Category chips ---
$chips_html = biopentra_storefront_build_home_category_chips_html();
if ( $chips_html === '' ) {
	echo "WARN: No category chips built.\n";
}

$categories_section = array(
	'id'       => 'a1cats0',
	'elType'   => 'container',
	'settings' => array(
		'content_width' => 'full',
		'padding'       => array(
			'unit'     => 'px',
			'top'      => '0',
			'right'    => '16',
			'bottom'   => '16',
			'left'     => '16',
			'isLinked' => false,
		),
		'css_classes'   => 'bp-home-cats-section',
	),
	'elements' => array(
		array(
			'id'         => 'a1cats1',
			'elType'     => 'widget',
			'widgetType' => 'shortcode',
			'settings'   => array(
				'shortcode' => '[biopentra_home_categories]',
			),
			'elements'   => array(),
		),
	),
	'isInner'  => false,
);

// --- Product grids: featured, newest, popular ---
$featured = $by_id['be24b65'];
$featured['settings']['css_classes'] = trim( ( $featured['settings']['css_classes'] ?? '' ) . ' bp-home-products-section' );
biopentra_home_v2_configure_product_section(
	$featured,
	'Featured products',
	array(
		'posts_per_page'          => 4,
		'product_query_orderby'   => 'menu_order',
		'query_order'             => 'ASC',
		'pagination_type'         => '',
	),
	true
);

$newest = biopentra_home_v2_clone_section( $by_id['be24b65'] );
biopentra_home_v2_configure_product_section(
	$newest,
	'Newest products',
	array(
		'posts_per_page'          => 4,
		'product_query_orderby'   => 'date',
		'query_order'             => 'DESC',
		'pagination_type'         => '',
	)
);

$popular = biopentra_home_v2_clone_section( $by_id['be24b65'] );
biopentra_home_v2_configure_product_section(
	$popular,
	'Popular products',
	array(
		'posts_per_page'          => 4,
		'product_query_orderby'   => 'popularity',
		'query_order'             => 'DESC',
		'pagination_type'         => '',
	)
);

$shop_url = function_exists( 'wc_get_page_id' ) ? get_permalink( (int) wc_get_page_id( 'shop' ) ) : home_url( '/shop/' );

// Button labels on product sections.
$set_shop_button = static function ( array &$section, $text ) use ( $shop_url ) {
	$walk = static function ( array &$nodes ) use ( &$walk, $text, $shop_url ) {
		foreach ( $nodes as &$node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}
			if ( ( $node['widgetType'] ?? '' ) === 'button' ) {
				$node['settings']['text'] = $text;
				$node['settings']['link']   = array(
					'url'               => $shop_url,
					'is_external'       => '',
					'nofollow'          => '',
					'custom_attributes' => '',
				);
			}
			if ( ! empty( $node['elements'] ) ) {
				$walk( $node['elements'] );
			}
		}
		unset( $node );
	};
	$walk( $section['elements'] );
};

$set_shop_button( $featured, 'View all products' );
$set_shop_button( $newest, 'Browse shop' );
$set_shop_button( $popular, 'Browse shop' );

// --- DOM order: commercial first, editorial below ---
$new_data = array(
	$hero,
	$search_section,
	$categories_section,
	$featured,
	$newest,
	$popular,
	$by_id['0b22897'],
	$by_id['7fe474f'],
	$by_id['why4444'],
	$by_id['faqPreview4444'],
	$by_id['238bcc6'],
);

$json = wp_json_encode( $new_data );

update_post_meta( $home_id, '_elementor_edit_mode', 'builder' );
update_post_meta( $home_id, '_elementor_template_type', 'wp-page' );
update_post_meta( $home_id, '_wp_page_template', 'elementor_header_footer' );
update_post_meta( $home_id, '_elementor_data', wp_slash( $json ) );

delete_post_meta( $home_id, '_elementor_css' );
delete_post_meta( $home_id, '_elementor_element_cache' );

if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}

if ( function_exists( 'wp_cache_flush' ) ) {
	wp_cache_flush();
}

echo "Homepage v2 applied to page {$home_id}.\n";
echo "Sections: hero → search → categories → featured/newest/popular → research → trust → why → faq → cta\n";
echo "Run flush trio: wp elementor flush-css && wp cache flush\n";
