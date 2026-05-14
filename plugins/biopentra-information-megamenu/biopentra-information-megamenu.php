<?php
/**
 * Plugin Name: Biopentra Information Mega-menu
 * Description: Scoped styles for the Elementor Information mega-menu panel (header template).
 * Version: 1.3.2
 * Author: Biopentra
 * Text Domain: biopentra-megamenu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BIOPENTRA_INFO_MEGA_URL', plugin_dir_url( __FILE__ ) );
define( 'BIOPENTRA_INFO_MEGA_PATH', plugin_dir_path( __FILE__ ) );

function biopentra_info_mega_enqueue_assets() {
	if ( is_admin() ) {
		return;
	}
	wp_register_style(
		'biopentra-information-mega',
		BIOPENTRA_INFO_MEGA_URL . 'assets/information-mega.css',
		array(),
		'1.3.2'
	);
	wp_enqueue_style( 'biopentra-information-mega' );
}
add_action( 'wp_enqueue_scripts', 'biopentra_info_mega_enqueue_assets', 25 );
