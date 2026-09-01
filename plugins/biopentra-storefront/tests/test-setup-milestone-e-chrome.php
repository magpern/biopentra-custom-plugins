<?php
/**
 * Standalone test for scripts/setup-milestone-e-chrome-cli.php.
 *
 * No WordPress / PHPUnit required. Stubs the handful of WP functions the CLI
 * uses, then includes the script and asserts behaviour.
 *
 * Usage:
 *   php wp-content/plugins/biopentra-storefront/tests/test-setup-milestone-e-chrome.php
 *
 * Focus: BIOPENTRA_E_SKIP_UMC=1 must
 *   (a) NOT add a universal_multicurrency_switcher widget,
 *   (b) DO add the data-bp-chrome-search control,
 *   (c) not error / exit non-zero,
 *   (d) not touch the umc_settings option.
 *
 * @package Biopentra_Storefront
 */

error_reporting( E_ALL );

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['__test_options']     = array(); // Option store; deliberately has NO 'umc_settings'.
$GLOBALS['__test_option_sets'] = array(); // Log of update_option() keys.
$GLOBALS['__test_post_meta']   = array();

// Fake Elementor header: one root container whose only child is the cart widget.
$GLOBALS['__test_post_meta']['3782']['_elementor_data'] = wp_json_encode_seed(
	array(
		array(
			'id'       => 'root001',
			'elType'   => 'container',
			'settings' => array(),
			'elements' => array(
				array(
					'id'         => 'cart001',
					'elType'     => 'widget',
					'widgetType' => 'woocommerce-menu-cart',
					'settings'   => array(),
					'elements'   => array(),
				),
			),
		),
	)
);

function wp_json_encode_seed( $v ) {
	return json_encode( $v );
}

function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['__test_options'] )
		? $GLOBALS['__test_options'][ $key ]
		: $default;
}

function update_option( $key, $value, $autoload = null ) {
	$GLOBALS['__test_options'][ $key ] = $value;
	$GLOBALS['__test_option_sets'][]   = $key;
	return true;
}

function get_post_meta( $post_id, $key, $single = false ) {
	return $GLOBALS['__test_post_meta'][ (string) $post_id ][ $key ] ?? '';
}

function update_post_meta( $post_id, $key, $value ) {
	$GLOBALS['__test_post_meta'][ (string) $post_id ][ $key ] = $value;
	return true;
}

function delete_post_meta( $post_id, $key ) {
	unset( $GLOBALS['__test_post_meta'][ (string) $post_id ][ $key ] );
	return true;
}

function wp_json_encode( $data, $options = 0, $depth = 512 ) {
	return json_encode( $data, $options, $depth );
}

function wp_slash( $value ) {
	return $value;
}

// ── Run the CLI under test ────────────────────────────────────────────────────
putenv( 'BIOPENTRA_E_SKIP_UMC=1' );
putenv( 'BIOPENTRA_E_HEADER_POST_ID=3782' );
putenv( 'BIOPENTRA_E_SKIP_HEADER' );

$script = dirname( __DIR__ ) . '/scripts/setup-milestone-e-chrome-cli.php';

ob_start();
include $script;
$output = ob_get_clean();

echo "--- CLI output ---\n{$output}\n------------------\n";

// ── Assertions ───────────────────────────────────────────────────────────────
$failures = array();

$final = json_decode( $GLOBALS['__test_post_meta']['3782']['_elementor_data'], true );
$json  = json_encode( $final );

if ( false !== strpos( $json, 'universal_multicurrency_switcher' ) ) {
	$failures[] = '(a) UMC switcher widget WAS inserted despite SKIP_UMC';
}
if ( false === strpos( $output, 'UMC switcher widget skipped (SKIP_UMC)' ) ) {
	$failures[] = '(a) expected skip message not printed';
}

if ( false === strpos( $json, 'data-bp-chrome-search' ) ) {
	$failures[] = '(b) data-bp-chrome-search control was NOT inserted';
}

if ( false === strpos( $output, 'OK' ) ) {
	$failures[] = '(c) CLI did not print terminal OK';
}

if ( in_array( 'umc_settings', $GLOBALS['__test_option_sets'], true ) ) {
	$failures[] = '(d) umc_settings option was written';
}
if ( array_key_exists( 'umc_settings', $GLOBALS['__test_options'] ) ) {
	$failures[] = '(d) umc_settings option exists in store';
}

// Idempotent: only the search widget was added → exactly one new child widget.
$cart_siblings = $final[0]['elements'];
$widget_types  = array_map(
	static function ( $n ) {
		return ( $n['widgetType'] ?? '' );
	},
	$cart_siblings
);
$html_count = count( array_filter( $widget_types, static function ( $t ) {
	return 'html' === $t;
} ) );
if ( 1 !== $html_count ) {
	$failures[] = 'expected exactly one html widget sibling, got: ' . implode( ',', $widget_types );
}
if ( in_array( 'shortcode', $widget_types, true ) ) {
	$failures[] = 'shortcode widget present among siblings';
}

if ( $failures ) {
	echo "\nFAIL (" . count( $failures ) . "):\n - " . implode( "\n - ", $failures ) . "\n";
	exit( 1 );
}

echo "\nPASS: 6 assertions (a,b,c,d + idempotency + no-shortcode-sibling)\n";
