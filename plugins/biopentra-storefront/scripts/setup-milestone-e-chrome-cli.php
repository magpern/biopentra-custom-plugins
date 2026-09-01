<?php
/**
 * Milestone E — idempotent header chrome + UMC placement CLI.
 *
 * - Backup reminder: run after docs backups exist
 * - Sets umc_settings.display.placement sticky_footer → manual
 * - Inserts [universal_multicurrency_switcher] shortcode into Elementor header 3782
 * - Inserts compact search control HTML widget into header 3782
 *
 * Usage:
 *   wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-milestone-e-chrome-cli.php
 *
 * Env:
 *   BIOPENTRA_E_HEADER_POST_ID=3782
 *   BIOPENTRA_E_SKIP_UMC=1     Skips the umc_settings option write (and its hard
 *                              exit) AND suppresses inserting the
 *                              [universal_multicurrency_switcher] shortcode
 *                              widget into the header. Use on hosts where the
 *                              universal-multicurrency plugin is not installed
 *                              (e.g. production before that cutover) so the
 *                              header does not render an empty/broken shortcode.
 *                              The compact search control is still inserted.
 *   BIOPENTRA_E_SKIP_HEADER=1
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$header_id = (int) ( getenv( 'BIOPENTRA_E_HEADER_POST_ID' ) ?: 3782 );
$skip_umc  = getenv( 'BIOPENTRA_E_SKIP_UMC' ) === '1';
$skip_hdr  = getenv( 'BIOPENTRA_E_SKIP_HEADER' ) === '1';

/**
 * @return string
 */
function biopentra_e_chrome_search_html() {
	return '<div class="bp-chrome-search">'
		. '<button type="button" class="bp-chrome-search__btn" data-bp-chrome-search aria-label="Search products">'
		. '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
		. '<circle cx="11" cy="11" r="7"></circle>'
		. '<line x1="21" y1="21" x2="16.65" y2="16.65"></line>'
		. '</svg>'
		. '</button></div>';
}

/**
 * @param array<int,mixed> $nodes
 * @param callable         $visitor
 */
function biopentra_e_walk_elements( array &$nodes, $visitor ) {
	foreach ( $nodes as &$node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		$visitor( $node );
		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			biopentra_e_walk_elements( $node['elements'], $visitor );
		}
	}
	unset( $node );
}

/**
 * @param array<int,mixed> $nodes
 * @return bool
 */
function biopentra_e_has_marker( array $nodes, $marker ) {
	$found = false;
	biopentra_e_walk_elements(
		$nodes,
		static function ( $node ) use ( &$found, $marker ) {
			if ( $found || ( $node['elType'] ?? '' ) !== 'widget' ) {
				return;
			}
			$settings = $node['settings'] ?? array();
			if ( ( $node['widgetType'] ?? '' ) === 'shortcode' ) {
				$sc = (string) ( $settings['shortcode'] ?? '' );
				if ( false !== strpos( $sc, $marker ) ) {
					$found = true;
				}
			}
			if ( ( $node['widgetType'] ?? '' ) === 'html' ) {
				$html = (string) ( $settings['html'] ?? '' );
				if ( false !== strpos( $html, $marker ) ) {
					$found = true;
				}
			}
		}
	);
	return $found;
}

/**
 * Insert a widget before woocommerce-menu-cart in the root header container.
 *
 * @param array<int,mixed>     $data
 * @param array<string,mixed>  $widget
 * @return bool inserted
 */
function biopentra_e_insert_before_cart( array &$data, array $widget ) {
	foreach ( $data as &$root ) {
		if ( ! is_array( $root ) || ( $root['elType'] ?? '' ) !== 'container' ) {
			continue;
		}
		if ( empty( $root['elements'] ) || ! is_array( $root['elements'] ) ) {
			continue;
		}
		$children = &$root['elements'];
		$cart_idx = null;
		foreach ( $children as $i => $child ) {
			if ( ! is_array( $child ) ) {
				continue;
			}
			if ( ( $child['elType'] ?? '' ) === 'widget' && ( $child['widgetType'] ?? '' ) === 'woocommerce-menu-cart' ) {
				$cart_idx = $i;
				break;
			}
		}
		if ( null === $cart_idx ) {
			continue;
		}
		array_splice( $children, $cart_idx, 0, array( $widget ) );
		return true;
	}
	unset( $root );
	return false;
}

