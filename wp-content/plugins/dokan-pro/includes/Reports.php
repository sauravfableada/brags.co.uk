<?php

namespace WeDevs\DokanPro;

use WeDevs\Dokan\Dashboard\Templates\Dashboard;
use WeDevs\Dokan\Utilities\ReportUtil;
use WeDevs\DokanPro\Admin\ReportLogExporter;
use WeDevs\DokanPro\Reports\ReportStatement;

/**
 * Dokan Pro Report Class
 *
 * @since 2.4
 *
 * @package dokan
 */
class Reports {

    /**
     * Load automatically when class inistantiate
     *
     * @since 2.4
     *
     * @uses actions|filter hooks
     */
    public function __construct() {
        add_action( 'dokan_report_content_inside_before', array( $this, 'show_seller_enable_message' ) );
        add_filter( 'dokan_get_dashboard_nav', array( $this, 'add_reports_menu' ) );
        add_action( 'dokan_load_custom_template', array( $this, 'load_reports_template' ) );

        if ( ! ( class_exists( ReportUtil::class ) && ReportUtil::is_analytics_enabled() ) ) {
            add_action( 'dokan_report_content_area_header', array( $this, 'report_header_render' ) );
            add_action( 'dokan_report_content', array( $this, 'render_review_content' ) );
        } else {
            add_action( 'dokan_report_content_inside_before', [ $this, 'add_report_content' ] );
        }

        add_action( 'template_redirect', array( $this, 'handle_statement' ) );
        add_action( 'init', [ $this, 'download_log_export_file' ], 15 );
    }

    /**
     * Add dashboard report root content.
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function add_report_content() {
        echo '<div id="dokan-analytics-app"></div>';
    }

    /**
     * Handle export statement.
     *
     * @return void
     */
    public function handle_statement() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        if ( ! dokan_is_user_seller( get_current_user_id() ) ) {
            return;
        }

