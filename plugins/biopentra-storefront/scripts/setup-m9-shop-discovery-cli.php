<?php
/**
 * M9 — Shop Discovery Consolidation (idempotent Elementor mutation on shop page).
 *
 * WP3 Path A: keep Elementor taxonomy-filter b3a2918 as the supported filter
 * engine for loop ed52b7f; remove the duplicate archive-chip band (b2cats0);
 * add M9 discovery CSS classes; tighten search/filter spacing; enable
 * horizontal scroll on the taxonomy filter for ≤1024.
 *
 * Does NOT remove b3a2918.
 *
 * Writes a first-run backup of _elementor_data to
 * docs/storefront-redesign/changes/backups/shop-pre-M9.json (never overwritten).
 *
 * Usage:
 *   wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-m9-shop-discovery-cli.php
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$shop_id = (int) ( getenv( 'BIOPENTRA_M9_SHOP_POST_ID' ) ?: 0 );
if ( $shop_id <= 0 && function_exists( 'wc_get_page_id' ) ) {
	$shop_id = (int) wc_get_page_id( 'shop' );
}
if ( $shop_id <= 0 ) {
	$shop_page = get_page_by_path( 'shop', OBJECT, 'page' );
	$shop_id   = $shop_page ? (int) $shop_page->ID : 0;
}

if ( $shop_id <= 0 ) {
	echo "ERROR: Could not resolve shop page.\n";
	exit( 1 );
}

echo "Shop page ID {$shop_id}\n";

$raw = get_post_meta( $shop_id, '_elementor_data', true );
if ( empty( $raw ) ) {
	echo "ERROR: No _elementor_data for shop {$shop_id}\n";
	exit( 1 );
}

$data = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
if ( ! is_array( $data ) ) {
	echo "ERROR: could not decode Elementor JSON for {$shop_id}\n";
	exit( 1 );
}

/**
 * @param array<int,mixed> $nodes
 * @return array<string,mixed>|null
 */
function biopentra_m9_find( array $nodes, string $id ) {
	foreach ( $nodes as $node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		if ( ( $node['id'] ?? '' ) === $id ) {
			return $node;
		}
		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			$found = biopentra_m9_find( $node['elements'], $id );
			if ( null !== $found ) {
				return $found;
			}
		}
	}
	return null;
}

/**
 * @param array<int,mixed> $nodes
 * @return array<string,mixed>|null
 */
function biopentra_m9_extract( array &$nodes, string $id ) {
	foreach ( $nodes as $i => &$node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		if ( ( $node['id'] ?? '' ) === $id ) {
			$removed = $node;
			unset( $nodes[ $i ] );
			$nodes = array_values( $nodes );
			return $removed;
		}
		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			$removed = biopentra_m9_extract( $node['elements'], $id );
			if ( null !== $removed ) {
				return $removed;
			}
		}
	}
	unset( $node );
	return null;
}

/**
 * @param array<string,mixed> $node
 */
function biopentra_m9_add_class( array &$node, string $class ) {
	if ( ! isset( $node['settings'] ) || ! is_array( $node['settings'] ) ) {
		$node['settings'] = array();
	}
	$existing = (string) ( $node['settings']['_css_classes'] ?? $node['settings']['css_classes'] ?? '' );
	$classes  = array_filter( explode( ' ', $existing ) );
	if ( ! in_array( $class, $classes, true ) ) {
		$classes[] = $class;
	}
	$joined                           = implode( ' ', $classes );
	$node['settings']['_css_classes'] = $joined;
	$node['settings']['css_classes']  = $joined;
}

/**
 * Patch a node by id (mutates tree).
 *
 * @param array<int,mixed> $nodes
 * @param callable         $cb
 */
function biopentra_m9_patch( array &$nodes, string $id, callable $cb ): bool {
	foreach ( $nodes as &$node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		if ( ( $node['id'] ?? '' ) === $id ) {
			$cb( $node );
			unset( $node );
			return true;
		}
		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			if ( biopentra_m9_patch( $node['elements'], $id, $cb ) ) {
				unset( $node );
				return true;
			}
		}
	}
	unset( $node );
	return false;
}

// Required frozen IDs must exist.
foreach ( array( '61f84d4', 'b2srch0', 'eae00bb', 'b3a2918', 'ed52b7f', 'f2917e3' ) as $rid ) {
	if ( null === biopentra_m9_find( $data, $rid ) ) {
		echo "ERROR: Missing expected element id {$rid} — structure drifted, refusing to proceed\n";
		exit( 1 );
	}
}

// First-run backup (never overwrite). Same pattern as M8 footer CLI:
// prefer host-mounted repo path; fall back to uploads if the container
// cannot see /opt/biopentra (operator copies into the repo afterward).
$repo_backup     = '/opt/biopentra/dev/biopentra-custom-plugins/docs/storefront-redesign/changes/backups/shop-pre-M9.json';
$uploads         = wp_upload_dir();
$fallback_backup = trailingslashit( $uploads['basedir'] ) . 'biopentra-m9-backups/shop-pre-M9.json';
$backup_payload  = is_string( $raw ) ? $raw : wp_json_encode( $data );

