<?php
/**
 * M8 — idempotent Elementor footer 3823 restructure: Direction B
 * (dark/structured endpoint), email-shortcode widget removal, Contact-form
 * CTA + Telegram relocated into the brand zone, Information/Legal regrouped
 * into one navigation block.
 *
 * Writes a pre-change backup of _elementor_data to
 * docs/storefront-redesign/changes/backups/footer-pre-M8.json on its first
 * run (never overwritten by later idempotent re-runs).
 *
 * Usage:
 *   wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-m8-global-footer-cli.php
 *
 * Env:
 *   BIOPENTRA_M8_FOOTER_POST_ID=3823
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$footer_id = (int) ( getenv( 'BIOPENTRA_M8_FOOTER_POST_ID' ) ?: 3823 );

$raw = get_post_meta( $footer_id, '_elementor_data', true );
if ( empty( $raw ) ) {
	echo "ERROR: No _elementor_data for footer {$footer_id}\n";
	exit( 1 );
}

$data = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
if ( ! is_array( $data ) ) {
	echo "ERROR: could not decode Elementor JSON for {$footer_id}\n";
	exit( 1 );
}

/**
 * Find a node by id anywhere in the tree.
 *
 * @param array<int,mixed> $nodes
 * @return array<string,mixed>|null Reference-free copy; use find_ref for mutation.
 */
function biopentra_m8_find( array $nodes, string $id ) {
	foreach ( $nodes as $node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		if ( ( $node['id'] ?? '' ) === $id ) {
			return $node;
		}
		if ( ! empty( $node['elements'] ) ) {
			$found = biopentra_m8_find( $node['elements'], $id );
			if ( null !== $found ) {
				return $found;
			}
		}
	}
	return null;
}

/**
 * Remove a node by id anywhere in the tree (mutates in place). Returns the
 * removed node, or null if not found.
 *
 * @param array<int,mixed> $nodes
 * @return array<string,mixed>|null
 */
function biopentra_m8_extract( array &$nodes, string $id ) {
	foreach ( $nodes as $i => &$node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		if ( ( $node['id'] ?? '' ) === $id ) {
			$removed = $node;
			unset( $nodes[ $i ] );
			$nodes = array_values( $nodes );
			return $removed;
		}
		if ( ! empty( $node['elements'] ) ) {
			$removed = biopentra_m8_extract( $node['elements'], $id );
			if ( null !== $removed ) {
				return $removed;
			}
		}
	}
	unset( $node );
	return null;
}

/**
 * Append a CSS class to a node's settings (idempotent).
 *
 * @param array<string,mixed> $node
 */
function biopentra_m8_add_class( array &$node, string $class ) {
	if ( ! isset( $node['settings'] ) || ! is_array( $node['settings'] ) ) {
		$node['settings'] = array();
	}
	$existing = (string) ( $node['settings']['_css_classes'] ?? '' );
	$classes  = array_filter( explode( ' ', $existing ) );
	if ( ! in_array( $class, $classes, true ) ) {
		$classes[] = $class;
	}
	$joined                                  = implode( ' ', $classes );
	$node['settings']['_css_classes']        = $joined;
	$node['settings']['css_classes']         = $joined;
}

// ── Idempotency guard: already restructured? ──
$root = null;
foreach ( $data as $n ) {
	if ( ( $n['id'] ?? '' ) === '782cf0a' ) {
		$root = $n;
		break;
	}
}
if ( null === $root ) {
	echo "ERROR: footer root 782cf0a not found — structure drifted from frozen plan, refusing to proceed\n";
	exit( 1 );
}
$already_done = ( ( $root['settings']['_css_classes'] ?? '' ) === 'bp-ft2-root' )
	|| false !== strpos( (string) ( $root['settings']['_css_classes'] ?? '' ), 'bp-ft2-root' );

if ( $already_done ) {
	echo "Footer {$footer_id}: already restructured for M8 (idempotent no-op)\n";
	echo "OK\n";
	exit( 0 );
}

// ── Backup (only if not already captured) ──
// The wpcli container only bind-mounts the plugin directory (see
// apps/wordpress/compose.yml), not the whole monorepo, so the git-tracked
// docs/storefront-redesign/changes/backups/footer-pre-M8.json path is not
// reachable from inside the container on this dev host. Try it anyway (it
// IS reachable during a full-repo-checkout production replay), then fall
// back to the WordPress uploads dir, which the container always has
// read/write access to and which persists on the host bind mount.
$repo_backup = '/opt/biopentra/dev/biopentra-custom-plugins/docs/storefront-redesign/changes/backups/footer-pre-M8.json';
$uploads     = wp_upload_dir();
$fallback_backup = trailingslashit( $uploads['basedir'] ) . 'biopentra-m8-backups/footer-pre-M8.json';

