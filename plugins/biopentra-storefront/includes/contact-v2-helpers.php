<?php
/**
 * M10 — Contact page channels shortcode and body class.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contact page post ID (DEV default 3410).
 *
 * @return int
 */
function biopentra_storefront_contact_page_id() {
	$from_env = (int) ( getenv( 'BIOPENTRA_M10_CONTACT_POST_ID' ) ?: 0 );
	if ( $from_env > 0 ) {
		return $from_env;
	}

	return 3410;
}

/**
 * Whether the current request is the M10 Contact page.
 *
 * @return bool
 */
function biopentra_storefront_is_contact_page() {
	return is_page( biopentra_storefront_contact_page_id() );
}

/**
 * Site-level chat enabled (delegates to Universal Telegram when present).
 *
 * @return bool
 */
function biopentra_storefront_contact_chat_is_enabled() {
	if ( function_exists( 'universal_telegram_chat_is_enabled' ) ) {
		return universal_telegram_chat_is_enabled();
	}

	return false;
}

/**
 * Telegram destination URL for Contact surfaces.
 *
 * @return string
 */
function biopentra_storefront_contact_telegram_url() {
	return 'https://t.me/biopentra';
}

/**
 * Render the subordinate contact-channels rail.
 *
 * @return string
 */
function biopentra_storefront_contact_channels_html() {
	$chat_enabled = biopentra_storefront_contact_chat_is_enabled();
	$telegram_url = biopentra_storefront_contact_telegram_url();

	$html = '<div class="bp-m10-channels">';

	$html .= '<p class="bp-m10-channels__label">' . esc_html__( 'Ways to reach us', 'biopentra-storefront' ) . '</p>';

	$html .= '<ul class="bp-m10-channels__list">';

	if ( $chat_enabled ) {
		$html .= '<li class="bp-m10-channels__item bp-m10-channels__item--chat">';
		$html .= '<div class="bp-m10-channels__row">';
		$html .= '<span class="bp-m10-channels__name">' . esc_html__( 'Website chat', 'biopentra-storefront' ) . '</span>';
		$html .= '<button type="button" class="bp-m10-channels__action" data-bp-m10-open-chat>' . esc_html__( 'Open chat', 'biopentra-storefront' ) . '</button>';
		$html .= '</div>';
		$html .= '</li>';
	}

	$html .= '<li class="bp-m10-channels__item bp-m10-channels__item--telegram">';
	$html .= '<div class="bp-m10-channels__row">';
	$html .= '<span class="bp-m10-channels__name">' . esc_html__( 'Telegram', 'biopentra-storefront' ) . '</span>';
	$html .= '<span class="bp-m10-channels__meta">@biopentra</span>';
	$html .= '</div>';
	$html .= '<a class="bp-m10-channels__action bp-m10-channels__action--link" href="' . esc_url( $telegram_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open Telegram', 'biopentra-storefront' ) . '</a>';
	$html .= '</li>';

	$html .= '</ul>';

	$html .= '<div class="bp-m10-channels__context">';
	$html .= '<p>' . esc_html__( 'Shipping within the European Union only.', 'biopentra-storefront' ) . '</p>';
	$html .= '<p><strong>' . esc_html__( 'Research use only', 'biopentra-storefront' ) . '</strong> — ' . esc_html__( 'All products are intended for research use only.', 'biopentra-storefront' ) . '</p>';
	$html .= '</div>';

	$html .= '</div>';

	return $html;
}

/**
 * Shortcode: [biopentra_contact_channels]
 *
 * @return string
 */
function biopentra_storefront_contact_channels_shortcode() {
	return biopentra_storefront_contact_channels_html();
}
add_shortcode( 'biopentra_contact_channels', 'biopentra_storefront_contact_channels_shortcode' );

/**
 * @param string[] $classes Body classes.
 * @return string[]
 */
function biopentra_storefront_contact_m10_body_classes( $classes ) {
	if ( biopentra_storefront_is_contact_page() ) {
		$classes[] = 'bp-contact-m10';
	}

	return $classes;
}
add_filter( 'body_class', 'biopentra_storefront_contact_m10_body_classes' );

/**
 * Submit-time ownership guard for wc_related_order (M10).
 *
 * @param array                $errors    Validation errors keyed by field.
 * @param object               $form      Fluent form object.
 * @param array                $fields    Field definitions.
 * @param array<string, mixed> $form_data Submitted data.
 * @return array
 */
function biopentra_storefront_contact_validate_related_order( $errors, $form, $fields, $form_data ) {
	if ( ! is_object( $form ) || empty( $form->id ) ) {
		return $errors;
	}

	$configured = (int) get_option( 'biopentra_inbox_contact_form_id', 0 );
	if ( (int) $form->id !== $configured ) {
		return $errors;
	}

	if ( ! is_array( $errors ) ) {
		$errors = array();
	}

	if ( ! is_array( $form_data ) ) {
		return $errors;
	}

	$order_raw = isset( $form_data['wc_related_order'] ) ? trim( (string) $form_data['wc_related_order'] ) : '';
	if ( $order_raw === '' ) {
		return $errors;
	}

	if ( ! is_user_logged_in() ) {
		$errors['wc_related_order'] = __( 'Log in to link an order to your message.', 'biopentra-storefront' );
		return $errors;
	}

	if ( ! function_exists( 'wc_get_order' ) ) {
		$errors['wc_related_order'] = __( 'Please select a valid order linked to your account.', 'biopentra-storefront' );
	 return $errors;
	}

	$order_id = absint( $order_raw );
	if ( $order_id <= 0 ) {
		$errors['wc_related_order'] = __( 'Please select a valid order linked to your account.', 'biopentra-storefront' );
		return $errors;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order || (int) $order->get_customer_id() !== (int) get_current_user_id() ) {
		$errors['wc_related_order'] = __( 'Please select a valid order linked to your account.', 'biopentra-storefront' );
	}

	return $errors;
}
add_filter( 'fluentform/validation_error', 'biopentra_storefront_contact_validate_related_order', 10, 4 );
