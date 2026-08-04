<?php
/**
 * Milestone E3 — idempotent Elementor footer 3823 mobile spacing compaction.
 *
 * Sets Elementor mobile padding/gap overrides so the footer is materially
 * shorter on 360–430 without removing crawlable links or research text.
 *
 * Usage:
 *   wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-milestone-e-footer-cli.php
 *
 * Env:
 *   BIOPENTRA_E_FOOTER_POST_ID=3823
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$footer_id = (int) ( getenv( 'BIOPENTRA_E_FOOTER_POST_ID' ) ?: 3823 );

$raw = get_post_meta( $footer_id, '_elementor_data', true );
if ( empty( $raw ) ) {
	echo "ERROR: No _elementor_data for footer {$footer_id}\n";
	exit( 1 );
}

$data = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
if ( ! is_array( $data ) ) {
	echo "ERROR: could not decode Elementor JSON for {$footer_id}\n";
	exit( 1 );
}

/**
 * Mobile spacing map: element id → settings patch.
 *
 * @return array<string,array<string,mixed>>
 */
function biopentra_e_footer_mobile_patches() {
	return array(
		// Root footer shell — was 48/32 pad + 44 gap.
		'782cf0a' => array(
			'padding_mobile'  => array(
				'unit'     => 'px',
				'top'      => '16',
				'right'    => '12',
				'bottom'   => '16',
				'left'     => '12',
				'isLinked' => false,
			),
			'flex_gap_mobile' => array(
				'column'   => '12',
				'row'      => '12',
				'isLinked' => true,
				'unit'     => 'px',
				'size'     => 12,
			),
		),
		// Brand + columns row — was gap 32.
		'77baf59' => array(
			'flex_gap_mobile' => array(
				'column'   => '12',
				'row'      => '12',
				'isLinked' => true,
				'unit'     => 'px',
				'size'     => 12,
			),
		),
		// Brand column — was gap 24.
		'90e7b9a' => array(
			'flex_gap_mobile' => array(
				'column'   => '8',
				'row'      => '8',
				'isLinked' => true,
				'unit'     => 'px',
				'size'     => 8,
			),
		),
		// Link columns — were gap 18.
		'5a08358' => array(
			'flex_gap_mobile' => array(
				'column'   => '6',
				'row'      => '6',
				'isLinked' => true,
				'unit'     => 'px',
				'size'     => 6,
			),
		),
		'537f779' => array(
			'flex_gap_mobile' => array(
				'column'   => '6',
				'row'      => '6',
				'isLinked' => true,
				'unit'     => 'px',
				'size'     => 6,
			),
		),
		'f352048' => array(
			'flex_gap_mobile' => array(
				'column'   => '8',
				'row'      => '8',
				'isLinked' => true,
				'unit'     => 'px',
				'size'     => 8,
			),
		),
		// Bottom legal/payment bar — was pad 32 / inner 18.
		'cf46e8f' => array(
			'padding_mobile'  => array(
				'unit'     => 'px',
				'top'      => '12',
				'right'    => '0',
				'bottom'   => '12',
				'left'     => '0',
				'isLinked' => false,
			),
			'flex_gap_mobile' => array(
				'column'   => '8',
				'row'      => '8',
				'isLinked' => true,
				'unit'     => 'px',
				'size'     => 8,
			),
		),
		'24736b6' => array(
			'padding_mobile'  => array(
				'unit'     => 'px',
				'top'      => '8',
				'right'    => '0',
				'bottom'   => '8',
				'left'     => '0',
				'isLinked' => false,
			),
			'flex_gap_mobile' => array(
				'column'   => '6',
				'row'      => '6',
				'isLinked' => true,
				'unit'     => 'px',
				'size'     => 6,
			),
		),
	);
}

/**
 * @param array<int,mixed>             $nodes
 * @param array<string,array<string,mixed>> $patches
 * @param int                          $changed
 */
function biopentra_e_apply_footer_patches( array &$nodes, array $patches, &$changed ) {
	foreach ( $nodes as &$node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		$id = (string) ( $node['id'] ?? '' );
		if ( $id && isset( $patches[ $id ] ) ) {
			if ( ! isset( $node['settings'] ) || ! is_array( $node['settings'] ) ) {
				$node['settings'] = array();
			}
			foreach ( $patches[ $id ] as $key => $value ) {
				$prev = $node['settings'][ $key ] ?? null;
				if ( $prev !== $value ) {
					$node['settings'][ $key ] = $value;
					++$changed;
					echo "Footer element {$id}: set {$key}\n";
				}
			}
		}
		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			biopentra_e_apply_footer_patches( $node['elements'], $patches, $changed );
		}
	}
	unset( $node );
}

$patches = biopentra_e_footer_mobile_patches();
$changed = 0;
biopentra_e_apply_footer_patches( $data, $patches, $changed );

if ( $changed > 0 ) {
	$encoded = wp_json_encode( $data );
	if ( false === $encoded ) {
		echo "ERROR: json_encode failed\n";
		exit( 1 );
	}
	update_post_meta( $footer_id, '_elementor_data', wp_slash( $encoded ) );
	delete_post_meta( $footer_id, '_elementor_element_cache' );
	delete_post_meta( $footer_id, '_elementor_css' );
	if ( class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
	echo "Footer {$footer_id}: saved ({$changed} setting writes) + Elementor caches cleared\n";
} else {
	echo "Footer {$footer_id}: already at Milestone E mobile spacing (idempotent)\n";
}

echo "OK\n";
