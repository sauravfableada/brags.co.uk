<?php

namespace WeDevs\DokanPro\Admin;

use WeDevs\DokanPro\Modules\TableRate\DokanGoogleDistanceMatrixAPI;
use WeDevs\DokanPro\Storage\Session;
use WP_Error;

/**
 * Shared service to host admin actions logic used by both AJAX and REST.
 */
class ToolsActions {

    /**
     * Queue background process to regenerate order commission.
     *
     * @return array|WP_Error
     */
    public function regenerate_order_commission() {
        $bg_processor = dokan_pro()->bg_process->regenerate_order_commission;

        $args = [
            'paged' => 1,
        ];

        $bg_processor->push_to_queue( $args )->save()->dispatch();

        return [
            'process' => 'running',
            'message' => __( 'Your orders have been successfully queued for processing. You will be notified once the task has been completed.', 'dokan' ),
        ];
    }

    /**
     * Remove duplicate sub-orders if found.
     * Mirrors logic from Admin\Ajax::check_duplicate_suborders.
     *
     * @param int $limit
     * @param int $offset
     * @param int $prev_done
     * @param int $total_orders
     *
     * @return array|WP_Error
     */
    public function check_duplicate_suborders( $limit = 0, $offset = 0, $prev_done = 0, $total_orders = 0 ) {
        // get session object
        $session = new Session( 'duplicate_suborders' );

        $query_args = [
            'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                [
                    'key'     => 'has_sub_order',
                    'value'   => '1',
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ],
            ],
        ];

        if ( 0 === $offset ) {
            $session->forget_session();
            $query_args['return'] = 'count';
            unset( $query_args['limit'] );
            unset( $query_args['offset'] );
            $total_orders = dokan()->order->all( $query_args );
        }

        $query_args['return'] = 'ids';
        $query_args['limit']  = $limit;
        $query_args['paged']  = $offset + 1;

        $orders           = dokan()->order->all( $query_args );
        $duplicate_orders = null !== $session->get( 'dokan_duplicate_order_ids' ) ? $session->get( 'dokan_duplicate_order_ids' ) : [];

        if ( empty( $orders ) ) {
            $is_legacy_dashboard_page = get_transient( 'dokan_legacy_dashboard_page' );
            $dashboard_page_slug      = $is_legacy_dashboard_page ? 'dokan' : 'dokan-dashboard';
            $dashboard_url            = admin_url( 'admin.php?page=' . $dashboard_page_slug );

            $message = count( $duplicate_orders )
                ? __( 'All orders are checked and we found some duplicate orders.', 'dokan' )
                : __( 'All orders are checked and no duplicate was found.', 'dokan' );

            $data = [
                'offset'        => 0,
                'done'          => 'All',
                'message'       => $message,
                'dashboard_url' => $dashboard_url,
            ];

            if ( count( $duplicate_orders ) ) {
                $data['duplicate'] = true;
            }

            return $data;
        }

        foreach ( $orders as $order_id ) {
            $sellers_count = count( dokan_get_sellers_by( $order_id ) );
            $sub_order_ids = dokan_get_suborder_ids_by( $order_id );

            if ( $sellers_count < count( $sub_order_ids ) ) {
                $duplicate_orders = array_merge( array_slice( $sub_order_ids, $sellers_count ), $duplicate_orders );
            }
        }

        if ( count( $duplicate_orders ) ) {
            $session->set( 'dokan_duplicate_order_ids', $duplicate_orders );
        }

        $done = $prev_done + count( $orders );

        return [
            'offset'       => $offset + 1,
            'total_orders' => $total_orders,
            'done'         => $done,
            // translators: %1$d: done orders, %2$d: total orders
            'message'      => sprintf( __( '%1$d orders checked out of %2$d', 'dokan' ), $done, $total_orders ),
        ];
    }

    /**
     * Queue background process to rewrite product variations author IDs.
     *
     * @param int $page
     *
     * @return array|WP_Error
     */
    public function rewrite_product_variations_author( $page = 1 ) {
        $page         = ! empty( $page ) ? absint( $page ) : 1;
        $bg_processor = dokan()->bg_process->rewrite_variable_products_author;

        $args = [
            'updating' => 'dokan_update_variable_product_variations_author_ids',
            'page'     => $page,
        ];

        $bg_processor->push_to_queue( $args )->save()->dispatch();

        return [
            'process' => 'running',
            'message' => __( 'Variable product variations author ids rewriting queued successfully', 'dokan' ),
        ];
    }

    /**
     * Get distance between two addresses using Google Distance Matrix API.
     * Performs module and API key checks as part of business logic.
     *
     * @param string $address1
     * @param string $address2
     *
     * @return array|WP_Error
     */
    public function get_distance_btwn_address( $address1, $address2 ) {
        $address1 = (string) $address1;
        $address2 = (string) $address2;

        // check if module active
        if ( ! dokan_pro()->module->is_active( 'table_rate_shipping' ) ) {
            return new WP_Error( 'forbidden', __( 'Table Rate Shipping module is not active', 'dokan' ), [ 'status' => 403 ] );
        }

        if ( empty( $address1 ) ) {
            return new WP_Error( 'invalid_address1', __( 'Address 1 is empty', 'dokan' ), [ 'status' => 400 ] );
        }

        if ( empty( $address2 ) ) {
            return new WP_Error( 'invalid_address2', __( 'Address 2 is empty', 'dokan' ), [ 'status' => 400 ] );
        }

        // check if API key is set
        $gmap_api_key = trim( dokan_get_option( 'gmap_api_key', 'dokan_appearance', '' ) );
        if ( empty( $gmap_api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'Google Map API key is not set', 'dokan' ), [ 'status' => 403 ] );
        }

        $api      = new DokanGoogleDistanceMatrixAPI( $gmap_api_key, false );
        $distance = $api->get_distance( $address1, $address2, false );

        if ( isset( $distance->status ) && 'OK' === $distance->status ) {
            return [ 'message' => __( 'Distance Matrix API is enabled.', 'dokan' ) ];
        }

        return new WP_Error(
            'distance_error',
            __( 'Distance Matrix API check failed.', 'dokan' ),
            [
                'status'        => 400,
                'error_code'    => isset( $distance->status ) ? $distance->status : 'UNKNOWN',
                'error_message' => isset( $distance->error_message ) ? $distance->error_message : 'N/A',
            ]
        );
    }

    /**
     * Create Dokan default pages if needed.
     * Logic extracted from Admin::create_default_pages but refactored to return data.
     *
     * @return array|WP_Error
     */
    public function create_default_pages() {
        $page_created = get_option( 'dokan_pages_created', false );

        $pages = [
            [
                'post_title' => __( 'Dashboard', 'dokan' ),
                'slug'       => 'dashboard',
                'page_id'    => 'dashboard',
                'content'    => '[dokan-dashboard]',
            ],
            [
                'post_title' => __( 'Store List', 'dokan' ),
                'slug'       => 'store-listing',
                'page_id'    => 'store_listing',
                'content'    => '[dokan-stores]',
            ],
            [
                'post_title' => __( 'My Orders', 'dokan' ),
                'slug'       => 'my-orders',
                'page_id'    => 'my_orders',
                'content'    => '[dokan-my-orders]',
            ],
            [
                'post_title' => __( 'Vendor Onboarding', 'dokan' ),
                'slug'       => 'vendor-onboarding',
                'page_id'    => 'vendor_onboarding',
                'content'    => '[dokan-vendor-onboarding-registration]',
            ],
        ];

        $dokan_pages = [];

        if ( ! $page_created ) {
            $old_pages = get_option( 'dokan_pages', [] );

            foreach ( $pages as $page ) {
                if ( in_array( $page['page_id'], array_keys( $old_pages ), true ) ) {
                    $dokan_pages[ $page['page_id'] ] = $old_pages[ $page['page_id'] ];
                    continue;
                }

                $page_id = wp_insert_post(
                    [
                        'post_title'     => $page['post_title'],
                        'post_name'      => $page['slug'],
                        'post_content'   => $page['content'],
                        'post_status'    => 'publish',
                        'post_type'      => 'page',
                        'comment_status' => 'closed',
                    ]
                );
                $dokan_pages[ $page['page_id'] ] = $page_id;
            }

            update_option( 'dokan_pages', $dokan_pages );
            flush_rewrite_rules();
        } else {
            foreach ( $pages as $page ) {
                if ( ! Admin::dokan_page_exist( $page['slug'] ) && ! Admin::dokan_is_post_slug_exists( $page['slug'] ) ) {
                    $page_id = wp_insert_post(
                        [
                            'post_title'     => $page['post_title'],
                            'post_name'      => $page['slug'],
                            'post_content'   => $page['content'],
                            'post_status'    => 'publish',
                            'post_type'      => 'page',
                            'comment_status' => 'closed',
                        ]
                    );
                    $dokan_pages[ $page['page_id'] ] = $page_id;
                    update_option( 'dokan_pages', $dokan_pages );
                }
            }

            flush_rewrite_rules();
        }

        update_option( 'dokan_pages_created', 1 );

        return [
            'message' => __( 'All the default pages has been created!', 'dokan' ),
        ];
    }

    /**
     * Check if all Dokan pages are created.
     *
     * @return array
     */
    public function check_all_dokan_pages_exists() {
        $all_pages_created = get_option( 'dokan_pages_created', false );

        return [
            'all_pages_exists' => (bool) $all_pages_created,
        ];
    }
}
