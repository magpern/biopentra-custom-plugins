<?php
/**
 * BioPentra mini-cart — Elementor side-cart layout + Blocksy/WC compatibility.
 *
 * @package Biopentra_Storefront
 * @version 10.0.0
 */

defined( 'ABSPATH' ) || exit;

$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

if ( ! function_exists( 'biopentra_mini_cart_drawer_render_item' ) ) {
	/**
	 * @param string $cart_item_key Key.
	 * @param array  $cart_item     Item.
	 * @return void
	 */
	function biopentra_mini_cart_drawer_render_item( $cart_item_key, $cart_item ) {
		$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
		if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
			return;
		}

		$product_id        = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
		$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
		$thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
		$product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
		$line_price        = apply_filters(
			'woocommerce_cart_item_subtotal',
			WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ),
			$cart_item,
			$cart_item_key
		);
		$subtitle          = function_exists( 'biopentra_mini_cart_drawer_row_subtitle' )
			? biopentra_mini_cart_drawer_row_subtitle( $cart_item, $_product )
			: '';
		?>
		<div class="bp-mini-cart-row elementor-menu-cart__product woocommerce-mini-cart-item mini_cart_item woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">

			<div class="bp-mini-cart-row__media elementor-menu-cart__product-image product-thumbnail">
				<div class="bp-mini-cart-row__thumb-wrap">
					<?php
					if ( ! $product_permalink ) {
						echo '<div class="bp-mini-cart-thumb">' . wp_kses_post( $thumbnail ) . '</div>';
					} else {
						printf( '<a href="%s" class="bp-mini-cart-thumb">%s</a>', esc_url( $product_permalink ), wp_kses_post( $thumbnail ) );
					}
					?>
					<div class="bp-mini-cart-row__remove product-remove elementor-menu-cart__product-remove">
						<?php
						foreach ( array( 'elementor_remove_from_cart_button', 'remove_from_cart_button' ) as $remove_class ) {
							echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								'woocommerce_cart_item_remove_link',
								sprintf(
									'<a href="%s" class="%s" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s"></a>',
									esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
									esc_attr( $remove_class ),
									esc_attr__( 'Remove this item', 'woocommerce' ),
									esc_attr( (string) $product_id ),
									esc_attr( $cart_item_key ),
									esc_attr( $_product->get_sku() )
								),
								$cart_item_key
							);
						}
						?>
					</div>
				</div>
			</div>

			<div class="bp-mini-cart-row__content">
				<div class="bp-mini-cart-row__copy elementor-menu-cart__product-name product-name product-data" data-title="<?php esc_attr_e( 'Product', 'woocommerce' ); ?>">
					<?php
					if ( ! $product_permalink ) {
						echo '<span class="product-title bp-mini-cart-row__title">' . wp_kses_post( $product_name ) . '</span>';
					} else {
						printf(
							'<a href="%s" class="product-title bp-mini-cart-row__title">%s</a>',
							esc_url( $product_permalink ),
							wp_kses_post( $product_name )
						);
					}
					echo $subtitle; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
					<div class="bp-mini-cart-row__price elementor-menu-cart__product-price product-price" data-title="<?php esc_attr_e( 'Price', 'woocommerce' ); ?>">
						<?php echo wp_kses_post( $line_price ); ?>
					</div>
				</div>

				<div class="bp-mini-cart-row__aside ct-product-actions" data-title="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>">
					<?php
					echo apply_filters( 'woocommerce_widget_cart_item_quantity', '', $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>
			</div>
		</div>
		<?php
	}
}

do_action( 'woocommerce_before_mini_cart' );

$cart_items = ( WC()->cart ) ? WC()->cart->get_cart() : array();

if ( empty( $cart_items ) ) :
	?>
	<div class="bp-mini-cart-empty woocommerce-mini-cart__empty-message" role="status">
		<p class="bp-mini-cart-empty__title"><?php esc_html_e( 'Your cart is empty', 'biopentra-storefront' ); ?></p>
		<p class="bp-mini-cart-empty__text"><?php esc_html_e( 'Add research materials to your cart to continue.', 'biopentra-storefront' ); ?></p>
		<a class="bp-mini-cart-empty__cta elementor-button" href="<?php echo esc_url( $shop_url ); ?>">
			<span class="elementor-button-text"><?php esc_html_e( 'Browse products', 'biopentra-storefront' ); ?></span>
		</a>
	</div>
	<?php
else :
	?>
	<div class="bp-mini-cart-body">
		<div class="elementor-menu-cart__products woocommerce-mini-cart cart_list product_list_widget cart woocommerce-cart-form__contents">
			<?php
			do_action( 'woocommerce_before_mini_cart_contents' );
			foreach ( $cart_items as $cart_item_key => $cart_item ) {
				biopentra_mini_cart_drawer_render_item( $cart_item_key, $cart_item );
			}
			do_action( 'woocommerce_mini_cart_contents' );
			?>
		</div>

		<div class="bp-mini-cart-sticky" aria-label="<?php esc_attr_e( 'Cart summary', 'biopentra-storefront' ); ?>">
			<div class="elementor-menu-cart__subtotal woocommerce-mini-cart__total total">
				<span class="bp-mini-cart-subtotal__label"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></span>
				<span class="bp-mini-cart-subtotal__amount"><?php echo WC()->cart->get_cart_subtotal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</div>

			<p class="bp-mini-cart-trust">
				<span class="bp-mini-cart-trust__item"><?php esc_html_e( 'Secure checkout', 'biopentra-storefront' ); ?></span>
				<span class="bp-mini-cart-trust__sep" aria-hidden="true"></span>
				<span class="bp-mini-cart-trust__item"><?php esc_html_e( 'EU fulfillment', 'biopentra-storefront' ); ?></span>
			</p>

			<?php do_action( 'woocommerce_widget_shopping_cart_before_buttons' ); ?>

			<div class="elementor-menu-cart__footer-buttons woocommerce-mini-cart__buttons buttons">
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="elementor-button elementor-button--view-cart elementor-size-md wc-forward">
					<span class="elementor-button-text"><?php esc_html_e( 'View cart', 'woocommerce' ); ?></span>
				</a>
				<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="elementor-button elementor-button--checkout elementor-size-md checkout wc-forward">
					<span class="elementor-button-text"><?php esc_html_e( 'Checkout', 'woocommerce' ); ?></span>
				</a>
			</div>

			<?php do_action( 'woocommerce_widget_shopping_cart_after_buttons' ); ?>
		</div>
	</div>
	<?php
endif;

do_action( 'woocommerce_after_mini_cart' );
