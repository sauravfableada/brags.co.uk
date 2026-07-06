<?php
/**
 * Get refund counts, used in admin area
 *
 * @since 2.4.11
 * @since 3.0.0 Move the logic to Refund manager class
 * @since 3.8.0 moved this function from dokan-pro/includes/functions.php
 *
 * @global WPDB $wpdb
 * @return array
 */
function dokan_get_refund_count( $seller_id = null ) {
    return dokan_pro()->refund->get_status_counts( $seller_id );
}

/**
 * Get refund localize data
 *
 * @since 2.6
 * @since 3.8.0 moved this function from dokan-pro/includes/functions.php
 *
 * @return array
 **/
function dokan_get_refund_localize_data() {
    return [
        'mon_decimal_point'            => wc_get_price_decimal_separator(),
        'remove_item_notice'           => __( 'Are you sure you want to remove the selected items? If you have previously reduced this item\'s stock, or this order was submitted by a customer, you will need to manually restore the item\'s stock.', 'dokan' ),
        'i18n_select_items'            => __( 'Please select some items.', 'dokan' ),
        'i18n_do_refund'               => __( 'Are you sure you wish to process this refund request? This action cannot be undone.', 'dokan' ),
        'i18n_delete_refund'           => __( 'Are you sure you wish to delete this refund? This action cannot be undone.', 'dokan' ),
        'remove_item_meta'             => __( 'Remove this item meta?', 'dokan' ),
        'ajax_url'                     => admin_url( 'admin-ajax.php' ),
        'order_item_nonce'             => wp_create_nonce( 'order-item' ),
        'post_id'                      => isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : '', //phpcs:ignore
        'currency_format_num_decimals' => wc_get_price_decimals(),
        'currency_format_symbol'       => get_woocommerce_currency_symbol(),
        'currency_format_decimal_sep'  => esc_attr( wc_get_price_decimal_separator() ),
        'currency_format_thousand_sep' => esc_attr( wc_get_price_thousand_separator() ),
        'currency_format'              => esc_attr( str_replace( [ '%1$s', '%2$s' ], [ '%s', '%v' ], get_woocommerce_price_format() ) ), // For accounting JS
        'round_at_subtotal'            => get_option( 'woocommerce_tax_round_at_subtotal', 'no' ),
        'rounding_precision'           => wc_get_rounding_precision(),
    ];
}

/**
 * Check if the refund request is allowed to be approved
 *
 * @since 3.8.0 moved this function from dokan-pro/includes/functions.php
 *
 * @param int $order_id
 *
 * @return boolean
 */
function dokan_is_refund_allowed_to_approve( $order_id ) {
    if ( ! $order_id ) {
        return false;
    }

    $order               = wc_get_order( $order_id );
    $order_status        = 'wc-' . $order->get_status();
    $active_order_status = dokan_withdraw_get_active_order_status();

    return in_array( $order_status, $active_order_status, true );
}
