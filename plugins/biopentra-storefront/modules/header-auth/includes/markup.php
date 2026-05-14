<?php
/**
 * Shared HTML for header auth (Elementor widget + shortcode).
 *
 * @package Biopentra_Header_Auth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public “Log in” URL: WooCommerce My Account when available, else wp-login.php.
 *
 * @return string
 */
function biopentra_header_auth_login_url() {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( 'myaccount' );
		if ( $url ) {
			return $url;
		}
	}
	return wp_login_url( home_url( '/' ) );
}

/**
 * “My account” destination (WooCommerce account page or profile fallback).
 *
 * @return string
 */
function biopentra_header_auth_my_account_url() {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( 'myaccount' );
		if ( $url ) {
			return $url;
		}
	}
	return admin_url( 'profile.php' );
}

/**
 * Inline user-outline icon (matches header strip; currentColor for styling).
 *
 * @return string
 */
function biopentra_header_auth_icon_html() {
	return '<span class="biopentra-header-auth__icon" aria-hidden="true"><svg class="biopentra-header-auth__svg" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="8" r="3.25" stroke="currentColor" stroke-width="1.5"/><path d="M5.5 20.25c0-3.6 2.9-5.5 6.5-5.5s6.5 1.9 6.5 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></span>';
}

/**
 * Label shown on the trigger when logged in.
 *
 * @param \WP_User $user       Current user.
 * @param string   $identifier One of email|display_name|user_login.
 * @return string
 */
function biopentra_header_auth_user_trigger_label( $user, $identifier ) {
	switch ( $identifier ) {
		case 'display_name':
			return $user->display_name ? $user->display_name : $user->user_login;
		case 'user_login':
			return $user->user_login;
		case 'email':
		default:
			if ( ! empty( $user->user_email ) ) {
				return $user->user_email;
			}
			if ( $user->display_name ) {
				return $user->display_name;
			}
			return $user->user_login;
	}
}

/**
 * Default two-item dropdown (shortcode / fallback when Elementor repeater empty).
 *
 * @param array $args Same keys as biopentra_header_auth_get_html defaults.
 * @return string HTML inner markup for .biopentra-header-auth__dropdown.
 */
function biopentra_header_auth_default_dropdown_html( array $args ) {
	$account = biopentra_header_auth_my_account_url();
	$logout  = wp_logout_url( home_url( '/' ) );

	return sprintf(
		'%1$s%2$s',
		sprintf(
			'<a role="menuitem" class="biopentra-header-auth__menuitem biopentra-header-auth__menuitem--row" href="%1$s"><span class="biopentra-header-auth__menu-text">%2$s</span></a>',
			esc_url( $account ),
			esc_html( $args['my_account_text'] )
		),
		sprintf(
			'<a role="menuitem" class="biopentra-header-auth__menuitem biopentra-header-auth__menuitem--row biopentra-header-auth__menuitem--danger" href="%1$s"><span class="biopentra-header-auth__menu-text">%2$s</span></a>',
			esc_url( $logout ),
			esc_html( $args['logout_text'] )
		)
	);
}

/**
 * @param array $args {
 *     @type string $login_text       Logged-out link label.
 *     @type string $logout_text      Dropdown log-out label (shortcode default menu).
 *     @type string $my_account_text  Dropdown my-account label (shortcode default menu).
 *     @type string $identifier       Logged-in trigger: email|display_name|user_login.
 *     @type string $dropdown_html    Optional. Full inner HTML for dropdown (Elementor repeater output).
 *     @type array  $login_link       Optional. Elementor URL control: url, is_external, nofollow (override logged-out href).
 * }
 * @return string HTML.
 */
function biopentra_header_auth_get_html( array $args = array() ) {
	$defaults = array(
		'login_text'      => __( 'Log in', 'biopentra-header-auth' ),
		'logout_text'     => __( 'Log out', 'biopentra-header-auth' ),
		'my_account_text' => __( 'My account', 'biopentra-header-auth' ),
		'identifier'      => 'email',
		'dropdown_html'   => '',
		'login_link'      => array(),
	);
	$args = array_merge( $defaults, $args );
	$args = array(
		'login_text'      => wp_strip_all_tags( (string) $args['login_text'] ),
		'logout_text'     => wp_strip_all_tags( (string) $args['logout_text'] ),
		'my_account_text' => wp_strip_all_tags( (string) $args['my_account_text'] ),
		'identifier'      => in_array( $args['identifier'], array( 'email', 'display_name', 'user_login' ), true )
			? $args['identifier']
			: 'email',
		'dropdown_html'   => is_string( $args['dropdown_html'] ) ? $args['dropdown_html'] : '',
		'login_link'      => is_array( $args['login_link'] ) ? $args['login_link'] : array(),
	);

	wp_enqueue_style( 'biopentra-header-auth' );

	$icon = biopentra_header_auth_icon_html();

	if ( is_user_logged_in() ) {
		wp_enqueue_script( 'biopentra-header-auth' );

		$user    = wp_get_current_user();
		$label   = biopentra_header_auth_user_trigger_label( $user, $args['identifier'] );
		$uid     = wp_unique_id( 'bph-' );
		$btn_id  = 'bph-trigger-' . $uid;
		$menu_id = 'bph-menu-' . $uid;

		$inner = '' !== trim( $args['dropdown_html'] )
			? $args['dropdown_html']
			: biopentra_header_auth_default_dropdown_html( $args );

		return sprintf(
			'<div class="biopentra-header-auth biopentra-header-auth--in" data-bph-auth="1"><button type="button" class="biopentra-header-auth__trigger" id="%1$s" aria-expanded="false" aria-haspopup="true" aria-controls="%2$s" aria-label="%3$s"><span class="biopentra-header-auth__label">%4$s</span>%5$s</button><div class="biopentra-header-auth__dropdown" id="%2$s" role="menu" aria-labelledby="%1$s" hidden>%6$s</div></div>',
			esc_attr( $btn_id ),
			esc_attr( $menu_id ),
			esc_attr__( 'Account menu', 'biopentra-header-auth' ),
			esc_html( $label ),
			$icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			$inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_* per item in callers.
		);
	}

	$in      = biopentra_header_auth_login_url();
	$target  = '';
	$rel     = '';
	$link    = $args['login_link'];
	if ( ! empty( $link['url'] ) ) {
		$in = $link['url'];
		if ( ! empty( $link['is_external'] ) ) {
			$target = ' target="_blank"';
			$rel    = ' rel="noopener noreferrer"';
			if ( ! empty( $link['nofollow'] ) ) {
				$rel = ' rel="noopener noreferrer nofollow"';
			}
		}
	}

	return sprintf(
		'<div class="biopentra-header-auth biopentra-header-auth--out" data-bph-auth="1"><a class="biopentra-header-auth__trigger" href="%1$s"%4$s%5$s><span class="biopentra-header-auth__label">%2$s</span>%3$s</a></div>',
		esc_url( $in ),
		esc_html( $args['login_text'] ),
		$icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
		$target, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$rel // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}
