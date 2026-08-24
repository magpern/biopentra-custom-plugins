<?php
/**
 * One-shot updater: inject Information mega-menu layout into Elementor header template.
 * Run from site root (Docker WP mount): ./wp eval-file wp-content/plugins/biopentra-storefront/scripts/cli-update-megamenu.php
 *
 * @package Biopentra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detail copy for progressive disclosure (templates + shared with patch mode).
 *
 * @return array<string, array{title:string, paragraphs:string[], learn_url:string}>
 */
function biopentra_megamenu_detail_specs() {
	return array(
		'quality-standards'    => array(
			'title'       => 'Quality Standards',
			'paragraphs'  => array(
				'Biopentra emphasizes quality-focused sourcing from established manufacturing partners.',
				'Supporting documentation is manufacturer-provided and reviewed for consistency with published product information.',
				'Where clarification is needed, we work with partners to obtain updated information.',
			),
			'learn_url'     => '',
		),
		'third-party-testing' => array(
			'title'       => 'Third-Party Testing',
			'paragraphs'  => array(
				'Manufacturer-provided certificates may reference independent laboratory testing where available for a given product or batch.',
				'Biopentra does not operate an in-house analytical laboratory; we rely on documentation supplied by manufacturing partners.',
				'Specifics vary by SKU—refer to materials provided with your purchase when applicable.',
			),
			'learn_url'     => '',
		),
		'certificates-coas'   => array(
			'title'       => 'Certificates / COAs',
			'paragraphs'  => array(
				'Certificates of Analysis (COAs) and related documents are manufacturer-provided when available and are intended to support transparency for research-use materials.',
				'Format and batch specificity depend on the supplier and product line.',
				'Unless explicitly stated on a listing, we do not represent that every unit maps to a unique, batch-specific certificate.',
			),
			'learn_url'     => '',
		),
		'purity-analytics'    => array(
			'title'       => 'Purity & Analytics',
			'paragraphs'  => array(
				'Purity and analytical values reflect manufacturer-reported data unless otherwise noted.',
				'Listings may summarize typical specifications rather than a single batch-specific certificate.',
				'For the most current detail, consult manufacturer-provided documentation supplied with your order where applicable.',
			),
			'learn_url'     => '',
		),
		'compliance-safety'   => array(
			'title'       => 'Compliance & Safety',
			'paragraphs'  => array(
				'Products are positioned for research use only (RUO) and should be handled consistent with applicable regulations and institutional requirements.',
				'Our policy pages summarize key disclosures to support informed purchasing.',
				'Customers remain responsible for lawful use, storage, and disposal in their jurisdiction.',
			),
			'learn_url'     => '',
		),
	);
}

/**
 * Hidden <template> sources for detail pane / mobile accordion (cloned by JS).
 */
function biopentra_megamenu_templates_html() {
	$html = '<div class="biopentra-info-mega-templates" hidden aria-hidden="true">';
	foreach ( biopentra_megamenu_detail_specs() as $id => $row ) {
		$tid = 'bp-detail-template-' . $id;
		$html .= '<template id="' . esc_attr( $tid ) . '">';
		$html .= '<div class="bp-detail-template-root">';
		$html .= '<h3 class="biopentra-info-mega-detail-heading">' . esc_html( $row['title'] ) . '</h3>';
		$html .= '<div class="biopentra-info-mega-detail-copy">';
		foreach ( $row['paragraphs'] as $p ) {
			$html .= '<p>' . esc_html( $p ) . '</p>';
		}
		$html .= '</div>';
		if ( ! empty( $row['learn_url'] ) ) {
			$html .= '<a class="biopentra-info-mega-detail-learn" href="' . esc_url( $row['learn_url'] ) . '">' . esc_html__( 'Learn more', 'biopentra-megamenu' ) . '</a>';
		}
		$html .= '</div></template>';
	}
	$html .= '</div>';
	return $html;
}

/**
 * Disclosure row: button + desktop flyout + mobile inline accordion (filled by JS).
 *
 * @param string $detail_id Kebab id matching template suffix.
 * @param string $label     Button label.
 */
function biopentra_megamenu_disclosure_button_li( $detail_id, $label ) {
	$inline_id = 'bp-inline-' . $detail_id;
	$flyout_id = 'bp-flyout-' . $detail_id;
	return '<li class="biopentra-info-mega-item biopentra-info-mega-disclosure">'
		. '<button type="button" class="biopentra-info-mega-trigger" data-bp-detail-id="' . esc_attr( $detail_id ) . '" aria-expanded="false" aria-controls="' . esc_attr( $flyout_id ) . '">'
		. esc_html( $label )
		. '</button>'
		. '<div id="' . esc_attr( $flyout_id ) . '" class="biopentra-info-mega-flyout" aria-hidden="true" role="region"></div>'
		. '<div id="' . esc_attr( $inline_id ) . '" class="biopentra-info-mega-inline-detail" hidden aria-hidden="true"></div>'
		. '</li>';
}

