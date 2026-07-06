<?php

namespace WeDevs\DokanPro\Reports;

use stdClass;
use WP_Error;

/**
 * Report Statements Manager Class.
 *
 * @since 4.0.0
 */
class ReportStatement {

    /**
     * Statement data
     *
     * @var stdClass
     */
    protected StatementData $statement_data;

    public function __construct() {
        $this->statement_data = apply_filters( 'dokan_reports_statement_data_instance', new StatementData() );
    }

    /**
     * Get report data for statement
     *
     * @since 3.8.0
     *
     * @param array $args
     *
     * @return StatementData
     */
    public function get_statement_data( $args = [] ): StatementData {

        if ( empty( $this->statement_data->get_vendor_id() ) ) {
            $this->query_statement_data( $args );
        }

        /**
         * Filters for the final statement data.
         *
         * @since 4.0.0
         *
         * @param stdClass $statement_data
         * @param array    $args
         */
        return apply_filters( 'dokan_report_get_statement_data', $this->statement_data, $args );
    }

    /**
     * Query statement data
     *
     * @since 4.0.0
     *
     * @param array $args {
     *     Optional. Arguments to filter statement data
     *
     *     @type int    $vendor_id  Vendor ID
     *     @type string $start_date Start date
     *     @type string $end_date   End date
     * }
     *
     * @return void|WP_Error
     */
    protected function query_statement_data( array $args = [] ) {
        $defaults = [
            'vendor_id'  => 0,
            'start_date' => '',
            'end_date'   => '',
        ];

        $args = wp_parse_args( $args, $defaults );

        /**
         * Filters for the query arguments before processing.
         *
         * @since 4.0.0
         *
         * @param array $args
         */
        $args = apply_filters( 'dokan_report_statement_query_args', $args );

        try {
            // Set the vendor. If not set, use the current user
            $vendor_id = ! empty( $args['vendor_id'] ) ? absint( $args['vendor_id'] ) : dokan_get_current_user_id();
            if ( ! dokan_is_user_seller( $vendor_id ) ) {
                throw new \Exception( esc_html__( 'Invalid vendor', 'dokan' ) );
            }

            $this->statement_data->set_vendor_id( $vendor_id );

            $this->statement_data->set_start_date( sanitize_text_field( $args['start_date'] ?? '' ) );

            $this->statement_data->set_end_date( sanitize_text_field( $args['end_date'] ?? '' ) );

            $this->statement_data->set_opening_balance();

            $status = implode( "', '", dokan_withdraw_get_active_order_status() );

            // Get statement entries
            $this->query_statement_entries( $args, $status );

            /**
             * Filters for reports statement data.
             *
             * @since 4.0.0
             *
             * @param stdClass $statement_data
             * @param array    $args
             */
            $this->statement_data = apply_filters( 'dokan_reports_statement_data', $this->statement_data, $args );
        } catch ( \Exception $e ) {
            new WP_Error( 'statement_error', $e->getMessage() );
        }
    }

    /**
     * Query statement entries
     *
     * @since 4.0.0
     *
     * @param array  $args
     * @param string $status
     *
     * @return void
     */
    protected function query_statement_entries( $args, $status ) {
        global $wpdb;

        $page     = ! empty( $args['page'] ) ? absint( $args['page'] ) : 1;
        $per_page = ! empty( $args['per_page'] ) ? absint( $args['per_page'] ) : 0;
        $offset   = ( $page - 1 ) * $per_page;
        $limit    = $per_page ? "LIMIT $offset, $per_page" : '';

        $sql = $wpdb->prepare(
            "SELECT * from {$wpdb->prefix}dokan_vendor_balance
            WHERE vendor_id = %d
            AND DATE(balance_date) >= %s
            AND DATE(balance_date) <= %s
            AND ( ( trn_type = 'dokan_orders' AND status IN ('{$status}') )
            OR trn_type IN ( 'dokan_withdraw', 'dokan_refund', 'gateway_fee' ) )
            ORDER BY balance_date {$limit}",
            $this->statement_data->get_vendor_id(),
            $this->statement_data->get_start_date(),
            $this->statement_data->get_end_date()
        );

        /**
         * Filter the statement entries SQL query
         *
         * @since 4.0.0
         *
         * @param string $sql
         * @param array  $args
         * @param string $status
         */
        $sql = apply_filters( 'dokan_report_statement_entries_query', $sql, $args, $status );

        // Get statement entries.
        $result            = (array) $wpdb->get_results( $sql ); // phpcs:ignore
        $processed_entries = $this->statement_data->process_statement_data( $result );

        /**
         * Filter the statement entries results
         *
         * @since 4.0.0
         *
         * @param array  $entries
         * @param array  $args
         * @param string $status
         */
        $entries = apply_filters( 'dokan_report_statement_entries', $processed_entries, $args, $status );

        $this->statement_data->set_entries( $entries );
    }

