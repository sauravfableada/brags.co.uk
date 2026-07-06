<?php

namespace WeDevs\DokanPro\Reports;

use DateTimeZone;
use WC_Admin_Report;
use WeDevs\Dokan\Utilities\OrderUtil;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

include_once( WC()->plugin_path() . '/includes/admin/reports/class-wc-admin-report.php' );

/**
 * Dokan Reports Manager Class
 *
 * @since 3.8.0
 */
class Manager extends WC_Admin_Report {

    /**
     * Get the current range and calculate the start and end dates.
     *
     * @since 3.8.0
     *
     * @param  string $current_range Type of range.
     */
    public function calculate_current_range( $current_range ) {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended

        switch ( $current_range ) {
            case 'custom':
                // fix start_date
                if ( ! $this->start_date ) {
                    $this->start_date = isset( $_REQUEST['start_date_alt'] ) ? dokan_current_datetime()->modify( sanitize_text_field( wp_unslash( $_REQUEST['start_date_alt'] ) ) )->getTimestamp() : dokan_current_datetime()->modify( '- 7 days' )->getTimestamp();
                } elseif ( is_string( $this->start_date ) ) {
                    $this->start_date = dokan_current_datetime()->modify( $this->start_date )->getTimestamp();
                }

                // fix end_date
                if ( ! $this->end_date ) {
                    $this->end_date = isset( $_REQUEST['end_date_alt'] ) ? dokan_current_datetime()->modify( sanitize_text_field( wp_unslash( $_REQUEST['end_date_alt'] ) ) )->getTimestamp() : dokan_current_datetime()->modify( 'midnight' )->getTimestamp();
                } elseif ( is_string( $this->end_date ) ) {
                    $this->end_date = dokan_current_datetime()->modify( $this->end_date )->getTimestamp();
                }

                $this->start_date = max( strtotime( '-20 years' ), $this->start_date );

                $interval = 0;
                $min_date = $this->start_date;

                // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
                while ( ( $min_date = strtotime( '+1 MONTH', $min_date ) ) <= $this->end_date ) {
                    $interval ++;
                }

                // 3-months max for day view
                if ( $interval > 3 ) {
                    $this->chart_groupby = 'month';
                } else {
                    $this->chart_groupby = 'day';
                }
                break;

            case 'year':
                $this->start_date    = dokan_current_datetime()->modify( 'first day of january this year' )->getTimestamp();
                $this->end_date      = dokan_current_datetime()->modify( 'midnight' )->getTimestamp();
                $this->chart_groupby = 'month';
                break;

            case 'last_month':
                $this->start_date        = dokan_current_datetime()->modify( 'first day of last month' )->getTimestamp();
                $this->end_date          = dokan_current_datetime()->modify( 'last day of last month' )->getTimestamp();
                $this->chart_groupby     = 'day';
                break;

            case 'month':
                $this->start_date    = dokan_current_datetime()->modify( 'first day of this month' )->getTimestamp();
                $this->end_date      = dokan_current_datetime()->modify( 'midnight' )->getTimestamp();
                $this->chart_groupby = 'day';
                break;

            case 'week':
                $this->start_date    = dokan_current_datetime()->modify( '-6 days' )->getTimestamp();
                $this->end_date      = dokan_current_datetime()->modify( 'midnight' );
                $this->chart_groupby = 'day';
                break;
        }

        // Group by.
        switch ( $this->chart_groupby ) {

            case 'day':
                $this->group_by_query = 'YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date)';
                $this->chart_interval = absint( ceil( max( 0, ( $this->end_date - $this->start_date ) / ( 60 * 60 * 24 ) ) ) );
                $this->barwidth       = 60 * 60 * 24 * 1000;
                break;

            case 'month':
                $this->group_by_query = 'YEAR(posts.post_date), MONTH(posts.post_date)';
                $this->chart_interval = 0;
                $min_date             = strtotime( date( 'Y-m-01', $this->start_date ) );

                // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
                while ( ( $min_date = strtotime( '+1 MONTH', $min_date ) ) <= $this->end_date ) {
                    $this->chart_interval ++;
                }

                $this->barwidth = 60 * 60 * 24 * 7 * 4 * 1000;
                break;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
    }

    /**
     * Check nonce for current range.
     *
     * @since  3.8.0
     *
     * @param  string $current_range Current range.
     */
    public function check_current_range_nonce( $current_range ) {
        if ( 'custom' !== $current_range ) {
            return;
        }

        if ( ! isset( $_REQUEST['dokan_report_filter_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['dokan_report_filter_nonce'] ), 'custom_range' ) ) {
            // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
            wp_die(
            /* translators: %1$s: open link, %2$s: close link */
                sprintf( esc_html__( 'This report link has expired. %1$sClick here to view the filtered report%2$s.', 'woocommerce' ), '<a href="' . esc_url( wp_nonce_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'custom_range', 'wc_reports_nonce' ) ) . '">', '</a>' ),
                esc_attr__( 'Confirm navigation', 'woocommerce' )
            );
            // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
        }
    }

    /**
     * Output the report.
     *
     * @since 3.8.0
     *
     * @return void
     */
    public function output_report() {
        parent::output_report(); // TODO: Change the autogenerated stub
    }

    /**
     * Get sales overview data
     *
     * @since 3.8.0
     *
     * @param int    $seller_id
     * @param string $start_date
     * @param string $end_date
     *
     * @return array
     */
    public function get_top_selling_data( $seller_id, $start_date, $end_date ) {
        global $wpdb;

        // get start date
        $start_date = dokan_current_datetime()->modify( $start_date );
        if ( ! $start_date ) {
            $start_date = dokan_current_datetime();
        }

        // get end date
        $end_date = dokan_current_datetime()->modify( $end_date );
        if ( ! $end_date ) {
            $end_date = $start_date->modify( '+1 days' );
        }

        $table      = "{$wpdb->prefix}woocommerce_order_items as order_items";
        $post_table = "{$wpdb->posts} AS posts";

        $join = " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id";
        $join .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_2 ON order_items.order_item_id = order_item_meta_2.order_item_id";

        if ( dokan_pro_is_hpos_enabled() ) {
            $start_date_gmt = $start_date->setTimezone( new DateTimeZone( 'UTC' ) );
            $end_date_gmt   = $end_date->setTimezone( new DateTimeZone( 'UTC' ) );
            $post_table     = OrderUtil::get_order_table_name();
            $join           .= " LEFT JOIN {$post_table} AS posts ON order_items.order_id = posts.id";
            $join           .= " LEFT JOIN {$wpdb->prefix}dokan_orders AS do ON posts.id = do.order_id";
            $where          = "
            AND posts.status != 'trash'
            AND posts.date_created_gmt > '" . $start_date_gmt->format( 'Y-m-d' ) . "'
            AND posts.date_created_gmt < '" . $end_date_gmt->format( 'Y-m-d' ) . "'
            ";
        } else {
            $join  .= " LEFT JOIN {$post_table} ON order_items.order_id = posts.ID";
            $join  .= " LEFT JOIN {$wpdb->prefix}dokan_orders AS do ON posts.ID = do.order_id";
            $where = "
            AND posts.post_type = 'shop_order'
            AND posts.post_status   != 'trash'
            AND posts.post_date > '" . $start_date->format( 'Y-m-d' ) . "'
            AND posts.post_date < '" . $end_date->format( 'Y-m-d' ) . "'
            ";
        }

        // Get order ids and dates in range
        $order_items = apply_filters( 'woocommerce_reports_top_sellers_order_items', $wpdb->get_results( "
            SELECT order_item_meta_2.meta_value as product_id, SUM( order_item_meta.meta_value ) as item_quantity FROM {$table}
            {$join}
            WHERE 1=1 {$where}
            AND   do.seller_id = {$seller_id}
            AND   do.order_status IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', [ 'wc-completed', 'wc-processing', 'wc-on-hold' ] ) ) . "')
            AND   order_items.order_item_type = 'line_item'
            AND   order_item_meta.meta_key = '_qty'
            AND   order_item_meta_2.meta_key = '_product_id'
            GROUP BY order_item_meta_2.meta_value
            ORDER BY item_quantity DESC
            LIMIT 25
        " ), $start_date->format( 'Y-m-d' ), $end_date->format( 'Y-m-d' ) );

        $data = [];

        foreach ( $order_items as $order_item ) {
            $product = wc_get_product( $order_item->product_id );

            $data[] = [
                'id'       => $product ? $product->get_id() : $order_item->product_id,
                'title'    => $product ? $product->get_title() : __( 'Product no longer exists', 'dokan' ),
                'url'      => $product ? $product->get_permalink() : '#',
                'edit_url' => $product ? dokan_edit_product_url( $order_item->product_id ) : '#',
                'sold_qty' => $order_item->item_quantity,
            ];
        }

        return $data;
    }

    /**
     * Get top earners data
     *
     * @since 3.8.0
     *
     * @param int    $seller_id
     * @param string $start_date
     * @param string $end_date
     *
     * @return array
     */
    public function get_top_earners_data( $seller_id, $start_date, $end_date ) {
        global $wpdb;

        // get start date
        $start_date = dokan_current_datetime()->modify( $start_date );
        if ( ! $start_date ) {
            $start_date = dokan_current_datetime();
        }

        // get end date
        $end_date = dokan_current_datetime()->modify( $end_date );
        if ( ! $end_date ) {
            $end_date = $start_date->modify( '+1 days' );
        }

        $table                 = "{$wpdb->prefix}woocommerce_order_items AS order_items";
        $post_table            = "{$wpdb->posts} AS posts";
        $withdraw_order_status = dokan_get_option( 'withdraw_order_status', 'dokan_withdraw', [ 'wc-completed' ] );

        $join = " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id";
        $join .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_2 ON order_items.order_item_id = order_item_meta_2.order_item_id";

        if ( dokan_pro_is_hpos_enabled() ) {
            $start_date_gmt = $start_date->setTimezone( new DateTimeZone( 'UTC' ) );
            $end_date_gmt   = $end_date->setTimezone( new DateTimeZone( 'UTC' ) );
            $post_table     = OrderUtil::get_order_table_name();
            $join           .= " LEFT JOIN {$post_table} AS posts ON order_items.order_id = posts.id";
            $join           .= " LEFT JOIN {$wpdb->prefix}dokan_orders AS do ON posts.id = do.order_id";
            $where          = "
            AND posts.status != 'trash'
            AND posts.date_created_gmt > '" . $start_date_gmt->format( 'Y-m-d' ) . "'
            AND posts.date_created_gmt < '" . $end_date_gmt->format( 'Y-m-d' ) . "'
            ";
        } else {
            $join  .= " LEFT JOIN {$post_table} ON order_items.order_id = posts.ID";
            $join  .= " LEFT JOIN {$wpdb->prefix}dokan_orders AS do ON posts.ID = do.order_id";
            $where = "
            AND posts.post_type = 'shop_order'
            AND posts.post_status   != 'trash'
            AND posts.post_date > '" . $start_date->format( 'Y-m-d' ) . "'
            AND posts.post_date < '" . $end_date->format( 'Y-m-d' ) . "'
            ";
        }

        // Get order ids and dates in range
        $order_items = apply_filters( 'woocommerce_reports_top_earners_order_items', $wpdb->get_results( "
            SELECT order_item_meta_2.meta_value as product_id, SUM( order_item_meta.meta_value ) as line_total, SUM( do.net_amount ) as total_earning
            FROM {$table}
            {$join}
            WHERE    1=1 {$where}
            AND      do.seller_id = {$seller_id}
            AND      do.order_status IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', esc_sql( $withdraw_order_status ) ) ) . "')
            AND      order_items.order_item_type = 'line_item'
            AND      order_item_meta.meta_key = '_line_total'
            AND      order_item_meta_2.meta_key = '_product_id'
            GROUP BY order_item_meta_2.meta_value
            ORDER BY line_total DESC
            LIMIT 25
        " ), $start_date, $end_date );

        $data = [];
        foreach ( $order_items as $order_item ) {
            $product = wc_get_product( $order_item->product_id );

            $data[] = [
                'id'            => $product ? $product->get_id() : $order_item->product_id,
                'title'         => $product ? $product->get_title() : __( 'Product no longer exists', 'dokan' ),
                'url'           => $product ? $product->get_permalink() : '#',
                'edit_url'      => $product ? dokan_edit_product_url( $order_item->product_id ) : '#',
                'sales'         => $order_item->line_total,
                'total_earning' => $order_item->total_earning,
            ];
        }

        return $data;
    }
}
