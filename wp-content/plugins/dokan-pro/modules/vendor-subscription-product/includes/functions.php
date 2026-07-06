<?php

use WeDevs\Dokan\Cache;
use WeDevs\Dokan\Utilities\OrderUtil;

/**
* Get translated string of order status
*
* @param string $status
* @return string
*/
function dokan_vps_get_subscription_status_translated( $status ) {
    switch ( $status ) {
        case 'completed':
        case 'wc-completed':
            return __( 'Completed', 'dokan' );

        case 'active':
        case 'wc-active':
            return __( 'Active', 'dokan' );

        case 'expired':
        case 'wc-expired':
            return __( 'Expired', 'dokan' );

        case 'pending':
        case 'wc-pending':
            return __( 'Pending Payment', 'dokan' );

        case 'on-hold':
        case 'wc-on-hold':
            return __( 'On-hold', 'dokan' );

        case 'processing':
        case 'wc-processing':
            return __( 'Processing', 'dokan' );

        case 'refunded':
        case 'wc-refunded':
            return __( 'Refunded', 'dokan' );

        case 'cancelled':
        case 'wc-cancelled':
            return __( 'Cancelled', 'dokan' );

        case 'failed':
        case 'wc-failed':
            return __( 'Failed', 'dokan' );

        case 'pending-cancel':
        case 'wc-pending-cancel':
            return __( 'Pending Cancellation', 'dokan' );

        default:
            return apply_filters( 'dokan_vps_get_order_status_translated', '', $status );
    }
}

/**
* Get bootstrap label class based on order status
*
* @param string $status
* @return string
*/
function dokan_vps_get_subscription_status_class( $status ) {
    switch ( $status ) {
        case 'completed':
        case 'wc-completed':
        case 'active':
        case 'wc-active':
            return 'success';

        case 'pending-cancel':
        case 'wc-pending-cancel':
        case 'pending':
        case 'wc-expired':
        case 'expired':
        case 'wc-failed':
        case 'failed':
        case 'wc-pending':
            return 'danger';

        case 'on-hold':
        case 'wc-on-hold':
            return 'warning';

        case 'processing':
        case 'wc-processing':
            return 'info';

        case 'refunded':
        case 'wc-cancelled':
        case 'cancelled':
        case 'wc-refunded':
            return 'default';

        default:
            return apply_filters( 'dokan_get_order_status_class', '', $status );
    }
}

/**
 * Display Date format for subscriptions
 *
 * @since 1.0.0
 *
 * @return void
 */
function dokan_vps_get_date_content( $subscription, $column ) {
    $date_type_map = array( 'last_payment_date' => 'last_order_date_created' );
    $date_type     = array_key_exists( $column, $date_type_map ) ? $date_type_map[ $column ] : $column;

    // @codingStandardsIgnoreStart
    if ( 0 == $subscription->get_time( $date_type, 'gmt' ) ) {
        $column_content = '-';
    } else {
        $column_content = sprintf( '<time class="%s" title="%s">%s</time>', esc_attr( $column ), esc_attr( date( __( 'Y/m/d g:i:s A', 'woocommerce-subscriptions' ) , $subscription->get_time( $date_type, 'site' ) ) ), esc_html( $subscription->get_date_to_display( $date_type ) ) );

        if ( 'next_payment_date' == $column && $subscription->payment_method_supports( 'gateway_scheduled_payments' ) && ! $subscription->is_manual() && $subscription->has_status( 'active' ) ) {
            $column_content .= '<div class="woocommerce-help-tip" data-tip="' . esc_attr__( 'This date should be treated as an estimate only. The payment gateway for this subscription controls when payments are processed.', 'woocommerce-subscriptions' ) . '"></div>';
        }
    }
    // @codingStandardsIgnoreEnd

    return $column_content;
}

/**
 * Get the subscriptions or count for a specific seller.
 *
 * @since 3.3.6
 *
 * @global wpdb $wpdb
 *
 * @param array $args
 *
 * @return array|int
 */
