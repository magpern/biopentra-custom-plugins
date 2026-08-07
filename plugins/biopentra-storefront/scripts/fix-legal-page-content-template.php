<?php
/**
 * Fix — legal/info pages losing the theme's boxed content column.
 *
 * A handful of plain-content pages (Privacy Policy, Cookie Policy, Terms &
 * Conditions, Refund Policy, Shipping Policy, FAQ, Research Use Disclaimer)
 * have `_wp_page_template = elementor_header_footer` (Elementor's edge-to-
 * edge "Full Width" canvas) but hold plain WordPress content — `_elementor_data`
 * is empty for all of them. That template intentionally skips the theme's
 * `.entry-content` boxed/constrained wrapper because it expects Elementor
 * itself to supply a container; with none present, the raw HTML renders
 * unconstrained to the viewport edge instead of in the theme's normal
 * centered column (compare a working page like Contact, which uses the
 * theme's `default` template and keeps `entry-content is-layout-constrained`).
 *
 * Fix: set these pages' `_wp_page_template` to `default`, matching every
 * other plain-content page on the site. Only pages that (a) are in the
 * target slug list, (b) still use `elementor_header_footer`, and (c) still
 * have no real Elementor data are touched — idempotent and safe to re-run.
 *
 * Switching to `default` exposes Blocksy's own page-title/hero bar, which
 * duplicates the `<h1>` these pages already have in their own content
 * (confirmed visually after the template fix). Blocksy's own
 * `has_hero_section` post option (default `'default'` = show) controls
 * this; the Contact page already sets it to `'disabled'` for exactly this
 * reason (it renders its own heading via Elementor). This script sets the
 * same option for these 7 pages so only their own content `<h1>` shows.
 *
 * Usage:
 *   wp eval-file wp-content/plugins/biopentra-storefront/scripts/fix-legal-page-content-template.php
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slugs = array(
	'privacy-policy',
	'cookie-policy',
	'terms-and-conditions',
	'refund-policy',
	'shipping-policy',
	'faq',
	'research-use-disclaimer',
);

$changed = 0;
$skipped = 0;

foreach ( $slugs as $slug ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( ! $page ) {
		echo "WARN: page not found for slug '{$slug}'\n";
		continue;
	}

	$template      = get_post_meta( $page->ID, '_wp_page_template', true );
	$elementor     = get_post_meta( $page->ID, '_elementor_data', true );
	$has_elementor = ! empty( $elementor ) && '[]' !== $elementor;

	if ( $has_elementor ) {
		echo "SKIP {$page->ID} ({$slug}): has real Elementor data — not a plain-content page, leaving alone\n";
		++$skipped;
		continue;
	}

	$did_something = false;

	if ( 'elementor_header_footer' === $template ) {
		update_post_meta( $page->ID, '_wp_page_template', 'default' );
		echo "FIXED {$page->ID} ({$slug}): _wp_page_template elementor_header_footer -> default\n";
		$did_something = true;
	}

	$blocksy_options = get_post_meta( $page->ID, 'blocksy_post_meta_options', true );
	if ( ! is_array( $blocksy_options ) ) {
		$blocksy_options = array();
	}
	if ( ( $blocksy_options['has_hero_section'] ?? '' ) !== 'disabled' ) {
		$blocksy_options['has_hero_section'] = 'disabled';
		update_post_meta( $page->ID, 'blocksy_post_meta_options', $blocksy_options );
		echo "FIXED {$page->ID} ({$slug}): Blocksy has_hero_section -> disabled (removes duplicate <h1>)\n";
		$did_something = true;
	}

	if ( $did_something ) {
		++$changed;
	} else {
		echo "SKIP {$page->ID} ({$slug}): already correct\n";
		++$skipped;
	}
}

if ( class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}

echo "Done: {$changed} fixed, {$skipped} skipped.\n";
echo "OK\n";
