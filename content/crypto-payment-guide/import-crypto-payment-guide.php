<?php
/**
 * Import / update the crypto payment how-to guide page (Elementor + media).
 *
 * Run from WordPress root:
 *   BIOPENTRA_CRYPTO_GUIDE_BUNDLE=/bundle docker compose --profile tools run --rm \
 *     -v /opt/biopentra/dev/biopentra-custom-plugins/content/crypto-payment-guide:/bundle:ro \
 *     wpcli wp eval-file /bundle/import-crypto-payment-guide.php
 *
 * @package Biopentra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve content bundle directory.
 */
function biopentra_crypto_guide_bundle_dir(): string {
	$from_env = getenv( 'BIOPENTRA_CRYPTO_GUIDE_BUNDLE' );
	if ( is_string( $from_env ) && $from_env !== '' && is_dir( $from_env ) ) {
		return rtrim( $from_env, '/' );
	}

	$candidates = array(
		__DIR__,
		'/opt/biopentra/dev/biopentra-custom-plugins/content/crypto-payment-guide',
	);

	foreach ( $candidates as $path ) {
		if ( is_dir( $path ) && is_dir( $path . '/media' ) ) {
			return rtrim( $path, '/' );
		}
	}

	return __DIR__;
}

/**
 * @return string
 */
function biopentra_crypto_guide_el_id(): string {
	return substr( md5( uniqid( 'bpel', true ) ), 0, 7 );
}

/**
 * @param array<string,mixed> $settings Container settings.
 * @param array<int,array<string,mixed>> $elements Child elements.
 * @return array<string,mixed>
 */
function biopentra_crypto_guide_container( array $settings, array $elements ): array {
	return array(
		'id'       => biopentra_crypto_guide_el_id(),
		'elType'   => 'container',
		'settings' => $settings,
		'elements' => $elements,
	);
}

/**
 * @param string               $widget_type Widget type.
 * @param array<string,mixed>  $settings    Widget settings.
 * @return array<string,mixed>
 */
function biopentra_crypto_guide_widget( string $widget_type, array $settings ): array {
	return array(
		'id'         => biopentra_crypto_guide_el_id(),
		'elType'     => 'widget',
		'widgetType' => $widget_type,
		'settings'   => $settings,
		'elements'   => array(),
	);
}

/**
 * @param string $html HTML content.
 * @return array<string,mixed>
 */
function biopentra_crypto_guide_text_widget( string $html ): array {
	return biopentra_crypto_guide_widget(
		'text-editor',
		array(
			'editor' => $html,
		)
	);
}

/**
 * @param string $title       Heading text.
 * @param string $header_size h1-h6.
 * @return array<string,mixed>
 */
function biopentra_crypto_guide_heading( string $title, string $header_size = 'h2' ): array {
	return biopentra_crypto_guide_widget(
		'heading',
		array(
			'title'       => $title,
			'header_size' => $header_size,
		)
	);
}

/**
 * @param int    $attachment_id Media attachment ID.
 * @param string $alt           Alt text.
 * @return array<string,mixed>
 */
function biopentra_crypto_guide_image_widget( int $attachment_id, string $alt ): array {
	$url = wp_get_attachment_url( $attachment_id );
	if ( ! is_string( $url ) ) {
		$url = '';
	}

	return biopentra_crypto_guide_widget(
		'image',
		array(
			'image' => array(
				'url'    => $url,
				'id'     => $attachment_id,
				'size'   => 'large',
				'alt'    => $alt,
				'source' => 'library',
			),
		)
	);
}

/**
 * @param string $filename Basename under media/.
 * @param string $alt      Alt text.
 * @return array<string,mixed>|null
 */
