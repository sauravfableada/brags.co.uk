<?php

namespace WeDevs\DokanPro\Reports;

use stdClass;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * SalesByDate class.
 *
 * @since 3.8.0
 */
class SalesByDate extends Manager {

    /**
     * Chart colors.
     *
     * @since 3.8.0
     *
     * @var array
     */
    public $chart_colours = [];

    /**
     * Chart Heading
     *
     * @since 3.8.0
     *
     * @var string|null
     */
    public $heading;

    /**
     * Show or hide chart legend.
     *
     * @since 3.8.0
     *
     * @var true
     */
    public $hide_sidebar;

    /**
     * Date range.
     *
     * @since 3.8.0
     *
     * @var string
     */
    public $current_range = 'custom';

    /**
     * The report data.
     *
     * @since 3.8.0
     *
     * @var stdClass
     */
    private $report_data;

    /**
     * Get report data.
     *
     * @since 3.8.0
     *
     * @param int|null $seller_id
     *
     * @return stdClass
     */
    public function get_report_data( $seller_id = null ) {
        if ( empty( $this->report_data ) ) {
            $this->calculate_current_range( $this->current_range );
            $this->query_report_data( $seller_id );
        }

        return $this->report_data;
    }

    /**
     * Get all data needed for this report and store in the class.
     *
     * @since 3.8.0
     *
     * @param int|null $seller_id
     *
     * @return void
     */
    private function query_report_data( $seller_id = null ) {
        $this->report_data            = new stdClass();
        $this->report_data->seller_id = $seller_id ?: dokan_get_current_user_id();

        $this->report_data->order_counts = (array) $this->get_order_report_data(
            [
                'data'         => [
                    'ID'        => [
                        'type'     => 'post_data',
                        'function' => 'COUNT',
                        'name'     => 'count',
                        'distinct' => true,
                    ],
                    'post_date' => [
                        'type'     => 'post_data',
                        'function' => '',
                        'name'     => 'post_date',
                    ],
                ],
                'where_meta'   => [
                    [
                        'meta_key'   => '_dokan_vendor_id',
                        'meta_value' => $this->report_data->seller_id,
                        'operator'   => '=',
                    ],
                ],
                'group_by'     => $this->group_by_query,
                'order_by'     => 'post_date ASC',
                'query_type'   => 'get_results',
                'filter_range' => true,
                'order_types'  => wc_get_order_types( 'order-count' ),
                'order_status' => [ 'completed', 'processing', 'on-hold', 'refunded' ],
            ]
        );

        $this->report_data->coupons = (array) $this->get_order_report_data(
            [
                'data'         => [
                    'order_item_name' => [
                        'type'     => 'order_item',
                        'function' => '',
                        'name'     => 'order_item_name',
                    ],
                    'discount_amount' => [
                        'type'            => 'order_item_meta',
                        'order_item_type' => 'coupon',
                        'function'        => 'SUM',
                        'name'            => 'discount_amount',
                    ],
                    'post_date'       => [
                        'type'     => 'post_data',
                        'function' => '',
                        'name'     => 'post_date',
                    ],
                ],
                'where'        => [
                    [
                        'key'      => 'order_items.order_item_type',
                        'value'    => 'coupon',
                        'operator' => '=',
                    ],
                ],
                'where_meta'   => [
                    [
                        'meta_key'   => '_dokan_vendor_id',
                        'meta_value' => $this->report_data->seller_id,
                        'operator'   => '=',
                    ],
                ],
                'group_by'     => $this->group_by_query . ', order_item_name',
                'order_by'     => 'post_date ASC',
                'query_type'   => 'get_results',
                'filter_range' => true,
                'order_types'  => wc_get_order_types( 'order-count' ),
                'order_status' => [ 'completed', 'processing', 'on-hold', 'refunded' ],
            ]
        );

        // All items from orders - even those refunded.
        $this->report_data->order_items = (array) $this->get_order_report_data(
            [
                'data'         => [
                    '_qty'      => [
                        'type'            => 'order_item_meta',
                        'order_item_type' => 'line_item',
                        'function'        => 'SUM',
                        'name'            => 'order_item_count',
                    ],
                    'post_date' => [
                        'type'     => 'post_data',
                        'function' => '',
                        'name'     => 'post_date',
                    ],
                ],
                'where'        => [
                    [
                        'key'      => 'order_items.order_item_type',
                        'value'    => 'line_item',
                        'operator' => '=',
                    ],
                ],
                'where_meta'   => [
                    [
                        'meta_key'   => '_dokan_vendor_id',
                        'meta_value' => $this->report_data->seller_id,
                        'operator'   => '=',
                    ],
                ],
                'group_by'     => $this->group_by_query,
                'order_by'     => 'post_date ASC',
                'query_type'   => 'get_results',
                'filter_range' => true,
                'order_types'  => wc_get_order_types( 'order-count' ),
                'order_status' => [ 'completed', 'processing', 'on-hold', 'refunded' ],
            ]
        );

        /**
         * Get the total of fully refunded items.
         */
        $this->report_data->refunded_order_items = absint(
            $this->get_order_report_data(
                [
                    'data'         => [
                        '_qty' => [
                            'type'            => 'order_item_meta',
                            'order_item_type' => 'line_item',
                            'function'        => 'SUM',
                            'name'            => 'order_item_count',
                        ],
                    ],
                    'where'        => [
                        [
                            'key'      => 'order_items.order_item_type',
                            'value'    => 'line_item',
                            'operator' => '=',
                        ],
                    ],
                    'where_meta'   => [
                        [
                            'meta_key'   => '_dokan_vendor_id',
                            'meta_value' => $this->report_data->seller_id,
                            'operator'   => '=',
                        ],
                    ],
                    'query_type'   => 'get_var',
                    'filter_range' => true,
                    'order_types'  => wc_get_order_types( 'order-count' ),
                    'order_status' => [ 'refunded' ],
                ]
            )
        );

        /**
         * Order totals by date. Charts should show GROSS amounts to avoid going -ve.
         */
        $this->report_data->orders = (array) $this->get_order_report_data(
            [
                'data'         => [
                    '_order_total'        => [
                        'type'     => 'meta',
                        'function' => 'SUM',
                        'name'     => 'total_sales',
                    ],
                    '_order_shipping'     => [
                        'type'     => 'meta',
                        'function' => 'SUM',
                        'name'     => 'total_shipping',
                    ],
                    '_order_tax'          => [
                        'type'     => 'meta',
                        'function' => 'SUM',
                        'name'     => 'total_tax',
                    ],
                    '_order_shipping_tax' => [
                        'type'     => 'meta',
                        'function' => 'SUM',
                        'name'     => 'total_shipping_tax',
                    ],
                    'post_date'           => [
                        'type'     => 'post_data',
                        'function' => '',
                        'name'     => 'post_date',
                    ],
                ],
                'where_meta'   => [
                    [
                        'meta_key'   => '_dokan_vendor_id',
                        'meta_value' => $this->report_data->seller_id,
                        'operator'   => '=',
                    ],
                ],
                'group_by'     => $this->group_by_query,
                'order_by'     => 'post_date ASC',
                'query_type'   => 'get_results',
                'filter_range' => true,
                'order_types'  => wc_get_order_types( 'sales-reports' ),
                'order_status' => [ 'completed', 'processing', 'on-hold', 'refunded' ],
            ]
        );

        /**
         * If an order is 100% refunded, we should look at the parent's totals, but the refund date.
         * We also need to ensure each parent order's values are only counted/summed once.
         */
        $this->report_data->full_refunds = (array) $this->get_order_report_data(
            [
                'data'                => [
                    '_order_total'        => [
                        'type'     => 'parent_meta',
                        'function' => '',
                        'name'     => 'total_refund',
                    ],
                    '_order_shipping'     => [
                        'type'     => 'parent_meta',
                        'function' => '',
                        'name'     => 'total_shipping',
                    ],
                    '_order_tax'          => [
                        'type'     => 'parent_meta',
                        'function' => '',
                        'name'     => 'total_tax',
                    ],
                    '_order_shipping_tax' => [
                        'type'     => 'parent_meta',
                        'function' => '',
                        'name'     => 'total_shipping_tax',
                    ],
                    'post_date'           => [
                        'type'     => 'post_data',
                        'function' => '',
                        'name'     => 'post_date',
                    ],
                ],
                'where_meta'          => [
                    [
                        'meta_key'   => '_dokan_vendor_id',
                        'meta_value' => $this->report_data->seller_id,
                        'operator'   => '=',
                    ],
                ],
                'group_by'            => 'posts.post_parent',
                'query_type'          => 'get_results',
                'filter_range'        => true,
                'order_status'        => false,
                'parent_order_status' => [ 'refunded' ],
            ]
        );

        foreach ( $this->report_data->full_refunds as $key => $order ) {
            $total_refund       = is_numeric( $order->total_refund ) ? $order->total_refund : 0;
            $total_shipping     = is_numeric( $order->total_shipping ) ? $order->total_shipping : 0;
            $total_tax          = is_numeric( $order->total_tax ) ? $order->total_tax : 0;
            $total_shipping_tax = is_numeric( $order->total_shipping_tax ) ? $order->total_shipping_tax : 0;

            $this->report_data->full_refunds[ $key ]->net_refund = $total_refund - ( $total_shipping + $total_tax + $total_shipping_tax );
        }

        /**
         * Partial refunds. This includes line items, shipping and taxes. Not grouped by date.
         */
        $this->report_data->partial_refunds = (array) $this->get_order_report_data(
            [
                'data'                => [
                    'ID'                  => [
                        'type'     => 'post_data',
                        'function' => '',
                        'name'     => 'refund_id',
                    ],
                    '_refund_amount'      => [
                        'type'     => 'meta',
                        'function' => '',
                        'name'     => 'total_refund',
                    ],
                    'post_date'           => [
                        'type'     => 'post_data',
                        'function' => '',
                        'name'     => 'post_date',
                    ],
                    'order_item_type'     => [
                        'type'      => 'order_item',
                        'function'  => '',
                        'name'      => 'item_type',
                        'join_type' => 'LEFT',
                    ],
                    '_order_total'        => [
                        'type'     => 'meta',
                        'function' => '',
                        'name'     => 'total_sales',
                    ],
                    '_order_shipping'     => [
                        'type'      => 'meta',
                        'function'  => '',
                        'name'      => 'total_shipping',
                        'join_type' => 'LEFT',
                    ],
                    '_order_tax'          => [
                        'type'      => 'meta',
                        'function'  => '',
                        'name'      => 'total_tax',
                        'join_type' => 'LEFT',
                    ],
                    '_order_shipping_tax' => [
                        'type'      => 'meta',
                        'function'  => '',
                        'name'      => 'total_shipping_tax',
                        'join_type' => 'LEFT',
                    ],
                    '_qty'                => [
                        'type'      => 'order_item_meta',
                        'function'  => 'SUM',
                        'name'      => 'order_item_count',
                        'join_type' => 'LEFT',
                    ],
                ],
                'where_meta'          => [
                    [
                        'meta_key'   => '_dokan_vendor_id',
                        'meta_value' => $this->report_data->seller_id,
                        'operator'   => '=',
                    ],
                ],
                'group_by'            => 'refund_id',
                'order_by'            => 'post_date ASC',
                'query_type'          => 'get_results',
                'filter_range'        => true,
                'order_status'        => false,
                'parent_order_status' => [ 'completed', 'processing', 'on-hold' ],
            ]
        );

        foreach ( $this->report_data->partial_refunds as $key => $order ) {
            $this->report_data->partial_refunds[ $key ]->net_refund = $order->total_refund - ( $order->total_shipping + $order->total_tax + $order->total_shipping_tax );
        }

        /**
         * Refund lines - all partial refunds on all order types so we can plot full AND partial refunds on the chart.
         */
        $this->report_data->refund_lines = (array) $this->get_order_report_data(
            [
                'data'                => [
                    'ID'                  => [
                        'type'     => 'post_data',
                        'function' => '',
                        'name'     => 'refund_id',
                    ],
                    '_refund_amount'      => [
                        'type'     => 'meta',
                        'function' => '',
                        'name'     => 'total_refund',
                    ],
                    'post_date'           => [
                        'type'     => 'post_data',
                        'function' => '',
                        'name'     => 'post_date',
                    ],
                    'order_item_type'     => [
                        'type'      => 'order_item',
                        'function'  => '',
                        'name'      => 'item_type',
                        'join_type' => 'LEFT',
                    ],
                    '_order_total'        => [
                        'type'     => 'meta',
                        'function' => '',
                        'name'     => 'total_sales',
                    ],
                    '_order_shipping'     => [
                        'type'      => 'meta',
                        'function'  => '',
                        'name'      => 'total_shipping',
                        'join_type' => 'LEFT',
                    ],
                    '_order_tax'          => [
                        'type'      => 'meta',
                        'function'  => '',
                        'name'      => 'total_tax',
                        'join_type' => 'LEFT',
                    ],
                    '_order_shipping_tax' => [
                        'type'      => 'meta',
                        'function'  => '',
                        'name'      => 'total_shipping_tax',
                        'join_type' => 'LEFT',
                    ],
                    '_qty'                => [
                        'type'      => 'order_item_meta',
                        'function'  => 'SUM',
                        'name'      => 'order_item_count',
                        'join_type' => 'LEFT',
                    ],
                ],
                'where_meta'          => [
                    [
                        'meta_key'   => '_dokan_vendor_id',
                        'meta_value' => $this->report_data->seller_id,
                        'operator'   => '=',
                    ],
                ],
                'group_by'            => 'refund_id',
                'order_by'            => 'post_date ASC',
                'query_type'          => 'get_results',
                'filter_range'        => true,
                'order_status'        => false,
                'parent_order_status' => [ 'completed', 'processing', 'on-hold', 'refunded' ],
            ]
        );

        /**
         * Total up refunds. Note: when an order is fully refunded, a refund line will be added.
         */
        $this->report_data->total_tax_refunded          = 0;
        $this->report_data->total_shipping_refunded     = 0;
        $this->report_data->total_shipping_tax_refunded = 0;
        $this->report_data->total_refunds               = 0;

        $this->report_data->refunded_orders = array_merge( $this->report_data->partial_refunds, $this->report_data->full_refunds );

        foreach ( $this->report_data->refunded_orders as $key => $value ) {
            $this->report_data->total_tax_refunded          += floatval( $value->total_tax < 0 ? $value->total_tax * -1 : $value->total_tax );
            $this->report_data->total_refunds               += floatval( $value->total_refund );
            $this->report_data->total_shipping_tax_refunded += floatval( $value->total_shipping_tax < 0 ? $value->total_shipping_tax * -1 : $value->total_shipping_tax );
            $this->report_data->total_shipping_refunded     += floatval( $value->total_shipping < 0 ? $value->total_shipping * -1 : $value->total_shipping );

            // Only applies to partial.
            if ( isset( $value->order_item_count ) ) {
                $this->report_data->refunded_order_items += floatval( $value->order_item_count < 0 ? $value->order_item_count * -1 : $value->order_item_count );
            }
        }

        // Totals from all orders - including those refunded. Subtract refunded amounts.
        $this->report_data->total_tax          = wc_format_decimal( array_sum( wp_list_pluck( $this->report_data->orders, 'total_tax' ) ) - $this->report_data->total_tax_refunded, 2 );
        $this->report_data->total_shipping     = wc_format_decimal( array_sum( wp_list_pluck( $this->report_data->orders, 'total_shipping' ) ) - $this->report_data->total_shipping_refunded, 2 );
        $this->report_data->total_shipping_tax = wc_format_decimal( array_sum( wp_list_pluck( $this->report_data->orders, 'total_shipping_tax' ) ) - $this->report_data->total_shipping_tax_refunded, 2 );

        // Total the refunds and sales amounts. Sales subtract refunds. Note - total_sales also includes shipping costs.
        $this->report_data->total_sales = wc_format_decimal( array_sum( wp_list_pluck( $this->report_data->orders, 'total_sales' ) ) - $this->report_data->total_refunds, 2 );
        $this->report_data->net_sales   = wc_format_decimal( $this->report_data->total_sales - $this->report_data->total_shipping - max( 0, $this->report_data->total_tax ) - max( 0, $this->report_data->total_shipping_tax ), 2 );

        // Calculate average based on net.
        $this->report_data->average_sales       = wc_format_decimal( $this->report_data->net_sales / ( $this->chart_interval + 1 ), 2 );
        $this->report_data->average_total_sales = wc_format_decimal( $this->report_data->total_sales / ( $this->chart_interval + 1 ), 2 );

        // Total orders and discounts also includes those which have been refunded at some point.
        $this->report_data->total_coupons         = number_format( array_sum( wp_list_pluck( $this->report_data->coupons, 'discount_amount' ) ), 2, '.', '' );
        $this->report_data->total_refunded_orders = absint( count( $this->report_data->full_refunds ) );

        // Total orders in this period, even if refunded.
        $this->report_data->total_orders = absint( array_sum( wp_list_pluck( $this->report_data->order_counts, 'count' ) ) );

        // Item items ordered in this period, even if refunded.
        $this->report_data->total_items = absint( array_sum( wp_list_pluck( $this->report_data->order_items, 'order_item_count' ) ) );

        // 3rd party filtering of report data
        $this->report_data = apply_filters( 'dokan_admin_report_data', $this->report_data );
    }

