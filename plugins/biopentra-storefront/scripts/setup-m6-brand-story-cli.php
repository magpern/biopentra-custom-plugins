<?php
/**
 * M6 — Brand Story & Ordering Confidence (idempotent Elementor patch).
 *
 * Transforms:
 * - 7fe474f Confidence Strip → compact dark cues
 * - why4444 Why BioPentra → desktop content | visual statement; mobile typography stack
 * - faqPreview4444 Ordering Questions → Elementor accordion (collapsed; no FAQ schema)
 *
 * Does NOT mutate 0b22897 (M5) or 238bcc6 (guidance).
 *
 * Run:
 *   cd /opt/biopentra/apps/wordpress
 *   docker compose run --rm -T wpcli wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-m6-brand-story-cli.php
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BIOPENTRA_M6_STRIP_ID = '7fe474f';
const BIOPENTRA_M6_WHY_ID   = 'why4444';
const BIOPENTRA_M6_FAQ_ID   = 'faqPreview4444';
const BIOPENTRA_M6_FAQ_WID  = 'm6faq01';
const BIOPENTRA_M6_WHY_BODY = 'm6whybd';
const BIOPENTRA_M6_WHY_PROOFS = 'm6whypr';
const BIOPENTRA_M6_WHY_CONTENT = 'm6whycn';
const BIOPENTRA_M6_WHY_IMAGE   = 'm6whyim';
const BIOPENTRA_M6_EU_CUE   = 'Reliable European dispatch.';
const BIOPENTRA_M6_RESEARCH_TITLE = 'Research-use positioning';
const BIOPENTRA_M6_RESEARCH_BODY  = 'Products are presented for laboratory research use.';
const BIOPENTRA_M6_VISUAL_FILE    = 'm6-why-biopentra-visual.png';
const BIOPENTRA_M6_VISUAL_SHA256  = 'b4ded743112c73692b296ffcdefaadee5e8ef398a85d3f5678aea4b20060630f';
const BIOPENTRA_M6_VISUAL_META    = '_biopentra_m6_why_visual';
const BIOPENTRA_M6_VISUAL_KEY     = 'm6-why-biopentra-visual';
const BIOPENTRA_M6_VISUAL_ALT     = 'BioPentra research vial in a laboratory setting';

/**
 * @param string $classes Existing classes.
 * @param array  $ensure  Classes that must appear once.
 * @param array  $remove  Classes to strip.
 * @return string
 */
