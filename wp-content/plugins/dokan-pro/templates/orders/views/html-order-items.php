<?php

/**
 * Dokan Order Items Template
 *
 * @since 2.9.0
 *
 * @package Dokan
 *
 * @var \WC_Order $order Order object
 * @var string $legacy_order
 */

use WeDevs\DokanPro\Refund\ProcessAutomaticRefund;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

global $wpdb;

// Get the payment gateway
$payment_gateway = wc_get_payment_gateway_by_order( $order );

// Get line items
$line_items          = $order->get_items( apply_filters( 'woocommerce_admin_order_item_types', 'line_item' ) );
$line_items_fee      = $order->get_items( 'fee' );
$line_items_shipping = $order->get_items( 'shipping' );

if ( wc_tax_enabled() ) {
    $order_taxes         = $order->get_taxes();
    $tax_classes         = WC_Tax::get_tax_classes();
    $classes_options     = array();
    $classes_options[''] = __( 'Standard', 'dokan' );

    if ( $tax_classes ) {
        foreach ( $tax_classes as $class ) {
            $classes_options[ sanitize_title( $class ) ] = $class;
        }
    }

    // Older orders won't have line taxes so we need to handle them differently :(
    $tax_data = '';
    if ( $line_items ) {
        $check_item = current( $line_items );
        $tax_data   = maybe_unserialize( isset( $check_item['line_tax_data'] ) ? $check_item['line_tax_data'] : '' );
    } elseif ( $line_items_shipping ) {
        $check_item = current( $line_items_shipping );
        $tax_data = maybe_unserialize( isset( $check_item['taxes'] ) ? $check_item['taxes'] : '' );
    } elseif ( $line_items_fee ) {
        $check_item = current( $line_items_fee );
        $tax_data   = maybe_unserialize( isset( $check_item['line_tax_data'] ) ? $check_item['line_tax_data'] : '' );
    }

    $legacy_order     = ! empty( $order_taxes ) && empty( $tax_data ) && ! is_array( $tax_data );
    $show_tax_columns = ! $legacy_order || count( $order_taxes ) === 1;
} else {
    $legacy_order = false;
    $order_taxes  = false;
}

$has_pending_request    = dokan_pro()->refund->has_pending_request( $order->get_id() );
$pending_request_message = '';

