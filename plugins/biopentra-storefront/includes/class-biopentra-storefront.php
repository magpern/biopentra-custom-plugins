<?php
/**
 * Main plugin controller (scaffold).
 *
 * Future: load module classes from modules/{header-auth,footer-contact,information-megamenu,variation-stock-selector}/.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Biopentra_Storefront {

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize plugin (no modules registered yet).
	 */
	public function init() {
		// Intentionally empty: migration will require_once module bootstraps here.
	}
}
