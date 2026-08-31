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

		// M8: footer-contact module (email shortcode) removed — see modules/footer-contact/ removal
		// in MILESTONE_M8_GLOBAL_FOOTER_REDESIGN.md. Contact form + Telegram replace it in template 3823.

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

		$pdp_purchase_panel_module = BIOPENTRA_STOREFRONT_PATH . 'modules/pdp-purchase-panel/class-pdp-purchase-panel-module.php';
		if ( is_readable( $pdp_purchase_panel_module ) ) {
			require_once $pdp_purchase_panel_module;
			if ( class_exists( 'Biopentra_Storefront_Pdp_Purchase_Panel_Module' ) ) {
				Biopentra_Storefront_Pdp_Purchase_Panel_Module::init();
			}
		}

		$pdp_rating_summary_module = BIOPENTRA_STOREFRONT_PATH . 'modules/pdp-rating-summary/class-pdp-rating-summary-module.php';
		if ( is_readable( $pdp_rating_summary_module ) ) {
			require_once $pdp_rating_summary_module;
			if ( class_exists( 'Biopentra_Storefront_Pdp_Rating_Summary_Module' ) ) {
				Biopentra_Storefront_Pdp_Rating_Summary_Module::init();
			}
		}

		$pdp_reviews_section_module = BIOPENTRA_STOREFRONT_PATH . 'modules/pdp-reviews-section/class-pdp-reviews-section-module.php';
		if ( is_readable( $pdp_reviews_section_module ) ) {
			require_once $pdp_reviews_section_module;
			if ( class_exists( 'Biopentra_Storefront_Pdp_Reviews_Section_Module' ) ) {
				Biopentra_Storefront_Pdp_Reviews_Section_Module::init();
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

		$bp_tokens_assets = BIOPENTRA_STOREFRONT_PATH . 'includes/bp-tokens-assets.php';
		if ( is_readable( $bp_tokens_assets ) ) {
			require_once $bp_tokens_assets;
		}

		// V1A design specimen (homepage trial only; reversible; not SDS v2 freeze).
		$v1a_specimen = BIOPENTRA_STOREFRONT_PATH . 'includes/v1a-specimen.php';
		if ( is_readable( $v1a_specimen ) ) {
			require_once $v1a_specimen;
		}

		// M1 review hygiene: disable V1A trial via existing filter (do not delete files).
		$m1_hygiene = BIOPENTRA_STOREFRONT_PATH . 'includes/m1-review-hygiene.php';
		if ( is_readable( $m1_hygiene ) ) {
			require_once $m1_hygiene;
		}

		$chrome_v1_assets = BIOPENTRA_STOREFRONT_PATH . 'includes/chrome-v1-assets.php';
		if ( is_readable( $chrome_v1_assets ) ) {
			require_once $chrome_v1_assets;
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

		$shopping_m9_helpers = BIOPENTRA_STOREFRONT_PATH . 'includes/shopping-m9-helpers.php';
		if ( is_readable( $shopping_m9_helpers ) ) {
			require_once $shopping_m9_helpers;
		}

		$footer_v2_assets = BIOPENTRA_STOREFRONT_PATH . 'includes/footer-v2-assets.php';
		if ( is_readable( $footer_v2_assets ) ) {
			require_once $footer_v2_assets;
		}

		$contact_v2_helpers = BIOPENTRA_STOREFRONT_PATH . 'includes/contact-v2-helpers.php';
		if ( is_readable( $contact_v2_helpers ) ) {
			require_once $contact_v2_helpers;
		}

		$contact_v2_assets = BIOPENTRA_STOREFRONT_PATH . 'includes/contact-v2-assets.php';
		if ( is_readable( $contact_v2_assets ) ) {
			require_once $contact_v2_assets;
		}

		$motion_assets = BIOPENTRA_STOREFRONT_PATH . 'includes/motion-assets.php';
		if ( is_readable( $motion_assets ) ) {
			require_once $motion_assets;
		}
	}
}
