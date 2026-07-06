<?php

namespace WeDevs\DokanPro\Reports;

/**
 * Report Statements Manager Class.
 *
 * @since 4.0.0
 */
class StatementData {

    protected string $start_date;

    protected string $end_date;

    protected int $vendor_id = 0;

    protected ?float $opening_balance = null;

    protected int $total_items;

    protected array $entries;

    public function get_start_date(): string {
        if ( empty( $this->start_date ) ) {
            $this->start_date = dokan_current_datetime()->modify( 'first day of this month' )->format( 'Y-m-d' );
        }

        return $this->start_date;
    }

    public function set_start_date( string $start_date ): void {
        if ( ! empty( $start_date ) ) {
            $this->start_date = dokan_current_datetime()->modify( $start_date )->format( 'Y-m-d' );
        }
    }

    public function get_end_date(): string {
        if ( empty( $this->end_date ) ) {
            $this->end_date = dokan_current_datetime()->format( 'Y-m-d' );
        }

        return $this->end_date;
    }

    public function set_end_date( string $end_date ): void {
        if ( ! empty( $end_date ) ) {
            $this->end_date = dokan_current_datetime()->modify( $end_date )->format( 'Y-m-d' );
        }
    }

    public function get_vendor_id(): int {
        return $this->vendor_id;
    }

    public function set_vendor_id( int $vendor_id ): void {
        $this->vendor_id = $vendor_id;
    }

    public function get_opening_balance(): float {
        if ( $this->opening_balance === null ) {
            $this->set_opening_balance();
        }

        return $this->opening_balance;
    }

    public function set_opening_balance(): void {
        $vendor                = dokan()->vendor->get( $this->get_vendor_id() );
        $this->opening_balance = $vendor->get_balance( // Get opening balance
            false,
            dokan_current_datetime()->modify( $this->get_start_date() . ' -1 days' )->format( 'Y-m-d' )
        );
    }

    public function get_total_items_count(): int {
        return $this->total_items ?? 0;
    }

    public function set_total_items_count( int $total_items ): void {
        if ( empty( $total_items ) ) {
            $total_items = 0;
        }

        $this->total_items = $total_items;
    }

    public function get_entries(): array {
        if ( empty( $this->entries ) ) {
            $this->process_statement_data();
        }

        return $this->entries;
    }

    public function set_entries( array $entries ): void {
        $this->entries = $entries;
    }

    public function process_statement_data( $entries = [] ) {
        $processed_entries = [];
        $balance           = $this->get_opening_balance();

        // Add opening balance if exists
        $processed_entries[] = apply_filters(
            'dokan_report_statement_opening_entry',
            [
                'id'           => 0,
                'vendor_id'    => $this->get_vendor_id(),
                'trn_id'       => null,
                'trn_type'     => 'opening_balance',
                'perticulars'  => esc_html__( 'Opening Balance', 'dokan' ),
                'debit'        => 0,
                'credit'       => 0,
                'status'       => '',
                'trn_date'     => null,
                'balance_date' => $this->get_start_date(),
                'balance'      => $balance,
                'trn_title'    => esc_html__( 'Opening Balance', 'dokan' ),
                'url'          => null,
            ],
            $balance,
            $this
        );

        // Process each raw statement entry
        foreach ( $entries as $entry ) {
            $balance += ( $entry->debit - $entry->credit );

            // Get type data for title and URL
            $type_data = $this->get_transaction_type_data( $entry );

            // Create processed entry with balance and formatted data
            $processed_entry = [
                'id'           => (int) $entry->id,
                'vendor_id'    => (int) $entry->vendor_id,
                'trn_id'       => (int) $entry->trn_id,
                'trn_type'     => $entry->trn_type,
                'perticulars'  => $entry->perticulars,
                'debit'        => (float) $entry->debit,
                'credit'       => (float) $entry->credit,
                'status'       => $entry->status,
                'trn_date'     => $entry->trn_date,
                'balance_date' => $entry->balance_date,
                'balance'      => (float) $balance,
                'trn_title'    => $type_data['title'],
                'url'          => $type_data['url'],
            ];

            /**
             * Filters for the processed statement entry.
             *
             * @since 4.0.0
             *
             * @param array   $processed_entry
             * @param object  $entry
             * @param float   $balance
             * @param array   $type_data
             */
            $processed_entries[] = apply_filters( 'dokan_report_statement_processed_entry', $processed_entry, $entry, $balance, $type_data );
        }

        /**
         * Filters for all processed entries.
         *
         * @since 4.0.0
         *
         * @param array   $processed_entries
         * @param object  $statement_data
         */
        return apply_filters( 'dokan_report_statement_processed_entries', $processed_entries, $this );
    }

    /**
     * Get transaction type data.
     *
     * @since 4.0.0
     *
     * @param object $entry
     *
     * @return array
     */
    protected function get_transaction_type_data( $entry ) {
        $type_data = [];

        switch ( $entry->trn_type ) {
            case 'dokan_orders':
                $type_data = [
                    'title' => esc_html__( 'Order', 'dokan' ),
                    'url'   => wp_nonce_url(
                        add_query_arg(
                            [ 'order_id' => $entry->trn_id ],
                            dokan_get_navigation_url( 'orders' )
                        ),
                        'dokan_view_order'
                    ),
                ];
                break;

            case 'dokan_withdraw':
                $type_data = [
                    'title' => esc_html__( 'Withdraw', 'dokan' ),
                    'url'   => add_query_arg(
                        [ 'type' => 'approved' ],
                        dokan_get_navigation_url( 'withdraw' )
                    ),
                ];
                break;

            case 'dokan_refund':
                $type_data = [
                    'title' => esc_html__( 'Refund', 'dokan' ),
                    'url'   => wp_nonce_url(
                        add_query_arg(
                            [ 'order_id' => $entry->trn_id ],
                            dokan_get_navigation_url( 'orders' )
                        ),
                        'dokan_view_order'
                    ),
                ];
                break;
            case 'gateway_fee':
                $type_data = [
                    'title' => esc_html__( 'Gateway Fee', 'dokan' ),
                    'url'   => '',
                ];
                break;

            default:
                $type_data = [
                    'title' => $entry->trn_type,
                    'url'   => '',
                ];
                break;
        }

        /**
         * Filter the transaction type data
         *
         * @since 4.0.0
         *
         * @param array   $type_data
         * @param object  $entry
         */
        return apply_filters( 'dokan_report_statement_transaction_type_data', $type_data, $entry );
    }
}
