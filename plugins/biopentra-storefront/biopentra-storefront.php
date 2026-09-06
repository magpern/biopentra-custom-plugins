<?php
/**
 * Plugin Name: Biopentra Storefront
 * Description: Consolidated storefront modules: mega-menu, footer contact, variation stock selector, header auth, technical SEO, product stock display. Deactivate duplicate legacy plugins when active.
 * Version: 0.9.45
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

define( 'BIOPENTRA_STOREFRONT_VERSION', '0.9.45' );
define( 'BIOPENTRA_STOREFRONT_PATH', plugin_dir_path( __FILE__ ) );
define( 'BIOPENTRA_STOREFRONT_URL', plugin_dir_url( __FILE__ ) );
define( 'BIOPENTRA_STOREFRONT_FILE', __FILE__ );

require_once BIOPENTRA_STOREFRONT_PATH . 'includes/class-biopentra-storefront.php';

/**
 * Automatic updates via the private update server (admin / cron only). Define
 * PRIVATE_UPDATE_SERVER (scheme + host, no trailing slash) in wp-config.php to
 * enable; when it is not defined the plugin does not check for updates.
 */
function biopentra_storefront_init_github_updater() {
	if ( ! is_admin() && ! ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) ) {
		return;
	}

	if ( ! defined( 'PRIVATE_UPDATE_SERVER' ) || ! PRIVATE_UPDATE_SERVER ) {
		return;
	}

	require_once BIOPENTRA_STOREFRONT_PATH . 'lib/plugin-update-checker/plugin-update-checker.php';
	\YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		rtrim( (string) PRIVATE_UPDATE_SERVER, '/' ) . '/?action=get_metadata&slug=biopentra-storefront',
		BIOPENTRA_STOREFRONT_FILE,
		'biopentra-storefront'
	);
}
add_action( 'plugins_loaded', 'biopentra_storefront_init_github_updater', 9 );

/**
 * Bootstrap storefront modules.
 */
function biopentra_storefront_init() {
	Biopentra_Storefront::instance()->init();
}
add_action( 'plugins_loaded', 'biopentra_storefront_init', 5 );
