<?php
/**
 * Plugin Name: Biopentra Storefront
 * Description: Consolidated storefront modules. Phases 1–4: mega-menu, footer contact, variation stock selector, header auth (legacy plugins retained; deactivate duplicates when activating this plugin).
 * Version: 0.5.3
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

define( 'BIOPENTRA_STOREFRONT_VERSION', '0.5.3' );
define( 'BIOPENTRA_STOREFRONT_PATH', plugin_dir_path( __FILE__ ) );
define( 'BIOPENTRA_STOREFRONT_URL', plugin_dir_url( __FILE__ ) );
define( 'BIOPENTRA_STOREFRONT_FILE', __FILE__ );

require_once BIOPENTRA_STOREFRONT_PATH . 'includes/class-biopentra-storefront.php';

/**
 * GitHub Release updater (admin / cron only).
 */
function biopentra_storefront_init_github_updater() {
	if ( ! is_admin() && ! ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) ) {
		return;
	}

	require_once BIOPENTRA_STOREFRONT_PATH . 'includes/class-github-updater.php';
	Biopentra_Storefront_Github_Updater::maybe_init();
}
add_action( 'plugins_loaded', 'biopentra_storefront_init_github_updater', 9 );

/**
 * Bootstrap storefront modules.
 */
function biopentra_storefront_init() {
	Biopentra_Storefront::instance()->init();
}
add_action( 'plugins_loaded', 'biopentra_storefront_init', 5 );