// ── UMC placement ──────────────────────────────────────────────────────────
if ( ! $skip_umc ) {
	$umc = get_option( 'umc_settings' );
	if ( ! is_array( $umc ) ) {
		echo "ERROR: umc_settings missing or not an array\n";
		exit( 1 );
	}
	$prev = $umc['display']['placement'] ?? '(unset)';
	if ( ! isset( $umc['display'] ) || ! is_array( $umc['display'] ) ) {
		$umc['display'] = array();
	}
	$umc['display']['placement'] = 'manual';
	$umc['display']['enabled']   = true;
	update_option( 'umc_settings', $umc, false );
	$verify = get_option( 'umc_settings' );
	$now    = is_array( $verify ) ? ( $verify['display']['placement'] ?? '' ) : '';
	echo "UMC placement: {$prev} → {$now}\n";
	if ( 'manual' !== $now ) {
		echo "ERROR: failed to set umc_settings.display.placement=manual\n";
		exit( 1 );
	}
} else {
	echo "UMC: skipped\n";
}

// ── Header Elementor patch ─────────────────────────────────────────────────
if ( $skip_hdr ) {
	echo "Header: skipped\n";
	echo "OK\n";
	return;
}

$raw = get_post_meta( $header_id, '_elementor_data', true );
if ( empty( $raw ) ) {
	echo "ERROR: No _elementor_data for header {$header_id}\n";
	exit( 1 );
}

$data = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
if ( ! is_array( $data ) ) {
	echo "ERROR: could not decode Elementor JSON for {$header_id}\n";
	exit( 1 );
}

$changed = false;

if ( $skip_umc ) {
	echo "Header {$header_id}: UMC switcher widget skipped (SKIP_UMC)\n";
} elseif ( ! biopentra_e_has_marker( $data, 'universal_multicurrency_switcher' ) && ! biopentra_e_has_marker( $data, 'umc_switcher' ) ) {
	$umc_widget = array(
		'id'         => substr( md5( 'bp-e-umc-switcher' ), 0, 7 ),
		'elType'     => 'widget',
		'widgetType' => 'shortcode',
		'settings'   => array(
			'shortcode'    => '[universal_multicurrency_switcher]',
			'_css_classes' => 'bp-chrome-umc',
		),
		'elements'   => array(),
	);
	if ( ! biopentra_e_insert_before_cart( $data, $umc_widget ) ) {
		echo "ERROR: could not find woocommerce-menu-cart to insert UMC shortcode\n";
		exit( 1 );
	}
	$changed = true;
	echo "Header {$header_id}: inserted UMC shortcode widget\n";
} else {
	echo "Header {$header_id}: UMC shortcode already present\n";
}

if ( ! biopentra_e_has_marker( $data, 'data-bp-chrome-search' ) ) {
	$search_widget = array(
		'id'         => substr( md5( 'bp-e-chrome-search' ), 0, 7 ),
		'elType'     => 'widget',
		'widgetType' => 'html',
		'settings'   => array(
			'html'         => biopentra_e_chrome_search_html(),
			'_css_classes' => 'bp-chrome-search-wrap',
		),
		'elements'   => array(),
	);
	if ( ! biopentra_e_insert_before_cart( $data, $search_widget ) ) {
		echo "ERROR: could not find woocommerce-menu-cart to insert search control\n";
		exit( 1 );
	}
	$changed = true;
	echo "Header {$header_id}: inserted chrome search control\n";
} else {
	echo "Header {$header_id}: search control already present\n";
}

if ( $changed ) {
	$encoded = wp_json_encode( $data );
	if ( false === $encoded ) {
		echo "ERROR: json_encode failed\n";
		exit( 1 );
	}
	update_post_meta( $header_id, '_elementor_data', wp_slash( $encoded ) );
	delete_post_meta( $header_id, '_elementor_element_cache' );
	delete_post_meta( $header_id, '_elementor_css' );
	if ( class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
	echo "Header {$header_id}: saved + Elementor caches cleared\n";
} else {
	echo "Header {$header_id}: no structural changes\n";
}

echo "OK\n";
