<?php
/**
 * Checkout pause (outage) and optional info banner; card gateway title helpers.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checkout pause / info banner controls for payment messaging.
 */
class Biopentra_Storefront_Checkout_Pause_Module {

	public const OPTION_PAUSED           = 'biopentra_checkout_paused';
	public const OPTION_ENABLED_GATEWAYS = 'biopentra_checkout_pause_enabled_gateways';
	public const OPTION_BANNER           = 'biopentra_checkout_banner';
	public const OPTION_BANNER_MESSAGE   = 'biopentra_checkout_banner_message';

	public const NOTICE_PAUSED = 'Checkout is temporarily unavailable due to a payment provider issue. We are working on it and expect to be back soon.';

	public const NOTICE_BANNER_DEFAULT = 'Payments are back online. Preferred method: BTCPay (Bitcoin). Next best: Banxa. Third option: Blockchain.com. Complete KYC and payment with the provider — you will not be returned to the shop, but your order will still be processed. Please wait for the confirmation email.';

	public const GATEWAY_BTCPAY = 'btcpaygf_default';

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		$show_banner = self::is_paused() || self::is_banner_enabled();

		if ( $show_banner ) {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ), 20 );
			add_action( 'wp_body_open', array( __CLASS__, 'render_site_banner' ), 1 );
			add_action( 'woocommerce_before_cart', array( __CLASS__, 'render_cart_notice' ), 5 );
			add_action( 'woocommerce_before_checkout_form', array( __CLASS__, 'render_checkout_notice' ), 5 );
			add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		}

		add_filter( 'woocommerce_gateway_title', array( __CLASS__, 'filter_gateway_title' ), 20, 2 );
	}

	/**
	 * @return bool
	 */
	public static function is_paused(): bool {
		return 'yes' === get_option( self::OPTION_PAUSED, 'no' );
	}

	/**
	 * @return bool
	 */
	public static function is_banner_enabled(): bool {
		return 'yes' === get_option( self::OPTION_BANNER, 'no' );
	}

	/**
	 * Active banner / notice text.
	 */
	public static function get_notice_text(): string {
		if ( self::is_paused() ) {
			return self::NOTICE_PAUSED;
		}

		$message = get_option( self::OPTION_BANNER_MESSAGE, self::NOTICE_BANNER_DEFAULT );
		if ( ! is_string( $message ) || trim( $message ) === '' ) {
			return self::NOTICE_BANNER_DEFAULT;
		}

		return $message;
	}

	/**
	 * Enable checkout pause and disable currently enabled gateways.
	 *
	 * @return array<string,mixed>
	 */
	public static function pause(): array {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return array(
				'ok'      => false,
				'message' => 'WooCommerce payment gateways are not available.',
			);
		}

		$enabled = self::get_enabled_gateway_ids();
		update_option( self::OPTION_ENABLED_GATEWAYS, $enabled, false );

		foreach ( $enabled as $gateway_id ) {
			self::set_gateway_enabled( $gateway_id, false );
		}

		update_option( self::OPTION_PAUSED, 'yes', false );

		return array(
			'ok'               => true,
			'disabledGateways' => $enabled,
		);
	}

	/**
	 * Disable checkout pause and restore previously enabled gateways.
	 *
	 * @return array<string,mixed>
	 */
	public static function resume(): array {
		$enabled = get_option( self::OPTION_ENABLED_GATEWAYS, array() );
		if ( ! is_array( $enabled ) ) {
			$enabled = array();
		}

		foreach ( $enabled as $gateway_id ) {
			self::set_gateway_enabled( (string) $gateway_id, true );
		}

		delete_option( self::OPTION_PAUSED );
		delete_option( self::OPTION_ENABLED_GATEWAYS );

		return array(
			'ok'               => true,
			'restoredGateways' => $enabled,
		);
	}

	/**
	 * Turn on the informational banner (does not disable gateways).
	 *
	 * @param string|null $message Optional custom message.
	 */
	public static function enable_banner( ?string $message = null ): void {
		update_option( self::OPTION_BANNER, 'yes', false );
		update_option(
			self::OPTION_BANNER_MESSAGE,
			( $message !== null && trim( $message ) !== '' ) ? $message : self::NOTICE_BANNER_DEFAULT,
			false
		);
	}

	/**
	 * Turn off the informational banner.
	 */
	public static function disable_banner(): void {
		update_option( self::OPTION_BANNER, 'no', false );
	}

	/**
	 * Rewrite "Card to USDC via X" titles to "Card to crypto via X (4% fee)".
	 * Never changes BTCPay.
	 *
	 * @return int Number of gateway options updated.
	 */
	public static function rewrite_card_to_crypto_titles(): int {
		global $wpdb;

		$updated = 0;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$names = $wpdb->get_col(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'woocommerce\\_vccp-gateway-%\\_settings' OR option_name LIKE 'woocommerce\\_%\\_settings'"
		);

		if ( ! is_array( $names ) ) {
			return 0;
		}

		foreach ( $names as $option_name ) {
			if ( ! is_string( $option_name ) || $option_name === '' ) {
				continue;
			}

			if ( str_contains( $option_name, 'btcpay' ) ) {
				continue;
			}

			$settings = get_option( $option_name, array() );
			if ( ! is_array( $settings ) || empty( $settings['title'] ) || ! is_string( $settings['title'] ) ) {
				continue;
			}

			$new_title = self::rewrite_card_title( $settings['title'] );
			if ( $new_title === $settings['title'] ) {
				continue;
			}

			$settings['title'] = $new_title;
			update_option( $option_name, $settings, false );
			++$updated;
		}

		return $updated;
	}

	/**
	 * @param string $title Existing gateway title.
	 */
	public static function rewrite_card_title( string $title ): string {
		if ( preg_match( '/^Card to USDC via (.+)$/u', $title, $matches ) ) {
			$provider = trim( $matches[1] );
			if ( $provider === '' ) {
				return $title;
			}

			// Avoid double-appending if already rewritten.
			if ( str_ends_with( $provider, '(4% fee)' ) ) {
				return 'Card to crypto via ' . preg_replace( '/\s*\(4% fee\)\s*$/', '', $provider ) . ' (4% fee)';
			}

			return 'Card to crypto via ' . $provider . ' (4% fee)';
		}

		if ( preg_match( '/^Card to crypto via (.+)$/u', $title, $matches ) && ! str_contains( $title, '(4% fee)' ) ) {
			return 'Card to crypto via ' . trim( $matches[1] ) . ' (4% fee)';
		}

		return $title;
	}

	/**
	 * Defensive display filter for leftover USDC titles.
	 *
	 * @param string $title Gateway title.
	 * @param string $id    Gateway ID.
	 */
	public static function filter_gateway_title( string $title, $id ): string {
		if ( (string) $id === self::GATEWAY_BTCPAY || str_contains( (string) $id, 'btcpay' ) ) {
			return $title;
		}

		return self::rewrite_card_title( $title );
	}

	/**
	 * @return string[]
	 */
	private static function get_enabled_gateway_ids(): array {
		$enabled = array();

		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway_id => $gateway ) {
			if ( isset( $gateway->enabled ) && 'yes' === $gateway->enabled ) {
				$enabled[] = (string) $gateway_id;
			}
		}

		return $enabled;
	}

	/**
	 * @param string $gateway_id Gateway ID.
	 * @param bool   $enabled    Whether the gateway should be enabled.
	 */
	private static function set_gateway_enabled( string $gateway_id, bool $enabled ): void {
		$option_name = 'woocommerce_' . $gateway_id . '_settings';
		$settings    = get_option( $option_name, array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings['enabled'] = $enabled ? 'yes' : 'no';
		update_option( $option_name, $settings, false );
	}

	/**
	 * Enqueue checkout banner styles.
	 */
	public static function enqueue_styles(): void {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'biopentra-checkout-pause',
			BIOPENTRA_STOREFRONT_URL . 'modules/checkout-pause/assets/checkout-pause.css',
			array(),
			BIOPENTRA_STOREFRONT_VERSION
		);
	}

	/**
	 * Site-wide banner.
	 */
	public static function render_site_banner(): void {
		if ( is_admin() ) {
			return;
		}

		$modifier = self::is_paused() ? 'paused' : 'info';

		echo '<div class="bp-checkout-pause-banner bp-checkout-pause-banner--' . esc_attr( $modifier ) . '" role="status">';
		echo '<p class="bp-checkout-pause-banner__text">' . esc_html( self::get_notice_text() ) . '</p>';
		echo '</div>';
	}

	/**
	 * Prominent cart notice.
	 */
	public static function render_cart_notice(): void {
		if ( ! function_exists( 'wc_print_notice' ) ) {
			return;
		}

		wc_print_notice( self::get_notice_text(), 'notice' );
	}

	/**
	 * Prominent checkout notice.
	 */
	public static function render_checkout_notice(): void {
		self::render_cart_notice();
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( array $classes ): array {
		if ( self::is_paused() ) {
			$classes[] = 'biopentra-checkout-paused';
		} elseif ( self::is_banner_enabled() ) {
			$classes[] = 'biopentra-checkout-banner';
		}

		return $classes;
	}
}
