<?php
/**
 * MOTION-1 — capture WordPress printed head/footer for the feature-branch
 * motion enqueue (not static source inspection).
 *
 * Loads includes/motion-assets.php from this file's plugin tree (extra
 * read-only bind on disposable wpcli). Does not switch the served checkout.
 *
 * Run via scripts/run-motion-1-acceptance.sh (enqueue step).
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$storefront_root = dirname( __DIR__ );
$motion_file     = $storefront_root . '/includes/motion-assets.php';

if ( ! is_readable( $motion_file ) ) {
	fwrite( STDERR, "ERROR: missing {$motion_file}\n" );
	exit( 1 );
}

if ( ! function_exists( 'biopentra_storefront_motion_enqueue_assets' ) ) {
	require_once $motion_file;
}

$GLOBALS['biopentra_motion_verify_failures'] = array();

/**
 * @param string $msg Failure line.
 */
function biopentra_motion_verify_fail( $msg ) {
	$GLOBALS['biopentra_motion_verify_failures'][] = $msg;
	echo "FAIL  {$msg}\n";
}

/**
 * @param string $msg Pass line.
 */
function biopentra_motion_verify_pass( $msg ) {
	echo "PASS  {$msg}\n";
}

/**
 * Reset printed-script state so a later surface can print again.
 */
function biopentra_motion_verify_reset_print_state() {
	global $wp_scripts, $wp_styles;

	if ( $wp_scripts instanceof WP_Scripts ) {
		$wp_scripts->done  = array();
		$wp_scripts->queue = array();
		$wp_scripts->to_do = array();
		$wp_scripts->remove( 'biopentra-motion-gate' );
		$wp_scripts->remove( 'biopentra-motion' );
	}
	if ( $wp_styles instanceof WP_Styles ) {
		$wp_styles->done  = array();
		$wp_styles->queue = array();
		$wp_styles->to_do = array();
		$wp_styles->remove( 'biopentra-motion' );
	}
}

/**
 * Point the main query at a page so is_page() / Woo conditionals resolve.
 *
 * @param int $page_id Page ID.
 */
function biopentra_motion_verify_set_page_query( $page_id ) {
	$page_id = (int) $page_id;
	$query   = new WP_Query(
		array(
			'page_id'     => $page_id,
			'post_status' => 'publish',
		)
	);
	$GLOBALS['wp_the_query'] = $query;
	$GLOBALS['wp_query']     = $query;
	$queried                 = $query->get_queried_object();
	if ( $queried instanceof WP_Post ) {
		$GLOBALS['post'] = $queried;
		setup_postdata( $queried );
	}
	if ( ! did_action( 'wp' ) ) {
		do_action( 'wp' );
	}
	biopentra_motion_verify_reset_wc_page_cache();
}

/**
 * WooCommerce caches is_cart()/is_checkout() for the request.
 */
function biopentra_motion_verify_reset_wc_page_cache() {
	if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils' ) ) {
		return;
	}
	$ref = new ReflectionClass( '\Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils' );
	foreach ( array( 'is_cart_page', 'is_checkout_page' ) as $prop ) {
		if ( ! $ref->hasProperty( $prop ) ) {
			continue;
		}
		$property = $ref->getProperty( $prop );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}
}

/**
 * @param string $handle Script handle.
 * @return int|null Group 0=head, 1=footer.
 */
function biopentra_motion_verify_script_group( $handle ) {
	$scripts = wp_scripts();
	if ( ! isset( $scripts->registered[ $handle ] ) ) {
		return null;
	}
	$obj = $scripts->registered[ $handle ];
	if ( isset( $obj->extra['group'] ) ) {
		return (int) $obj->extra['group'];
	}
	return 0;
}

/**
 * @param string $handle Script handle.
 * @return string Concatenated extra after.
 */
function biopentra_motion_verify_inline_after( $handle ) {
	$data = wp_scripts()->get_data( $handle, 'after' );
	if ( is_array( $data ) ) {
		return implode( "\n", $data );
	}
	return is_string( $data ) ? $data : '';
}

/**
 * Capture wp_head + wp_footer HTML after enqueue.
 *
 * @return array{head:string,footer:string}
 */
function biopentra_motion_verify_capture() {
	biopentra_motion_verify_reset_print_state();
	biopentra_storefront_motion_enqueue_assets();

	ob_start();
	wp_head();
	$head = (string) ob_get_clean();

	ob_start();
	wp_footer();
	$footer = (string) ob_get_clean();

	return array(
		'head'   => $head,
		'footer' => $footer,
	);
}

