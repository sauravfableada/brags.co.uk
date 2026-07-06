<?php

namespace WeDevs\DokanPro\Payments;

use DokanPro\Modules\Subscription\Helper as SubscriptionHelper;
use WC_Order;
use WeDevs\Dokan\Models\VendorBalance;

class GatewayFeeHandler {

    /**
     * Register the hooks for the GatewayFeeHandler.
     *
     * @return void
     */
    public function __construct() {
        add_action( 'dokan_process_payment_gateway_fee', [ $this, 'process_payment_gateway_fee' ], 10, 3 );
    }

    /**
     * Store gateway fee in order meta
     *
     * @param float $processing_fee
     * @param WC_Order $order
     * @param string $gateway_id
     *
     * @since 4.1.1
     *
     * return void
     */
    public function process_payment_gateway_fee( float $processing_fee, WC_Order $order, string $gateway_id ) {
        if ( ! apply_filters( 'dokan_should_process_payment_gateway_fee', true, $processing_fee, $order, $gateway_id ) ) {
			return;
		}
        $order = wc_get_order( $order );
        if ( ! $order ) {
            return; // If the order does not exist, skip processing
        }

        if ( $order->get_payment_method() !== $gateway_id ) {
            return; // Skip if the order's payment method does not match the gateway ID
        }

        $gateway_title = ucwords( str_replace( [ '_', '-' ], ' ', $gateway_id ) );

        // update the parent order meta with the processing fee
        $order->update_meta_data( 'dokan_gateway_fee', $processing_fee );

        /* translators: 1) gateway title, 2) processing fee with currency */
        $order->add_order_note( sprintf( __( '[%1$s] Gateway processing fee: %2$s', 'dokan' ), $gateway_title, wc_price( $processing_fee, [ 'currency' => $order->get_currency() ] ) ) );
        $order->save();

        // Get all sub orders for the parent order
        $orders = dokan()->commission->get_all_order_to_be_processed( $order );

        $paid_by_seller = $order->get_meta( 'dokan_gateway_fee_paid_by' ) === 'seller';

        foreach ( $orders as $sub_order ) {
            $fee_for_suborder = $this->get_fee_for_suborder( $processing_fee, $sub_order, $order );
            $vendor_gateway_fee = $sub_order->get_meta( 'dokan_vendor_gateway_fee' );
            // If the vendor gateway fee is already set, use it instead of calculating again.
            if ( ! empty( $vendor_gateway_fee ) ) {
                $fee_for_suborder = wc_format_decimal( $vendor_gateway_fee );
            }
            if ( $fee_for_suborder <= 0 ) {
                continue; // Skip if the vendor ID or gateway fee is not available.
            }
            $sub_order->update_meta_data( 'dokan_gateway_fee', $fee_for_suborder );
            $sub_order->update_meta_data( 'dokan_gateway_fee_paid_by', $paid_by_seller ? 'seller' : 'admin' );
            $sub_order->save();

            do_action( 'dokan_gateway_fee_after_save', $sub_order, $fee_for_suborder, $gateway_id );

            if ( ! $paid_by_seller ) {
                // If the seller does not pay the fee, skip processing this suborder.
                continue;
            }

			$this->store_gateway_fee_vendor_balance( $sub_order, $fee_for_suborder, $gateway_title );
        }

        do_action( 'dokan_processed_payment_gateway_fee', $processing_fee, $order, $gateway_id );
    }

    /**
     * Store gateway fee in vendor balance table
     *
     * @param WC_Order $sub_order
     * @param $processing_fee
     * @param string $gateway_title
     *
     * @since 4.1.1
     *
     * @return void
     */
    private function store_gateway_fee_vendor_balance( WC_Order $sub_order, $processing_fee, string $gateway_title ) {
        if ( class_exists( SubscriptionHelper::class ) ) {
			if ( SubscriptionHelper::is_vendor_subscription_order( $sub_order ) ) {
				return; // Skip storing in vendor balance for subscription orders
			}
        }

        $order_id = $sub_order->get_id();
        $vendor_id = dokan_get_seller_id_by_order( $sub_order->get_id() );

        global $wpdb;
        // Check if the record already exists
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}dokan_vendor_balance WHERE vendor_id = %d AND trn_id = %d AND trn_type = %s",
                $vendor_id,
                $order_id,
                'gateway_fee'
            )
        );

        if ( $exists ) {
            return; // If the record already exists, skip inserting
        }

        $vendor_balance = dokan()->get_container()->get( VendorBalance::class );

        $vendor_balance->set_vendor_id( $vendor_id );
        $vendor_balance->set_trn_id( $order_id );
        $vendor_balance->set_trn_type( 'gateway_fee' );
        $vendor_balance->set_particulars( 'Gateway Fee – ' . $gateway_title );
        $vendor_balance->set_credit( $processing_fee );
        $vendor_balance->set_trn_date( current_time( 'mysql' ) );
        $vendor_balance->set_balance_date( current_time( 'mysql' ) );
        $vendor_balance->set_status( 'approved' );
        $vendor_balance->save();
    }

    /**
     * Retrieves the processing for suborder.
     *
     * @since 4.1.1
     *
     * @param float    $processing_fee
     * @param WC_Order $suborder
     * @param WC_Order $order
     *
     * @return float|string
     */
    private static function get_fee_for_suborder( float $processing_fee, WC_Order $suborder, WC_Order $order ) {
        $order_total    = $order->get_total();
		$suborder_total = $suborder->get_total();

		if ( $order_total <= 0 ) {
			return 0.0;
		}
        $stripe_fee_for_vendor = $processing_fee * ( $suborder_total / $order_total );
        return wc_format_decimal( $stripe_fee_for_vendor );
    }
}