if ( is_dir( dirname( $repo_backup ) ) && ! file_exists( $repo_backup ) ) {
	file_put_contents( $repo_backup, $backup_payload );
	echo "Backup written: {$repo_backup}\n";
} elseif ( file_exists( $repo_backup ) ) {
	echo "Backup already present: {$repo_backup} (not overwritten)\n";
} else {
	wp_mkdir_p( dirname( $fallback_backup ) );
	if ( ! file_exists( $fallback_backup ) ) {
		file_put_contents( $fallback_backup, $backup_payload );
		echo "Repo backup path not reachable from this container; fallback backup written: {$fallback_backup}\n";
		echo "Operator: copy this file into docs/storefront-redesign/changes/backups/shop-pre-M9.json in the repo.\n";
	} else {
		echo "Fallback backup already present: {$fallback_backup} (not overwritten)\n";
	}
}

$already = (string) get_post_meta( $shop_id, 'bp_shop_m9_applied', true );
$removed_cats = biopentra_m9_find( $data, 'b2cats0' ) === null;

// Remove duplicate archive-chip band (navigation chips). Keep b3a2918.
$extracted = biopentra_m9_extract( $data, 'b2cats0' );
if ( null !== $extracted ) {
	echo "Removed archive-chip section b2cats0 (and nested b2cats1 shortcode).\n";
} elseif ( $removed_cats ) {
	echo "Archive-chip section b2cats0 already absent (idempotent).\n";
} else {
	echo "WARN: b2cats0 not found for removal — continuing.\n";
}

biopentra_m9_patch(
	$data,
	'b2srch0',
	static function ( array &$node ) {
		biopentra_m9_add_class( $node, 'bp-shop-m9-search' );
		biopentra_m9_add_class( $node, 'bp-m9-discovery' );
		$node['settings']['padding'] = array(
			'unit'     => 'px',
			'top'      => '8',
			'right'    => '16',
			'bottom'   => '4',
			'left'     => '16',
			'isLinked' => false,
		);
		$node['settings']['padding_tablet'] = array(
			'unit'     => 'px',
			'top'      => '8',
			'right'    => '16',
			'bottom'   => '4',
			'left'     => '16',
			'isLinked' => false,
		);
		$node['settings']['padding_mobile'] = array(
			'unit'     => 'px',
			'top'      => '8',
			'right'    => '12',
			'bottom'   => '4',
			'left'     => '12',
			'isLinked' => false,
		);
	}
);

biopentra_m9_patch(
	$data,
	'b3a2918',
	static function ( array &$node ) {
		biopentra_m9_add_class( $node, 'bp-shop-m9-cats' );
		// Enable Elementor horizontal scroll (CSS still owns M9 chip language).
		$node['settings']['horizontal_scroll']        = 'enable';
		$node['settings']['horizontal_scroll_tablet'] = 'enable';
		$node['settings']['horizontal_scroll_mobile'] = 'enable';
	}
);

biopentra_m9_patch(
	$data,
	'c4b3a91',
	static function ( array &$node ) {
		biopentra_m9_add_class( $node, 'bp-shop-m9-filter-row' );
		$node['settings']['padding'] = array(
			'unit'     => 'px',
			'top'      => '0',
			'right'    => '16',
			'bottom'   => '8',
			'left'     => '16',
			'isLinked' => false,
		);
		$node['settings']['padding_mobile'] = array(
			'unit'     => 'px',
			'top'      => '0',
			'right'    => '12',
			'bottom'   => '8',
			'left'     => '12',
			'isLinked' => false,
		);
	}
);

biopentra_m9_patch(
	$data,
	'eae00bb',
	static function ( array &$node ) {
		biopentra_m9_add_class( $node, 'bp-shop-m9-products' );
		// Pull products closer to discovery band.
		$node['settings']['margin'] = array(
			'unit'     => 'px',
			'top'      => '0',
			'right'    => '0',
			'bottom'   => '0',
			'left'     => '0',
			'isLinked' => false,
		);
	}
);

$encoded = wp_json_encode( $data );
if ( ! is_string( $encoded ) || '' === $encoded ) {
	echo "ERROR: Failed to encode Elementor JSON\n";
	exit( 1 );
}

update_post_meta( $shop_id, '_elementor_data', wp_slash( $encoded ) );
update_post_meta( $shop_id, '_elementor_version', '0.4' );
update_post_meta( $shop_id, 'bp_shop_m9_applied', '1' );
if ( ! get_post_meta( $shop_id, 'bp_shop_v2_applied', true ) ) {
	update_post_meta( $shop_id, 'bp_shop_v2_applied', '1' );
}

// Clear Elementor CSS cache for this document when available.
if ( class_exists( '\Elementor\Plugin' ) ) {
	$plugin = \Elementor\Plugin::$instance;
	if ( isset( $plugin->files_manager ) && method_exists( $plugin->files_manager, 'clear_cache' ) ) {
		$plugin->files_manager->clear_cache();
		echo "Elementor files_manager cache cleared.\n";
	}
}

echo "M9 shop discovery structure applied (idempotent). already_flag={$already}\n";
echo "b3a2918 retained as Elementor taxonomy-filter engine for ed52b7f.\n";
echo "DONE\n";
