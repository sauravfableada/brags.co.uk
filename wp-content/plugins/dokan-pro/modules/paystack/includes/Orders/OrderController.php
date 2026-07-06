<?php

namespace WeDevs\DokanPro\Modules\Paystack\Orders;

use Exception;
use WC_Order;
use WeDevs\Dokan\Cache;
use WeDevs\Dokan\Exceptions\DokanException;
use WeDevs\Dokan\Models\VendorBalance;
use WeDevs\DokanPro\Modules\Paystack\Api\Transaction;
use WeDevs\DokanPro\Modules\Paystack\Support\Helper;
use WP_Error;

class OrderController {
    /**
     * OrderController constructor.
     *
     * @since 4.1.1
     */
	public function __construct() {
        add_action( 'wp', [ $this, 'maybe_process_order_redirect' ] );
		add_action( 'dokan_paystack_payment_verified', [ $this, 'process_vendor_payments' ] );
        add_action( 'dokan_paystack_charged_succeeded', [ $this, 'process_vendor_payments' ] );
	}


    /**
     * Processes order redirect if necessary.
     *
     * @since 4.1.1
     *
     * @return void
     */
    public function maybe_process_order_redirect() {
        // Check if we are on the order received page
        $request = wp_unslash( $_GET ); // phpcs:ignore
        if (
            ! is_order_received_page() ||
            empty( $request['wc_payment_method'] ) ||
            Helper::get_gateway_id() !== sanitize_text_field( wp_unslash( $request['wc_payment_method'] ) ) ||
            empty( $request['order_id'] )
        ) {
            return;
        }

        $order = wc_get_order( absint( $request['order_id'] ) );
        if ( ! $order ) {
            return;
        }

        $reference = $request['paystack_txnref'] ?? $request['reference'];
        try {
            $this->verify_payment( $reference, $order );
        } catch ( Exception $e ) {
            // Log the error but do not interrupt the user flow
            dokan_log( sprintf( 'Paystack payment verification on redirect failed: %s', $e->getMessage() ) );
            // translators: 1: Error message.
            $order->update_status( 'failed', sprintf( __( 'Payment failed: %s', 'dokan' ), $e->getMessage() ) );
            wc_add_notice( $e->getMessage(), 'error' );
        }
    }

    /**
     * Verify payment with Paystack API.
     *
     * @since 4.1.1
     *
     * @param string $reference Transaction reference
     * @param WC_Order $order WooCommerce order object
     *
     * @return void
     * @throws Exception If verification fails or transaction is not successful
     */
    private function verify_payment( string $reference, WC_Order $order ): void {
        // Verify that the reference from the order matches the transaction reference
        $order_ref = $order->get_meta( '_dokan_paystack_reference' );
        if ( $order_ref !== $reference ) {
            throw new DokanException(
                'dokan_paystack_invalid_reference',
                esc_html__( 'Invalid payment reference.', 'dokan' )
            );
        }
        try {
            // Use the Transaction service to verify the payment
            $response = Transaction::verify( $reference );
            $transaction = $response['data'];

            // Check if the transaction was successful
            if ( ! isset( $transaction['status'] ) && $transaction['status'] !== 'success' ) {
				dokan_log( 'Paystack transaction verification failed: ' . $transaction['message'] );
                throw new DokanException(
                    'dokan_paystack_verification_failed',
                    esc_html__( 'Payment verification failed.', 'dokan' )
                );
            }

            // Verify order amount matches
            $order_total = Helper::format_paystack_amount( $order->get_total() ); // Convert to kobo/cents
            $paid_amount = absint( $transaction['amount'] );

            if ( $order_total !== $paid_amount ) {
                dokan_log( "Paystack amount mismatch: Order $order_total, Paid $paid_amount" );
                throw new DokanException(
                    'dokan_paystack_amount_mismatch',
                    esc_html__( 'Payment amount does not match order total.', 'dokan' )
                );
            }
            $payment_currency = $transaction['currency'];
            $order_currency = $order->get_currency();
            if ( $payment_currency !== $order->get_currency() ) {
                dokan_log( "Paystack currency mismatch: Order $order_currency, Paid $payment_currency" );
                throw new DokanException(
                    'dokan_paystack_currency_mismatch',
                    esc_html__( 'Payment currency does not match order currency.', 'dokan' )
                );
            }
            self::process_verified_payment( $order, $transaction );
            do_action( 'dokan_paystack_payment_verified', $order, $transaction );
        } catch ( Exception $e ) {
            dokan_log( 'Paystack verification exception: ' . $e->getMessage() );
            throw new DokanException(
                'dokan_paystack_verification_exception',
                esc_html__( 'Payment verification failed.', 'dokan' )
            );
        }
    }

