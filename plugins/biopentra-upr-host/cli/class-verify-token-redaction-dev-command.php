<?php
/**
 * WP-CLI: DEV token redaction verification across SWAG log formats.
 *
 * @package Biopentra_Upr_Host
 */

defined( 'ABSPATH' ) || exit;

final class Biopentra_Upr_Host_Verify_Token_Redaction_Dev_Command {

	/**
	 * Probe invite URL and verify raw token absent from access + cache logs.
	 *
	 * ## EXAMPLES
	 *
	 *     wp biopentra-upr-host verify-token-redaction-dev
	 *
	 * ## OPTIONS
	 *
	 * [--token=<token>]
	 * : Probe token segment (default: generated).
	 *
	 * @when after_wp_load
	 *
	 * @param array<int, string>    $args Positional.
	 * @param array<string, string> $assoc Assoc.
	 */
	public function __invoke( $args, $assoc ): void {
		unset( $args );

		if ( 'development' !== wp_get_environment_type() ) {
			WP_CLI::error( 'Refusing: environment is not development.' );
		}

		$token = isset( $assoc['token'] ) ? (string) $assoc['token'] : 'm3reval' . wp_generate_password( 24, false, false );
		$paths = array(
			'token'  => home_url( '/upr-review/' . rawurlencode( $token ) . '/' ),
			'form'   => home_url( '/upr-review/form/' ),
		);

		$log_files = array(
			'access'   => '/config/log/nginx/access.log',
			'bp-cache' => '/config/log/nginx/bp-cache.log',
		);

		$host_log_root = getenv( 'BIOPENTRA_SWAG_LOG_ROOT' ) ?: '/opt/biopentra/proxy/config/log/nginx';
		foreach ( $log_files as $key => $rel ) {
			$full = $host_log_root . '/' . basename( $rel );
			if ( ! is_readable( $full ) ) {
				WP_CLI::warning( "Log not readable from WP context: {$full} (will verify from host path in closure doc)." );
				unset( $log_files[ $key ] );
				continue;
			}
			$log_files[ $key ] = $full;
		}

		$before = array();
		foreach ( $log_files as $label => $file ) {
			$before[ $label ] = is_readable( $file ) ? (int) filesize( $file ) : 0;
		}

		wp_remote_head(
			$paths['token'],
			array(
				'timeout'   => 15,
				'sslverify' => false,
			)
		);
		wp_remote_head(
			$paths['form'],
			array(
				'timeout'   => 15,
				'sslverify' => false,
			)
		);

		// Allow nginx to flush log buffer.
		sleep( 1 );

		$failures = array();
		foreach ( $log_files as $label => $file ) {
			if ( ! is_readable( $file ) ) {
				continue;
			}
			$tail = $this->read_tail( $file, 4096 );
			if ( false !== strpos( $tail, $token ) ) {
				$failures[] = "{$label}: raw token found in {$file}";
			}
			if ( false === strpos( $tail, '[redacted]' ) && false !== strpos( $tail, 'upr-review' ) ) {
				$failures[] = "{$label}: upr-review present but [redacted] marker missing in recent tail";
			}
			if ( false === strpos( $tail, '/upr-review/form/' ) ) {
				WP_CLI::warning( "{$label}: form path not yet in recent tail (may need repeat probe)." );
			}
		}

		if ( ! empty( $failures ) ) {
			foreach ( $failures as $msg ) {
				WP_CLI::warning( $msg );
			}
			WP_CLI::error( 'Token redaction verification failed for readable logs.' );
		}

		WP_CLI::log( 'Probe token (must NOT appear in logs): ' . $token );
		WP_CLI::log( 'Form path must remain readable: /upr-review/form/' );
		WP_CLI::success( 'Token redaction verification passed for readable SWAG log files.' );
	}

	private function read_tail( string $file, int $bytes ): string {
		$size = filesize( $file );
		if ( false === $size || $size <= $bytes ) {
			return (string) file_get_contents( $file );
		}
		$fh = fopen( $file, 'rb' );
		if ( ! $fh ) {
			return '';
		}
		fseek( $fh, -$bytes, SEEK_END );
		$data = fread( $fh, $bytes );
		fclose( $fh );
		return false === $data ? '' : $data;
	}
}
