<?php
/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.8.0
 */

defined( 'ABSPATH' ) || exit; ?>

<form class="c-cart" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
	<?php do_action( 'woocommerce_before_cart_table' ); ?>
	<table class="c-cart__table c-table--responsive" cellspacing="0">
		<thead>
			<tr>
				<th>&nbsp;</th>
				<th><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Price', 'woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Quantity', 'woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<?php do_action( 'woocommerce_before_cart_contents' ); ?>
			<?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) : ?>
				<?php
					$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
					$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
				?>
				<?php if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) : ?>
					<?php $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key ); ?>
					<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'c-cart__item', $cart_item, $cart_item_key ) ); ?>">
						<td data-label="<?php echo $_product->get_name(); ?>" class="js-toggle-parent">
							<?php $thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key ); ?>
							<?php if ( ! $product_permalink ) : ?>
								<?php echo $thumbnail; ?>
							<?php else: ?>
								<?php printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail ); ?>
							<?php endif; ?>
						</td>
						<td data-label="<?php esc_attr_e( 'Product', 'woocommerce' ); ?>">
							<div>
								<?php if ( ! $product_permalink ) : ?>
									<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) . '&nbsp;' ); ?>
								<?php else: ?>
									<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ), $cart_item, $cart_item_key ) ); ?>
								<?php endif; ?>

								<?php do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key ); ?>

								<!-- Meta data. -->
								<?php echo wc_get_formatted_cart_item_data( $cart_item ); ?>

								<!-- Backorder notification. -->
								<?php if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) : ?>
									<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'woocommerce' ) . '</p>', $product_id ) ); ?>
								<?php endif; ?>
							</div>
						</td>
						<td data-label="<?php esc_attr_e( 'Price', 'woocommerce' ); ?>">
							<?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); ?>
						</td>
						<td data-label="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>">
							<?php if ( $_product->is_sold_individually() ) : ?>
								<?php $product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key ); ?>
							<?php else: ?>
								<?php
									$product_quantity = woocommerce_quantity_input(
										array(
											'input_name'   => "cart[{$cart_item_key}][qty]",
											'input_value'  => $cart_item['quantity'],
											'max_value'    => $_product->get_max_purchase_quantity(),
											'min_value'    => '0',
											'product_name' => $_product->get_name(),
										), $_product, false
									);
								?>
							<?php endif; ?>
							<?php echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); ?>
						</td>
						<td data-label="<?php esc_attr_e( 'Subtotal', 'woocommerce' ); ?>">
							<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); ?>
						</td>
						<td>
							<?php
								echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									'woocommerce_cart_item_remove_link',
									sprintf(
										'<a href="%s" class="o-link" aria-label="%s" data-product_id="%s" data-product_sku="%s">Remove</a>',
										esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
										esc_html__( 'Remove this item', 'woocommerce' ),
										esc_attr( $product_id ),
										esc_attr( $_product->get_sku() )
									),
									$cart_item_key
								);
							?>
						</td>
					</tr>
				<?php endif; ?>
			<?php endforeach; ?>
			<?php do_action( 'woocommerce_cart_contents' ); ?>
			<?php do_action( 'woocommerce_after_cart_contents' ); ?>
		</tbody>
	</table>

	<div class="c-cart__info" bg="grid">
		<div class="c-cart__coupons u-spacing">
			<?php if ( wc_coupons_enabled() ) : ?>
				<div class="c-coupon c-form--inline">
					<label for="coupon_code" class="o-form__item-label is-vishidden"><?php esc_html_e( 'Coupon:', 'woocommerce' ); ?></label>
					<input type="text" name="coupon_code" class="o-form__item-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" />
					<button type="submit" class="o-button" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>"><?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?></button>
					<?php do_action( 'woocommerce_cart_coupon' ); ?>
				</div>
				<button type="submit" class="o-link" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>"><?php esc_html_e( 'Update cart', 'woocommerce' ); ?></button>
			<?php endif; ?>

			<?php do_action( 'woocommerce_cart_actions' ); ?>
			<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
		</div>
		<div class="c-cart__collaterals u-spacing">
			<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>
			<div class="c-cart-collaterals">
				<?php do_action( 'woocommerce_cart_collaterals' ); ?>
			</div>
			<?php do_action( 'woocommerce_after_cart' ); ?>
		</div>
	</div>
	<?php do_action( 'woocommerce_after_cart_table' ); ?>
</form>

