<?php
/**
 * Required UPR release pin for DEV pilot (bind-mount checkout).
 *
 * @package Biopentra_Upr_Host
 */

defined( 'ABSPATH' ) || exit;

final class Biopentra_Upr_Host_Upr_Pin {

	public const REQUIRED_VERSION = '0.2.2';

	public const REQUIRED_COMMIT = '43c9989291a4c7eab7f9fd57603c851486da287a';

	public const REQUIRED_TAG = 'v0.2.2';

	/**
	 * @return array{ok:bool,version:string,commit:?string,errors:list<string>}
	 */
	public static function verify(): array {
		$errors  = array();
		$version = defined( 'UPR_VERSION' ) ? (string) UPR_VERSION : '';

		if ( self::REQUIRED_VERSION !== $version ) {
			$errors[] = sprintf(
				'UPR_VERSION is "%s"; required "%s".',
				$version,
				self::REQUIRED_VERSION
			);
		}

		$commit = self::resolve_installed_commit();
		if ( null === $commit ) {
			$errors[] = 'Could not resolve universal-product-reviews Git commit from bind-mount.';
		} elseif ( 0 !== strcasecmp( self::REQUIRED_COMMIT, $commit ) ) {
			$errors[] = sprintf(
				'UPR Git commit is "%s"; required "%s".',
				$commit,
				self::REQUIRED_COMMIT
			);
		}

		if ( ! class_exists( \UniversalProductReviews\Submission\NativePdpForm::class ) ) {
			$errors[] = 'UPR NativePdpForm API is unavailable (required for host display helper).';
		}
		if ( ! class_exists( \UniversalProductReviews\Submission\NativeSubmissionGuard::class ) ) {
			$errors[] = 'UPR NativeSubmissionGuard API is unavailable (required for native enforcement).';
		}

		return array(
			'ok'      => empty( $errors ),
			'version' => $version,
			'commit'  => $commit,
			'errors'  => $errors,
		);
	}

	/**
	 * Fail-closed readiness for native-PDP display decisions.
	 */
	public static function display_api_ready(): bool {
		return class_exists( \UniversalProductReviews\Submission\NativePdpForm::class )
			&& defined( 'UPR_VERSION' )
			&& self::REQUIRED_VERSION === (string) UPR_VERSION;
	}

	public static function resolve_installed_commit(): ?string {
		if ( ! defined( 'UPR_PLUGIN_DIR' ) ) {
			return null;
		}
		$plugin_dir = rtrim( (string) UPR_PLUGIN_DIR, '/' );
		$git_dir    = $plugin_dir . '/.git';
		if ( ! is_dir( $git_dir ) ) {
			return null;
		}

		$head_file = $git_dir . '/HEAD';
		if ( ! is_readable( $head_file ) ) {
			return null;
		}

		$head = trim( (string) file_get_contents( $head_file ) );
		if ( str_starts_with( $head, 'ref: ' ) ) {
			$ref = trim( substr( $head, 5 ) );
			$ref_file = $git_dir . '/' . $ref;
			if ( is_readable( $ref_file ) ) {
				return strtolower( substr( trim( (string) file_get_contents( $ref_file ) ), 0, 40 ) );
			}
			$packed = $git_dir . '/packed-refs';
			if ( is_readable( $packed ) ) {
				$lines = file( $packed, FILE_IGNORE_NEW_LINES );
				if ( is_array( $lines ) ) {
					foreach ( $lines as $line ) {
						if ( str_starts_with( $line, '#' ) ) {
							continue;
						}
						$parts = preg_split( '/\s+/', trim( $line ), 2 );
						if ( is_array( $parts ) && 2 === count( $parts ) && $parts[1] === $ref ) {
							return strtolower( substr( $parts[0], 0, 40 ) );
						}
					}
				}
			}
			return null;
		}

		return strtolower( substr( $head, 0, 40 ) );
	}
}
