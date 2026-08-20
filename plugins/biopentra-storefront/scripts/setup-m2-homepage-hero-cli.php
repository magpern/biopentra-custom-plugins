<?php
/**
 * M2 — Premium Ecommerce homepage hero (idempotent Elementor patch).
 *
 * Does NOT rebuild Milestone A homepage IA. Only patches hero + related page CSS.
 *
 * Run:
 *   cd /opt/biopentra/apps/wordpress
 *   docker compose run --rm -T wpcli wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-m2-homepage-hero-cli.php
 *
 * Backup _elementor_data and _elementor_page_settings before first run.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param array    $nodes Tree (by ref).
 * @param string   $id    Element id.
 * @param callable $cb    function( array &$node ): void
 * @return bool
 */
function biopentra_m2_hero_walk_patch( array &$nodes, $id, $cb ) {
	foreach ( $nodes as &$node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		if ( isset( $node['id'] ) && $node['id'] === $id ) {
			$cb( $node );
			unset( $node );
			return true;
		}
		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			if ( biopentra_m2_hero_walk_patch( $node['elements'], $id, $cb ) ) {
				unset( $node );
				return true;
			}
		}
	}
	unset( $node );
	return false;
}

/**
 * Deduplicate space-separated CSS classes and ensure markers.
 *
 * @param string $classes Existing classes.
 * @param array  $ensure  Classes that must be present once.
 * @return string
 */
function biopentra_m2_hero_normalize_classes( $classes, array $ensure ) {
	$parts = preg_split( '/\s+/', trim( (string) $classes ) );
	$parts = array_values( array_filter( $parts, static function ( $p ) {
		return $p !== '';
	} ) );
	$seen  = array();
	$out   = array();
	foreach ( $parts as $p ) {
		if ( isset( $seen[ $p ] ) ) {
			continue;
		}
		$seen[ $p ] = true;
		$out[]      = $p;
	}
	foreach ( $ensure as $need ) {
		if ( ! isset( $seen[ $need ] ) ) {
			$out[]        = $need;
			$seen[ $need ] = true;
		}
	}
	return implode( ' ', $out );
}

/**
 * Remove hero-scoped rules from Elementor page custom CSS (keep non-hero rules).
 *
 * @param string $css Custom CSS.
 * @return string
 */
