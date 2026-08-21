<?php
/**
 * M5 — Trust & Fulfillment Story (idempotent Elementor + media patch).
 *
 * - Imports PO-selected master from plugin assets/media/ (dedup by meta).
 * - Restructures section 0b22897: heading → intro → image → compact trust rows.
 * - Applies bp-m5-trust classes; strips elevated card chrome via settings + CSS.
 *
 * Run:
 *   cd /opt/biopentra/apps/wordpress
 *   docker compose run --rm -T wpcli wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-m5-trust-fulfillment-cli.php
 *
 * Backup _elementor_data before first run (redesign changes/backups/).
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BIOPENTRA_M5_SECTION_ID     = '0b22897';
const BIOPENTRA_M5_IMAGE_ID       = 'm5img01';
const BIOPENTRA_M5_CONTENT_ID     = 'm5cnt01';
const BIOPENTRA_M5_ASSET_META     = '_biopentra_m5_master';
const BIOPENTRA_M5_ASSET_KEY      = 'm5-trust-fulfillment-master';
const BIOPENTRA_M5_MASTER_SHA256  = 'e37001d57c98cecefe1c46c318eaf276a86d36c3934a4d7b89a2538bb9432500';
const BIOPENTRA_M5_MASTER_FILE    = 'm5-trust-fulfillment-master.png';

/**
 * @param string $classes Existing classes.
 * @param array  $ensure  Classes that must appear once.
 * @param array  $remove  Classes to strip.
 * @return string
 */
function biopentra_m5_normalize_classes( $classes, array $ensure, array $remove = array() ) {
	$parts = preg_split( '/\s+/', trim( (string) $classes ) );
	$parts = array_values(
		array_filter(
			$parts,
			static function ( $p ) use ( $remove ) {
				return $p !== '' && ! in_array( $p, $remove, true );
			}
		)
	);
	$seen = array();
	$out  = array();
	foreach ( $parts as $p ) {
		if ( isset( $seen[ $p ] ) ) {
			continue;
		}
		$seen[ $p ] = true;
		$out[]      = $p;
	}
	foreach ( $ensure as $need ) {
		if ( ! isset( $seen[ $need ] ) ) {
			$out[]         = $need;
			$seen[ $need ] = true;
		}
	}
	return implode( ' ', $out );
}

/**
 * Find existing M5 master attachment by stable meta key.
 *
 * @return int Attachment ID or 0.
 */
function biopentra_m5_find_attachment() {
	$q = new WP_Query(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => BIOPENTRA_M5_ASSET_META,
			'meta_value'     => BIOPENTRA_M5_ASSET_KEY,
			'no_found_rows'  => true,
		)
	);
	if ( ! empty( $q->posts[0] ) ) {
		return (int) $q->posts[0];
	}
	return 0;
}

/**
 * Import master PNG from plugin assets if missing. Dedup via meta key.
 *
 * @return int Attachment ID.
 */
