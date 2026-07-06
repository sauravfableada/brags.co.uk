<?php

namespace WeDevs\DokanPro\REST;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class ReportsController extends WP_REST_Controller {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'dokan/v1';

    /**
     * Route name
     *
     * @var string
     */
    protected $base = 'reports';

    /**
     * Register all routes related with reports
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->base . '/sales_overview', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_sales_overview' ],
                'permission_callback' => [ $this, 'check_sales_overview_permission' ],
                'args'                => $this->get_collection_params(),
            ],
        ] );

        register_rest_route( $this->namespace, '/' . $this->base . '/top_selling', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_top_selling' ],
                'permission_callback' => [ $this, 'check_top_selling_permission' ],
                'args'                => $this->get_collection_params(),
            ],
        ] );

        register_rest_route( $this->namespace, '/' . $this->base . '/top_earners', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_top_earners' ],
                'permission_callback' => [ $this, 'check_top_earners_permission' ],
                'args'                => $this->get_collection_params(),
            ],
        ] );

        register_rest_route( $this->namespace, '/' . $this->base . '/summary', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_report_summary' ],
                'permission_callback' => [ $this, 'check_report_summary_permission' ],
                'args'                => $this->get_collection_params(),
            ],
        ] );
    }

    /**
     * Check permission to view this report
     *
     * @since 2.8.0
     *
     * @param WP_REST_Request $request
     *
     * @return bool
     */
    public function check_sales_overview_permission( $request ) {
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        if ( ! current_user_can( 'dokan_view_overview_report' ) ) {
            return false;
        }

        if ( isset( $request['vendor_id'] ) && dokan_get_current_user_id() !== (int) $request['id'] ) {
            return false;
        }

        return true;
    }

    /**
     * Check permission to view this top_selling
     *
     * @since 2.8.0
     *
     * @return bool
     */
    public function check_top_selling_permission() {
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        if ( ! current_user_can( 'dokan_view_top_selling_report' ) ) {
            return false;
        }

        if ( isset( $request['vendor_id'] ) && dokan_get_current_user_id() !== (int) $request['id'] ) {
            return false;
        }

        return true;
    }

    /**
     * Check permission to view this top_selling
     *
     * @since 2.8.0
     *
     * @return bool
     */
    public function check_top_earners_permission() {
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        if ( ! current_user_can( 'dokan_view_top_earning_report' ) ) {
            return false;
        }

        if ( isset( $request['vendor_id'] ) && dokan_get_current_user_id() !== (int) $request['id'] ) {
            return false;
        }

        return true;
    }

    /**
     * Check permission to view this top_selling
     *
     * @since 2.8.0
     *
     * @return bool
     */
    public function check_report_summary_permission() {
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        if ( ! current_user_can( 'dokan_view_sales_overview' ) ) {
            return false;
        }

        if ( isset( $request['vendor_id'] ) && dokan_get_current_user_id() !== (int) $request['id'] ) {
            return false;
        }

        return true;
    }

    /**
     * Get report summary
     *
     * @since 2.8.0
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     */
    public function get_report_summary( $request ) {
        $seller_id = $request['vendor_id'] ? absint( $request['vendor_id'] ) : dokan_get_current_user_id();

        $data = [
            'pageviews'      => (int) dokan_author_pageviews( $seller_id ),
            'orders_count'   => dokan_count_orders( $seller_id ),
            'sales'          => dokan_author_total_sales( $seller_id ),
            'seller_balance' => dokan_get_seller_earnings( $seller_id ),
        ];

        return rest_ensure_response( $data );
    }

    /**
     * Get report data for Sales Overview
     *
     * @since 2.8.0
     * @since 3.8.0 rewritten whole method
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     */
    public function get_sales_overview( $request ) {
        $seller_id = $request['vendor_id'] ? absint( $request['vendor_id'] ) : dokan_get_current_user_id();
        if ( isset( $request['vendor_id'] ) && ! current_user_can( 'manage_options' ) ) {
            // prevent non-admins from viewing another seller's data
            $seller_id = dokan_get_current_user_id();
        }

        $start_date = dokan_current_datetime()->modify( $request['start_date'] );
        if ( ! $start_date ) {
            $start_date = dokan_current_datetime()->modify( 'first day of this month' );
        }

        $end_date = dokan_current_datetime()->modify( $request['end_date'] );
        if ( ! $end_date ) {
            $end_date = dokan_current_datetime()->modify( 'midnight' )->getTimestamp();
        }

        $sales_by_date                = new \WeDevs\DokanPro\Reports\SalesByDate();
        $sales_by_date->current_range = 'custom';
        $sales_by_date->start_date    = $start_date->getTimestamp();
        $sales_by_date->end_date      = $end_date->getTimestamp();
        $data                         = $sales_by_date->get_report_data( $seller_id );

        return rest_ensure_response( $data );
    }

    /**
     * Get report data for Top Selling products
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     */
    public function get_top_selling( $request ) {
        $seller_id = $request['vendor_id'] ? absint( $request['vendor_id'] ) : dokan_get_current_user_id();
        if ( isset( $request['vendor_id'] ) && ! current_user_can( 'manage_options' ) ) {
            // prevent non-admins from viewing another seller's data
            $seller_id = dokan_get_current_user_id();
        }

        $report_manager = new \WeDevs\DokanPro\Reports\Manager();
        $data           = $report_manager->get_top_selling_data( $seller_id, $request['start_date'], $request['end_date'] );

        return rest_ensure_response( $data );
    }

    /**
     * Get report data for Top Earning products
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     */
    public function get_top_earners( $request ) {
        $seller_id = $request['vendor_id'] ? absint( $request['vendor_id'] ) : dokan_get_current_user_id();
        if ( isset( $request['vendor_id'] ) && ! current_user_can( 'manage_options' ) ) {
            // prevent non-admins from viewing another seller's data
            $seller_id = dokan_get_current_user_id();
        }

        $report_manager = new \WeDevs\DokanPro\Reports\Manager();
        $data           = $report_manager->get_top_earners_data( $seller_id, $request['start_date'], $request['end_date'] );

        return rest_ensure_response( $data );
    }

    /**
     * Get collection params
     *
     * @return array
     */
    public function get_collection_params() {
        return [
            'vendor_id'  => [
                'description'       => __( 'ID of the Store', 'dokan' ),
                'type'              => 'integer',
                'context'           => [ 'view' ],
                'default'           => dokan_get_current_user_id(),
                'sanitize_callback' => 'absint',
                'validate_callback' => 'dokan_rest_validate_store_id',
            ],
            'start_date' => [
                'type'              => 'string',
                'format'            => 'date-time',
                'default'           => dokan_current_datetime()->format( 'Y-m-01' ),
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'end_date'   => [
                'type'              => 'string',
                'format'            => 'date-time',
                'default'           => dokan_current_datetime()->modify( 'midnight' )->format( 'Y-m-d' ),
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }
}
