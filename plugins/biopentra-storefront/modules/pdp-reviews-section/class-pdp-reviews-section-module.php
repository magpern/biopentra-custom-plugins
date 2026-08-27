<?php
/**
 * Dedicated PDP reviews section for standard Blocksy product templates.
 *
 * Renders native WooCommerce review markup via a direct-loaded template fork
 * at woocommerce_after_single_product_summary priority 12 (tabs remain off).
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Biopentra_Storefront_Pdp_Reviews_Section_Module {

	public const ACTION_PRIORITY = 12;

	/**
	 * Register hooks.
	 */
	public static function init() {
		if ( ! function_exists( 'is_product' ) ) {
			return;
		}

		add_action( 'woocommerce_after_single_product_summary', array( __CLASS__, 'render_section' ), self::ACTION_PRIORITY );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 40 );
	}

	/**
	 * Enqueue hash/focus helper on product pages only.
	 */
	public static function enqueue_assets() {
		if ( ! is_product() || self::is_elementor_product_document() ) {
			return;
		}

		$rel  = 'assets/js/pdp-reviews-section.js';
		$path = BIOPENTRA_STOREFRONT_PATH . $rel;
		if ( ! is_readable( $path ) ) {
			return;
		}

		wp_enqueue_script(
			'biopentra-storefront-pdp-reviews-section',
			BIOPENTRA_STOREFRONT_URL . $rel,
			array(),
			BIOPENTRA_STOREFRONT_VERSION,
			true
		);
	}

	/**
	 * Render dedicated #reviews section (Blocksy standard PDPs only).
	 */
	public static function render_section() {
		if ( ! self::should_render_section() ) {
			return;
		}

		$product = wc_get_product( get_the_ID() );
		if ( ! $product ) {
			return;
		}

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- required for WC reviews template.
		$GLOBALS['product'] = $product;

		echo '<section class="bp-pdp-reviews-section" data-bp-reviews-section="1">';

		$template = BIOPENTRA_STOREFRONT_PATH . 'templates/woocommerce/single-product-reviews.php';
		$filter   = static function () use ( $template ) {
			return $template;
		};

		/*
		 * Use comments_template() so WP sets up the comment query the same way as
		 * the native reviews tab, but force our direct-load fork (not theme-locatable).
		 */
		add_filter( 'comments_template', $filter, 1000 );
		comments_template();
		remove_filter( 'comments_template', $filter, 1000 );

		echo '</section>';
	}

	/**
	 * Whether the native PDP review form may render (display-only).
	 *
	 * Prefers UPR NativePdpForm; falls back to host thin wrapper; fail-closed.
	 *
	 * @param int $product_id Product ID.
	 */
	public static function should_render_native_form( $product_id ) {
		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			return false;
		}

		if ( class_exists( \UniversalProductReviews\Submission\NativePdpForm::class ) ) {
			return \UniversalProductReviews\Submission\NativePdpForm::should_render( $product_id );
		}

		if ( class_exists( 'Biopentra_Upr_Host_Review_Availability_Ux' ) ) {
			return Biopentra_Upr_Host_Review_Availability_Ux::can_submit_for_product( $product_id );
		}

		return false;
	}

	/**
	 * @return bool
	 */
	private static function should_render_section() {
		if ( ! is_product() ) {
			return false;
		}

		if ( self::is_elementor_product_document() ) {
			return false;
		}

		return true;
	}

	/**
	 * Elementor product documents are out of Phase 1 scope.
	 *
	 * @return bool
	 */
	private static function is_elementor_product_document() {
		$post_id = get_the_ID();
		if ( $post_id <= 0 ) {
			return false;
		}

		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->documents ) ) {
			$document = \Elementor\Plugin::$instance->documents->get( $post_id );
			if ( $document && method_exists( $document, 'is_built_with_elementor' ) && $document->is_built_with_elementor() ) {
				return true;
			}
		}

		return 'builder' === get_post_meta( $post_id, '_elementor_edit_mode', true );
	}
}
