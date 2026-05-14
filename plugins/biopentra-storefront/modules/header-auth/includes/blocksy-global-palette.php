<?php
/**
 * One-time migration: Blocksy “Global Color Palette” → Biopentra brand (matches account/checkout blues).
 *
 * Blocksy stores this as the theme mod `colorPalette` (keys color1–color8 → CSS vars --theme-palette-color-*).
 * Re-run by deleting the option `biopentra_header_auth_blocksy_palette_applied` (then load wp-admin once).
 *
 * @package Biopentra_Header_Auth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Biopentra global palette (color1 = primary accent, color2 = darker accent, 3–4 text/dark, 5–8 neutrals).
 *
 * @return array<string, array{color: string}>
 */
function biopentra_header_auth_get_blocksy_color_palette_value() {
	return array(
		'color1' => array( 'color' => '#1f5fae' ),
		'color2' => array( 'color' => '#174a87' ),
		'color3' => array( 'color' => '#3d4f66' ),
		'color4' => array( 'color' => '#0f1f33' ),
		'color5' => array( 'color' => '#e1e8ed' ),
		'color6' => array( 'color' => '#f2f5f7' ),
		'color7' => array( 'color' => '#FAFBFC' ),
		'color8' => array( 'color' => '#ffffff' ),
	);
}

/**
 * Apply Blocksy global palette once (admin only), when the active theme (or parent) is Blocksy.
 */
function biopentra_header_auth_maybe_apply_blocksy_global_palette() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( apply_filters( 'biopentra_header_auth_skip_blocksy_palette_migration', false ) ) {
		return;
	}
	if ( get_option( 'biopentra_header_auth_blocksy_palette_applied', '' ) === '1' ) {
		return;
	}
	$theme  = wp_get_theme();
	$parent = $theme->get_template();
	if ( $parent !== 'blocksy' ) {
		return;
	}

	set_theme_mod( 'colorPalette', biopentra_header_auth_get_blocksy_color_palette_value() );
	update_option( 'biopentra_header_auth_blocksy_palette_applied', '1', false );
}
