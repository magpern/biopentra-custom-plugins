<?php
/**
 * Plugin Name: Biopentra Storefront
 * Description: Consolidated storefront modules (header auth, footer contact, mega-menu styles, variation selector). Logic migration pending — keep legacy plugins active until migration is complete.
 * Version: 0.1.0
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

define( 'BIOPENTRA_STOREFRONT_VERSION', '0.1.0' );
define( 'BIOPENTRA_STOREFRONT_PATH', plugin_dir_path( __FILE__ ) );
define( 'BIOPENTRA_STOREFRONT_URL', plugin_dir_url( __FILE__ ) );

require_once BIOPENTRA_STOREFRONT_PATH . 'includes/class-biopentra-storefront.php';

/**
 * Bootstrap (scaffold only — modules do not register hooks yet).
 */
function biopentra_storefront_init() {
	Biopentra_Storefront::instance()->init();
}
add_action( 'plugins_loaded', 'biopentra_storefront_init', 5 );