if ( $has_pending_request ) {
    $refunds = new \WeDevs\DokanPro\Refund\Refunds(
        [
			'order_id' => $order->get_id(),
			'status'   => \WeDevs\DokanPro\Refund\Refunds::STATUS_PENDING,
			'limit'    => 1,
		]
    );
    $pending_refunds = $refunds->get_refunds();
    $pending_request_amount = isset( $pending_refunds[0] ) && is_a( $pending_refunds[0], 'WeDevs\DokanPro\Refund\Refund' ) ? $pending_refunds[0]->get_refund_amount() : 0;

    // translators: %1$s: amount
    $pending_request_message = sprintf( esc_html__( 'Refund request for %1$s submitted and is pending review.', 'dokan' ), wc_price( $pending_request_amount ) );
}
?>
<div class="woocommerce_order_items_wrapper wc-order-items-editable">
    <table cellpadding="0" cellspacing="0" class="woocommerce_order_items dokan-table dokan-table-strip">
        <thead class="dokan-show-thead">
            <tr>
                <!-- <th><input type="checkbox" class="check-column" /></th> -->
                <th class="item sortable" colspan="2" data-sort="string-ins"><?php esc_html_e( 'Item', 'dokan' ); ?></th>

                <?php do_action( 'woocommerce_admin_order_item_headers', $order ); ?>

                <th class="item_cost sortable" data-sort="float"><?php esc_html_e( 'Cost', 'dokan' ); ?></th>
                <th class="quantity sortable" data-sort="int"><?php esc_html_e( 'Qty', 'dokan' ); ?></th>
                <th class="line_cost sortable" data-sort="float"><?php esc_html_e( 'Total', 'dokan' ); ?></th>

                <?php
                if ( empty( $legacy_order ) && ! empty( $order_taxes ) ) :
                    foreach ( $order_taxes as $tax_id => $tax_item ) :
                        $tax_class      = wc_get_tax_class_by_tax_id( $tax_item['rate_id'] );
                        $tax_class_name = isset( $classes_options[ $tax_class ] ) ? $classes_options[ $tax_class ] : __( 'Tax', 'dokan' );
                        $column_label   = ! empty( $tax_item['label'] ) ? $tax_item['label'] : __( 'Tax', 'dokan' );
                        ?>
                            <th class="line_tax tips" data-tip="
                            <?php
                                echo esc_attr( $tax_item['name'] . ' (' . $tax_class_name . ')' );
                            ?>
                                ">
                            <?php echo esc_attr( $column_label ); ?>
                                <input type="hidden" class="order-tax-id" name="order_taxes[<?php echo $tax_id; ?>]" value="<?php echo esc_attr( $tax_item['rate_id'] ); ?>">
                                <a class="delete-order-tax" href="#" data-rate_id="<?php echo $tax_id; ?>"></a>
                            </th>
                        <?php
                    endforeach;
                endif;
                ?>

                <?php if ( $order->is_editable() ) : ?>
                    <th class="wc-order-edit-line-item" width="1%">&nbsp;</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody id="order_line_items">
        <?php
        foreach ( $line_items as $item_id => $item ) {
            $_product = $item->get_product();

            dokan_get_template_part(
                'orders/views/html-order-item', '', array(
                    'pro'          => true,
                    'order'        => $order,
                    'legacy_order' => $legacy_order,
                    'order_taxes'  => $order_taxes,
                    'item'         => $item,
                    'item_id'      => $item_id,
                    '_product'     => $_product,
                )
            );

            do_action( 'woocommerce_order_item_' . $item['type'] . '_html', $item_id, $item, $order );
        }
        ?>
        </tbody>
        <tbody id="order_shipping_line_items">
        <?php
            $shipping_methods = WC()->shipping() ? WC()->shipping->load_shipping_methods() : array();
        foreach ( $line_items_shipping as $item_id => $item ) {
            dokan_get_template_part(
                'orders/views/html-order-shipping', '', array(
                    'pro'              => true,
                    'item'             => $item,
                    'item_id'          => $item_id,
                    'order'            => $order,
                    'legacy_order'     => $legacy_order,
                    'order_taxes'      => $order_taxes,
                    'shipping_methods' => $shipping_methods,
                )
            );
        }
        ?>
        </tbody>
        <tbody id="order_fee_line_items">
        <?php
        foreach ( $line_items_fee as $item_id => $item ) {
            dokan_get_template_part(
                'orders/views/html-order-fee', '', array(
                    'pro'          => true,
                    'item'         => $item,
                    'item_id'      => $item_id,
                    'order'        => $order,
                    'order_taxes'  => $order_taxes,
                    'legacy_order' => $legacy_order,
                )
            );
        }
        ?>
        </tbody>
        <tbody id="order_refunds">
        <?php
        $refunds = $order->get_refunds();
        if ( $refunds ) {
            foreach ( $refunds as $refund ) {
                dokan_get_template_part(
                    'orders/views/html-order-refund', '', array(
                        'pro'          => true,
                        'refund'       => $refund,
                        'order_taxes'  => $order_taxes,
                        'legacy_order' => $legacy_order,
                    )
                );
            }
        }
        ?>
        </tbody>
    </table>
