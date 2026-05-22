<?php
/**
 * CLI test for stock level resolution (run via wp eval-file).
 *
 * Usage: ./wp eval-file wp-content/plugins/biopentra-storefront/modules/product-stock-display/tests/test-stock-levels.php
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! class_exists( 'Biopentra_Storefront_Product_Stock_Display_Module' ) ) {
	echo "ERROR: Product stock display module not loaded.\n";
	exit( 1 );
}

update_option(
	Biopentra_Storefront_Product_Stock_Display_Settings::OPTION_KEY,
	array_merge(
		Biopentra_Storefront_Product_Stock_Display_Settings::defaults(),
		array( 'enabled' => 'yes' )
	)
);

/**
 * @param WC_Product $product Product.
 * @return string
 */
function bp_stock_test_label( WC_Product $product ) {
	$availability = apply_filters( 'woocommerce_get_availability', $product->get_availability(), $product );
	return wp_strip_all_tags( $availability['availability'] );
}

$product = new WC_Product_Simple();
$product->set_manage_stock( true );

$cases = array(
	0  => 'Out of stock',
	1  => 'Only 1 left',
	3  => 'Only 3 left',
	4  => 'Low stock',
	9  => 'Low stock',
	10 => 'In stock',
);

$failed = 0;
foreach ( $cases as $qty => $expected ) {
	$product->set_stock_quantity( $qty );
	$product->set_stock_status( $qty > 0 ? 'instock' : 'outofstock' );
	$label = bp_stock_test_label( $product );
	if ( $label !== $expected ) {
		echo "FAIL qty={$qty}: expected \"{$expected}\", got \"{$label}\"\n";
		++$failed;
	} else {
		echo "OK   qty={$qty}: {$label}\n";
	}
}

$product->set_manage_stock( false );
$product->set_stock_status( 'instock' );
$label = bp_stock_test_label( $product );
if ( 'In stock' !== $label ) {
	echo "FAIL unknown qty: expected \"In stock\", got \"{$label}\"\n";
	++$failed;
} else {
	echo "OK   unknown qty (no manage stock): {$label}\n";
}

if ( $failed > 0 ) {
	echo "\n{$failed} case(s) failed.\n";
	exit( 1 );
}

echo "\nAll stock display cases passed.\n";