/**
 * Frontend flyout + accordion script (also written to assets when writable).
 */
function biopentra_megamenu_runtime_js() {
	return <<<'BPINFOMEGAJS'
/**
 * Desktop: flyouts use :hover + .is-active (JS); one active disclosure at a time.
 * Mobile: accordion only (.biopentra-info-mega-inline-detail).
 */
(function () {
	'use strict';

	if (window.__BIOPENTRA_INFO_MEGA_JS__) {
		return;
	}
	window.__BIOPENTRA_INFO_MEGA_JS__ = true;

	var MQ = '(min-width: 1025px)';

	function getTemplate(root, detailId) {
		return root.querySelector('#bp-detail-template-' + detailId);
	}

	function syncAriaControls(triggers, desktop) {
		triggers.forEach(function (btn) {
			var id = btn.getAttribute('data-bp-detail-id');
			if (!id) return;
			btn.setAttribute(
				'aria-controls',
				desktop ? 'bp-flyout-' + id : 'bp-inline-' + id
			);
		});
	}

	function fillFlyoutFromTemplate(root, detailId, flyoutEl) {
		var tpl = getTemplate(root, detailId);
		if (!tpl || !tpl.content || !flyoutEl) return false;
		var frag = tpl.content.cloneNode(true);
		var wrap = frag.querySelector('.bp-detail-template-root');
		if (!wrap) return false;
		flyoutEl.innerHTML = '';
		flyoutEl.appendChild(wrap);
		return true;
	}

	function clearFlyoutContents(root) {
		root.querySelectorAll('.biopentra-info-mega-flyout').forEach(function (fly) {
			fly.innerHTML = '';
			delete fly.dataset.bpPrefilled;
			fly.setAttribute('aria-hidden', 'true');
		});
	}

	function prefillDesktopFlyouts(root) {
		root.querySelectorAll('.biopentra-info-mega-item.biopentra-info-mega-disclosure').forEach(function (item) {
			var btn = item.querySelector('.biopentra-info-mega-trigger');
			var fly = item.querySelector('.biopentra-info-mega-flyout');
			if (!btn || !fly || fly.dataset.bpPrefilled === '1') return;
			var id = btn.getAttribute('data-bp-detail-id');
			if (!id) return;
			if (fillFlyoutFromTemplate(root, id, fly)) {
				fly.dataset.bpPrefilled = '1';
				fly.setAttribute('aria-hidden', 'true');
			}
		});
	}

	function clearDesktopActive(root) {
		root.querySelectorAll('.biopentra-info-mega-item.biopentra-info-mega-disclosure.is-active').forEach(function (el) {
			el.classList.remove('is-active');
		});
	}

	function syncFlyoutAria(root) {
		root.querySelectorAll('.biopentra-info-mega-item.biopentra-info-mega-disclosure').forEach(function (item) {
			var fly = item.querySelector('.biopentra-info-mega-flyout');
			if (!fly) return;
			var open = item.classList.contains('is-active');
			try {
				open = open || item.matches(':hover');
			} catch (e) {
				/* ignore */
			}
			fly.setAttribute('aria-hidden', open ? 'false' : 'true');
		});
	}

	function bindDesktopDisclosure(root, mq) {
		root.querySelectorAll('.biopentra-info-mega-item.biopentra-info-mega-disclosure').forEach(function (item) {
			if (item.dataset.bpDesktopDisclosureBound === '1') return;
			item.dataset.bpDesktopDisclosureBound = '1';

			function activateOnlyThis() {
				clearDesktopActive(root);
				item.classList.add('is-active');
				syncFlyoutAria(root);
			}

			function maybeDeactivateThis() {
				window.setTimeout(function () {
					if (!mq.matches) return;
					try {
						if (!item.matches(':hover') && !item.contains(document.activeElement)) {
							item.classList.remove('is-active');
						}
					} catch (e) {
						item.classList.remove('is-active');
					}
					syncFlyoutAria(root);
				}, 80);
			}

			item.addEventListener('pointerenter', function () {
				if (!mq.matches) return;
				clearDesktopActive(root);
				item.classList.add('is-active');
				syncFlyoutAria(root);
			});

			item.addEventListener('pointerleave', function () {
				if (!mq.matches) return;
				maybeDeactivateThis();
			});

			item.addEventListener('focusin', function () {
				if (!mq.matches) return;
				activateOnlyThis();
			});

			item.addEventListener('focusout', function () {
				if (!mq.matches) return;
				maybeDeactivateThis();
			});
		});
	}

	function clearMobile(root, triggers) {
		triggers.forEach(function (btn) {
			var item = btn.closest('.biopentra-info-mega-item');
			var pan = item ? item.querySelector('.biopentra-info-mega-inline-detail') : null;
			btn.setAttribute('aria-expanded', 'false');
			if (pan) {
				pan.hidden = true;
				pan.innerHTML = '';
				pan.setAttribute('aria-hidden', 'true');
			}
		});
	}

	function openMobileInline(root, detailId, triggerEl) {
		var item = triggerEl.closest('.biopentra-info-mega-item');
		var pan = item ? item.querySelector('.biopentra-info-mega-inline-detail') : null;
		if (!pan) return;
		var tpl = getTemplate(root, detailId);
		if (!tpl || !tpl.content) return;
		var frag = tpl.content.cloneNode(true);
		var wrap = frag.querySelector('.bp-detail-template-root');
		if (!wrap) return;
		var copy = wrap.querySelector('.biopentra-info-mega-detail-copy');
		var learn = wrap.querySelector('.biopentra-info-mega-detail-learn');
		pan.innerHTML = '';
		var inner = document.createElement('div');
		inner.className = 'biopentra-info-mega-inline-detail-inner';
		if (copy) inner.innerHTML = copy.innerHTML;
		if (learn) inner.appendChild(learn.cloneNode(true));
		pan.appendChild(inner);
		pan.hidden = false;
		pan.setAttribute('aria-hidden', 'false');
	}

	function initMega(root) {
		if (root.getAttribute('data-bp-mega-init') === '1') return;
		root.setAttribute('data-bp-mega-init', '1');

		var mq = window.matchMedia(MQ);
		var triggers = Array.prototype.slice.call(
			root.querySelectorAll('.biopentra-info-mega-trigger[data-bp-detail-id]')
		);
		if (!triggers.length) return;

		function applyMode() {
			syncAriaControls(triggers, mq.matches);
			if (mq.matches) {
				clearMobile(root, triggers);
				clearDesktopActive(root);
				prefillDesktopFlyouts(root);
				bindDesktopDisclosure(root, mq);
				syncFlyoutAria(root);
			} else {
				clearDesktopActive(root);
				clearFlyoutContents(root);
				clearMobile(root, triggers);
			}
		}

		applyMode();

		triggers.forEach(function (btn) {
			var id = btn.getAttribute('data-bp-detail-id');
			if (!id) return;
			var item = btn.closest('.biopentra-info-mega-item');

			btn.addEventListener('click', function (e) {
				if (mq.matches) {
					e.preventDefault();
					btn.blur();
					clearDesktopActive(root);
					syncFlyoutAria(root);
					return;
				}
				e.preventDefault();
				var expanded = btn.getAttribute('aria-expanded') === 'true';
				triggers.forEach(function (other) {
					if (other === btn) return;
					other.setAttribute('aria-expanded', 'false');
					var oItem = other.closest('.biopentra-info-mega-item');
					var p = oItem ? oItem.querySelector('.biopentra-info-mega-inline-detail') : null;
					if (p) {
						p.hidden = true;
						p.innerHTML = '';
						p.setAttribute('aria-hidden', 'true');
					}
				});
				var pan = item ? item.querySelector('.biopentra-info-mega-inline-detail') : null;
				if (expanded) {
					btn.setAttribute('aria-expanded', 'false');
					if (pan) {
						pan.hidden = true;
						pan.innerHTML = '';
						pan.setAttribute('aria-hidden', 'true');
					}
					return;
				}
				openMobileInline(root, id, btn);
				btn.setAttribute('aria-expanded', 'true');
			});
		});

		root.addEventListener('keydown', function (e) {
			if (e.key !== 'Escape') return;
			clearMobile(root, triggers);
			clearDesktopActive(root);
			syncFlyoutAria(root);
			if (mq.matches && root.contains(document.activeElement)) {
				try {
					document.activeElement.blur();
				} catch (err) {
					/* ignore */
				}
			}
		});

		var resizeTimer;
		window.addEventListener(
			'resize',
			function () {
				clearTimeout(resizeTimer);
				resizeTimer = window.setTimeout(applyMode, 150);
			},
			{ passive: true }
		);

		mq.addEventListener('change', applyMode);
	}

	function boot() {
		document.querySelectorAll('.biopentra-information-mega').forEach(initMega);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	var mo = new MutationObserver(function () {
		document.querySelectorAll('.biopentra-information-mega:not([data-bp-mega-init])').forEach(initMega);
	});
	if (document.body) {
		mo.observe(document.body, { childList: true, subtree: true });
	}
})();

BPINFOMEGAJS;
}



/**
 * @param string $u_ship Shipping permalink.
 */
function biopentra_megamenu_col2_html( $u_ship ) {
	$html = '<ul class="biopentra-info-mega-links">';
	$html .= biopentra_megamenu_disclosure_button_li( 'quality-standards', 'Quality Standards' );
	$html .= biopentra_megamenu_disclosure_button_li( 'third-party-testing', 'Third-Party Testing' );
	$html .= biopentra_megamenu_disclosure_button_li( 'certificates-coas', 'Certificates / COAs' );
	$html .= biopentra_megamenu_disclosure_button_li( 'purity-analytics', 'Purity & Analytics' );
	$html .= '<li><a href="' . esc_url( $u_ship ) . '">Shipping &amp; Cold Chain</a></li>';
	$html .= '</ul>';
	return $html;
}

/**
 * @param string $u_terms    Terms URL.
 * @param string $u_privacy Privacy URL.
 * @param string $u_refund Refund URL.
 * @param string $u_ship   Shipping URL.
 * @param string $u_disclaim Disclaimer URL.
 */
function biopentra_megamenu_col3_html( $u_terms, $u_privacy, $u_refund, $u_ship, $u_disclaim ) {
	$html = '<ul class="biopentra-info-mega-links">';
	$html .= '<li><a href="' . esc_url( $u_terms ) . '">Terms &amp; Conditions</a></li>';
	$html .= '<li><a href="' . esc_url( $u_privacy ) . '">Privacy Policy</a></li>';
	$html .= '<li><a href="' . esc_url( $u_refund ) . '">Returns &amp; Refunds</a></li>';
	$html .= '<li><a href="' . esc_url( $u_ship ) . '">Shipping Policy</a></li>';
	$html .= biopentra_megamenu_disclosure_button_li( 'compliance-safety', 'Compliance & Safety' );
	$html .= '<li><a href="' . esc_url( $u_disclaim ) . '">Disclaimer</a></li>';
	$html .= '</ul>';
	return $html;
}

/**
 * Copy-only patch (editors + trust strip). Stage/templates require full CLI run once.
 * Run: BIOPENTRA_MEGA_COPY_PATCH=1 ./wp eval-file wp-content/plugins/biopentra-storefront/scripts/cli-update-megamenu.php
 */
if ( getenv( 'BIOPENTRA_MEGA_COPY_PATCH' ) === '1' ) {
	$post_id = 3782;
	$raw     = get_post_meta( $post_id, '_elementor_data', true );
	if ( ! is_string( $raw ) || $raw === '' ) {
		fwrite( STDERR, "Missing _elementor_data for post {$post_id}\n" );
		exit( 1 );
	}
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) ) {
		fwrite( STDERR, "Invalid Elementor JSON\n" );
		exit( 1 );
	}
	$u_ship     = get_permalink( 3827 );
	$u_refund   = get_permalink( 3828 );
	$u_terms    = get_permalink( 3829 );
	$u_disclaim = get_permalink( 3830 );
	$u_privacy  = get_permalink( 3472 );

	$quality_editor  = biopentra_megamenu_col2_html( $u_ship );
	$policies_editor = biopentra_megamenu_col3_html( $u_terms, $u_privacy, $u_refund, $u_ship, $u_disclaim );

	$patch_first_text_editor = function ( &$elements, $html ) {
		if ( empty( $elements ) || ! is_array( $elements ) ) {
			return;
		}
		foreach ( $elements as &$child ) {
			if ( ! is_array( $child ) ) {
				continue;
			}
			if ( isset( $child['widgetType'] ) && 'text-editor' === $child['widgetType'] ) {
				$child['settings']['editor'] = $html;
				return;
			}
		}
		unset( $child );
	};

	$trust_sub = '<p class="biopentra-info-mega-sub">' . esc_html__( 'We source research-use materials from manufacturing partners and provide manufacturer-reported specifications and documentation where available.', 'biopentra-megamenu' ) . '</p>';

	$patch_trust_cta = function ( &$elements ) use ( &$patch_trust_cta, $trust_sub ) {
		if ( empty( $elements ) || ! is_array( $elements ) ) {
			return;
		}
		foreach ( $elements as &$el ) {
			if ( ! is_array( $el ) ) {
				continue;
			}
			$wt = isset( $el['widgetType'] ) ? $el['widgetType'] : '';
			if ( 'heading' === $wt ) {
				$el['settings']['title'] = 'Partner sourcing with manufacturer-provided documentation';
			} elseif ( 'text-editor' === $wt && isset( $el['settings']['editor'] ) && false !== strpos( (string) $el['settings']['editor'], 'biopentra-info-mega-sub' ) ) {
				$el['settings']['editor'] = $trust_sub;
			} elseif ( 'button' === $wt && isset( $el['settings']['link']['url'] ) && '#' === $el['settings']['link']['url'] ) {
				$el['settings']['text'] = 'Explore quality documentation';
			}
			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				$patch_trust_cta( $el['elements'] );
			}
		}
		unset( $el );
	};

	$patch_elements = function ( array $elements, string $quality_editor, string $policies_editor ) use ( &$patch_elements, $patch_first_text_editor, $patch_trust_cta ) {
		foreach ( $elements as &$el ) {
			if ( ! is_array( $el ) ) {
				continue;
			}
			$settings = isset( $el['settings'] ) && is_array( $el['settings'] ) ? $el['settings'] : array();
			$title    = isset( $settings['_title'] ) ? (string) $settings['_title'] : '';

			if ( isset( $el['elType'] ) && 'container' === $el['elType'] ) {
				if ( 'Quality & Trust' === $title ) {
					$patch_first_text_editor( $el['elements'], $quality_editor );
				} elseif ( 'Policies & Legal' === $title ) {
					$patch_first_text_editor( $el['elements'], $policies_editor );
				} elseif ( 'Trust CTA' === $title ) {
					$patch_trust_cta( $el['elements'] );
				}
			}

			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				$el['elements'] = $patch_elements( $el['elements'], $quality_editor, $policies_editor );
			}
		}
		unset( $el );
		return $elements;
	};
	$patched = $patch_elements( $data, $quality_editor, $policies_editor );
	$json    = wp_json_encode( $patched );
	if ( ! is_string( $json ) ) {
		fwrite( STDERR, "Failed to encode JSON\n" );
		exit( 1 );
	}
	update_post_meta( $post_id, '_elementor_data', wp_slash( $json ) );
	delete_post_meta( $post_id, '_elementor_element_cache' );
	delete_post_meta( $post_id, '_elementor_css' );
	if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) && method_exists( \Elementor\Plugin::$instance->files_manager, 'clear_cache' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
	echo "OK: Patched Information mega-menu copy on post {$post_id}.\n";
	exit( 0 );
}

