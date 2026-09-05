<?php
/**
 * PDP bulk pricing selector (contract consumer).
 *
 * Requires mp-commerce-promotions `mp_cp_bulk_pricing_storefront_v1` filter.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Biopentra_Storefront_Pdp_Bulk_Pricing_Module {

	/**
	 * Register hooks.
	 */
	public static function init() {
		if ( ! function_exists( 'is_product' ) ) {
			return;
		}

		add_action( 'woocommerce_before_add_to_cart_form', array( __CLASS__, 'render_selector' ), 7 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue PDP assets when contract is available.
	 */
	public static function enqueue_assets() {
		if ( ! is_product() ) {
			return;
		}

		$product = wc_get_product( get_the_ID() );
		if ( ! $product || ! $product->is_type( 'simple' ) ) {
			return;
		}

		$contract = self::get_contract( $product );
		if ( $contract === null ) {
			return;
		}

		$base = BIOPENTRA_STOREFRONT_URL . 'assets/';
		$ver  = BIOPENTRA_STOREFRONT_VERSION;

		wp_enqueue_style(
			'bp-pdp-bulk-pricing',
			$base . 'css/pdp-bulk-pricing.css',
			array(),
			$ver
		);

		wp_enqueue_script(
			'bp-pdp-bulk-pricing',
			$base . 'js/pdp-bulk-pricing.js',
			array(),
			$ver,
			true
		);

		wp_enqueue_script(
			'bp-pdp-bulk-pricing-sticky-sync',
			$base . 'js/pdp-bulk-pricing-sticky-sync.js',
			array( 'bp-pdp-bulk-pricing' ),
			$ver,
			true
		);

		wp_localize_script(
			'bp-pdp-bulk-pricing',
			'bpPdpBulkPricing',
			array(
				'contract' => $contract,
			)
		);
	}

	/**
	 * Render anchor selector between price/stock and quantity controls.
	 */
	public static function render_selector() {
		if ( ! is_product() ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product || ! $product->is_type( 'simple' ) ) {
			return;
		}

		$contract = self::get_contract( $product );
		if ( $contract === null || empty( $contract['anchors'] ) || ! is_array( $contract['anchors'] ) ) {
			return;
		}

		$group_label = isset( $contract['a11y']['group_label'] )
			? (string) $contract['a11y']['group_label']
			: __( 'Quantity pricing', 'biopentra-storefront' );

		$baseline_qty = isset( $contract['form']['baseline_quantity'] )
			? max( 1, (int) $contract['form']['baseline_quantity'] )
			: 1;

		echo '<div class="bp-pdp-bulk-pricing" data-bp-pdp-bulk-pricing hidden>';
		echo '<fieldset class="bp-pdp-bulk-pricing__fieldset">';
		echo '<legend class="bp-pdp-bulk-pricing__legend">' . esc_html( $group_label ) . '</legend>';
		echo '<div class="bp-pdp-bulk-pricing__options" role="radiogroup" aria-label="' . esc_attr( $group_label ) . '">';

		foreach ( $contract['anchors'] as $index => $anchor ) {
			if ( ! is_array( $anchor ) ) {
				continue;
			}

			$tier_id   = isset( $anchor['tier_id'] ) ? (string) $anchor['tier_id'] : (string) $index;
			$anchor_qty = isset( $anchor['anchor_quantity'] ) ? max( 1, (int) $anchor['anchor_quantity'] ) : 1;
			$unit_html  = isset( $anchor['unit_html'] ) ? (string) $anchor['unit_html'] : '';
			$line_html  = isset( $anchor['line_total_html'] ) ? (string) $anchor['line_total_html'] : '';
			$badge      = isset( $anchor['badge'] ) && is_string( $anchor['badge'] ) && $anchor['badge'] !== ''
				? $anchor['badge']
				: '';
			$checked    = $anchor_qty === $baseline_qty ? ' checked' : '';
			$input_id   = 'bp-pdp-bulk-pricing-' . esc_attr( $tier_id );

			echo '<label class="bp-pdp-bulk-pricing__option" for="' . esc_attr( $input_id ) . '">';
			echo '<input type="radio" class="bp-pdp-bulk-pricing__radio" name="bp_pdp_bulk_anchor" id="' . esc_attr( $input_id ) . '"';
			echo ' value="' . esc_attr( (string) $anchor_qty ) . '"';
			echo ' data-tier-id="' . esc_attr( $tier_id ) . '"';
			echo ' data-unit-minor="' . esc_attr( (string) (int) ( $anchor['unit_minor'] ?? 0 ) ) . '"';
			echo ' data-line-minor="' . esc_attr( (string) (int) ( $anchor['line_total_minor'] ?? 0 ) ) . '"';
			echo $checked . ' />';
			echo '<span class="bp-pdp-bulk-pricing__card">';
			echo '<span class="bp-pdp-bulk-pricing__qty">' . esc_html( sprintf( _n( '%d unit', '%d units', $anchor_qty, 'biopentra-storefront' ), $anchor_qty ) ) . '</span>';
			echo '<span class="bp-pdp-bulk-pricing__unit">' . wp_kses_post( $unit_html ) . '</span>';
			echo '<span class="bp-pdp-bulk-pricing__line">' . wp_kses_post( $line_html ) . '</span>';
			if ( $badge !== '' ) {
				echo '<span class="bp-pdp-bulk-pricing__badge">' . esc_html( $badge ) . '</span>';
			}
			echo '</span></label>';
		}

		echo '</div>';
		echo '<p class="bp-pdp-bulk-pricing__custom" data-bp-bulk-custom-preview hidden>';
		echo '<span class="bp-pdp-bulk-pricing__custom-label">' . esc_html__( 'Custom quantity', 'biopentra-storefront' ) . '</span> ';
		echo '<span class="bp-pdp-bulk-pricing__custom-unit" data-bp-bulk-custom-unit></span>';
		echo '<span class="bp-pdp-bulk-pricing__custom-total" data-bp-bulk-custom-total></span>';
		echo '</p>';

		if ( ! empty( $contract['pricing_disclaimer'] ) ) {
			echo '<p class="bp-pdp-bulk-pricing__disclaimer">' . esc_html( (string) $contract['pricing_disclaimer'] ) . '</p>';
		}

		echo '</fieldset></div>';
	}

	/**
	 * @param WC_Product $product
	 * @return array<string, mixed>|null
	 */
	private static function get_contract( $product ) {
		if ( ! has_filter( 'mp_cp_bulk_pricing_storefront_v1' ) ) {
			return null;
		}

		$contract = apply_filters( 'mp_cp_bulk_pricing_storefront_v1', null, $product );
		if ( ! is_array( $contract ) || empty( $contract['contract_version'] ) ) {
			return null;
		}

		return $contract;
	}
}
