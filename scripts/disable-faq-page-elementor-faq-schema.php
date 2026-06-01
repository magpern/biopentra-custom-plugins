<?php
/**
 * Disable Elementor per-accordion FAQ schema on the FAQ page.
 *
 * Dry-run by default. Set BIOPENTRA_FAQ_SCHEMA_CLEANUP_APPLY=1 to write.
 *
 * Example (from WooCommerce docker project root):
 *   docker compose run --rm --no-deps \
 *     -e BIOPENTRA_FAQ_SCHEMA_CLEANUP_APPLY=1 \
 *     -v /path/to/biopentra-custom-plugins/scripts/disable-faq-page-elementor-faq-schema.php:/tmp/r.php:ro \
 *     wpcli eval-file /tmp/r.php
 *
 * @package BiopentraCustomPlugins
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$apply   = getenv( 'BIOPENTRA_FAQ_SCHEMA_CLEANUP_APPLY' ) === '1';
$post_id = (int) getenv( 'BIOPENTRA_FAQ_PAGE_ID' );
if ( $post_id <= 0 ) {
	$post_id = 3826;
}

$meta_key = '_elementor_data';
$raw      = get_post_meta( $post_id, $meta_key, true );
if ( ! is_string( $raw ) || '' === $raw ) {
	fwrite( STDERR, "Missing {$meta_key} for post {$post_id}\n" );
	exit( 1 );
}

$data = json_decode( $raw, true );
if ( ! is_array( $data ) ) {
	fwrite( STDERR, "Invalid JSON in {$meta_key} for post {$post_id}\n" );
	exit( 1 );
}

$changed_widgets = 0;

/**
 * @param array<int|string, mixed> $nodes Elementor nodes.
 */
$walk = static function ( &$nodes ) use ( &$walk, &$changed_widgets ) {
	if ( ! is_array( $nodes ) ) {
		return;
	}

	foreach ( $nodes as &$node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}

		$widget_type = isset( $node['widgetType'] ) ? (string) $node['widgetType'] : '';
		if ( in_array( $widget_type, array( 'accordion', 'toggle', 'nested-accordion' ), true ) ) {
			if ( isset( $node['settings']['faq_schema'] ) && 'yes' === $node['settings']['faq_schema'] ) {
				$node['settings']['faq_schema'] = '';
				++$changed_widgets;
				echo "Disabled faq_schema on {$widget_type} widget {$node['id']}\n";
			}
		}

		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			$walk( $node['elements'] );
		}
	}
};

$walk( $data );

if ( 0 === $changed_widgets ) {
	echo "No Elementor FAQ schema widgets found on post {$post_id}.\n";
	exit( 0 );
}

echo ( $apply ? 'APPLY' : 'DRY-RUN' ) . ": {$changed_widgets} widget(s) on post {$post_id}.\n";

if ( ! $apply ) {
	exit( 0 );
}

$json = wp_json_encode( $data );
if ( ! is_string( $json ) || '' === $json ) {
	fwrite( STDERR, "Failed to encode updated Elementor data.\n" );
	exit( 1 );
}

update_post_meta( $post_id, $meta_key, wp_slash( $json ) );
delete_post_meta( $post_id, '_elementor_element_cache' );

if ( class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}

echo "Updated post {$post_id} and cleared Elementor cache.\n";