    /**
     * Query summary data
     *
     * @since 4.0.0
     *
     * @param array $args
     *
     * @return array|WP_Error
     */
    public function get_summary_data( $args ) {
        global $wpdb;

        // Set the statements start and end date.
        $this->statement_data->set_vendor_id( dokan_get_current_user_id() );
        $this->statement_data->set_start_date( sanitize_text_field( $args['start_date'] ?? '' ) );
        $this->statement_data->set_end_date( sanitize_text_field( $args['end_date'] ?? '' ) );

        $status = implode( "', '", dokan_withdraw_get_active_order_status() );
        $sql    = $wpdb->prepare(
            "SELECT
            COUNT(*) as total_items,
            COALESCE(SUM(debit), 0) as total_debit,
            COALESCE(SUM(credit), 0) as total_credit,
            MIN(balance_date) as first_entry_date
            FROM {$wpdb->prefix}dokan_vendor_balance
            WHERE vendor_id = %d
            AND DATE(balance_date) >= %s
            AND DATE(balance_date) <= %s
            AND ( ( trn_type = 'dokan_orders' AND status IN ('{$status}') )
            OR trn_type IN ( 'dokan_withdraw', 'dokan_refund', 'gateway_fee' ) )",
            $this->statement_data->get_vendor_id(),
            $this->statement_data->get_start_date(),
            $this->statement_data->get_end_date()
        );

        /**
         * Filters for the summary data SQL query.
         *
         * @since 4.0.0
         *
         * @param string $sql
         * @param string $status
         * @param object $statement_data
         */
        $sql = apply_filters( 'dokan_report_statement_summary_query', $sql, $status, $this->statement_data );

        // Get counts and totals in a single query.
        $results = $wpdb->get_row( $sql ); // phpcs:ignore

        /**
         * Filter the summary data results.
         *
         * @since 4.0.0
         *
         * @param object  $results
         * @param string  $status
         * @param object  $statement_data
         */
        $results = apply_filters( 'dokan_report_statement_summary_results', $results, $status, $this->statement_data );

        // Set total items
        $this->statement_data->set_total_items_count( (int) $results->total_items );

        // Opening balance will be calculated up to start date
        $opening_balance = $this->statement_data->get_opening_balance();

        // Calculate final balance
        $total_debit  = (float) $results->total_debit;
        $total_credit = (float) $results->total_credit;
        $balance      = $opening_balance + ( $total_debit - $total_credit );

        $summary_data = [
            'total_items'  => (int) $results->total_items,
            'total_debit'  => $total_debit,
            'total_credit' => $total_credit,
            'balance'      => $balance,
        ];

        /**
         * Filters for the summary data.
         *
         * @since 4.0.0
         *
         * @param array   $summary
         * @param object  $results
         * @param float   $opening_balance
         */
        return apply_filters( 'dokan_report_statement_summary', $summary_data, $results, $opening_balance );
    }

    /**
     * Get statement csv data.
     *
     * @since 4.0.0
     *
     * @param array $args
     *
     * @return string|WP_Error|StatementData
     */
    public function get_statement_csv_data( $args = [] ) {
        $data = $this->get_statement_data( $args );

        if ( is_wp_error( $data ) ) {
            return $data;
        }

        $csv     = '';
        $headers = [
            esc_html__( 'Trn Date', 'dokan' ),
            esc_html__( 'Balance Date', 'dokan' ),
            esc_html__( 'ID', 'dokan' ),
            esc_html__( 'Type', 'dokan' ),
            esc_html__( 'Debit', 'dokan' ),
            esc_html__( 'Credit', 'dokan' ),
            esc_html__( 'Balance', 'dokan' ),
        ];

        /**
         * Filters for the CSV headers.
         *
         * @since 4.0.0
         *
         * @param array    $headers
         * @param stdClass $data
         */
        $headers = apply_filters( 'dokan_report_statement_csv_headers', $headers, $data );

        $csv .= implode( ', ', $headers ) . ', ' . "\r\n";

        // Add opening balance row.
        $opening_row = [
            $data->get_start_date(),
            '--',
            '#--',
            esc_html__( 'Opening Balance', 'dokan' ),
            '--',
            '--',
            number_format( $data->get_opening_balance(), 4, '.', '' ),
        ];

        /**
         * Filters for the CSV opening balance row.
         *
         * @since 4.0.0
         *
         * @param array    $opening_row
         * @param stdClass $data
         */
        $opening_row = apply_filters( 'dokan_report_statement_csv_opening_row', $opening_row, $data );

        $csv .= implode( ', ', $opening_row ) . ', ' . "\r\n";

        // Add statement entries
        foreach ( $data->get_entries() as $entry ) {
            // Skip opening balance as we've already added it
            if ( $entry['trn_type'] === 'opening_balance' ) {
                continue;
            }

            $row = [
                dokan_current_datetime()->modify( $entry['trn_date'] )->format( 'Y-m-d' ),
                dokan_current_datetime()->modify( $entry['balance_date'] )->format( 'Y-m-d' ),
                '#' . $entry['trn_id'],
                $entry['trn_title'],
                number_format( $entry['debit'], 4, '.', '' ),
                number_format( $entry['credit'], 4, '.', '' ),
                number_format( $entry['balance'], 4, '.', '' ),
            ];

            /**
             * Filters for the CSV entry row.
             *
             * @since 4.0.0
             *
             * @param array $row
             * @param array $entry
             */
            $row  = apply_filters( 'dokan_report_statement_csv_row', $row, $entry );
            $csv .= implode( ', ', $row ) . ', ' . "\r\n";
        }

        /**
         * Filter the final CSV data
         *
         * @since 4.0.0
         *
         * @param string   $csv
         * @param stdClass $data
         * @param array    $args
         */
        return apply_filters( 'dokan_report_statement_csv_data', $csv, $data, $args );
    }
}
