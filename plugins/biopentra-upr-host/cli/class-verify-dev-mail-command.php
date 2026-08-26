<?php
/**
 * WP-CLI: fail-closed DEV mail verification.
 *
 * @package Biopentra_Upr_Host
 */

defined( 'ABSPATH' ) || exit;

use UniversalProductReviews\Email\EmailMessage;
use UniversalProductReviews\Email\LoggingMailTransport;
use UniversalProductReviews\Email\MailTransportFactory;

final class Biopentra_Upr_Host_Verify_Dev_Mail_Command {

	/**
	 * Verify DEV environment and that UPR invitation mail uses LoggingMailTransport.
	 *
	 * ## EXAMPLES
	 *
	 *     wp biopentra-upr-host verify-dev-mail
	 *
	 * @when after_wp_load
	 *
	 * @param array<int, string>    $args Positional.
	 * @param array<string, string> $assoc Assoc.
	 */
	public function __invoke( $args, $assoc ): void {
		unset( $args, $assoc );

		$env = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		if ( 'development' !== $env ) {
			WP_CLI::error(
				sprintf(
					'Refusing to run invitation-triggering validation: wp_get_environment_type() is "%s" (required: development).',
					$env
				)
			);
		}

		if ( ! class_exists( MailTransportFactory::class ) ) {
			WP_CLI::error( 'UPR MailTransportFactory not available — is universal-product-reviews active?' );
		}

		$transport = MailTransportFactory::make();
		if ( ! $transport instanceof LoggingMailTransport ) {
			WP_CLI::error(
				sprintf(
					'Expected LoggingMailTransport on development; got %s. Bridge/SMTP must not handle DEV invitation mail.',
					is_object( $transport ) ? get_class( $transport ) : gettype( $transport )
				)
			);
		}

		$before = count( LoggingMailTransport::$sent );
		$message = new EmailMessage(
			'dev-verify-' . wp_generate_uuid4() . '@example.invalid',
			'[UPR host] DEV mail verification (logging only)',
			'Controlled DEV verification — must not leave the logging transport.',
			'biopentra-upr-host-verify-' . time()
		);

		$result = $transport->send( $message );
		$after  = count( LoggingMailTransport::$sent );

		if ( ! $result->success || $after <= $before ) {
			WP_CLI::error( 'LoggingMailTransport did not record a successful controlled send.' );
		}

		WP_CLI::success(
			sprintf(
				'DEV mail verification passed (env=development, transport=%s, recorded=%d).',
				LoggingMailTransport::class,
				$after - $before
			)
		);
	}
}
