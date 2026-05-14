<?php
/**
 * Information mega-menu: front-end stylesheet (migrated from biopentra-information-megamenu).
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and enqueues the Information mega-menu CSS.
 *
 * Mirrors legacy behavior: skip admin, `wp_enqueue_scripts` priority 25,
 * handle `biopentra-information-mega`, style version string `1.3.2`.
 */
class Biopentra_Storefront_Information_Megamenu_Module {

	/**
	 * Same handle as legacy plugin for drop-in compatibility.
	 */
	const STYLE_HANDLE = 'biopentra-information-mega';

	/**
	 * Same version string as legacy `wp_register_style` fourth argument.
	 */
	const STYLE_VERSION = '1.3.2';

	/**
	 * Relative path under plugin root (no leading slash).
	 */
	const STYLE_REL_PATH = 'assets/information-megamenu/information-mega.css';

	/**
	 * Register hook (idempotent if called once).
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 25 );
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

		$file = BIOPENTRA_STOREFRONT_PATH . self::STYLE_REL_PATH;
		if ( ! is_readable( $file ) ) {
			return;
		}

		wp_register_style(
			self::STYLE_HANDLE,
			BIOPENTRA_STOREFRONT_URL . self::STYLE_REL_PATH,
			array(),
			self::STYLE_VERSION
		);
		wp_enqueue_style( self::STYLE_HANDLE );
	}
}
