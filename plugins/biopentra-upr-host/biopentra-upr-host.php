<?php
/**
 * Plugin Name: Biopentra UPR Host
 * Description: Biopentra host adapters for Universal Product Reviews (delivery, support, DEV mail safety).
 * Version: 0.1.0
 * Author: Biopentra
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce, universal-product-reviews
 * Text Domain: biopentra-upr-host
 * License: GPL-2.0-or-later
 *
 * @package Biopentra_Upr_Host
 */

defined( 'ABSPATH' ) || exit;

define( 'BIOPENTRA_UPR_HOST_VERSION', '0.1.0' );
define( 'BIOPENTRA_UPR_HOST_FILE', __FILE__ );
define( 'BIOPENTRA_UPR_HOST_PATH', plugin_dir_path( __FILE__ ) );

require_once BIOPENTRA_UPR_HOST_PATH . 'includes/class-options.php';
require_once BIOPENTRA_UPR_HOST_PATH . 'includes/class-delivery-adapter.php';
require_once BIOPENTRA_UPR_HOST_PATH . 'includes/class-support-adapter.php';
require_once BIOPENTRA_UPR_HOST_PATH . 'includes/class-review-availability-ux.php';
require_once BIOPENTRA_UPR_HOST_PATH . 'includes/class-admin-settings.php';
require_once BIOPENTRA_UPR_HOST_PATH . 'includes/class-plugin.php';

add_action(
	'plugins_loaded',
	static function (): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		Biopentra_Upr_Host_Plugin::instance()->init();
	},
	20
);

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once BIOPENTRA_UPR_HOST_PATH . 'cli/class-verify-dev-mail-command.php';
	WP_CLI::add_command( 'biopentra-upr-host verify-dev-mail', 'Biopentra_Upr_Host_Verify_Dev_Mail_Command' );
}