/**
 * Append "How to Pay with Crypto" link to Learn column (idempotent).
 * Run: BIOPENTRA_MEGA_CRYPTO_GUIDE_LINK=1 ./wp eval-file .../cli-update-megamenu.php
 * Optional: BIOPENTRA_MEGA_HEADER_POST_ID=3782
 */
if ( getenv( 'BIOPENTRA_MEGA_CRYPTO_GUIDE_LINK' ) === '1' ) {
	$post_id = (int) ( getenv( 'BIOPENTRA_MEGA_HEADER_POST_ID' ) ?: 3782 );
	$raw     = get_post_meta( $post_id, '_elementor_data', true );
	if ( ! is_string( $raw ) || $raw === '' ) {
		fwrite( STDERR, "Missing _elementor_data for post {$post_id}\n" );
		exit( 1 );
	}
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) ) {
		fwrite( STDERR, "Invalid Elementor JSON\n" );
		exit( 1 );
	}

	$guide_page = get_page_by_path( 'how-to-pay-with-crypto', OBJECT, 'page' );
	$guide_url  = ( $guide_page && 'publish' === $guide_page->post_status ) ? get_permalink( $guide_page ) : '';
	if ( ! is_string( $guide_url ) || $guide_url === '' ) {
		fwrite( STDERR, "Guide page how-to-pay-with-crypto not found or not published. Run import script first.\n" );
		exit( 1 );
	}

	$link_label = 'How to Pay with Crypto';
	$link_li    = '<li><a href="' . esc_url( $guide_url ) . '">' . esc_html( $link_label ) . '</a></li>';
	$changed    = false;

	$patch_learn = function ( array &$elements ) use ( &$patch_learn, $guide_url, $link_label, $link_li, &$changed ) {
		foreach ( $elements as &$el ) {
			if ( ! is_array( $el ) ) {
				continue;
			}
			$settings = isset( $el['settings'] ) && is_array( $el['settings'] ) ? $el['settings'] : array();
			$title    = isset( $settings['_title'] ) ? (string) $settings['_title'] : '';

			if ( isset( $el['elType'] ) && 'container' === $el['elType'] && 'Learn' === $title && ! empty( $el['elements'] ) ) {
				$patch_editor = function ( array &$children ) use ( $guide_url, $link_label, $link_li, &$changed ) {
					foreach ( $children as &$child ) {
						if ( ! is_array( $child ) ) {
							continue;
						}
						if ( isset( $child['widgetType'] ) && 'text-editor' === $child['widgetType'] ) {
							$editor = isset( $child['settings']['editor'] ) ? (string) $child['settings']['editor'] : '';
							if ( str_contains( $editor, 'how-to-pay-with-crypto' ) || str_contains( $editor, $link_label ) ) {
								echo "Skip: Learn column link already present.\n";
								return;
							}
							if ( str_contains( $editor, 'biopentra-info-mega-links' ) && str_contains( $editor, '</ul>' ) ) {
								$child['settings']['editor'] = preg_replace( '/<\/ul>/', $link_li . '</ul>', $editor, 1 );
								$changed                   = true;
								return;
							}
						}
					}
					unset( $child );
				};
				$patch_editor( $el['elements'] );
			}

			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				$patch_learn( $el['elements'] );
			}
		}
		unset( $el );
	};

	$patch_learn( $data );

	if ( ! $changed ) {
		echo "No changes applied (link may already exist or Learn column structure changed).\n";
		exit( 0 );
	}

	$json = wp_json_encode( $data );
	if ( ! is_string( $json ) ) {
		fwrite( STDERR, "Failed to encode JSON\n" );
		exit( 1 );
	}

	update_post_meta( $post_id, '_elementor_data', wp_slash( $json ) );
	delete_post_meta( $post_id, '_elementor_element_cache' );
	delete_post_meta( $post_id, '_elementor_css' );

	if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) && method_exists( \Elementor\Plugin::$instance->files_manager, 'clear_cache' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}

	echo "OK: Appended Learn column crypto guide link on header post {$post_id} -> {$guide_url}\n";
	exit( 0 );
}

