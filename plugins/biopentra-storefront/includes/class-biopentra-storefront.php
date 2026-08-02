<?php
/**
 * Main plugin controller.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Biopentra_Storefront {

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Load storefront modules (safe if optional files are absent).
	 */
	public function init() {
		$module_file = BIOPENTRA_STOREFRONT_PATH . 'modules/information-megamenu/class-information-megamenu-module.php';
		if ( is_readable( $module_file ) ) {
			require_once $module_file;
			if ( class_exists( 'Biopentra_Storefront_Information_Megamenu_Module' ) ) {
				Biopentra_Storefront_Information_Megamenu_Module::init();
			}
		}

		$footer_module = BIOPENTRA_STOREFRONT_PATH . 'modules/footer-contact/class-footer-contact-module.php';
		if ( is_readable( $footer_module ) ) {
			require_once $footer_module;
			if ( class_exists( 'Biopentra_Storefront_Footer_Contact_Module' ) ) {
				Biopentra_Storefront_Footer_Contact_Module::init();
			}
		}

		$cvss_module = BIOPENTRA_STOREFRONT_PATH . 'modules/variation-stock-selector/class-variation-stock-selector-module.php';
		if ( is_readable( $cvss_module ) ) {
			require_once $cvss_module;
			if ( class_exists( 'Biopentra_Storefront_Variation_Stock_Selector_Module' ) ) {
				Biopentra_Storefront_Variation_Stock_Selector_Module::init();
			}
		}

		$header_auth_module = BIOPENTRA_STOREFRONT_PATH . 'modules/header-auth/class-header-auth-module.php';
		if ( is_readable( $header_auth_module ) ) {
			require_once $header_auth_module;
			if ( class_exists( 'Biopentra_Storefront_Header_Auth_Module' ) ) {
				Biopentra_Storefront_Header_Auth_Module::init();
			}
		}

		$checkout_payment_display_module = BIOPENTRA_STOREFRONT_PATH . 'modules/checkout-payment-display/class-checkout-payment-display-module.php';
		if ( is_readable( $checkout_payment_display_module ) ) {
			require_once $checkout_payment_display_module;
			if ( class_exists( 'Biopentra_Storefront_Checkout_Payment_Display_Module' ) ) {
				Biopentra_Storefront_Checkout_Payment_Display_Module::init();
			}
		}

		$seo_module = BIOPENTRA_STOREFRONT_PATH . 'modules/technical-seo/class-technical-seo-module.php';
		if ( is_readable( $seo_module ) ) {
			require_once $seo_module;
			if ( class_exists( 'Biopentra_Storefront_Technical_Seo_Module' ) ) {
				Biopentra_Storefront_Technical_Seo_Module::init();
			}
		}

		$stock_display_module = BIOPENTRA_STOREFRONT_PATH . 'modules/product-stock-display/class-product-stock-display-module.php';
		if ( is_readable( $stock_display_module ) ) {
			require_once $stock_display_module;
			if ( class_exists( 'Biopentra_Storefront_Product_Stock_Display_Module' ) ) {
				Biopentra_Storefront_Product_Stock_Display_Module::init();
			}
		}

		$checkout_pause_module = BIOPENTRA_STOREFRONT_PATH . 'modules/checkout-pause/class-checkout-pause-module.php';
		if ( is_readable( $checkout_pause_module ) ) {
			require_once $checkout_pause_module;
			if ( class_exists( 'Biopentra_Storefront_Checkout_Pause_Module' ) ) {
				Biopentra_Storefront_Checkout_Pause_Module::init();
			}
		}

		$crypto_guide_module = BIOPENTRA_STOREFRONT_PATH . 'modules/crypto-payment-guide/class-crypto-payment-guide-module.php';
		if ( is_readable( $crypto_guide_module ) ) {
			require_once $crypto_guide_module;
			if ( class_exists( 'Biopentra_Storefront_Crypto_Payment_Guide_Module' ) ) {
				Biopentra_Storefront_Crypto_Payment_Guide_Module::init();
			}
		}

		$home_v2_helpers = BIOPENTRA_STOREFRONT_PATH . 'includes/home-v2-helpers.php';
		if ( is_readable( $home_v2_helpers ) ) {
			require_once $home_v2_helpers;
		}

		$home_v2_assets = BIOPENTRA_STOREFRONT_PATH . 'includes/home-v2-assets.php';
		if ( is_readable( $home_v2_assets ) ) {
			require_once $home_v2_assets;
		}

		$shopping_v2_helpers = BIOPENTRA_STOREFRONT_PATH . 'includes/shopping-v2-helpers.php';
		if ( is_readable( $shopping_v2_helpers ) ) {
			require_once $shopping_v2_helpers;
		}

		$shopping_v2_assets = BIOPENTRA_STOREFRONT_PATH . 'includes/shopping-v2-assets.php';
		if ( is_readable( $shopping_v2_assets ) ) {
			require_once $shopping_v2_assets;
		}
	}
}