function biopentra_m6_normalize_classes( $classes, array $ensure, array $remove = array() ) {
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
 * Find section by id in top-level Elementor tree.
 *
 * @param array  $data Tree.
 * @param string $id   Section id.
 * @return int|null
 */
function biopentra_m6_find_section_index( array $data, $id ) {
	foreach ( $data as $i => $section ) {
		if ( ( $section['id'] ?? '' ) === $id ) {
			return (int) $i;
		}
	}
	return null;
}

/**
 * Index nodes by id (shallow children of a section).
 *
 * @param array $section Section node.
 * @return array
 */
function biopentra_m6_index_children( array $section ) {
	$by = array();
	foreach ( ( $section['elements'] ?? array() ) as $child ) {
		if ( ! empty( $child['id'] ) ) {
			$by[ $child['id'] ] = $child;
		}
	}
	return $by;
}

/**
 * Compact a strip/why point container (strip card chrome).
 *
 * @param array  $card   Container.
 * @param string $class  Marker class.
 * @return array
 */
function biopentra_m6_compact_item( array $card, $class ) {
	$card['settings']['content_width'] = 'full';
	$card['settings']['width']         = array(
		'unit'  => '%',
		'size'  => 25,
		'sizes' => array(),
	);
	$card['settings']['width_tablet']  = array(
		'unit'  => '%',
		'size'  => 50,
		'sizes' => array(),
	);
	$card['settings']['width_mobile']  = array(
		'unit'  => '%',
		'size'  => 50,
		'sizes' => array(),
	);
	$card['settings']['background_background'] = 'classic';
	$card['settings']['background_color']      = '';
	unset(
		$card['settings']['border_border'],
		$card['settings']['border_width'],
		$card['settings']['border_color'],
		$card['settings']['border_radius'],
		$card['settings']['box_shadow_box_shadow'],
		$card['settings']['box_shadow_box_shadow_type']
	);
	$card['settings']['padding'] = array(
		'unit'     => 'px',
		'top'      => '8',
		'right'    => '12',
		'bottom'   => '8',
		'left'     => '12',
		'isLinked' => false,
	);
	$card['settings']['flex_direction'] = 'column';
	$card['settings']['flex_gap']       = array(
		'column'   => '4',
		'row'      => '4',
		'isLinked' => true,
		'unit'     => 'px',
		'size'     => 4,
	);
	$card['settings']['_css_classes'] = biopentra_m6_normalize_classes(
		$card['settings']['_css_classes'] ?? '',
		array( $class ),
		array( 'bp-lower-card', 'bp-lower-card-secure', 'bp-lower-card-faq' )
	);
	$card['settings']['css_classes'] = $card['settings']['_css_classes'];
	return $card;
}

/**
 * Compact Why proof row (full width).
 *
 * @param array  $card  Container.
 * @param string $class Class.
 * @return array
 */
function biopentra_m6_why_proof( array $card, $class ) {
	$card = biopentra_m6_compact_item( $card, $class );
	$card['settings']['width']        = array(
		'unit'  => '%',
		'size'  => 100,
		'sizes' => array(),
	);
	$card['settings']['width_tablet'] = $card['settings']['width'];
	$card['settings']['width_mobile'] = $card['settings']['width'];
	$card['settings']['padding']      = array(
		'unit'     => 'px',
		'top'      => '12',
		'right'    => '0',
		'bottom'   => '12',
		'left'     => '0',
		'isLinked' => false,
	);
	foreach ( $card['elements'] as &$child ) {
		if ( ( $child['widgetType'] ?? '' ) === 'heading' ) {
			$child['settings']['align']       = 'left';
			$child['settings']['header_size'] = 'h3';
			$child['settings']['title_color'] = '#0a303e';
			$child['settings']['typography_font_size'] = array(
				'unit'  => 'px',
				'size'  => 17,
				'sizes' => array(),
			);
			$child['settings']['typography_font_weight'] = '600';
			$child['settings']['_css_classes'] = biopentra_m6_normalize_classes(
				$child['settings']['_css_classes'] ?? '',
				array( 'bp-m6-why__proof-title' )
			);
		}
		if ( ( $child['widgetType'] ?? '' ) === 'text-editor' ) {
			$child['settings']['align']      = 'left';
			$child['settings']['text_color'] = '#486077';
			$child['settings']['_css_classes'] = biopentra_m6_normalize_classes(
				$child['settings']['_css_classes'] ?? '',
				array( 'bp-m6-why__proof-body' )
			);
		}
	}
	unset( $child );
	return $card;
}

/**
 * Build classic Elementor accordion widget for FAQ.
 *
 * @param array $pairs List of [title, content].
 * @return array
 */
function biopentra_m6_faq_accordion( array $pairs ) {
	$tabs = array();
	foreach ( $pairs as $i => $pair ) {
		$tabs[] = array(
			'_id'         => sprintf( 'm6ftab%d', $i + 1 ),
			'tab_title'   => $pair[0],
			'tab_content' => '<p>' . esc_html( $pair[1] ) . '</p>',
		);
	}

	return array(
		'id'         => BIOPENTRA_M6_FAQ_WID,
		'elType'     => 'widget',
		'widgetType' => 'accordion',
		'settings'   => array(
			'tabs'                 => $tabs,
			'selected_icon'        => array(
				'value'   => 'fas fa-plus',
				'library' => 'fa-solid',
			),
			'selected_active_icon' => array(
				'value'   => 'fas fa-minus',
				'library' => 'fa-solid',
			),
			'title_html_tag'       => 'div',
			'faq_schema'           => '',
			'_css_classes'         => 'bp-m6-faq__accordion',
			'css_classes'          => 'bp-m6-faq__accordion',
		),
		'elements'   => array(),
	);
}

/**
 * Refresh research-use proof copy on an already-transformed Why section.
 *
 * @param array $why Why section (by ref).
 */
function biopentra_m6_refresh_research_proof( array &$why ) {
	$walk = static function ( array &$nodes ) use ( &$walk ) {
		foreach ( $nodes as &$n ) {
			if ( ( $n['id'] ?? '' ) === 'whyRh' && ( $n['widgetType'] ?? '' ) === 'heading' ) {
				$n['settings']['title'] = BIOPENTRA_M6_RESEARCH_TITLE;
			}
			if ( ( $n['id'] ?? '' ) === 'whyRp' && ( $n['widgetType'] ?? '' ) === 'text-editor' ) {
				$n['settings']['editor'] = '<p>' . esc_html( BIOPENTRA_M6_RESEARCH_BODY ) . '</p>';
			}
			if ( ! empty( $n['elements'] ) && is_array( $n['elements'] ) ) {
				$walk( $n['elements'] );
			}
		}
		unset( $n );
	};
	$walk( $why['elements'] );
}

/**
 * Find M6 Why visual attachment by stable meta.
 *
 * @return int
 */
function biopentra_m6_find_visual_attachment() {
	$q = new WP_Query(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => BIOPENTRA_M6_VISUAL_META,
			'meta_value'     => BIOPENTRA_M6_VISUAL_KEY,
			'no_found_rows'  => true,
		)
	);
	return ! empty( $q->posts[0] ) ? (int) $q->posts[0] : 0;
}

