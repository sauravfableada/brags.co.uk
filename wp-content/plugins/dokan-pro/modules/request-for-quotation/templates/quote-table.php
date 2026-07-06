<?php
defined( 'ABSPATH' ) || exit;

$colspan = 4;
$colspan = $colspan + 2;
$colspan = $colspan + 2;

do_action( 'dokan_before_quote_table' ); ?>

<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents dokan_quote_table_contents dokan-table order-items" cellspacing="0">
    <thead>
    <tr>
        <th class="product-thumbnail">&nbsp;</th>
        <th colspan="<?php echo esc_attr( $hide_price ? 4 : 1 ); ?>" class="product-name<?php echo esc_attr( $hide_price ? ' hide-product-price' : '' ); ?>">
            <?php esc_html_e( 'Product', 'dokan' ); ?>
        </th>
        <?php if ( ! $hide_price ) : ?>
            <th class="product-price"><?php esc_html_e( 'Price', 'dokan' ); ?></th>
            <th class="product-price"><?php esc_html_e( 'Offered Price', 'dokan' ); ?></th>
        <?php endif; ?>
        <th class="product-quantity"><?php esc_html_e( 'Quantity', 'dokan' ); ?></th>
        <th class="product-remove">&nbsp;</th>
    </tr>
    </thead>
    <tbody>
    <?php do_action( 'dokan_before_quote_contents' ); ?>

    <?php
    foreach ( $quotes as $quote_item_key => $quote_item ) {
        if ( ! isset( $quote_item['data'] ) || ! is_object( $quote_item['data'] ) ) {
            continue;
        }

        $_product      = $quote_item['data'];
        $product_id    = $quote_item['product_id'];
        $price         = empty( $quote_item['addons_price'] ) ? $_product->get_price( 'edit' ) : $quote_item['addons_price'];
        $offered_price = isset( $quote_item['offered_price'] ) ? floatval( $quote_item['offered_price'] ) : $price;

        if ( $_product && $_product->exists() && $quote_item['quantity'] > 0 ) {
            $product_permalink = $_product->is_visible() ? $_product->get_permalink( $quote_item ) : '';
            ?>
            <tr class="woocommerce-cart-form__quote-item <?php echo esc_attr( $quote_item['product_id'] ); ?>">
                <td class="product-thumbnail">
                    <?php
                    $thumbnail = $_product->get_image();

                    if ( ! $product_permalink ) {
                        echo wp_kses_post( $thumbnail ); // phpcs:ignore WordPress.Security.EscapeOutput
                    } else {
                        printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), wp_kses_post( $thumbnail ) ); // phpcs:ignore WordPress.Security.EscapeOutput
                    }
                    ?>
                </td>
                <td colspan="<?php echo esc_attr( $hide_price ? 4 : 1 ); ?>" class="product-name<?php echo esc_attr( $hide_price ? ' hide-product-price' : '' ); ?>" data-title="<?php esc_attr_e( 'Product', 'dokan' ); ?>">
                    <?php
                    if ( ! $product_permalink ) {
                        echo wp_kses_post( $_product->get_name() . '&nbsp;' );
                    } else {
                        echo wp_kses_post( sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ) );
                    }

                    if ( $_product->is_type( 'variation' ) ) {
                        $quote_item['variation'] = $_product->get_formatted_name();
                    }
                    // Meta data.
                    echo wp_kses_post( wc_get_formatted_cart_item_data( $quote_item ) ); // phpcs:ignore WordPress.Security.EscapeOutput

                    // Backorder notification.
                    if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $quote_item['quantity'] ) ) {
                        echo wp_kses_post( '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'dokan' ) . '</p>' );
                    }

                    if ( $_product->get_sku() ) {
                        echo wp_kses_post( sprintf( '<p><small>SKU:%s</small></p>', esc_attr( $_product->get_sku() ) ) );
                    }
                    ?>
                </td>
                <?php if ( ! $hide_price ) : ?>
                    <td class="product-price" data-title="<?php esc_attr_e( 'Price', 'dokan' ); ?>">
                        <?php
                        $args['qty']   = 1;
                        $args['price'] = empty( $quote_item['addons_price'] ) ? $_product->get_price( 'edit' ) : $quote_item['addons_price'];
                        echo wp_kses_post( wc_price( $args['price'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput
                        ?>
                    </td>
                    <td class="product-price offered-price" data-title="<?php esc_attr_e( 'Offered Price', 'dokan' ); ?>">
                        <input type="number" class="input-text offered-price-input text" step="any" name="offered_price[<?php echo esc_attr( $quote_item_key ); ?>]" value="<?php echo esc_attr( $offered_price ); ?>">
                    </td>
                <?php endif; ?>
                <td colspan="1" class="product-quantity" data-title="<?php esc_attr_e( 'Quantity', 'dokan' ); ?>">
                    <?php
                    if ( $_product->is_sold_individually() ) {
                        $product_quantity = sprintf( '<input type="hidden" class="qty" name="quote_qty[%s]" value="1" />', $quote_item_key );
                    } else {
                        woocommerce_quantity_input(
                            [
                                'input_name'   => "quote_qty[{$quote_item_key}]",
                                'input_value'  => $quote_item['quantity'],
                                'max_value'    => $_product->get_max_purchase_quantity(),
                                'min_value'    => '0',
                                'product_name' => $_product->get_name(),
                            ],
                            $_product,
                            true
                        );
                    }
                    ?>
                </td>
                <td class="product-remove">
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo wp_kses_post(
                        sprintf(
                            /* translators: 1) Quote item key, 2) Aria label, 3) Product id, 4) Product sku, 5) Hide price rule */
                            '<a href="%1$s" class="remove remove-cart-item remove-dokan-quote-item" aria-label="%2$s" data-cart_item_key="%1$s" data-product_id="%3$s" data-product_sku="%4$s" data-hide_price="%5$s">&times;</a>',
                            esc_attr( $quote_item_key ),
                            esc_html__( 'Remove this item', 'dokan' ),
                            esc_attr( $product_id ),
                            esc_attr( $_product->get_sku() ),
                            esc_attr( $hide_price ),
                        )
                    );
                    ?>
                </td>
            </tr>
            <?php
        }
    }
    ?>
    </tbody>
</table>

