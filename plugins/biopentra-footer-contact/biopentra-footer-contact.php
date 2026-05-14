<?php
/**
 * Plugin Name: Biopentra Footer Contact
 * Description: Placeholder-page noindex helper; footer email image with JS-built mailto (no address in HTML).
 * Version: 1.1.1
 * Author: Biopentra
 * Text Domain: biopentra-footer-contact
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BIOPENTRA_FOOTER_CONTACT_VERSION', '1.1.1' );
define( 'BIOPENTRA_FOOTER_CONTACT_PATH', plugin_dir_path( __FILE__ ) );
define( 'BIOPENTRA_FOOTER_CONTACT_URL', plugin_dir_url( __FILE__ ) );

const BIOPENTRA_PLACEHOLDER_META = '_biopentra_placeholder_page';

function biopentra_footer_contact_register_assets() {
	wp_register_script(
		'biopentra-footer-contact-email',
		BIOPENTRA_FOOTER_CONTACT_URL . 'assets/footer-contact-email.js',
		array(),
		BIOPENTRA_FOOTER_CONTACT_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'biopentra_footer_contact_register_assets', 5 );

function biopentra_footer_contact_is_placeholder_page() {
	if ( ! is_singular() ) {
		return false;
	}
	$id = get_queried_object_id();
	return $id && get_post_meta( $id, BIOPENTRA_PLACEHOLDER_META, true ) === '1';
}

function biopentra_footer_contact_wp_robots( $robots ) {
	if ( biopentra_footer_contact_is_placeholder_page() ) {
		$robots['noindex'] = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'biopentra_footer_contact_wp_robots' );

function biopentra_footer_contact_shortcode_email() {
	wp_enqueue_script( 'biopentra-footer-contact-email' );

	$src = plugins_url( 'assets/bp-e1.png', __FILE__ );
	if ( function_exists( 'wp_is_using_https' ) && wp_is_using_https() ) {
		$src = set_url_scheme( $src, 'https' );
	} elseif ( is_ssl() ) {
		$src = set_url_scheme( $src, 'https' );
	}
	$w = 420;
	$h = 52;

	$label = esc_html__( 'Email', 'biopentra-footer-contact' );
	$aria  = esc_attr__( 'Send email to Biopentra', 'biopentra-footer-contact' );
	$form  = esc_html__( 'Contact form', 'biopentra-footer-contact' );
	$form_url = esc_url( home_url( '/contact' ) );

	return sprintf(
		'<div class="bp-ft-v2-contact-group">'
		. '<p class="bp-ft-v2-contact-label">%s</p>'
		. '<button type="button" class="biopentra-footer-email-btn" aria-label="%s">'
		. '<span class="biopentra-footer-email" role="presentation">'
		. '<img src="%s" width="%d" height="%d" alt="" decoding="async" loading="lazy" />'
		. '</span></button>'
		. '<p class="bp-ft-v2-contact-form"><a href="%s">%s</a></p></div>',
		$label,
		$aria,
		esc_url( $src ),
		(int) $w,
		(int) $h,
		$form_url,
		$form
	);
}
add_shortcode( 'biopentra_footer_email', 'biopentra_footer_contact_shortcode_email' );