/**
 * @return string
 */
function biopentra_megamenu_el_id() {
	return substr( md5( uniqid( 'bpmm', true ) ), 0, 7 );
}

/**
 * @param int         $attachment_id Attachment ID.
 * @param string|null $fallback_url  Fallback URL.
 * @return array{id:int,url:string}
 */
function biopentra_megamenu_img( $attachment_id, $fallback_url = '' ) {
	$url = wp_get_attachment_image_url( (int) $attachment_id, 'medium' );
	if ( ! $url ) {
		$url = $fallback_url ? $fallback_url : '';
	}
	return array(
		'id'  => (int) $attachment_id,
		'url' => $url,
	);
}

/**
 * @param string $label Label.
 * @param string $url   URL (may be #).
 * @return string HTML li.
 */
function biopentra_megamenu_li( $label, $url ) {
	$url   = esc_url( $url );
	$label = esc_html( $label );
	return '<li><a href="' . $url . '">' . $label . '</a></li>';
}

/**
 * @param string[] $items Array of [ label, url ].
 * @return string
 */
function biopentra_megamenu_ul( array $items ) {
	$lis = '';
	foreach ( $items as $pair ) {
		$lis .= biopentra_megamenu_li( $pair[0], $pair[1] );
	}
	return '<ul class="biopentra-info-mega-links">' . $lis . '</ul>';
}

