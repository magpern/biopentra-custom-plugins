<?php
/**
 * Technical SEO foundation: meta defaults, robots, schema, sitemap hygiene.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storefront technical SEO module.
 */
class Biopentra_Storefront_Technical_Seo_Module {

	/**
	 * Post slugs that must never be indexed.
	 *
	 * @var string[]
	 */
	private static $noindex_page_slugs = array(
		'cart-2',
		'checkout-2',
		'my-account-2',
		'access-restricted',
		'footer-preview-eu-v2',
		'gift-card-balance',
		'mp-cp-block-cart-qa',
		'mp-cp-block-checkout-qa',
	);

	/**
	 * Bootstrap hooks.
	 */
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'render_meta_tags' ), 1 );
		add_filter( 'wp_robots', array( __CLASS__, 'filter_wp_robots' ), 99 );
		add_filter( 'robots_txt', array( __CLASS__, 'filter_robots_txt' ), 99, 2 );
		add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'attachment_alt_fallback' ), 20, 3 );
		add_filter( 'wp_sitemaps_posts_query_args', array( __CLASS__, 'filter_sitemap_posts' ), 20, 2 );
		add_filter( 'wp_sitemaps_posts_query_args', array( __CLASS__, 'filter_sitemap_products' ), 25, 2 );
		add_filter( 'woocommerce_product_is_visible', array( __CLASS__, 'hide_qa_products_from_catalog' ), 10, 2 );
		add_filter( 'wp_sitemaps_taxonomies_query_args', array( __CLASS__, 'filter_sitemap_taxonomies' ), 20, 2 );
		add_action( 'wp_head', array( __CLASS__, 'render_json_ld' ), 20 );
		add_filter( 'elementor/widget/render_content', array( __CLASS__, 'strip_elementor_faq_schema_on_faq_page' ), 10, 2 );
		add_filter( 'get_canonical_url', array( __CLASS__, 'filter_canonical_url' ), 20, 2 );
		add_filter(
			'wp_sitemaps_add_provider',
			static function ( $provider, $name ) {
				if ( 'users' === $name ) {
					return false;
				}
				return $provider;
			},
			10,
			2
		);
	}

	/**
	 * Rank Math is the SEO authority for meta, social tags, and core JSON-LD.
	 */
	private static function rank_math_handles_head_seo() {
		return defined( 'RANK_MATH_FILE' );
	}

	/**
	 * Whether the current request should be noindexed.
	 */
	public static function should_noindex_request() {
		if ( is_search() ) {
			return true;
		}

		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return true;
		}

		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return true;
		}

		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return true;
		}

		if ( is_tax( 'product_cat', 'uncategorized' ) ) {
			return true;
		}

		if ( is_singular( 'page' ) ) {
			$post = get_queried_object();
			if ( $post instanceof WP_Post && in_array( $post->post_name, self::$noindex_page_slugs, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array<string, bool|string> $robots Robots directives.
	 * @return array<string, bool|string>
	 */
	public static function filter_wp_robots( $robots ) {
		if ( ! self::should_noindex_request() ) {
			return $robots;
		}

		$robots['noindex'] = true;
		$robots['nofollow'] = true;
		unset( $robots['max-image-preview'] );

		return $robots;
	}

	/**
	 * @param string $output Robots.txt output.
	 * @param bool   $public Whether site is public.
	 */
	public static function filter_robots_txt( $output, $public ) {
		if ( ! $public ) {
			return $output;
		}

		if ( '' !== $output && ! str_ends_with( rtrim( $output ), "\n" ) ) {
			$output .= "\n";
		}

		$sitemap = trailingslashit( home_url() ) . 'sitemap.xml';
		if ( false === stripos( $output, 'Sitemap:' ) ) {
			$output .= "Sitemap: {$sitemap}\n";
		}

		if ( false === stripos( $output, '/product-category/uncategorized/' ) ) {
			$output .= "Disallow: /product-category/uncategorized/\n";
		}

		if ( false === stripos( $output, '/?s=' ) ) {
			$output .= "Disallow: /?s=\n";
			$output .= "Disallow: /*?s=\n";
		}

		return $output;
	}

	/**
	 * @param array<string, mixed> $query_args Sitemap query args.
	 * @param string               $post_type  Post type.
	 * @return array<string, mixed>
	 */
	public static function filter_sitemap_posts( $query_args, $post_type ) {
		if ( 'page' !== $post_type ) {
			return $query_args;
		}

		$query_args['post_name__not_in'] = array_merge(
			(array) ( $query_args['post_name__not_in'] ?? array() ),
			self::$noindex_page_slugs
		);

		return $query_args;
	}

	/**
	 * Exclude QA/smoke products from the product sitemap (belt-and-suspenders).
	 *
	 * @param array<string, mixed> $query_args Sitemap query args.
	 * @param string               $post_type  Post type.
	 * @return array<string, mixed>
	 */
	public static function filter_sitemap_products( $query_args, $post_type ) {
		if ( 'product' !== $post_type ) {
			return $query_args;
		}

		$exclude = self::get_qa_product_ids();
		if ( empty( $exclude ) ) {
			return $query_args;
		}

		$query_args['post__not_in'] = array_merge(
			array_map( 'absint', (array) ( $query_args['post__not_in'] ?? array() ) ),
			$exclude
		);

		return $query_args;
	}

	/**
	 * Hide smoke/QA products from shop archives if still published.
	 *
	 * @param bool $visible   Visible flag.
	 * @param int  $product_id Product ID.
	 */
	public static function hide_qa_products_from_catalog( $visible, $product_id ) {
		if ( self::is_qa_storefront_product( $product_id ) ) {
			return false;
		}

		return $visible;
	}

	/**
	 * @return list<int>
	 */
	private static function get_qa_product_ids() {
		$ids = array();
		foreach ( get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		) as $product_id ) {
			if ( self::is_qa_storefront_product( (int) $product_id ) ) {
				$ids[] = (int) $product_id;
			}
		}

		return $ids;
	}

	/**
	 * @param int $product_id Product post ID.
	 */
	public static function is_qa_storefront_product( $product_id ) {
		$post = get_post( $product_id );
		if ( ! $post instanceof WP_Post || 'product' !== $post->post_type ) {
			return false;
		}

		$sku = get_post_meta( $product_id, '_sku', true );
		$hay = strtolower( $post->post_title . ' ' . $post->post_name . ' ' . (string) $sku );

		$patterns = array( 'smoke', 'qa', 'test', 'demo', 'mp-cp', 'mp cp', 'scheduled delivery', 'delivery security', 'browser qa', 'commerce growth gift' );
		foreach ( $patterns as $pattern ) {
			if ( str_contains( $hay, $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array<string, mixed> $query_args Taxonomy query args.
	 * @param string               $taxonomy   Taxonomy name.
	 * @return array<string, mixed>
	 */
	public static function filter_sitemap_taxonomies( $query_args, $taxonomy ) {
		if ( 'product_cat' !== $taxonomy ) {
			return $query_args;
		}

		$exclude = array_map( 'absint', (array) ( $query_args['exclude'] ?? array() ) );
		$exclude[] = absint( get_option( 'default_product_cat', 0 ) );

		$smoke_bogo = get_term_by( 'slug', 'smoke-bogo', 'product_cat' );
		if ( $smoke_bogo instanceof WP_Term ) {
			$exclude[] = (int) $smoke_bogo->term_id;
		}

		$query_args['exclude'] = array_values( array_filter( array_unique( $exclude ) ) );

		return $query_args;
	}

	/**
	 * @param string   $url         Canonical URL.
	 * @param WP_Post|null $post    Post object.
	 */
	public static function filter_canonical_url( $url, $post ) {
		if ( self::should_noindex_request() && is_singular() ) {
			$correct = get_permalink( get_queried_object_id() );
			if ( $correct ) {
				return $correct;
			}
		}

		return $url;
	}

	/**
	 * Meta description for the current document.
	 */
	public static function get_meta_description() {
		if ( is_front_page() ) {
			$tagline = get_bloginfo( 'description', 'display' );
			if ( $tagline ) {
				return self::trim_description( $tagline );
			}
		}

		if ( is_singular() ) {
			$post = get_queried_object();
			if ( ! $post instanceof WP_Post ) {
				return '';
			}

			$custom = get_post_meta( $post->ID, '_biopentra_meta_description', true );
			if ( is_string( $custom ) && '' !== trim( $custom ) ) {
				return self::trim_description( $custom );
			}

			if ( has_excerpt( $post ) ) {
				return self::trim_description( get_the_excerpt( $post ) );
			}

			if ( 'product' === $post->post_type && function_exists( 'wc_get_product' ) ) {
				$product = wc_get_product( $post->ID );
				if ( $product ) {
					$short = $product->get_short_description();
					if ( is_string( $short ) && '' !== trim( wp_strip_all_tags( $short ) ) ) {
						return self::trim_description( $short );
					}

					return self::trim_description(
						sprintf(
							/* translators: %s: product name */
							__( 'Shop %s — research-grade peptides with COA-backed quality from BioPentra.', 'biopentra-storefront' ),
							$product->get_name()
						)
					);
				}
			}

			$content = wp_strip_all_tags( (string) $post->post_content );
			if ( '' !== $content ) {
				return self::trim_description( $content );
			}
		}

		if ( is_tax() || is_category() || is_tag() ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term && ! empty( $term->description ) ) {
				return self::trim_description( $term->description );
			}
		}

		if ( function_exists( 'is_shop' ) && is_shop() ) {
			$shop_id = (int) wc_get_page_id( 'shop' );
			if ( $shop_id > 0 ) {
				$shop = get_post( $shop_id );
				if ( $shop instanceof WP_Post && has_excerpt( $shop ) ) {
					return self::trim_description( get_the_excerpt( $shop ) );
				}
			}

			return self::trim_description(
				__( 'Browse research-grade peptides at BioPentra. Fast EU shipping, COA-backed quality, and secure checkout.', 'biopentra-storefront' )
			);
		}

		if ( is_page( 'faq' ) ) {
			return self::trim_description(
				__( 'Answers about BioPentra orders, shipping, research-use policies, payments, and product quality.', 'biopentra-storefront' )
			);
		}

		return '';
	}

	/**
	 * @param string $text Raw description text.
	 */
	private static function trim_description( $text ) {
		$text = wp_strip_all_tags( html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' ) );
		$text = preg_replace( '/\s+/u', ' ', $text );
		$text = trim( (string) $text );

		if ( mb_strlen( $text ) > 160 ) {
			$text = mb_substr( $text, 0, 157 ) . '…';
		}

		return $text;
	}

	/**
	 * Open Graph image URL for the current document.
	 */
	public static function get_og_image_url() {
		if ( is_singular() && has_post_thumbnail() ) {
			$url = get_the_post_thumbnail_url( get_queried_object_id(), 'large' );
			if ( $url ) {
				return $url;
			}
		}

		$logo_id = (int) get_theme_mod( 'custom_logo' );
		if ( $logo_id > 0 ) {
			$url = wp_get_attachment_image_url( $logo_id, 'full' );
			if ( $url ) {
				return $url;
			}
		}

		$site_icon = get_site_icon_url( 512 );
		if ( $site_icon ) {
			return $site_icon;
		}

		return '';
	}

	/**
	 * Output meta description + Open Graph tags.
	 */
	public static function render_meta_tags() {
		if ( self::rank_math_handles_head_seo() ) {
			return;
		}

		if ( is_admin() || is_feed() || is_robots() ) {
			return;
		}

		$description = self::get_meta_description();
		if ( $description ) {
			printf(
				'<meta name="description" content="%s" />' . "\n",
				esc_attr( $description )
			);
		}

		$title = wp_get_document_title();
		$url   = self::get_current_url();
		$type  = is_singular( 'product' ) ? 'product' : ( is_singular() ? 'article' : 'website' );
		$image = self::get_og_image_url();

		printf( '<meta property="og:locale" content="%s" />' . "\n", esc_attr( get_locale() ) );
		printf( '<meta property="og:site_name" content="%s" />' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
		printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $title ) );
		printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $url ) );
		printf( '<meta property="og:type" content="%s" />' . "\n", esc_attr( $type ) );

		if ( $description ) {
			printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $description ) );
		}

		if ( $image ) {
			printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $image ) );
		}

		printf( '<meta name="twitter:card" content="%s" />' . "\n", $image ? 'summary_large_image' : 'summary' );
		printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( $title ) );
		if ( $description ) {
			printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( $description ) );
		}
		if ( $image ) {
			printf( '<meta name="twitter:image" content="%s" />' . "\n", esc_url( $image ) );
		}
	}

	/**
	 * Current front-end URL.
	 */
	private static function get_current_url() {
		global $wp;
		if ( isset( $wp->request ) ) {
			return home_url( add_query_arg( array(), $wp->request ) );
		}

		return home_url( '/' );
	}

	/**
	 * JSON-LD: Organization, WebSite, BreadcrumbList, FAQPage (FAQ), Product enhancements.
	 */
	public static function render_json_ld() {
		if ( is_admin() || is_feed() ) {
			return;
		}

		$graphs = array();
		$defer_schema_to_rank_math = self::rank_math_handles_head_seo();

		if ( is_front_page() && ! $defer_schema_to_rank_math ) {
			$graphs[] = array(
				'@context' => 'https://schema.org',
				'@type'    => 'Organization',
				'name'     => get_bloginfo( 'name' ),
				'url'      => home_url( '/' ),
				'logo'     => self::get_og_image_url(),
			);
			$graphs[] = array(
				'@context'        => 'https://schema.org',
				'@type'           => 'WebSite',
				'name'            => get_bloginfo( 'name' ),
				'url'             => home_url( '/' ),
				'potentialAction' => array(
					'@type'       => 'SearchAction',
					'target'      => home_url( '/?s={search_term_string}' ),
					'query-input' => 'required name=search_term_string',
				),
			);
		}

		if ( ! $defer_schema_to_rank_math ) {
			$breadcrumb = self::get_breadcrumb_schema();
			if ( $breadcrumb ) {
				$graphs[] = $breadcrumb;
			}
		}

		if ( is_page( 'faq' ) ) {
			$faq = self::get_faq_schema_from_page();
			if ( $faq ) {
				$graphs[] = $faq;
			}
		}

		if ( empty( $graphs ) ) {
			return;
		}

		foreach ( $graphs as $graph ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
		}
	}

	/**
	 * BreadcrumbList from WordPress / WooCommerce context.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function get_breadcrumb_schema() {
		if ( is_front_page() ) {
			return null;
		}

		$items = array(
			array(
				'@type'    => 'ListItem',
				'position' => 1,
				'name'     => __( 'Home', 'biopentra-storefront' ),
				'item'     => home_url( '/' ),
			),
		);

		if ( function_exists( 'is_shop' ) && is_shop() ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => __( 'Shop', 'biopentra-storefront' ),
				'item'     => wc_get_page_permalink( 'shop' ),
			);
		} elseif ( is_singular( 'product' ) ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => __( 'Shop', 'biopentra-storefront' ),
				'item'     => wc_get_page_permalink( 'shop' ),
			);
			$terms = get_the_terms( get_queried_object_id(), 'product_cat' );
			if ( is_array( $terms ) && ! empty( $terms ) ) {
				$term = $terms[0];
				if ( 'uncategorized' !== $term->slug ) {
					$items[] = array(
						'@type'    => 'ListItem',
						'position' => 3,
						'name'     => $term->name,
						'item'     => get_term_link( $term ),
					);
				}
			}
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => count( $items ) + 1,
				'name'     => get_the_title(),
				'item'     => get_permalink(),
			);
		} elseif ( is_singular() ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => get_the_title(),
				'item'     => get_permalink(),
			);
		} elseif ( is_tax( 'product_cat' ) ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => 2,
					'name'     => __( 'Shop', 'biopentra-storefront' ),
					'item'     => wc_get_page_permalink( 'shop' ),
				);
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => 3,
					'name'     => $term->name,
					'item'     => get_term_link( $term ),
				);
			}
		} else {
			return null;
		}

		return array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);
	}

	/**
	 * Remove per-widget Elementor FAQPage JSON-LD on the FAQ page (consolidated in render_json_ld).
	 *
	 * @param string                  $content Widget HTML.
	 * @param \Elementor\Widget_Base $widget  Elementor widget instance.
	 */
	public static function strip_elementor_faq_schema_on_faq_page( $content, $widget ) {
		if ( ! is_page( 'faq' ) || ! is_string( $content ) || '' === $content ) {
			return $content;
		}

		if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) ) {
			return $content;
		}

		if ( ! in_array( $widget->get_name(), array( 'accordion', 'toggle', 'nested-accordion' ), true ) ) {
			return $content;
		}

		return (string) preg_replace(
			'#<script\s+type=["\']application/ld\+json["\'][^>]*>.*?"@type"\s*:\s*"FAQPage".*?</script>#is',
			'',
			$content
		);
	}

	/**
	 * Parse FAQ page Elementor accordions (or headings) into one FAQPage schema.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function get_faq_schema_from_page() {
		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return null;
		}

		$entities = self::collect_faq_entities_from_elementor_data( (int) $post->ID );
		if ( count( $entities ) < 2 ) {
			$entities = self::collect_faq_entities_from_post_content( (string) $post->post_content );
		}

		if ( count( $entities ) < 2 ) {
			return null;
		}

		return array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		);
	}

	/**
	 * @param int $post_id Page post ID.
	 * @return array<int, array<string, mixed>>
	 */
	private static function collect_faq_entities_from_elementor_data( $post_id ) {
		$raw = get_post_meta( $post_id, '_elementor_data', true );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return array();
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return array();
		}

		$entities = array();
		self::walk_elementor_faq_nodes( $data, $entities );

		return $entities;
	}

	/**
	 * @param array<int|string, mixed>        $nodes    Elementor document nodes.
	 * @param array<int, array<string, mixed>> $entities FAQ Question nodes (by reference).
	 */
	private static function walk_elementor_faq_nodes( $nodes, array &$entities ) {
		if ( ! is_array( $nodes ) ) {
			return;
		}

		foreach ( $nodes as $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}

			$widget_type = isset( $node['widgetType'] ) ? (string) $node['widgetType'] : '';
			$settings    = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();

			if ( in_array( $widget_type, array( 'accordion', 'toggle' ), true ) ) {
				$tabs = isset( $settings['tabs'] ) && is_array( $settings['tabs'] ) ? $settings['tabs'] : array();
				foreach ( $tabs as $tab ) {
					if ( ! is_array( $tab ) ) {
						continue;
					}
					$entity = self::build_faq_entity(
						isset( $tab['tab_title'] ) ? (string) $tab['tab_title'] : '',
						isset( $tab['tab_content'] ) ? (string) $tab['tab_content'] : ''
					);
					if ( $entity ) {
						$entities[] = $entity;
					}
				}
			} elseif ( 'nested-accordion' === $widget_type ) {
				$items = isset( $settings['items'] ) && is_array( $settings['items'] ) ? $settings['items'] : array();
				foreach ( $items as $index => $item ) {
					if ( ! is_array( $item ) ) {
						continue;
					}
					$answer = self::collect_nested_accordion_item_content( $node, $index );
					$entity = self::build_faq_entity(
						isset( $item['item_title'] ) ? (string) $item['item_title'] : '',
						$answer
					);
					if ( $entity ) {
						$entities[] = $entity;
					}
				}
			}

			if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
				self::walk_elementor_faq_nodes( $node['elements'], $entities );
			}
		}
	}

	/**
	 * @param array<string, mixed> $node  Nested accordion widget node.
	 * @param int                  $index Item index.
	 */
	private static function collect_nested_accordion_item_content( array $node, $index ) {
		$elements = isset( $node['elements'] ) && is_array( $node['elements'] ) ? $node['elements'] : array();
		if ( ! isset( $elements[ $index ] ) || ! is_array( $elements[ $index ] ) ) {
			return '';
		}

		$parts = array();
		self::collect_elementor_text_content( $elements[ $index ], $parts );

		return implode( ' ', $parts );
	}

	/**
	 * @param array<string, mixed> $node Elementor node.
	 * @param string[]             $parts Text fragments (by reference).
	 */
	private static function collect_elementor_text_content( array $node, array &$parts ) {
		$settings = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();

		foreach ( array( 'editor', 'text', 'description', 'tab_content', 'html' ) as $key ) {
			if ( ! empty( $settings[ $key ] ) && is_string( $settings[ $key ] ) ) {
				$parts[] = $settings[ $key ];
			}
		}

		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			foreach ( $node['elements'] as $child ) {
				if ( is_array( $child ) ) {
					self::collect_elementor_text_content( $child, $parts );
				}
			}
		}
	}

	/**
	 * @param string $content Post content HTML fallback.
	 * @return array<int, array<string, mixed>>
	 */
	private static function collect_faq_entities_from_post_content( $content ) {
		if ( '' === $content ) {
			return array();
		}

		$main = $content;
		if ( preg_match( '/<main[^>]*>(.*)<\/main>/is', $content, $m ) ) {
			$main = $m[1];
		}

		$entities = array();
		if ( preg_match_all( '/<h[2-4][^>]*>(.*?)<\/h[2-4]>\s*(.*?)(?=<h[2-4]|$)/is', $main, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$entity = self::build_faq_entity( $match[1], $match[2], true );
				if ( $entity ) {
					$entities[] = $entity;
				}
			}
		}

		return $entities;
	}

	/**
	 * @param string $question Question text (may contain HTML).
	 * @param string $answer   Answer text (may contain HTML).
	 * @param bool   $plain_answer When true, strip tags from the answer body.
	 * @return array<string, mixed>|null
	 */
	private static function build_faq_entity( $question, $answer, $plain_answer = false ) {
		$question = trim( wp_strip_all_tags( html_entity_decode( (string) $question, ENT_QUOTES, 'UTF-8' ) ) );
		$answer   = (string) $answer;

		if ( $plain_answer ) {
			$answer = self::trim_description( wp_strip_all_tags( html_entity_decode( $answer, ENT_QUOTES, 'UTF-8' ) ) );
		} else {
			$answer = self::format_faq_answer_text( $answer );
		}

		if ( strlen( $question ) < 8 || strlen( wp_strip_all_tags( $answer ) ) < 16 ) {
			return null;
		}

		return array(
			'@type'          => 'Question',
			'name'           => $question,
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $answer,
			),
		);
	}

	/**
	 * @param string $html FAQ answer HTML from Elementor.
	 */
	private static function format_faq_answer_text( $html ) {
		$html = do_shortcode( (string) $html );
		$html = wptexturize( $html );

		return wp_kses_post( $html );
	}

	/**
	 * @param array<string, string> $attr       Image attributes.
	 * @param WP_Post               $attachment Attachment post.
	 * @param string|int[]          $size       Image size.
	 * @return array<string, string>
	 */
	public static function attachment_alt_fallback( $attr, $attachment, $size ) {
		unset( $size );

		if ( ! empty( $attr['alt'] ) || ! $attachment instanceof WP_Post ) {
			return $attr;
		}

		$alt = trim( (string) get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ) );
		if ( '' !== $alt ) {
			$attr['alt'] = $alt;
			return $attr;
		}

		$title = get_the_title( $attachment );
		if ( $title ) {
			$attr['alt'] = $title;
		}

		return $attr;
	}
}