    /**
     * Update order after successful payment.
     *
     * @since 4.1.1
     *
     * @param WC_Order $order WooCommerce order
     * @param array $transaction Transaction data from Paystack
     *
     * @return void
     * @throws Exception If any error occurs during order update
     */
    public static function process_verified_payment( WC_Order $order, array $transaction ): void {
        $transaction_id = $transaction['id'];
        $reference      = $transaction['reference'];
        $channel        = $transaction['channel'];
        $fees           = Helper::format_paystack_amount( $transaction['fees'], true ); // Convert to base currency

        // Set transaction ID
        if ( ! empty( $transaction_id ) ) {
            $order->set_transaction_id( $transaction_id );
        }
        // Set the order processing fee paid by the seller or admin
        $seller_pays_gateway_fee = Helper::seller_pays_gateway_fee();
        $order->update_meta_data( 'dokan_gateway_fee_paid_by', $seller_pays_gateway_fee ? 'seller' : 'admin' );

        /*
         * Update the vendor gateway fee meta
         *
         * This meta will be used to determine who pays the gateway fee
         * when processing the payment gateway fee.
         *
         * @see https://paystack.com/docs/payments/multi-split-payments/#fees-on-multi-split
         */

        if ( $seller_pays_gateway_fee && isset( $transaction['split']['shares'] ) ) {
            try {
                // If the order has suborders, process each suborder
                $orders = $order->get_meta( 'has_sub_order' )
                ? dokan()->order->get_child_orders( $order )
                : [ $order ];

                // Loop through each suborder to update the vendor gateway fee
                foreach ( $orders as $sub_order ) {
                    $vendor_id = dokan_get_seller_id_by_order( $sub_order );
                    $seller_account_code = Helper::get_seller_account_id( $vendor_id );
                    $distributed_fees = OrderManager::get_distributed_fees( $transaction['split'], $seller_account_code );

                    /*
                     * Apply filters to allow customization of the distributed fees.
                     *
                     * @param array $distributed_fees The distributed fees calculated for the suborder.
                     * @param array $transaction_split The split details from the transaction.
                     * @param WC_Order $sub_order The suborder being processed.
                     */
                    $distributed_fees = apply_filters(
                        'dokan_paystack_distribute_gateway_fee',
                        $distributed_fees,
                        $transaction['split'],
                        $sub_order
                    );

                    // If the seller account code matches the payment account code, update the gateway fee
					if ( $distributed_fees !== false ) {
                        $vendor_fee = Helper::format_paystack_amount( $distributed_fees['vendor_fee'], true );
                        $admin_fee = Helper::format_paystack_amount( $distributed_fees['admin_fee'], true );
						$sub_order->update_meta_data( 'dokan_vendor_gateway_fee', $vendor_fee );
						$sub_order->update_meta_data( 'dokan_admin_gateway_fee', $admin_fee );
                        $sub_order->save();
					} else {
						// Log if the seller account code does not match the payment account code
						dokan_log( sprintf( 'Seller account code %s does not match payment account code for order %d', $seller_account_code, $sub_order->get_id() ) );
					}
				}
                // update admin commission gateway fee
                $total_admin_fee = Helper::format_paystack_amount( $transaction['split']['shares']['fees'], true );
                $order->update_meta_data( 'dokan_admin_gateway_fee', $total_admin_fee );
                $order->save();
            } catch ( Exception $e ) {
                // Log the error if unable to update the order meta
                dokan_log( sprintf( 'Error updating gateway fee for order %d: %s', $order->get_id(), $e->getMessage() ) );
            }
        }

        /* Process the gateway fees
        * Make sure to call this after updating the vendor gateway fee meta
        *
        * @see \WeDevs\DokanPro\Modules\Paystack\Payments\GatewayFeeHandler::process_payment_gateway_fee()
        */
        do_action( 'dokan_process_payment_gateway_fee', $fees, $order, Helper::get_gateway_id() );

        /* translators: 1: Paystack generated Order ID  */
        $order->add_order_note( sprintf( __( 'Paystack Transaction ID: %s.', 'dokan' ), $transaction_id ) );

        // Get gateway title for notes
        $gateway_title = Helper::get_gateway_title();

        // Add order notes
        $order->add_order_note(
            sprintf(
                // translators: %1$s: Gateway title, %2$s: Transaction ID, %3$s: Reference, %4$s: Channel
                __( 'Payment completed via [%1$s]. Transaction ID: %2$s, Reference: %3$s, Channel: %4$s', 'dokan' ),
                $gateway_title,
                $transaction_id,
                $reference,
                $channel
            )
        );

        // Update order meta
        $order->update_meta_data( '_dokan_paystack_payment_capture_id', $transaction_id );
        $order->update_meta_data( '_dokan_paystack_channel', $channel );
        // Complete payment
        $order->payment_complete( $transaction_id );
        // Save order
        $order->save();

        /*
         * Trigger action after payment is completed.
         */
        do_action( 'dokan_paystack_payment_completed', $order, $transaction );
    }