function biopentra_m5_ensure_attachment() {
	$existing = biopentra_m5_find_attachment();
	if ( $existing > 0 ) {
		echo "M5 media: existing attachment {$existing}\n";
		return $existing;
	}

	$src = trailingslashit( BIOPENTRA_STOREFRONT_PATH ) . 'assets/media/' . BIOPENTRA_M5_MASTER_FILE;
	if ( ! is_readable( $src ) ) {
		echo "ERROR: M5 master not readable at {$src}\n";
		return 0;
	}

	$hash = hash_file( 'sha256', $src );
	if ( $hash !== BIOPENTRA_M5_MASTER_SHA256 ) {
		echo "ERROR: M5 master SHA-256 mismatch (got {$hash})\n";
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = wp_tempnam( BIOPENTRA_M5_MASTER_FILE );
	if ( ! $tmp || ! copy( $src, $tmp ) ) {
		echo "ERROR: Could not stage M5 master for import\n";
		return 0;
	}

	$file_array = array(
		'name'     => BIOPENTRA_M5_MASTER_FILE,
		'tmp_name' => $tmp,
	);

	$att_id = media_handle_sideload(
		$file_array,
		0,
		'Research vial with protective packaging materials'
	);

	if ( is_wp_error( $att_id ) ) {
		@unlink( $tmp ); // phpcs:ignore
		echo 'ERROR: media_handle_sideload failed: ' . $att_id->get_error_message() . "\n";
		return 0;
	}

	update_post_meta( (int) $att_id, BIOPENTRA_M5_ASSET_META, BIOPENTRA_M5_ASSET_KEY );
	update_post_meta( (int) $att_id, '_biopentra_m5_master_sha256', BIOPENTRA_M5_MASTER_SHA256 );
	wp_update_post(
		array(
			'ID'           => (int) $att_id,
			'post_excerpt' => 'M5 Trust & Fulfillment editorial still-life (brand imagery)',
		)
	);
	update_post_meta( (int) $att_id, '_wp_attachment_image_alt', 'Research vial with protective packaging materials' );

	echo "M5 media: imported attachment {$att_id}\n";
	return (int) $att_id;
}

/**
 * Build Elementor image widget settings for attachment.
 *
 * @param int $att_id Attachment ID.
 * @return array
 */
function biopentra_m5_image_widget( $att_id ) {
	$url = wp_get_attachment_image_url( $att_id, 'full' );
	return array(
		'id'         => BIOPENTRA_M5_IMAGE_ID,
		'elType'     => 'widget',
		'settings'   => array(
			'image'        => array(
				'url'    => is_string( $url ) ? $url : '',
				'id'     => (int) $att_id,
				'size'   => '',
				'alt'    => 'Research vial with protective packaging materials',
				'source' => 'library',
			),
			'image_size'   => 'full',
			'align'        => 'center',
			'caption_source' => 'none',
			'_css_classes' => 'bp-m5-trust__media',
			'css_classes'  => 'bp-m5-trust__media',
		),
		'elements'   => array(),
		'widgetType' => 'image',
	);
}

/**
 * Flatten a trust card container into a compact row (no elevated chrome).
 *
 * @param array $card Card container node.
 * @return array
 */
function biopentra_m5_compact_point( array $card ) {
	$card['settings']['content_width']      = 'full';
	$card['settings']['width']              = array(
		'unit' => '%',
		'size' => 100,
		'sizes' => array(),
	);
	$card['settings']['width_tablet']       = array(
		'unit' => '%',
		'size' => 100,
		'sizes' => array(),
	);
	$card['settings']['width_mobile']       = array(
		'unit' => '%',
		'size' => 100,
		'sizes' => array(),
	);
	$card['settings']['background_background'] = 'classic';
	$card['settings']['background_color']      = '';
	unset( $card['settings']['border_border'], $card['settings']['border_width'], $card['settings']['border_color'], $card['settings']['border_radius'] );
	$card['settings']['padding'] = array(
		'unit'     => 'px',
		'top'      => '14',
		'right'    => '0',
		'bottom'   => '14',
		'left'     => '0',
		'isLinked' => false,
	);
	$card['settings']['padding_mobile'] = array(
		'unit'     => 'px',
		'top'      => '12',
		'right'    => '0',
		'bottom'   => '12',
		'left'     => '0',
		'isLinked' => false,
	);
	$card['settings']['flex_direction'] = 'column';
	$card['settings']['flex_gap']       = array(
		'column'   => '6',
		'row'      => '6',
		'isLinked' => true,
		'unit'     => 'px',
		'size'     => 6,
	);
	$card['settings']['_css_classes'] = biopentra_m5_normalize_classes(
		$card['settings']['_css_classes'] ?? '',
		array( 'bp-m5-trust__point' ),
		array( 'bp-lower-card', 'bp-lower-card-secure' )
	);
	$card['settings']['css_classes'] = $card['settings']['_css_classes'];

	foreach ( $card['elements'] as &$child ) {
		if ( ( $child['widgetType'] ?? '' ) === 'heading' ) {
			$child['settings']['header_size'] = 'h3';
			$child['settings']['align']       = 'left';
			$child['settings']['title_color'] = '#0a303e';
			$child['settings']['typography_font_size'] = array(
				'unit'  => 'px',
				'size'  => 17,
				'sizes' => array(),
			);
			$child['settings']['typography_font_size_tablet'] = array(
				'unit'  => 'px',
				'size'  => 17,
				'sizes' => array(),
			);
			$child['settings']['typography_font_size_mobile'] = array(
				'unit'  => 'px',
				'size'  => 16,
				'sizes' => array(),
			);
			$child['settings']['typography_font_weight'] = '600';
			$child['settings']['_css_classes'] = biopentra_m5_normalize_classes(
				$child['settings']['_css_classes'] ?? '',
				array( 'bp-m5-trust__point-title' )
			);
		}
		if ( ( $child['widgetType'] ?? '' ) === 'text-editor' ) {
			$child['settings']['align']      = 'left';
			$child['settings']['text_color'] = '#486077';
			// Keep body HTML; strip forced center if present.
			$editor = (string) ( $child['settings']['editor'] ?? '' );
			$editor = preg_replace( '/text-align\s*:\s*center;?/i', '', $editor );
			$child['settings']['editor'] = $editor;
			$child['settings']['_css_classes'] = biopentra_m5_normalize_classes(
				$child['settings']['_css_classes'] ?? '',
				array( 'bp-m5-trust__point-body' )
			);
		}
	}
	unset( $child );

	return $card;
}

$home_id = (int) get_option( 'page_on_front' );
if ( ! $home_id ) {
	$home_page = get_page_by_path( 'home', OBJECT, 'page' );
	$home_id   = $home_page ? (int) $home_page->ID : 0;
}

if ( ! $home_id ) {
	echo "ERROR: Could not resolve front page.\n";
	return;
}

echo "M5 trust CLI — home page ID {$home_id} (" . get_post_field( 'post_name', $home_id ) . ")\n";

$att_id = biopentra_m5_ensure_attachment();
if ( $att_id <= 0 ) {
	echo "ERROR: Aborting — no attachment.\n";
	return;
}

$raw  = get_post_meta( $home_id, '_elementor_data', true );
$data = is_string( $raw ) ? json_decode( $raw, true ) : null;
if ( ! is_array( $data ) || empty( $data ) ) {
	echo "ERROR: No Elementor data on home page.\n";
	return;
}

$section_idx = null;
foreach ( $data as $i => $section ) {
	if ( ( $section['id'] ?? '' ) === BIOPENTRA_M5_SECTION_ID ) {
		$section_idx = $i;
		break;
	}
}

if ( null === $section_idx ) {
	echo 'ERROR: Missing M5 section id ' . BIOPENTRA_M5_SECTION_ID . "\n";
	return;
}

$section = &$data[ $section_idx ];

// Section shell.
$section['settings']['_css_classes'] = biopentra_m5_normalize_classes(
	$section['settings']['_css_classes'] ?? ( $section['settings']['css_classes'] ?? '' ),
	array( 'bp-m5-trust' ),
	array( 'bp-lower-secure' )
);
$section['settings']['css_classes']       = $section['settings']['_css_classes'];
$section['settings']['background_color']  = '#eef4fb';
$section['settings']['flex_direction']    = 'column';
$section['settings']['flex_gap']          = array(
	'column'   => '20',
	'row'      => '20',
	'isLinked' => true,
	'unit'     => 'px',
	'size'     => 20,
);
$section['settings']['flex_gap_mobile']   = array(
	'column'   => '16',
	'row'      => '16',
	'isLinked' => true,
	'unit'     => 'px',
	'size'     => 16,
);
$section['settings']['padding']           = array(
	'unit'     => 'px',
	'top'      => '56',
	'right'    => '24',
	'bottom'   => '56',
	'left'     => '24',
	'isLinked' => false,
);
$section['settings']['padding_tablet']    = array(
	'unit'     => 'px',
	'top'      => '48',
	'right'    => '20',
	'bottom'   => '48',
	'left'     => '20',
	'isLinked' => false,
);
$section['settings']['padding_mobile']    = array(
	'unit'     => 'px',
	'top'      => '40',
	'right'    => '16',
	'bottom'   => '40',
	'left'     => '16',
	'isLinked' => false,
);

$by_id = array();
foreach ( $section['elements'] as $child ) {
	$by_id[ $child['id'] ] = $child;
	// Unwrap previously nested content children for idempotent rebuild.
	if ( ( $child['id'] ?? '' ) === BIOPENTRA_M5_CONTENT_ID && ! empty( $child['elements'] ) ) {
		foreach ( $child['elements'] as $nested ) {
			$by_id[ $nested['id'] ] = $nested;
		}
	}
}

$heading = $by_id['dd45b67'] ?? null;
$intro   = $by_id['7d26d1f'] ?? null;
$points  = $by_id['4f720f0'] ?? null;
$image   = $by_id[ BIOPENTRA_M5_IMAGE_ID ] ?? null;
$content = $by_id[ BIOPENTRA_M5_CONTENT_ID ] ?? null;

if ( ! $heading || ! $intro || ! $points ) {
	echo "ERROR: Expected heading dd45b67, intro 7d26d1f, points 4f720f0\n";
	return;
}

// Heading / intro classes + alignment (CSS will refine desktop).
$heading['settings']['_css_classes'] = biopentra_m5_normalize_classes(
	$heading['settings']['_css_classes'] ?? '',
	array( 'bp-m5-trust__heading' )
);
$heading['settings']['css_classes']  = $heading['settings']['_css_classes'];
$heading['settings']['align']        = 'left';
$heading['settings']['title_color']  = '#0a303e';
$heading['settings']['typography_font_size'] = array(
	'unit'  => 'px',
	'size'  => 28,
	'sizes' => array(),
);
$heading['settings']['typography_font_size_tablet'] = array(
	'unit'  => 'px',
	'size'  => 26,
	'sizes' => array(),
);
$heading['settings']['typography_font_size_mobile'] = array(
	'unit'  => 'px',
	'size'  => 22,
	'sizes' => array(),
);

$intro['settings']['_css_classes'] = biopentra_m5_normalize_classes(
	$intro['settings']['_css_classes'] ?? '',
	array( 'bp-m5-trust__intro' )
);
$intro['settings']['css_classes']  = $intro['settings']['_css_classes'];
$intro['settings']['align']        = 'left';
$intro['settings']['text_color']   = '#486077';
$editor = (string) ( $intro['settings']['editor'] ?? '' );
$editor = preg_replace( '/text-align\s*:\s*center;?/i', 'text-align:left;', $editor );
$editor = preg_replace( '/max-width\s*:\s*760px;?/i', 'max-width:42rem;', $editor );
$intro['settings']['editor'] = $editor;

// Points container → vertical stack.
$points['settings']['flex_direction']        = 'column';
$points['settings']['flex_direction_tablet'] = 'column';
$points['settings']['flex_direction_mobile'] = 'column';
$points['settings']['flex_gap']              = array(
	'column'   => '0',
	'row'      => '0',
	'isLinked' => true,
	'unit'     => 'px',
	'size'     => 0,
);
$points['settings']['_css_classes'] = biopentra_m5_normalize_classes(
	$points['settings']['_css_classes'] ?? '',
	array( 'bp-m5-trust__points' )
);
$points['settings']['css_classes'] = $points['settings']['_css_classes'];

foreach ( $points['elements'] as $pi => $point ) {
	$points['elements'][ $pi ] = biopentra_m5_compact_point( $point );
}

if ( ! $image ) {
	$image = biopentra_m5_image_widget( $att_id );
	echo "M5 structure: added image widget " . BIOPENTRA_M5_IMAGE_ID . "\n";
} else {
	// Refresh attachment binding (env-local ID).
	$url = wp_get_attachment_image_url( $att_id, 'full' );
	$image['settings']['image'] = array(
		'url'    => is_string( $url ) ? $url : '',
		'id'     => (int) $att_id,
		'size'   => '',
		'alt'    => 'Research vial with protective packaging materials',
		'source' => 'library',
	);
	$image['settings']['image_size']   = 'full';
	$image['settings']['_css_classes'] = biopentra_m5_normalize_classes(
		$image['settings']['_css_classes'] ?? '',
		array( 'bp-m5-trust__media' )
	);
	$image['settings']['css_classes'] = $image['settings']['_css_classes'];
	echo "M5 structure: refreshed image widget " . BIOPENTRA_M5_IMAGE_ID . " → attachment {$att_id}\n";
}

// Content column wrapper (desktop grid cell; mobile display:contents).
if ( ! $content ) {
	$content = array(
		'id'       => BIOPENTRA_M5_CONTENT_ID,
		'elType'   => 'container',
		'settings' => array(
			'content_width'   => 'full',
			'flex_direction'  => 'column',
			'flex_gap'        => array(
				'column'   => '12',
				'row'      => '12',
				'isLinked' => true,
				'unit'     => 'px',
				'size'     => 12,
			),
			'_css_classes'    => 'bp-m5-trust__content',
			'css_classes'     => 'bp-m5-trust__content',
		),
		'elements' => array(),
	);
	echo 'M5 structure: added content wrapper ' . BIOPENTRA_M5_CONTENT_ID . "\n";
} else {
	$content['settings']['content_width']  = 'full';
	$content['settings']['flex_direction'] = 'column';
	$content['settings']['flex_gap']       = array(
		'column'   => '12',
		'row'      => '12',
		'isLinked' => true,
		'unit'     => 'px',
		'size'     => 12,
	);
	$content['settings']['_css_classes'] = biopentra_m5_normalize_classes(
		$content['settings']['_css_classes'] ?? '',
		array( 'bp-m5-trust__content' )
	);
	$content['settings']['css_classes'] = $content['settings']['_css_classes'];
	echo 'M5 structure: refreshed content wrapper ' . BIOPENTRA_M5_CONTENT_ID . "\n";
}

$content['elements'] = array( $heading, $intro, $points );

// Image + content column (mobile CSS reorders via display:contents + flex order).
$section['elements'] = array( $image, $content );
unset( $section );

update_post_meta( $home_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
delete_post_meta( $home_id, '_elementor_css' );
delete_post_meta( $home_id, '_elementor_element_cache' );
if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}
if ( function_exists( 'wp_cache_flush' ) ) {
	wp_cache_flush();
}

$verify = biopentra_m5_find_attachment();
$ids    = array();
foreach ( $data[ $section_idx ]['elements'] as $el ) {
	$ids[] = $el['id'];
}

echo "M5 trust CLI complete.\n";
echo '  section=' . BIOPENTRA_M5_SECTION_ID . " classes=bp-m5-trust\n";
echo '  children=' . implode( ',', $ids ) . "\n";
echo "  attachment={$verify} meta=" . BIOPENTRA_M5_ASSET_KEY . "\n";
echo "  idempotent: re-run refreshes image binding; no duplicate widgets/media\n";
