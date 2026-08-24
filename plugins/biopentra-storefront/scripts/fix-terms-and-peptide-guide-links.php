<?php
/**
 * Restore Terms & Conditions body copy and point Learn → Peptide Guide at the real page.
 *
 * Diagnosis (DEV + production):
 * - Page `terms-and-conditions` (3829) is published but `post_content` and
 *   `_elementor_data` are empty. The full T&C copy lives on revision 4093's
 *   `_elementor_data` (built May 2026, never copied onto the published page).
 *   Sibling legal pages (Privacy, Refund, Shipping) were converted to plain
 *   HTML in `post_content` with the theme `default` template; Terms never was.
 * - Information mega-menu Learn column has `<a href="#">Peptide Guide</a>`
 *   because the `peptide-guide` page was left as `draft`.
 *
 * This script:
 * 1. Converts revision Elementor JSON into HTML matching sibling policy pages.
 * 2. Writes it to the published Terms page and keeps `default` template.
 * 3. Publishes Peptide Guide (Elementor page) if it is still draft.
 * 4. Replaces the Learn-column `#` href with the published permalink.
 *
 * Idempotent. Replay on production with the same command.
 *
 * Usage:
 *   wp eval-file wp-content/plugins/biopentra-storefront/scripts/fix-terms-and-peptide-guide-links.php
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BIOPENTRA_TERMS_SLUG          = 'terms-and-conditions';
const BIOPENTRA_TERMS_PAGE_ID       = 3829;
const BIOPENTRA_TERMS_REVISION_ID   = 4093;
const BIOPENTRA_PEPTIDE_GUIDE_SLUG  = 'peptide-guide';
const BIOPENTRA_HEADER_POST_ID      = 3782;

/**
 * Flatten Elementor heading + text-editor widgets into policy-page HTML.
 *
 * @param array $elements Elementor elements tree.
 * @param bool  $first    Whether the next heading should be h1.
 * @return string
 */
function biopentra_terms_html_from_elementor( array $elements, &$first = true ) {
	$html = '';
	foreach ( $elements as $el ) {
		if ( ! is_array( $el ) ) {
			continue;
		}
		$type = isset( $el['widgetType'] ) ? (string) $el['widgetType'] : '';
		if ( 'heading' === $type ) {
			$title = isset( $el['settings']['title'] ) ? (string) $el['settings']['title'] : '';
			$title = trim( wp_strip_all_tags( $title ) );
			if ( $title !== '' ) {
				$tag   = $first ? 'h1' : 'h2';
				$first = false;
				$html .= '<' . $tag . '>' . esc_html( $title ) . '</' . $tag . ">\n";
			}
		}
		if ( 'text-editor' === $type ) {
			$editor = isset( $el['settings']['editor'] ) ? (string) $el['settings']['editor'] : '';
			$editor = trim( $editor );
			if ( $editor !== '' ) {
				$html .= $editor . "\n";
			}
		}
		if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
			$html .= biopentra_terms_html_from_elementor( $el['elements'], $first );
		}
	}
	return $html;
}

/**
 * Canonical T&C body (May 2026), used when Elementor revision data is absent
 * (typical on a production clone that never received the revision meta).
 *
 * @return string
 */
