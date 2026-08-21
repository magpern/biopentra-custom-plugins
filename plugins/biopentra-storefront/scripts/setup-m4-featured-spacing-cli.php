<?php
/**
 * M4 — Compact Featured section top spacing (idempotent).
 *
 * Tightens Elementor padding on homepage Featured container be24b65 only.
 * Does not modify M3 category rail widgets/classes.
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

$found = false;
foreach ( $data as &$section ) {
	if ( ( $section['id'] ?? '' ) !== 'be24b65' ) {
		continue;
	}
	$found = true;
	$section['settings']['padding']         = array(
		'unit'     => 'px',
		'top'      => '0',
		'right'    => '24',
		'bottom'   => '78',
		'left'     => '24',
		'isLinked' => false,
	);
	$section['settings']['padding_tablet']  = array(
		'unit'     => 'px',
		'top'      => '0',
		'right'    => '20',
		'bottom'   => '66',
		'left'     => '20',
		'isLinked' => false,
	);
	$section['settings']['padding_mobile']  = array(
		'unit'     => 'px',
		'top'      => '0',
		'right'    => '16',
		'bottom'   => '54',
		'left'     => '16',
		'isLinked' => false,
	);
	/* Boxed containers apply content padding on the inner — keep top here only. */
	$section['settings']['padding']['top']        = '0';
	$section['settings']['padding_tablet']['top'] = '0';
	$section['settings']['padding_mobile']['top'] = '0';
	$section['settings']['flex_gap']        = array(
		'column'   => '22',
		'row'      => '22',
		'isLinked' => true,
		'unit'     => 'px',
		'size'     => 22,
	);
	$section['settings']['flex_gap_mobile'] = array(
		'column'   => '20',
		'row'      => '20',
		'isLinked' => true,
		'unit'     => 'px',
		'size'     => 20,
	);
	break;
}
unset( $section );

if ( ! $found ) {
	echo "ERROR: Featured section be24b65 not found\n";
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

echo "M4 Featured spacing patch applied on page {$home_id}\n";
echo "  section padding-top=0 (all breakpoints); CSS owns single-layer inner top pad\n";
echo "  flex_gap mobile=20 desktop=22\n";
