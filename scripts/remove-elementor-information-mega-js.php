<?php
/**
 * Remove legacy Elementor HTML-widget injection of information-mega.js
 * (biopentra-information-megamenu URL) from _elementor_data and _elementor_element_cache.
 *
 * Dry-run by default. Set BIOPENTRA_ELEMENTOR_CLEANUP_APPLY=1 to write.
 *
 * Example (from WooCommerce docker project root):
 *   docker compose run --rm --no-deps \
 *     -e BIOPENTRA_ELEMENTOR_CLEANUP_APPLY=1 \
 *     -v /path/to/custom-wordpress-plugins/scripts/remove-elementor-information-mega-js.php:/tmp/r.php:ro \
 *     wpcli eval-file /tmp/r.php
 *
 * @package BiopentraCustomPlugins
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$apply = getenv( 'BIOPENTRA_ELEMENTOR_CLEANUP_APPLY' ) === '1';

// Exact substring as stored inside Elementor JSON / cached HTML (JSON-escaped quotes and slashes).
$needle = '<script defer src=\"http:\/\/www.biopentra.eu\/wp-content\/plugins\/biopentra-information-megamenu\/assets\/information-mega.js?ver=1.3.2\"><\/script>';

$data_post_ids    = array( 3782, 4132, 4289, 4296, 4297, 4299, 4300 );
$cache_post_ids   = array( 3782 );
$data_meta_key    = '_elementor_data';
$cache_meta_key   = '_elementor_element_cache';

$changed = 0;

/**
 * Write postmeta without update_post_meta(): for `revision` posts, update_post_meta()
 * can report success while leaving the row unchanged (observed on WP 6.x + Elementor).
 *
 * @param int    $meta_id Meta row ID.
 * @param string $new_val Unslashed meta value to store.
 * @return void
 */
$write_postmeta = static function ( $post_id, $meta_id, $new_val ) {
	global $wpdb;
	$wpdb->update(
		$wpdb->postmeta,
		array( 'meta_value' => $new_val ),
		array( 'meta_id' => (int) $meta_id ),
		array( '%s' ),
		array( '%d' )
	);
	wp_cache_delete( (int) $post_id, 'post_meta' );
};

foreach ( $data_post_ids as $post_id ) {
	global $wpdb;
	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1",
			$post_id,
			$data_meta_key
		),
		ARRAY_A
	);
	if ( ! $row || ! isset( $row['meta_value'], $row['meta_id'] ) ) {
		continue;
	}
	$raw = (string) $row['meta_value'];
	if ( $raw === '' || strpos( $raw, $needle ) === false ) {
		continue;
	}
	$new = str_replace( $needle, '', $raw, $count );
	if ( $count < 1 ) {
		continue;
	}
	$decoded = json_decode( $new, true );
	if ( ! is_array( $decoded ) ) {
		fwrite( STDERR, "ABORT: post {$post_id} {$data_meta_key} would become invalid JSON after removal.\n" );
		exit( 1 );
	}
	if ( $apply ) {
		$write_postmeta( (int) $post_id, (int) $row['meta_id'], $new );
	}
	++$changed;
	echo ( $apply ? 'UPDATED' : 'DRY-RUN' ) . " post_id={$post_id} meta={$data_meta_key} meta_id={$row['meta_id']} replacements={$count}\n";
}

foreach ( $cache_post_ids as $post_id ) {
	global $wpdb;
	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1",
			$post_id,
			$cache_meta_key
		),
		ARRAY_A
	);
	if ( ! $row || ! isset( $row['meta_value'], $row['meta_id'] ) ) {
		continue;
	}
	$raw = (string) $row['meta_value'];
	if ( $raw === '' || strpos( $raw, $needle ) === false ) {
		continue;
	}
	$new = str_replace( $needle, '', $raw, $count );
	if ( $count < 1 ) {
		continue;
	}
	if ( $apply ) {
		$write_postmeta( (int) $post_id, (int) $row['meta_id'], $new );
	}
	++$changed;
	echo ( $apply ? 'UPDATED' : 'DRY-RUN' ) . " post_id={$post_id} meta={$cache_meta_key} meta_id={$row['meta_id']} replacements={$count}\n";
}

echo 'Rows touched (meta entries): ' . $changed . "\n";
if ( ! $apply && $changed > 0 ) {
	echo "Set BIOPENTRA_ELEMENTOR_CLEANUP_APPLY=1 to apply updates.\n";
}
