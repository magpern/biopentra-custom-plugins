<?php
/**
 * Premium mini-cart drawer (Elementor side-cart + Blocksy offcanvas/dropdown).
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return bool
 */
function biopentra_mini_cart_drawer_quantity_inputs_enabled() {
	return (bool) apply_filters( 'biopentra_mini_cart_drawer_quantity_inputs', true );
}

/**
 * @param string $template      Path.
 * @param string $template_name Name.
 * @param string $template_path Path.
 * @return string
 */
function biopentra_mini_cart_drawer_locate_template( $template, $template_name, $template_path ) {
	if ( 'cart/mini-cart.php' !== $template_name ) {
		return $template;
	}

	$custom = BIOPENTRA_HEADER_AUTH_PATH . 'templates/woocommerce/cart/mini-cart.php';
	if ( is_readable( $custom ) ) {
		return $custom;
	}

	return $template;
}

/**
 * Compact subtitle: variation attributes or trimmed short description.
 *
 * @param array       $cart_item Cart item.
 * @param \WC_Product $_product  Product.
 * @return string
 */
function biopentra_mini_cart_drawer_row_subtitle( $cart_item, $_product ) {
	$variation_html = wc_get_formatted_cart_item_data( $cart_item );
	if ( $variation_html ) {
		return '<div class="bp-mini-cart-row__meta">' . $variation_html . '</div>';
	}

	$short = $_product->get_short_description();
	if ( ! $short ) {
		return '';
	}

	return '<p class="bp-mini-cart-row__meta">' . esc_html( wp_trim_words( wp_strip_all_tags( $short ), 14, '…' ) ) . '</p>';
}

/**
 * Quantity pill (Blocksy qty AJAX when theme scripts load).
 *
 * @param string $html          Default markup.
 * @param array  $cart_item     Item.
 * @param string $cart_item_key Key.
 * @return string
 */
function biopentra_mini_cart_drawer_item_quantity( $html, $cart_item, $cart_item_key ) {
	if ( ! biopentra_mini_cart_drawer_quantity_inputs_enabled() ) {
		return $html;
	}

	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $html;
	}

	$_product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
	if ( ! $_product || ! $_product->exists() ) {
		return $html;
	}

	$product_price = apply_filters(
		'woocommerce_cart_item_price',
		WC()->cart->get_product_price( $_product ),
		$cart_item,
		$cart_item_key
	);

	if ( $_product->is_sold_individually() ) {
		return '<div class="bp-mini-cart-qty-pill bp-mini-cart-qty-pill--static" aria-label="' . esc_attr__( 'Quantity', 'biopentra-storefront' ) . '">' .
			'<span class="bp-mini-cart-qty-pill__value">' . esc_html( (string) $cart_item['quantity'] ) . '</span>' .
			'</div>';
	}

	$max_value = $_product->get_max_purchase_quantity();
	$min_value = 0;

	ob_start();
	echo '<div class="bp-mini-cart-qty-pill">';
	woocommerce_quantity_input(
		array(
			'input_name'   => "cart[{$cart_item_key}][qty]",
			'input_value'  => $cart_item['quantity'],
			'max_value'    => $max_value,
			'min_value'    => $min_value,
			'product_name' => $_product->get_name(),
		),
		$_product,
		true
	);
	echo '</div>';

	return (string) ob_get_clean();
}

/**
 * @return void
 */
function biopentra_mini_cart_drawer_register_assets() {
	wp_register_style(
		'biopentra-mini-cart-drawer',
		BIOPENTRA_HEADER_AUTH_URL . 'assets/mini-cart-drawer.css',
		array(),
		BIOPENTRA_HEADER_AUTH_VERSION
	);

	wp_register_script(
		'biopentra-mini-cart-drawer',
		BIOPENTRA_HEADER_AUTH_URL . 'assets/mini-cart-drawer.js',
		array( 'jquery' ),
		BIOPENTRA_HEADER_AUTH_VERSION,
		true
	);
}

/**
 * @return void
 */
function biopentra_mini_cart_drawer_enqueue_assets() {
	if ( is_admin() || ! function_exists( 'WC' ) ) {
		return;
	}

	biopentra_mini_cart_drawer_register_assets();
	wp_enqueue_style( 'biopentra-mini-cart-drawer' );
	wp_enqueue_script( 'biopentra-mini-cart-drawer' );

	$accent = sanitize_hex_color( (string) apply_filters( 'biopentra_header_auth_wc_account_accent', '#1f5fae' ) ) ?: '#1f5fae';
	$title_accent = sanitize_hex_color( (string) apply_filters( 'biopentra_mini_cart_drawer_title_accent', '#c73659' ) ) ?: '#c73659';

	wp_add_inline_style(
		'biopentra-mini-cart-drawer',
		':root{--bp-mc-accent:' . esc_attr( $accent ) . ';--bp-mc-title-accent:' . esc_attr( $title_accent ) . ';}'
	);

	wp_localize_script(
		'biopentra-mini-cart-drawer',
		'bpMiniCart',
		array(
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'hasBlocksyQty' => function_exists( 'blocksy_get_theme_mod' ),
			'shopUrl'       => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
		)
	);
}

/**
 * AJAX: update cart line quantity from the custom mini-cart drawer.
 *
 * @return void
 */
function biopentra_mini_cart_drawer_ajax_update_qty() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		wp_send_json_error( null, 400 );
	}

	$hash = isset( $_POST['hash'] ) ? sanitize_text_field( wp_unslash( $_POST['hash'] ) ) : '';
	$qty  = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : 0;

	if ( '' === $hash || ! WC()->cart->get_cart_item( $hash ) ) {
		wp_send_json_error( null, 400 );
	}

	$updated = WC()->cart->set_quantity( $hash, $qty, true );
	if ( false === $updated ) {
		wp_send_json_error( null, 400 );
	}

	WC()->cart->calculate_totals();
	if ( method_exists( WC()->cart, 'set_session' ) ) {
		WC()->cart->set_session();
	}

	wp_send_json_success(
		array(
			'quantity'  => $qty,
			'cart_hash' => WC()->cart->get_cart_hash(),
		)
	);
}

/**
 * Boot hooks (called from header auth module).
 *
 * @return void
 */
function biopentra_mini_cart_drawer_boot() {
	add_action( 'wp_ajax_biopentra_update_mini_cart_qty', 'biopentra_mini_cart_drawer_ajax_update_qty' );
	add_action( 'wp_ajax_nopriv_biopentra_update_mini_cart_qty', 'biopentra_mini_cart_drawer_ajax_update_qty' );
	add_filter( 'woocommerce_locate_template', 'biopentra_mini_cart_drawer_locate_template', 999, 3 );
	add_filter( 'woocommerce_widget_cart_item_quantity', 'biopentra_mini_cart_drawer_item_quantity', 20, 3 );
	add_action( 'wp_enqueue_scripts', 'biopentra_mini_cart_drawer_register_assets', 5 );
	add_action( 'wp_enqueue_scripts', 'biopentra_mini_cart_drawer_enqueue_assets', 110 );
}
