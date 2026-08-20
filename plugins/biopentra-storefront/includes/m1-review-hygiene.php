<?php
/**
 * M1 review hygiene — disable V1A visual trial without removing its files.
 *
 * Enable again: remove this filter, or:
 *   remove_filter( 'biopentra_v1a_specimen_enabled', '__return_false' );
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'biopentra_v1a_specimen_enabled', '__return_false' );
