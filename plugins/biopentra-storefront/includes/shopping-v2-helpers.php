<?php
/**
 * Milestone B — shared shop/search/category commercial helpers.
 * Milestone D3 — SEO guide product/archive ownership via page meta.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Post meta: ordered product slugs for SEO guide grids. */
define( 'BIOPENTRA_SEO_GRID_PRODUCTS_META', '_bp_seo_grid_products' );

/** Post meta: WC product_cat slug for “Browse all” archive link. */
define( 'BIOPENTRA_SEO_ARCHIVE_TERM_META', '_bp_seo_archive_term' );

/**
 * Product search form for the shop page (filters the shop loop grid via ?s=).
 *
 * @return string
 */
function biopentra_storefront_get_shop_search_form_html() {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return '';
	}

	$shop_id = (int) wc_get_page_id( 'shop' );
	if ( $shop_id <= 0 ) {
		return '';
	}

	$action = get_permalink( $shop_id );
	$value  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return sprintf(
		'<form class="biopentra-shop-search bp-shop-search" role="search" method="get" action="%s">
	<label class="screen-reader-text" for="biopentra-shop-s">%s</label>
	<input id="biopentra-shop-s" class="bp-shop-search__input" type="search" name="s" value="%s" placeholder="%s" autocomplete="off" enterkeyhint="search" aria-describedby="biopentra-shop-s-hint" />
	<span id="biopentra-shop-s-hint" class="screen-reader-text">%s</span>
</form>',
		esc_url( $action ),
		esc_attr__( 'Search products', 'biopentra-storefront' ),
		esc_attr( $value ),
		esc_attr__( 'Search peptides and research compounds…', 'biopentra-storefront' ),
		esc_html__( 'Press Enter to filter products on this page.', 'biopentra-storefront' )
	);
}

/**
 * Product search form for search results (preserves query + post_type=product).
 *
 * @return string
 */
function biopentra_storefront_get_search_results_form_html() {
	$value = get_search_query( false );

	return sprintf(
		'<form class="biopentra-shop-search bp-search-v2__form" role="search" method="get" action="%s">
	<label class="screen-reader-text" for="biopentra-search-refine">%s</label>
	<input id="biopentra-search-refine" class="bp-search-v2__input" type="search" name="s" value="%s" placeholder="%s" autocomplete="off" enterkeyhint="search" />
	<input type="hidden" name="post_type" value="product" />
</form>',
		esc_url( home_url( '/' ) ),
		esc_attr__( 'Refine product search', 'biopentra-storefront' ),
		esc_attr( $value ),
		esc_attr__( 'Refine your product search…', 'biopentra-storefront' )
	);
}

/**
 * Link to the WooCommerce product archive for a slug.
 *
 * @param string $term_slug product_cat slug.
 * @return string HTML or empty.
 */
function biopentra_storefront_build_wc_archive_link_html( $term_slug ) {
	$term = get_term_by( 'slug', $term_slug, 'product_cat' );
	if ( ! $term instanceof WP_Term ) {
		return '';
	}

	$link = get_term_link( $term );
	if ( is_wp_error( $link ) ) {
		return '';
	}

	return sprintf(
		'<p class="bp-seo-archive-link"><a href="%s">%s</a></p>',
		esc_url( $link ),
		esc_html(
			sprintf(
				/* translators: %s: WooCommerce category name */
				__( 'Browse all %s in the shop', 'biopentra-storefront' ),
				$term->name
			)
		)
	);
}

/**
 * SEO category page structural configuration keyed by page slug.
 *
 * Product inventories and archive terms are page-owned (D3). Structural
 * layout keys remain here for Elementor CLI + CSS enqueue.
 *
 * @return array<string, array<string, mixed>>
 */
