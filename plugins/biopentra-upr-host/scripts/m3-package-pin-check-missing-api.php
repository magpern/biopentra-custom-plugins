<?php
/**
 * Child: pin must deny when InvitationAuthorisation is absent.
 *
 * @package Biopentra_Upr_Host
 */

declare( strict_types=1 );

namespace UniversalProductReviews\Submission {
	class NativePdpForm {}
	class NativeSubmissionGuard {}
}

namespace {
	define( 'ABSPATH', '/tmp/' );

	require_once dirname( __DIR__ ) . '/includes/class-upr-pin.php';

	$dir = sys_get_temp_dir() . '/upr-pin-missing-api-' . bin2hex( random_bytes( 4 ) );
	mkdir( $dir, 0700, true );
	define( 'UPR_PLUGIN_DIR', $dir );
	define( 'UPR_VERSION', \Biopentra_Upr_Host_Upr_Pin::REQUIRED_VERSION );
	file_put_contents(
		$dir . '/' . \Biopentra_Upr_Host_Upr_Pin::PACKAGE_META_BASENAME,
		json_encode(
			array(
				'schema'  => \Biopentra_Upr_Host_Upr_Pin::PACKAGE_META_SCHEMA,
				'version' => \Biopentra_Upr_Host_Upr_Pin::REQUIRED_VERSION,
				'tag'     => \Biopentra_Upr_Host_Upr_Pin::REQUIRED_TAG,
				'commit'  => \Biopentra_Upr_Host_Upr_Pin::REQUIRED_COMMIT,
			)
		) . "\n"
	);

	$got = \Biopentra_Upr_Host_Upr_Pin::verify();
	if ( $got['ok'] ) {
		fwrite( STDERR, "FAIL expected deny without InvitationAuthorisation\n" );
		exit( 1 );
	}
	$blob = implode( ' ', $got['errors'] );
	if ( false === strpos( $blob, 'InvitationAuthorisation' ) ) {
		fwrite( STDERR, 'FAIL expected InvitationAuthorisation error; got ' . $blob . PHP_EOL );
		exit( 1 );
	}
	exit( 0 );
}
