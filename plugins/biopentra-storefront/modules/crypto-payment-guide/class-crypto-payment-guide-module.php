<?php
/**
 * Cart/checkout links to the crypto payment how-to guide page.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Crypto payment guide banner (cart) and compact link (checkout).
 */
class Biopentra_Storefront_Crypto_Payment_Guide_Module {

	public const OPTION_ENABLED  = 'biopentra_crypto_guide_enabled';
	public const OPTION_PAGE_ID  = 'biopentra_crypto_guide_page_id';
	public const PAGE_SLUG       = 'how-to-pay-with-crypto';
	public const STYLE_HANDLE    = 'biopentra-crypto-payment-guide';

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_action( 'woocommerce_before_cart', array( __CLASS__, 'render_cart_banner' ), 8 );
		add_action( 'elementor/page_templates/header-footer/before_content', array( __CLASS__, 'render_cart_banner_elementor' ), 8 );
		add_filter( 'the_content', array( __CLASS__, 'prepend_cart_banner_to_content' ), 8 );
		add_action( 'woocommerce_review_order_before_payment', array( __CLASS__, 'render_checkout_link' ), 3 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ), 125 );
	}

	/**
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return 'yes' === get_option( self::OPTION_ENABLED, 'yes' );
	}

	/**
	 * Resolve published guide page URL or empty string.
	 */
	public static function get_guide_url(): string {
		$page_id = (int) get_option( self::OPTION_PAGE_ID, 0 );

		if ( $page_id > 0 ) {
			$url = get_permalink( $page_id );
			if ( is_string( $url ) && $url !== '' && 'publish' === get_post_status( $page_id ) ) {
				return (string) apply_filters( 'biopentra_crypto_guide_page_url', $url );
			}
		}

		$page = get_page_by_path( self::PAGE_SLUG, OBJECT, 'page' );
		if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
			$url = get_permalink( $page );
			if ( is_string( $url ) && $url !== '' ) {
				return (string) apply_filters( 'biopentra_crypto_guide_page_url', $url );
			}
		}

		return (string) apply_filters( 'biopentra_crypto_guide_page_url', '' );
	}

	/**
	 * @return bool
	 */
	public static function should_render(): bool {
		if ( ! self::is_enabled() ) {
			return false;
		}

		if ( ! function_exists( 'WC' ) ) {
			return false;
		}

		return self::get_guide_url() !== '';
	}

	/**
	 * Whether the current request is the WooCommerce cart page.
	 */
	private static function is_cart_page(): bool {
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return true;
		}

		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return false;
		}

		$cart_id = (int) wc_get_page_id( 'cart' );
		return $cart_id > 0 && is_page( $cart_id );
	}

	/**
	 * Whether the current request is the WooCommerce checkout page (not thank-you).
	 */
	private static function is_checkout_page(): bool {
		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			return false;
		}

		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return true;
		}

		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return false;
		}

		$checkout_id = (int) wc_get_page_id( 'checkout' );
		return $checkout_id > 0 && is_page( $checkout_id );
	}

	/**
	 * Enqueue scoped styles on cart and checkout only.
	 */
	public static function enqueue_styles(): void {
		if ( is_admin() || ! self::should_render() ) {
			return;
		}

		if ( ! self::is_cart_page() && ! self::is_checkout_page() ) {
			return;
		}

		wp_enqueue_style(
			self::STYLE_HANDLE,
			BIOPENTRA_STOREFRONT_URL . 'modules/crypto-payment-guide/assets/crypto-payment-guide.css',
			array(),
			BIOPENTRA_STOREFRONT_VERSION
		);
	}

	/**
	 * Whether cart banner HTML was already output this request.
	 *
	 * @var bool
	 */
	private static $cart_banner_rendered = false;

	/**
	 * Cart banner for Elementor header-footer page templates (e.g. cart page ID 82).
	 */
	public static function render_cart_banner_elementor(): void {
		if ( ! self::is_cart_page() || ! self::should_render() ) {
			return;
		}

		self::output_cart_banner();
	}

	/**
	 * Full informational banner on cart page (classic cart template).
	 */
	public static function render_cart_banner(): void {
		if ( ! self::is_cart_page() || ! self::should_render() ) {
			return;
		}

		self::output_cart_banner();
	}

	/**
	 * Elementor cart pages may not render the classic cart template; prepend via the_content.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function prepend_cart_banner_to_content( string $content ): string {
		if ( ! self::is_cart_page() || ! self::should_render() ) {
			return $content;
		}

		if ( ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		if ( self::$cart_banner_rendered ) {
			return $content;
		}

		ob_start();
		self::output_cart_banner();
		$banner = (string) ob_get_clean();

		if ( $banner === '' ) {
			return $content;
		}

		return $banner . $content;
	}

	/**
	 * Echo cart banner markup once per request.
	 */
	private static function output_cart_banner(): void {
		if ( self::$cart_banner_rendered ) {
			return;
		}

		$url = self::get_guide_url();
		if ( $url === '' ) {
			return;
		}

		$html = self::build_cart_banner_html( $url );
		$html = (string) apply_filters( 'biopentra_crypto_guide_cart_banner_html', $html, $url );

		if ( $html === '' ) {
			return;
		}

		self::$cart_banner_rendered = true;

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- filtered HTML built with escaped parts.
		echo $html;
	}

	/**
	 * Compact link row above payment methods on checkout.
	 */
	public static function render_checkout_link(): void {
		if ( ! self::is_checkout_page() || ! self::should_render() ) {
			return;
		}

		$url = self::get_guide_url();
		if ( $url === '' ) {
			return;
		}

		$html = self::build_checkout_link_html( $url );
		$html = (string) apply_filters( 'biopentra_crypto_guide_checkout_link_html', $html, $url );

		if ( $html === '' ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- filtered HTML built with escaped parts.
		echo $html;
	}

	/**
	 * @param string $url Guide page URL.
	 */
	private static function build_cart_banner_html( string $url ): string {
		$link_text = esc_html__( 'View the step-by-step guide', 'biopentra-storefront' );

		ob_start();
		?>
		<div class="bp-crypto-payment-guide bp-crypto-payment-guide--cart" role="region" aria-label="<?php esc_attr_e( 'Crypto payment help', 'biopentra-storefront' ); ?>">
			<div class="bp-crypto-payment-guide__inner">
				<span class="bp-crypto-payment-guide__icons" aria-hidden="true">
					<img class="bp-crypto-payment-guide__icon" src="<?php echo esc_url( self::icon_url( 'bitcoin.svg' ) ); ?>" alt="" width="24" height="24" decoding="async" />
					<img class="bp-crypto-payment-guide__icon" src="<?php echo esc_url( self::icon_url( 'card.svg' ) ); ?>" alt="" width="24" height="24" decoding="async" />
				</span>
				<p class="bp-crypto-payment-guide__text">
					<strong><?php esc_html_e( 'New to crypto payments?', 'biopentra-storefront' ); ?></strong>
					<?php esc_html_e( 'You can pay with Bitcoin or use your card through a secure third-party provider.', 'biopentra-storefront' ); ?>
					<a class="bp-crypto-payment-guide__link" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $link_text ); ?> &rarr;</a>
				</p>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * @param string $url Guide page URL.
	 */
	private static function build_checkout_link_html( string $url ): string {
		$link_text = esc_html__( 'How to pay with crypto — step-by-step guide', 'biopentra-storefront' );

		ob_start();
		?>
		<div class="bp-crypto-payment-guide bp-crypto-payment-guide--checkout" role="region" aria-label="<?php esc_attr_e( 'Crypto payment help', 'biopentra-storefront' ); ?>">
			<a class="bp-crypto-payment-guide__checkout-link" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $link_text ); ?> &rarr;</a>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * @param string $filename Icon filename under assets/icons/.
	 */
	private static function icon_url( string $filename ): string {
		return BIOPENTRA_STOREFRONT_URL . 'modules/crypto-payment-guide/assets/icons/' . ltrim( $filename, '/' );
	}
}
