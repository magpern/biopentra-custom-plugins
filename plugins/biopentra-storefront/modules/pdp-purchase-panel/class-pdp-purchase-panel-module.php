<?php
/**
 * PDP-1 — Purchase-summary purchase panel.
 *
 * Wraps the native WooCommerce purchase controls (price/stock/variation
 * selection/quantity/Add-to-cart) in a single visual panel using additive
 * hooks only. No template override, no duplicated commerce state.
 *
 * Spec: docs/storefront-redesign/plans/PDP-1_PURCHASE_SUMMARY_REDESIGN.md
 * Freeze commit: 4ae5ee798b26ceecd5d758f81d0cb7a194ed1a4d
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Biopentra_Storefront_Pdp_Purchase_Panel_Module {

	/**
	 * Render-state flag for the variable-product panel wrapper (§2.4 of the plan).
	 *
	 * @var bool
	 */
	private static $variable_panel_open = false;

	/**
	 * Register hooks (idempotent if called once).
	 */
	public static function init() {
		if ( ! function_exists( 'is_product' ) ) {
			return;
		}

		// Eyebrow (WP1) — before the title, native product summary hook.
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_eyebrow' ), 4 );

		// Simple-product price relocation (WP0, §3) — type-scoped, never touches variable products.
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'maybe_relocate_simple_price' ), 1 );

		// Simple-product panel open/close — always balanced (simple.php has no empty-state skip).
		add_action( 'woocommerce_before_add_to_cart_form', array( __CLASS__, 'open_panel_simple' ), 1 );
		add_action( 'woocommerce_after_add_to_cart_form', array( __CLASS__, 'close_panel_simple' ), 99 );

		// Variable-product panel open/close — balanced via render-state flag (§2.4).
		add_action( 'woocommerce_after_variations_table', array( __CLASS__, 'open_panel_variable' ), 1 );
		add_action( 'woocommerce_after_variations_form', array( __CLASS__, 'close_panel_variable' ), 99 );

		// Trust row (WP2) — appended just before panel-close, both product types.
		add_action( 'woocommerce_after_add_to_cart_form', array( __CLASS__, 'render_trust_row_simple' ), 90 );
		add_action( 'woocommerce_after_variations_form', array( __CLASS__, 'render_trust_row_variable' ), 90 );
	}

	/**
	 * §3 — relocate the native single price for simple products only.
	 *
	 * Gated on product type both at attach-time and inside the callback so
	 * variable/grouped/external products never have their priority-10 price
	 * hook touched. Hook removal/re-add is per-request, not persistent.
	 */
	public static function maybe_relocate_simple_price() {
		global $product;

		if ( ! $product instanceof WC_Product || ! $product->is_type( 'simple' ) ) {
			return;
		}

		// DISABLED — WP0 structural proof found that Blocksy's theme
		// (inc/components/woocommerce/single/single-modifications.php)
		// already unhooks 'woocommerce_template_single_price' from this
		// action at 'wp' priority 9000000000 and instead renders it via its
		// own render_layout() ordering system. remove_action() here is a
		// no-op (nothing left to remove), so the add_action() below caused a
		// second, duplicate .price element instead of a relocation. See the
		// WP0 stop-report for the required plan/PO decision before this can
		// be re-enabled with a different mechanism.
		//
		// remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
		// add_action( 'woocommerce_before_add_to_cart_form', 'woocommerce_template_single_price', 5 );
	}

	/**
	 * §2.1 — open the purchase panel for simple products.
	 */
	public static function open_panel_simple() {
		global $product;

		if ( ! $product instanceof WC_Product || ! $product->is_type( 'simple' ) ) {
			return;
		}

		echo '<div class="bp-pdp-purchase-panel">';
	}

	/**
	 * Close the simple-product purchase panel (always balanced with open_panel_simple).
	 */
	public static function close_panel_simple() {
		global $product;

		if ( ! $product instanceof WC_Product || ! $product->is_type( 'simple' ) ) {
			return;
		}

		echo '</div>';
	}

	/**
	 * §2.1/§2.4 — open the purchase panel for variable products.
	 *
	 * Only fires when variable.php's else-branch actually rendered the
	 * variations table (i.e. genuine variations exist) — tracked via a
	 * render-state flag, not a re-derivation of WooCommerce's own condition.
	 */
	public static function open_panel_variable() {
		echo '<div class="bp-pdp-purchase-panel">';
		self::$variable_panel_open = true;
	}

	/**
	 * §2.4 — close the variable-product purchase panel only if it was opened.
	 */
	public static function close_panel_variable() {
		if ( ! self::$variable_panel_open ) {
			return;
		}

		echo '</div>';
		self::$variable_panel_open = false;
	}

	/**
	 * §11 — locked trust row copy, both product types.
	 */
	private static function trust_row_html() {
		$items = array(
			__( 'EU fulfillment', 'biopentra-storefront' ),
			__( 'Secure checkout', 'biopentra-storefront' ),
			__( 'COA-backed quality', 'biopentra-storefront' ),
		);

		$html = '<ul class="bp-pdp-trust-row">';
		foreach ( $items as $item ) {
			$html .= '<li class="bp-pdp-trust-row__item">' . esc_html( $item ) . '</li>';
		}
		$html .= '</ul>';

		return $html;
	}

	/**
	 * Render trust row for simple products (only if the panel was opened).
	 */
	public static function render_trust_row_simple() {
		global $product;

		if ( ! $product instanceof WC_Product || ! $product->is_type( 'simple' ) ) {
			return;
		}

		echo self::trust_row_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render trust row for variable products (only if the panel was opened).
	 */
	public static function render_trust_row_variable() {
		if ( ! self::$variable_panel_open ) {
			return;
		}

		echo self::trust_row_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * §4 — deterministic category eyebrow. Never surfaces the default
	 * ("Uncategorized") category; omits the eyebrow entirely if no other
	 * category is assigned.
	 */
	public static function render_eyebrow() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$product_id      = $product->get_id();
		$default_cat_id  = (int) get_option( 'default_product_cat' );
		$eyebrow         = '';

		// Priority 1: Rank Math primary category, if set and not the default term.
		$primary_term_id = (int) get_post_meta( $product_id, 'rank_math_primary_product_cat', true );
		if ( $primary_term_id && $primary_term_id !== $default_cat_id ) {
			$assigned_terms = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $assigned_terms ) && in_array( $primary_term_id, $assigned_terms, true ) ) {
				$term = get_term( $primary_term_id, 'product_cat' );
				if ( $term && ! is_wp_error( $term ) ) {
					$eyebrow = $term->name;
				}
			}
		}

		// Priority 2: first non-default assigned category, by term_id ascending.
		if ( '' === $eyebrow ) {
			$terms = get_the_terms( $product_id, 'product_cat' );
			if ( is_array( $terms ) && ! empty( $terms ) ) {
				$non_default = array_filter(
					$terms,
					function ( $term ) use ( $default_cat_id ) {
						return (int) $term->term_id !== $default_cat_id;
					}
				);

				if ( ! empty( $non_default ) ) {
					usort(
						$non_default,
						function ( $a, $b ) {
							return $a->term_id <=> $b->term_id;
						}
					);
					$eyebrow = reset( $non_default )->name;
				}
			}
		}

		// Priority 3: omit entirely — never render "Uncategorized".
		if ( '' === $eyebrow ) {
			return;
		}

		echo '<p class="bp-pdp-eyebrow">' . esc_html( $eyebrow ) . '</p>';
	}
}
