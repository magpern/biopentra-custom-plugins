<?php
/**
 * M3 — Homepage category discovery band (idempotent Elementor patch).
 *
 * Removes homepage search widget from the under-hero band; retargets section
 * classes for M3 category discovery. Does NOT rebuild Milestone A IA or touch
 * M1/M2 hero/header.
 *
 * Run:
 *   cd /opt/biopentra/apps/wordpress
 *   docker compose run --rm -T wpcli wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-m3-homepage-category-cli.php
 *
 * Backup _elementor_data before first run.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param array    $nodes Tree (by ref).
 * @param string   $id    Element id.
 * @param callable $cb    function( array &$node ): void
 * @return bool
 */
function biopentra_m3_cats_walk_patch( array &$nodes, $id, $cb ) {
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
			if ( biopentra_m3_cats_walk_patch( $node['elements'], $id, $cb ) ) {
				unset( $node );
				return true;
			}
		}
	}
	unset( $node );
	return false;
}

/**
 * @param string $classes Existing classes.
 * @param array  $ensure  Classes that must appear once.
 * @param array  $remove  Classes to strip.
 * @return string
 */
function biopentra_m3_cats_normalize_classes( $classes, array $ensure, array $remove = array() ) {
	$parts = preg_split( '/\s+/', trim( (string) $classes ) );
	$parts = array_values(
		array_filter(
			$parts,
			static function ( $p ) use ( $remove ) {
				return $p !== '' && ! in_array( $p, $remove, true );
			}
		)
	);
	$seen = array();
	$out  = array();
	foreach ( $parts as $p ) {
		if ( isset( $seen[ $p ] ) ) {
			continue;
		}
		$seen[ $p ] = true;
		$out[]      = $p;
	}
	foreach ( $ensure as $need ) {
		if ( ! isset( $seen[ $need ] ) ) {
			$out[]         = $need;
			$seen[ $need ] = true;
		}
	}
	return implode( ' ', $out );
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

echo "M3 category CLI — home page ID {$home_id} (" . get_post_field( 'post_name', $home_id ) . ")\n";

$raw  = get_post_meta( $home_id, '_elementor_data', true );
$data = is_string( $raw ) ? json_decode( $raw, true ) : null;

if ( ! is_array( $data ) || empty( $data ) ) {
	echo "ERROR: No Elementor data on home page.\n";
	return;
}

$band_idx = null;
foreach ( $data as $i => $section ) {
	if ( ( $section['id'] ?? '' ) === 'a1srch0' ) {
		$band_idx = $i;
		break;
	}
}

if ( null === $band_idx ) {
	echo "ERROR: Missing category/search band id a1srch0.\n";
	return;
}

$band = &$data[ $band_idx ];

$band['settings']['css_classes'] = biopentra_m3_cats_normalize_classes(
	$band['settings']['css_classes'] ?? '',
	array( 'bp-home-cats-section', 'bp-m3-cats' ),
	array()
);

$band['settings']['padding'] = array(
	'unit'     => 'px',
	'top'      => '0',
	'right'    => '16',
	'bottom'   => '0',
	'left'     => '16',
	'isLinked' => false,
);
$band['settings']['padding_tablet'] = array(
	'unit'     => 'px',
	'top'      => '0',
	'right'    => '16',
	'bottom'   => '0',
	'left'     => '16',
	'isLinked' => false,
);
$band['settings']['padding_mobile'] = array(
	'unit'     => 'px',
	'top'      => '0',
	'right'    => '16',
	'bottom'   => '0',
	'left'     => '16',
	'isLinked' => false,
);

// Structural removal of homepage search widget a1srch3.
$before_count = isset( $band['elements'] ) && is_array( $band['elements'] ) ? count( $band['elements'] ) : 0;
if ( ! empty( $band['elements'] ) && is_array( $band['elements'] ) ) {
	$band['elements'] = array_values(
		array_filter(
			$band['elements'],
			static function ( $child ) {
				return ( $child['id'] ?? '' ) !== 'a1srch3';
			}
		)
	);
}
$after_count = isset( $band['elements'] ) && is_array( $band['elements'] ) ? count( $band['elements'] ) : 0;

$has_cats = false;
foreach ( $band['elements'] as $child ) {
	if ( ( $child['id'] ?? '' ) === 'a1cats1' ) {
		$has_cats = true;
		break;
	}
}

if ( ! $has_cats ) {
	echo "ERROR: Category widget a1cats1 missing after search removal.\n";
	return;
}

// Keep shortcode plain — homepage label/exclude handled in PHP via is_front_page().
biopentra_m3_cats_walk_patch(
	$band['elements'],
	'a1cats1',
	static function ( array &$node ) {
		$node['settings']['shortcode'] = '[biopentra_home_categories]';
	}
);

$json = wp_json_encode( $data );
update_post_meta( $home_id, '_elementor_data', wp_slash( $json ) );

delete_post_meta( $home_id, '_elementor_css' );
delete_post_meta( $home_id, '_elementor_element_cache' );

if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}

if ( function_exists( 'wp_cache_flush' ) ) {
	wp_cache_flush();
}

echo "M3 category patch applied.\n";
echo "  band_classes={$band['settings']['css_classes']}\n";
echo "  children_before={$before_count} after={$after_count}\n";
echo "  search_widget_a1srch3=" . ( $before_count > $after_count ? "removed\n" : "already absent\n" );
echo "Run: wp elementor flush-css && wp cache flush\n";
