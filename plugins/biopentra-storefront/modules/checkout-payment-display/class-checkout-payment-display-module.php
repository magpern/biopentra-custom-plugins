<?php
/**
 * Checkout payment method labels/icons.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display-only checkout payment method customizations.
 */
class Biopentra_Storefront_Checkout_Payment_Display_Module {

	/**
	 * Payment gateway IDs handled by this module.
	 */
	private const GATEWAY_BTCPAY     = 'btcpaygf_default';
	private const GATEWAY_BANXA      = 'vccp-gateway-banxa';
	private const GATEWAY_KLARNA     = 'vccp-gateway-klarna';
	private const GATEWAY_GUARDARIAN = 'vccp-gateway-guardarian';

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_filter( 'woocommerce_gateway_icon', array( __CLASS__, 'gateway_icon' ), 20, 2 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ), 130 );
	}

	/**
	 * Enqueue checkout payment display CSS.
	 */
	public static function enqueue_styles(): void {
		if ( is_admin() || ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		wp_enqueue_style(
			'biopentra-checkout-payment-display',
			BIOPENTRA_STOREFRONT_URL . 'modules/checkout-payment-display/assets/checkout-payment-display.css',
			array(),
			BIOPENTRA_STOREFRONT_VERSION
		);
	}

	/**
	 * Replace gateway icons with the supported storefront SVG assets.
	 *
	 * @param string $icon Existing gateway icon HTML.
	 * @param string $id   Gateway ID.
	 * @return string
	 */
	public static function gateway_icon( string $icon, string $id ): string {
		if ( self::GATEWAY_BTCPAY === $id ) {
			return self::render_icons(
				array(
					'bitcoin' => 'Bitcoin',
				)
			);
		}

		if ( in_array( $id, array( self::GATEWAY_BANXA, self::GATEWAY_KLARNA, self::GATEWAY_GUARDARIAN ), true ) ) {
			return self::render_icons(
				array(
					'visa'       => 'Visa',
					'mastercard' => 'Mastercard',
				)
			);
		}

		return $icon;
	}

	/**
	 * Render payment icon HTML.
	 *
	 * @param array<string,string> $icons Icon key => alt label.
	 * @return string
	 */
	private static function render_icons( array $icons ): string {
		$urls = self::icon_urls();
		$html = '<span class="bp-checkout-payment-icons" aria-hidden="true">';

		foreach ( $icons as $key => $label ) {
			if ( empty( $urls[ $key ] ) ) {
				continue;
			}

			$html .= sprintf(
				'<img class="bp-checkout-payment-icon bp-checkout-payment-icon--%1$s" src="%2$s" alt="%3$s" loading="lazy" decoding="async" />',
				esc_attr( $key ),
				esc_url( $urls[ $key ] ),
				esc_attr( $label )
			);
		}

		$html .= '</span>';

		return $html;
	}

	/**
	 * Storefront-bundled SVG icon URLs.
	 *
	 * @return array<string,string>
	 */
	private static function icon_urls(): array {
		$base = trailingslashit( BIOPENTRA_STOREFRONT_URL . 'modules/header-auth/assets/images' );

		return array(
			'bitcoin'    => $base . 'Bitcoin-Cryptocurrency.svg',
			'visa'       => $base . 'Visa-logo.svg',
			'mastercard' => $base . 'Mastercard-logo.svg',
		);
	}
}
