<?php
/**
 * PDP rating summary using WooCommerce approved review APIs.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Biopentra_Storefront_Pdp_Rating_Summary_Module {

	/**
	 * Register hooks.
	 */
	public static function init() {
		if ( ! function_exists( 'is_product' ) ) {
			return;
		}

		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_summary' ), 6 );
	}

	/**
	 * Render compact rating summary linking to #reviews.
	 */
	public static function render_summary() {
		if ( ! is_product() ) {
			return;
		}

		if ( class_exists( 'Biopentra_Upr_Host_Options' ) && ! Biopentra_Upr_Host_Options::get( 'enable_pdp_summary', true ) ) {
			return;
		}

		$product = wc_get_product( get_the_ID() );
		if ( ! $product ) {
			return;
		}

		$count   = (int) $product->get_review_count();
		$average = (float) $product->get_average_rating();
		$url     = get_permalink( $product->get_id() ) . '#reviews';

		echo '<div class="bp-pdp-rating-summary" data-bp-pdp-rating-summary>';
		if ( $count <= 0 ) {
			echo '<p class="bp-pdp-rating-summary__empty">';
			echo esc_html__( 'No reviews yet', 'biopentra-storefront' );
			echo ' — <a href="' . esc_url( $url ) . '">' . esc_html__( 'Be the first', 'biopentra-storefront' ) . '</a>';
			echo '</p>';
		} else {
			echo '<p class="bp-pdp-rating-summary__line">';
			echo '<a href="' . esc_url( $url ) . '">';
			echo esc_html(
				sprintf(
					/* translators: 1: average rating, 2: review count */
					_n( '%1$s average from %2$d review', '%1$s average from %2$d reviews', $count, 'biopentra-storefront' ),
					number_format_i18n( $average, 1 ),
					$count
				)
			);
			echo '</a></p>';
		}
		echo '</div>';
	}
}
