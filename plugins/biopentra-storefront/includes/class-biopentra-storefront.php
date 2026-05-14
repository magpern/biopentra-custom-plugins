<?php
/**
 * Main plugin controller.
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
	 * Load storefront modules (safe if optional files are absent).
	 */
	public function init() {
		$module_file = BIOPENTRA_STOREFRONT_PATH . 'modules/information-megamenu/class-information-megamenu-module.php';
		if ( is_readable( $module_file ) ) {
			require_once $module_file;
			if ( class_exists( 'Biopentra_Storefront_Information_Megamenu_Module' ) ) {
				Biopentra_Storefront_Information_Megamenu_Module::init();
			}
		}

		$footer_module = BIOPENTRA_STOREFRONT_PATH . 'modules/footer-contact/class-footer-contact-module.php';
		if ( is_readable( $footer_module ) ) {
			require_once $footer_module;
			if ( class_exists( 'Biopentra_Storefront_Footer_Contact_Module' ) ) {
				Biopentra_Storefront_Footer_Contact_Module::init();
			}
		}
	}
}