if ( is_dir( dirname( $repo_backup ) ) && ! file_exists( $repo_backup ) ) {
	file_put_contents( $repo_backup, wp_json_encode( $data, JSON_PRETTY_PRINT ) );
	echo "Backup written: {$repo_backup}\n";
} elseif ( file_exists( $repo_backup ) ) {
	echo "Backup already present: {$repo_backup} (not overwritten)\n";
} else {
	wp_mkdir_p( dirname( $fallback_backup ) );
	if ( ! file_exists( $fallback_backup ) ) {
		file_put_contents( $fallback_backup, wp_json_encode( $data, JSON_PRETTY_PRINT ) );
		echo "Repo backup path not reachable from this container; fallback backup written: {$fallback_backup}\n";
		echo "Operator: copy this file into docs/storefront-redesign/changes/backups/footer-pre-M8.json in the repo.\n";
	} else {
		echo "Fallback backup already present: {$fallback_backup} (not overwritten)\n";
	}
}

// ── 1. Remove the email shortcode widget + its now-empty Contact column ──
$telegram_widget = biopentra_m8_extract( $data, '092e844' );
$email_widget     = biopentra_m8_extract( $data, 'f6906c1' ); // discarded — this is the removal.
$contact_heading  = biopentra_m8_extract( $data, 'cae4044' ); // discarded — "Contact" column heading no longer needed.
$contact_column   = biopentra_m8_extract( $data, 'f352048' ); // discarded — dissolved into brand zone.

if ( null === $telegram_widget ) {
	echo "ERROR: Telegram widget 092e844 not found before extraction — aborting without saving\n";
	exit( 1 );
}
if ( null === $email_widget ) {
	echo "NOTE: email widget f6906c1 not found (already removed?) — continuing\n";
}

// ── 2. Remove "Contact" from the Information nav list (widget 696f38d) ──
$info_html_id = '696f38d';
biopentra_m8_mutate( $data, $info_html_id, function ( array &$node ) {
	$html = (string) ( $node['settings']['html'] ?? '' );
	$html = preg_replace( '#<li><a href="/contact/">Contact</a></li>#', '', $html, 1 );
	$node['settings']['html'] = $html;
} );

/**
 * Mutate a node's settings in place by id, via callback($node).
 *
 * @param array<int,mixed> $nodes
 */
function biopentra_m8_mutate( array &$nodes, string $id, callable $cb ) {
	foreach ( $nodes as &$node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		if ( ( $node['id'] ?? '' ) === $id ) {
			$cb( $node );
			return true;
		}
		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			if ( biopentra_m8_mutate( $node['elements'], $id, $cb ) ) {
				return true;
			}
		}
	}
	unset( $node );
	return false;
}

// ── 3. Build the new Contact-form CTA button widget ──
$contact_url = home_url( '/contact/' );
$cta_widget  = array(
	'id'         => 'm8ftcta1',
	'elType'     => 'widget',
	'widgetType' => 'button',
	'settings'   => array(
		'text'          => 'Contact form',
		'link'          => array(
			'url'         => $contact_url,
			'is_external' => '',
			'nofollow'    => '',
		),
		'size'          => 'sm',
		'_css_classes'  => 'bp-ft2-cta',
		'css_classes'   => 'bp-ft2-cta',
	),
	'elements'   => array(),
);
biopentra_m8_add_class( $telegram_widget, 'bp-ft2-telegram' );

// ── 4. Assemble the new brand zone: logo, copy, CTA, Telegram ──
$brand_zone = biopentra_m8_find( $data, '90e7b9a' );
if ( null === $brand_zone ) {
	echo "ERROR: brand zone 90e7b9a not found — aborting\n";
	exit( 1 );
}
biopentra_m8_mutate( $data, '90e7b9a', function ( array &$node ) use ( $cta_widget, $telegram_widget ) {
	$node['settings']['width']        = array( 'unit' => '%', 'size' => 54, 'sizes' => array() );
	$node['settings']['width_mobile'] = array( 'unit' => '%', 'size' => 100, 'sizes' => array() );
	$node['elements'][]               = $cta_widget;
	$node['elements'][]               = $telegram_widget;
} );
biopentra_m8_add_class_by_id( $data, '90e7b9a', 'bp-ft2-brand' );
biopentra_m8_add_class_by_id( $data, 'b869098', 'bp-ft2-logo' );
biopentra_m8_add_class_by_id( $data, '0ffe789', 'bp-ft2-copy' );

