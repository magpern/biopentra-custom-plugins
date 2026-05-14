<?php
/**
 * Footer contact: placeholder noindex, email shortcode with JS-built mailto.
 *
 * Migrated from biopentra-footer-contact (legacy plugin retained; do not run both active for cutover).
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mirrors legacy `biopentra-footer-contact.php` hooks and output.
 *
 * If `biopentra-footer-contact` is still active, it registers `[biopentra_footer_email]` first;
 * this module skips all registration to avoid duplicate shortcode / double filters.
 */
class Biopentra_Storefront_Footer_Contact_Module {

	/**
	 * Post meta key used by placeholder pages (unchanged contract).
	 */
	const PLACEHOLDER_META = '_biopentra_placeholder_page';

	/**
	 * Same script handle as legacy for predictable dequeue/replace behavior.
	 */
	const SCRIPT_HANDLE = 'biopentra-footer-contact-email';

	const SCRIPT_VERSION = '1.1.1';

	const SCRIPT_REL_PATH = 'assets/footer-contact/footer-contact-email.js';

	const IMAGE_REL_PATH = 'assets/footer-contact/bp-e1.png';

	/**
	 * Register hooks (idempotent if called once).
	 */
	public static function init() {
		if ( shortcode_exists( 'biopentra_footer_email' ) ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 5 );
		add_filter( 'wp_robots', array( __CLASS__, 'filter_wp_robots' ) );
		add_shortcode( 'biopentra_footer_email', array( __CLASS__, 'shortcode_email' ) );
	}

	/**
	 * Register script on front (priority 5, same as legacy).
	 */
	public static function register_assets() {
		if ( is_admin() ) {
			return;
		}

		if ( ! defined( 'BIOPENTRA_STOREFRONT_PATH' ) || ! defined( 'BIOPENTRA_STOREFRONT_URL' ) ) {
			return;
		}

		$js = BIOPENTRA_STOREFRONT_PATH . self::SCRIPT_REL_PATH;
		if ( ! is_readable( $js ) ) {
			return;
		}

		wp_register_script(
			self::SCRIPT_HANDLE,
			BIOPENTRA_STOREFRONT_URL . self::SCRIPT_REL_PATH,
			array(),
			self::SCRIPT_VERSION,
			true
		);
	}

	/**
	 * @return bool
	 */
	public static function is_placeholder_page() {
		if ( ! is_singular() ) {
			return false;
		}
		$id = get_queried_object_id();
		return (bool) ( $id && get_post_meta( $id, self::PLACEHOLDER_META, true ) === '1' );
	}

	/**
	 * @param array<string, bool|string> $robots Robots meta directives.
	 * @return array<string, bool|string>
	 */
	public static function filter_wp_robots( $robots ) {
		if ( self::is_placeholder_page() ) {
			$robots['noindex'] = true;
		}
		return $robots;
	}

	/**
	 * Shortcode: email row with JS mailto (no address in HTML).
	 *
	 * @param array<string, string>|string $atts Shortcode attributes (none used; accepted for parity).
	 * @return string
	 */
	public static function shortcode_email( $atts = array() ) {
		if ( wp_script_is( self::SCRIPT_HANDLE, 'registered' ) ) {
			wp_enqueue_script( self::SCRIPT_HANDLE );
		}

		$img_html = '';
		if ( defined( 'BIOPENTRA_STOREFRONT_PATH' ) && defined( 'BIOPENTRA_STOREFRONT_FILE' ) ) {
			$abs = BIOPENTRA_STOREFRONT_PATH . self::IMAGE_REL_PATH;
			if ( is_readable( $abs ) ) {
				$src = plugins_url( self::IMAGE_REL_PATH, BIOPENTRA_STOREFRONT_FILE );
				if ( function_exists( 'wp_is_using_https' ) && wp_is_using_https() ) {
					$src = set_url_scheme( $src, 'https' );
				} elseif ( is_ssl() ) {
					$src = set_url_scheme( $src, 'https' );
				}
				$w = 420;
				$h = 52;
				$img_html = sprintf(
					'<img src="%s" width="%d" height="%d" alt="" decoding="async" loading="lazy" />',
					esc_url( $src ),
					(int) $w,
					(int) $h
				);
			}
		}

		$label = esc_html__( 'Email', 'biopentra-footer-contact' );
		$aria  = esc_attr__( 'Send email to Biopentra', 'biopentra-footer-contact' );
		$form  = esc_html__( 'Contact form', 'biopentra-footer-contact' );
		$form_url = esc_url( home_url( '/contact' ) );

		return sprintf(
			'<div class="bp-ft-v2-contact-group">'
			. '<p class="bp-ft-v2-contact-label">%s</p>'
			. '<button type="button" class="biopentra-footer-email-btn" aria-label="%s">'
			. '<span class="biopentra-footer-email" role="presentation">%s</span></button>'
			. '<p class="bp-ft-v2-contact-form"><a href="%s">%s</a></p></div>',
			$label,
			$aria,
			$img_html,
			$form_url,
			$form
		);
	}
}
