<?php

namespace WeDevs\DokanPro\REST;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WeDevs\Dokan\REST\DokanBaseAdminController;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\Query as OrdersStatsQuery;

/**
 * Admin Report Stats Controller
 *
 * @since 5.0.0
 *
 * @package dokan
 */
class AdminReportStatsController extends DokanBaseAdminController {

    /**
     * Route base.
     *
     * @var string
     */
    protected $base = 'report-stats';

    /**
     * Register all routes related with report stats.
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
                    'args'                => $this->get_summary_params(),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace, '/' . $this->base . '/overview', [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_overview' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => $this->get_overview_params(),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
    }

    /**
     * Get summary data.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response
     */
    public function get_summary( $request ) {
        $from        = $request->get_param( 'from' ) ?? null;
        $to          = $request->get_param( 'to' ) ?? null;
        $seller_id   = (int) $request->get_param( 'seller_id' );
        $filter_type = $request->get_param( 'filter_type' ) ?? 'by-day';
        $date_range  = $this->parse_date_range( $from, $to, $filter_type );

        $data = [
            'start_date'  => $date_range['current_month_start'],
            'end_date'    => $date_range['current_month_end'],
            'products'    => $this->get_product_data( $from, $to, $seller_id ),
            'withdraw'    => $this->get_withdraw_data(),
            'vendors'     => $this->get_vendor_data( $from, $to, $seller_id ),
            'net_revenue' => [
                'this_month' => $this->get_net_sales( $date_range['current_month_start_date'], $date_range['current_month_end_date'], $seller_id ),
                'last_month' => $this->get_net_sales( $date_range['previous_month_start_date'], $date_range['previous_month_end_date'], $seller_id ),
            ],
            'order_count' => [
                'this_month' => $this->get_order_count( $date_range['current_month_start_date'], $date_range['current_month_end_date'], $seller_id ),
                'last_month' => $this->get_order_count( $date_range['previous_month_start_date'], $date_range['previous_month_end_date'], $seller_id ),
            ],
            'commissions' => [
                'this_month' => $this->get_commission_earned( $date_range['current_month_start_date'], $date_range['current_month_end_date'], $seller_id ),
                'last_month' => $this->get_commission_earned( $date_range['previous_month_start_date'], $date_range['previous_month_end_date'], $seller_id ),
            ],
        ];

        return rest_ensure_response(
            apply_filters(
                'dokan_admin_report_stats_summary',
                $data,
                $request
            )
        );
    }

    /**
     * Get overview chart data.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response
     */
    public function get_overview( $request ) {
        $from        = $request->get_param( 'from' ) ?? null;
        $to          = $request->get_param( 'to' ) ?? null;
        $seller_id   = (int) $request->get_param( 'seller_id' );
        $filter_type = $request->get_param( 'filter_type' ) ?? 'by-day';
        $date_range  = $this->parse_date_range( $from, $to, $filter_type );

        $data = $this->get_overview_chart_data(
            $date_range['current_month_start'],
            $date_range['current_month_end'],
            $seller_id,
            $filter_type
        );

        $response = [
            'intervals' => apply_filters( 'dokan_admin_report_stats_overview_data', $data, $from, $to, $seller_id ),
        ];

        return rest_ensure_response( apply_filters( 'dokan_admin_report_stats_overview_response', $response, $request ) );
    }

