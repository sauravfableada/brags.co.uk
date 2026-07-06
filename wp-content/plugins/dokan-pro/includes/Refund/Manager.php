<?php

namespace WeDevs\DokanPro\Refund;

use WeDevs\Dokan\Cache;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Manager {

    /**
     * Get a collection of Dokan refunds
     *
     * @since 3.0.0
     *
     * @param array $args
     *
     * @return array|object Return an object with `total` and `max_num_pages` when `paginate` arg
     *                      is provided
     */
    public function all( $args = [] ) {
        //todo: will change cache group to individual seller after seller filter is added
        $cache_group = 'refunds';
        $cache_key   = 'refunds_data_' . md5( wp_json_encode( $args ) );
        $refunds_data     = Cache::get( $cache_key, $cache_group );

        if ( false === $refunds_data ) {
            $refunds = new Refunds( $args );
            $refunds_data = [
                'refunds'       => $refunds->get_refunds(),
                'total'         => $refunds->get_total(),
                'max_num_pages' => $refunds->get_maximum_num_pages(),
            ];

            Cache::set( $cache_key, $refunds_data, $cache_group );
        }

        if ( empty( $args['paginate'] ) ) {
            return $refunds_data['refunds'];
        } else {
            return (object) [
                'refunds'       => $refunds_data['refunds'],
                'total'         => $refunds_data['total'],
                'max_num_pages' => $refunds_data['max_num_pages'],
            ];
        }
    }

    /**
     * Get a single refund object
     *
     * @since 3.0.0
     *
     * @param int $id
     *
     * @return Refund|null
     */
    public function get( $id ) {
        global $wpdb;

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "select * from {$wpdb->dokan_refund} where id = %d",
                $id
            ), ARRAY_A
        );

        if ( ! $result ) {
            return null;
        }

        $refund     = new Refund( $result );
        $attributes = array_keys( $refund->get_data() );

        foreach ( $attributes as $attribute ) {
            $sanitizer_method = "sanitize_$attribute";

            if ( method_exists( Sanitizer::class, $sanitizer_method ) ) {
                $setter = "set_$attribute";
                $getter = "get_$attribute";
                $refund->$setter( Sanitizer::$sanitizer_method( $refund->$getter() ) );
            }
        }

        return $refund;
    }

    /**
     * Create a refund
     *
     * @since 3.0.0
     *
     * @param array $args
     *
     * @return Refund|WP_Error
     */
    public function create( $args ) {
        if ( isset( $args['id'] ) ) {
            unset( $args['id'] );
        }

        if ( isset( $args['status'] ) ) {
            unset( $args['status'] );
        }

        $args['status'] = 0;

        // get order instance
        $order = wc_get_order( $args['order_id'] );

        if ( ! $order || $order->get_meta( 'has_sub_order' ) ) {
            return new WP_Error( 'dokan_pro_refund_create', __( 'You can not refund orders that have suborders. Please refund from specific suborders.', 'dokan' ) );
        }

        $seller_id = dokan_get_seller_id_by_order( $args['order_id'] );

        if ( ! $seller_id ) {
            // translators: %d: order id
            return new WP_Error( 'dokan_pro_refund_create', sprintf( __( 'Seller id not found for order_id %d', 'dokan' ), $args['order_id'] ) );
        }

        $args['seller_id'] = $seller_id;

        if ( $this->has_pending_request( $args['order_id'] ) ) {
            // translators: %d: order id
            return new WP_Error( 'dokan_pro_refund_create', sprintf( __( 'There is a pending refund request exists associated with the order_id %d', 'dokan' ), $args['order_id'] ) );
        }

        if ( ! $this->is_approvable( $args['order_id'] ) ) {
            return new WP_Error( 'dokan_pro_refund_error_create', __( 'Refund requests can not be made due to a mismatch on withdrawal options selected on admin settings. Please check Order Status for Withdraw option from Dokan --> Settings --> Withdraw Options.', 'dokan' ) );
        }

        $refund = new Refund( $args );

        return $refund->save();
    }

    /**
     * Get refund statuses
     *
     * @since 3.0.0
     *
     * @return array
     */
    public function get_statuses() {
        return [
            'pending'   => Refunds::STATUS_PENDING,
            'completed' => Refunds::STATUS_COMPLETED,
            'cancelled' => Refunds::STATUS_CANCELLED,
        ];
    }

    /**
     * Get status code by status name
     *
     * @since 3.0.0
     *
     * @param string
     *
     * @return integer
     */
    public function get_status_code( $status ) {
        $statuses = $this->get_statuses();

        return $statuses[ $status ] ?? 0;
    }

    /**
     * Get status codes
     *
     * @since 3.0.0
     *
     * @return array
     */
    public function get_status_codes() {
        return array_values( $this->get_statuses() );
    }

    /**
     * Get status names
     *
     * @since 3.0.0
     *
     * @return array
     */
    public function get_status_names() {
        $names = [];

        foreach ( $this->get_statuses() as $status_name => $status_code ) {
            $names[ $status_code ] = $status_name;
        }

        return $names;
    }

    /**
     * Get status code name for a code number
     *
     * @since 3.0.0
     *
     * @param int $status_code
     *
     * @return string
     */
    public function get_status_name( $status_code ) {
        $names = $this->get_status_names();

        return $names[ $status_code ] ?? null;
    }

    /**
     * Refund status counts for a seller
     *
     * @since 3.0.0
     *
     * @param int $seller_id
     *
     * @return array
     */
    public function get_status_counts( $seller_id = null ) {
        global $wpdb;

        $where      = '';
        $query_args = [ 1, 1 ];

        if ( $seller_id ) {
            $where        .= ' and seller_id = %d';
            $query_args[] = $seller_id;
        }

        $cache_group = ! empty( $seller_id ) ? "refund_{$seller_id}" : 'refunds';
        $cache_key   = 'get_status_counts_' . md5( wp_json_encode( $query_args ) );
        $results     = Cache::get( $cache_key, $cache_group );

        if ( false === $results ) {
            // phpcs:disable
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "select count(*) as count, status from $wpdb->dokan_refund where %d=%d $where group by status",
                    ...$query_args
                ), ARRAY_A
            );
            // phpcs:enable

            Cache::set( $cache_key, $results, $cache_group );
        }

        $counts     = [];
        $count_list = wp_list_pluck( $results, 'count', 'status' );
        $statuses   = dokan_pro()->refund->get_statuses();

        foreach ( $statuses as $status => $status_code ) {
            $counts[ $status ] = isset( $count_list[ $status_code ] ) ? absint( $count_list[ $status_code ] ) : 0;
        }

        return $counts;
    }

    /**
     * Find if an order has any pending request
     *
     * @since 3.0.0
     *
     * @param int $order_id
     *
     * @return bool
     */
    public function has_pending_request( $order_id ) {
        global $wpdb;

        $has_request = $wpdb->get_var(
            $wpdb->prepare(
                "select count(*) from $wpdb->dokan_refund where status = %d and order_id = %d", 0, $order_id
            )
        );

        return (bool) absint( $has_request );
    }

    /**
     * Checks if an order is eligible to approve
     *
     * @since 3.0.0
     *
     * @param int $order_id
     *
     * @return bool
     */
    public function is_approvable( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return false;
        }

        $order_status        = 'wc-' . $order->get_status();
        $active_order_status = dokan_withdraw_get_active_order_status();

        if ( in_array( $order_status, $active_order_status, true ) ) {
            return true;
        }

        return false;
    }
}
