<?php
/**
 * V1A Design Specimen — reversible trial visual system on the homepage.
 *
 * Scope: fonts, colors, geometry demos + card-preview only.
 * Not: SDS v2 freeze, V2 header, template 3608 migration, tags/releases.
 *
 * Disable: add_filter( 'biopentra_v1a_specimen_enabled', '__return_false' );
 * Or remove this file from class-biopentra-storefront.php require.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the V1A specimen is active.
 *
 * @return bool
 */
function biopentra_v1a_specimen_enabled() {
	return (bool) apply_filters( 'biopentra_v1a_specimen_enabled', true );
}

/**
 * Front-page only specimen context.
 *
 * @return bool
 */
function biopentra_v1a_specimen_should_render() {
	return biopentra_v1a_specimen_enabled() && is_front_page();
}

/**
 * Asset version for cache bust without bumping plugin release version.
 *
 * @param string $relative Path under plugin root.
 * @return string
 */
function biopentra_v1a_asset_ver( $relative ) {
	$path = BIOPENTRA_STOREFRONT_PATH . ltrim( $relative, '/' );
	return is_readable( $path ) ? (string) filemtime( $path ) : BIOPENTRA_STOREFRONT_VERSION;
}

/**
 * Enqueue V1A fonts + specimen CSS on the homepage only.
 */
