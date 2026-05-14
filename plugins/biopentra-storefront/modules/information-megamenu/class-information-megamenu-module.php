<?php
/**
 * Information mega-menu: front-end stylesheet and script (migrated from biopentra-information-megamenu).
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and enqueues the Information mega-menu CSS and JS.
 *
 * CSS mirrors legacy: skip admin, `wp_enqueue_scripts` priority 25,
 * style handle `biopentra-information-mega`, version `1.3.2`.
 *
 * JS uses a **new** script handle `biopentra-storefront-information-mega` (WordPress appends `-js` to id attribute).
 * Loaded in footer with `defer` via `script_loader_tag` for broad WP compatibility.
 *
 * Duplicate protection: Elementor may still contain an HTML widget with
 * `<script src="…/biopentra-information-megamenu/assets/information-mega.js">`.
 * The runtime file sets `window.__BIOPENTRA_INFO_MEGA_JS__` so a second execution is skipped;
 * both URLs may still download until the Elementor widget is removed — see
 * `docs/information-mega-js-cutover-plan.md`.
 */
class Biopentra_Storefront_Information_Megamenu_Module {

	/**
	 * Same style handle as legacy plugin for drop-in CSS compatibility.
	 */
	const STYLE_HANDLE = 'biopentra-information-mega';

	const STYLE_VERSION = '1.3.2';

	const STYLE_REL_PATH = 'assets/information-megamenu/information-mega.css';

	/**
	 * New script handle (distinct from legacy Elementor-injected URL).
	 */
	const SCRIPT_HANDLE = 'biopentra-storefront-information-mega';

	const SCRIPT_VERSION = '1.3.2';

	const SCRIPT_REL_PATH = 'assets/information-megamenu/information-mega.js';

	/**
	 * Register hooks (idempotent if called once).
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 25 );
		add_filter( 'script_loader_tag', array( __CLASS__, 'filter_script_defer' ), 10, 2 );
	}

	/**
	 * Front-end only; no-op in admin (matches legacy `is_admin()` guard).
	 */
	public static function enqueue_assets() {
		if ( is_admin() ) {
			return;
		}

		if ( ! defined( 'BIOPENTRA_STOREFRONT_PATH' ) || ! defined( 'BIOPENTRA_STOREFRONT_URL' ) ) {
			return;
		}

		$css_file = BIOPENTRA_STOREFRONT_PATH . self::STYLE_REL_PATH;
		if ( is_readable( $css_file ) ) {
			wp_register_style(
				self::STYLE_HANDLE,
				BIOPENTRA_STOREFRONT_URL . self::STYLE_REL_PATH,
				array(),
				self::STYLE_VERSION
			);
			wp_enqueue_style( self::STYLE_HANDLE );
		}

		$js_file = BIOPENTRA_STOREFRONT_PATH . self::SCRIPT_REL_PATH;
		if ( ! is_readable( $js_file ) ) {
			return;
		}

		wp_register_script(
			self::SCRIPT_HANDLE,
			BIOPENTRA_STOREFRONT_URL . self::SCRIPT_REL_PATH,
			array(),
			self::SCRIPT_VERSION,
			true
		);
		wp_enqueue_script( self::SCRIPT_HANDLE );
	}

	/**
	 * Add defer to the storefront mega-menu script tag (footer load).
	 *
	 * @param string $tag    Full script tag.
	 * @param string $handle Script handle.
	 * @return string
	 */
	public static function filter_script_defer( $tag, $handle ) {
		if ( self::SCRIPT_HANDLE !== $handle ) {
			return $tag;
		}
		if ( strpos( $tag, ' defer' ) !== false ) {
			return $tag;
		}
		return str_replace( '<script ', '<script defer ', $tag, 1 );
	}
}
