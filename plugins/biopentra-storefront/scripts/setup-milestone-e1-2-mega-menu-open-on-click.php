<?php
/**
 * Milestone E1.2 — idempotent fix for mega-menu hover-close regression.
 *
 * Elementor Pro's mega-menu widget defaults `open_on` to "hover". In hover
 * mode, moving the mouse from the "Information" title toward an item inside
 * its open panel very often crosses a sibling top-level title (Home / Shop /
 * About Us / Contact) still in the same row — and Elementor Pro's own
 * mega-menu.js unconditionally closes the active dropdown whenever the mouse
 * enters ANY title (`onMouseTitleEnter` → `changeActiveTab()` →
 * `deactivateActiveTab()`), even a plain link with no dropdown of its own.
 * This was unreachable before Milestone E1.1 because desktop was stuck in
 * the collapsed/hamburger layout, so the hover-open codepath never actually
 * ran on desktop until E1.1 restored the horizontal nav.
 *
 * Fix: set the mega-menu widget's own `open_on` setting to "click" — a
 * supported, documented Elementor Pro control (not a CSS/JS override).
 * Click-to-open sidesteps the fragile hover geometry entirely and matches
 * the behaviour already used on mobile/tablet (which always open on click).
 *
 * Usage:
 *   wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-milestone-e1-2-mega-menu-open-on-click.php
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
function biopentra_e12_walk_elements( array &$nodes, $visitor ) {
	foreach ( $nodes as &$node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		$visitor( $node );
		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			biopentra_e12_walk_elements( $node['elements'], $visitor );
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

$found       = 0;
$changed     = 0;
$already_set = 0;

biopentra_e12_walk_elements(
	$data,
	static function ( &$node ) use ( &$found, &$changed, &$already_set ) {
		if ( ( $node['elType'] ?? '' ) !== 'widget' || ( $node['widgetType'] ?? '' ) !== 'mega-menu' ) {
			return;
		}
		++$found;
		if ( ! isset( $node['settings'] ) || ! is_array( $node['settings'] ) ) {
			$node['settings'] = array();
		}
		$current = $node['settings']['open_on'] ?? '(default: hover)';
		if ( 'click' === ( $node['settings']['open_on'] ?? '' ) ) {
			++$already_set;
			echo "Mega-menu widget {$node['id']}: open_on already 'click'\n";
			return;
		}
		$node['settings']['open_on'] = 'click';
		++$changed;
		echo "Mega-menu widget {$node['id']}: open_on {$current} -> click\n";
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
	echo "Header {$header_id}: no changes needed ({$already_set} widget(s) already correct)\n";
}

echo "OK\n";
