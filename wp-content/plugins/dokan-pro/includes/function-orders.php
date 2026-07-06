<?php

/**
 * Get seller refund by date range
 *
 * @param string $start_date
 * @param string $end_date
 * @param int    $seller_id
 *
 * @deprecated 4.0.0 Will be removed on next major release
 *
 * @return object[]|WP_Error
 */
function dokan_get_seller_refund_by_date( $start_date, $end_date, $seller_id = false ) {
    wc_deprecated_function( 'dokan_get_seller_refund_by_date', '3.8.0', 'dokan_pro()->refund->all()' );

    global $wpdb;

    $seller_id           = ! $seller_id ? get_current_user_id() : intval( $seller_id );
    $refund_status_where = $wpdb->prepare( ' AND status = %d', 1 );
    $refund_date_query   = $wpdb->prepare( ' AND DATE( date ) >= %s AND DATE( date ) <= %s', $start_date, $end_date );

    $refund_sql = "SELECT *
            FROM {$wpdb->prefix}dokan_refund
            WHERE
                seller_id = %d
                $refund_date_query
                $refund_status_where
            ORDER BY date ASC";

    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    $results = $wpdb->get_results( $wpdb->prepare( $refund_sql, $seller_id ) );
    if ( false === $results ) {
        // translators: %s: database error message
        return new WP_Error( 'database-error', sprintf( __( 'DB query error while getting seller refund data: %s', 'dokan' ), $wpdb->last_error ) );
    }

    return $results;
}

if ( ! function_exists( 'dokan_sync_order_table' ) ) :
    /**
     * Insert an order in sync table once a order is created
     *
     * @since 2.4
     *
     * @param int $order_id
     *
     * @deprecated 4.0.0 Will be removed on next major release
     *
     * @todo Remove this function on next major release
     *
     * @return void
     */
    function dokan_sync_order_table( $order_id ) {
        wc_deprecated_function( 'dokan_sync_order_table', '3.8.0' );

        global $wpdb;

        $order = wc_get_order( $order_id );
        if ( ! $order || $order->get_meta( 'has_sub_order', true ) === '1' ) {
            return;
        }

        $seller_id   = dokan_get_seller_id_by_order( $order_id );
        $order_total = $order->get_total();

        if ( $order->get_total_refunded() ) {
            $order_total = $order_total - $order->get_total_refunded();
        }

        $order_status     = $order->get_status();
        $admin_commission = dokan()->commission->get_earning_by_order( $order, 'admin' );
        $net_amount       = $order_total - $admin_commission;
        $net_amount       = apply_filters( 'dokan_sync_order_net_amount', $net_amount, $order );

        // make sure order status contains "wc-" prefix
        if ( stripos( $order_status, 'wc-' ) === false ) {
            $order_status = 'wc-' . $order_status;
        }

        $wpdb->insert(
            $wpdb->prefix . 'dokan_orders',
            [
                'order_id'     => $order_id,
                'seller_id'    => $seller_id,
                'order_total'  => $order_total,
                'net_amount'   => $net_amount,
                'order_status' => $order_status,
            ],
            [
                '%d',
                '%d',
                '%f',
                '%f',
                '%s',
            ]
        );
    }
endif;

/**
 * Load order items template
 *
 * @since 3.8.0 moved this method from includes/functions.php
 *
 * @param int $order_id
 *
 * @return void
 */
function dokan_render_order_table_items( $order_id ) {
    $data  = get_post_meta( $order_id );
    $order = new WC_Order( $order_id );

    dokan_get_template_part(
        'orders/views/html-order-items', '', array(
            'pro'   => true,
            'data'  => $data,
            'order' => $order,
        )
    );
}

/**
 * Shared "should render customer details?" callback for vendor order-details and email gates.
 *
 * Returns false when the "Hide Customer Info" selling option is enabled, otherwise honors
 * the upstream caller's decision. The option is to read each call so admin toggles take effect live.
 *
 * @since 5.0.1
 *
 * @param bool $show Whether the upstream caller intends to render the section.
 *
 * @return bool
 */
function dokan_maybe_hide_customer_info( $show ) {
    if ( ! $show ) {
        return $show;
    }

    $should_render = 'on' !== dokan_get_option( 'hide_customer_info', 'dokan_selling', 'off' );

    /**
     * Filters whether vendor-facing customer-identifying details should render.
     *
     * @since 5.0.1
     *
     * @param bool $should_render Whether to render the section. False = hidden.
     * @param bool $show          Original upstream value before this filter ran.
     */
    return (bool) apply_filters( 'dokan_should_render_customer_info', $should_render, $show );
}