function biopentra_terms_fallback_html() {
	return <<<'HTML'
<h1>Terms &amp; Conditions</h1>
<p>Last updated: May 2026</p>
<h2>1. Introduction</h2>
<p>These Terms &amp; Conditions govern the use of the Biopentra website and the purchase of products offered through the website.</p><p>By accessing this website or placing an order, you agree to comply with these Terms &amp; Conditions.</p><p>If you do not agree with these Terms, you should not use this website or purchase products from Biopentra.</p>
<h2>2. Research Use Only</h2>
<p>All products sold by Biopentra are strictly intended for laboratory and scientific research purposes only.</p><p>Products are not intended for human consumption, medical use, veterinary use, therapeutic use, diagnostic use, or any other unauthorized application.</p><p>Customers are solely responsible for ensuring that products are used lawfully and appropriately within their jurisdiction and intended research environment.</p><p>Biopentra reserves the right to refuse service or cancel orders where misuse or inappropriate use is suspected.</p>
<h2>3. Eligibility to Purchase</h2>
<p>By placing an order through this website, you confirm that you are legally permitted to purchase research materials within your jurisdiction.</p><p>Customers are responsible for understanding and complying with applicable local laws and regulations related to the purchase, importation, possession, and use of research products.</p>
<h2>4. Product Information</h2>
<p>Biopentra aims to provide accurate product descriptions and information wherever reasonably possible.</p><p>However, product specifications, availability, packaging, and product details may occasionally be updated or modified without prior notice.</p><p>Images displayed on the website are for illustrative purposes only and may not always represent final packaging or presentation exactly.</p>
<h2>5. Orders and Acceptance</h2>
<p>Submission of an order does not automatically constitute acceptance of the order.</p><p>Biopentra reserves the right to:</p><ul><li>refuse or cancel orders,</li><li>limit quantities,</li><li>request additional verification,</li><li>or decline transactions at its discretion.</li></ul><p>Orders may be delayed in the event of stock discrepancies, supplier issues, or operational circumstances.</p><p>In the event of a stock or inventory error, orders are generally delayed rather than cancelled whenever reasonably possible.</p><p>Customers will be notified if significant delays occur.</p>
<h2>6. Pricing and Payments</h2>
<p>All prices displayed on the website are subject to change without prior notice.</p><p>Biopentra reserves the right to correct pricing errors, inaccuracies, or technical mistakes at any time.</p><p>Orders will not be processed until payment has been successfully received and confirmed.</p>
<h2>7. Cryptocurrency Payments</h2>
<p>Biopentra may accept cryptocurrency payments through third-party payment processors.</p><p>Cryptocurrency transactions are considered final and non-reversible once confirmed on the blockchain or through the payment provider.</p><p>Biopentra is not responsible for:</p><ul><li>incorrect wallet addresses submitted by customers,</li><li>blockchain delays,</li><li>network fees,</li><li>exchange rate fluctuations,</li><li>or payment provider interruptions.</li></ul><p>Customers are responsible for submitting correct payment information during checkout.</p>
<h2>8. Shipping and Delivery</h2>
<p>Biopentra currently ships within the European Union only.</p><p>Orders are generally dispatched within the estimated handling time displayed on the website, although delays may occasionally occur due to operational or shipping circumstances.</p><p>Delivery times are estimates only and are not guaranteed.</p><p>Shipping is handled through third-party postal or delivery providers.</p><p>Once a shipment has been transferred to the carrier, delivery performance is outside the direct control of Biopentra.</p>
<h2>9. Lost, Seized, or Refused Packages</h2>
<p>Customers are responsible for ensuring that products may legally be imported, possessed, and used within their jurisdiction.</p><p>Biopentra is not liable for packages that are:</p><ul><li>seized,</li><li>refused,</li><li>delayed by customs or authorities,</li><li>or otherwise prevented from delivery due to local regulations or customer-related circumstances.</li></ul><p>In the unlikely event that a shipment appears lost during transit, customers may contact support for assistance. Resolution is evaluated on a case-by-case basis.</p>
<h2>10. Returns and Replacements</h2>
<p>Due to the nature of research products and requirements related to product integrity, sterility, and safety, returns are generally not accepted.</p><p>Biopentra does not accept returns for:</p><ul><li>opened products,</li><li>change of mind,</li><li>ordering mistakes,</li><li>or products that have already been shipped.</li></ul><p>However, customers should contact support if:</p><ul><li>products arrive damaged during shipping,</li><li>or an incorrect item has been received.</li></ul><p>Eligible replacement requests are reviewed individually.</p>
<h2>11. Order Cancellations</h2>
<p>Orders may be cancelled prior to payment confirmation.</p><p>Once payment has been received and processing has begun, cancellations are generally no longer possible.</p>
<h2>12. Account Suspension and Refusal of Service</h2>
<p>Biopentra reserves the right to:</p><ul><li>suspend or terminate customer accounts,</li><li>restrict website access,</li><li>refuse orders,</li><li>or decline service</li></ul><p>where suspicious activity, misuse, abuse, fraud, or violations of these Terms are suspected.</p>
<h2>13. Limitation of Liability</h2>
<p>Products sold by Biopentra are supplied strictly for research purposes only.</p><p>Customers assume full responsibility for the handling, storage, application, and use of all products purchased through the website.</p><p>Biopentra makes no guarantees regarding:</p><ul><li>suitability for a particular purpose,</li><li>research outcomes,</li><li>compatibility,</li><li>or specific results.</li></ul><p>To the maximum extent permitted by applicable law, Biopentra shall not be liable for:</p><ul><li>indirect damages,</li><li>consequential damages,</li><li>loss of data,</li><li>business interruption,</li><li>misuse of products,</li><li>or damages arising from improper handling or unauthorized use.</li></ul>
<h2>14. Website Availability</h2>
<p>Biopentra does not guarantee uninterrupted access to the website.</p><p>The website, product listings, and services may be modified, suspended, or discontinued at any time without prior notice.</p>
<h2>15. Changes to These Terms</h2>
<p>These Terms &amp; Conditions may be updated periodically to reflect operational, legal, or technical changes.</p><p>Updated versions will be published on this page.</p><p>Continued use of the website following updates constitutes acceptance of the revised Terms.</p>
<h2>16. Contact</h2>
<p>Questions regarding these Terms &amp; Conditions may be submitted through the contact methods available on the website.</p>
HTML;
}