/**
 * @param string $haystack Printed HTML.
 * @param string $needle   Substring.
 */
function biopentra_motion_verify_contains( $haystack, $needle ) {
	return false !== strpos( $haystack, $needle );
}

echo "MOTION-1 enqueue output capture\n";
echo 'motion_assets_file: ' . $motion_file . "\n";
echo 'served_storefront_path: ' . ( defined( 'BIOPENTRA_STOREFRONT_PATH' ) ? BIOPENTRA_STOREFRONT_PATH : '?' ) . "\n";
echo 'feature_css_readable: ' . ( is_readable( $storefront_root . '/assets/css/bp-motion.css' ) ? 'yes' : 'no' ) . "\n";
echo 'show_on_front: ' . (string) get_option( 'show_on_front' ) . "\n";
echo 'page_on_front: ' . (string) get_option( 'page_on_front' ) . "\n";
echo "\n";

$front_id    = (int) get_option( 'page_on_front' );
$cart_id     = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'cart' ) : 0;
$checkout_id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'checkout' ) : 0;
$shop_id     = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'shop' ) : 0;

if ( $front_id < 1 ) {
	biopentra_motion_verify_fail( 'no static front page id (page_on_front)' );
}

/* --- Front page: assets MUST print with head/footer contract. --- */
echo "=== surface: front_page ===\n";
biopentra_motion_verify_set_page_query( $front_id );
echo 'is_front_page=' . ( is_front_page() ? '1' : '0' );
echo ' is_admin=' . ( is_admin() ? '1' : '0' );
echo ' is_cart=' . ( function_exists( 'is_cart' ) && is_cart() ? '1' : '0' );
echo ' is_checkout=' . ( function_exists( 'is_checkout' ) && is_checkout() ? '1' : '0' ) . "\n";
echo 'should_load=' . ( biopentra_storefront_motion_should_load() ? '1' : '0' ) . "\n";

if ( ! is_front_page() ) {
	biopentra_motion_verify_fail( 'front_page query did not make is_front_page() true' );
}
if ( ! biopentra_storefront_motion_should_load() ) {
	biopentra_motion_verify_fail( 'should_load false on front page' );
}

$front = biopentra_motion_verify_capture();

$gate_enqueued  = wp_script_is( 'biopentra-motion-gate', 'enqueued' );
$ctrl_enqueued  = wp_script_is( 'biopentra-motion', 'enqueued' );
$css_enqueued   = wp_style_is( 'biopentra-motion', 'enqueued' );
$gate_src       = isset( wp_scripts()->registered['biopentra-motion-gate'] ) ? wp_scripts()->registered['biopentra-motion-gate']->src : 'UNREGISTERED';
$ctrl_src       = isset( wp_scripts()->registered['biopentra-motion'] ) ? (string) wp_scripts()->registered['biopentra-motion']->src : 'UNREGISTERED';
$gate_group     = biopentra_motion_verify_script_group( 'biopentra-motion-gate' );
$ctrl_group     = biopentra_motion_verify_script_group( 'biopentra-motion' );
$css_deps       = isset( wp_styles()->registered['biopentra-motion'] ) ? wp_styles()->registered['biopentra-motion']->deps : array();
$inline         = biopentra_motion_verify_inline_after( 'biopentra-motion-gate' );

echo 'enqueued gate=' . ( $gate_enqueued ? '1' : '0' ) . ' controller=' . ( $ctrl_enqueued ? '1' : '0' ) . ' css=' . ( $css_enqueued ? '1' : '0' ) . "\n";
echo 'gate_src=' . var_export( $gate_src, true ) . " gate_group={$gate_group}\n";
echo 'controller_src=' . $ctrl_src . " controller_group={$ctrl_group}\n";
echo 'css_deps=' . implode( ',', $css_deps ) . "\n";

