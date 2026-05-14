<?php
/**
 * Plugin Name: Biopentra Storefront
 * Description: Consolidated storefront modules. Phase 1: Information mega-menu. Phase 2: Footer contact shortcode and placeholder noindex (legacy plugins retained; deactivate duplicates when activating this plugin).
 * Version: 0.2.0
 * Author: Biopentra
 * Text Domain: biopentra-storefront
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BIOPENTRA_STOREFRONT_VERSION', '0.2.0' );
define( 'BIOPENTRA_STOREFRONT_PATH', plugin_dir_path( __FILE__ ) );
define( 'BIOPENTRA_STOREFRONT_URL', plugin_dir_url( __FILE__ ) );
define( 'BIOPENTRA_STOREFRONT_FILE', __FILE__ );

require_once BIOPENTRA_STOREFRONT_PATH . 'includes/class-biopentra-storefront.php';

/**
 * Bootstrap storefront modules.
 */
function biopentra_storefront_init() {
	Biopentra_Storefront::instance()->init();
}
add_action( 'plugins_loaded', 'biopentra_storefront_init', 5 );