/**
 * @return string HTML or empty.
 */
function biopentra_terms_source_html() {
	$raw = get_post_meta( BIOPENTRA_TERMS_REVISION_ID, '_elementor_data', true );
	if ( is_string( $raw ) && $raw !== '' ) {
		$data = json_decode( $raw, true );
		if ( is_array( $data ) ) {
			$first = true;
			$html  = trim( biopentra_terms_html_from_elementor( $data, $first ) );
			if ( $html !== '' && false !== strpos( $html, '1. Introduction' ) ) {
				return $html;
			}
		}
	}
	return trim( biopentra_terms_fallback_html() );
}

$admin = get_user_by( 'id', 1 );
if ( $admin ) {
	wp_set_current_user( (int) $admin->ID );
}

$changed = 0;

// --- 1. Terms & Conditions -------------------------------------------------
$terms = get_page_by_path( BIOPENTRA_TERMS_SLUG, OBJECT, 'page' );
if ( ! $terms && get_post( BIOPENTRA_TERMS_PAGE_ID ) ) {
	$terms = get_post( BIOPENTRA_TERMS_PAGE_ID );
}
if ( ! $terms ) {
	fwrite( STDERR, "FAIL: terms page not found (slug " . BIOPENTRA_TERMS_SLUG . " / id " . BIOPENTRA_TERMS_PAGE_ID . ")\n" );
	exit( 1 );
}

$source_html = biopentra_terms_source_html();
if ( $source_html === '' || false === strpos( $source_html, '1. Introduction' ) ) {
	fwrite( STDERR, "FAIL: could not build Terms HTML (revision " . BIOPENTRA_TERMS_REVISION_ID . " and fallback both empty/invalid)\n" );
	exit( 1 );
}

$existing = trim( (string) $terms->post_content );
if ( $existing !== '' && false !== strpos( $existing, '1. Introduction' ) && strlen( $existing ) > 500 ) {
	echo "SKIP terms {$terms->ID}: post_content already has T&C copy (" . strlen( $existing ) . " bytes)\n";
} else {
	$result = wp_update_post(
		array(
			'ID'           => $terms->ID,
			'post_content' => $source_html,
			'post_status'  => 'publish',
		),
		true
	);
	if ( is_wp_error( $result ) ) {
		fwrite( STDERR, 'FAIL: wp_update_post terms: ' . $result->get_error_message() . "\n" );
		exit( 1 );
	}
	update_post_meta( $terms->ID, '_wp_page_template', 'default' );
	$blocksy = get_post_meta( $terms->ID, 'blocksy_post_meta_options', true );
	if ( ! is_array( $blocksy ) ) {
		$blocksy = array();
	}
	$blocksy['has_hero_section'] = 'disabled';
	update_post_meta( $terms->ID, 'blocksy_post_meta_options', $blocksy );
	echo "FIXED terms {$terms->ID}: wrote " . strlen( $source_html ) . " bytes of T&C HTML from revision " . BIOPENTRA_TERMS_REVISION_ID . "\n";
	++$changed;
}

