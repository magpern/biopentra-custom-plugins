<?php
/**
 * M7 — Research & Product Guidance (idempotent Elementor patch).
 *
 * Transforms homepage section 238bcc6 from three equal dark cards into a light
 * editorial guidance hub: primary Research Use + two secondary link rows.
 *
 * Does NOT mutate faqPreview4444 (M6) or Theme Builder footer.
 *
 * Run:
 *   cd /opt/biopentra/apps/wordpress
 *   docker compose run --rm -T wpcli wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-m7-research-guidance-cli.php
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BIOPENTRA_M7_SECTION_ID = '238bcc6';
const BIOPENTRA_M7_HEADING_ID = 'd232850';
const BIOPENTRA_M7_INTRO_ID   = 'm7intro';
const BIOPENTRA_M7_LAYOUT_ID  = '2b6cbf7';
const BIOPENTRA_M7_PRIMARY_ID = 'e0985d6';
const BIOPENTRA_M7_SEC_STACK  = 'm7secrs';
const BIOPENTRA_M7_ROW_STORAGE = '16becb8';
const BIOPENTRA_M7_ROW_ORDER   = '1c171fb';

const BIOPENTRA_M7_INTRO = 'Guidance for researchers reviewing products, storage, and ordering.';
const BIOPENTRA_M7_PRIMARY_TITLE = 'Research Use Information';
const BIOPENTRA_M7_PRIMARY_BODY  = 'Review responsible research-use information and product page details before ordering.';
const BIOPENTRA_M7_PRIMARY_CTA   = 'Explore research-use guidance →';
const BIOPENTRA_M7_PRIMARY_HREF  = '/about/';

const BIOPENTRA_M7_STORAGE_TITLE = 'Storage & Handling Guidelines';
const BIOPENTRA_M7_STORAGE_BODY  = 'Practical recommendations for storing and handling research compounds in laboratory settings.';
const BIOPENTRA_M7_STORAGE_HREF  = '/storage-handling/';

const BIOPENTRA_M7_ORDER_TITLE = 'Ordering & Delivery Help';
const BIOPENTRA_M7_ORDER_BODY  = 'Practical information about shipping, packaging, and checkout before placing an order.';
const BIOPENTRA_M7_ORDER_HREF  = '/faq/';

/**
 * @param string $classes Existing.
 * @param array  $ensure  Must appear once.
 * @param array  $remove  Strip.
 * @return string
 */
