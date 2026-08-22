<?php
/**
 * M10 — Contact page experience (idempotent Elementor mutation on page 3410).
 *
 * Removes redundant mode cards, bottom band, self-ref Contact-form CTA, and
 * Elementor prefill widget. Inserts [biopentra_contact_channels] shortcode rail.
 *
 * Writes first-run backup to docs/storefront-redesign/changes/backups/contact-pre-M10.json.
 *
 * Usage:
 *   wp eval-file wp-content/plugins/biopentra-storefront/scripts/setup-m10-contact-page-cli.php
 *
 * Env:
 *   BIOPENTRA_M10_CONTACT_POST_ID=3410
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$contact_id = (int) ( getenv( 'BIOPENTRA_M10_CONTACT_POST_ID' ) ?: 3410 );

echo "Contact page ID {$contact_id}\n";

$raw = get_post_meta( $contact_id, '_elementor_data', true );
if ( empty( $raw ) ) {
	echo "ERROR: No _elementor_data for contact {$contact_id}\n";
	exit( 1 );
}

$data = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
if ( ! is_array( $data ) ) {
	echo "ERROR: could not decode Elementor JSON for {$contact_id}\n";
	exit( 1 );
}

/**
 * @param array<int,mixed> $nodes
 * @return array<string,mixed>|null
 */
function biopentra_m10_find( array $nodes, string $id ) {
	foreach ( $nodes as $node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		if ( ( $node['id'] ?? '' ) === $id ) {
			return $node;
		}
		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			$found = biopentra_m10_find( $node['elements'], $id );
			if ( null !== $found ) {
				return $found;
			}
		}
	}
	return null;
}

/**
 * @param array<int,mixed> $nodes
 * @return array<string,mixed>|null
 */
function biopentra_m10_extract( array &$nodes, string $id ) {
	foreach ( $nodes as $i => &$node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		if ( ( $node['id'] ?? '' ) === $id ) {
			$removed = $node;
			unset( $nodes[ $i ] );
			$nodes   = array_values( $nodes );
			return $removed;
		}
		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			$removed = biopentra_m10_extract( $node['elements'], $id );
			if ( null !== $removed ) {
				unset( $node );
				return $removed;
			}
		}
	}
	unset( $node );
	return null;
}

/**
 * @param array<string,mixed> $node
 */
function biopentra_m10_add_class( array &$node, string $class ) {
	if ( ! isset( $node['settings'] ) || ! is_array( $node['settings'] ) ) {
		$node['settings'] = array();
	}
	$existing = (string) ( $node['settings']['_css_classes'] ?? $node['settings']['css_classes'] ?? '' );
	$classes  = array_filter( explode( ' ', $existing ) );
	if ( ! in_array( $class, $classes, true ) ) {
		$classes[] = $class;
	}
	$joined                           = implode( ' ', $classes );
	$node['settings']['_css_classes'] = $joined;
	$node['settings']['css_classes']  = $joined;
}

/**
 * @param array<int,mixed> $nodes
 */
function biopentra_m10_patch( array &$nodes, string $id, callable $cb ): bool {
	foreach ( $nodes as &$node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		if ( ( $node['id'] ?? '' ) === $id ) {
			$cb( $node );
			unset( $node );
			return true;
		}
		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			if ( biopentra_m10_patch( $node['elements'], $id, $cb ) ) {
				unset( $node );
				return true;
			}
		}
	}
	unset( $node );
	return false;
}

foreach ( array( 'ef97090', 'a3a80db', '4e80a62', 'b45c068', '733fa02', '51c4ced', '646ab51', '274a45c' ) as $rid ) {
	if ( null === biopentra_m10_find( $data, $rid ) ) {
		echo "ERROR: Missing expected element id {$rid} — structure drifted, refusing to proceed\n";
		exit( 1 );
	}
}

$repo_backup     = '/opt/biopentra/dev/biopentra-custom-plugins/docs/storefront-redesign/changes/backups/contact-pre-M10.json';
$uploads         = wp_upload_dir();
$fallback_backup = trailingslashit( $uploads['basedir'] ) . 'biopentra-m10-backups/contact-pre-M10.json';
$backup_payload  = is_string( $raw ) ? $raw : wp_json_encode( $data );