	/**
	 * Process vendor payments for the order.
	 *
	 * This method updates the vendor's balance and inserts a credit entry
	 * in the vendor balance table after the order is completed.
	 *
	 * @since 4.1.1
	 *
	 * @param WC_Order $order The WooCommerce order object.
	 *
	 * @return WP_Error|void Returns WP_Error on failure, otherwise void.
	 */
	public function process_vendor_payments( WC_Order $order ) {
        // do something with the order
		$orders = $order->get_meta( 'has_sub_order' )
			? dokan()->order->get_child_orders( $order )
			: [ $order ];

		foreach ( $orders as $suborder ) {
			// Process vendor payments for each suborder
			$suborder_id        = $suborder->get_id();
			$vendor_id          = dokan_get_seller_id_by_order( $suborder_id );
            $vendor_earning     = dokan()->commission->get_vendor_earning_subtotal_by_order( $suborder_id );

			// update the vendor balance withdraw status
			global $wpdb;

            // Update Net amount in dokan orders.
            $updated = $wpdb->update(
                $wpdb->dokan_orders,
                [ 'net_amount' => $vendor_earning ],
                [ 'order_id' => $suborder_id ],
                [ '%f' ],
                [ '%d' ]
            );
            if ( false === $updated ) {
                return new WP_Error( 'update_dokan_order_error', sprintf( '[process_vendor_payments] Error while updating order table data: %1$s', $wpdb->last_error ) );
            }

            // Update vendor balance debit entry and vendor balance threshold date.
            $balance_date = dokan_current_datetime()->format( 'Y-m-d h:i:s' );
            VendorBalance::update_by_transaction(
                $suborder_id, VendorBalance::TRN_TYPE_DOKAN_ORDERS, [
					'debit' => $vendor_earning,
					'balance_date' => $balance_date,
				]
            );

            // Insert a vendor withdraw credit entry with vendor balance threshold date.
            $vendor_balance = dokan()->get_container()->get( VendorBalance::class );
            $vendor_balance->set_vendor_id( $vendor_id );
            $vendor_balance->set_trn_id( $suborder_id );
            $vendor_balance->set_trn_type( VendorBalance::TRN_TYPE_DOKAN_WITHDRAW );
            $vendor_balance->set_particulars( 'Paid Via ' . Helper::get_gateway_title() );
            $vendor_balance->set_credit( $vendor_earning );
            $vendor_balance->set_trn_date( $balance_date );
            $vendor_balance->set_balance_date( $balance_date );
            $vendor_balance->set_status( 'approved' );
            $vendor_balance->save();

			$suborder->update_meta_data( '_dokan_paystack_payment_withdraw_balance_added', 'yes' );
			$suborder->save();

            // clear cache
            // remove cache for seller earning
            $cache_key = "get_earning_from_order_table_{$suborder_id}_seller";
            Cache::delete( $cache_key );

            // remove cache for admin earning
            $cache_key = "get_earning_from_order_table_{$suborder_id}_admin";
            Cache::delete( $cache_key );
		}
    }
}