$p_peptide = biopentra_megamenu_img( 3507 );
$p_lab     = biopentra_megamenu_img( 4130 );
$p_box     = biopentra_megamenu_img( 4129 );

$u_faq      = get_permalink( 3826 );
$u_ship     = get_permalink( 3827 );
$u_refund   = get_permalink( 3828 );
$u_terms    = get_permalink( 3829 );
$u_disclaim = get_permalink( 3830 );
$u_privacy  = get_permalink( 3472 );

$hash = '#';

$storage_page = get_page_by_path( 'storage-handling', OBJECT, 'page' );
$u_storage    = ( $storage_page && 'publish' === $storage_page->post_status )
	? get_permalink( $storage_page )
	: $hash;

$peptides_page = get_page_by_path( 'what-are-peptides-v2', OBJECT, 'page' );
if ( ! $peptides_page ) {
	$peptides_page = get_page_by_path( 'what-are-peptides', OBJECT, 'page' );
}
$u_peptides = ( $peptides_page && 'publish' === $peptides_page->post_status )
	? get_permalink( $peptides_page )
	: $hash;

$peptide_guide_page = get_page_by_path( 'peptide-guide', OBJECT, 'page' );
$u_peptide_guide    = ( $peptide_guide_page && 'publish' === $peptide_guide_page->post_status )
	? get_permalink( $peptide_guide_page )
	: $hash;