// --- 2. Publish Peptide Guide ----------------------------------------------
$guide      = get_page_by_path( BIOPENTRA_PEPTIDE_GUIDE_SLUG, OBJECT, 'page' );
$guide_url  = '';
$header_id  = (int) ( getenv( 'BIOPENTRA_MEGA_HEADER_POST_ID' ) ?: BIOPENTRA_HEADER_POST_ID );
if ( ! $guide ) {
	echo "WARN: peptide-guide page not found — skipping publish and mega-menu link patch\n";
} else {
	if ( 'publish' === $guide->post_status ) {
		echo "SKIP peptide-guide {$guide->ID}: already published\n";
	} else {
		$result = wp_update_post(
			array(
				'ID'          => $guide->ID,
				'post_status' => 'publish',
			),
			true
		);
		if ( is_wp_error( $result ) ) {
			fwrite( STDERR, 'FAIL: publish peptide-guide: ' . $result->get_error_message() . "\n" );
			exit( 1 );
		}
		echo "FIXED peptide-guide {$guide->ID}: draft -> publish\n";
		++$changed;
	}

	$guide_tpl = (string) get_post_meta( $guide->ID, '_wp_page_template', true );
	$guide_el  = get_post_meta( $guide->ID, '_elementor_data', true );
	$has_el    = is_string( $guide_el ) && $guide_el !== '' && '[]' !== $guide_el;
	if ( $has_el && 'elementor_header_footer' !== $guide_tpl ) {
		update_post_meta( $guide->ID, '_wp_page_template', 'elementor_header_footer' );
		echo "FIXED peptide-guide {$guide->ID}: _wp_page_template -> elementor_header_footer\n";
		++$changed;
	}

	$guide     = get_post( $guide->ID );
	$guide_url = get_permalink( $guide );
	if ( ! is_string( $guide_url ) || $guide_url === '' ) {
		fwrite( STDERR, "FAIL: peptide-guide permalink empty after publish\n" );
		exit( 1 );
	}

	// --- 3. Mega-menu Learn column href ----------------------------------------
	$raw = get_post_meta( $header_id, '_elementor_data', true );
	if ( ! is_string( $raw ) || $raw === '' ) {
		echo "WARN: missing _elementor_data for header {$header_id} — skipping mega-menu link patch\n";
	} else {
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			fwrite( STDERR, "FAIL: invalid Elementor JSON on header {$header_id}\n" );
			exit( 1 );
		}

		$mega_changed = false;
		$patch_mega   = function ( array &$elements ) use ( &$patch_mega, $guide_url, &$mega_changed ) {
			foreach ( $elements as &$el ) {
				if ( ! is_array( $el ) ) {
					continue;
				}
				if ( isset( $el['widgetType'] ) && 'text-editor' === $el['widgetType'] ) {
					$editor = isset( $el['settings']['editor'] ) ? (string) $el['settings']['editor'] : '';
					if ( $editor === '' || false === strpos( $editor, 'Peptide Guide' ) ) {
						if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
							$patch_mega( $el['elements'] );
						}
						continue;
					}
					$updated = preg_replace(
						'/<a href="#"(?: rel="[^"]*")?>Peptide Guide<\/a>/',
						'<a href="' . esc_url( $guide_url ) . '">Peptide Guide</a>',
						$editor,
						1,
						$count
					);
					if ( $count > 0 && is_string( $updated ) ) {
						$el['settings']['editor'] = $updated;
						$mega_changed             = true;
					} elseif ( false !== strpos( $editor, 'peptide-guide' ) ) {
						echo "SKIP mega-menu: Peptide Guide already points at peptide-guide\n";
					}
				}
				if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
					$patch_mega( $el['elements'] );
				}
			}
			unset( $el );
		};
		$patch_mega( $data );

		if ( $mega_changed ) {
			$json = wp_json_encode( $data );
			if ( ! is_string( $json ) ) {
				fwrite( STDERR, "FAIL: failed to encode header JSON\n" );
				exit( 1 );
			}
			update_post_meta( $header_id, '_elementor_data', wp_slash( $json ) );
			delete_post_meta( $header_id, '_elementor_element_cache' );
			delete_post_meta( $header_id, '_elementor_css' );
			echo "FIXED mega-menu header {$header_id}: Peptide Guide -> {$guide_url}\n";
			++$changed;
		} elseif ( false === strpos( $raw, 'Peptide Guide' ) ) {
			echo "WARN: header {$header_id} has no Peptide Guide link to patch\n";
		}
	}
}

if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}
if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
	if ( $guide ) {
		( new \Elementor\Core\Files\CSS\Post( (int) $guide->ID ) )->update();
	}
	( new \Elementor\Core\Files\CSS\Post( $header_id ) )->update();
}
if ( function_exists( 'wp_cache_flush' ) ) {
	wp_cache_flush();
}

$guide_out = $guide_url !== '' ? $guide_url : '(skipped)';
echo "Done: {$changed} change(s). terms=" . get_permalink( $terms ) . " peptide-guide={$guide_out}\n";
echo "OK\n";