</div>
<div class="wc-order-data-row wc-order-totals-items wc-order-items-editable">
    <?php
    $coupons = $order->get_items( 'coupon' );

    if ( $coupons ) {
		?>
        <div class="wc-used-coupons">
            <ul class="wc_coupon_list">
            <?php
            echo '<li><strong>' . __( 'Coupon(s) Used', 'dokan' ) . '</strong></li>';
            foreach ( $coupons as $item_id => $item ) {
                $coupon = new WC_Coupon( $item->get_name() );

                $item_link = '#';

                $vendor_coupon = $coupon->get_id() ? add_query_arg(
                    array(
                        'post' => $coupon->get_id(),
                        'view' => 'add_coupons',
                        'action' => 'edit',
                    ), dokan_get_navigation_url( 'coupons' )
                ) : dokan_get_navigation_url( 'coupons' );

                $marketplace_coupon = $coupon->get_id() ? add_query_arg(
                    array(
                        'coupons_type' => 'marketplace_coupons',
                    ), dokan_get_navigation_url( 'coupons' )
                ) : dokan_get_navigation_url( 'coupons' );

                $item_link = dokan_is_coupon_created_by_admin_for_vendor( $coupon ) ? $marketplace_coupon : $vendor_coupon;
                $item_link = apply_filters( 'dokan_pro_dashboard_order_item_coupon_url', $item_link, $item, $coupon );

                $tip_html = sprintf( '%1$s %2$s', get_woocommerce_currency_symbol( $order->get_currency() ), $item->get_discount() );
                if ( empty( $item_link ) || '#' === $item_link ) {
                    echo '<li class="code"><span class="tips" data-title="' . esc_attr( $tip_html ) . '"><span>' . $item->get_name() . '</span></span></li>';
                } else {
                    echo '<li class="code"><a href="' . esc_url( $item_link ) . '" class="dokan-vendor-order-page-tips" data-title="' . esc_attr( $tip_html ) . '"><span>' . $item->get_name() . '</span></a></li>';
                }
            }
            ?>
            </ul>
        </div>
        <?php
    }
    ?>
    <table class="wc-order-totals">
        <tr>
            <td><?php esc_html_e( 'Discount', 'dokan' ); ?> <span class="tips" title="<?php esc_attr_e( 'This is the total discount. Discounts are defined per line item.', 'dokan' ); ?>"><span class="fa fa-question-circle dokan-vendor-order-page-tips"></span></span> :</td>
            <td class="total" style="width: 35%;">-
                <?php echo wc_price( $order->get_total_discount(), array( 'currency' => dokan_replace_func( 'get_order_currency', 'get_currency', $order ) ) ); ?>
            </td>
        </tr>

        <?php do_action( 'woocommerce_admin_order_totals_after_discount', dokan_get_prop( $order, 'id' ) ); ?>

        <tr>
            <td><?php esc_html_e( 'Shipping', 'dokan' ); ?> <span class="tips" title="<?php esc_attr_e( 'This is the shipping and handling total costs for the order.', 'dokan' ); ?>"><span class="fa fa-question-circle dokan-vendor-order-page-tips"></span></span> :</td>
            <td class="total" style="width: 35%;"><?php echo wc_price( $order->get_total_shipping(), array( 'currency' => dokan_replace_func( 'get_order_currency', 'get_currency', $order ) ) ); ?></td>
        </tr>

        <?php do_action( 'woocommerce_admin_order_totals_after_shipping', dokan_get_prop( $order, 'id' ) ); ?>

        <?php if ( wc_tax_enabled() ) : ?>
            <?php foreach ( $order->get_tax_totals() as $code => $tax_item ) : ?>
                <tr>
                    <td><?php echo esc_html( $tax_item->label ); ?>:</td>
                    <td class="total" style="width: 35%;"><?php echo wp_kses_post( $tax_item->formatted_amount ); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php do_action( 'woocommerce_admin_order_totals_after_tax', $order->get_id() ); ?>

        <tr>
            <td><?php esc_html_e( 'Order Total', 'dokan' ); ?>:</td>
            <td class="total" style="width: 35%;">
                <div class="view"><?php echo $order->get_formatted_order_total(); ?></div>
                <div class="edit" style="display: none;">
                    <input type="text" class="wc_input_price" id="_order_total" name="_order_total" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo ( isset( $data['_order_total'][0] ) ) ? esc_attr( wc_format_localized_price( $data['_order_total'][0] ) ) : ''; ?>" />
                    <div class="clear"></div>
                </div>
            </td>
        </tr>

        <?php do_action( 'woocommerce_admin_order_totals_after_total', dokan_get_prop( $order, 'id' ) ); ?>

        <tr>
            <td class="refunded-total"><?php esc_html_e( 'Refunded', 'dokan' ); ?>:</td>
            <td class="total refunded-total" style="width: 35%;">-<?php echo wc_price( $order->get_total_refunded(), array( 'currency' => dokan_replace_func( 'get_order_currency', 'get_currency', $order ) ) ); ?></td>
        </tr>

        <?php
        if ( class_exists( 'WC_Subscriptions' ) ) {
            require_once ABSPATH . 'wp-admin/includes/screen.php';
        }

        do_action( 'woocommerce_admin_order_totals_after_refunded', dokan_get_prop( $order, 'id' ) );
        ?>

    </table>
    <div class="clear"></div>
</div>