/**
 * @param array<int,mixed> $nodes
 */
function biopentra_m8_add_class_by_id( array &$nodes, string $id, string $class ) {
	biopentra_m8_mutate( $nodes, $id, function ( array &$node ) use ( $class ) {
		biopentra_m8_add_class( $node, $class );
	} );
}

// ── 5. Regroup Information + Legal into one navigation-zone container ──
$info_col  = biopentra_m8_extract( $data, '5a08358' );
$legal_col = biopentra_m8_extract( $data, '537f779' );
if ( null === $info_col || null === $legal_col ) {
	echo "ERROR: Information/Legal columns not found — aborting without saving\n";
	exit( 1 );
}
biopentra_m8_add_class( $info_col, 'bp-ft2-navcol' );
biopentra_m8_add_class( $legal_col, 'bp-ft2-navcol' );
$info_col['settings']['width']         = array( 'unit' => '%', 'size' => 50, 'sizes' => array() );
$legal_col['settings']['width']        = array( 'unit' => '%', 'size' => 50, 'sizes' => array() );
$info_col['settings']['width_mobile']  = array( 'unit' => '%', 'size' => 100, 'sizes' => array() );
$legal_col['settings']['width_mobile'] = array( 'unit' => '%', 'size' => 100, 'sizes' => array() );

$navzone = array(
	'id'       => 'm8ftnavz1',
	'elType'   => 'container',
	'settings' => array(
		'flex_direction'    => 'row',
		'flex_gap'          => array( 'column' => '32', 'row' => '32', 'isLinked' => true, 'unit' => 'px', 'size' => 32 ),
		'width'             => array( 'unit' => '%', 'size' => 42, 'sizes' => array() ),
		'width_mobile'      => array( 'unit' => '%', 'size' => 100, 'sizes' => array() ),
		'flex_direction_mobile' => 'row',
		'flex_gap_mobile'   => array( 'column' => '16', 'row' => '16', 'isLinked' => true, 'unit' => 'px', 'size' => 16 ),
		'_css_classes'      => 'bp-ft2-navzone',
		'css_classes'       => 'bp-ft2-navzone',
	),
	'elements' => array( $info_col, $legal_col ),
);

biopentra_m8_mutate( $data, '77baf59', function ( array &$node ) use ( $navzone ) {
	$node['elements'][] = $navzone;
} );
biopentra_m8_add_class_by_id( $data, '77baf59', 'bp-ft2-band' );

// ── 6. Root: dark ground + class hook, tighter section rhythm ──
biopentra_m8_mutate( $data, '782cf0a', function ( array &$node ) {
	$node['settings']['background_background'] = 'classic';
	$node['settings']['background_color']      = '#0f1f33';
	$node['settings']['flex_gap']              = array( 'column' => '28', 'row' => '28', 'isLinked' => true, 'unit' => 'px', 'size' => 28 );
	$node['settings']['padding']               = array( 'unit' => 'px', 'top' => '40', 'right' => '32', 'bottom' => '24', 'left' => '32', 'isLinked' => false );
} );
biopentra_m8_add_class_by_id( $data, '782cf0a', 'bp-ft2-root' );

// ── 7. Utility/bottom band: class hook, dark-appropriate hairline ──
biopentra_m8_mutate( $data, 'cf46e8f', function ( array &$node ) {
	$node['settings']['border_color'] = '#23324a';
} );
biopentra_m8_add_class_by_id( $data, 'cf46e8f', 'bp-ft2-utility' );
biopentra_m8_add_class_by_id( $data, '24736b6', 'bp-ft2-payrow' );
biopentra_m8_add_class_by_id( $data, 'payLogos3823', 'bp-ft2-paylogos' );
biopentra_m8_add_class_by_id( $data, 'e5be984', 'bp-ft2-legalbar' );
biopentra_m8_add_class_by_id( $data, 'payLabel3823', 'bp-ft2-paylabel' );

// ── Save ──
$encoded = wp_json_encode( $data );
if ( false === $encoded ) {
	echo "ERROR: json_encode failed\n";
	exit( 1 );
}
update_post_meta( $footer_id, '_elementor_data', wp_slash( $encoded ) );
delete_post_meta( $footer_id, '_elementor_element_cache' );
delete_post_meta( $footer_id, '_elementor_css' );
if ( class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}

echo "Footer {$footer_id}: M8 restructure applied + Elementor caches cleared\n";
echo "OK\n";
