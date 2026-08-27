<?php
/**
 * Offline: verify host pin against an extracted UPR tree (UPR_PLUGIN_DIR).
 *
 * Env:
 *   UPR_PLUGIN_DIR — absolute path to extracted universal-product-reviews
 *   EXPECT_OK — "1" expect accept, "0" expect deny (default 1)
 *
 * @package Biopentra_Upr_Host
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
	define( 'UPR_VERSION', \Biopentra_Upr_Host_Upr_Pin::REQUIRED_VERSION );

	$expect_ok = ( getenv( 'EXPECT_OK' ) === false || getenv( 'EXPECT_OK' ) === '' )
		? true
		: ( getenv( 'EXPECT_OK' ) === '1' );

	$got = \Biopentra_Upr_Host_Upr_Pin::verify();
	if ( (bool) $got['ok'] !== $expect_ok ) {
		fwrite( STDERR, 'FAIL expect_ok=' . ( $expect_ok ? '1' : '0' ) . ' got ' . json_encode( $got ) . PHP_EOL );
		exit( 1 );
	}
	echo $expect_ok ? "OK pin accepts\n" : "OK pin denies\n";
	exit( 0 );
}
