<?php

namespace WeDevs\DokanPro\REST;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WeDevs\Dokan\REST\DokanBaseAdminController;
use WeDevs\Dokan\Models\DataStore\VendorOrderStatsStore;

/**
 * Admin Report Logs Controller
 *
 * Collects report logs data from dokan_order_stats table.
 *
 * @since 5.0.0
 *
 * @package dokan
 */
class AdminReportLogsController extends DokanBaseAdminController {

    /**
     * Route base.
     *
     * @var string
     */
    protected $base = 'report-logs';

    /**
     * Vendor Order Stats Store instance.
     *
     * @var VendorOrderStatsStore
     */
    protected $store;

    /**
     * Constructor.
     *
     * @since 5.0.0
     */
    public function __construct() {
        $this->store = new VendorOrderStatsStore();
    }

    /**
     * Register all routes related with report logs.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace, '/' . $this->base, [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_report_logs' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => array_merge(
                        $this->get_collection_params(),
                        $this->get_report_logs_params()
                    ),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
    }

    /**
     * Get the column names for export.
     *
     * @since 5.0.0
     *
     * @return array Key value pair of Column ID => Label.
     */
    public function get_export_columns() {
        return apply_filters(
            'dokan_admin_report_logs_export_columns',
            [
                'order_id'             => esc_html__( 'Order ID', 'dokan' ),
                'vendor_id'            => esc_html__( 'Vendor ID', 'dokan' ),
                'vendor_name'          => esc_html__( 'Vendor Name', 'dokan' ),
                'previous_order_total' => esc_html__( 'Previous Order Total', 'dokan' ),
                'order_total'          => esc_html__( 'Order Total', 'dokan' ),
                'vendor_earning'       => esc_html__( 'Vendor Earning', 'dokan' ),
                'commission'           => esc_html__( 'Commission', 'dokan' ),
                'gateway_fee'          => esc_html__( 'Gateway Fee', 'dokan' ),
                'gateway_fee_paid_by'  => esc_html__( 'Gateway Fee Paid By', 'dokan' ),
                'shipping'             => esc_html__( 'Shipping', 'dokan' ),
                'tax'                  => esc_html__( 'Tax', 'dokan' ),
                'status'               => esc_html__( 'Status', 'dokan' ),
                'date'                 => esc_html__( 'Date', 'dokan' ),
            ]
        );
    }

    /**
     * Get the column values for export.
     *
     * @since 5.0.0
     *
     * @param array $item Single report item/row.
     *
     * @return array Key value pair of Column ID => Value.
     */
    public function prepare_item_for_export( $item ) {
        return apply_filters(
            'dokan_admin_report_logs_prepare_item_for_export',
            [
                'order_id'             => $item['order_id'],
                'vendor_id'            => $item['vendor_id'],
                'vendor_name'          => ! empty( $item['vendor_name'] ) ? $item['vendor_name'] : esc_html__( 'MarketPlace', 'dokan' ),
                'previous_order_total' => $item['order_total'], // Assuming this is what's meant by previous order total in this context
                'order_total'          => (float) $item['order_total'] - (float) $item['total_refunded'],
                'vendor_earning'       => $item['vendor_earning'],
                'commission'           => $item['admin_commission'],
                'gateway_fee'          => (float) $item['vendor_gateway_fee'] + (float) $item['admin_gateway_fee'],
                'gateway_fee_paid_by'  => (float) $item['admin_gateway_fee'] > 0 ? esc_html__( 'Admin', 'dokan' ) : esc_html__( 'Vendor', 'dokan' ),
                'shipping'             => (float) $item['vendor_shipping_fee'] + (float) $item['admin_shipping_fee'],
                'tax'                  => (float) $item['vendor_order_tax'] + (float) $item['admin_order_tax'] + (float) $item['vendor_shipping_tax'] + (float) $item['admin_shipping_tax'],
                'status'               => $item['status'],
                'date'                 => $item['date'],
            ]
        );
    }

