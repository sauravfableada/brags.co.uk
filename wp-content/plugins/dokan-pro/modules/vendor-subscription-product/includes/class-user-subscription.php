<?php

/**
* User subscription for Vendor Dashboard
*/
class Dokan_VSP_User_Subscription {

    /**
     * Load autometically when class initiate
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_filter( 'dokan_query_var_filter', [ $this, 'load_subscription_query_var' ], 15, 1 );
        add_filter( 'dokan_get_dashboard_nav', [ $this, 'add_subscription_menu' ], 15 );
        add_filter( 'dokan_get_dashboard_nav_template_dependency', [ $this, 'get_subscription_nav_template_dependency' ] );
        add_filter( 'dokan_load_custom_template', [ $this, 'load_subscription_content' ], 15, 1 );
        add_action( 'dokan_vps_subscriptions_related_orders_meta_box_rows', [ $this, 'render_related_order_content' ], 15, 1 );
        add_action( 'template_redirect', [ $this, 'handle_subscription_schedule' ], 99 );
        add_action( 'wp_ajax_dokan_vps_change_status', [ $this, 'change_subscription_status' ] );
        // TODO: This is for backward compatibility. It renames dokan_commission_meta key to _dokan_commission_meta. Remove this after few versions.
        add_filter( 'woocommerce_order_again_cart_item_data', [ $this, 'dokan_on_commission_meta' ], 10, 2 );
    }

    /**
     * Replace dokan_commission_meta key to _dokan_commission_meta.
     *
     * @param array         $cart_items
     * @param WC_Order_Item $line_item
     *
     * @return array
     */
    public function dokan_on_commission_meta( $cart_items, $line_item ) {
        $value = $line_item->get_meta( 'dokan_commission_meta', true );

        if ( '' !== $value ) {
            $line_item->delete_meta_data( 'dokan_commission_meta' );
            $line_item->update_meta_data( '_dokan_commission_meta', $value );
            $line_item->save();

            $cart_items = array_map(
                function ( $item ) {
                    if ( isset( $item['custom_line_item_meta']['dokan_commission_meta'] ) ) {
                        unset( $item['custom_line_item_meta']['dokan_commission_meta'] );
                    }

                    return $item;
                },
                $cart_items
            );
        }

        return $cart_items;
    }

    /**
     * Load subscription query vars
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function load_subscription_query_var( $query_vars ) {
        $query_vars[] = 'user-subscription';

        return $query_vars;
    }

    /**
     * Add subscription menu
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function add_subscription_menu( $urls ) {
        $urls['user-subscription'] = [
            'title'       => __( 'User Subscriptions', 'dokan' ),
            'icon'        => '<i class="fas fa-users"></i>',
            'url'         => dokan_get_navigation_url( 'user-subscription' ),
            'pos'         => 50,
            'icon_name'   => 'Layers2',
            'permission'  => 'dokan_view_order_menu',
            'react_route' => 'user-subscription',
        ];

        return $urls;
    }

    /**
     * Get subscription nav template dependency
     *
     * @param array $dependencies
     *
     * @since 4.0.0
     *
     * @return array
     */
    public function get_subscription_nav_template_dependency( array $dependencies ) {
        $dependencies['user-subscription'] = [
            [
                'slug' => 'subscription/subscrptions',
                'name' => '',
                'args' => [ 'is_subscription_product' => true ],
            ],
            [
                'slug' => 'subscription/subscription-details',
                'name' => '',
                'args' => [ 'is_subscription_product' => true ],
            ],
            [
                'slug' => 'subscription/html-related-orders-row',
                'name' => '',
                'args' => [ 'is_subscription_product' => true ],
            ],

        ];

        return $dependencies;
    }