        if ( ! isset( $_GET['dokan_report_filter_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['dokan_report_filter_nonce'] ) ), 'dokan_report_filter' ) ) {
            return;
        }

        if ( isset( $_GET['dokan_statement_export_all'] ) ) {
            $args = [
                'vendor_id'  => dokan_get_current_user_id(),
                'start_date' => isset( $_GET['start_date_alt'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date_alt'] ) ) : '',
                'end_date'   => isset( $_GET['end_date_alt'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date_alt'] ) ) : '',
            ];

            $report_statement = new ReportStatement();
            $csv_data         = $report_statement->get_statement_csv_data( $args );

            if ( is_wp_error( $csv_data ) ) {
                dokan_log( 'Error: ' . $csv_data->get_error_message() );
                wp_safe_redirect(
                    add_query_arg(
                        [
                            'start_date_alt' => sanitize_text_field( wp_unslash( $_GET['start_date_alt'] ?? '' ) ),
                            'end_date_alt'   => sanitize_text_field( wp_unslash( $_GET['end_date_alt'] ?? '' ) ),
                            'chart'          => 'sales_statement',
                            'export_error'   => true,
                        ],
                        dokan_get_navigation_url( 'reports' )
                    )
                );
                return;
            }

            $filename = 'Statement-' . dokan_current_datetime()->format( 'Y-m-d' );
            header( 'Content-Type: application/csv; charset=' . get_option( 'blog_charset' ) );
            header( "Content-Disposition: attachment; filename={$filename}.csv" );

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $csv_data;
            exit();
        }
    }

    /**
     * Show Seller Enable Error Message
     *
     * @since 2.4
     *
     * @return void
     */
    public function show_seller_enable_message() {
        $user_id = get_current_user_id();

        if ( ! dokan_is_seller_enabled( $user_id ) ) {
            echo dokan_seller_not_enabled_notice();
        }
    }

    /**
     * Add Report Menu
     *
     * @since 2.4
     *
     * @param array $urls
     *
     * @return array
     */
    public function add_reports_menu( $urls ) {
		$is_analytics_available = class_exists( ReportUtil::class ) && ReportUtil::is_analytics_enabled();

        $urls['reports'] = array(
            'title'      => __( 'Reports', 'dokan' ),
            'icon'       => '<i class="fas fa-chart-line"></i>',
            'url'        => dokan_get_navigation_url( 'reports' ) . ( $is_analytics_available ? '?path=%2Fanalytics%2Fproducts' : '' ),
            'pos'        => 60,
            'icon_name'  => 'ChartPie',
            'permission' => 'dokan_view_report_menu',
        );

        // Add reports submenu if woocommerce analytics is enabled.
	    if ( $is_analytics_available ) {
            $reports_submenu = [
                'report_products'   => [
                    'title'      => esc_html__( 'Products', 'dokan' ),
                    'icon'       => '<i class="fa-solid fa-box"></i>',
                    'url'        => dokan_get_navigation_url( 'reports' ) . '?path=%2Fanalytics%2Fproducts',
                    'pos'        => 60,
                    'permission' => 'dokan_view_report_menu',
                ],
                'report_revenue'    => [
                    'title'      => esc_html__( 'Revenue', 'dokan' ),
                    'icon'       => '<i class="fa-solid fa-chart-column"></i>',
                    'url'        => dokan_get_navigation_url( 'reports' ) . '?path=%2Fanalytics%2Frevenue',
                    'pos'        => 60,
                    'permission' => 'dokan_view_report_menu',
                ],
                'report_orders'     => [
                    'title'      => esc_html__( 'Orders', 'dokan' ),
                    'icon'       => '<i class="fa-solid fa-cart-shopping"></i>',
                    'url'        => dokan_get_navigation_url( 'reports' ) . '?path=%2Fanalytics%2Forders',
                    'pos'        => 60,
                    'permission' => 'dokan_view_report_menu',
                ],
                'report_variations' => [
                    'title'      => esc_html__( 'Variations', 'dokan' ),
                    'icon'       => '<i class="fa-solid fa-boxes-stacked"></i>',
                    'url'        => dokan_get_navigation_url( 'reports' ) . '?path=%2Fanalytics%2Fvariations',
                    'pos'        => 60,
                    'permission' => 'dokan_view_report_menu',
                ],
                'report_categories' => [
                    'title'      => esc_html__( 'Categories', 'dokan' ),
                    'icon'       => '<i class="fa-solid fa-bars-staggered"></i>',
                    'url'        => dokan_get_navigation_url( 'reports' ) . '?path=%2Fanalytics%2Fcategories',
                    'pos'        => 60,
                    'permission' => 'dokan_view_report_menu',
                ],
                'report_coupons'    => [
                    'title'      => esc_html__( 'Coupons', 'dokan' ),
                    'icon'       => '<i class="fa-solid fa-ticket"></i>',
                    'url'        => dokan_get_navigation_url( 'reports' ) . '?path=%2Fanalytics%2Fcoupons',
                    'pos'        => 60,
                    'permission' => 'dokan_view_report_menu',
                ],
                'report_stock'      => [
                    'title'      => esc_html__( 'Stock', 'dokan' ),
                    'icon'       => '<i class="fa-solid fa-boxes-packing"></i>',
                    'url'        => dokan_get_navigation_url( 'reports' ) . '?path=%2Fanalytics%2Fstock',
                    'pos'        => 60,
                    'permission' => 'dokan_view_report_menu',
                ],
                'report_statement'  => [
                    'title'      => esc_html__( 'Statement', 'dokan' ),
                    'icon'       => '<i class="fa-solid fa-file-invoice"></i>',
                    'url'        => dokan_get_navigation_url( 'reports' ) . '?path=%2Fanalytics%2Fstatement',
                    'pos'        => 60,
                    'permission' => 'dokan_view_statement_report',
                ],
            ];

            /**
             * Filter to get the seller dashboard reports navigation.
             *
             * @since 4.0.0
             *
             * @param $reports_submenu array.
             */
            $urls['reports']['submenu'] = apply_filters( 'dokan_get_dashboard_reports_nav', $reports_submenu );
        }

        return $urls;
    }

    /**
     * Load Report Main Template
     *
     * @since 2.4
     *
     * @param  array $query_vars
     *
     * @return void
     */
    public function load_reports_template( $query_vars ) {
        if ( isset( $query_vars['reports'] ) ) {
            if ( ! current_user_can( 'dokan_view_review_menu' ) ) {
                dokan_get_template_part(
                    'global/dokan-error',
                    '',
                    [
                        'deleted' => false,
                        'message' => __( 'You have no permission to view review page', 'dokan' ),
                    ]
                );
            } else {
                dokan_get_template_part( 'report/reports', '', array( 'pro' => true ) );
            }
        }
    }

    /**
     * Render Report Header Template
     *
     * @since 2.4
     *
     * @return void
     */
    public function report_header_render() {
        dokan_get_template_part( 'report/header', '', array( 'pro' => true ) );
    }

    /**
     * Render Review Content
     *
     * @return void
     */
    public function render_review_content() {
        $charts  = $this->get_reports_charts();
        $link    = dokan_get_navigation_url( 'reports' );
        $current = isset( $_GET['chart'] ) ? sanitize_text_field( wp_unslash( $_GET['chart'] ) ) : 'overview'; // phpcs:ignore

        dokan_get_template_part(
            'report/content', '', [
                'pro'     => true,
                'charts'  => $charts,
                'link'    => $link,
                'current' => $current,
            ]
        );
    }

    /**
     * Returns the definitions for the reports and charts
     *
     * @since 1.0
     *
     * @return array
     */
    protected function get_reports_charts() {
        $charts = [
            'title'  => __( 'Sales', 'dokan' ),
            'charts' => [
                'overview'        => [
                    'title'       => __( 'Overview', 'dokan' ),
                    'description' => '',
                    'hide_title'  => true,
                    'function'    => 'dokan_sales_overview',
                    'permission'  => 'dokan_view_overview_report',
                ],
                'sales_by_day'    => [
                    'title'       => __( 'Sales by day', 'dokan' ),
                    'description' => '',
                    'function'    => 'dokan_daily_sales',
                    'permission'  => 'dokan_view_daily_sale_report',
                ],
                'top_sellers'     => [
                    'title'       => __( 'Top selling', 'dokan' ),
                    'description' => '',
                    'function'    => 'dokan_top_sellers',
                    'permission'  => 'dokan_view_top_selling_report',
                ],
                'top_earners'     => [
                    'title'       => __( 'Top earning', 'dokan' ),
                    'description' => '',
                    'function'    => 'dokan_top_earners',
                    'permission'  => 'dokan_view_top_earning_report',
                ],
                'sales_statement' => [
                    'title'       => __( 'Statement', 'dokan' ),
                    'description' => '',
                    'function'    => 'dokan_seller_sales_statement',
                    'permission'  => 'dokan_view_statement_report',
                ],
            ],
        ];

        return apply_filters( 'dokan_reports_charts', $charts );
    }

    /**
     * Download exported log file
     *
     * @since 3.4.1
     */
    public function download_log_export_file() {
        if ( ! isset( $_GET['download-order-log-csv'] ) || ! wp_verify_nonce( $_GET['download-order-log-csv'], 'download-order-log-csv-nonce' ) ) { // phpcs:ignore
            return;
        }

        // export logs
        include_once DOKAN_PRO_INC . '/Admin/ReportLogExporter.php';
        $exporter = new ReportLogExporter();
        $exporter->export();
    }
}
