<?php
/**
 * BioPentra technical SEO audit (WP-CLI: wp eval-file wp-content/plugins/biopentra-storefront/scripts/seo-audit.php).
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$base = trailingslashit( home_url() );

$paths = array(
	'/',
	'/shop/',
	'/faq/',
	'/cart-2/',
	'/checkout-2/',
	'/my-account-2/',
	'?s=peptide',
	'/product-category/uncategorized/',
);

$products = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 3,
		'fields'         => 'ids',
	)
);
foreach ( $products as $pid ) {
	$paths[] = wp_make_link_relative( get_permalink( $pid ) );
}

echo "=== BioPentra SEO Audit ===\n";
echo 'blog_public: ' . get_option( 'blog_public' ) . "\n";
echo 'siteurl: ' . site_url() . "\n";
echo 'home: ' . home_url() . "\n\n";

$title_counts = array();
$missing_desc = array();

foreach ( $paths as $path ) {
	$url  = $base . ltrim( $path, '/' );
	$resp = wp_remote_get(
		$url,
		array(
			'timeout'   => 20,
			'sslverify' => false,
		)
	);
	$code = is_wp_error( $resp ) ? 0 : (int) wp_remote_retrieve_response_code( $resp );
	$body = is_wp_error( $resp ) ? '' : (string) wp_remote_retrieve_body( $resp );

	$title = 'n/a';
	if ( preg_match( '/<title>(.*?)<\/title>/is', $body, $m ) ) {
		$title = html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES, 'UTF-8' );
	}
	$robots = 'default';
	if ( preg_match( '/name=[\'"]robots[\'"][^>]*content=[\'"]([^\'"]+)/i', $body, $m ) ) {
		$robots = $m[1];
	}
	$canonical = 'none';
	if ( preg_match( '/rel=[\'"]canonical[\'"][^>]*href=[\'"]([^\'"]+)/i', $body, $m ) ) {
		$canonical = $m[1];
	}
	$desc = 'MISSING';
	if ( preg_match( '/name=[\'"]description[\'"][^>]*content=[\'"]([^\'"]+)/i', $body, $m ) ) {
		$desc = substr( $m[1], 0, 80 );
	}
	$og_image = preg_match( '/property=[\'"]og:image[\'"]/i', $body ) ? 'yes' : 'no';
	$schema   = preg_match_all( '/application\/ld\+json/i', $body );

	$title_key = strtolower( trim( $title ) );
	if ( ! isset( $title_counts[ $title_key ] ) ) {
		$title_counts[ $title_key ] = array();
	}
	$title_counts[ $title_key ][] = $path;
	if ( 'MISSING' === $desc ) {
		$missing_desc[] = $path;
	}

	printf(
		"%s\n  HTTP:%d | robots:%s | canonical:%s\n  title:%s\n  desc:%s | og:image:%s | schema:%d\n\n",
		$path,
		$code,
		$robots,
		$canonical,
		substr( $title, 0, 70 ),
		$desc,
		$og_image,
		$schema
	);
}

echo "=== Duplicate titles ===\n";
foreach ( $title_counts as $t => $paths_dup ) {
	if ( count( $paths_dup ) > 1 && 'n/a' !== $t ) {
		echo "  \"$t\" => " . implode( ', ', $paths_dup ) . "\n";
	}
}

echo "\n=== Products missing excerpt ===\n";
$all_products = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);
$no_excerpt = 0;
foreach ( $all_products as $pid ) {
	if ( ! has_excerpt( $pid ) ) {
		++$no_excerpt;
	}
}
echo "  $no_excerpt / " . count( $all_products ) . " products without excerpt\n";

echo "\n=== QA pages (published) ===\n";
$qa_pages = get_posts(
	array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		's'              => 'MP CP',
	)
);
foreach ( $qa_pages as $p ) {
	echo "  {$p->ID} {$p->post_title} ({$p->post_name})\n";
}

echo "\n=== Sitemap ===\n";
echo '  WP sitemaps: ' . ( get_option( 'blog_public' ) ? 'enabled' : 'DISABLED (blog_public=0)' ) . "\n";