if ( ! $gate_enqueued ) {
	biopentra_motion_verify_fail( 'front: biopentra-motion-gate not enqueued' );
} else {
	biopentra_motion_verify_pass( 'front: biopentra-motion-gate enqueued' );
}
if ( false !== $gate_src && '' !== $gate_src && null !== $gate_src ) {
	biopentra_motion_verify_fail( 'front: gate src must be src-less (false/empty), got ' . var_export( $gate_src, true ) );
} else {
	biopentra_motion_verify_pass( 'front: gate is src-less' );
}
if ( 0 !== $gate_group ) {
	biopentra_motion_verify_fail( "front: gate group must be 0 (head), got {$gate_group}" );
} else {
	biopentra_motion_verify_pass( 'front: gate group 0 (head)' );
}
if ( ! biopentra_motion_verify_contains( $inline, 'classList.add("bp-motion")' ) ) {
	biopentra_motion_verify_fail( 'front: gate inline extra missing bp-motion class add' );
} else {
	biopentra_motion_verify_pass( 'front: gate inline extra attached to gate handle' );
}
if ( ! biopentra_motion_verify_contains( $front['head'], 'biopentra-motion-gate' ) ) {
	biopentra_motion_verify_fail( 'front: wp_head HTML missing biopentra-motion-gate' );
} else {
	biopentra_motion_verify_pass( 'front: wp_head prints biopentra-motion-gate' );
}
if ( ! biopentra_motion_verify_contains( $front['head'], 'classList.add("bp-motion")' ) ) {
	biopentra_motion_verify_fail( 'front: wp_head HTML missing gate inline body' );
} else {
	biopentra_motion_verify_pass( 'front: wp_head prints gate inline script body' );
}
if ( biopentra_motion_verify_contains( $front['head'], 'bp-motion.js' ) ) {
	biopentra_motion_verify_fail( 'front: controller bp-motion.js printed in wp_head' );
} else {
	biopentra_motion_verify_pass( 'front: controller absent from wp_head' );
}
if ( ! $ctrl_enqueued ) {
	biopentra_motion_verify_fail( 'front: biopentra-motion controller not enqueued' );
} else {
	biopentra_motion_verify_pass( 'front: biopentra-motion controller enqueued' );
}
if ( 1 !== $ctrl_group ) {
	biopentra_motion_verify_fail( "front: controller group must be 1 (footer), got {$ctrl_group}" );
} else {
	biopentra_motion_verify_pass( 'front: controller group 1 (footer)' );
}
if ( ! biopentra_motion_verify_contains( $ctrl_src, 'bp-motion.js' ) ) {
	biopentra_motion_verify_fail( 'front: controller src is not bp-motion.js' );
} else {
	biopentra_motion_verify_pass( 'front: controller src is bp-motion.js' );
}
if ( ! biopentra_motion_verify_contains( $front['footer'], 'bp-motion.js' ) ) {
	biopentra_motion_verify_fail( 'front: wp_footer HTML missing bp-motion.js' );
} else {
	biopentra_motion_verify_pass( 'front: wp_footer prints bp-motion.js' );
}
if ( biopentra_motion_verify_contains( $front['footer'], 'biopentra-motion-gate' ) ) {
	biopentra_motion_verify_fail( 'front: gate handle printed in wp_footer' );
} else {
	biopentra_motion_verify_pass( 'front: gate absent from wp_footer' );
}
if ( ! $css_enqueued ) {
	biopentra_motion_verify_fail( 'front: biopentra-motion CSS not enqueued' );
} else {
	biopentra_motion_verify_pass( 'front: biopentra-motion CSS enqueued' );
}
if ( ! in_array( 'biopentra-bp-tokens', $css_deps, true ) ) {
	biopentra_motion_verify_fail( 'front: CSS deps missing biopentra-bp-tokens' );
} else {
	biopentra_motion_verify_pass( 'front: CSS depends on biopentra-bp-tokens' );
}
if ( ! biopentra_motion_verify_contains( $front['head'], 'bp-motion.css' ) ) {
	biopentra_motion_verify_fail( 'front: wp_head HTML missing bp-motion.css' );
} else {
	biopentra_motion_verify_pass( 'front: wp_head prints bp-motion.css' );
}
if ( ! biopentra_motion_verify_contains( $front['head'], 'data-bp-motion-state="pending"' ) ) {
	biopentra_motion_verify_fail( 'front: wp_head missing noscript pending-visible rule' );
} else {
	biopentra_motion_verify_pass( 'front: wp_head prints noscript pending-visible rule' );
}

/**
 * Positive surface: MOTION-1 head/footer contract must print.
 *
 * @param string $label   Surface name.
 * @param array  $printed {head,footer}.
 */