function biopentra_crypto_guide_step_block( string $filename, string $alt, string $body_html ): ?array {
	$map = biopentra_crypto_guide_media_map();
	if ( ! isset( $map[ $filename ] ) ) {
		fwrite( STDERR, "Warning: missing media mapping for {$filename}\n" );
		return biopentra_crypto_guide_container(
			array(
				'content_width'  => 'full',
				'flex_direction' => 'column',
				'padding'        => array(
					'unit'     => 'px',
					'top'      => '0',
					'right'    => '0',
					'bottom'   => '24',
					'left'     => '0',
					'isLinked' => false,
				),
			),
			array( biopentra_crypto_guide_text_widget( $body_html ) )
		);
	}

	$attachment_id = (int) $map[ $filename ]['id'];
	$widgets       = array( biopentra_crypto_guide_text_widget( $body_html ) );
	if ( $attachment_id > 0 ) {
		$widgets[] = biopentra_crypto_guide_image_widget( $attachment_id, $alt );
	}

	return biopentra_crypto_guide_container(
		array(
			'content_width'  => 'full',
			'flex_direction' => 'column',
			'padding'        => array(
				'unit'     => 'px',
				'top'      => '0',
				'right'    => '0',
				'bottom'   => '24',
				'left'     => '0',
				'isLinked' => false,
			),
		),
		$widgets
	);
}

/** @var array<string,array{id:int,url:string}>|null */
$GLOBALS['biopentra_crypto_guide_media_map'] = null;

/**
 * @return array<string,array{id:int,url:string}>
 */
function biopentra_crypto_guide_media_map(): array {
	if ( is_array( $GLOBALS['biopentra_crypto_guide_media_map'] ) ) {
		return $GLOBALS['biopentra_crypto_guide_media_map'];
	}
	return array();
}

/**
 * Import PNG files from bundle media/ into the Media Library.
 *
 * @param string $bundle_dir Bundle root.
 * @return array<string,array{id:int,url:string}>
 */
function biopentra_crypto_guide_import_media( string $bundle_dir ): array {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$media_dir = $bundle_dir . '/media';
	$map       = array();

	$files = array(
		'blockchain-amount-preview-buy.png'        => 'Blockchain.com checkout showing order amount and Preview buy button',
		'blockchain-login-email.png'               => 'Blockchain.com login or email entry screen',
		'blockchain-verify-email.png'              => 'Email verification step on Blockchain.com',
		'blockchain-kyc-form.png'                  => 'KYC information form on payment provider',
		'blockchain-kyc-id.png'                    => 'Government ID upload for KYC verification',
		'blockchain-payment-google-pay.png'        => 'Payment method selection example using Google Pay on Blockchain.com',
		'blockchain-complete-purchase.png'         => 'Final purchase confirmation with pre-set crypto address on Blockchain.com',
		'biopentra-order-pending-payment.png'      => 'Biopentra order showing pending payment while settlement completes',
		'biopentra-order-processing.png'           => 'Biopentra order status changed to Processing',
		'biopentra-order-confirmation-email.png'   => 'Order confirmation email from Biopentra',
	);

	foreach ( $files as $filename => $alt ) {
		$path = $media_dir . '/' . $filename;
		if ( ! is_readable( $path ) ) {
			fwrite( STDERR, "Missing media file: {$path}\n" );
			continue;
		}

		$existing = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'meta_key'       => '_biopentra_crypto_guide_file',
				'meta_value'     => $filename,
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $existing ) ) {
			$id          = (int) $existing[0];
			$map[ $filename ] = array(
				'id'  => $id,
				'url' => (string) wp_get_attachment_url( $id ),
			);
			continue;
		}

		$upload = wp_upload_bits( $filename, null, file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! empty( $upload['error'] ) ) {
			fwrite( STDERR, "Upload failed for {$filename}: {$upload['error']}\n" );
			continue;
		}

		$filetype = wp_check_filetype( $filename, null );
		$attachment = array(
			'post_mime_type' => $filetype['type'] ?: 'image/png',
			'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$id = wp_insert_attachment( $attachment, $upload['file'] );
		if ( is_wp_error( $id ) || ! $id ) {
			fwrite( STDERR, "Attachment insert failed for {$filename}\n" );
			continue;
		}

		$meta = wp_generate_attachment_metadata( $id, $upload['file'] );
		if ( is_array( $meta ) ) {
			wp_update_attachment_metadata( $id, $meta );
		}

		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
		update_post_meta( $id, '_biopentra_crypto_guide_file', $filename );

		$map[ $filename ] = array(
			'id'  => (int) $id,
			'url' => (string) wp_get_attachment_url( $id ),
		);
	}

	$GLOBALS['biopentra_crypto_guide_media_map'] = $map;
	return $map;
}