    /**
     * Retrieves the query params for report logs.
     *
     * @since 5.0.0
     *
     * @return array Query parameters for the collection.
     */
    public function get_report_logs_params() {
        return apply_filters(
            'dokan_admin_report_logs_query_params',
            [
                'vendor_id'    => [
                    'description'       => __( 'Vendor ID to filter by.', 'dokan' ),
                    'type'              => 'integer',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'absint',
                ],
                'order_id'     => [
                    'description'       => __( 'Order ID to filter by.', 'dokan' ),
                    'type'              => 'integer',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'absint',
                ],
                'order_status' => [
                    'description'       => __( 'Order status to filter by.', 'dokan' ),
                    'required'          => false,
                    'type'              => 'string',
                    'default'           => '',
                    'enum'              => array_merge( [ '' ], array_keys( wc_get_order_statuses() ) ),
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'orderby'      => [
                    'description' => __( 'Column to order by.', 'dokan' ),
                    'required'    => false,
                    'type'        => 'string',
                    'default'     => 'order_id',
                    'enum'        => [ 'order_id', 'vendor_id', 'vendor_earning', 'admin_commission', 'admin_earning' ],
                ],
                'order'        => [
                    'description' => __( 'Order direction.', 'dokan' ),
                    'required'    => false,
                    'type'        => 'string',
                    'enum'        => [ 'desc', 'asc' ],
                    'default'     => 'desc',
                ],
                'start_date'   => [
                    'description'       => __( 'Start date for filtering (Y-m-d).', 'dokan' ),
                    'type'              => 'string',
                    'format'            => 'date',
                    'required'          => false,
                    'default'           => '',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'end_date'     => [
                    'description'       => __( 'End date for filtering (Y-m-d).', 'dokan' ),
                    'type'              => 'string',
                    'format'            => 'date',
                    'required'          => false,
                    'default'           => '',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ]
        );
    }

    /**
     * Get query args from request parameters.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return array Query arguments.
     */
    protected function get_query_args( WP_REST_Request $request ): array {
        return apply_filters(
            'dokan_admin_report_logs_query_args',
            [
                'exclude_statuses' => true,
                'vendor_id'        => $request->get_param( 'vendor_id' ),
                'order_id'         => $request->get_param( 'order_id' ),
                'order_status'     => sanitize_text_field( $request->get_param( 'order_status' ) ?? '' ),
                'orderby'          => sanitize_text_field( $request->get_param( 'orderby' ) ?? 'order_id' ),
                'order'            => sanitize_text_field( $request->get_param( 'order' ) ?? 'desc' ),
                'start_date'       => sanitize_text_field( $request->get_param( 'start_date' ) ?? '' ),
                'end_date'         => sanitize_text_field( $request->get_param( 'end_date' ) ?? '' ),
                'per_page'         => absint( $request->get_param( 'per_page' ) ?? 10 ),
                'page'             => absint( $request->get_param( 'page' ) ?? 1 ),
            ]
        );
    }

    /**
     * Get report logs from dokan_order_stats table.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function get_report_logs( WP_REST_Request $request ) {
        $args = $this->get_query_args( $request );

        $total_items = $this->store->get_report_count( $args );
        $results     = $this->store->get_report_data( $args );
        $logs        = $this->prepare_logs_for_response( $results );

        $response = rest_ensure_response( $logs );
        $response = $this->format_collection_response( $response, $request, $total_items );

        return $response;
    }

    /**
     * Prepare log items for response.
     *
     * Processes all raw data from dokan_order_stats and wc_order_stats
     * and prepares the complete response.
     *
     * @since 5.0.0
     *
     * @param array $results Raw database results.
     *
     * @return array Prepared log items.
     */
    protected function prepare_logs_for_response( $results ) {
        if ( empty( $results ) ) {
            return [];
        }

        $logs     = [];
        $statuses = wc_get_order_statuses();
        $dp       = wc_get_price_decimals();

        // Collect all order IDs to fetch refunds for them.
        $order_ids  = array_column( $results, 'order_id' );
        $refunds    = $this->store->get_refund_data( $order_ids );
        $orders_map = $this->prefetch_orders_and_vendors( $results );

        foreach ( $results as $row ) {
            $order = $orders_map[ $row->order_id ] ?? null;

            if ( ! $order ) {
                continue;
            }

            $order_status = $order->get_status();
            $status_key   = 'wc-' . $order_status;
            $status_label = $statuses[ $status_key ] ?? $order_status;

            $vendor_name = '';
            if ( ! empty( $row->vendor_id ) ) {
                $vendor      = dokan()->vendor->get( $row->vendor_id );
                $vendor_name = $vendor ? $vendor->get_shop_name() : '';
            }

            // Order total from wc_order_stats total_sales.
            $order_total    = (float) ( $row->order_total ?? $order->get_total() );
            $total_refunded = $refunds[ $row->order_id ] ?? 0;

            $logs[] = apply_filters(
                'dokan_admin_report_logs_prepare_item_for_response',
                [
                    'id'                  => (int) $row->order_id,
                    'order_id'            => (int) $row->order_id,
                    'vendor_id'           => (int) $row->vendor_id,
                    'order_type'          => (int) $row->order_type,
                    'vendor_name'         => $vendor_name,
                    'order_total'         => wc_format_decimal( $order_total, $dp ),
                    'total_refunded'      => wc_format_decimal( $total_refunded, $dp ),
                    'vendor_earning'      => wc_format_decimal( $row->vendor_earning, $dp ),
                    'vendor_gateway_fee'  => wc_format_decimal( $row->vendor_gateway_fee, $dp ),
                    'vendor_shipping_fee' => wc_format_decimal( $row->vendor_shipping_fee, $dp ),
                    'vendor_discount'     => wc_format_decimal( $row->vendor_discount, $dp ),
                    'vendor_shipping_tax' => wc_format_decimal( $row->vendor_shipping_tax ?? 0, $dp ),
                    'vendor_order_tax'    => wc_format_decimal( $row->vendor_order_tax ?? 0, $dp ),
                    'admin_earning'       => wc_format_decimal( $row->admin_earning ?? 0, $dp ),
                    'admin_commission'    => wc_format_decimal( $row->admin_commission, $dp ),
                    'admin_gateway_fee'   => wc_format_decimal( $row->admin_gateway_fee, $dp ),
                    'admin_shipping_fee'  => wc_format_decimal( $row->admin_shipping_fee, $dp ),
                    'admin_discount'      => wc_format_decimal( $row->admin_discount, $dp ),
                    'admin_shipping_tax'  => wc_format_decimal( $row->admin_shipping_tax ?? 0, $dp ),
                    'admin_order_tax'     => wc_format_decimal( $row->admin_order_tax ?? 0, $dp ),
                    'admin_subsidy'       => wc_format_decimal( $row->admin_subsidy, $dp ),
                    'status'              => $status_label,
                    'order_status'        => $order_status,
                    'date'                => $row->order_date ?? ( $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '' ),
                ],
                $row,
                $order
            );
        }

        return apply_filters(
            'dokan_admin_report_logs_prepare_items',
            $logs,
            $results
        );
    }

    /**
     * Batch-fetch orders and prime vendor cache for a set of results.
     *
     * Prevents N+1 queries by loading all orders in one call
     * and pre-caching all vendor user data.
     *
     * @since 5.0.0
     *
     * @param array $results Raw database results with order_id and vendor_id.
     *
     * @return array<int, \WC_Order> Map of order_id => WC_Order.
     */
    protected function prefetch_orders_and_vendors( array $results ): array {
        $order_ids  = wp_list_pluck( $results, 'order_id' );
        $orders_map = [];

        if ( ! empty( $order_ids ) ) {
            $fetched_orders = wc_get_orders(
                [
                    'post__in' => array_map( 'absint', $order_ids ),
                    'limit'    => -1,
                ]
            );

            foreach ( $fetched_orders as $order ) {
                $orders_map[ $order->get_id() ] = $order;
            }
        }

        $vendor_ids = array_unique( array_filter( wp_list_pluck( $results, 'vendor_id' ) ) );

        if ( ! empty( $vendor_ids ) ) {
            cache_users( array_map( 'absint', $vendor_ids ) );
        }

        return $orders_map;
    }

    /**
     * Get the item schema.
     *
     * @since 5.0.0
     *
     * @return array
     */
    public function get_item_schema() {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'report-log',
            'type'       => 'object',
            'properties' => [
                'id'                   => [
                    'description' => esc_html__( 'Unique identifier for the log entry.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'order_id'             => [
                    'description' => esc_html__( 'Order ID.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                ],
                'vendor_id'            => [
                    'description' => esc_html__( 'Vendor ID.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                ],
                'vendor_name'          => [
                    'description' => esc_html__( 'Vendor shop name.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'order_total'          => [
                    'description' => esc_html__( 'Order total from wc_order_stats total_sales.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'total_refunded'       => [
                    'description' => esc_html__( 'Total refunded amount for the order.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'vendor_earning'       => [
                    'description' => esc_html__( 'Vendor earning.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'order_type'           => [
                    'description' => esc_html__( 'Order type.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                ],
                'vendor_gateway_fee'   => [
                    'description' => esc_html__( 'Vendor gateway fee.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'vendor_shipping_fee'  => [
                    'description' => esc_html__( 'Vendor shipping fee.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'vendor_discount'      => [
                    'description' => esc_html__( 'Vendor discount.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'vendor_shipping_tax'  => [
                    'description' => esc_html__( 'Vendor shipping tax.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'vendor_order_tax'     => [
                    'description' => esc_html__( 'Vendor order tax.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'admin_earning'        => [
                    'description' => esc_html__( 'Admin earning.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'admin_commission'     => [
                    'description' => esc_html__( 'Admin commission.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'admin_gateway_fee'    => [
                    'description' => esc_html__( 'Admin gateway fee.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'admin_shipping_fee'   => [
                    'description' => esc_html__( 'Admin shipping fee.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'admin_discount'       => [
                    'description' => esc_html__( 'Admin discount.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'admin_shipping_tax'   => [
                    'description' => esc_html__( 'Admin shipping tax.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'admin_order_tax'      => [
                    'description' => esc_html__( 'Admin order tax.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'admin_subsidy'        => [
                    'description' => esc_html__( 'Admin subsidy.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'status'               => [
                    'description' => esc_html__( 'Order status label.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'order_status'         => [
                    'description' => esc_html__( 'Order status key.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'date'                 => [
                    'description' => esc_html__( 'Order creation date.', 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => [ 'view' ],
                ],
            ],
        ];
    }
}
