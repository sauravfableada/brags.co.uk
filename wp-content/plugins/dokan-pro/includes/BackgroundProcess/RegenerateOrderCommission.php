<?php

namespace WeDevs\DokanPro\BackgroundProcess;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Background_Process', false ) ) {
    include_once dirname( WC_PLUGIN_FILE ) . 'includes/abstracts/class-wc-background-process.php';
}

use WC_Background_Process;
use WeDevs\Dokan\Cache;

/**
 * RewriteVariableProductsAuthor Class.
 *
 * @since 3.9.3
 */
class RegenerateOrderCommission extends WC_Background_Process {

    /**
     * Initiate a new background process.
     */
    public function __construct() {
        $this->action = 'dokan_pro_regenerated_order_commission';

        parent::__construct();
    }

    /**
     * Dispatch updater.
     *
     * Updater will still run via cron job if this fails for any reason.
     *
     * @since 3.9.3
     *
     * @return void
     */
    public function dispatch() {
        $dispatched = parent::dispatch();

        if ( is_wp_error( $dispatched ) ) {
            dokan_log(
                sprintf( 'Unable to dispatch Dokan variable product variations author update: %s', $dispatched->get_error_message() ),
                'error'
            );
        }
    }

    /**
     * Perform updates.
     *
     * @since 3.9.3
     *
     * @param array $args
     *
     * @return bool|array
     */
    public function task( $args ) {
        if ( empty( $args['paged'] ) ) {
            return false;
        }

        $paged = absint( $args['paged'] );

        $args = [
            'type'   => 'shop_order',
            'return' => 'ids',
            'limit'  => 10,
            'paged'  => $paged,
        ];

        $orders = dokan()->order->all( $args );

        if ( empty( $orders ) ) {
            return false;
        }

        foreach ( $orders as $order_id ) {
            $this->sync_order_commission( $order_id );
        }

        return [
            'paged' => ++$paged,
        ];
    }

    /**
     * Sync order commission.
     *
     * @since 3.9.3
     *
     * @param int $order_id
     *
     * @return void
     */
    private function sync_order_commission( int $order_id ) {
        global $wpdb;

        $order = wc_get_order( $order_id );

        if ( ! $order || $order->get_meta( 'has_sub_order', true ) ) {
            return;
        }

        // if the order has been refunded, do not recalculate the commission value
        if ( 'refunded' === $order->get_status() || ! empty( $order->get_total_refunded() ) ) {
            return;
        }

        $seller_id = dokan_get_seller_id_by_order( $order->get_id() );
        if ( ! $seller_id ) {
            return;
        }

        // short-circuit method dokan()->commission->get_earning_from_order_table() to exclude getting earning from order table
        $cache_key = "get_earning_from_order_table_{$order_id}_{seller}";
        Cache::set( $cache_key, null );

        $cache_key = "get_earning_from_order_table_{$order_id}_{admin}";
        Cache::set( $cache_key, null );

        $net_amount = dokan()->commission->get_earning_by_order( $order, 'seller' );
        $net_amount = apply_filters( 'dokan_order_net_amount', $net_amount, $order );

        $gateway_fee = $order->get_meta( 'dokan_gateway_fee' );
        if ( ! empty( $gateway_fee ) && 'seller' === $order->get_meta( 'dokan_gateway_fee_paid_by' ) ) {
            $gateway_fee = $order->get_meta( 'dokan_gateway_fee' );
            $net_amount  = $net_amount - $gateway_fee;
        }

        // get order data from dokan orders table
        $order_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, net_amount FROM $wpdb->dokan_orders WHERE order_id = %d",
                $order_id
            ),
            ARRAY_A
        );

        if ( empty( $order_data ) || wc_format_decimal( $order_data['net_amount'], '' ) === wc_format_decimal( $net_amount, '' ) ) {
            return;
        }

        $wpdb->update(
            $wpdb->dokan_orders,
            [
                'net_amount' => $net_amount,
            ],
            [
                'id' => $order_data['id'],
            ],
            [
                '%f',
            ],
            [
                '%d',
            ]
        );

        // update the vendor balance table
        $wpdb->update(
            $wpdb->dokan_vendor_balance,
            [
                'debit'        => $net_amount,
            ],
            [
                'trn_id'   => $order_id,
                'trn_type' => 'dokan_orders',
            ],
            [
                '%f',
            ],
            [
                '%d',
                '%s',
            ]
        );
    }

    /**
     * Complete the process.
     *
     * @since 3.9.3
     *
     * @return void
     */
    protected function complete() {
        set_transient( 'dokan_regenerate_order_commission_updated', true, HOUR_IN_SECONDS );
        dokan_log( 'Dokan order commission regenerated successfully.', 'info' );
        parent::complete();
    }
}