function biopentra_v1a_specimen_enqueue() {
	if ( ! biopentra_v1a_specimen_should_render() ) {
		return;
	}

	wp_enqueue_style(
		'biopentra-v1a-fonts',
		BIOPENTRA_STOREFRONT_URL . 'assets/css/bp-v1a-fonts.css',
		array(),
		biopentra_v1a_asset_ver( 'assets/css/bp-v1a-fonts.css' )
	);

	wp_enqueue_style(
		'biopentra-v1a-specimen',
		BIOPENTRA_STOREFRONT_URL . 'assets/css/bp-v1a-specimen.css',
		array( 'biopentra-v1a-fonts', 'biopentra-bp-tokens' ),
		biopentra_v1a_asset_ver( 'assets/css/bp-v1a-specimen.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'biopentra_v1a_specimen_enqueue', 15 );

/**
 * Preload critical woff2 to reduce FOIT/CLS.
 */
function biopentra_v1a_specimen_preload_fonts() {
	if ( ! biopentra_v1a_specimen_should_render() ) {
		return;
	}

	$preload = array(
		'assets/fonts/barlow/barlow-latin-400-normal.woff2',
		'assets/fonts/barlow-condensed/barlow-condensed-latin-700-normal.woff2',
	);
	foreach ( $preload as $rel ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( BIOPENTRA_STOREFRONT_URL . $rel )
		);
	}
}
add_action( 'wp_head', 'biopentra_v1a_specimen_preload_fonts', 4 );

/**
 * Body class for scoped trial chrome.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function biopentra_v1a_specimen_body_class( $classes ) {
	if ( biopentra_v1a_specimen_should_render() ) {
		$classes[] = 'bp-v1a-trial';
	}
	return $classes;
}
add_filter( 'body_class', 'biopentra_v1a_specimen_body_class' );

/**
 * Optional product image URL for the card preview (real catalog when available).
 *
 * @return string Empty string if none.
 */
function biopentra_v1a_specimen_preview_image_url() {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return '';
	}

	$products = wc_get_products(
		array(
			'status' => 'publish',
			'limit'  => 8,
			'orderby'=> 'date',
			'order'  => 'DESC',
			'return' => 'objects',
		)
	);

	foreach ( $products as $product ) {
		if ( ! $product instanceof WC_Product ) {
			continue;
		}
		$name = $product->get_name();
		// Prefer a real catalog SKU over obvious fixtures for visual QA.
		if ( preg_match( '/(dogfood|smoke|acceptance|fixture|m\d{1,2}[- ])/i', $name ) ) {
			continue;
		}
		$id = $product->get_image_id();
		if ( $id ) {
			$url = wp_get_attachment_image_url( (int) $id, 'woocommerce_thumbnail' );
			if ( $url ) {
				return $url;
			}
		}
	}

	return '';
}

/**
 * Markup for the V1A specimen panel.
 *
 * @return string
 */
function biopentra_v1a_specimen_html() {
	$img = biopentra_v1a_specimen_preview_image_url();

	ob_start();
	?>
	<aside class="bp-v1a-specimen" id="bp-v1a-specimen" aria-label="<?php echo esc_attr__( 'V1A design specimen', 'biopentra-storefront' ); ?>">
		<p class="bp-v1a-specimen__banner">
			<?php echo esc_html__( 'V1A Design Specimen — trial visual system for Product Owner review (not a release; SDS v2 not frozen).', 'biopentra-storefront' ); ?>
		</p>
		<div class="bp-v1a-specimen__grid">
			<div>
				<p class="bp-v1a-specimen__section-title"><?php echo esc_html__( 'Typography', 'biopentra-storefront' ); ?></p>
				<div class="bp-v1a-specimen__type">
					<h1><?php echo esc_html__( 'Research Peptides. Trusted EU Fulfillment.', 'biopentra-storefront' ); ?></h1>
					<h2><?php echo esc_html__( 'Featured products', 'biopentra-storefront' ); ?></h2>
					<h3><?php echo esc_html__( 'Tirzepatide', 'biopentra-storefront' ); ?></h3>
					<p><?php echo esc_html__( 'Body copy in Barlow: denser commercial rhythm with restrained steel-blue accent and neutral surfaces.', 'biopentra-storefront' ); ?></p>
					<p class="bp-v1a-specimen__meta"><?php echo esc_html__( 'Meta / supporting — COA with every batch · Discreet EU shipping', 'biopentra-storefront' ); ?></p>
					<p class="bp-v1a-specimen__price"><?php echo esc_html__( 'from € 44,99', 'biopentra-storefront' ); ?></p>
				</div>

				<p class="bp-v1a-specimen__section-title"><?php echo esc_html__( 'Controls', 'biopentra-storefront' ); ?></p>
				<div class="bp-v1a-specimen__controls" role="group" aria-label="<?php echo esc_attr__( 'Button samples', 'biopentra-storefront' ); ?>">
					<span class="bp-v1a-specimen__btn bp-v1a-specimen__btn--primary"><?php echo esc_html__( 'Shop products', 'biopentra-storefront' ); ?></span>
					<span class="bp-v1a-specimen__btn bp-v1a-specimen__btn--secondary"><?php echo esc_html__( 'All compounds', 'biopentra-storefront' ); ?></span>
				</div>
				<div class="bp-v1a-specimen__controls" role="group" aria-label="<?php echo esc_attr__( 'Category chip samples', 'biopentra-storefront' ); ?>">
					<span class="bp-v1a-specimen__chip bp-v1a-specimen__chip--active"><?php echo esc_html__( 'Growth & Performance', 'biopentra-storefront' ); ?></span>
					<span class="bp-v1a-specimen__chip"><?php echo esc_html__( 'Recovery Support', 'biopentra-storefront' ); ?></span>
				</div>
				<div class="bp-v1a-specimen__field">
					<label for="bp-v1a-specimen-search"><?php echo esc_html__( 'Search', 'biopentra-storefront' ); ?></label>
					<input
						id="bp-v1a-specimen-search"
						class="bp-v1a-specimen__input"
						type="search"
						readonly
						value=""
						placeholder="<?php echo esc_attr__( 'Search peptides', 'biopentra-storefront' ); ?>"
					/>
				</div>
			</div>
			<div>
				<p class="bp-v1a-specimen__section-title"><?php echo esc_html__( 'Product card preview', 'biopentra-storefront' ); ?></p>
				<div class="bp-v1a-card-preview">
					<div class="bp-v1a-card-preview__media">
						<?php if ( $img ) : ?>
							<img src="<?php echo esc_url( $img ); ?>" alt="" width="220" height="220" loading="eager" decoding="async" />
						<?php else : ?>
							<span><?php echo esc_html__( 'Vial photo', 'biopentra-storefront' ); ?></span>
						<?php endif; ?>
						<span class="bp-v1a-card-preview__add" aria-hidden="true">+</span>
					</div>
					<div class="bp-v1a-card-preview__body">
						<span class="bp-v1a-card-preview__badge"><?php echo esc_html__( 'In stock', 'biopentra-storefront' ); ?></span>
						<p class="bp-v1a-card-preview__title"><?php echo esc_html__( 'Tirzepatide', 'biopentra-storefront' ); ?></p>
						<p class="bp-v1a-card-preview__price"><?php echo esc_html__( 'from € 44,99', 'biopentra-storefront' ); ?></p>
					</div>
				</div>
				<p class="bp-v1a-specimen__note">
					<?php echo esc_html__( 'Preview only — not template 3608 / loop-card migration. No blueprint marks. No product duotone. Nav contract unchanged (≤1024 collapsed, >1024 horizontal).', 'biopentra-storefront' ); ?>
				</p>
			</div>
		</div>
	</aside>
	<?php
	return (string) ob_get_clean();
}

/**
 * Print specimen after Elementor Theme Builder header on the homepage.
 *
 * Elementor Pro fires `elementor/theme/after_do_{$location}` (e.g. after_do_header).
 */
function biopentra_v1a_specimen_after_header() {
	if ( ! biopentra_v1a_specimen_should_render() ) {
		return;
	}
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with escaping in biopentra_v1a_specimen_html().
	echo biopentra_v1a_specimen_html();
}
add_action( 'elementor/theme/after_do_header', 'biopentra_v1a_specimen_after_header', 10 );