    /**
     * Get the legend for the main chart sidebar.
     *
     * @since 3.8.0
     *
     * @return array
     */
    public function get_chart_legend() {
        $legend = [];
        $data   = $this->get_report_data();

        switch ( $this->chart_groupby ) {
            case 'day':
                $average_total_sales_title = sprintf(
                /* translators: %s: average total sales */
                    __( '%s average gross daily sales', 'dokan' ),
                    '<strong>' . wc_price( $data->average_total_sales ) . '</strong>'
                );
                $average_sales_title       = sprintf(
                /* translators: %s: average sales */
                    __( '%s average net daily sales', 'dokan' ),
                    '<strong>' . wc_price( $data->average_sales ) . '</strong>'
                );
                break;
            case 'month':
            default:
                $average_total_sales_title = sprintf(
                /* translators: %s: average total sales */
                    __( '%s average gross monthly sales', 'dokan' ),
                    '<strong>' . wc_price( $data->average_total_sales ) . '</strong>'
                );
                $average_sales_title       = sprintf(
                /* translators: %s: average sales */
                    __( '%s average net monthly sales', 'dokan' ),
                    '<strong>' . wc_price( $data->average_sales ) . '</strong>'
                );
                break;
        }

        $legend[] = [
            'title'            => sprintf(
            /* translators: %s: total sales */
                __( '%s gross sales in this period', 'dokan' ),
                '<strong>' . wc_price( $data->total_sales ) . '</strong>'
            ),
            'placeholder'      => __( 'This is the sum of the order totals after any refunds and including shipping and taxes.', 'dokan' ),
            'color'            => $this->chart_colours['sales_amount'],
            'highlight_series' => 6,
        ];
        if ( $data->average_total_sales > 0 ) {
            $legend[] = [
                'title'            => $average_total_sales_title,
                'color'            => $this->chart_colours['average'],
                'highlight_series' => 2,
            ];
        }

        $legend[] = [
            'title'            => sprintf(
            /* translators: %s: net sales */
                __( '%s net sales in this period', 'dokan' ),
                '<strong>' . wc_price( $data->net_sales ) . '</strong>'
            ),
            'placeholder'      => __( 'This is the sum of the order totals after any refunds and excluding shipping and taxes.', 'dokan' ),
            'color'            => $this->chart_colours['net_sales_amount'],
            'highlight_series' => 7,
        ];
        if ( $data->average_sales > 0 ) {
            $legend[] = [
                'title'            => $average_sales_title,
                'color'            => $this->chart_colours['net_average'],
                'highlight_series' => 3,
            ];
        }

        $legend[] = [
            'title'            => sprintf(
            /* translators: %s: total orders */
                __( '%s orders placed', 'dokan' ),
                '<strong>' . $data->total_orders . '</strong>'
            ),
            'color'            => $this->chart_colours['order_count'],
            'highlight_series' => 1,
        ];

        $legend[] = [
            'title'            => sprintf(
            /* translators: %s: total items */
                __( '%s items purchased', 'dokan' ),
                '<strong>' . $data->total_items . '</strong>'
            ),
            'color'            => $this->chart_colours['item_count'],
            'highlight_series' => 0,
        ];
        $legend[] = [
            'title'            => sprintf(
            /* translators: 1: total refunds 2: total refunded orders 3: refunded items */
                _n( '%1$s refunded %2$d order (%3$d item)', '%1$s refunded %2$d orders (%3$d items)', $this->report_data->total_refunded_orders, 'dokan' ),
                '<strong>' . wc_price( $data->total_refunds ) . '</strong>',
                $this->report_data->total_refunded_orders,
                $this->report_data->refunded_order_items
            ),
            'color'            => $this->chart_colours['refund_amount'],
            'highlight_series' => 8,
        ];
        $legend[] = [
            'title'            => sprintf(
            /* translators: %s: total shipping */
                __( '%s charged for shipping', 'dokan' ),
                '<strong>' . wc_price( $data->total_shipping ) . '</strong>'
            ),
            'color'            => $this->chart_colours['shipping_amount'],
            'highlight_series' => 5,
        ];
        $legend[] = [
            'title'            => sprintf(
            /* translators: %s: total coupons */
                __( '%s worth of coupons used', 'dokan' ),
                '<strong>' . wc_price( $data->total_coupons ) . '</strong>'
            ),
            'color'            => $this->chart_colours['coupon_amount'],
            'highlight_series' => 4,
        ];

        return $legend;
    }