function biopentra_m2_hero_strip_page_css( $css ) {
	$css = (string) $css;
	if ( $css === '' ) {
		return $css;
	}

	// Drop the 2026-05-25 hero band block selectors (migrated to home-v2.css).
	$hero_needles = array(
		'elementor-element-41734c4',
		'elementor-element-2ac6697',
		'elementor-element-2f51258',
		'elementor-element-3fb1a1d',
		'elementor-element-ce05168',
		'elementor-element-9d79260',
		'elementor-element-52085d0',
		'bp-hero-accent',
	);

	// Split into rough rule blocks; keep blocks that do not reference hero needles.
	$parts  = preg_split( '/(?<=\})/', $css );
	$kept   = array();
	$marker = "/* M2: hero visual CSS migrated to biopentra-storefront home-v2.css */\n";
	$saw_hero = false;

	foreach ( $parts as $part ) {
		$part = trim( $part );
		if ( $part === '' ) {
			continue;
		}
		$is_hero = false;
		foreach ( $hero_needles as $needle ) {
			if ( strpos( $part, $needle ) !== false ) {
				$is_hero = true;
				break;
			}
		}
		if ( $is_hero ) {
			$saw_hero = true;
			continue;
		}
		$kept[] = $part;
	}

	$out = trim( implode( "\n\n", $kept ) );
	if ( $saw_hero ) {
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

echo "M2 hero CLI — home page ID {$home_id} (" . get_post_field( 'post_name', $home_id ) . ")\n";

$raw  = get_post_meta( $home_id, '_elementor_data', true );
$data = is_string( $raw ) ? json_decode( $raw, true ) : null;

if ( ! is_array( $data ) || empty( $data ) ) {
	echo "ERROR: No Elementor data on home page.\n";
	return;
}

$hero_idx = null;
foreach ( $data as $i => $section ) {
	if ( ( $section['id'] ?? '' ) === '41734c4' ) {
		$hero_idx = $i;
		break;
	}
}

if ( null === $hero_idx ) {
	echo "ERROR: Missing hero section id 41734c4.\n";
	return;
}

$shop_url = function_exists( 'wc_get_page_id' ) ? get_permalink( (int) wc_get_page_id( 'shop' ) ) : home_url( '/shop/' );
$ship     = get_page_by_path( 'shipping-policy' );
$ship_url = $ship ? get_permalink( $ship ) : home_url( '/shipping-policy/' );
$hero_url = function_exists( 'biopentra_storefront_home_hero_image_url' )
	? biopentra_storefront_home_hero_image_url()
	: ( wp_get_attachment_image_url( 4520, 'full' ) ?: content_url( 'uploads/2026/05/home-hero.webp' ) );

$hero = &$data[ $hero_idx ];

$hero['settings']['css_classes'] = biopentra_m2_hero_normalize_classes(
	$hero['settings']['css_classes'] ?? '',
	array( 'bp-home-hero', 'bp-m2-hero' )
);

// Compact Elementor mins — CSS owns preferred/max density.
$hero['settings']['min_height']        = array(
	'unit'  => 'px',
	'size'  => 200,
	'sizes' => array(),
);
$hero['settings']['min_height_tablet'] = array(
	'unit'  => 'px',
	'size'  => 220,
	'sizes' => array(),
);
$hero['settings']['min_height_mobile'] = array(
	'unit'  => 'px',
	'size'  => 180,
	'sizes' => array(),
);

$hero['settings']['padding'] = array(
	'unit'     => 'px',
	'top'      => '28',
	'right'    => '16',
	'bottom'   => '28',
	'left'     => '16',
	'isLinked' => false,
);
$hero['settings']['padding_tablet'] = array(
	'unit'     => 'px',
	'top'      => '28',
	'right'    => '24',
	'bottom'   => '28',
	'left'     => '24',
	'isLinked' => false,
);
$hero['settings']['padding_mobile'] = array(
	'unit'     => 'px',
	'top'      => '20',
	'right'    => '16',
	'bottom'   => '20',
	'left'     => '16',
	'isLinked' => false,
);

// Prefer env-local attachment URL in Elementor data (CSS also sets --bp-home-hero-image).
if ( ! isset( $hero['settings']['background_image'] ) || ! is_array( $hero['settings']['background_image'] ) ) {
	$hero['settings']['background_image'] = array();
}
$hero['settings']['background_image']['url'] = $hero_url;
$hero['settings']['background_image']['id']  = 4520;
$hero['settings']['background_background']   = 'classic';
$hero['settings']['background_position']     = 'center right';
$hero['settings']['background_position_tablet'] = 'center center';
$hero['settings']['background_position_mobile'] = '58% center';
$hero['settings']['background_size']         = 'cover';

// Soften Elementor overlay toward teal (CSS gradients are authoritative).
$hero['settings']['background_overlay_background'] = 'classic';
$hero['settings']['background_overlay_color']      = '#0a303e';
$hero['settings']['background_overlay_opacity']    = array(
	'unit'  => 'px',
	'size'  => 0.55,
	'sizes' => array(),
);

// H1 type sizes — CSS clamp is authoritative; keep Elementor values sane if CSS fails.
biopentra_m2_hero_walk_patch(
	$hero['elements'],
	'2f51258',
	static function ( array &$node ) {
		$node['settings']['typography_font_size'] = array(
			'unit'  => 'px',
			'size'  => 36,
			'sizes' => array(),
		);
		$node['settings']['typography_font_size_tablet'] = array(
			'unit'  => 'px',
			'size'  => 32,
			'sizes' => array(),
		);
		$node['settings']['typography_font_size_mobile'] = array(
			'unit'  => 'px',
			'size'  => 28,
			'sizes' => array(),
		);
		$node['settings']['title_color'] = '#FFFFFF';
	}
);

// Keep subcopy empty (visual-only default).
biopentra_m2_hero_walk_patch(
	$hero['elements'],
	'3fb1a1d',
	static function ( array &$node ) {
		if ( ( $node['widgetType'] ?? '' ) === 'text-editor' ) {
			$node['settings']['editor'] = '';
		}
	}
);

// Nested CTAs under ce05168 — environment-relative destinations.
biopentra_m2_hero_walk_patch(
	$hero['elements'],
	'9d79260',
	static function ( array &$node ) use ( $shop_url ) {
		$node['settings']['text'] = 'Shop Products';
		$node['settings']['link'] = array(
			'url'               => $shop_url,
			'is_external'       => '',
			'nofollow'          => '',
			'custom_attributes' => '',
		);
	}
);

biopentra_m2_hero_walk_patch(
	$hero['elements'],
	'52085d0',
	static function ( array &$node ) use ( $ship_url ) {
		$node['settings']['text'] = 'Learn About Ordering';
		$node['settings']['link'] = array(
			'url'               => $ship_url,
			'is_external'       => '',
			'nofollow'          => '',
			'custom_attributes' => '',
		);
	}
);

$json = wp_json_encode( $data );
update_post_meta( $home_id, '_elementor_data', wp_slash( $json ) );

// Migrate / strip hero rules from page custom CSS.
$page_settings = get_post_meta( $home_id, '_elementor_page_settings', true );
if ( ! is_array( $page_settings ) ) {
	$page_settings = array();
}
$before_css = (string) ( $page_settings['custom_css'] ?? '' );
$after_css  = biopentra_m2_hero_strip_page_css( $before_css );
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

echo "M2 hero patch applied.\n";
echo "  shop_url={$shop_url}\n";
echo "  ship_url={$ship_url}\n";
echo "  hero_image={$hero_url}\n";
echo "  css_classes={$hero['settings']['css_classes']}\n";
echo "  page_custom_css_before=" . strlen( $before_css ) . " after=" . strlen( $after_css ) . "\n";
echo "Run: wp elementor flush-css && wp cache flush\n";
