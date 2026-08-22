<?php
/**
 * Header corrective — restore desktop Information mega-menu hover + overlay behaviour.
 *
 * Reverts the E1.2 `open_on=click` workaround now that M1 desktop CSS again uses
 * Elementor's absolute overlay positioning (see bp-header-m1.css ≥1025 rules).
 *
 * Usage:
 *   wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-header-mega-menu-desktop-restore-cli.php
 *
 * Env:
 *   BIOPENTRA_E_HEADER_POST_ID=3782
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$header_id = (int) ( getenv( 'BIOPENTRA_E_HEADER_POST_ID' ) ?: 3782 );

/**
 * @param array<int,mixed> $nodes
 * @param callable         $visitor
 */
function biopentra_header_mega_walk( array &$nodes, $visitor ) {
	foreach ( $nodes as &$node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		$visitor( $node );
		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			biopentra_header_mega_walk( $node['elements'], $visitor );
		}
	}
	unset( $node );
}

$raw = get_post_meta( $header_id, '_elementor_data', true );
if ( empty( $raw ) ) {
	echo "ERROR: No _elementor_data for header {$header_id}\n";
	exit( 1 );
}

$data = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
if ( ! is_array( $data ) ) {
	echo "ERROR: could not decode Elementor JSON for {$header_id}\n";
	exit( 1 );
}

$found   = 0;
$changed = 0;

biopentra_header_mega_walk(
	$data,
	static function ( &$node ) use ( &$found, &$changed ) {
		if ( ( $node['elType'] ?? '' ) !== 'widget' || ( $node['widgetType'] ?? '' ) !== 'mega-menu' ) {
			return;
		}
		++$found;
		if ( ! isset( $node['settings'] ) || ! is_array( $node['settings'] ) ) {
			$node['settings'] = array();
		}
		$current = $node['settings']['open_on'] ?? '(default: hover)';
		if ( 'hover' === ( $node['settings']['open_on'] ?? 'hover' ) ) {
			echo "Mega-menu widget {$node['id']}: open_on already 'hover'\n";
			return;
		}
		$node['settings']['open_on'] = 'hover';
		++$changed;
		echo "Mega-menu widget {$node['id']}: open_on {$current} -> hover\n";
	}
);

if ( 0 === $found ) {
	echo "ERROR: no mega-menu widget found in header {$header_id}\n";
	exit( 1 );
}

if ( $changed > 0 ) {
	$encoded = wp_json_encode( $data );
	if ( false === $encoded ) {
		echo "ERROR: json_encode failed\n";
		exit( 1 );
	}
	update_post_meta( $header_id, '_elementor_data', wp_slash( $encoded ) );
	delete_post_meta( $header_id, '_elementor_element_cache' );
	delete_post_meta( $header_id, '_elementor_css' );
	if ( class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
	echo "Header {$header_id}: saved + Elementor caches cleared ({$changed} widget(s) updated)\n";
} else {
	echo "Header {$header_id}: no changes needed\n";
}

echo "OK\n";