    /**
     * Output the report.
     *
     * @since 3.8.0
     *
     * @return void
     */
    public function output_report() {
        $ranges = [
            'year'       => __( 'Year', 'dokan' ),
            'last_month' => __( 'Last month', 'dokan' ),
            'month'      => __( 'This month', 'dokan' ),
            '7day'       => __( 'Last 7 days', 'dokan' ),
        ];

        $this->chart_colours = [
            'sales_amount'     => '#b1d4ea',
            'net_sales_amount' => '#3498db',
            'average'          => '#b1d4ea',
            'net_average'      => '#3498db',
            'order_count'      => '#dbe1e3',
            'item_count'       => '#ecf0f1',
            'shipping_amount'  => '#5cc488',
            'coupon_amount'    => '#f1c40f',
            'refund_amount'    => '#e74c3c',
        ];

        if ( ! in_array( $this->current_range, [ 'custom', 'year', 'last_month', 'month', 'week' ], true ) ) {
            $this->current_range = 'week';
        }

        $this->get_report_data();

        include DOKAN_PRO_TEMPLATE_DIR . '/report/report-by-date.php';
    }

    /**
     * Round our totals correctly.
     *
     * @since 3.8.0
     *
     * @param array|string $amount Chart total.
     *
     * @return array|string
     */
    private function round_chart_totals( $amount ) {
        if ( is_array( $amount ) ) {
            return [ $amount[0], wc_format_decimal( $amount[1], wc_get_price_decimals() ) ];
        } else {
            return wc_format_decimal( $amount, wc_get_price_decimals() );
        }
    }

