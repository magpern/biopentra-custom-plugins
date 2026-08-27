<?php
/**
 * PDP review availability UX via named WooCommerce hooks.
 *
 * Decouples approved-review display from submission gating (M3 PDP reviews freeze).
 * Does not close comments_open for UPR availability — that hid native #reviews lists.
 *
 * @package Biopentra_Upr_Host
 */

defined( 'ABSPATH' ) || exit;

final class Biopentra_Upr_Host_Review_Availability_Ux {

	/**
	 * After UPR GuestSubmissionGuard (priority 5) so guests are handled by UPR first.
	 */
	public const PREPROCESS_PRIORITY = 15;

	public static function register(): void {
		add_action( 'woocommerce_before_single_product_reviews', array( __CLASS__, 'render_unavailable_message' ), 5 );
		add_filter( 'woocommerce_product_review_comment_form_args', array( __CLASS__, 'filter_comment_form_args' ) );
		add_filter( 'preprocess_comment', array( __CLASS__, 'reject_unavailable_native_product_review' ), self::PREPROCESS_PRIORITY );
	}

	/**
	 * Display-only: whether the native PDP review form may be rendered.
	 *
	 * Delegates eligibility to `upr_product_review_availability`. Does not authorize
	 * submission. Guests never receive the native PDP form (M2 path is `/upr-review/form/` only).
	 *
	 * @param int $product_id Product ID.
	 */
	public static function can_submit_for_product( int $product_id ): bool {
		if ( $product_id <= 0 ) {
			return false;
		}

		$user_id      = get_current_user_id();
		$availability = self::availability_for( $product_id, $user_id );

		if ( empty( $availability['can_submit'] ) ) {
			return false;
		}

		if ( $user_id <= 0 ) {
			return false;
		}

		$authorization = is_array( $availability['context'] ?? null )
			? ( $availability['context']['authorization'] ?? null )
			: null;
		if ( 'form_session' === $authorization ) {
			return false;
		}

		return true;
	}

	public static function render_unavailable_message(): void {
		if ( ! is_product() ) {
			return;
		}
		$product = wc_get_product( get_the_ID() );
		if ( ! $product ) {
			return;
		}

		$product_id   = (int) $product->get_id();
		$availability = self::availability_for( $product_id, get_current_user_id() );

		// Show messaging when native PDP form must not appear (includes guests with M2 session).
		if ( self::can_submit_for_product( $product_id ) ) {
			return;
		}

		$message = apply_filters(
			'upr_product_review_unavailable_message',
			null,
			$product_id,
			get_current_user_id(),
			$availability
		);

		if ( null === $message || '' === $message ) {
			$code = is_array( $availability ) ? (string) ( $availability['reason_code'] ?? '' ) : '';
			if ( 'guest_requires_invitation' === $code ) {
				$message = __( 'Product reviews are available by invitation after delivery.', 'biopentra-upr-host' );
			} elseif ( 'not_verified_purchaser' === $code ) {
				$message = __( 'Only verified purchasers can leave a review for this product.', 'biopentra-upr-host' );
			} elseif ( 'product_not_reviewable' === $code ) {
				$message = __( 'This product is no longer accepting new reviews.', 'biopentra-upr-host' );
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
		$product_id = (int) get_the_ID();
		if ( ! self::can_submit_for_product( $product_id ) ) {
			$args['title_reply'] = '';
		}
		return $args;
	}

	/**
	 * Reject native product-review POSTs when UPR availability says can_submit=false.
	 *
	 * Closes the logged-in gap left by UPR GuestSubmissionGuard (guests only).
	 * Does not invent authorization rules — mirrors upr_product_review_availability.
	 *
	 * @param array<string, mixed> $commentdata Comment data.
	 * @return array<string, mixed>
	 */
	public static function reject_unavailable_native_product_review( array $commentdata ): array {
		if ( ! self::is_product_review_comment( $commentdata ) ) {
			return $commentdata;
		}

		$product_id   = (int) ( $commentdata['comment_post_ID'] ?? 0 );
		$user_id      = get_current_user_id();
		$availability = self::availability_for( $product_id, $user_id );

		if ( ! empty( $availability['can_submit'] ) ) {
			return $commentdata;
		}

		wp_die(
			esc_html__( 'Product review submission is not available for this product.', 'biopentra-upr-host' ),
			esc_html__( 'Review submission unavailable', 'biopentra-upr-host' ),
			array( 'response' => 403 )
		);
	}

	/**
	 * @param array<string, mixed> $commentdata Comment data.
	 */
	private static function is_product_review_comment( array $commentdata ): bool {
		$post_id = (int) ( $commentdata['comment_post_ID'] ?? 0 );
		if ( $post_id <= 0 || 'product' !== get_post_type( $post_id ) ) {
			return false;
		}

		// After WC_Comments::update_comment_type (priority 1), product reviews are type "review".
		$comment_type = isset( $commentdata['comment_type'] ) ? (string) $commentdata['comment_type'] : '';
		return 'review' === $comment_type;
	}

	/**
	 * @param int $product_id Product ID.
	 * @param int $user_id    User ID (0 = guest).
	 * @return array{can_submit?:bool,reason_code?:?string,context?:array}
	 */
	private static function availability_for( int $product_id, int $user_id ): array {
		$default = array(
			'can_submit'  => true,
			'reason_code' => null,
			'context'     => array(),
		);
		$result  = apply_filters( 'upr_product_review_availability', $default, $product_id, $user_id );
		return is_array( $result ) ? $result : $default;
	}
}
