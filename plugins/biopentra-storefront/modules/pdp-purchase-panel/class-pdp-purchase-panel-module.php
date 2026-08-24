<?php
/**
 * PDP-1 — Purchase-summary purchase panel.
 *
 * Wraps the native WooCommerce purchase controls (price/stock/variation
 * selection/quantity/Add-to-cart) in a single visual panel using additive
 * hooks only. No template override, no duplicated commerce state.
 *
 * Spec: docs/storefront-redesign/plans/PDP-1_PURCHASE_SUMMARY_REDESIGN.md
 * Freeze commit: 4ae5ee798b26ceecd5d758f81d0cb7a194ed1a4d
 * Blocksy-ownership amendment: see plan Addendum A (dated 2026-08-24).
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Biopentra_Storefront_Pdp_Purchase_Panel_Module {

	/**
	 * Render-state flag for the variable-product panel wrapper (§2.4 of the plan).
	 *
	 * @var bool
	 */
	private static $variable_panel_open = false;

	/**
	 * Register hooks (idempotent if called once).
	 */
	public static function init() {
		if ( ! function_exists( 'is_product' ) ) {
			return;
		}

		// Eyebrow (WP1) — before the title, native product summary hook.
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_eyebrow' ), 4 );

		// Simple-product price relocation (WP0, §3, Addendum A) — suppress
		// Blocksy's own outer price layer via its supported layout filter
		// (Blocksy owns woocommerce_single_product_summary's title/price/
		// excerpt rendering; there is no WooCommerce hook left to remove).
		add_filter( 'blocksy:woocommerce:product-single:layout', array( __CLASS__, 'suppress_blocksy_simple_price_layer' ) );
		add_action( 'woocommerce_before_add_to_cart_form', array( __CLASS__, 'maybe_render_simple_price' ), 5 );

		// WP4 polish: Blocksy's default layout renders its own divider
		// ('divider_2') between the add-to-cart layer and product_meta —
		// redundant with layout.css's existing .product_meta top border
		// (D2A), producing two visible lines with excessive gap between
		// them. Same supported layout filter as the price suppression;
		// applies to both product types (the panel/meta transition looks
		// the same regardless of product type).
		add_filter( 'blocksy:woocommerce:product-single:layout', array( __CLASS__, 'suppress_redundant_meta_divider' ) );

		// Simple-product panel open/close — always balanced (simple.php has no empty-state skip).
		add_action( 'woocommerce_before_add_to_cart_form', array( __CLASS__, 'open_panel_simple' ), 1 );
		add_action( 'woocommerce_after_add_to_cart_form', array( __CLASS__, 'close_panel_simple' ), 99 );

		// Variable-product panel open/close — balanced via render-state flag (§2.4).
		add_action( 'woocommerce_after_variations_table', array( __CLASS__, 'open_panel_variable' ), 1 );
		add_action( 'woocommerce_after_variations_form', array( __CLASS__, 'close_panel_variable' ), 99 );

		// Trust row (WP2) — appended just before panel-close, both product types.
		add_action( 'woocommerce_after_add_to_cart_form', array( __CLASS__, 'render_trust_row_simple' ), 90 );
		add_action( 'woocommerce_after_variations_form', array( __CLASS__, 'render_trust_row_variable' ), 90 );

		// WP4 polish: remove the variation "Clear" link (PO decision, 2026-08-24).
		add_filter( 'woocommerce_reset_variations_link', '__return_empty_string' );

		// WP4 polish: wrap native product_meta labels (SKU:/Category:/Tags:) in
		// their own <span> so CSS can align them into a label/value layout —
		// filters WordPress's own translation pipeline for these three exact
		// strings, scoped to single-product pages only. No template override:
		// meta.php's own markup and logic are untouched, only the already-
		// translatable label strings it echoes are wrapped.
		add_filter( 'gettext', array( __CLASS__, 'wrap_meta_label_gettext' ), 10, 3 );
		add_filter( 'ngettext', array( __CLASS__, 'wrap_meta_label_ngettext' ), 10, 5 );

		// SKU's label can't go through the gettext filter above (meta.php
		// echoes it via esc_html_e(), which would re-escape an injected
		// <span> into literal visible text — see the gettext callback's
		// docblock). Instead, buffer the native product_meta output and
		// string-replace the already-escaped "SKU:" text in the rendered
		// HTML — same visual result, no double-escaping, no template
		// override (meta.php's own markup/logic are still untouched).
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'start_meta_buffer' ), 39 );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'end_meta_buffer' ), 41 );
	}

	/**
	 * @var string
	 */
	private static $sku_label_needle = '';

	/**
	 * Start buffering just before woocommerce_template_single_meta() (priority 40).
	 */
	public static function start_meta_buffer() {
		self::$sku_label_needle = '<span class="sku_wrapper">' . esc_html__( 'SKU:', 'woocommerce' );
		ob_start();
	}

	/**
	 * Wrap the already-rendered "SKU:" label text and echo the buffered output.
	 */
	public static function end_meta_buffer() {
		$html = ob_get_clean();

		if ( '' !== self::$sku_label_needle && false !== strpos( $html, self::$sku_label_needle ) ) {
			$html = str_replace(
				self::$sku_label_needle,
				'<span class="sku_wrapper"><span class="bp-pdp-meta-label">' . esc_html__( 'SKU:', 'woocommerce' ) . '</span>',
				$html
			);
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * WP4 — the exact native label strings from templates/single-product/meta.php
	 * that should be wrapped for grid alignment.
	 *
	 * "SKU:" is deliberately excluded: meta.php echoes it via esc_html_e(),
	 * which re-escapes whatever this filter returns, turning an injected
	 * <span> into literal visible "&lt;span&gt;" text. "Category:"/"Categories:"/
	 * "Tag:"/"Tags:" are safe to wrap because wc_get_product_category_list()/
	 * wc_get_product_tag_list() use the translated string raw, as the $before
	 * HTML parameter, with no further escaping. SKU gets a CSS-only treatment
	 * instead (see purchase-panel.css) using its existing nested .sku span.
	 */
	private static $meta_labels = array( 'Category:', 'Categories:', 'Tag:', 'Tags:' );

	/**
	 * @param string $translation Translated string.
	 * @param string $text        Original (untranslated) string.
	 * @param string $domain      Text domain.
	 * @return string
	 */
	public static function wrap_meta_label_gettext( $translation, $text, $domain ) {
		if ( 'woocommerce' !== $domain || ! is_product() || ! in_array( $text, self::$meta_labels, true ) ) {
			return $translation;
		}

		return '<span class="bp-pdp-meta-label">' . esc_html( $translation ) . '</span>';
	}

	/**
	 * @param string $translation Translated plural string.
	 * @param string $single      Singular form.
	 * @param string $plural      Plural form.
	 * @param int    $number      Count.
	 * @param string $domain      Text domain.
	 * @return string
	 */
	public static function wrap_meta_label_ngettext( $translation, $single, $plural, $number, $domain ) {
		if ( 'woocommerce' !== $domain || ! is_product() || ! in_array( $single, self::$meta_labels, true ) ) {
			return $translation;
		}

		return '<span class="bp-pdp-meta-label">' . esc_html( $translation ) . '</span>';
	}

	/**
	 * §3 / Addendum A — suppress Blocksy's own outer 'product_price' layer
	 * for simple products only, for the current render only.
	 *
	 * This is Blocksy's own supported extension seam
	 * (`blocksy:woocommerce:product-single:layout`, applied in
	 * `render_layout()` in themes/blocksy/inc/components/woocommerce/single/single.php)
	 * — it filters the in-memory layout array for this request only, never
	 * touching the persisted `woo_single_layout` theme mod, and only ever
	 * runs inside the single-product summary render (default-gallery /
	 * stacked-gallery view types), so it cannot affect shop/loop cards.
	 * Variable products are explicitly excluded — their top-level price
	 * range layer is left untouched.
	 *
	 * @param array $layout Ordered list of layer definitions, each with
	 *                       'id' and 'enabled' keys.
	 * @return array
	 */
	public static function suppress_blocksy_simple_price_layer( $layout ) {
		global $product;

		if ( ! is_product() || ! $product instanceof WC_Product || ! $product->is_type( 'simple' ) ) {
			return $layout;
		}

		if ( ! is_array( $layout ) ) {
			return $layout;
		}

		foreach ( $layout as $index => $layer ) {
			if ( isset( $layer['id'] ) && 'product_price' === $layer['id'] ) {
				$layout[ $index ]['enabled'] = false;
			}
		}

		return $layout;
	}

	/**
	 * WP4 — remove Blocksy's own 'divider_2' layer (between add-to-cart and
	 * product_meta) for the current render only. In-memory only, no
	 * persisted theme-mod change; applies to both product types.
	 *
	 * @param array $layout Ordered list of layer definitions.
	 * @return array
	 */
	public static function suppress_redundant_meta_divider( $layout ) {
		if ( ! is_product() || ! is_array( $layout ) ) {
			return $layout;
		}

		foreach ( $layout as $index => $layer ) {
			if ( isset( $layer['__id'] ) && 'divider_2' === $layer['__id'] ) {
				$layout[ $index ]['enabled'] = false;
			}
		}

		return $layout;
	}

	/**
	 * §3 / Addendum A — render the native single price exactly once, inside
	 * the simple-product purchase panel. WooCommerce remains the sole price
	 * authority; this only changes where its own renderer is invoked.
	 */
	public static function maybe_render_simple_price() {
		global $product;

		if ( ! $product instanceof WC_Product || ! $product->is_type( 'simple' ) ) {
			return;
		}

		woocommerce_template_single_price();
	}

	/**
	 * §2.1 — open the purchase panel for simple products.
	 */
	public static function open_panel_simple() {
		global $product;

		if ( ! $product instanceof WC_Product || ! $product->is_type( 'simple' ) ) {
			return;
		}

		echo '<div class="bp-pdp-purchase-panel">';
	}

	/**
	 * Close the simple-product purchase panel (always balanced with open_panel_simple).
	 */
	public static function close_panel_simple() {
		global $product;

		if ( ! $product instanceof WC_Product || ! $product->is_type( 'simple' ) ) {
			return;
		}

		echo '</div>';
	}

	/**
	 * §2.1/§2.4 — open the purchase panel for variable products.
	 *
	 * Only fires when variable.php's else-branch actually rendered the
	 * variations table (i.e. genuine variations exist) — tracked via a
	 * render-state flag, not a re-derivation of WooCommerce's own condition.
	 */
	public static function open_panel_variable() {
		echo '<div class="bp-pdp-purchase-panel">';
		self::$variable_panel_open = true;
	}

	/**
	 * §2.4 — close the variable-product purchase panel only if it was opened.
	 */
	public static function close_panel_variable() {
		if ( ! self::$variable_panel_open ) {
			return;
		}

		echo '</div>';
		self::$variable_panel_open = false;
	}

	/**
	 * §11 amendment (PO decision, 2026-08-24) — icon + two-line trust row.
	 * Copy replaces the plan's originally locked single-line text per
	 * explicit PO override; both product types share this markup.
	 */
	private static function trust_row_html() {
		$items = array(
			array(
				'title'    => __( 'EU Warehouse', 'biopentra-storefront' ),
				'subtitle' => __( 'Fast delivery', 'biopentra-storefront' ),
				'icon'     => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="6" width="13" height="11"></rect><path d="M14 9h4l4 4v4h-8z"></path><circle cx="6" cy="19" r="2"></circle><circle cx="17" cy="19" r="2"></circle></svg>',
			),
			array(
				'title'    => __( 'Secure Payment', 'biopentra-storefront' ),
				'subtitle' => __( 'SSL encrypted', 'biopentra-storefront' ),
				'icon'     => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>',
			),
			array(
				'title'    => __( 'Lab Quality', 'biopentra-storefront' ),
				'subtitle' => __( 'Purity verified', 'biopentra-storefront' ),
				'icon'     => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l7 3v6c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V5z"></path></svg>',
			),
		);

		$html = '<ul class="bp-pdp-trust-row">';
		foreach ( $items as $item ) {
			$html .= '<li class="bp-pdp-trust-row__item">'
				. '<span class="bp-pdp-trust-row__icon" aria-hidden="true">' . $item['icon'] . '</span>'
				. '<span class="bp-pdp-trust-row__text">'
				. '<span class="bp-pdp-trust-row__title">' . esc_html( $item['title'] ) . '</span>'
				. '<span class="bp-pdp-trust-row__subtitle">' . esc_html( $item['subtitle'] ) . '</span>'
				. '</span>'
				. '</li>';
		}
		$html .= '</ul>';

		return $html;
	}

	/**
	 * Render trust row for simple products (only if the panel was opened).
	 */
	public static function render_trust_row_simple() {
		global $product;

		if ( ! $product instanceof WC_Product || ! $product->is_type( 'simple' ) ) {
			return;
		}

		echo self::trust_row_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render trust row for variable products (only if the panel was opened).
	 */
	public static function render_trust_row_variable() {
		if ( ! self::$variable_panel_open ) {
			return;
		}

		echo self::trust_row_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * §4 — deterministic category eyebrow. Never surfaces the default
	 * ("Uncategorized") category; omits the eyebrow entirely if no other
	 * category is assigned.
	 */
	public static function render_eyebrow() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$product_id      = $product->get_id();
		$default_cat_id  = (int) get_option( 'default_product_cat' );
		$eyebrow         = '';

		// Priority 1: Rank Math primary category, if set and not the default term.
		$primary_term_id = (int) get_post_meta( $product_id, 'rank_math_primary_product_cat', true );
		if ( $primary_term_id && $primary_term_id !== $default_cat_id ) {
			$assigned_terms = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $assigned_terms ) && in_array( $primary_term_id, $assigned_terms, true ) ) {
				$term = get_term( $primary_term_id, 'product_cat' );
				if ( $term && ! is_wp_error( $term ) ) {
					$eyebrow = $term->name;
				}
			}
		}

		// Priority 2: first non-default assigned category, by term_id ascending.
		if ( '' === $eyebrow ) {
			$terms = get_the_terms( $product_id, 'product_cat' );
			if ( is_array( $terms ) && ! empty( $terms ) ) {
				$non_default = array_filter(
					$terms,
					function ( $term ) use ( $default_cat_id ) {
						return (int) $term->term_id !== $default_cat_id;
					}
				);

				if ( ! empty( $non_default ) ) {
					usort(
						$non_default,
						function ( $a, $b ) {
							return $a->term_id <=> $b->term_id;
						}
					);
					$eyebrow = reset( $non_default )->name;
				}
			}
		}

		// Priority 3: omit entirely — never render "Uncategorized".
		if ( '' === $eyebrow ) {
			return;
		}

		echo '<p class="bp-pdp-eyebrow">' . esc_html( $eyebrow ) . '</p>';
	}
}
