<?php
/**
 * Configurable product stock labels (replaces WooCommerce exact quantity display).
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filters woocommerce_get_availability on the storefront only.
 */
class Biopentra_Storefront_Product_Stock_Display_Module {

	const STYLE_HANDLE = 'biopentra-storefront-product-stock-display';

	const STYLE_VERSION = '1.0.0';

	const STYLE_REL_PATH = 'assets/product-stock-display/stock-display.css';

	/**
	 * Stock level keys used for labels and CSS modifiers.
	 */
	const LEVEL_IN_STOCK    = 'in-stock';
	const LEVEL_LOW_STOCK   = 'low-stock';
	const LEVEL_ONLY_LEFT   = 'only-left';
	const LEVEL_OUT_OF_STOCK = 'out-of-stock';

	/**
	 * Register hooks.
	 */
	public static function init() {
		$settings_file = __DIR__ . '/class-product-stock-display-settings.php';
		if ( is_readable( $settings_file ) ) {
			require_once $settings_file;
			Biopentra_Storefront_Product_Stock_Display_Settings::init();
		}

		add_filter( 'woocommerce_get_availability', array( __CLASS__, 'filter_availability' ), 20, 2 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
	}

	/**
	 * Enqueue base CSS and per-site color variables from settings.
	 */
	public static function enqueue_styles() {
		if ( is_admin() || ! self::should_apply() ) {
			return;
		}

		if ( ! defined( 'BIOPENTRA_STOREFRONT_PATH' ) || ! defined( 'BIOPENTRA_STOREFRONT_URL' ) ) {
			return;
		}

		$css = BIOPENTRA_STOREFRONT_PATH . self::STYLE_REL_PATH;
		if ( ! is_readable( $css ) ) {
			return;
		}

		wp_register_style(
			self::STYLE_HANDLE,
			BIOPENTRA_STOREFRONT_URL . self::STYLE_REL_PATH,
			array(),
			self::STYLE_VERSION
		);

		$settings = Biopentra_Storefront_Product_Stock_Display_Settings::get();
		$inline   = sprintf(
			':root{--bp-stock-in-stock:%1$s;--bp-stock-low-stock:%2$s;--bp-stock-only-left:%3$s;--bp-stock-out-of-stock:%4$s;}',
			esc_attr( $settings['color_in_stock'] ),
			esc_attr( $settings['color_low_stock'] ),
			esc_attr( $settings['color_only_left'] ),
			esc_attr( $settings['color_out_of_stock'] )
		);

		wp_enqueue_style( self::STYLE_HANDLE );
		wp_add_inline_style( self::STYLE_HANDLE, $inline );
	}

	/**
	 * @param array<string, string> $availability Availability data.
	 * @param WC_Product            $product      Product.
	 * @return array<string, string>
	 */
	public static function filter_availability( $availability, $product ) {
		if ( is_admin() || ! self::should_apply() ) {
			return $availability;
		}

		if ( ! $product instanceof WC_Product ) {
			return $availability;
		}

		if ( self::should_preserve_wc_backorder_text( $product ) ) {
			return $availability;
		}

		$level = self::resolve_stock_level( $product );
		if ( null === $level ) {
			return $availability;
		}

		$settings = Biopentra_Storefront_Product_Stock_Display_Settings::get();
		$text     = self::label_for_level( $level, $product, $settings );

		$availability['availability'] = self::format_availability_html( $level, $text );
		$availability['class']        = self::wc_class_for_level( $level );

		return $availability;
	}

	/**
	 * Whether custom display is active on the frontend.
	 */
	private static function should_apply() {
		if ( ! class_exists( 'Biopentra_Storefront_Product_Stock_Display_Settings' ) ) {
			return false;
		}
		return Biopentra_Storefront_Product_Stock_Display_Settings::is_enabled();
	}

	/**
	 * Leave WooCommerce backorder notification strings unchanged.
	 *
	 * @param WC_Product $product Product.
	 */
	private static function should_preserve_wc_backorder_text( $product ) {
		if ( ! $product->is_on_backorder( 1 ) ) {
			return false;
		}
		if ( $product->managing_stock() && $product->backorders_require_notification() ) {
			return true;
		}
		if ( ! $product->managing_stock() ) {
			return true;
		}
		return false;
	}

	/**
	 * Resolve display level for a product.
	 *
	 * @param WC_Product $product Product.
	 * @return string|null One of LEVEL_* constants, or null to skip.
	 */
	private static function resolve_stock_level( $product ) {
		if ( ! $product->is_in_stock() ) {
			return self::LEVEL_OUT_OF_STOCK;
		}

		if ( $product->managing_stock() ) {
			$qty = $product->get_stock_quantity();
			if ( null !== $qty ) {
				$qty = (int) $qty;
				if ( $qty <= 0 ) {
					return self::LEVEL_OUT_OF_STOCK;
				}

				$settings   = Biopentra_Storefront_Product_Stock_Display_Settings::get();
				$only_left  = (int) $settings['only_left_threshold'];
				$low_stock  = (int) $settings['low_stock_threshold'];

				if ( $qty <= $only_left ) {
					return self::LEVEL_ONLY_LEFT;
				}
				if ( $qty <= $low_stock ) {
					return self::LEVEL_LOW_STOCK;
				}
				return self::LEVEL_IN_STOCK;
			}
		}

		if ( $product->is_in_stock() ) {
			return self::LEVEL_IN_STOCK;
		}

		return self::LEVEL_OUT_OF_STOCK;
	}

	/**
	 * @param string               $level    Stock level key.
	 * @param WC_Product           $product  Product.
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return string Plain text label (escaped later).
	 */
	private static function label_for_level( $level, $product, $settings ) {
		switch ( $level ) {
			case self::LEVEL_OUT_OF_STOCK:
				return (string) $settings['text_out_of_stock'];
			case self::LEVEL_ONLY_LEFT:
				$template = (string) $settings['text_only_left'];
				$qty      = $product->managing_stock() ? (int) $product->get_stock_quantity() : 0;
				return str_replace( '{stock}', (string) max( 0, $qty ), $template );
			case self::LEVEL_LOW_STOCK:
				return (string) $settings['text_low_stock'];
			case self::LEVEL_IN_STOCK:
			default:
				return (string) $settings['text_in_stock'];
		}
	}

	/**
	 * @param string $level Stock level key.
	 * @param string $text  Label text.
	 * @return string Safe HTML for stock.php (wp_kses_post).
	 */
	private static function format_availability_html( $level, $text ) {
		$modifier = str_replace( '_', '-', $level );
		$class    = 'bp-stock-status bp-stock-status--' . $modifier;

		return sprintf(
			'<span class="%1$s">%2$s</span>',
			esc_attr( $class ),
			esc_html( $text )
		);
	}

	/**
	 * WooCommerce stock paragraph class (purchasability styling).
	 *
	 * @param string $level Stock level key.
	 * @return string
	 */
	private static function wc_class_for_level( $level ) {
		if ( self::LEVEL_OUT_OF_STOCK === $level ) {
			return 'out-of-stock';
		}
		return 'in-stock';
	}
}