function biopentra_m7_normalize_classes( $classes, array $ensure, array $remove = array() ) {
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
 * @param array  $data Tree.
 * @param string $id   Id.
 * @return int|null
 */
function biopentra_m7_find_section_index( array $data, $id ) {
	foreach ( $data as $i => $section ) {
		if ( ( $section['id'] ?? '' ) === $id ) {
			return (int) $i;
		}
	}
	return null;
}

/**
 * Deep-find node by id; return reference path as [parent, index] via mutation of $holder.
 *
 * @param array  $elements Elements.
 * @param string $id       Id.
 * @return array|null Node copy (not by-ref for nested rebuild).
 */
function biopentra_m7_find_node( array $elements, $id ) {
	foreach ( $elements as $el ) {
		if ( ( $el['id'] ?? '' ) === $id ) {
			return $el;
		}
		$found = biopentra_m7_find_node( $el['elements'] ?? array(), $id );
		if ( $found ) {
			return $found;
		}
	}
	return null;
}

/**
 * @param array  $card   Card/row container.
 * @param string $title  Heading.
 * @param string $body   Body HTML text.
 * @param string $href   Relative URL.
 * @param string $cta    Button label (empty = no button; heading linked).
 * @param string $class  Marker class.
 * @param bool   $primary Whether primary feature.
 * @return array
 */
function biopentra_m7_build_item( array $card, $title, $body, $href, $cta, $class, $primary ) {
	$card['settings']['content_width'] = 'full';
	$card['settings']['_css_classes']  = biopentra_m7_normalize_classes(
		$card['settings']['_css_classes'] ?? '',
		array( $class ),
		array( 'bp-lower-card', 'bp-lower-card-guidance' )
	);
	$card['settings']['css_classes'] = $card['settings']['_css_classes'];

	// Reset elevated card chrome from Elementor settings.
	$card['settings']['background_background'] = 'classic';
	$card['settings']['background_color']      = '';
	$card['settings']['border_border']         = '';
	$card['settings']['border_radius']         = array(
		'unit'     => 'px',
		'top'      => '0',
		'right'    => '0',
		'bottom'   => '0',
		'left'     => '0',
		'isLinked' => true,
	);
	$card['settings']['box_shadow_box_shadow_type'] = '';
	$card['settings']['padding'] = array(
		'unit'     => 'px',
		'top'      => '0',
		'right'    => '0',
		'bottom'   => '0',
		'left'     => '0',
		'isLinked' => true,
	);

	if ( $primary ) {
		$card['settings']['width'] = array(
			'unit'  => '%',
			'size'  => 58,
			'sizes' => array(),
		);
	} else {
		$card['settings']['width'] = array(
			'unit'  => '%',
			'size'  => 100,
			'sizes' => array(),
		);
	}
	$card['settings']['width_tablet'] = array(
		'unit'  => '%',
		'size'  => 100,
		'sizes' => array(),
	);
	$card['settings']['width_mobile'] = array(
		'unit'  => '%',
		'size'  => 100,
		'sizes' => array(),
	);

	$heading = null;
	$text    = null;
	$button  = null;
	foreach ( ( $card['elements'] ?? array() ) as $child ) {
		$wt = $child['widgetType'] ?? '';
		if ( 'heading' === $wt && ! $heading ) {
			$heading = $child;
		} elseif ( 'text-editor' === $wt && ! $text ) {
			$text = $child;
		} elseif ( 'button' === $wt && ! $button ) {
			$button = $child;
		}
	}

	if ( ! $heading ) {
		$heading = array(
			'id'         => substr( md5( $class . 'h' ), 0, 7 ),
			'elType'     => 'widget',
			'widgetType' => 'heading',
			'settings'   => array(),
			'elements'   => array(),
		);
	}
	if ( ! $text ) {
		$text = array(
			'id'         => substr( md5( $class . 't' ), 0, 7 ),
			'elType'     => 'widget',
			'widgetType' => 'text-editor',
			'settings'   => array(),
			'elements'   => array(),
		);
	}

	$heading['settings']['title']       = $title;
	$heading['settings']['header_size'] = $primary ? 'h3' : 'h4';
	$heading['settings']['align']       = 'left';
	$heading['settings']['title_color'] = '#0a303e';
	$heading['settings']['_css_classes'] = biopentra_m7_normalize_classes(
		$heading['settings']['_css_classes'] ?? '',
		array( $primary ? 'bp-m7-guidance__primary-title' : 'bp-m7-guidance__row-title' )
	);
	$heading['settings']['css_classes'] = $heading['settings']['_css_classes'];

	if ( $primary ) {
		$heading['settings']['link'] = array(
			'url'         => '',
			'is_external' => '',
			'nofollow'    => '',
		);
	} else {
		$heading['settings']['link'] = array(
			'url'         => $href,
			'is_external' => '',
			'nofollow'    => '',
			'custom_attributes' => '',
		);
	}

	$text['settings']['editor']     = '<p>' . esc_html( $body ) . '</p>';
	$text['settings']['align']      = 'left';
	$text['settings']['text_color'] = '#486077';
	$text['settings']['_css_classes'] = biopentra_m7_normalize_classes(
		$text['settings']['_css_classes'] ?? '',
		array( $primary ? 'bp-m7-guidance__primary-body' : 'bp-m7-guidance__row-body' )
	);
	$text['settings']['css_classes'] = $text['settings']['_css_classes'];

	$elements = array( $heading, $text );

	if ( $primary ) {
		if ( ! $button ) {
			$button = array(
				'id'         => 'e7bc804',
				'elType'     => 'widget',
				'widgetType' => 'button',
				'settings'   => array(),
				'elements'   => array(),
			);
		}
		$button['settings']['text'] = $cta;
		$button['settings']['link'] = array(
			'url'         => $href,
			'is_external' => '',
			'nofollow'    => '',
			'custom_attributes' => '',
		);
		$button['settings']['align'] = 'left';
		$button['settings']['_css_classes'] = biopentra_m7_normalize_classes(
			$button['settings']['_css_classes'] ?? '',
			array( 'bp-m7-guidance__cta' )
		);
		$button['settings']['css_classes'] = $button['settings']['_css_classes'];
		// Neutral button chrome — visual is CSS text-link.
		$button['settings']['background_color'] = '';
		$button['settings']['button_text_color'] = '#0088b0';
		$button['settings']['border_border'] = '';
		$elements[] = $button;
	}

	$card['elements'] = $elements;
	return $card;
}

/**
 * Strip legacy page custom CSS that styles the old equal-card dark guidance band.
 *
 * @param string $css Page custom CSS.
 * @return string
 */
function biopentra_m7_strip_page_css( $css ) {
	$drop_needles = array(
		'elementor-element-238bcc6',
		'elementor-element-e0985d6',
		'elementor-element-16becb8',
		'elementor-element-1c171fb',
		'bp-lower-guidance',
		'bp-lower-card-guidance',
	);

	$parts  = preg_split( '/(?<=\})/', $css );
	$kept   = array();
	$saw    = false;
	$marker = "/* M7: guidance visual CSS migrated to biopentra-storefront home-v2.css */\n";

	foreach ( $parts as $part ) {
		$part = trim( $part );
		if ( $part === '' ) {
			continue;
		}

		// Mixed :is(...) with M5 + guidance cards — drop only guidance card IDs.
		if ( preg_match( '/:is\(/', $part ) && ( preg_match( '/e0985d6|16becb8|1c171fb/', $part ) ) ) {
			$cleaned = preg_replace( '/,?\s*\.elementor-element-(?:e0985d6|16becb8|1c171fb)/', '', $part );
			$cleaned = preg_replace( '/:is\(\s*,/', ':is(', $cleaned );
			$cleaned = preg_replace( '/:is\(\s*\)/', ':is()', $cleaned );
			if ( preg_match( '/:is\(\s*\)/', $cleaned ) || ! preg_match( '/\{/', $cleaned ) ) {
				$saw = true;
				continue;
			}
			$part = $cleaned;
			$saw  = true;
		}

		$is_m7 = false;
		foreach ( $drop_needles as $needle ) {
			if ( false !== strpos( $part, $needle ) ) {
				// Keep shared isolation that also lists M5 if present without being guidance-only chrome.
				if ( false !== strpos( $part, 'elementor-element-0b22897' ) && false !== strpos( $part, 'elementor-element-238bcc6' ) && false === strpos( $part, '::before' ) && false === strpos( $part, '::after' ) && false === strpos( $part, 'background' ) ) {
					// Drop 238bcc6 from shared selector lists so M5 isolation remains.
					$part = preg_replace( '/,\s*body\.page-id-\d+\s*\.elementor-element-238bcc6/', '', $part );
					$part = preg_replace( '/body\.page-id-\d+\s*\.elementor-element-238bcc6\s*,/', '', $part );
					$saw  = true;
					$is_m7 = false;
					break;
				}
				$is_m7 = true;
				break;
			}
		}
		if ( $is_m7 ) {
			$saw = true;
			continue;
		}
		$kept[] = $part;
	}

	$out = trim( implode( "\n\n", $kept ) );
	if ( $saw && false === strpos( $out, 'M7: guidance visual CSS' ) ) {
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

echo "M7 guidance CLI — home page ID {$home_id} (" . get_post_field( 'post_name', $home_id ) . ")\n";

$raw = get_post_meta( $home_id, '_elementor_data', true );
if ( ! is_string( $raw ) || $raw === '' ) {
	echo "ERROR: Missing _elementor_data.\n";
	return;
}

$data = json_decode( $raw, true );
if ( ! is_array( $data ) ) {
	echo "ERROR: Invalid _elementor_data JSON.\n";
	return;
}

$idx = biopentra_m7_find_section_index( $data, BIOPENTRA_M7_SECTION_ID );
if ( null === $idx ) {
	echo 'ERROR: Section ' . BIOPENTRA_M7_SECTION_ID . " not found.\n";
	return;
}

$section = $data[ $idx ];

$section['settings']['_css_classes'] = biopentra_m7_normalize_classes(
	$section['settings']['_css_classes'] ?? '',
	array( 'bp-m7-guidance', 'bp-lower-guidance' )
);
$section['settings']['css_classes']            = $section['settings']['_css_classes'];
$section['settings']['background_background']  = 'classic';
$section['settings']['background_color']       = '#f3f2f2';
$section['settings']['flex_direction']         = 'column';
$section['settings']['flex_gap']               = array(
	'unit'  => 'px',
	'size'  => 16,
	'column'=> '16',
	'row'   => '16',
);
$section['settings']['padding'] = array(
	'unit'     => 'px',
	'top'      => '40',
	'right'    => '24',
	'bottom'   => '48',
	'left'     => '24',
	'isLinked' => false,
);
$section['settings']['padding_tablet'] = array(
	'unit'     => 'px',
	'top'      => '32',
	'right'    => '20',
	'bottom'   => '40',
	'left'     => '20',
	'isLinked' => false,
);
$section['settings']['padding_mobile'] = array(
	'unit'     => 'px',
	'top'      => '28',
	'right'    => '16',
	'bottom'   => '36',
	'left'     => '16',
	'isLinked' => false,
);
$section['settings']['boxed_width'] = array(
	'unit'  => 'px',
	'size'  => 1240,
	'sizes' => array(),
);

$by_id = array();
foreach ( ( $section['elements'] ?? array() ) as $child ) {
	if ( ! empty( $child['id'] ) ) {
		$by_id[ $child['id'] ] = $child;
	}
}

$heading = $by_id[ BIOPENTRA_M7_HEADING_ID ] ?? null;
$layout  = $by_id[ BIOPENTRA_M7_LAYOUT_ID ] ?? null;
$intro   = $by_id[ BIOPENTRA_M7_INTRO_ID ] ?? null;

if ( ! $heading || ! $layout ) {
	echo 'ERROR: Expected heading ' . BIOPENTRA_M7_HEADING_ID . ' and layout ' . BIOPENTRA_M7_LAYOUT_ID . "\n";
	return;
}

$heading['settings']['title']       = 'Research & Product Guidance';
$heading['settings']['header_size'] = 'h2';
$heading['settings']['align']       = 'left';
$heading['settings']['title_color'] = '#0a303e';
$heading['settings']['_css_classes'] = biopentra_m7_normalize_classes(
	$heading['settings']['_css_classes'] ?? '',
	array( 'bp-m7-guidance__heading' )
);
$heading['settings']['css_classes'] = $heading['settings']['_css_classes'];

if ( ! $intro ) {
	$intro = array(
		'id'         => BIOPENTRA_M7_INTRO_ID,
		'elType'     => 'widget',
		'widgetType' => 'text-editor',
		'settings'   => array(),
		'elements'   => array(),
	);
}
$intro['settings']['editor']     = '<p>' . esc_html( BIOPENTRA_M7_INTRO ) . '</p>';
$intro['settings']['align']      = 'left';
$intro['settings']['text_color'] = '#486077';
$intro['settings']['_css_classes'] = biopentra_m7_normalize_classes(
	$intro['settings']['_css_classes'] ?? '',
	array( 'bp-m7-guidance__intro' )
);
$intro['settings']['css_classes'] = $intro['settings']['_css_classes'];

// Collect existing cards from layout (may already be transformed).
$layout_children = array();
foreach ( ( $layout['elements'] ?? array() ) as $c ) {
	if ( ! empty( $c['id'] ) ) {
		$layout_children[ $c['id'] ] = $c;
	}
}

// If secondary stack already exists, pull rows from it.
$sec_stack = $layout_children[ BIOPENTRA_M7_SEC_STACK ] ?? null;
if ( $sec_stack ) {
	foreach ( ( $sec_stack['elements'] ?? array() ) as $c ) {
		if ( ! empty( $c['id'] ) ) {
			$layout_children[ $c['id'] ] = $c;
		}
	}
}

$primary = $layout_children[ BIOPENTRA_M7_PRIMARY_ID ] ?? null;
$row_st  = $layout_children[ BIOPENTRA_M7_ROW_STORAGE ] ?? null;
$row_or  = $layout_children[ BIOPENTRA_M7_ROW_ORDER ] ?? null;

if ( ! $primary || ! $row_st || ! $row_or ) {
	echo "ERROR: Expected primary + two secondary card containers.\n";
	return;
}

$primary = biopentra_m7_build_item(
	$primary,
	BIOPENTRA_M7_PRIMARY_TITLE,
	BIOPENTRA_M7_PRIMARY_BODY,
	BIOPENTRA_M7_PRIMARY_HREF,
	BIOPENTRA_M7_PRIMARY_CTA,
	'bp-m7-guidance__primary',
	true
);

$row_st = biopentra_m7_build_item(
	$row_st,
	BIOPENTRA_M7_STORAGE_TITLE,
	BIOPENTRA_M7_STORAGE_BODY,
	BIOPENTRA_M7_STORAGE_HREF,
	'',
	'bp-m7-guidance__row',
	false
);

$row_or = biopentra_m7_build_item(
	$row_or,
	BIOPENTRA_M7_ORDER_TITLE,
	BIOPENTRA_M7_ORDER_BODY,
	BIOPENTRA_M7_ORDER_HREF,
	'',
	'bp-m7-guidance__row',
	false
);

if ( ! $sec_stack ) {
	$sec_stack = array(
		'id'       => BIOPENTRA_M7_SEC_STACK,
		'elType'   => 'container',
		'settings' => array(),
		'elements' => array(),
	);
}
$sec_stack['settings']['content_width'] = 'full';
$sec_stack['settings']['flex_direction'] = 'column';
$sec_stack['settings']['flex_gap'] = array(
	'unit'   => 'px',
	'size'   => 0,
	'column' => '0',
	'row'    => '0',
);
$sec_stack['settings']['width'] = array(
	'unit'  => '%',
	'size'  => 40,
	'sizes' => array(),
);
$sec_stack['settings']['width_tablet'] = array(
	'unit'  => '%',
	'size'  => 100,
	'sizes' => array(),
);
$sec_stack['settings']['width_mobile'] = array(
	'unit'  => '%',
	'size'  => 100,
	'sizes' => array(),
);
$sec_stack['settings']['_css_classes'] = biopentra_m7_normalize_classes(
	$sec_stack['settings']['_css_classes'] ?? '',
	array( 'bp-m7-guidance__secondary' )
);
$sec_stack['settings']['css_classes'] = $sec_stack['settings']['_css_classes'];
$sec_stack['elements'] = array( $row_st, $row_or );

$layout['settings']['content_width']  = 'full';
$layout['settings']['flex_direction'] = 'row';
$layout['settings']['flex_direction_tablet'] = 'column';
$layout['settings']['flex_direction_mobile'] = 'column';
$layout['settings']['flex_gap'] = array(
	'unit'   => 'px',
	'size'   => 28,
	'column' => '28',
	'row'    => '20',
);
$layout['settings']['_css_classes'] = biopentra_m7_normalize_classes(
	$layout['settings']['_css_classes'] ?? '',
	array( 'bp-m7-guidance__layout' )
);
$layout['settings']['css_classes'] = $layout['settings']['_css_classes'];
$layout['elements'] = array( $primary, $sec_stack );

$section['elements'] = array( $heading, $intro, $layout );
$data[ $idx ]        = $section;

update_post_meta( $home_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );

$page_settings = get_post_meta( $home_id, '_elementor_page_settings', true );
if ( ! is_array( $page_settings ) ) {
	$page_settings = array();
}
$before_css = (string) ( $page_settings['custom_css'] ?? '' );
$after_css  = biopentra_m7_strip_page_css( $before_css );
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

echo "M7 guidance CLI complete.\n";
echo '  section=' . BIOPENTRA_M7_SECTION_ID . " primary=" . BIOPENTRA_M7_PRIMARY_ID . "\n";
echo '  hrefs=/about/ /storage-handling/ /faq/\n';
echo '  page_custom_css_before=' . strlen( $before_css ) . ' after=' . strlen( $after_css ) . "\n";
echo "  idempotent: re-run refreshes structure; no duplicate secondary stack\n";
