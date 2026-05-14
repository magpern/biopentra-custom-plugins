<?php
/**
 * Plugin Name: Biopentra Header Auth
 * Description: Log in / log out and greeting for Elementor headers. Widget + shortcode [biopentra_header_auth].
 * Version: 1.5.0
 * Author: BioPentra
 * Text Domain: biopentra-header-auth
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Storefront Phase 4 may have already loaded these callbacks; avoid redeclare during same-request activation.
if ( function_exists( 'biopentra_header_auth_register_assets' ) ) {
	return;
}

define( 'BIOPENTRA_HEADER_AUTH_VERSION', '1.5.0' );
define( 'BIOPENTRA_HEADER_AUTH_PATH', plugin_dir_path( __FILE__ ) );
define( 'BIOPENTRA_HEADER_AUTH_URL', plugin_dir_url( __FILE__ ) );

require_once BIOPENTRA_HEADER_AUTH_PATH . 'includes/markup.php';
require_once BIOPENTRA_HEADER_AUTH_PATH . 'includes/blocksy-global-palette.php';
require_once BIOPENTRA_HEADER_AUTH_PATH . 'includes/cart-enhancements.php';

/**
 * Register front-end assets (Elementor + shortcode).
 */
function biopentra_header_auth_register_assets() {
	wp_register_style(
		'biopentra-header-auth',
		BIOPENTRA_HEADER_AUTH_URL . 'assets/header-auth.css',
		array(),
		BIOPENTRA_HEADER_AUTH_VERSION
	);
	wp_register_script(
		'biopentra-header-auth',
		BIOPENTRA_HEADER_AUTH_URL . 'assets/header-auth.js',
		array(),
		BIOPENTRA_HEADER_AUTH_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'biopentra_header_auth_register_assets', 5 );

/**
 * WooCommerce storefront cosmetics (My Account, Checkout) — enqueue after Blocksy Woo bundle.
 * Cart-only styling lives in `cart-enhancements.css` (see includes/cart-enhancements.php).
 */
function biopentra_header_auth_enqueue_wc_account_form_styles() {
	if (
		! function_exists( 'is_account_page' )
		|| ! function_exists( 'is_checkout' )
		|| (
			! is_account_page()
			&& ! is_checkout()
		)
	) {
		return;
	}
	// Blocksy dequeues default WC handles; account styling lives in `ct-woocommerce-styles`.
	$deps = array();
	if ( wp_style_is( 'ct-woocommerce-styles', 'registered' ) || wp_style_is( 'ct-woocommerce-styles', 'enqueued' ) ) {
		$deps[] = 'ct-woocommerce-styles';
	} elseif ( wp_style_is( 'ct-main-styles', 'registered' ) || wp_style_is( 'ct-main-styles', 'enqueued' ) ) {
		$deps[] = 'ct-main-styles';
	} else {
		foreach ( array( 'woocommerce-general', 'woocommerce-layout' ) as $handle ) {
			if ( wp_style_is( $handle, 'registered' ) || wp_style_is( $handle, 'enqueued' ) ) {
				$deps[] = $handle;
			}
		}
	}
	wp_register_style(
		'biopentra-wc-account-forms',
		BIOPENTRA_HEADER_AUTH_URL . 'assets/wc-account-forms.css',
		$deps,
		BIOPENTRA_HEADER_AUTH_VERSION
	);
	wp_enqueue_style( 'biopentra-wc-account-forms' );

	$accent = sanitize_hex_color( (string) apply_filters( 'biopentra_header_auth_wc_account_accent', '#1f5fae' ) ) ?: '#1f5fae';
	$hover  = sanitize_hex_color( (string) apply_filters( 'biopentra_header_auth_wc_account_accent_hover', '#174a87' ) ) ?: '#174a87';
	wp_add_inline_style(
		'biopentra-wc-account-forms',
		'body.woocommerce-account,body.woocommerce-checkout{--bph-wc-accent:' . esc_attr( $accent ) . ';--bph-wc-accent-hover:' . esc_attr( $hover ) . ';--bph-wc-accent-muted:#3d4f66;}'
	);
}
add_action( 'wp_enqueue_scripts', 'biopentra_header_auth_enqueue_wc_account_form_styles', 100 );

/**
 * Remove “Downloads” from the My Account sidebar menu.
 *
 * @param array<string, string> $items Endpoint key => label.
 * @return array<string, string>
 */
function biopentra_header_auth_hide_account_downloads_menu_item( $items ) {
	if ( is_array( $items ) && isset( $items['downloads'] ) ) {
		unset( $items['downloads'] );
	}
	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'biopentra_header_auth_hide_account_downloads_menu_item', 99 );

/**
 * Ensure Blocksy’s `ct-ajax-add-to-cart` class exists when AJAX add-to-cart is enabled.
 *
 * Blocksy’s WooCommerceAddToCart only adds this class on single product URLs or when
 * `wp_doing_ajax()` is true *and* `blocksy_has_product_specific_layer( 'product_add_to_cart' )`
 * passes — but that helper returns false when `get_post_type()` is not `product`, which
 * happens during many admin-ajax quick-view loads. Without the class, the theme’s JS
 * never intercepts the form and the browser POSTs to the product page instead.
 *
 * @param array<int, string> $classes Product post classes.
 * @return array<int, string>
 */
function biopentra_header_auth_blocksy_ensure_ct_ajax_add_to_cart_class( $classes ) {
	if ( ! is_array( $classes ) || in_array( 'ct-ajax-add-to-cart', $classes, true ) ) {
		return $classes;
	}
	if ( ! function_exists( 'blocksy_get_theme_mod' ) || ! function_exists( 'blocksy_manager' ) ) {
		return $classes;
	}
	if ( ! class_exists( 'WC_Product', false ) ) {
		return $classes;
	}

	global $product;
	if ( ! $product instanceof WC_Product || $product->is_type( 'external' ) ) {
		return $classes;
	}

	if ( get_option( 'woocommerce_cart_redirect_after_add', 'no' ) !== 'no' ) {
		return $classes;
	}
	if ( blocksy_get_theme_mod( 'has_ajax_add_to_cart', 'yes' ) !== 'yes' ) {
		return $classes;
	}

	$context_needs_class = wp_doing_ajax() || ! blocksy_manager()->screen->is_product();
	if ( ! $context_needs_class ) {
		return $classes;
	}

	$types = array( 'simple', 'variable', 'subscription', 'variable-subscription', 'woosb' );
	if ( ! in_array( $product->get_type(), $types, true ) ) {
		return $classes;
	}

	$classes[] = 'ct-ajax-add-to-cart';
	return $classes;
}
add_filter( 'blocksy:woocommerce:single-product:post-class', 'biopentra_header_auth_blocksy_ensure_ct_ajax_add_to_cart_class', 20 );

/**
 * Elementor panel: “Biopentra” widget category.
 *
 * @param \Elementor\Elements_Manager $elements_manager Elements manager.
 */
function biopentra_header_auth_register_elementor_category( $elements_manager ) {
	$elements_manager->add_category(
		'biopentra',
		array(
			'title' => __( 'Biopentra', 'biopentra-header-auth' ),
			'icon'  => 'eicon-user-circle-o',
		)
	);
}
add_action( 'elementor/elements/categories_registered', 'biopentra_header_auth_register_elementor_category' );

/**
 * Shortcode fallback (e.g. HTML widget with shortcode execution).
 *
 * @param array|string $atts Attributes.
 * @return string
 */
function biopentra_header_auth_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'login_text'      => __( 'Log in', 'biopentra-header-auth' ),
			'logout_text'     => __( 'Log out', 'biopentra-header-auth' ),
			'my_account_text' => __( 'My account', 'biopentra-header-auth' ),
			'identifier'      => 'email',
		),
		$atts,
		'biopentra_header_auth'
	);

	wp_enqueue_style( 'biopentra-header-auth' );

	return biopentra_header_auth_get_html(
		array(
			'login_text'      => $atts['login_text'],
			'logout_text'     => $atts['logout_text'],
			'my_account_text' => $atts['my_account_text'],
			'identifier'      => $atts['identifier'],
		)
	);
}
add_shortcode( 'biopentra_header_auth', 'biopentra_header_auth_shortcode' );

/**
 * Elementor widget.
 */
function biopentra_header_auth_register_elementor_widget( $widgets_manager ) {
	require_once BIOPENTRA_HEADER_AUTH_PATH . 'includes/elementor-widget-header-auth.php';
	$widgets_manager->register( new \Biopentra_Header_Auth\Elementor_Widget_Header_Auth() );
}

/**
 * Attach widget registration after Elementor exists.
 *
 * Do not use only `elementor/loaded`: that action runs while Elementor’s main
 * file loads. If this plugin loads later in `active_plugins` order, the hook
 * has already fired and the widget never registers (invisible in the panel).
 */
function biopentra_header_auth_boot_elementor_widgets() {
	if ( ! class_exists( '\Elementor\Plugin' ) ) {
		return;
	}
	add_action( 'elementor/widgets/register', 'biopentra_header_auth_register_elementor_widget' );
}
add_action( 'plugins_loaded', 'biopentra_header_auth_boot_elementor_widgets', 20 );
