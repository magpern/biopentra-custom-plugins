<?php
/**
 * Offline: verify upr-host-adapter pin against an extracted UPR tree (UPR_PLUGIN_DIR).
 *
 * Env:
 *   UPR_PLUGIN_DIR — absolute path to extracted universal-product-reviews
 *   HOST_INCLUDES — path to host includes/ (default /host/includes)
 *   EXPECT_OK — "1" expect accept, "0" expect deny (default 1)
 *
 * @package Biopentra_Custom_Plugins
 */

declare( strict_types=1 );

namespace UniversalProductReviews\Submission {
	class NativePdpForm {}
	class NativeSubmissionGuard {}
}

namespace UniversalProductReviews\Invitations {
	class InvitationAuthorisation {}
}

namespace {
	define( 'ABSPATH', '/tmp/' );

	$host_includes = getenv( 'HOST_INCLUDES' ) ?: '/host/includes';
	require_once rtrim( $host_includes, '/' ) . '/class-upr-pin.php';

	$dir = getenv( 'UPR_PLUGIN_DIR' );
	if ( ! is_string( $dir ) || '' === $dir ) {
		fwrite( STDERR, "UPR_PLUGIN_DIR required\n" );
		exit( 2 );
	}
	define( 'UPR_PLUGIN_DIR', $dir );
	define( 'UPR_VERSION', \Upr_Host_Adapter_Upr_Pin::REQUIRED_VERSION );

	$expect_ok = ( getenv( 'EXPECT_OK' ) === false || getenv( 'EXPECT_OK' ) === '' )
		? true
		: ( getenv( 'EXPECT_OK' ) === '1' );

	$got = \Upr_Host_Adapter_Upr_Pin::verify();
	if ( (bool) $got['ok'] !== $expect_ok ) {
		$encoded = json_encode( $got );
		fwrite( STDERR, 'FAIL expect_ok=' . ( $expect_ok ? '1' : '0' ) . ' got ' . ( is_string( $encoded ) ? $encoded : '{}' ) . PHP_EOL );
		exit( 1 );
	}
	echo $expect_ok ? "OK pin accepts\n" : "OK pin denies\n";
	exit( 0 );
}
