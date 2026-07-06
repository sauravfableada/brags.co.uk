<?php

namespace WeDevs\DokanPro\REST;

use WeDevs\Dokan\Analytics\Reports\OrderType;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WeDevs\Dokan\REST\DokanBaseAdminController;
use WeDevs\Dokan\Models\DataStore\VendorOrderStatsStore;

/**
 * Admin Report Earnings Controller
 *
 * Provides earning summary and earning overview/reports data
 * from the dokan_order_stats table.
 *
 * @since 5.0.0
 *
 * @package dokan
 */
class AdminReportEarningsController extends DokanBaseAdminController {

    /**
     * Route base.
     *
     * @var string
     */
    protected $base = 'report-earnings';

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
     * Register all routes related with report earnings.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace, '/' . $this->base . '/summary', [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_summary' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => $this->get_earnings_params(),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace, '/' . $this->base, [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_earnings' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => array_merge(
                        $this->get_collection_params(),
                        $this->get_earnings_params()
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
            'dokan_admin_report_earnings_export_columns',
            [
                'order_id'     => esc_html__( 'Order ID', 'dokan' ),
                'vendor_id'    => esc_html__( 'Vendor ID', 'dokan' ),
                'vendor_name'  => esc_html__( 'Vendor Name', 'dokan' ),
                'date'         => esc_html__( 'Date', 'dokan' ),
                'earning_type' => esc_html__( 'Earning Type', 'dokan' ),
                'source'       => esc_html__( 'Source', 'dokan' ),
                'details'      => esc_html__( 'Details', 'dokan' ),
                'amount'       => esc_html__( 'Amount', 'dokan' ),
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
            'dokan_admin_report_earnings_export_row',
            [
                'order_id'     => $item['order_id'],
                'vendor_id'    => $item['vendor_id'],
                'vendor_name'  => ! empty( $item['vendor_name'] ) ? $item['vendor_name'] : esc_html__( 'MarketPlace', 'dokan' ),
                'date'         => $item['date'],
                'earning_type' => $item['earning_type'],
                'source'       => $item['source'],
                'details'      => wp_specialchars_decode( $item['details'] ),
                'amount'       => $item['amount'],
            ]
        );
    }

    /**
     * Get date filter parameters.
     *
     * @since 5.0.0
     *
     * @return array
     */
    protected function get_date_filter_params(): array {
        return [
            'start_date' => [
                'description'       => esc_html__( 'Start date for filtering (Y-m-d).', 'dokan' ),
                'type'              => 'string',
                'format'            => 'date',
                'required'          => false,
                'default'           => '',
                'validate_callback' => 'rest_validate_request_arg',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'end_date'   => [
                'description'       => esc_html__( 'End date for filtering (Y-m-d).', 'dokan' ),
                'type'              => 'string',
                'format'            => 'date',
                'required'          => false,
                'default'           => '',
                'validate_callback' => 'rest_validate_request_arg',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }

    /**
     * Get earnings endpoint parameters.
     *
     * @since 5.0.0
     *
     * @return array
     */
    protected function get_earnings_params(): array {
        return apply_filters(
            'dokan_admin_report_earnings_params',
            array_merge(
                $this->get_date_filter_params(),
                [
                    'vendor_id'    => [
                        'description'       => __( 'Vendor ID to filter by.', 'dokan' ),
                        'type'              => 'integer',
                        'validate_callback' => 'rest_validate_request_arg',
                        'sanitize_callback' => 'absint',
                    ],
                    'order_id'     => [
                        'description'       => __( 'Order ID to search by.', 'dokan' ),
                        'type'              => 'integer',
                        'validate_callback' => 'rest_validate_request_arg',
                        'sanitize_callback' => 'absint',
                    ],
                    'earning_type' => [
                        'description' => esc_html__( 'Earning type to filter by (commission, subscription, other_revenue).', 'dokan' ),
                        'required'    => false,
                        'type'        => 'string',
                        'default'     => '',
                        'enum'        => [ '', 'commission', 'subscription', 'other_revenue' ],
                    ],
                    'orderby'      => [
                        'description' => esc_html__( 'Column to order by.', 'dokan' ),
                        'required'    => false,
                        'type'        => 'string',
                        'default'     => 'order_id',
                        'enum'        => [ 'order_id', 'vendor_id', 'vendor_earning', 'admin_commission', 'admin_earning' ],
                    ],
                    'order'        => [
                        'description' => esc_html__( 'Order direction.', 'dokan' ),
                        'required'    => false,
                        'type'        => 'string',
                        'enum'        => [ 'desc', 'asc' ],
                        'default'     => 'desc',
                    ],
                ]
            )
        );
    }

    /**
     * Get earning summary data.
     *
     * Returns totals for admin earnings, commissions,
     * subscription, and other revenue.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response
     */
    public function get_summary( WP_REST_Request $request ) {
        $args         = [ 'exclude_statuses' => true ];
        $full_summary = $this->store->get_report_summary( $args );

        // Base summary contains only core fields; modules add their own via filter.
        $summary = [
            'total_earnings' => $full_summary['total_earnings'] ?? 0,
            'net_earning'    => $full_summary['net_earning'] ?? 0,
            'commission'     => $full_summary['commission'] ?? 0,
        ];

        return rest_ensure_response(
            apply_filters( 'dokan_admin_report_earnings_summary', $summary, $full_summary, $request )
        );
    }

    /**
     * Get paginated earnings data.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response
     */
    public function get_earnings( WP_REST_Request $request ) {
        $args = $this->get_query_args( $request );

        $total_items = $this->store->get_report_count( $args );
        $results     = $this->store->get_report_data( $args );
        $earnings    = $this->prepare_earnings_for_response( $results );

        $response = rest_ensure_response( $earnings );
        $response = $this->format_collection_response( $response, $request, $total_items );

        return $response;
    }

    /**
     * Get query args from request.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return array
     */
    protected function get_query_args( WP_REST_Request $request ): array {
        return apply_filters(
            'dokan_admin_report_earnings_query_args',
            [
                'exclude_statuses' => true,
                'vendor_id'        => $request->get_param( 'vendor_id' ),
                'order_id'         => $request->get_param( 'order_id' ),
                'earning_type'     => sanitize_text_field( $request->get_param( 'earning_type' ) ),
                'start_date'       => sanitize_text_field( $request->get_param( 'start_date' ) ),
                'end_date'         => sanitize_text_field( $request->get_param( 'end_date' ) ),
                'orderby'          => sanitize_text_field( $request->get_param( 'orderby' ) ?? 'order_id' ),
                'order'            => sanitize_text_field( $request->get_param( 'order' ) ?? 'desc' ),
                'per_page'         => absint( $request->get_param( 'per_page' ) ?? 10 ),
                'page'             => absint( $request->get_param( 'page' ) ?? 1 ),
            ]
        );
    }

    /**
     * Get the earning type label based on order_type.
     *
     * @since 5.0.0
     *
     * @param int $order_type The order type value.
     *
     * @return string Earning type label.
     */
    protected function get_earning_type_label( int $order_type ): string {
        $earning_type_labels = apply_filters(
            'dokan_admin_report_earnings_type_labels',
            [
                OrderType::DOKAN_SINGLE_ORDER                => esc_html__( 'Commission', 'dokan' ),
                OrderType::DOKAN_SUBORDER                    => esc_html__( 'Commission', 'dokan' ),
                OrderType::DOKAN_ADVERTISEMENT_PRODUCT_ORDER => esc_html__( 'Other Revenue', 'dokan' ),
                OrderType::DOKAN_ADVERTISEMENT_REFUND_ORDER  => esc_html__( 'Other Revenue', 'dokan' ),
                OrderType::DOKAN_SUBSCRIPTION_ORDER          => esc_html__( 'Subscription', 'dokan' ),
                OrderType::DOKAN_SUBSCRIPTION_REFUND_ORDER   => esc_html__( 'Subscription', 'dokan' ),
            ]
        );

        return $earning_type_labels[ $order_type ] ?? '';
    }

    /**
     * Get the source description for an earning entry.
     *
     * @since 5.0.0
     *
     * @param int $order_id The order ID.
     *
     * @return string Source description.
     */
    protected function get_earning_source( int $order_id ): string {
        /* translators: %s: order ID */
        return sprintf( esc_html__( 'Order #%s', 'dokan' ), $order_id );
    }

    /**
     * Get the detail description for an earning entry.
     *
     * @since 5.0.0
     *
     * @param int            $order_type  The order type value.
     * @param string         $vendor_name The vendor shop name.
     * @param object         $row         Raw database row.
     * @param \WC_Order|null $order       The order object (pre-fetched).
     *
     * @return string Detail description.
     */
    protected function get_earning_details( int $order_type, string $vendor_name, $row, $order = null ): string {
        if ( ! $order ) {
            $order = wc_get_order( $row->order_id ?? 0 );
        }

        $product_titles = $this->get_product_titles_by_order( $order );

        switch ( $order_type ) {
            case OrderType::DOKAN_SINGLE_ORDER:
            case OrderType::DOKAN_SUBORDER:
                // Sale of "product title 1, product title 2..." - Vendor Name
                return sprintf(
                    /* translators: 1: product titles, 2: vendor name */
                    esc_html__( 'Sale of "%1$s" %2$s', 'dokan' ),
                    $product_titles,
                    ! empty( $vendor_name ) ? "- {$vendor_name}" : ''
                );

            case OrderType::DOKAN_ADVERTISEMENT_PRODUCT_ORDER:
            case OrderType::DOKAN_ADVERTISEMENT_REFUND_ORDER:
                // Advertisement Product Title - Vendor Name
                return sprintf(
                    /* translators: 1: product titles */
                    esc_html__( '%1$s - Marketplace', 'dokan' ),
                    $product_titles
                );

            case OrderType::DOKAN_SUBSCRIPTION_ORDER:
            case OrderType::DOKAN_SUBSCRIPTION_REFUND_ORDER:
                // Subscription Product Title - Vendor Name
                return sprintf(
                    /* translators: 1: product titles */
                    esc_html__( '%1$s - Marketplace', 'dokan' ),
                    $product_titles
                );

            default:
                return apply_filters(
                    'dokan_admin_report_default_earnings_details',
                    esc_html__( '--', 'dokan' ),
                    $order_type,
                    $vendor_name,
                    $row
                );
        }
    }

    /**
     * Get product titles by order (including sub-orders).
     *
     * @since 5.0.0
     *
     * @param \WC_Order|bool $order The order object.
     *
     * @return string Comma separated product titles.
     */
    protected function get_product_titles_by_order( $order ) {
        if ( ! $order ) {
            return '';
        }

        $product_titles = [];

        // Check if it's a parent order with suborders.
        $sub_order_ids = dokan_get_suborder_ids_by( $order->get_id() );

        if ( ! empty( $sub_order_ids ) ) {
            foreach ( $sub_order_ids as $sub_order_id ) {
                $sub_order = wc_get_order( $sub_order_id );
                if ( $sub_order ) {
                    foreach ( $sub_order->get_items() as $item ) {
                        $product_titles[] = $item->get_name();
                    }
                }
            }
        } else {
            // It's a single order or a suborder itself.
            foreach ( $order->get_items() as $item ) {
                $product_titles[] = $item->get_name();
            }
        }

        return implode( ', ', array_unique( $product_titles ) );
    }

    /**
     * Get the earning amount based on order type.
     *
     * @since 5.0.0
     *
     * @param int    $order_type The order type value.
     * @param object $row        Raw database row.
     *
     * @return float
     */
    protected function get_earning_amount( int $order_type, $row ): float {
        return (float) $row->admin_earning;
    }

    /**
     * Prepare earning items for response.
     *
     * @since 5.0.0
     *
     * @param array $results Raw database results.
     *
     * @return array Prepared earning items.
     */
    protected function prepare_earnings_for_response( array $results ): array {
        if ( empty( $results ) ) {
            return [];
        }

        $orders_map = $this->prefetch_orders_and_vendors( $results );
        $earnings   = [];
        $dp         = wc_get_price_decimals();

        foreach ( $results as $row ) {
            $order = $orders_map[ $row->order_id ] ?? null;

            if ( ! $order ) {
                continue;
            }

            $order_type = (int) $row->order_type;

            $vendor_name = '';
            if ( ! empty( $row->vendor_id ) ) {
                $vendor      = dokan()->vendor->get( $row->vendor_id );
                $vendor_name = $vendor ? $vendor->get_shop_name() : '';
            }

            $date = $row->order_date ?? ( $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '' );

            $earnings[] = apply_filters(
                'dokan_admin_report_earnings_prepare_item_for_response',
                [
                    'id'               => (int) $row->order_id,
                    'order_id'         => (int) $row->order_id,
                    'vendor_id'        => (int) $row->vendor_id,
                    'order_type'       => $order_type,
                    'vendor_name'      => $vendor_name,
                    'date'             => $date,
                    'earning_type'     => $this->get_earning_type_label( $order_type ),
                    'source'           => $this->get_earning_source( (int) $row->order_id ),
                    'details'          => $this->get_earning_details( $order_type, $vendor_name, $row, $order ),
                    'amount'           => wc_format_decimal( $this->get_earning_amount( $order_type, $row ), $dp ),
                    'order_total'      => wc_format_decimal( isset( $row->order_total ) ? (float) $row->order_total : (float) $order->get_total(), $dp ),
                    'vendor_earning'   => wc_format_decimal( $row->vendor_earning, $dp ),
                    'admin_earning'    => wc_format_decimal( $row->admin_earning, $dp ),
                    'admin_commission' => wc_format_decimal( $row->admin_commission, $dp ),
                ],
                $row,
                $order,
                $order_type
            );
        }

        return apply_filters(
            'dokan_admin_report_earnings_prepare_items_for_response',
            $earnings,
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
            'title'      => 'report-earning',
            'type'       => 'object',
            'properties' => [
                'id'                 => [
                    'description' => esc_html__( 'Unique identifier for the earning entry.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'order_id'           => [
                    'description' => esc_html__( 'Order ID.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                ],
                'vendor_id'          => [
                    'description' => esc_html__( 'Vendor ID.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                ],
                'order_type'         => [
                    'description' => esc_html__( 'Order type.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                ],
                'vendor_name'        => [
                    'description' => esc_html__( 'Vendor shop name.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'date'               => [
                    'description' => esc_html__( 'Order creation date.', 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => [ 'view' ],
                ],
                'earning_type'       => [
                    'description' => esc_html__( 'Type of earning (Commission, Subscription, Other Revenue).', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'source'             => [
                    'description' => esc_html__( 'Source of the earning.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'details'            => [
                    'description' => esc_html__( 'Details about the earning.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'amount'             => [
                    'description' => esc_html__( 'Earning amount.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'order_total'        => [
                    'description' => esc_html__( 'Order total from wc_order_stats total_sales.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'vendor_earning'     => [
                    'description' => esc_html__( 'Vendor earning.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'admin_earning'      => [
                    'description' => esc_html__( 'Admin earning.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'admin_commission'   => [
                    'description' => esc_html__( 'Admin commission.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
            ],
        ];
    }
}