if ( is_dir( dirname( $repo_backup ) ) && ! file_exists( $repo_backup ) ) {
	file_put_contents( $repo_backup, $backup_payload );
	echo "Backup written: {$repo_backup}\n";
} elseif ( file_exists( $repo_backup ) ) {
	echo "Backup already present: {$repo_backup} (not overwritten)\n";
} else {
	wp_mkdir_p( dirname( $fallback_backup ) );
	if ( ! file_exists( $fallback_backup ) ) {
		file_put_contents( $fallback_backup, $backup_payload );
		echo "Fallback backup written: {$fallback_backup}\n";
	} else {
		echo "Fallback backup already present: {$fallback_backup} (not overwritten)\n";
	}
}

// Remove bottom promo band.
if ( null !== biopentra_m10_extract( $data, 'fff6430' ) ) {
	echo "Removed bottom band fff6430.\n";
} else {
	echo "Bottom band fff6430 already absent (idempotent).\n";
}

// Remove mode cards and Elementor prefill widget from form surface.
foreach ( array( '123d679', 'd3307cf' ) as $remove_id ) {
	if ( null !== biopentra_m10_extract( $data, $remove_id ) ) {
		echo "Removed {$remove_id}.\n";
	}
}

// Replace quick-response card contents with shortcode rail.
biopentra_m10_patch(
	$data,
	'3ecdd8b',
	static function ( array &$node ) {
		$node['elements'] = array(
			array(
				'id'         => 'm10chnls',
				'elType'     => 'widget',
				'widgetType' => 'shortcode',
				'settings'   => array(
					'shortcode' => '[biopentra_contact_channels]',
				),
				'elements'   => array(),
			),
		);
		$node['settings']['background_background'] = 'classic';
		$node['settings']['background_color']      = 'transparent';
		$node['settings']['border_border']         = 'none';
		$node['settings']['padding']               = array(
			'unit'     => 'px',
			'top'      => '0',
			'right'    => '0',
			'bottom'   => '0',
			'left'     => '0',
			'isLinked' => true,
		);
		biopentra_m10_add_class( $node, 'bp-m10-rail-inner' );
	}
);

biopentra_m10_patch(
	$data,
	'b45c068',
	static function ( array &$node ) {
		biopentra_m10_add_class( $node, 'bp-m10-body' );
	}
);

biopentra_m10_patch(
	$data,
	'733fa02',
	static function ( array &$node ) {
		biopentra_m10_add_class( $node, 'bp-m10-rail' );
		$node['settings']['width'] = array( 'unit' => '%', 'size' => 34 );
	}
);

biopentra_m10_patch(
	$data,
	'51c4ced',
	static function ( array &$node ) {
		biopentra_m10_add_class( $node, 'bp-m10-form-col' );
		$node['settings']['width'] = array( 'unit' => '%', 'size' => 66 );
	}
);

biopentra_m10_patch(
	$data,
	'274a45c',
	static function ( array &$node ) {
		biopentra_m10_add_class( $node, 'bp-m10-form-surface' );
	}
);

biopentra_m10_patch(
	$data,
	'1a019e3',
	static function ( array &$node ) {
		biopentra_m10_add_class( $node, 'bp-m10-form-intro' );
		$node['settings']['editor'] = '<p style="margin:0;color:#3D4F66;line-height:1.55;">Choose a reason below and send us your message. We will get back to you as soon as possible.</p>';
	}
);

biopentra_m10_patch(
	$data,
	'8427aa7',
	static function ( array &$node ) {
		biopentra_m10_add_class( $node, 'bp-m10-privacy' );
	}
);

biopentra_m10_patch(
	$data,
	'4e80a62',
	static function ( array &$node ) {
		$node['settings']['padding'] = array(
			'unit'     => 'px',
			'top'      => '32',
			'right'    => '20',
			'bottom'   => '48',
			'left'     => '20',
			'isLinked' => false,
		);
	}
);

$encoded = wp_json_encode( $data );
if ( ! $encoded ) {
	echo "ERROR: failed to encode Elementor JSON\n";
	exit( 1 );
}

update_post_meta( $contact_id, '_elementor_data', wp_slash( $encoded ) );
update_post_meta( $contact_id, 'bp_contact_m10_applied', '1' );

if ( class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}

echo "M10 contact page structure applied to {$contact_id}.\n";