function biopentra_motion_verify_present( $label, $printed ) {
	$gate_enqueued = wp_script_is( 'biopentra-motion-gate', 'enqueued' );
	$ctrl_enqueued = wp_script_is( 'biopentra-motion', 'enqueued' );
	$css_enqueued  = wp_style_is( 'biopentra-motion', 'enqueued' );
	$gate_src      = isset( wp_scripts()->registered['biopentra-motion-gate'] ) ? wp_scripts()->registered['biopentra-motion-gate']->src : 'UNREGISTERED';
	$ctrl_src      = isset( wp_scripts()->registered['biopentra-motion'] ) ? (string) wp_scripts()->registered['biopentra-motion']->src : 'UNREGISTERED';
	$gate_group    = biopentra_motion_verify_script_group( 'biopentra-motion-gate' );
	$ctrl_group    = biopentra_motion_verify_script_group( 'biopentra-motion' );
	$css_deps      = isset( wp_styles()->registered['biopentra-motion'] ) ? wp_styles()->registered['biopentra-motion']->deps : array();
	$inline        = biopentra_motion_verify_inline_after( 'biopentra-motion-gate' );

	if ( ! $gate_enqueued ) {
		biopentra_motion_verify_fail( "{$label}: biopentra-motion-gate not enqueued" );
	} else {
		biopentra_motion_verify_pass( "{$label}: biopentra-motion-gate enqueued" );
	}
	if ( false !== $gate_src && '' !== $gate_src && null !== $gate_src ) {
		biopentra_motion_verify_fail( "{$label}: gate src must be src-less, got " . var_export( $gate_src, true ) );
	} else {
		biopentra_motion_verify_pass( "{$label}: gate is src-less" );
	}
	if ( 0 !== $gate_group ) {
		biopentra_motion_verify_fail( "{$label}: gate group must be 0 (head), got {$gate_group}" );
	} else {
		biopentra_motion_verify_pass( "{$label}: gate group 0 (head)" );
	}
	if ( ! biopentra_motion_verify_contains( $inline, 'classList.add("bp-motion")' ) ) {
		biopentra_motion_verify_fail( "{$label}: gate inline extra missing bp-motion class add" );
	} else {
		biopentra_motion_verify_pass( "{$label}: gate inline extra attached to gate handle" );
	}
	if ( ! biopentra_motion_verify_contains( $printed['head'], 'biopentra-motion-gate' ) ) {
		biopentra_motion_verify_fail( "{$label}: wp_head HTML missing biopentra-motion-gate" );
	} else {
		biopentra_motion_verify_pass( "{$label}: wp_head prints biopentra-motion-gate" );
	}
	if ( biopentra_motion_verify_contains( $printed['head'], 'bp-motion.js' ) ) {
		biopentra_motion_verify_fail( "{$label}: controller bp-motion.js printed in wp_head" );
	} else {
		biopentra_motion_verify_pass( "{$label}: controller absent from wp_head" );
	}
	if ( ! $ctrl_enqueued || 1 !== $ctrl_group || ! biopentra_motion_verify_contains( $ctrl_src, 'bp-motion.js' ) ) {
		biopentra_motion_verify_fail( "{$label}: controller footer enqueue missing" );
	} else {
		biopentra_motion_verify_pass( "{$label}: controller group 1 src bp-motion.js" );
	}
	if ( ! biopentra_motion_verify_contains( $printed['footer'], 'bp-motion.js' ) ) {
		biopentra_motion_verify_fail( "{$label}: wp_footer HTML missing bp-motion.js" );
	} else {
		biopentra_motion_verify_pass( "{$label}: wp_footer prints bp-motion.js" );
	}
	if ( ! $css_enqueued || ! in_array( 'biopentra-bp-tokens', $css_deps, true ) ) {
		biopentra_motion_verify_fail( "{$label}: CSS missing or missing biopentra-bp-tokens dep" );
	} else {
		biopentra_motion_verify_pass( "{$label}: CSS depends on biopentra-bp-tokens" );
	}
	if ( ! biopentra_motion_verify_contains( $printed['head'], 'bp-motion.css' ) ) {
		biopentra_motion_verify_fail( "{$label}: wp_head HTML missing bp-motion.css" );
	} else {
		biopentra_motion_verify_pass( "{$label}: wp_head prints bp-motion.css" );
	}
}

/**
 * Negative surface: MOTION-1 must not print.
 *
 * @param string $label Surface name.
 * @param array  $printed {head,footer}.
 */
function biopentra_motion_verify_absent( $label, $printed ) {
	$blobs = $printed['head'] . $printed['footer'];
	$hits  = array(
		'biopentra-motion-gate' => biopentra_motion_verify_contains( $blobs, 'biopentra-motion-gate' ),
		'bp-motion.js'          => biopentra_motion_verify_contains( $blobs, 'bp-motion.js' ),
		'bp-motion.css'         => biopentra_motion_verify_contains( $blobs, 'bp-motion.css' ),
	);
	$enqueued = wp_script_is( 'biopentra-motion-gate', 'enqueued' ) || wp_script_is( 'biopentra-motion', 'enqueued' ) || wp_style_is( 'biopentra-motion', 'enqueued' );
	if ( $enqueued || in_array( true, $hits, true ) ) {
		biopentra_motion_verify_fail( $label . ': MOTION-1 assets present (enqueued=' . ( $enqueued ? '1' : '0' ) . ' hits=' . json_encode( $hits ) . ')' );
	} else {
		biopentra_motion_verify_pass( $label . ': MOTION-1 assets absent from enqueue and printed HTML' );
	}
}