    /**
     * Get the main chart.
     *
     * @since 3.8.0
     *
     * @return void
     */
    public function get_main_chart() {
        global $wp_locale;

        // Prepare data for the report.
        $data = [
            'order_counts'         => $this->prepare_chart_data( $this->report_data->order_counts, 'post_date', 'count', $this->chart_interval, $this->start_date, $this->chart_groupby ),
            'order_item_counts'    => $this->prepare_chart_data( $this->report_data->order_items, 'post_date', 'order_item_count', $this->chart_interval, $this->start_date, $this->chart_groupby ),
            'order_amounts'        => $this->prepare_chart_data( $this->report_data->orders, 'post_date', 'total_sales', $this->chart_interval, $this->start_date, $this->chart_groupby ),
            'coupon_amounts'       => $this->prepare_chart_data( $this->report_data->coupons, 'post_date', 'discount_amount', $this->chart_interval, $this->start_date, $this->chart_groupby ),
            'shipping_amounts'     => $this->prepare_chart_data( $this->report_data->orders, 'post_date', 'total_shipping', $this->chart_interval, $this->start_date, $this->chart_groupby ),
            'refund_amounts'       => $this->prepare_chart_data( $this->report_data->refund_lines, 'post_date', 'total_refund', $this->chart_interval, $this->start_date, $this->chart_groupby ),
            'net_refund_amounts'   => $this->prepare_chart_data( $this->report_data->refunded_orders, 'post_date', 'net_refund', $this->chart_interval, $this->start_date, $this->chart_groupby ),
            'shipping_tax_amounts' => $this->prepare_chart_data( $this->report_data->orders, 'post_date', 'total_shipping_tax', $this->chart_interval, $this->start_date, $this->chart_groupby ),
            'tax_amounts'          => $this->prepare_chart_data( $this->report_data->orders, 'post_date', 'total_tax', $this->chart_interval, $this->start_date, $this->chart_groupby ),
            'net_order_amounts'    => [],
            'gross_order_amounts'  => [],
        ];

        foreach ( $data['order_amounts'] as $order_amount_key => $order_amount_value ) {
            $data['gross_order_amounts'][ $order_amount_key ]    = $order_amount_value;
            $data['gross_order_amounts'][ $order_amount_key ][1] -= $data['refund_amounts'][ $order_amount_key ][1];

            $data['net_order_amounts'][ $order_amount_key ] = $order_amount_value;
            // Subtract the sum of the values from net order amounts.
            $data['net_order_amounts'][ $order_amount_key ][1] -=
                $data['net_refund_amounts'][ $order_amount_key ][1] +
                $data['shipping_amounts'][ $order_amount_key ][1] +
                $data['shipping_tax_amounts'][ $order_amount_key ][1] +
                $data['tax_amounts'][ $order_amount_key ][1];
        }

        // 3rd party filtering of report data.
        $data = apply_filters( 'dokan_admin_report_chart_data', $data );

        // Encode in json format.
        $chart_data = wp_json_encode(
            [
                'order_counts'        => array_values( $data['order_counts'] ),
                'order_item_counts'   => array_values( $data['order_item_counts'] ),
                'order_amounts'       => array_map( [ $this, 'round_chart_totals' ], array_values( $data['order_amounts'] ) ),
                'gross_order_amounts' => array_map( [ $this, 'round_chart_totals' ], array_values( $data['gross_order_amounts'] ) ),
                'net_order_amounts'   => array_map( [ $this, 'round_chart_totals' ], array_values( $data['net_order_amounts'] ) ),
                'shipping_amounts'    => array_map( [ $this, 'round_chart_totals' ], array_values( $data['shipping_amounts'] ) ),
                'coupon_amounts'      => array_map( [ $this, 'round_chart_totals' ], array_values( $data['coupon_amounts'] ) ),
                'refund_amounts'      => array_map( [ $this, 'round_chart_totals' ], array_values( $data['refund_amounts'] ) ),
            ]
        );
        ?>
        <div class="chart-container">
            <div class="chart chart-legend-container" style='margin-bottom: 10px;'></div>
            <div class="chart-placeholder main" style="width: 100%; height: 450px;"></div>
        </div>
        <script type="text/javascript">
            var main_chart;

            jQuery(function() {
                var order_data = JSON.parse(decodeURIComponent('<?php echo rawurlencode( $chart_data ); ?>'));
                var legendContainer = jQuery('.chart-container > .chart-legend-container');
                var drawGraph = function(highlight) {
                    var series = [
                        {
                            label: "<?php echo esc_js( __( 'Number of items sold', 'dokan' ) ); ?>",
                            data: order_data.order_item_counts,
                            color: '<?php echo esc_js( $this->chart_colours['item_count'] ); ?>',
                            bars: { fillColor: '<?php echo esc_js( $this->chart_colours['item_count'] ); ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo esc_js( $this->barwidth ); ?> * 0.5, align: 'center' },
                            shadowSize: 0,
                            hoverable: false,
                        },
                        {
                            label: "<?php echo esc_js( __( 'Number of orders', 'dokan' ) ); ?>",
                            data: order_data.order_counts,
                            color: '<?php echo esc_js( $this->chart_colours['order_count'] ); ?>',
                            bars: { fillColor: '<?php echo esc_js( $this->chart_colours['order_count'] ); ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo esc_js( $this->barwidth ); ?> * 0.5, align: 'center' },
                            shadowSize: 0,
                            hoverable: true,
                            prepend_tooltip: "<?php echo sprintf( '%1$s: %2$s', __( 'Number of orders', 'dokan' ), get_woocommerce_currency_symbol() ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>"
                        },
                        {
                            label: "<?php echo esc_js( __( 'Average gross sales amount', 'dokan' ) ); ?>",
                            data: [[ <?php echo esc_js( min( array_keys( $data['order_amounts'] ) ) ); ?>, <?php echo esc_js( $this->report_data->average_total_sales ); ?> ], [ <?php echo esc_js( max( array_keys( $data['order_amounts'] ) ) ); ?>, <?php echo esc_js( $this->report_data->average_total_sales ); ?> ]],
                            yaxis: 2,
                            color: '<?php echo esc_js( $this->chart_colours['average'] ); ?>',
                            points: { show: false },
                            lines: { show: true, lineWidth: 2, fill: false },
                            shadowSize: 0,
                            hoverable: false,
                        },
                        {
                            label: "<?php echo esc_js( __( 'Average net sales amount', 'dokan' ) ); ?>",
                            data: [[ <?php echo esc_js( min( array_keys( $data['order_amounts'] ) ) ); ?>, <?php echo esc_js( $this->report_data->average_sales ); ?> ], [ <?php echo esc_js( max( array_keys( $data['order_amounts'] ) ) ); ?>, <?php echo esc_js( $this->report_data->average_sales ); ?> ]],
                            yaxis: 2,
                            color: '<?php echo esc_js( $this->chart_colours['net_average'] ); ?>',
                            points: { show: false },
                            lines: { show: true, lineWidth: 2, fill: false },
                            shadowSize: 0,
                            hoverable: false,
                        },
                        {
                            label: "<?php echo esc_js( __( 'Coupon amount', 'dokan' ) ); ?>",
                            data: order_data.coupon_amounts,
                            yaxis: 2,
                            color: '<?php echo esc_js( $this->chart_colours['coupon_amount'] ); ?>',
                            points: { show: true, radius: 5, lineWidth: 2, fillColor: '#fff', fill: true },
                            lines: { show: true, lineWidth: 2, fill: false },
                            shadowSize: 0,
                            hoverable: false,
                            <?php echo $this->get_currency_tooltip();  // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
                        },
                        {
                            label: "<?php echo esc_js( __( 'Shipping amount', 'dokan' ) ); ?>",
                            data: order_data.shipping_amounts,
                            yaxis: 2,
                            color: '<?php echo esc_js( $this->chart_colours['shipping_amount'] ); ?>',
                            points: { show: true, radius: 5, lineWidth: 2, fillColor: '#fff', fill: true },
                            lines: { show: true, lineWidth: 2, fill: false },
                            shadowSize: 0,
                            hoverable: true,
                            prepend_tooltip: "<?php echo sprintf( '%1$s: %2$s', __( 'Shipping amount', 'dokan' ), get_woocommerce_currency_symbol() ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>"
                        },
                        {
                            label: "<?php echo esc_js( __( 'Gross sales amount', 'dokan' ) ); ?>",
                            data: order_data.gross_order_amounts,
                            yaxis: 2,
                            color: '<?php echo esc_js( $this->chart_colours['sales_amount'] ); ?>',
                            points: { show: true, radius: 5, lineWidth: 2, fillColor: '#fff', fill: true },
                            lines: { show: true, lineWidth: 2, fill: false },
                            shadowSize: 0,
                            hoverable: true,
                            prepend_tooltip: "<?php echo sprintf( '%1$s: %2$s', __( 'Gross sales amount', 'dokan' ), get_woocommerce_currency_symbol() ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>"
                        },
                        {
                            label: "<?php echo esc_js( __( 'Net sales amount', 'dokan' ) ); ?>",
                            data: order_data.net_order_amounts,
                            yaxis: 2,
                            color: '<?php echo esc_js( $this->chart_colours['net_sales_amount'] ); ?>',
                            points: { show: true, radius: 6, lineWidth: 4, fillColor: '#fff', fill: true },
                            lines: { show: true, lineWidth: 5, fill: false },
                            shadowSize: 0,
                            hoverable: true,
                            prepend_tooltip: "<?php echo sprintf( '%1$s: %2$s', __( 'Net sales amount', 'dokan' ), get_woocommerce_currency_symbol() ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>"
                        },
                        {
                            label: "<?php echo esc_js( __( 'Refund amount', 'dokan' ) ); ?>",
                            data: order_data.refund_amounts,
                            yaxis: 2,
                            color: '<?php echo esc_js( $this->chart_colours['refund_amount'] ); ?>',
                            points: { show: true, radius: 5, lineWidth: 2, fillColor: '#fff', fill: true },
                            lines: { show: true, lineWidth: 2, fill: false },
                            shadowSize: 0,
                            hoverable: true,
                            prepend_tooltip: "<?php echo sprintf( '%1$s: %2$s', __( 'Refund amount', 'dokan' ), get_woocommerce_currency_symbol() ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>"
                        }
                    ];

                    if (highlight !== 'undefined' && series[highlight]) {
                        highlight_series = series[highlight];

                        highlight_series.color = '#9c5d90';

                        if (highlight_series.bars) {
                            highlight_series.bars.fillColor = '#9c5d90';
                        }

                        if (highlight_series.lines) {
                            highlight_series.lines.lineWidth = 5;
                        }
                    }

                    main_chart = jQuery.plot(
                        jQuery('.chart-placeholder.main'),
                        series,
                        {
                            legend: {
                                show: true,
                                position: 'nw',
                                noColumns: 2,
                                container: legendContainer
                            },
                            grid: {
                                color: '#aaa',
                                borderColor: 'transparent',
                                borderWidth: 0,
                                hoverable: true
                            },
                            xaxes: [{
                                color: '#aaa',
                                position: 'bottom',
                                tickColor: 'transparent',
                                mode: 'time',
                                timeformat: "<?php echo ( 'day' === $this->chart_groupby ) ? '%d %b' : '%b'; ?>",
                                monthNames: JSON.parse(decodeURIComponent('<?php echo rawurlencode( wp_json_encode( array_values( $wp_locale->month_abbrev ) ) ); ?>')),
                                tickLength: 1,
                                minTickSize: [1, "<?php echo esc_js( $this->chart_groupby ); ?>"],
                                font: {
                                    color: '#aaa'
                                }
                            }],
                            yaxes: [
                                {
                                    min: 0,
                                    minTickSize: 1,
                                    tickDecimals: 0,
                                    color: '#d4d9dc',
                                    font: { color: '#aaa' }
                                },
                                {
                                    position: 'right',
                                    min: 0,
                                    tickDecimals: 2,
                                    alignTicksWithAxis: 1,
                                    color: 'transparent',
                                    font: { color: '#aaa' }
                                }
                            ]
                        }
                    );

                    jQuery('.chart-placeholder').trigger('resize');
                };

                drawGraph();

                jQuery('.highlight_series').on('mouseenter',
                    function() {
                        drawGraph(jQuery(this).data('series'));
                    }).on('mouseleave',
                    function() {
                        drawGraph();
                    }
                );
            });
        </script>
        <?php
    }
}