function dokan_vps_get_seller_subscriptions( $args = array() ) {
    global $wpdb;

    $default = array(
        'seller_id'   => dokan_get_current_user_id(),
        'status'      => 'all',
        'order_date'  => null,
        'limit'       => 0,
        'offset'      => 0,
        'customer_id' => null,
        'relation'    => null,
        'return'      => 'subscriptions',
    );

    $args                = wp_parse_args( $args, $default );
    $cache_group         = "seller_order_data_{$args['seller_id']}";
    $cache_key           = 'seller_subscriptions_info_' . md5( wp_json_encode( $args ) );
    $subscriptions_info  = Cache::get( $cache_key, $cache_group );
    $subscriptions_array = array();

    $order_date              = $args['order_date'] ? $args['order_date'] : null;
    $order_table_name        = $wpdb->posts;
    $id_field_name           = 'ID';
    $order_parent_field_name = 'post_parent';
    $type_field_name         = 'post_type';
    $status_field_name       = 'post_status';
    $date_field_name         = 'post_date';

    if ( dokan_pro_is_hpos_enabled() ) {
        $order_table_name        = OrderUtil::get_order_table_name();
        $id_field_name           = 'id';
        $order_parent_field_name = 'parent_order_id';
        $type_field_name         = 'type';
        $status_field_name       = 'status';
        $date_field_name         = 'date_created_gmt';

        if ( $order_date ) {
            $order_date = dokan_current_datetime()->modify( $order_date );
            if ( $order_date ) {
                $order_date = $order_date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d');
            }
        }
    }

    if ( false === $subscriptions_info ) {
        $status_where   = ( $args['status'] === 'all' ) ? '' : $wpdb->prepare( ' AND dokan_orders.order_status = %s', $args['status'] );
        $date_query     = ( $args['order_date'] ) ? $wpdb->prepare( " AND DATE( subscriptions.{$date_field_name} ) = %s", $args['order_date'] ) : '';
        if ( $args['customer_id'] ) {
            $where_customer = dokan_pro_is_hpos_enabled() ? $wpdb->prepare( ' AND subscriptions.customer_id = %d', $args['customer_id'] ) : $wpdb->prepare( ' AND postmeta.meta_key = %s AND postmeta.meta_value = %d', '_customer_user', $args['customer_id'] );
            $join_customer  = dokan_pro_is_hpos_enabled() ? '' : "INNER JOIN $wpdb->postmeta as postmeta on subscriptions.ID = postmeta.post_id";
        } else {
            $join_customer  = '';
            $where_customer = '';
        }

        $limit_query    = $args['limit'] ? $wpdb->prepare( "ORDER BY subscriptions.{$id_field_name} DESC LIMIT %d, %d", $args['offset'], $args['limit'] ) : '';

        // phpcs:disable
        $subscriptions_query = $wpdb->prepare(
            "SELECT subscriptions.{$id_field_name} as ID FROM {$order_table_name} as subscriptions
                INNER JOIN {$order_table_name} as orders ON subscriptions.{$order_parent_field_name} = orders.{$id_field_name}
                INNER JOIN {$wpdb->prefix}dokan_orders as dokan_orders on dokan_orders.order_id = subscriptions.{$order_parent_field_name}
                {$join_customer}
                WHERE
                    dokan_orders.seller_id = %d AND
                    subscriptions.{$type_field_name} = 'shop_subscription' AND
                    orders.{$type_field_name} = 'shop_order' AND
                    orders.{$status_field_name} != 'trash' AND
                    subscriptions.{$order_parent_field_name} = orders.{$id_field_name}
                    {$where_customer}
                    {$date_query} {$status_where}
                GROUP BY subscriptions.{$id_field_name}
                {$limit_query}
                ", $args['seller_id']
        );

        $subscriptions_count_query = "SELECT COUNT(*) as count FROM ( {$subscriptions_query} ) AS subscription_query";
        $subscriptions_info        = ( $args['return'] === 'subscriptions' ) ? $wpdb->get_results( $subscriptions_query ) : $wpdb->get_var( $subscriptions_count_query );
        // phpcs:enable

        Cache::set( $cache_key, $subscriptions_info, $cache_group );
    }

    if ( $args['return'] === 'subscriptions' ) {
        foreach ( $subscriptions_info as $subscription ) {
            $subscriptions_array[ $subscription->ID ] = wcs_get_subscription( $subscription->ID );
        }

        return $subscriptions_array;
    }
    return absint( $subscriptions_info );
}