$col1_editor = biopentra_megamenu_ul(
	array(
		array( 'What Are Peptides?', $u_peptides ),
		array( 'Peptide Guide', $u_peptide_guide ),
		array( 'Research & Intended Use', $u_disclaim ),
		array( 'Storage & Handling', $u_storage ),
		array( 'FAQ', $u_faq ),
	)
);

$col2_editor = biopentra_megamenu_col2_html( $u_ship );
$col3_editor = biopentra_megamenu_col3_html( $u_terms, $u_privacy, $u_refund, $u_ship, $u_disclaim );

$storefront_main = dirname( __DIR__ ) . '/biopentra-storefront.php';
$js_asset_path   = dirname( __DIR__ ) . '/assets/information-megamenu/information-mega.js';
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- CLI publishes editable JS asset.
@file_put_contents( $js_asset_path, biopentra_megamenu_runtime_js() );

$script_ver = '1.3.2';
if ( is_readable( $js_asset_path ) ) {
	$script_loader_html = '<script defer src="' . esc_url( plugins_url( 'assets/information-megamenu/information-mega.js', $storefront_main ) ) . '?ver=' . rawurlencode( $script_ver ) . '"></script>';
} else {
	$script_loader_html = '<div class="biopentra-info-mega-script-host"><script>' . biopentra_megamenu_runtime_js() . '</script></div>';
}

$templates_html = biopentra_megamenu_templates_html();

$cta_url = $hash;

$trust_sub_editor = '<p class="biopentra-info-mega-sub">' . esc_html__( 'We source research-use materials from manufacturing partners and provide manufacturer-reported specifications and documentation where available.', 'biopentra-megamenu' ) . '</p>';

/**
 * Build column branch: heading, links, image card (layout via scoped CSS).
 *
 * @param string                     $title       Column heading.
 * @param array{id:int,url:string}   $image       Image data.
 * @param string                     $editor_html     Link list HTML.
 * @param string                     $column_classes Optional extra classes (e.g. biopentra-info-mega-column--right).
 * @return array
 */
