<?php

namespace WeDevs\DokanPro\Modules\Paystack\Refund;

use Exception;
use WC_Order;
use WeDevs\Dokan\Exceptions\DokanException;
use WeDevs\DokanPro\Modules\Paystack\Api\Transaction;
use WeDevs\DokanPro\Modules\Paystack\Support\Helper;
use WeDevs\DokanPro\Refund\Refund;
use WeDevs\DokanPro\Modules\Paystack\Api\Refund as RefundApi;

class RefundHandler {
    public function __construct() {
        add_action( 'dokan_refund_approve_before_insert', [ $this, 'process_refund' ] );
        add_filter( 'dokan_pro_exclude_auto_approve_api_refund_request', [ $this, 'exclude_auto_approve_api_refund' ] );
    }

	/**
	 * This method will refund payments to seller.
	 *
     * @param Refund $refund
     *
	 * @since 4.1.1
	 *
	 * @return void
	 * @throws Exception
	 */
    public function process_refund( $refund ) {
        // get code editor suggestion on refund object
        if ( ! $refund instanceof Refund ) {
            return;
        }

        $order = wc_get_order( $refund->get_order_id() );

        // return if $order is not an instance of WC_Order
        if ( ! $order instanceof WC_Order ) {
            return;
        }

        // return if not paid with dokan paystack payment gateway
        if ( Helper::get_gateway_id() !== $order->get_payment_method() ) {
            return;
        }

        // Get parent order id, because charge id is stored on parent order id
        $parent_order_id = $order->get_parent_id() ? $order->get_parent_id() : $order->get_id();
        $parent_order = wc_get_order( $parent_order_id );
        if ( ! $parent_order ) {
            return;
        }

        // Step 1: check if reference id exists
        $reference = $parent_order->get_meta( '_dokan_paystack_reference', true );

        if ( empty( $reference ) ) {
            // we can't automatically reverse vendor balance, so manual refund and approval is required
            $order->add_order_note( __( 'Dokan Paystack Refund Error: Automatic refund is not possible for this order.', 'dokan' ) );
            return;
        }

        try {
            // step 2: process customer refund on the paystack
			$paystack_refund = $this->refund( $refund, $order, $reference );
			$refund_message = sprintf(
            /* translators: 1: Refund amount 2: Refund transaction id 3: Refund message */
                __( 'Refunded from admin paystack account: %1$s. Refund ID: %2$s. Reason - %3$s', 'dokan' ),
                wc_price( $refund->get_refund_amount(), [ 'currency' => $order->get_currency() ] ),
                $paystack_refund['data']['id'],
                $refund->get_refund_reason()
			);
			$order->add_order_note( $refund_message );
			// save order meta
			$order->save();
        } catch ( Exception $e ) {
            $error_message = sprintf(
                /* translators: 1: refund id 2: order id 3: error message */
                __( 'Dokan Paystack Refund Error: Refund failed on Paystack End. Manual Refund Required. Refund ID: %1$d, Order ID: %2$d, Error Message: %3$s', 'dokan' ),
                $refund->get_id(),
                $refund->get_order_id(),
                $e->getMessage()
            );
            dokan_log( $error_message, 'error' );
            $order->add_order_note( $error_message );
        }
    }

    /**
     * Refunds the payment using Paystack API.
     *
     * @param Refund $refund
     * @param WC_Order $order
     * @param string $reference
     * @param float $amount
     *
     * @return array
     * @throws DokanException
     */
    private function refund( $refund, $order, $reference ): array {
        $amount = $refund->get_refund_amount();
        // Check if the refund amount is valid
        if ( $amount <= 0 ) {
            throw new DokanException(
                'dokan_paystack_invalid_refund_amount',
                esc_html__( 'Invalid refund amount.', 'dokan' )
            );
        }
        // Verify the transaction
        $transaction = Transaction::verify( $reference );

        // check the refund amount is less than or equal to the transaction amount
        if ( $transaction['data']['amount'] < Helper::format_paystack_amount( $amount ) ) {
            throw new DokanException(
                'dokan_paystack_refund_amount_exceeds_transaction',
                esc_html__( 'Refund amount exceeds the transaction amount.', 'dokan' )
            );
        }

        do_action( 'dokan_paystack_refund_before_create', $order, $refund );

        // Prepare refund arguments
        $args = apply_filters(
            'dokan_paystack_refund_args', [
				'transaction'   => $reference,
				'amount'        => Helper::format_paystack_amount( $amount ),
			], $refund, $order
        );
        // Call the Paystack API to process the refund
        $response = RefundApi::create( $args );

        do_action( 'dokan_paystack_refund_after_created', $order, $response );

        return $response;
    }

    /**
     * Exclude dokan paystack from auto approve api refund.
     *
     * @since 4.1.1
     *
     * @param array $payment_gateways
     *
     * @return array
     */
    public function exclude_auto_approve_api_refund( array $payment_gateways ): array {
        $payment_gateways[] = Helper::get_gateway_id();
        return $payment_gateways;
    }
}
