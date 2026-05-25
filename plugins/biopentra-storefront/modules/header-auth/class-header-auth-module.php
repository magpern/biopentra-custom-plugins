<?php
/**
 * Header auth: shortcode, Elementor widget, WC account/checkout/cart, Blocksy integrations.
 *
 * Migrated from `biopentra-header-auth` (legacy plugin retained; do not run both active).
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storefront-owned header auth (Phase 4).
 */
class Biopentra_Storefront_Header_Auth_Module {

	/**
	 * @return bool
	 */
	private static function legacy_plugin_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( 'biopentra-header-auth/biopentra-header-auth.php' );
	}

	/**
	 * @return void
	 */
	private static function define_constants() {
		if ( ! defined( 'BIOPENTRA_HEADER_AUTH_VERSION' ) ) {
			define( 'BIOPENTRA_HEADER_AUTH_VERSION', '1.5.10' );
		}
		if ( ! defined( 'BIOPENTRA_HEADER_AUTH_PATH' ) ) {
			define( 'BIOPENTRA_HEADER_AUTH_PATH', trailingslashit( BIOPENTRA_STOREFRONT_PATH . 'modules/header-auth' ) );
		}
		if ( ! defined( 'BIOPENTRA_HEADER_AUTH_URL' ) ) {
			define( 'BIOPENTRA_HEADER_AUTH_URL', trailingslashit( BIOPENTRA_STOREFRONT_URL . 'modules/header-auth' ) );
		}
	}

	/**
	 * @return void
	 */
	private static function load_includes() {
		require_once BIOPENTRA_HEADER_AUTH_PATH . 'includes/markup.php';
		require_once BIOPENTRA_HEADER_AUTH_PATH . 'includes/cart-enhancements.php';
		require_once BIOPENTRA_HEADER_AUTH_PATH . 'includes/blocksy-global-palette.php';
		require_once BIOPENTRA_HEADER_AUTH_PATH . 'includes/mini-cart-drawer.php';
		require_once BIOPENTRA_HEADER_AUTH_PATH . 'includes/header-auth-hooks.php';
		require_once BIOPENTRA_HEADER_AUTH_PATH . 'includes/checkout-v2-styles.php';
	}

	/**
	 * Register hooks (idempotent if legacy plugin owns the site).
	 */
	public static function init() {
		if ( self::legacy_plugin_active() ) {
			return;
		}

		self::define_constants();
		self::load_includes();
		biopentra_mini_cart_drawer_boot();

		add_action( 'wp_enqueue_scripts', 'biopentra_header_auth_register_assets', 5 );
		add_action( 'wp_enqueue_scripts', 'biopentra_storefront_register_checkout_v2_styles', 5 );
		add_action( 'wp_enqueue_scripts', 'biopentra_header_auth_enqueue_wc_account_form_styles', 100 );
		add_action( 'wp_enqueue_scripts', 'biopentra_storefront_enqueue_checkout_v2_styles', 130 );
		add_filter( 'woocommerce_account_menu_items', 'biopentra_header_auth_hide_account_downloads_menu_item', 99 );
		add_filter( 'blocksy:woocommerce:single-product:post-class', 'biopentra_header_auth_blocksy_ensure_ct_ajax_add_to_cart_class', 20 );
		add_action( 'elementor/elements/categories_registered', 'biopentra_header_auth_register_elementor_category' );
		add_shortcode( 'biopentra_header_auth', 'biopentra_header_auth_shortcode' );
		add_action( 'plugins_loaded', 'biopentra_header_auth_boot_elementor_widgets', 20 );

		add_action( 'wp_enqueue_scripts', 'biopentra_header_auth_enqueue_cart_enhancements_assets', 100 );
		add_action( 'woocommerce_before_cart_totals', 'biopentra_header_auth_cart_free_shipping_progress', 5 );
		add_action( 'admin_init', 'biopentra_header_auth_maybe_apply_blocksy_global_palette', 5 );
	}
}