function biopentra_storefront_seo_category_configs() {
	return array(
		'growth-hormone-releasing-peptides' => array(
			'root_class'     => 'ghrproot',
			'hero_class'     => 'ghrphero',
			'body_class'     => 'ghrpbody',
			'grid_widget_id' => 'b2grid4430',
		),
		'metabolic-research-peptides'       => array(
			'root_class'     => 'metaroot',
			'hero_class'     => 'metahero',
			'body_class'     => 'metabody',
			'grid_widget_id' => 'b2grid4431',
		),
		'lyophilized-research-materials'    => array(
			'root_class'     => 'lyoroot',
			'hero_class'     => 'lyohero',
			'body_class'     => 'lyobody',
			'grid_widget_id' => 'b2grid4432',
		),
	);
}

/**
 * Deprecated one-release fallback inventories (Milestone B → D3 landing).
 *
 * Used only when page meta is empty. Remove in the release after 0.8.0.
 *
 * @return array<string, array{product_slugs: string[], wc_archive: string}>
 */
function biopentra_storefront_seo_category_legacy_inventory() {
	return array(
		'growth-hormone-releasing-peptides' => array(
			'product_slugs' => array( 'ipamorelin', 'ghrp-2', 'ghrp-6', 'hexarelin', 'sermorelin', 'cjc-1295-no-dac-ipa' ),
			'wc_archive'    => 'growth-performance',
		),
		'metabolic-research-peptides'       => array(
			'product_slugs' => array( 'triple-g', 'tirzepatide', 'mots-c', 'aod9604' ),
			'wc_archive'    => 'weight-management',
		),
		'lyophilized-research-materials'    => array(
			'product_slugs' => array( 'bpc-157', 'tb-500', 'cjc-1295-no-dac-ipa', 'mots-c', 'triple-g', 'kisspeptin' ),
			'wc_archive'    => 'research-peptides',
		),
	);
}

/**
 * Normalize stored grid product meta to an ordered slug list.
 *
 * @param mixed $raw Post meta value.
 * @return string[]
 */
function biopentra_storefront_normalize_seo_grid_products( $raw ) {
	if ( is_array( $raw ) ) {
		$slugs = $raw;
	} elseif ( is_string( $raw ) && '' !== $raw ) {
		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) ) {
			$slugs = $decoded;
		} else {
			$slugs = preg_split( '/[\s,]+/', $raw ) ?: array();
		}
	} else {
		return array();
	}

	$out = array();
	foreach ( $slugs as $slug ) {
		$slug = sanitize_title( (string) $slug );
		if ( '' !== $slug ) {
			$out[] = $slug;
		}
	}

	return array_values( array_unique( $out ) );
}

/**
 * Read ordered product slugs owned by a page (meta first, legacy fallback).
 *
 * @param int $page_id Page ID.
 * @return string[]
 */
function biopentra_storefront_get_seo_grid_product_slugs( $page_id ) {
	$page_id = (int) $page_id;
	if ( $page_id <= 0 ) {
		return array();
	}

	$from_meta = biopentra_storefront_normalize_seo_grid_products(
		get_post_meta( $page_id, BIOPENTRA_SEO_GRID_PRODUCTS_META, true )
	);
	if ( ! empty( $from_meta ) ) {
		return $from_meta;
	}

	$slug    = get_post_field( 'post_name', $page_id );
	$legacy  = biopentra_storefront_seo_category_legacy_inventory();
	if ( ! empty( $legacy[ $slug ]['product_slugs'] ) && is_array( $legacy[ $slug ]['product_slugs'] ) ) {
		return array_map( 'sanitize_title', $legacy[ $slug ]['product_slugs'] );
	}

	return array();
}

/**
 * Read WC archive term slug owned by a page (meta first, legacy fallback).
 *
 * @param int $page_id Page ID.
 * @return string
 */
function biopentra_storefront_get_seo_archive_term( $page_id ) {
	$page_id = (int) $page_id;
	if ( $page_id <= 0 ) {
		return '';
	}

	$from_meta = sanitize_title( (string) get_post_meta( $page_id, BIOPENTRA_SEO_ARCHIVE_TERM_META, true ) );
	if ( '' !== $from_meta ) {
		return $from_meta;
	}

	$slug   = get_post_field( 'post_name', $page_id );
	$legacy = biopentra_storefront_seo_category_legacy_inventory();
	if ( ! empty( $legacy[ $slug ]['wc_archive'] ) ) {
		return sanitize_title( (string) $legacy[ $slug ]['wc_archive'] );
	}

	return '';
}

