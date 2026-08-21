<?php
/**
 * Restore FAQ page Elementor data from a revision (DEV).
 * Usage: wp eval-file .../restore-faq-from-revision.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$src = (int) ( getenv( 'BIOPENTRA_FAQ_REV' ) ?: 4571 );
$dst = (int) ( getenv( 'BIOPENTRA_FAQ_PAGE' ) ?: 3826 );

$raw = get_post_meta( $src, '_elementor_data', true );
if ( ! is_string( $raw ) || $raw === '' ) {
	echo "ERROR: empty _elementor_data on revision {$src}\n";
	return;
}

$decoded = json_decode( $raw, true );
if ( ! is_array( $decoded ) ) {
	echo "ERROR: invalid JSON on revision {$src}\n";
	return;
}

$parent = (int) get_post_field( 'post_parent', $src );
if ( $parent && $parent !== $dst ) {
	echo "ERROR: revision {$src} parent={$parent}, expected {$dst}\n";
	return;
}

update_post_meta( $dst, '_elementor_data', wp_slash( $raw ) );
update_post_meta( $dst, '_elementor_edit_mode', 'builder' );
update_post_meta( $dst, '_elementor_template_type', 'wp-page' );
update_post_meta( $dst, '_wp_page_template', 'elementor_header_footer' );

$ps = get_post_meta( $src, '_elementor_page_settings', true );
if ( ( is_array( $ps ) && $ps ) || ( is_string( $ps ) && $ps !== '' ) ) {
	update_post_meta( $dst, '_elementor_page_settings', $ps );
}

delete_post_meta( $dst, '_elementor_css' );
delete_post_meta( $dst, '_elementor_element_cache' );
if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}
if ( function_exists( 'wp_cache_flush' ) ) {
	wp_cache_flush();
}

$check = get_post_meta( $dst, '_elementor_data', true );
echo "Restored FAQ page {$dst} from revision {$src}\n";
echo '  data_len=' . strlen( (string) $check ) . "\n";
echo '  top_sections=' . ( is_array( json_decode( (string) $check, true ) ) ? count( json_decode( (string) $check, true ) ) : 0 ) . "\n";