$make_column = function ( $title, array $image, $editor_html, $column_classes = '' ) {
	$cid = biopentra_megamenu_el_id();
	$iid = biopentra_megamenu_el_id();
	$hid = biopentra_megamenu_el_id();
	$tid = biopentra_megamenu_el_id();
	$img_wrap = biopentra_megamenu_el_id();
	$classes  = trim( 'biopentra-info-mega-column ' . $column_classes );

	return array(
		'id'       => $cid,
		'elType'   => 'container',
		'settings' => array(
			'_title'           => $title,
			'css_classes'      => $classes,
			'content_width'    => 'full',
			'flex_direction'   => 'column',
			'flex_align_items' => 'stretch',
			'flex_gap'         => array(
				'size'     => 14,
				'unit'     => 'px',
				'column'   => '14',
				'row'      => '14',
				'isLinked' => true,
			),
		),
		'elements' => array(
			array(
				'id'         => $hid,
				'elType'     => 'widget',
				'widgetType' => 'heading',
				'settings'   => array(
					'title'       => $title,
					'header_size' => 'h4',
					'title_color' => '#324A6D',
					'typography_typography' => 'custom',
					'typography_font_family' => 'Poppins',
					'typography_font_size' => array(
						'unit'  => 'px',
						'size'  => 15,
						'sizes' => array(),
					),
					'typography_font_weight' => '600',
					'typography_text_transform' => 'uppercase',
					'typography_letter_spacing' => array(
						'unit'  => 'px',
						'size'  => 0.6,
						'sizes' => array(),
					),
				),
				'elements' => array(),
			),
			array(
				'id'         => $tid,
				'elType'     => 'widget',
				'widgetType' => 'text-editor',
				'settings'   => array(
					'editor' => $editor_html,
				),
				'elements' => array(),
			),
			array(
				'id'       => $img_wrap,
				'elType'   => 'container',
				'settings' => array(
					'_title'         => 'Column image',
					'css_classes'    => 'biopentra-info-mega-image',
					'content_width'  => 'full',
				),
				'elements' => array(
					array(
						'id'         => $iid,
						'elType'     => 'widget',
						'widgetType' => 'image',
						'settings'   => array(
							'image'      => $image,
							'object-fit' => 'cover',
						),
						'elements' => array(),
					),
				),
				'isInner' => true,
			),
		),
		'isInner' => true,
	);
};

$row_id  = biopentra_megamenu_el_id();
$cta_row = biopentra_megamenu_el_id();
$h_cta   = biopentra_megamenu_el_id();
$t_cta   = biopentra_megamenu_el_id();
$b_cta   = biopentra_megamenu_el_id();
$templates_wid    = biopentra_megamenu_el_id();
$runtime_js_wid = biopentra_megamenu_el_id();

$row_three = array(
	'id'       => $row_id,
	'elType'   => 'container',
	'settings' => array(
		'_title'          => 'Mega columns',
		'css_classes'     => 'biopentra-info-mega-columns',
		'content_width'   => 'full',
		'flex_direction'  => 'row',
		'flex_wrap'       => 'nowrap',
		'width'           => array(
			'unit'   => '%',
			'size'   => 100,
			'sizes'  => array(),
		),
	),
	'elements' => array(
		$make_column( 'Learn', $p_peptide, $col1_editor ),
		$make_column( 'Quality & Trust', $p_lab, $col2_editor ),
		$make_column( 'Policies & Legal', $p_box, $col3_editor, 'biopentra-info-mega-column--right' ),
	),
	'isInner' => true,
);

$templates_widget = array(
	'id'         => $templates_wid,
	'elType'     => 'widget',
	'widgetType' => 'html',
	'settings'   => array(
		'html' => $templates_html,
	),
	'elements' => array(),
);

$runtime_script_widget = array(
	'id'         => $runtime_js_wid,
	'elType'     => 'widget',
	'widgetType' => 'html',
	'settings'   => array(
		'html' => $script_loader_html,
	),
	'elements' => array(),
);