    /**
     * Get the query params for the summary endpoint.
     *
     * @since 5.0.0
     *
     * @return array
     */
    public function get_summary_params() {
        return apply_filters(
            'dokan_admin_report_stats_summary_params',
            [
                'from' => [
                    'description'       => esc_html__( 'Start date for the report.', 'dokan' ),
                    'type'              => 'string',
                    'format'            => 'date',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'to' => [
                    'description'       => esc_html__( 'End date for the report.', 'dokan' ),
                    'type'              => 'string',
                    'format'            => 'date',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'seller_id' => [
                    'description'       => esc_html__( 'Seller ID to filter by.', 'dokan' ),
                    'type'              => 'integer',
                    'default'           => 0,
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'absint',
                ],
                'filter_type' => [
                    'description'       => esc_html__( 'Filter type for the report.', 'dokan' ),
                    'type'              => 'string',
                    'enum'              => [ 'by-day', 'by-year', 'by-vendor' ],
                    'default'           => 'by-day',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ]
        );
    }

    /**
     * Get the query params for the overview endpoint.
     *
     * @since 5.0.0
     *
     * @return array
     */
    public function get_overview_params() {
        return apply_filters(
            'dokan_admin_report_stats_overview_params',
            [
                'from' => [
                    'description'       => esc_html__( 'Start date for the overview.', 'dokan' ),
                    'type'              => 'string',
                    'format'            => 'date',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'to' => [
                    'description'       => esc_html__( 'End date for the overview.', 'dokan' ),
                    'type'              => 'string',
                    'format'            => 'date',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'seller_id' => [
                    'description'       => esc_html__( 'Seller ID to filter by.', 'dokan' ),
                    'type'              => 'integer',
                    'default'           => 0,
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'absint',
                ],
                'filter_type' => [
                    'description'       => esc_html__( 'Filter type for the overview.', 'dokan' ),
                    'type'              => 'string',
                    'enum'              => [ 'by-day', 'by-year', 'by-vendor' ],
                    'default'           => 'by-day',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ]
        );
    }

    /**
     * Get net sales for a given date range.
     *
     * @since 5.0.0
     *
     * @param string $start_date Start date in Y-m-d format.
     * @param string $end_date   End date in Y-m-d format.
     * @param int    $seller_id  Seller ID. Optional.
     *
     * @return float Net sales amount.
     */
    public function get_net_sales( string $start_date, string $end_date, int $seller_id = 0 ): float {
        $query_args = [
            'before' => $this->format_date( $end_date, '23:59:59' ),
            'after'  => $this->format_date( $start_date ),
            'fields' => [
                'net_revenue',
            ],
        ];

        if ( ! empty( $seller_id ) ) {
            $query_args['sellers'] = $seller_id;
        }

        $query_args = apply_filters(
            'dokan_admin_report_stats_net_sales_args',
            $query_args,
            $start_date,
            $end_date,
            $seller_id
        );

        $net_sales = $this->get_stats_data( $query_args, 'net_revenue' );

        return apply_filters( 'dokan_admin_report_stats_net_sales', (float) $net_sales, $start_date, $end_date, $seller_id );
    }

    /**
     * Get commission earned for a given date range.
     *
     * @since 5.0.0
     *
     * @param string $start_date Start date in Y-m-d format.
     * @param string $end_date   End date in Y-m-d format.
     * @param int    $seller_id  Seller ID. Optional.
     *
     * @return float Commission earned amount.
     */
    public function get_commission_earned( string $start_date, string $end_date, int $seller_id = 0 ): float {
        $query_args = [
            'before' => $this->format_date( $end_date, '23:59:59' ),
            'after'  => $this->format_date( $start_date ),
            'fields' => [
                'net_revenue',
                'total_admin_commission',
            ],
        ];

        if ( ! empty( $seller_id ) ) {
            $query_args['sellers'] = $seller_id;
        }

        $query_args = apply_filters(
            'dokan_admin_report_stats_commission_earned_args',
            $query_args,
            $start_date,
            $end_date,
            $seller_id
        );

        $commission = $this->get_stats_data( $query_args, 'total_admin_commission' );

        return apply_filters( 'dokan_admin_report_stats_commission_earned', (float) $commission, $start_date, $end_date, $seller_id );
    }

    /**
     * Get stats data for a given query args and field.
     *
     * @since 5.0.0
     *
     * @param array  $query_args Query arguments.
     * @param string $field      Field name.
     *
     * @return float Stats data.
     */
    protected function get_stats_data( array $query_args, string $field ): float {
        try {
            if ( ! empty( $query_args['sellers'] ) ) {
                $_GET['sellers'] = $query_args['sellers'];
            }

            $data_store = new OrdersStatsQuery( $query_args );
            $stats_data = $data_store->get_data();

            if ( is_wp_error( $stats_data ) ) {
                return 0;
            }

            return (float) ( $stats_data->totals->{$field} ?? 0 );
        } catch ( \Exception $e ) {
            return 0;
        }
    }

    /**
     * Get an order count for a given date range.
     *
     * @since 5.0.0
     *
     * @param string $start_date Start date in Y-m-d format.
     * @param string $end_date   End date in Y-m-d format.
     * @param int    $seller_id  Seller ID. Optional.
     *
     * @return int Order count.
     */
    public function get_order_count( string $start_date, string $end_date, int $seller_id = 0 ): int {
        $query_args = [
            'before' => $this->format_date( $end_date, '23:59:59' ),
            'after'  => $this->format_date( $start_date ),
            'fields' => [ 'orders_count' ],
        ];

        if ( ! empty( $seller_id ) ) {
            $query_args['sellers'] = $seller_id;
        }

        $query_args = apply_filters(
            'dokan_admin_report_stats_order_count_args',
            $query_args,
            $start_date,
            $end_date,
            $seller_id
        );

        $orders_count = $this->get_stats_data( $query_args, 'orders_count' );

        return apply_filters(
            'dokan_admin_report_stats_orders_count',
            (int) ( $orders_count ?? 0 ),
            $start_date,
            $end_date,
            $seller_id
        );
    }

    /**
     * Get vendor count data.
     *
     * @since 5.0.0
     *
     * @param string|null $from      Start date. Optional.
     * @param string|null $to        End date. Optional.
     * @param int         $seller_id Seller ID. Optional.
     *
     * @return array Vendor count data.
     */
    public function get_vendor_data( $from = null, $to = null, $seller_id = 0 ): array {
        $seller_data = dokan_get_seller_count( $from, $to );

        return apply_filters(
            'dokan_admin_report_stats_vendor_data',
            [
                'this_month'  => $seller_data['this_month'] ?? 0,
                'last_month'  => $seller_data['last_month'] ?? 0,
                'this_period' => $seller_data['this_period'] ?? null,
                'inactive'    => $seller_data['inactive'] ?? 0,
                'parcent'     => $seller_data['parcent'] ?? '',
                'class'       => $seller_data['class'] ?? '',
            ],
            $from,
            $to,
            $seller_id
        );
    }

    /**
     * Get overview chart data for a given date range.
     *
     * @since 5.0.0
     *
     * @param string $start_date  Start date in Y-m-d format.
     * @param string $end_date    End date in Y-m-d format.
     * @param int    $seller_id   Seller ID. Optional.
     * @param string $filter_type Filter type. Optional.
     *
     * @return array Chart data rows.
     */
    public function get_overview_chart_data( string $start_date, string $end_date, int $seller_id = 0, string $filter_type = 'by-day' ): array {
        $query_args = apply_filters(
            'dokan_admin_report_stats_overview_chart_args',
            [
                'order'    => 'asc',
                'before'   => $this->format_date( $end_date, '23:59:59' ),
                'after'    => $this->format_date( $start_date ),
                'interval' => 'by-year' === $filter_type ? 'month' : 'day',
                'per_page' => 100,
                'fields'   => [
                    'total_sales',
                    'net_revenue',
                    'orders_count',
                    'total_admin_commission',
                ],
            ],
            $start_date,
            $end_date,
            $seller_id
        );

        // Add seller_id to query args if provided.
        if ( ! empty( $seller_id ) ) {
            $query_args['sellers'] = $seller_id;
        }

        $results = [];

        try {
            if ( ! empty( $query_args['sellers'] ) ) {
                $_GET['sellers'] = $query_args['sellers'];
            }

            $data_store = new OrdersStatsQuery( $query_args );
            $stats_data = $data_store->get_data();
            $intervals  = $stats_data->intervals ?? [];

            foreach ( $intervals as $interval_data ) {
                $subtotals = (array) ( $interval_data['subtotals'] ?? [] );
                $date      = dokan_current_datetime()->modify( $interval_data['date_start'] )->format( 'Y-m-d' );

                $results[] = [
                    'date'        => $date,
                    'net_revenue' => (float) ( $subtotals['net_revenue'] ?? 0 ),
                    'order_count' => (int) ( $subtotals['orders_count'] ?? 0 ),
                    'commissions' => (float) ( $subtotals['total_admin_commission'] ?? 0 ),
                ];
            }
        } catch ( \Exception $e ) {
            // Return empty results on error.
            dokan_log( $e->getMessage(), 'error' );
        }

        return apply_filters( 'dokan_admin_report_stats_overview_chart_data', $results, $start_date, $end_date, $seller_id );
    }

    /**
     * Get product count data.
     *
     * @since 5.0.0
     *
     * @param string|null $from      Start date. Optional.
     * @param string|null $to        End date. Optional.
     * @param int         $seller_id Seller ID. Optional.
     *
     * @return array Product count data.
     */
    public function get_product_data( $from = null, $to = null, $seller_id = 0 ): array {
        $product_data = dokan_get_product_count( $from, $to, $seller_id );

        /**
         * Filter product count data.
         *
         * @since 5.0.0
         *
         * @param array       $product_data Product count data.
         * @param string|null $from         Start date. Optional.
         * @param string|null $to           End date. Optional.
         * @param int         $seller_id    Seller ID. Optional.
         */
        return apply_filters(
            'dokan_admin_report_stats_product_data',
            [
                'this_month'  => $product_data['this_month'] ?? 0,
                'last_month'  => $product_data['last_month'] ?? 0,
                'this_period' => $product_data['this_period'] ?? null,
                'parcent'     => $product_data['parcent'] ?? '',
                'class'       => $product_data['class'] ?? '',
            ],
            $from,
            $to,
            $seller_id
        );
    }

    /**
     * Get pending withdrawal count data.
     *
     * @since 5.0.0
     *
     * @return array Withdraw data.
     */
    public function get_withdraw_data(): array {
        return apply_filters( 'dokan_admin_report_stats_withdraw_data', dokan_get_withdraw_count() );
    }

    /**
     * Format date for WC Report query.
     *
     * @since 5.0.0
     *
     * @param string $date Date string.
     * @param string $time Time to append if missing. Default '00:00:00'.
     *
     * @return string Formatted date.
     */
    protected function format_date( string $date, string $time = '00:00:00' ): string {
        return strpos( $date, ':' ) === false ? $date . ' ' . $time : $date;
    }

    /**
     * Parse date and return formatted date ranges
     *
     * @since 5.0.0
     *
     * @param string|null $from        Start date in Y-m-d format (optional)
     * @param string|null $to          End date in Y-m-d format (optional)
     * @param string      $filter_type Filter type (optional)
     *
     * @return array Array containing parsed date information
     */
    public function parse_date_range( $from = null, $to = null, $filter_type = 'by-day' ) {
        $current_time = dokan_current_datetime();

        $current_month_start = ! empty( $from ) ? $current_time->modify( $from )->format( 'Y-m-d' ) : $current_time->format( 'Y-m-01' );
        $current_month_end   = ! empty( $to ) ? $current_time->modify( $to )->format( 'Y-m-d' ) : $current_time->format( 'Y-m-d' );

        $start_date_obj = $current_time->modify( $current_month_start );
        $end_date_obj   = dokan_current_datetime()->modify( $current_month_end );

        if ( 'by-year' === $filter_type ) {
            $prev_start           = $start_date_obj->modify( '-1 year' );
            $previous_month_start = $prev_start->format( 'Y-m-d' );
            $previous_month_end   = $prev_start->modify( 'last day of december' )->format( 'Y-m-d' );
        } else {
            $prev_start           = $start_date_obj->modify( '-1 month' );
            $previous_month_start = $prev_start->format( 'Y-m-d' );
            $previous_month_end   = $prev_start->modify( 'last day of this month' )->format( 'Y-m-d' );
        }

        return [
            'current_month_start'       => $current_month_start,
            'current_month_end'         => $current_month_end,
            'previous_month_start'      => $previous_month_start,
            'previous_month_end'        => $previous_month_end,
            'current_month_start_date'  => $current_month_start . ' 00:00:00',
            'current_month_end_date'    => $current_month_end . ' 23:59:59',
            'previous_month_start_date' => $previous_month_start . ' 00:00:00',
            'previous_month_end_date'   => $previous_month_end . ' 23:59:59',
        ];
    }

    /**
     * Get the report stats schema, conforming to JSON Schema.
     *
     * @since 5.0.0
     *
     * @return array
     */
    public function get_item_schema() {
        if ( $this->schema ) {
            return $this->add_additional_fields_schema( $this->schema );
        }

        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'report-stats',
            'type'       => 'object',
            'properties' => [
                'start_date'  => [
                    'description' => esc_html__( 'Effective start date used for the report (Y-m-d).', 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'date',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'end_date'    => [
                    'description' => esc_html__( 'Effective end date used for the report (Y-m-d).', 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'date',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'products'    => [
                    'description' => esc_html__( 'Product count data.', 'dokan' ),
                    'type'        => 'object',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                    'properties'  => [
                        'this_month' => [
                            'description' => esc_html__( 'Product count for this month.', 'dokan' ),
                            'type'        => 'integer',
                        ],
                        'last_month' => [
                            'description' => esc_html__( 'Product count for last month.', 'dokan' ),
                            'type'        => 'integer',
                        ],
                    ],
                ],
                'vendors'     => [
                    'description' => esc_html__( 'Vendor count data.', 'dokan' ),
                    'type'        => 'object',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                    'properties'  => [
                        'this_month' => [
                            'description' => esc_html__( 'Vendor count for this month.', 'dokan' ),
                            'type'        => 'integer',
                        ],
                        'last_month' => [
                            'description' => esc_html__( 'Vendor count for last month.', 'dokan' ),
                            'type'        => 'integer',
                        ],
                        'inactive'   => [
                            'description' => esc_html__( 'Inactive vendor count.', 'dokan' ),
                            'type'        => 'integer',
                        ],
                    ],
                ],
                'withdraw'    => [
                    'description' => esc_html__( 'Withdraw data.', 'dokan' ),
                    'type'        => 'object',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                    'properties'  => [
                        'pending' => [
                            'description' => esc_html__( 'Pending withdraw count.', 'dokan' ),
                            'type'        => 'integer',
                        ],
                    ],
                ],
                'net_revenue' => [
                    'description' => esc_html__( 'Net revenue data.', 'dokan' ),
                    'type'        => 'object',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                    'properties'  => [
                        'this_month' => [
                            'description' => esc_html__( 'Net revenue for this month.', 'dokan' ),
                            'type'        => 'number',
                        ],
                        'last_month' => [
                            'description' => esc_html__( 'Net revenue for last month.', 'dokan' ),
                            'type'        => 'number',
                        ],
                    ],
                ],
                'order_count' => [
                    'description' => esc_html__( 'Order count data.', 'dokan' ),
                    'type'        => 'object',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                    'properties'  => [
                        'this_month' => [
                            'description' => esc_html__( 'Order count for this month.', 'dokan' ),
                            'type'        => 'integer',
                        ],
                        'last_month' => [
                            'description' => esc_html__( 'Order count for last month.', 'dokan' ),
                            'type'        => 'integer',
                        ],
                    ],
                ],
                'commissions' => [
                    'description' => esc_html__( 'Commissions earned data.', 'dokan' ),
                    'type'        => 'object',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                    'properties'  => [
                        'this_month' => [
                            'description' => esc_html__( 'Commissions earned for this month.', 'dokan' ),
                            'type'        => 'number',
                        ],
                        'last_month' => [
                            'description' => esc_html__( 'Commissions earned for last month.', 'dokan' ),
                            'type'        => 'number',
                        ],
                    ],
                ],
            ],
        ];

        $this->schema = $schema;

        return $this->add_additional_fields_schema( $this->schema );
    }
}