/* --- Cart --- */
echo "\n=== surface: cart ===\n";
if ( $cart_id < 1 ) {
	biopentra_motion_verify_fail( 'cart page id missing' );
} else {
	biopentra_motion_verify_set_page_query( $cart_id );
	echo 'is_front_page=' . ( is_front_page() ? '1' : '0' );
	echo ' is_cart=' . ( function_exists( 'is_cart' ) && is_cart() ? '1' : '0' ) . "\n";
	echo 'should_load=' . ( biopentra_storefront_motion_should_load() ? '1' : '0' ) . "\n";
	if ( function_exists( 'is_cart' ) && ! is_cart() ) {
		biopentra_motion_verify_fail( 'cart query did not make is_cart() true' );
	}
	$printed = biopentra_motion_verify_capture();
	biopentra_motion_verify_absent( 'cart', $printed );
}

/* --- Checkout --- */
echo "\n=== surface: checkout ===\n";
if ( $checkout_id < 1 ) {
	biopentra_motion_verify_fail( 'checkout page id missing' );
} else {
	biopentra_motion_verify_set_page_query( $checkout_id );
	echo 'is_front_page=' . ( is_front_page() ? '1' : '0' );
	echo ' is_checkout=' . ( function_exists( 'is_checkout' ) && is_checkout() ? '1' : '0' ) . "\n";
	echo 'should_load=' . ( biopentra_storefront_motion_should_load() ? '1' : '0' ) . "\n";
	if ( function_exists( 'is_checkout' ) && ! is_checkout() ) {
		biopentra_motion_verify_fail( 'checkout query did not make is_checkout() true' );
	}
	$printed = biopentra_motion_verify_capture();
	biopentra_motion_verify_absent( 'checkout', $printed );
}

/* --- Shop (2026-08-31: assets MUST print; same head/footer contract). --- */
echo "\n=== surface: shop ===\n";
if ( $shop_id < 1 ) {
	biopentra_motion_verify_fail( 'shop page id missing' );
} else {
	biopentra_motion_verify_set_page_query( $shop_id );
	echo 'is_front_page=' . ( is_front_page() ? '1' : '0' );
	echo ' is_shop=' . ( function_exists( 'is_shop' ) && is_shop() ? '1' : '0' ) . "\n";
	echo 'should_load=' . ( biopentra_storefront_motion_should_load() ? '1' : '0' ) . "\n";
	if ( function_exists( 'is_shop' ) && ! is_shop() ) {
		biopentra_motion_verify_fail( 'shop query did not make is_shop() true' );
	}
	if ( ! biopentra_storefront_motion_should_load() ) {
		biopentra_motion_verify_fail( 'should_load false on shop' );
	}
	$printed = biopentra_motion_verify_capture();
	biopentra_motion_verify_present( 'shop', $printed );
}

/* --- Admin --- */
echo "\n=== surface: admin ===\n";
$GLOBALS['current_screen'] = new class() {
	/**
	 * @param string $cap Unused.
	 */
	public function in_admin( $cap = '' ) {
		return true;
	}
};
echo 'is_admin=' . ( is_admin() ? '1' : '0' ) . ' is_front_page=' . ( is_front_page() ? '1' : '0' ) . "\n";
echo 'should_load=' . ( biopentra_storefront_motion_should_load() ? '1' : '0' ) . "\n";
if ( ! is_admin() ) {
	biopentra_motion_verify_fail( 'admin stub did not make is_admin() true' );
}
$printed = biopentra_motion_verify_capture();
biopentra_motion_verify_absent( 'admin', $printed );
unset( $GLOBALS['current_screen'] );

echo "\n";
$failures = $GLOBALS['biopentra_motion_verify_failures'];
if ( $failures ) {
	echo 'RESULT: FAIL (' . count( $failures ) . ")\n";
	foreach ( $failures as $line ) {
		echo " - {$line}\n";
	}
	exit( 1 );
}

echo "RESULT: PASS\n";
exit( 0 );