$cta_inner = array(
	'id'       => $cta_row,
	'elType'   => 'container',
	'settings' => array(
		'_title'               => 'Trust CTA',
		'css_classes'          => 'biopentra-info-mega-trust',
		'content_width'        => 'full',
		'flex_direction'       => 'row',
		'flex_wrap'            => 'wrap',
		'flex_align_items'     => 'center',
		'flex_justify_content' => 'space-between',
	),
	'elements' => array(
		array(
			'id'       => biopentra_megamenu_el_id(),
			'elType'   => 'container',
			'settings' => array(
				'_title'          => 'Trust copy',
				'content_width'   => 'full',
				'flex_direction' => 'column',
				'flex_gap'       => array(
					'size'     => 4,
					'unit'     => 'px',
					'isLinked' => true,
				),
			),
			'elements' => array(
				array(
					'id'         => $h_cta,
					'elType'     => 'widget',
					'widgetType' => 'heading',
					'settings'   => array(
						'title'       => 'Partner sourcing with manufacturer-provided documentation',
						'header_size' => 'h4',
						'title_color' => '#324A6D',
						'typography_typography' => 'custom',
						'typography_font_family' => 'Poppins',
						'typography_font_size' => array(
							'unit'  => 'px',
							'size'  => 17,
							'sizes' => array(),
						),
						'typography_font_weight' => '600',
					),
					'elements' => array(),
				),
				array(
					'id'         => $t_cta,
					'elType'     => 'widget',
					'widgetType' => 'text-editor',
					'settings'   => array(
						'editor' => $trust_sub_editor,
					),
					'elements' => array(),
				),
			),
			'isInner' => true,
		),
		array(
			'id'       => biopentra_megamenu_el_id(),
			'elType'   => 'container',
			'settings' => array(
				'_title'               => 'CTA button wrap',
				'content_width'        => 'full',
				'flex_justify_content' => 'flex-end',
				'flex_align_items'     => 'center',
			),
			'elements' => array(
				array(
					'id'         => $b_cta,
					'elType'     => 'widget',
					'widgetType' => 'button',
					'settings'   => array(
						'text'       => __( 'Explore quality documentation', 'biopentra-megamenu' ),
						'link'       => array(
							'url'               => $cta_url,
							'is_external'       => '',
							'nofollow'          => '',
							'custom_attributes' => '',
						),
						'align'      => 'right',
						'align_mobile' => 'left',
						'button_text_color' => '#FFFFFF',
						'background_color' => '#324A6D',
						'border_radius' => array(
							'unit'      => 'px',
							'top'       => '8',
							'right'     => '8',
							'bottom'    => '8',
							'left'      => '8',
							'isLinked'  => true,
						),
						'text_padding' => array(
							'unit'      => 'px',
							'top'       => '12',
							'right'     => '18',
							'bottom'    => '12',
							'left'      => '18',
							'isLinked'  => false,
						),
					),
					'elements' => array(),
				),
			),
			'isInner' => true,
		),
	),
	'isInner' => true,
);

$mega_root_id = biopentra_megamenu_el_id();

$mega_root = array(
	'id'       => $mega_root_id,
	'elType'   => 'container',
	'settings' => array(
		'_title'           => 'Information mega-menu',
		'content_width'    => 'full',
		'flex_direction'   => 'column',
		'flex_gap'         => array(
			'size'     => 0,
			'unit'     => 'px',
			'isLinked' => true,
		),
		'padding'          => array(
			'unit'      => 'px',
			'top'       => '0',
			'right'     => '0',
			'bottom'    => '0',
			'left'      => '0',
			'isLinked'  => true,
		),
		'css_classes'      => 'biopentra-information-mega',
	),
	'elements' => array( $row_three, $templates_widget, $runtime_script_widget, $cta_inner ),
	'isInner'  => true,
	'isLocked' => true,
);

$header_id = 3782;
$raw       = get_post_meta( $header_id, '_elementor_data', true );
if ( ! is_string( $raw ) || $raw === '' ) {
	echo "ERROR: No _elementor_data for post $header_id\n";
	exit( 1 );
}

$data = json_decode( $raw, true );
if ( ! is_array( $data ) ) {
	echo "ERROR: Invalid JSON\n";
	exit( 1 );
}

$found = false;

/**
 * Recursively find mega-menu widget and inject into Information dropdown container.
 *
 * @param array $elements Elements tree.
 * @return bool
 */
$inject = function ( &$elements ) use ( &$inject, &$found, $mega_root ) {
	foreach ( $elements as &$el ) {
		if ( ! empty( $el['widgetType'] ) && $el['widgetType'] === 'mega-menu' && ! empty( $el['elements'] ) ) {
			foreach ( $el['elements'] as &$inner ) {
				$st = isset( $inner['settings'] ) && is_array( $inner['settings'] ) ? $inner['settings'] : array();
				$ti = isset( $st['_title'] ) ? (string) $st['_title'] : '';
				if ( ( isset( $inner['id'] ) && $inner['id'] === '86d35b5' ) || $ti === 'Information dropdown' ) {
					$inner['elements']              = array( $mega_root );
					$inner['settings']['_title'] = 'Information dropdown';
					$found                          = true;
					return true;
				}
			}
			unset( $inner );
		}
		if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
			if ( $inject( $el['elements'] ) ) {
				return true;
			}
		}
	}
	return false;
};

$inject( $data );

if ( ! $found ) {
	echo "ERROR: mega-menu or Information dropdown container not found — header structure may have changed.\n";
	exit( 1 );
}

$encoded = wp_slash( wp_json_encode( $data ) );
update_post_meta( $header_id, '_elementor_data', $encoded );

delete_post_meta( $header_id, '_elementor_element_cache' );
delete_post_meta( $header_id, '_elementor_css' );

if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) && method_exists( \Elementor\Plugin::$instance->files_manager, 'clear_cache' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}

echo "OK: Updated Elementor header {$header_id} Information mega-menu.\n";