/**
 * Resolve configured product IDs for an SEO category page slug.
 *
 * @param string $page_slug Page post_name.
 * @return int[]
 */
function biopentra_storefront_seo_category_product_ids( $page_slug ) {
	$page = get_page_by_path( $page_slug, OBJECT, 'page' );
	if ( ! $page instanceof WP_Post ) {
		return array();
	}

	$ids = array();
	foreach ( biopentra_storefront_get_seo_grid_product_slugs( (int) $page->ID ) as $slug ) {
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		if ( $post instanceof WP_Post ) {
			$ids[] = (int) $post->ID;
		}
	}

	return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Limit Elementor loop grids on SEO category pages to page-owned products.
 *
 * @param array                             $query_args Query args.
 * @param \Elementor\Widget_Base|\WP_Widget $widget     Widget.
 * @return array
 */
function biopentra_storefront_seo_category_loop_query( $query_args, $widget ) {
	if ( ! is_page() || ! is_object( $widget ) || ! method_exists( $widget, 'get_id' ) ) {
		return $query_args;
	}

	$page_id   = get_queried_object_id();
	$page_slug = get_post_field( 'post_name', $page_id );
	$configs   = biopentra_storefront_seo_category_configs();

	if ( empty( $configs[ $page_slug ] ) || $configs[ $page_slug ]['grid_widget_id'] !== $widget->get_id() ) {
		return $query_args;
	}

	$ids = biopentra_storefront_seo_category_product_ids( $page_slug );
	if ( empty( $ids ) ) {
		return $query_args;
	}

	$query_args['post__in'] = $ids;
	$query_args['orderby']  = 'post__in';

	return $query_args;
}
add_filter( 'elementor/query/query_args', 'biopentra_storefront_seo_category_loop_query', 30, 2 );

/**
 * Register Milestone B shortcodes (Elementor strips raw forms in HTML widgets).
 */
function biopentra_storefront_register_shopping_v2_shortcodes() {
	add_shortcode(
		'biopentra_shop_search',
		static function () {
			return biopentra_storefront_get_shop_search_form_html();
		}
	);

	add_shortcode(
		'biopentra_search_refine',
		static function () {
			return biopentra_storefront_get_search_results_form_html();
		}
	);

	add_shortcode(
		'biopentra_wc_archive_link',
		static function ( $atts ) {
			$atts = shortcode_atts(
				array( 'slug' => '' ),
				$atts,
				'biopentra_wc_archive_link'
			);

			$term_slug = sanitize_title( (string) $atts['slug'] );

			// Prefer page-owned archive term when rendering on an SEO guide page.
			if ( is_page() ) {
				$page_term = biopentra_storefront_get_seo_archive_term( get_queried_object_id() );
				if ( '' !== $page_term ) {
					$term_slug = $page_term;
				}
			}

			if ( '' === $term_slug ) {
				$term_slug = 'research-peptides';
			}

			return biopentra_storefront_build_wc_archive_link_html( $term_slug );
		}
	);
}
add_action( 'init', 'biopentra_storefront_register_shopping_v2_shortcodes' );

/**
 * Render search refinement UI on product search results (Blocksy native template).
 */
function biopentra_storefront_search_v2_render_refine_bar() {
	if ( ! is_search() || ! function_exists( 'wc_get_page_id' ) ) {
		return;
	}

	if ( empty( $_GET['post_type'] ) || 'product' !== sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	echo '<div class="bp-search-v2" role="search">';
	echo biopentra_storefront_get_search_results_form_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div>';
}
add_action( 'woocommerce_before_shop_loop', 'biopentra_storefront_search_v2_render_refine_bar', 3 );
add_action( 'woocommerce_no_products_found', 'biopentra_storefront_search_v2_render_refine_bar', 3 );