/**
 * Build Elementor document tree.
 *
 * @return array<int,array<string,mixed>>
 */
function biopentra_crypto_guide_build_elementor_data(): array {
	$checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );

	$band_padding = array(
		'unit'     => 'px',
		'top'      => '48',
		'right'    => '40',
		'bottom'   => '48',
		'left'     => '40',
		'isLinked' => false,
	);

	$content_gap = array(
		'unit'     => 'px',
		'size'     => 16,
		'sizes'    => array(),
		'column'   => '16',
		'row'      => '16',
		'isLinked' => true,
	);

	/**
	 * Full-width section band with centered frame (matches other Biopentra info pages).
	 *
	 * @param string               $bg_color Background hex.
	 * @param array<int,mixed>     $elements Widgets/containers inside the content column.
	 * @param array<string,mixed>  $extra    Optional content-column settings (e.g. _element_id).
	 * @return array<string,mixed>
	 */
	$framed_section = function ( string $bg_color, array $elements, array $extra = array() ) use ( $band_padding, $content_gap ) {
		$content_settings = array_merge(
			array(
				'width'          => array(
					'size' => 100,
					'unit' => '%',
				),
				'max_width'      => array(
					'size' => 820,
					'unit' => 'px',
				),
				'flex_direction' => 'column',
				'align_items'    => 'flex-start',
				'gap'            => $content_gap,
			),
			$extra
		);

		return biopentra_crypto_guide_container(
			array(
				'content_width'       => 'full',
				'flex_direction'      => 'column',
				'align_items'         => 'center',
				'background_background' => 'classic',
				'background_color'    => $bg_color,
				'padding'             => $band_padding,
			),
			array(
				biopentra_crypto_guide_container(
					array(
						'content_width'  => 'full',
						'width'          => array(
							'size' => 100,
							'unit' => '%',
						),
						'max_width'      => array(
							'size' => 1240,
							'unit' => 'px',
						),
						'flex_direction' => 'column',
						'align_items'    => 'flex-start',
					),
					array(
						biopentra_crypto_guide_container( $content_settings, $elements ),
					)
				),
			)
		);
	};

	$hero = $framed_section(
		'#F6F9FC',
		array(
			biopentra_crypto_guide_heading( 'How to pay with crypto', 'h1' ),
			biopentra_crypto_guide_text_widget(
				'<p>Biopentra accepts crypto payments in two ways: pay with Bitcoin directly, or pay with your card through an independent third-party provider that converts your payment to crypto. This guide explains what to expect at checkout.</p>'
			),
		)
	);

	$section_a = $framed_section(
		'#FFFFFF',
		array(
			biopentra_crypto_guide_heading( 'Pay with Bitcoin (BTCPay)', 'h2' ),
			biopentra_crypto_guide_text_widget(
				'<ol>'
				. '<li>At checkout, select the <strong>Bitcoin / BTCPay</strong> payment method (the exact label is shown at checkout).</li>'
				. '<li>Click <strong>Place order</strong>. You may be redirected to BTCPay or see an invoice modal, depending on store configuration.</li>'
				. '<li>Pay the invoice amount using your wallet (QR code, copy address, or wallet connect, as offered).</li>'
				. '<li>Complete payment before the invoice expires.</li>'
				. '<li>Order confirmation is sent by email. Timing depends on network confirmation. You may or may not return to the shop afterward.</li>'
				. '</ol>'
			),
		),
		array( '_element_id' => 'pay-with-bitcoin' )
	);

	$section_b = $framed_section(
		'#F6F9FC',
		array(
			biopentra_crypto_guide_heading( 'Pay with card (card-to-crypto overview)', 'h2' ),
			biopentra_crypto_guide_text_widget(
				'<p>When you select the Blockchain.com payment method — or any other method labelled <strong>“Card to…”</strong> — you are redirected to a third-party payment provider. The process is usually similar: register (first time) or log in (returning). First-time users <strong>often</strong> need identity verification (KYC). Follow the provider’s instructions.</p>'
				. '<p><strong>Important:</strong> Do not change the cryptocurrency or wallet/blockchain address shown during checkout — these are set by the payment flow.</p>'
				. '<ol>'
				. '<li>At checkout, select a <strong>card-to-crypto</strong> payment method (the provider name is shown at checkout).</li>'
				. '<li>Click <strong>Place order</strong> — you are redirected to that provider’s hosted checkout (not Biopentra).</li>'
				. '<li>The provider may request identity verification, depending on their requirements and your history with them.</li>'
				. '<li>Pay using a payment method accepted by that provider. Debit cards are often more reliable than credit cards, but acceptance varies.</li>'
				. '<li>After the provider completes payment, Biopentra receives confirmation asynchronously. You should receive an order confirmation email. You may or may not be redirected back to the shop.</li>'
				. '</ol>'
			),
		),
		array( '_element_id' => 'pay-with-card' )
	);

	$blockchain_steps = array(
		biopentra_crypto_guide_heading( 'Pay with Blockchain.com', 'h2' ),
		biopentra_crypto_guide_text_widget(
			'<p id="pay-with-blockchain-com">Detailed walkthrough for the Blockchain.com card-to-crypto gateway. Other <strong>Card to…</strong> providers follow a similar pattern, but screens and options may differ.</p>'
		),
		biopentra_crypto_guide_heading( 'On the provider checkout', 'h3' ),
	);

	$steps = array(
		array(
			'blockchain-amount-preview-buy.png',
			'Blockchain.com checkout showing order amount and Preview buy button',
			'<p><strong>Step 1 — Amount:</strong> You are presented with a screen for the order amount. Accept it and press <strong>Preview buy</strong>.</p>',
		),
		array(
			'blockchain-login-email.png',
			'Blockchain.com login or email entry screen',
			'<p><strong>Step 2 — Email:</strong> Log in or supply your email address.</p>',
		),
		array(
			'blockchain-verify-email.png',
			'Email verification step on Blockchain.com',
			'<p><strong>Step 3 — Verify email:</strong> Complete email verification as instructed.</p>',
		),
		array(
			'blockchain-kyc-form.png',
			'KYC information form on payment provider',
			'<p><strong>Step 4 — KYC form:</strong> Fill out identity verification if required. This information is sent to the payment provider only — not to Biopentra.</p>',
		),
		array(
			'blockchain-kyc-id.png',
			'Government ID upload for KYC verification',
			'<p><strong>Step 5 — Government ID:</strong> If instructed to complete KYC, you may need a government-issued ID (such as a passport).</p>',
		),
		array(
			'blockchain-payment-google-pay.png',
			'Payment method selection example using Google Pay on Blockchain.com',
			'<p><strong>Step 6 — Payment method:</strong> Select a payment option accepted by the provider. The example below shows <strong>Google Pay</strong> — <em>example only; available payment methods depend on the provider and your region.</em></p>',
		),
		array(
			'blockchain-complete-purchase.png',
			'Final purchase confirmation with pre-set crypto address on Blockchain.com',
			'<p><strong>Step 7 — Complete purchase:</strong> Review and complete the purchase. The wallet address and cryptocurrency type are pre-set for this order — do not modify them.</p>',
		),
	);

	foreach ( $steps as $step ) {
		$block = biopentra_crypto_guide_step_block( $step[0], $step[1], $step[2] );
		if ( $block ) {
			$blockchain_steps[] = $block;
		}
	}

	$blockchain_steps[] = biopentra_crypto_guide_text_widget(
		'<p><strong>Step 8 — Payment completed:</strong> Once payment is completed on the provider site, you can ignore any subsequent pages there. The crypto transfer may take several minutes; timing varies.</p>'
		. '<p><strong>Step 9 — After payment:</strong> Typically you are <strong>not</strong> returned to the shop automatically. If you return manually, your order may temporarily appear unpaid and your cart may still look populated while settlement completes — this can be normal.</p>'
	);

	$blockchain_steps[] = biopentra_crypto_guide_heading( 'What you’ll see back in the store', 'h3' );

	$store_steps = array(
		array(
			'biopentra-order-pending-payment.png',
			'Biopentra order showing pending payment while settlement completes',
			'<p>While the payment is being registered, your order may show as awaiting payment. Wait rather than placing a duplicate order.</p>',
		),
		array(
			'biopentra-order-processing.png',
			'Biopentra order status changed to Processing',
			'<p>After a few minutes, the status should change to <strong>Processing</strong> once payment is confirmed.</p>',
		),
		array(
			'biopentra-order-confirmation-email.png',
			'Order confirmation email from Biopentra',
			'<p>When payment is registered, you will receive a confirmation email. You will receive a separate email when your order is shipped.</p>',
		),
	);

	foreach ( $store_steps as $step ) {
		$block = biopentra_crypto_guide_step_block( $step[0], $step[1], $step[2] );
		if ( $block ) {
			$blockchain_steps[] = $block;
		}
	}

	$other_providers = $framed_section(
		'#F6F9FC',
		array(
			biopentra_crypto_guide_heading( 'Other card-to-crypto providers', 'h2' ),
			biopentra_crypto_guide_text_widget(
				'<p><strong>Revolut</strong> and <strong>Bitnovo</strong> use the same general flow described above. Screens, verification steps, accepted cards, and redirect behaviour <strong>may differ</strong> from Blockchain.com. Select the provider shown at checkout and follow its instructions.</p>'
			),
		)
	);

	$section_fees = $framed_section(
		'#FFFFFF',
		array(
			biopentra_crypto_guide_heading( 'Fees', 'h2' ),
			biopentra_crypto_guide_text_widget(
				'<p>Payment fees, if any, are shown on the payment method label and in your order total at checkout.</p>'
				. '<table><thead><tr><th></th><th>Bitcoin (BTCPay)</th><th>Card-to-crypto</th></tr></thead><tbody>'
				. '<tr><td>Fee</td><td>Shown at checkout</td><td>Shown at checkout</td></tr>'
				. '</tbody></table>'
			),
		)
	);

	$faq_tabs = array(
		array(
			'tab_title'   => 'Why was I sent to another website to pay?',
			'tab_content' => 'Card-to-crypto payments are completed on the provider’s secure checkout. Biopentra does not process card details directly.',
		),
		array(
			'tab_title'   => 'Does Biopentra see my card, ID, or KYC details?',
			'tab_content' => 'No. Identity verification and card payment are handled entirely by the third-party provider.',
		),
		array(
			'tab_title'   => 'Why does my order still look unpaid after I paid?',
			'tab_content' => 'Settlement can take several minutes. Your order should update once the provider confirms payment to Biopentra.',
		),
		array(
			'tab_title'   => 'Why wasn’t I returned to the shop?',
			'tab_content' => 'Many provider checkouts do not redirect back to the store. Wait for your confirmation email instead of placing a duplicate order.',
		),
		array(
			'tab_title'   => 'How long until my order is confirmed?',
			'tab_content' => 'Often within a few minutes, but timing varies depending on the provider and payment method.',
		),
		array(
			'tab_title'   => 'Which payment method should I choose?',
			'tab_content' => 'Bitcoin (BTCPay) suits customers with a crypto wallet. Card-to-crypto suits customers who prefer paying by card.',
		),
		array(
			'tab_title'   => 'What if payment fails or I close the browser?',
			'tab_content' => 'Return to checkout and try again, or contact support with your order number if you were charged but the order did not update.',
		),
	);

	$section_faq = $framed_section(
		'#F6F9FC',
		array(
			biopentra_crypto_guide_heading( 'Frequently asked questions', 'h2' ),
			biopentra_crypto_guide_widget(
				'toggle',
				array(
					'tabs' => $faq_tabs,
				)
			),
		)
	);

	$section_cta = $framed_section(
		'#FFFFFF',
		array(
			biopentra_crypto_guide_widget(
				'button',
				array(
					'text' => 'Proceed to checkout',
					'link' => array(
						'url'         => $checkout_url,
						'is_external' => '',
						'nofollow'    => '',
					),
					'align' => 'left',
				)
			),
		)
	);

	$section_c = $framed_section(
		'#FFFFFF',
		$blockchain_steps,
		array( '_element_id' => 'pay-with-blockchain-com' )
	);

	return array(
		biopentra_crypto_guide_container(
			array(
				'content_width'  => 'full',
				'flex_direction' => 'column',
				'align_items'    => 'stretch',
			),
			array(
				$hero,
				$section_a,
				$section_b,
				$section_c,
				$other_providers,
				$section_fees,
				$section_faq,
				$section_cta,
			)
		),
	);
}

