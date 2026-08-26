<?php
/**
 * PDP review availability UX via named WooCommerce hooks.
 *
 * @package Biopentra_Upr_Host
 */

defined( 'ABSPATH' ) || exit;

final class Biopentra_Upr_Host_Review_Availability_Ux {

	public static function register(): void {
		add_filter( 'comments_open', array( __CLASS__, 'maybe_close_product_reviews' ), 20, 2 );
		add_action( 'woocommerce_before_single_product_reviews', array( __CLASS__, 'render_unavailable_message' ), 5 );
		add_filter( 'woocommerce_product_review_comment_form_args', array( __CLASS__, 'filter_comment_form_args' ) );
	}

	/**
	 * @param bool $open    Whether comments are open.
	 * @param int  $post_id Post ID.
	 */
	public static function maybe_close_product_reviews( $open, $post_id ): bool {
		$post_id = (int) $post_id;
		if ( ! $open || $post_id <= 0 || 'product' !== get_post_type( $post_id ) ) {
			return (bool) $open;
		}

		$availability = self::availability_for( $post_id );
		if ( empty( $availability['can_submit'] ) ) {
			return false;
		}
		return (bool) $open;
	}

	public static function render_unavailable_message(): void {
		if ( ! is_product() ) {
			return;
		}
		$product = wc_get_product( get_the_ID() );
		if ( ! $product ) {
			return;
		}

		$availability = self::availability_for( (int) $product->get_id() );
		if ( ! empty( $availability['can_submit'] ) ) {
			return;
		}

		$message = apply_filters(
			'upr_product_review_unavailable_message',
			null,
			(int) $product->get_id(),
			get_current_user_id(),
			$availability
		);

		if ( null === $message || '' === $message ) {
			$code = is_array( $availability ) ? (string) ( $availability['reason_code'] ?? '' ) : '';
			if ( 'guest_requires_invitation' === $code ) {
				$message = __( 'Product reviews are available by invitation after delivery.', 'biopentra-upr-host' );
			} elseif ( 'not_verified_purchaser' === $code ) {
				$message = __( 'Only verified purchasers can leave a review for this product.', 'biopentra-upr-host' );
			} elseif ( 'reviews_disabled' === $code ) {
				$message = __( 'Reviews are currently disabled for this product.', 'biopentra-upr-host' );
			} else {
				$message = __( 'New reviews are not being accepted for this product.', 'biopentra-upr-host' );
			}
		}

		echo '<p class="biopentra-upr-host-review-unavailable" role="status">' . esc_html( (string) $message ) . '</p>';
	}

	/**
	 * @param array<string, mixed> $args Form args.
	 * @return array<string, mixed>
	 */
	public static function filter_comment_form_args( array $args ): array {
		if ( ! is_product() ) {
			return $args;
		}
		$product_id   = (int) get_the_ID();
		$availability = self::availability_for( $product_id );
		if ( empty( $availability['can_submit'] ) ) {
			$args['title_reply'] = '';
		}
		return $args;
	}

	/**
	 * @param int $product_id Product ID.
	 * @return array{can_submit?:bool,reason_code?:?string,context?:array}
	 */
	private static function availability_for( int $product_id ): array {
		$default = array(
			'can_submit'  => true,
			'reason_code' => null,
			'context'     => array(),
		);
		$result  = apply_filters( 'upr_product_review_availability', $default, $product_id, get_current_user_id() );
		return is_array( $result ) ? $result : $default;
	}
}