    /**
     * Load subscription content
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function load_subscription_content( $query_vars ) {
        if ( isset( $query_vars['user-subscription'] ) ) {
            $subscription_id = isset( $_GET['subscription_id'] ) ? intval( $_GET['subscription_id'] ) : 0;

            if ( $subscription_id ){
                $_nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
                if ( $_nonce ) {
                    dokan_get_template_part( 'subscription/subscription-details', '', [ 'is_subscription_product' => true, 'subscription_id' => $subscription_id ] );
                } else {
                    echo __( 'You have no permission to view this information', 'dokan' );
                }
            } else{
                dokan_get_template_part( 'subscription/subscrptions', '', [ 'is_subscription_product' => true ] );
            }
            return;
        }
    }

    /**
     * Render related order content
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_related_order_content( $subscription ) {
        $orders_to_display     = array();
        $subscriptions         = array();
        $orders_by_type        = array();
        $unknown_orders        = array(); // Orders which couldn't be loaded.
        $this_order            = $subscription;

        // If this is a subscription screen,
        $subscriptions[] = $subscription;

        // Resubscribed subscriptions and orders.
        $initial_subscriptions         = wcs_get_subscriptions_for_resubscribe_order( $subscription );
        $orders_by_type['resubscribe'] = WCS_Related_Order_Store::instance()->get_related_order_ids( $subscription, 'resubscribe' );

        foreach ( $subscriptions as $subscription ) {
            // If we're on a single subscription or renewal order's page, display the parent orders
            if ( 1 === count( $subscriptions ) && $subscription->get_parent_id() ) {
                $orders_by_type['parent'][] = $subscription->get_parent_id();
            }

            // Finally, display the renewal orders
            $orders_by_type['renewal'] = $subscription->get_related_orders( 'ids', 'renewal' );

            // Build the array of subscriptions and orders to display.
            $subscription->update_meta_data( '_relationship', _x( 'Subscription', 'relation to order', 'woocommerce-subscriptions' ) );
            $orders_to_display[] = $subscription;
        }

        foreach ( $initial_subscriptions as $subscription ) {
            $subscription->update_meta_data( '_relationship', _x( 'Initial Subscription', 'relation to order', 'woocommerce-subscriptions' ) );
            $orders_to_display[] = $subscription;
        }

        // Assign all order and subscription relationships and filter out non-objects.
        foreach ( $orders_by_type as $order_type => $orders ) {
            foreach ( $orders as $order_id ) {
                $order = wc_get_order( $order_id );

                switch ( $order_type ) {
                    case 'renewal':
                        $relation = _x( 'Renewal Order', 'relation to order', 'woocommerce-subscriptions' );
                        break;
                    case 'parent':
                        $relation = _x( 'Parent Order', 'relation to order', 'woocommerce-subscriptions' );
                        break;
                    case 'resubscribe':
                        $relation = wcs_is_subscription( $order ) ? _x( 'Resubscribed Subscription', 'relation to order', 'woocommerce-subscriptions' ) : _x( 'Resubscribe Order', 'relation to order', 'woocommerce-subscriptions' );
                        break;
                    default:
                        $relation = _x( 'Unknown Order Type', 'relation to order', 'woocommerce-subscriptions' );
                        break;
                }

                if ( $order ) {
                    $order->update_meta_data( '_relationship', $relation );
                    $orders_to_display[] = $order;
                } else {
                    $unknown_orders[] = array(
                        'order_id' => $order_id,
                        'relation' => $relation,
                    );
                }
            }
        }

        if ( has_filter( 'woocommerce_subscriptions_admin_related_orders_to_display' ) ) {
            wcs_deprecated_hook( 'woocommerce_subscriptions_admin_related_orders_to_display', '3.8.0', 'wcs_admin_subscription_related_orders_to_display' );

            /**
             * Filters the orders to display in the Related Orders meta box.
             *
             * This filter is deprecated in favour of 'wcs_admin_subscription_related_orders_to_display'.
             *
             * @deprecated 3.8.0
             *
             * @param array   $orders_to_display An array of orders to display in the Related Orders meta box.
             * @param array   $subscriptions An array of subscriptions related to the order.
             * @param WP_Post $post The order post object.
             */
            $orders_to_display = apply_filters( 'woocommerce_subscriptions_admin_related_orders_to_display', $orders_to_display, $subscriptions, get_post( $this_order->get_id() ) );
        }

        /**
         * Filters the orders to display in the Related Orders meta box.
         *
         * @since 3.8.0
         *
         * @param array    $orders_to_display An array of orders to display in the Related Orders meta box.
         * @param array    $subscriptions An array of subscriptions related to the order.
         * @param WC_Order $order The order object.
         */
        $orders_to_display = apply_filters( 'wcs_admin_subscription_related_orders_to_display', $orders_to_display, $subscriptions, $this_order );

        wcs_sort_objects( $orders_to_display, 'date_created', 'descending' );

        foreach ( $orders_to_display as $order ) {
            // Skip the current order or subscription being viewed.
            if ( $order->get_id() === $this_order->get_id() ) {
                continue;
            }

            dokan_get_template_part( 'subscription/html-related-orders-row', '', [ 'is_subscription_product' => true, 'order' => $order ] );
        }
    }

    /**
     * Handle subscription schedule
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function handle_subscription_schedule() {
        if ( ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'dokan_change_subscription_schedule' ) ) {

            $subscription_id = isset( $_POST['subscription_id'] ) ? intval( $_POST['subscription_id'] ) : 0;
            $subscription = wcs_get_subscription( $subscription_id );

            if ( isset( $_POST['_billing_interval'] ) ) {
                $subscription->set_billing_interval( $_POST['_billing_interval'] );
            }

            if ( ! empty( $_POST['_billing_period'] ) ) {
                $subscription->set_billing_period( $_POST['_billing_period'] );
            }

            $dates = array();

            foreach ( wcs_get_subscription_date_types() as $date_type => $date_label ) {
                $date_key = wcs_normalise_date_type_key( $date_type );

                if ( 'last_order_date_created' == $date_key ) {
                    continue;
                }

                $utc_timestamp_key = $date_type . '_timestamp_utc';

                // A subscription needs a created date, even if it wasn't set or is empty
                if ( 'date_created' === $date_key && empty( $_POST[ $utc_timestamp_key ] ) ) {
                    $datetime = current_time( 'timestamp', true );
                } elseif ( isset( $_POST[ $utc_timestamp_key ] ) ) {
                    $datetime = $_POST[ $utc_timestamp_key ];
                } else { // No date to set
                    continue;
                }
                $dates[ $date_key ] = date( 'Y-m-d H:i:s', $datetime );
            }

            try {
                $subscription->update_dates( $dates, 'gmt' );

                wp_cache_delete( $subscription_id, 'posts' );
            } catch ( Exception $e ) {
                wcs_add_admin_notice( $e->getMessage(), 'error' );
            }

            $subscription->save();
        }
    }

    /**
     * Change subscription status
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function change_subscription_status() {
        check_ajax_referer( 'dokan_vps_change_status' );

        if ( ! current_user_can( 'dokan_manage_order' ) ) {
            wp_send_json_error( __( 'You have no permission to manage this order', 'dokan' ) );
            return;
        }

        $subscription_id     = isset( $_POST['subscription_id'] ) ? intval( $_POST['subscription_id'] ) : '';
        $subscription_status = isset( $_POST['subscription_status'] ) ? sanitize_text_field( $_POST['subscription_status'] ) : '';

        $subscription = wcs_get_subscription( $subscription_id );
        $subscription->update_status( $subscription_status );

        $statuses     = wcs_get_subscription_statuses();
        $status_label = isset( $statuses[ $subscription_status ] ) ? $statuses[ $subscription_status ] : $subscription_status;
        $status_class = dokan_vps_get_subscription_status_class( $subscription_status );

        $html = '<label class="dokan-label dokan-label-' . esc_attr( $status_class ) . '">' . esc_attr( $status_label ) . '</label>';

        wp_send_json_success( $html );
    }
}