/**
 * Create or update guide page.
 *
 * @param string $bundle_dir Bundle directory.
 * @return array<string,mixed>
 */
function biopentra_crypto_guide_import_page( string $bundle_dir ): array {
	biopentra_crypto_guide_import_media( $bundle_dir );

	$slug  = 'how-to-pay-with-crypto';
	$title = 'How to Pay with Crypto';

	$page = get_page_by_path( $slug, OBJECT, 'page' );
	$id   = ( $page instanceof WP_Post ) ? (int) $page->ID : 0;

	if ( $id <= 0 ) {
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => '',
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			return array(
				'ok'      => false,
				'message' => $id->get_error_message(),
			);
		}
	}

	$data = biopentra_crypto_guide_build_elementor_data();
	$json = wp_json_encode( $data );

	update_post_meta( $id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $id, '_elementor_template_type', 'wp-page' );
	update_post_meta( $id, '_wp_page_template', 'elementor_header_footer' );
	update_post_meta( $id, '_elementor_data', wp_slash( $json ) );
	update_post_meta( $id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );

	delete_post_meta( $id, '_elementor_css' );
	delete_post_meta( $id, '_elementor_element_cache' );

	update_option( 'biopentra_crypto_guide_page_id', (int) $id, false );
	if ( get_option( 'biopentra_crypto_guide_enabled', '' ) === '' ) {
		update_option( 'biopentra_crypto_guide_enabled', 'yes', false );
	}

	// Persist elementor JSON artifact for prod transfer (skip on read-only bundle mounts).
	$artifact = $bundle_dir . '/elementor-data.json';
	if ( is_writable( $bundle_dir ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $artifact, $json );
	}

	if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) && method_exists( \Elementor\Plugin::$instance->files_manager, 'clear_cache' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}

	return array(
		'ok'       => true,
		'page_id'  => (int) $id,
		'url'      => get_permalink( $id ),
		'media'    => count( biopentra_crypto_guide_media_map() ),
		'artifact' => $artifact,
	);
}

$bundle = biopentra_crypto_guide_bundle_dir();
$result = biopentra_crypto_guide_import_page( $bundle );

if ( empty( $result['ok'] ) ) {
	fwrite( STDERR, 'ERROR: ' . ( $result['message'] ?? 'import failed' ) . "\n" );
	exit( 1 );
}

echo 'OK: Crypto payment guide page ID ' . (int) $result['page_id'] . ' — ' . (string) $result['url'] . "\n";
echo 'Imported/verified ' . (int) $result['media'] . " media file(s).\n";
echo 'Wrote elementor-data.json artifact.' . "\n";
