<?php
/**
 * Milestone B — shared shop/search/category commercial helpers.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
 * SEO category page configuration keyed by page slug.
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
			'wc_archive'     => 'growth-performance',
			'product_slugs'  => array( 'ipamorelin', 'ghrp-2', 'ghrp-6', 'hexarelin', 'sermorelin', 'cjc-1295-no-dac-ipa' ),
		),
		'metabolic-research-peptides'       => array(
			'root_class'     => 'metaroot',
			'hero_class'     => 'metahero',
			'body_class'     => 'metabody',
			'grid_widget_id' => 'b2grid4431',
			'wc_archive'     => 'weight-management',
			'product_slugs'  => array( 'triple-g', 'tirzepatide', 'mots-c', 'aod9604' ),
		),
		'lyophilized-research-materials'    => array(
			'root_class'     => 'lyoroot',
			'hero_class'     => 'lyohero',
			'body_class'     => 'lyobody',
			'grid_widget_id' => 'b2grid4432',
			'wc_archive'     => 'research-peptides',
			'product_slugs'  => array( 'bpc-157', 'tb-500', 'cjc-1295-no-dac-ipa', 'mots-c', 'triple-g', 'kisspeptin' ),
		),
	);
}

/**
 * Resolve configured product IDs for an SEO category page slug.
 *
 * @param string $page_slug Page post_name.
 * @return int[]
 */
function biopentra_storefront_seo_category_product_ids( $page_slug ) {
	$configs = biopentra_storefront_seo_category_configs();
	if ( empty( $configs[ $page_slug ]['product_slugs'] ) ) {
		return array();
	}

	$ids = array();
	foreach ( $configs[ $page_slug ]['product_slugs'] as $slug ) {
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		if ( $post instanceof WP_Post ) {
			$ids[] = (int) $post->ID;
		}
	}

	return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Limit Elementor loop grids on SEO category pages to configured products.
 *
 * @param array                           $query_args Query args.
 * @param \Elementor\Widget_Base|\WP_Widget $widget   Widget.
 * @return array
 */
function biopentra_storefront_seo_category_loop_query( $query_args, $widget ) {
	if ( ! is_page() || ! is_object( $widget ) || ! method_exists( $widget, 'get_id' ) ) {
		return $query_args;
	}

	$page_slug = get_post_field( 'post_name', get_queried_object_id() );
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
				array( 'slug' => 'research-peptides' ),
				$atts,
				'biopentra_wc_archive_link'
			);

			return biopentra_storefront_build_wc_archive_link_html( sanitize_title( $atts['slug'] ) );
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