<?php if ( current_user_can( 'dokan_manage_refund' ) && dokan_is_refund_allowed_to_approve( $order->get_id() ) ) : ?>
    <?php if ( $has_pending_request ) : ?>
    <div class="dokan-alert dokan-alert-info">
        <p>
            <?php echo $pending_request_message; ?>
        </p>
    </div>
    <?php else : ?>
    <div class="wc-order-data-row wc-order-bulk-actions">
        <p class="add-items">
            <?php if ( ( $order->get_total() - $order->get_total_refunded() ) > 0 ) : ?>
                <button type="button" class="dokan-btn dokan-btn-default refund-items"><?php esc_html_e( 'Request Refund', 'dokan' ); ?></button>
            <?php endif; ?>
        </p>
        <div class="clear"></div>
    </div>
    <?php endif; ?>

    <?php if ( ( $order->get_total() - $order->get_total_refunded() ) > 0 ) : ?>
        <div class="wc-order-data-row wc-order-refund-items" style="display: none;">
            <table class="wc-order-totals dokan-table dokan-table-strip">

                <?php if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) : ?>
                    <tr>
                        <td><?php esc_html_e( 'Restock refunded items', 'dokan' ); ?>:</td>
                        <td class="total"><input type="checkbox" id="restock_refunded_items" name="restock_refunded_items" <?php checked( apply_filters( 'dokan_restock_refunded_items', true ) ); ?> /></td>
                    </tr>
                <?php endif; ?>

                <tr>
                    <td><?php esc_html_e( 'Amount already refunded', 'dokan' ); ?>:</td>
                    <td class="total">-<?php echo wc_price( $order->get_total_refunded(), array( 'currency' => dokan_replace_func( 'get_order_currency', 'get_currency', $order ) ) ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Total available to refund', 'dokan' ); ?>:</td>
                    <td class="total"><?php echo wc_price( $order->get_total() - $order->get_total_refunded(), array( 'currency' => dokan_replace_func( 'get_order_currency', 'get_currency', $order ) ) ); ?></td>
                </tr>
                <tr>
                    <td><label for="refund_amount"><?php esc_html_e( 'Refund amount', 'dokan' ); ?>:</label></td>
                    <td class="total">
                        <input type="text" id="refund_amount" name="refund_amount" class="wc_input_price" disabled="disabled" />
                        <div class="clear"></div>
                    </td>
                </tr>
                <tr>
                    <td><label for="refund_reason"><?php esc_html_e( 'Reason for refund (optional)', 'dokan' ); ?>:</label></td>
                    <td class="total">
                        <input type="text" class="text" id="refund_reason" name="refund_reason" />
                        <div class="clear"></div>
                    </td>
                </tr>
            </table>
            <div class="clear"></div>
            <div class="refund-actions">
                <?php
                $refund_amount = '<span class="wc-order-refund-amount">' . wc_price( 0, array( 'currency' => dokan_replace_func( 'get_order_currency', 'get_currency', $order ) ) ) . '</span>';
                $gateway_name  = false !== $payment_gateway ? ( ! empty( $payment_gateway->method_title ) ? $payment_gateway->method_title : $payment_gateway->get_title() ) : __( 'Payment gateway', 'dokan' );
                if ( ProcessAutomaticRefund::instance()->is_excluded_payment_gateway( $payment_gateway->id ?? '' ) ) :
                    ?>
                    <?php // translators: %s: Refund amount ?>
                    <button type="button" class="dokan-btn dokan-btn-default do-manual-refund tips" data-tip="<?php esc_attr_e( 'You will need to manually issue a refund through your payment gateway after using this.', 'dokan' ); ?>"><?php printf( _x( 'Refund %s Manually', 'Refund $amount Manually', 'dokan' ), $refund_amount ); ?></button>

                    <?php
                    if (
                        false !== $payment_gateway
                        && $payment_gateway->can_refund_order( $order )
                    ) {
                        /* translators: refund amount, gateway name */
                        echo '<button type="button" class="dokan-btn dokan-btn-default do-api-refund">' . sprintf( esc_html__( 'Refund %1$s via %2$s', 'dokan' ), wp_kses_post( $refund_amount ), esc_html( $gateway_name ) ) . '</button>';
                    }
                    ?>
                <?php else : ?>
                    <?php // translators: %s: Refund amount ?>
                    <button type="button" class="dokan-btn dokan-btn-default <?php echo esc_attr( ( false !== $payment_gateway && $payment_gateway->can_refund_order( $order ) ) ? 'do-api-refund' : 'do-manual-refund' ); ?> tips" data-tip="<?php esc_attr_e( 'Admin will decide whether admin manually issue a refund or through your payment gateway via API automatically.', 'dokan' ); ?>"><?php printf( _x( 'Submit Refund Request %s', 'Submit Refund Request $amount', 'dokan' ), $refund_amount ); ?></button>

                <?php endif; ?>
                    <button type="button" class="dokan-btn dokan-btn-default cancel-action"><?php esc_html_e( 'Cancel', 'dokan' ); ?></button>
                <div class="clear"></div>
            </div>
            <div class="clear"></div>
        </div>
    <?php endif; ?>

<?php endif ?>
