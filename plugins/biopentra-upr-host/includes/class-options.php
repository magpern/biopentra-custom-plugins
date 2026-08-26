<?php
/**
 * Host options.
 *
 * @package Biopentra_Upr_Host
 */

defined( 'ABSPATH' ) || exit;

final class Biopentra_Upr_Host_Options {

	public const OPTION_KEY = 'biopentra_upr_host_settings';

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'confirm_on_shipped'       => true,
			'support_delay_days'       => 14,
			'support_delay_tags'       => array( 'order_issue', 'review_delay' ),
			'support_suppress_tags'    => array( 'chargeback', 'compliance', 'safety' ),
			'support_open_statuses'    => array( 'open', 'pending' ),
			'enable_pdp_summary'       => true,
			'enable_card_ratings'      => false,
			'card_ratings_min_count'   => 3,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return array_merge( self::defaults(), $stored );
	}

	/**
	 * @param string $key Option key.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public static function get( string $key, $default = null ) {
		$all = self::all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
	}

	public static function confirm_on_shipped(): bool {
		return (bool) self::get( 'confirm_on_shipped', true );
	}

	public static function support_delay_days(): int {
		return max( 1, (int) self::get( 'support_delay_days', 14 ) );
	}

	/**
	 * @return list<string>
	 */
	public static function support_delay_tags(): array {
		return self::string_list( self::get( 'support_delay_tags', array() ) );
	}

	/**
	 * @return list<string>
	 */
	public static function support_suppress_tags(): array {
		return self::string_list( self::get( 'support_suppress_tags', array() ) );
	}

	/**
	 * @return list<string>
	 */
	public static function support_open_statuses(): array {
		return self::string_list( self::get( 'support_open_statuses', array( 'open', 'pending' ) ) );
	}

	/**
	 * @param mixed $value Raw list.
	 * @return list<string>
	 */
	private static function string_list( $value ): array {
		if ( is_string( $value ) ) {
			$value = preg_split( '/[\s,]+/', $value ) ?: array();
		}
		if ( ! is_array( $value ) ) {
			return array();
		}
		$out = array();
		foreach ( $value as $item ) {
			$item = sanitize_key( (string) $item );
			if ( '' !== $item ) {
				$out[] = $item;
			}
		}
		return array_values( array_unique( $out ) );
	}
}