/**
 * Import Why visual derivative if missing (dedup via meta).
 *
 * @return int Attachment ID or 0.
 */
function biopentra_m6_ensure_visual_attachment() {
	$existing = biopentra_m6_find_visual_attachment();
	if ( $existing > 0 ) {
		echo "M6 why media: existing attachment {$existing}\n";
		return $existing;
	}

	$src = trailingslashit( BIOPENTRA_STOREFRONT_PATH ) . 'assets/media/' . BIOPENTRA_M6_VISUAL_FILE;
	if ( ! is_readable( $src ) ) {
		echo "ERROR: M6 why visual not readable at {$src}\n";
		return 0;
	}

	$hash = hash_file( 'sha256', $src );
	if ( $hash !== BIOPENTRA_M6_VISUAL_SHA256 ) {
		echo "ERROR: M6 why visual SHA-256 mismatch (got {$hash})\n";
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = wp_tempnam( BIOPENTRA_M6_VISUAL_FILE );
	if ( ! $tmp || ! copy( $src, $tmp ) ) {
		echo "ERROR: Could not stage M6 why visual for import\n";
		return 0;
	}

	$att_id = media_handle_sideload(
		array(
			'name'     => BIOPENTRA_M6_VISUAL_FILE,
			'tmp_name' => $tmp,
		),
		0,
		BIOPENTRA_M6_VISUAL_ALT
	);

	if ( is_wp_error( $att_id ) ) {
		@unlink( $tmp ); // phpcs:ignore
		echo 'ERROR: media_handle_sideload failed: ' . $att_id->get_error_message() . "\n";
		return 0;
	}

	update_post_meta( (int) $att_id, BIOPENTRA_M6_VISUAL_META, BIOPENTRA_M6_VISUAL_KEY );
	update_post_meta( (int) $att_id, '_biopentra_m6_why_visual_sha256', BIOPENTRA_M6_VISUAL_SHA256 );
	update_post_meta( (int) $att_id, '_wp_attachment_image_alt', BIOPENTRA_M6_VISUAL_ALT );
	wp_update_post(
		array(
			'ID'           => (int) $att_id,
			'post_excerpt' => 'M6 Why BioPentra desktop visual statement (vial / lab crop)',
		)
	);

	echo "M6 why media: imported attachment {$att_id}\n";
	return (int) $att_id;
}

/**
 * Build / refresh Why image widget bound to attachment.
 *
 * @param int $att_id Attachment ID.
 * @return array
 */
function biopentra_m6_why_image_widget( $att_id ) {
	$url = wp_get_attachment_image_url( $att_id, 'large' );
	if ( ! is_string( $url ) || $url === '' ) {
		$url = wp_get_attachment_image_url( $att_id, 'full' );
	}
	return array(
		'id'         => BIOPENTRA_M6_WHY_IMAGE,
		'elType'     => 'widget',
		'widgetType' => 'image',
		'settings'   => array(
			'image'          => array(
				'url'    => is_string( $url ) ? $url : '',
				'id'     => (int) $att_id,
				'size'   => '',
				'alt'    => BIOPENTRA_M6_VISUAL_ALT,
				'source' => 'library',
			),
			'image_size'     => 'large',
			'align'          => 'center',
			'caption_source' => 'none',
			'_css_classes'   => 'bp-m6-why__media',
			'css_classes'    => 'bp-m6-why__media',
		),
		'elements'   => array(),
	);
}

/**
 * Assemble Why section: content column (heading+intro+proofs) | visual (desktop).
 *
 * @param array $why     Why section (by ref).
 * @param array $title   Heading widget.
 * @param array $intro   Intro widget.
 * @param array $proofs  Proofs container.
 * @param int   $att_id  Attachment ID.
 */
function biopentra_m6_apply_why_desktop_layout( array &$why, array $title, array $intro, array $proofs, $att_id ) {
	$title['settings']['_css_classes'] = biopentra_m6_normalize_classes(
		$title['settings']['_css_classes'] ?? '',
		array( 'bp-m6-why__heading' )
	);
	$title['settings']['css_classes'] = $title['settings']['_css_classes'];
	$title['settings']['align']       = 'left';
	$title['settings']['title_color'] = '#0a303e';
	$title['settings']['typography_font_weight'] = '700';

	$intro['settings']['_css_classes'] = biopentra_m6_normalize_classes(
		$intro['settings']['_css_classes'] ?? '',
		array( 'bp-m6-why__intro' )
	);
	$intro['settings']['css_classes'] = $intro['settings']['_css_classes'];

	$body = array(
		'id'       => BIOPENTRA_M6_WHY_BODY,
		'elType'   => 'container',
		'settings' => array(
			'content_width'  => 'full',
			'flex_direction' => 'column',
			'flex_gap'       => array(
				'column'   => '16',
				'row'      => '16',
				'isLinked' => true,
				'unit'     => 'px',
				'size'     => 16,
			),
			'_css_classes'   => 'bp-m6-why__body',
			'css_classes'    => 'bp-m6-why__body',
		),
		'elements' => array( $intro, $proofs ),
	);

	$content = array(
		'id'       => BIOPENTRA_M6_WHY_CONTENT,
		'elType'   => 'container',
		'settings' => array(
			'content_width'  => 'full',
			'flex_direction' => 'column',
			'flex_gap'       => array(
				'column'   => '20',
				'row'      => '20',
				'isLinked' => true,
				'unit'     => 'px',
				'size'     => 20,
			),
			'_css_classes'   => 'bp-m6-why__content',
			'css_classes'    => 'bp-m6-why__content',
		),
		'elements' => array( $title, $body ),
	);

	$why['elements'] = array( $content, biopentra_m6_why_image_widget( $att_id ) );
}

/**
 * Strip legacy glass-card / elevated FAQ rules from homepage page custom CSS.
 * Leaves non-M6 selectors intact (including mixed :is() lists after faq* removal).
 *
 * @param string $css Page custom CSS.
 * @return string
 */
function biopentra_m6_strip_page_css( $css ) {
	$css = (string) $css;
	if ( $css === '' ) {
		return $css;
	}

	$drop_needles = array(
		'elementor-element-7fe474f',
		'elementor-element-bt0card',
		'elementor-element-bt1card',
		'elementor-element-bt2card',
		'elementor-element-bt3card',
		'elementor-element-why0card',
		'elementor-element-why1card',
		'elementor-element-why2card',
		'elementor-element-why3card',
		'elementor-element-whyGrid',
		'elementor-element-faqGrid4444',
		'elementor-element-faq0card',
		'elementor-element-faq1card',
		'elementor-element-faq2card',
		'elementor-element-faq3card',
		'elementor-element-faqPreview4444',
	);

	$parts    = preg_split( '/(?<=\})/', $css );
	$kept     = array();
	$saw_m6   = false;
	$marker   = "/* M6: strip/why/faq visual CSS migrated to biopentra-storefront home-v2.css */\n";

	foreach ( $parts as $part ) {
		$part = trim( $part );
		if ( $part === '' ) {
			continue;
		}

		// Mixed :is(...) blocks: remove only faq card IDs so other lower cards keep chrome.
		if ( preg_match( '/:is\(/', $part ) && preg_match( '/faq[0-3]card/', $part ) ) {
			$cleaned = preg_replace( '/,?\s*\.elementor-element-faq[0-3]card/', '', $part );
			$cleaned = preg_replace( '/:is\(\s*,/', ':is(', $cleaned );
			$cleaned = preg_replace( '/:is\(\s*\)/', ':is()', $cleaned );
			if ( preg_match( '/:is\(\s*\)/', $cleaned ) || ! preg_match( '/\{/', $cleaned ) ) {
				$saw_m6 = true;
				continue;
			}
			$part   = $cleaned;
			$saw_m6 = true;
		}

		$is_m6 = false;
		foreach ( $drop_needles as $needle ) {
			if ( false !== strpos( $part, $needle ) ) {
				// Keep rhythm isolation rule that also lists M5/guidance — strip faqPreview only.
				if ( false !== strpos( $part, 'elementor-element-0b22897' ) && false !== strpos( $part, 'elementor-element-238bcc6' ) ) {
					$part = preg_replace( '/,\s*body\.page-id-4444\s*\.elementor-element-faqPreview4444/', '', $part );
					$part = preg_replace( '/body\.page-id-4444\s*\.elementor-element-faqPreview4444\s*,/', '', $part );
					$saw_m6 = true;
					$is_m6  = false;
					break;
				}
				$is_m6 = true;
				break;
			}
		}
		if ( $is_m6 ) {
			$saw_m6 = true;
			continue;
		}
		$kept[] = $part;
	}

	$out = trim( implode( "\n\n", $kept ) );
	if ( $saw_m6 && false === strpos( $out, 'M6: strip/why/faq visual CSS' ) ) {
		$out = $marker . ( $out !== '' ? "\n" . $out : '' );
	}
	return $out;
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

echo "M6 brand CLI — home page ID {$home_id} (" . get_post_field( 'post_name', $home_id ) . ")\n";

$raw  = get_post_meta( $home_id, '_elementor_data', true );
$data = is_string( $raw ) ? json_decode( $raw, true ) : null;
if ( ! is_array( $data ) || empty( $data ) ) {
	echo "ERROR: No Elementor data on home page.\n";
	return;
}

$strip_i = biopentra_m6_find_section_index( $data, BIOPENTRA_M6_STRIP_ID );
$why_i   = biopentra_m6_find_section_index( $data, BIOPENTRA_M6_WHY_ID );
$faq_i   = biopentra_m6_find_section_index( $data, BIOPENTRA_M6_FAQ_ID );

if ( null === $strip_i || null === $why_i || null === $faq_i ) {
	echo "ERROR: Missing M6 section(s). strip={$strip_i} why={$why_i} faq={$faq_i}\n";
	return;
}

// -------------------------------------------------------------------------
// A. Confidence Strip
// -------------------------------------------------------------------------
$strip = &$data[ $strip_i ];
$strip['settings']['_css_classes'] = biopentra_m6_normalize_classes(
	$strip['settings']['_css_classes'] ?? '',
	array( 'bp-m6-confidence' )
);
$strip['settings']['css_classes']      = $strip['settings']['_css_classes'];
$strip['settings']['background_color'] = '#0a303e';
$strip['settings']['padding']          = array(
	'unit'     => 'px',
	'top'      => '28',
	'right'    => '24',
	'bottom'   => '28',
	'left'     => '24',
	'isLinked' => false,
);
$strip['settings']['padding_mobile']   = array(
	'unit'     => 'px',
	'top'      => '22',
	'right'    => '16',
	'bottom'   => '22',
	'left'     => '16',
	'isLinked' => false,
);

$row = &$strip['elements'][0];
if ( ( $row['id'] ?? '' ) !== 'b2e4a7f' ) {
	echo "ERROR: Expected strip row b2e4a7f\n";
	return;
}
$row['settings']['flex_direction']        = 'row';
$row['settings']['flex_wrap']             = 'wrap';
$row['settings']['flex_direction_mobile'] = 'row';
$row['settings']['flex_wrap_mobile']      = 'wrap';
$row['settings']['flex_gap']              = array(
	'column'   => '0',
	'row'      => '0',
	'isLinked' => true,
	'unit'     => 'px',
	'size'     => 0,
);
$row['settings']['_css_classes'] = biopentra_m6_normalize_classes(
	$row['settings']['_css_classes'] ?? '',
	array( 'bp-m6-confidence__row' )
);
$row['settings']['css_classes'] = $row['settings']['_css_classes'];

foreach ( $row['elements'] as $ci => $card ) {
	$row['elements'][ $ci ] = biopentra_m6_compact_item( $card, 'bp-m6-confidence__item' );
	foreach ( $row['elements'][ $ci ]['elements'] as &$w ) {
		if ( ( $w['widgetType'] ?? '' ) === 'heading' ) {
			$w['settings']['align']       = 'left';
			$w['settings']['title_color'] = '#e9f8ff';
			$w['settings']['typography_font_size'] = array(
				'unit'  => 'px',
				'size'  => 15,
				'sizes' => array(),
			);
			$w['settings']['typography_font_weight'] = '600';
			$w['settings']['_css_classes'] = biopentra_m6_normalize_classes(
				$w['settings']['_css_classes'] ?? '',
				array( 'bp-m6-confidence__title' )
			);
		}
		if ( ( $w['widgetType'] ?? '' ) === 'text-editor' ) {
			$w['settings']['align']      = 'left';
			$w['settings']['text_color'] = '#b8c9d4';
			$w['settings']['typography_font_size'] = array(
				'unit'  => 'px',
				'size'  => 13,
				'sizes' => array(),
			);
			$w['settings']['_css_classes'] = biopentra_m6_normalize_classes(
				$w['settings']['_css_classes'] ?? '',
				array( 'bp-m6-confidence__body' )
			);
		}
	}
	unset( $w );
}

// Shorten EU Fulfillment body by card id bt0p (stable from plan).
foreach ( $row['elements'] as &$card ) {
	if ( ( $card['id'] ?? '' ) !== 'bt0card' ) {
		continue;
	}
	foreach ( $card['elements'] as &$w ) {
		if ( ( $w['id'] ?? '' ) === 'bt0p' || ( ( $w['widgetType'] ?? '' ) === 'text-editor' ) ) {
			$w['settings']['editor'] = '<p>' . esc_html( BIOPENTRA_M6_EU_CUE ) . '</p>';
			break;
		}
	}
	unset( $w );
}
unset( $card, $row, $strip );
echo "M6 strip: compact cues + EU body shortened\n";

// -------------------------------------------------------------------------
// B. Why BioPentra (desktop content | visual; mobile content-only)
// -------------------------------------------------------------------------
$att_id = biopentra_m6_ensure_visual_attachment();
if ( $att_id <= 0 ) {
	echo "ERROR: M6 why visual attachment unavailable\n";
	return;
}

$why = &$data[ $why_i ];
$why['settings']['_css_classes'] = biopentra_m6_normalize_classes(
	$why['settings']['_css_classes'] ?? '',
	array( 'bp-m6-why' )
);
$why['settings']['css_classes']      = $why['settings']['_css_classes'];
$why['settings']['background_color'] = '#f3f2f2';
$why['settings']['padding']          = array(
	'unit'     => 'px',
	'top'      => '48',
	'right'    => '24',
	'bottom'   => '48',
	'left'     => '24',
	'isLinked' => false,
);
$why['settings']['padding_mobile']   = array(
	'unit'     => 'px',
	'top'      => '40',
	'right'    => '16',
	'bottom'   => '40',
	'left'     => '16',
	'isLinked' => false,
);

$wby     = biopentra_m6_index_children( $why );
$title   = $wby['whyTitle'] ?? null;
$intro   = $wby['whyIntro'] ?? null;
$grid    = $wby['whyGrid'] ?? null;
$body    = $wby[ BIOPENTRA_M6_WHY_BODY ] ?? null;
$content = $wby[ BIOPENTRA_M6_WHY_CONTENT ] ?? null;

// Extract title/intro/proofs from already-transformed trees.
if ( $content ) {
	$cby   = biopentra_m6_index_children( $content );
	$title = $cby['whyTitle'] ?? $title;
	$body  = $cby[ BIOPENTRA_M6_WHY_BODY ] ?? $body;
}
if ( $body ) {
	$bby   = biopentra_m6_index_children( $body );
	$intro = $bby['whyIntro'] ?? $intro;
	$proofs_existing = $bby[ BIOPENTRA_M6_WHY_PROOFS ] ?? null;
} else {
	$proofs_existing = null;
}

if ( $title && $intro && $grid ) {
	// First-time transform from original four-card grid.
	$title['settings']['typography_font_size'] = array(
		'unit'  => 'px',
		'size'  => 36,
		'sizes' => array(),
	);
	$title['settings']['typography_font_size_tablet'] = array(
		'unit'  => 'px',
		'size'  => 30,
		'sizes' => array(),
	);
	$title['settings']['typography_font_size_mobile'] = array(
		'unit'  => 'px',
		'size'  => 26,
		'sizes' => array(),
	);

	$intro['settings']['align']      = 'left';
	$intro['settings']['text_color'] = '#486077';

	$gby = array();
	foreach ( ( $grid['elements'] ?? array() ) as $gc ) {
		$gby[ $gc['id'] ] = $gc;
	}
	$clear = $gby['why1card'] ?? null;
	$prof  = $gby['why3card'] ?? null;
	if ( ! $clear || ! $prof ) {
		echo "ERROR: Expected why1card and why3card in whyGrid\n";
		return;
	}

	$clear = biopentra_m6_why_proof( $clear, 'bp-m6-why__proof' );
	$prof  = biopentra_m6_why_proof( $prof, 'bp-m6-why__proof' );

	$research         = $gby['why0card'] ?? $clear;
	$research['id']   = 'whyRcard';
	$research         = biopentra_m6_why_proof( $research, 'bp-m6-why__proof' );
	foreach ( $research['elements'] as &$rw ) {
		if ( ( $rw['widgetType'] ?? '' ) === 'heading' ) {
			$rw['id']                 = 'whyRh';
			$rw['settings']['title']  = BIOPENTRA_M6_RESEARCH_TITLE;
		}
		if ( ( $rw['widgetType'] ?? '' ) === 'text-editor' ) {
			$rw['id']                  = 'whyRp';
			$rw['settings']['editor']  = '<p>' . esc_html( BIOPENTRA_M6_RESEARCH_BODY ) . '</p>';
		}
	}
	unset( $rw );

	$proofs = array(
		'id'       => BIOPENTRA_M6_WHY_PROOFS,
		'elType'   => 'container',
		'settings' => array(
			'content_width'  => 'full',
			'flex_direction' => 'column',
			'flex_gap'       => array(
				'column'   => '0',
				'row'      => '0',
				'isLinked' => true,
				'unit'     => 'px',
				'size'     => 0,
			),
			'_css_classes'   => 'bp-m6-why__proofs',
			'css_classes'    => 'bp-m6-why__proofs',
		),
		'elements' => array( $clear, $prof, $research ),
	);

	biopentra_m6_apply_why_desktop_layout( $why, $title, $intro, $proofs, $att_id );
	echo "M6 why: content | visual layout + 3 proofs (desktop image; mobile content-only via CSS)\n";
} elseif ( $title && $intro && $proofs_existing ) {
	biopentra_m6_refresh_research_proof( $why );
	biopentra_m6_apply_why_desktop_layout( $why, $title, $intro, $proofs_existing, $att_id );
	echo "M6 why: refreshed content | visual layout + media binding\n";
} else {
	echo "ERROR: why4444 missing expected title/intro/proofs structure\n";
	return;
}
unset( $why );

// -------------------------------------------------------------------------
// C. Ordering Questions → accordion
// -------------------------------------------------------------------------
$faq = &$data[ $faq_i ];
$faq['settings']['_css_classes'] = biopentra_m6_normalize_classes(
	$faq['settings']['_css_classes'] ?? '',
	array( 'bp-m6-faq', 'bp-m6-faq--force-collapsed' ),
	array( 'bp-lower-faq' )
);
$faq['settings']['css_classes']      = $faq['settings']['_css_classes'];
$faq['settings']['background_color'] = '#ffffff';
$faq['settings']['padding']          = array(
	'unit'     => 'px',
	'top'      => '20',
	'right'    => '24',
	'bottom'   => '28',
	'left'     => '24',
	'isLinked' => false,
);
$faq['settings']['padding_mobile']   = array(
	'unit'     => 'px',
	'top'      => '36',
	'right'    => '16',
	'bottom'   => '44',
	'left'     => '16',
	'isLinked' => false,
);

$fby   = biopentra_m6_index_children( $faq );
$ftitle = $fby['faqTitle4444'] ?? null;
$fintro = $fby['faqIntro4444'] ?? null;
$fgrid  = $fby['faqGrid4444'] ?? null;
$facc   = $fby[ BIOPENTRA_M6_FAQ_WID ] ?? null;

$pairs = array(
	array( 'How are orders shipped?', 'Orders are prepared for European delivery with tracking options where available.' ),
	array( 'Are orders packaged discreetly?', 'Packaging is handled with privacy and secure delivery in mind.' ),
	array( 'What payment methods are available?', 'Checkout supports modern payment options shown during ordering.' ),
	array( 'Are products intended for research use?', 'Products are presented for laboratory research use only and are not for human consumption.' ),
);

if ( $fgrid ) {
	// Prefer extracting live Q/A from cards if present.
	$extracted = array();
	foreach ( ( $fgrid['elements'] ?? array() ) as $card ) {
		$q = '';
		$a = '';
		foreach ( ( $card['elements'] ?? array() ) as $cw ) {
			if ( ( $cw['widgetType'] ?? '' ) === 'heading' ) {
				$q = html_entity_decode( wp_strip_all_tags( (string) ( $cw['settings']['title'] ?? '' ) ) );
			}
			if ( ( $cw['widgetType'] ?? '' ) === 'text-editor' ) {
				$a = html_entity_decode( wp_strip_all_tags( (string) ( $cw['settings']['editor'] ?? '' ) ) );
			}
		}
		if ( $q !== '' && $a !== '' ) {
			$extracted[] = array( $q, $a );
		}
	}
	if ( count( $extracted ) === 4 ) {
		$pairs = $extracted;
	}
}

$accordion = biopentra_m6_faq_accordion( $pairs );

if ( $ftitle ) {
	$ftitle['settings']['_css_classes'] = biopentra_m6_normalize_classes(
		$ftitle['settings']['_css_classes'] ?? '',
		array( 'bp-m6-faq__heading' )
	);
	$ftitle['settings']['css_classes'] = $ftitle['settings']['_css_classes'];
	$ftitle['settings']['align']       = 'left';
	$ftitle['settings']['title_color'] = '#0a303e';
}
if ( $fintro ) {
	$fintro['settings']['_css_classes'] = biopentra_m6_normalize_classes(
		$fintro['settings']['_css_classes'] ?? '',
		array( 'bp-m6-faq__intro' )
	);
	$fintro['settings']['css_classes'] = $fintro['settings']['_css_classes'];
	$fintro['settings']['align']       = 'left';
	$fintro['settings']['text_color']  = '#486077';
}

$faq['elements'] = array_values(
	array_filter(
		array( $ftitle, $fintro, $accordion )
	)
);
unset( $faq );
echo "M6 faq: accordion with " . count( $pairs ) . " items (faq_schema off)\n";

update_post_meta( $home_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );

// Neutralize legacy M6 page custom CSS (glass cards / elevated FAQ chrome).
$page_settings = get_post_meta( $home_id, '_elementor_page_settings', true );
if ( ! is_array( $page_settings ) ) {
	$page_settings = array();
}
$before_css = (string) ( $page_settings['custom_css'] ?? '' );
$after_css  = biopentra_m6_strip_page_css( $before_css );
$page_settings['custom_css'] = $after_css;
update_post_meta( $home_id, '_elementor_page_settings', $page_settings );

delete_post_meta( $home_id, '_elementor_css' );
delete_post_meta( $home_id, '_elementor_element_cache' );
if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}
if ( function_exists( 'wp_cache_flush' ) ) {
	wp_cache_flush();
}

echo "M6 brand CLI complete.\n";
echo '  strip=' . BIOPENTRA_M6_STRIP_ID . " why=" . BIOPENTRA_M6_WHY_ID . ' faq=' . BIOPENTRA_M6_FAQ_ID . "\n";
echo '  EU cue="' . BIOPENTRA_M6_EU_CUE . "\"\n";
echo '  page_custom_css_before=' . strlen( $before_css ) . ' after=' . strlen( $after_css ) . "\n";
echo "  idempotent: re-run refreshes structure; no duplicate FAQ tabs when already transformed\n";
