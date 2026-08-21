<?php
/**
 * M4 — Compact Featured / Newest / Popular section spacing (idempotent).
 *
 * Zeroes Elementor section top padding (CSS owns single-layer inner padding).
 * Reduces bottom padding so CTA → next heading is not a dead zone.
 * Does not modify M3 or product cards.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$home_id = (int) get_option( 'page_on_front' );
if ( ! $home_id ) {
	echo "ERROR: no page_on_front\n";
	return;
}

$raw  = get_post_meta( $home_id, '_elementor_data', true );
$data = is_string( $raw ) ? json_decode( $raw, true ) : null;
if ( ! is_array( $data ) ) {
	echo "ERROR: no elementor data\n";
	return;
}

$targets = array( 'be24b65', 'a3853b0', '17b27f7' );
$found   = array();

foreach ( $data as &$section ) {
	$id = $section['id'] ?? '';
	if ( ! in_array( $id, $targets, true ) ) {
		continue;
	}
	$found[] = $id;

	// Section shell: no top pad (inner owns it). Modest bottom for Elementor fallback.
	$section['settings']['padding'] = array(
		'unit'     => 'px',
		'top'      => '0',
		'right'    => '24',
		'bottom'   => '0',
		'left'     => '24',
		'isLinked' => false,
	);
	$section['settings']['padding_tablet'] = array(
		'unit'     => 'px',
		'top'      => '0',
		'right'    => '20',
		'bottom'   => '0',
		'left'     => '20',
		'isLinked' => false,
	);
	$section['settings']['padding_mobile'] = array(
		'unit'     => 'px',
		'top'      => '0',
		'right'    => '16',
		'bottom'   => '0',
		'left'     => '16',
		'isLinked' => false,
	);
	$section['settings']['flex_gap'] = array(
		'column'   => '20',
		'row'      => '20',
		'isLinked' => true,
		'unit'     => 'px',
		'size'     => 20,
	);
	$section['settings']['flex_gap_mobile'] = array(
		'column'   => '18',
		'row'      => '18',
		'isLinked' => true,
		'unit'     => 'px',
		'size'     => 18,
	);
	$section['settings']['flex_gap_tablet'] = array(
		'column'   => '22',
		'row'      => '22',
		'isLinked' => true,
		'unit'     => 'px',
		'size'     => 22,
	);
}
unset( $section );

if ( count( $found ) !== 3 ) {
	echo 'ERROR: expected 3 product sections, found ' . implode( ',', $found ) . "\n";
	return;
}

update_post_meta( $home_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
delete_post_meta( $home_id, '_elementor_css' );
delete_post_meta( $home_id, '_elementor_element_cache' );
if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}
if ( function_exists( 'wp_cache_flush' ) ) {
	wp_cache_flush();
}

echo "M4 product-stack spacing patch applied on page {$home_id}\n";
echo '  sections=' . implode( ',', $found ) . "\n";
echo "  section padding top/bottom=0; CSS owns inner rhythm; flex_gap mobile=18\n";
