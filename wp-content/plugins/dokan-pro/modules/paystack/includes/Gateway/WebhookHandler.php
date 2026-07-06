<?php

namespace WeDevs\DokanPro\Modules\Paystack\Gateway;

use Exception;
use WeDevs\Dokan\Exceptions\DokanException;
use WeDevs\DokanPro\Modules\Paystack\Api\Transaction;
use WeDevs\DokanPro\Modules\Paystack\Orders\OrderController;
use WeDevs\DokanPro\Modules\Paystack\Support\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class WebhookHandler
 *
 * @package WeDevs\DokanPro\Modules\Paystack\Gateway
 *
 * @since 4.1.1
 */
class WebhookHandler {
    /**
     * Webhook prefix.
     *
     * @since 4.1.1
     *
     * @var string
     */
    private static string $prefix = 'dokan-paystack';

    /**
     * Class constructor.
     *
     * @since 4.1.1
     *
     * @return void
     */
    public function __construct() {
        // Payment listener/API hook.
		add_action( 'woocommerce_api_' . self::$prefix, [ $this, 'process_webhooks' ] );
    }

    /*
     * Get Webhook URL.
     *
     * @since 4.1.1
     *
     * @return string Webhook URL
     */
    public static function get_webhook_url(): string {
        return WC()->api_request_url( self::$prefix );
    }

    /**
     * Process Webhook.
     *
     * @since 4.1.1
     *
     * @return void
     * @throws Exception If any error occurs during webhook processing
     */
    public function process_webhooks(): void {
        $server = $_SERVER;// phpcs:ignore
        if ( ! array_key_exists( 'HTTP_X_PAYSTACK_SIGNATURE', $server ) ) {
			exit;
		}
        // Get the request body
        $body = file_get_contents( 'php://input' );

        // Verify webhook signature
        if ( ! $this->verify_webhook_signature( $body ) ) {
            dokan_log( 'Paystack Webhook: Invalid signature' );
            http_response_code( 400 );
            exit;
        }

        // Parse the webhook data
        $webhook_data = json_decode( $body, true );
        // Process the webhook event
        $this->process_webhook_event( $webhook_data );

        // Return success response to Paystack
        http_response_code( 200 );
        exit;
    }

    /**
     * Verify webhook signature from Paystack.
     *
     * @since 4.1.1
     *
     * @param string $body Request body
     *
     * @return bool
     */
    private function verify_webhook_signature( string $body ): bool {
        $signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? ''; // phpcs:ignore

        if ( empty( $signature ) ) {
            return false;
        }
		// Check if the signature matches the expected hash
		if ( $signature !== hash_hmac( 'sha512', $body, Helper::get_api_secret() ) ) {
			return false;
		}

        return true;
    }

    /**
     * Process webhook event based on an event type.
     *
     *
     * @param array $webhook_data Webhook data from Paystack
     *
     * @return void
     * @throws Exception If any error occurs during event processing
     */
    private function process_webhook_event( array $webhook_data ): void {
        $event = $webhook_data['event'] ?? '';
        $data = $webhook_data['data'] ?? [];

        if ( empty( $event ) || empty( $data ) ) {
            return;
        }

        switch ( $event ) {
            case 'charge.success':
                $this->handle_charge_success( $data );
                break;

            default:
                dokan_log( "Paystack Webhook: Unhandled event type: $event" );
                break;
        }
    }

    /**
     * Handle successful charge webhook.
     *
     * @since 4.1.1
     *
     * @param array $data Transaction data
     *
     * @return void
     * @throws Exception If any error occurs during order update
     */
    private function handle_charge_success( array $data ): void {
        $reference = $data['reference'] ?? '';

        // Get order ID from reference or metadata
        $order_id = Helper::get_order_id( $reference );

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        // Verify that the reference from the order matches the transaction reference
        $order_ref = $order->get_meta( '_dokan_paystack_reference' );
        if ( $order_ref !== $reference ) {
            dokan_log( "[Paystack] Webhook: Reference mismatch for order $order_id. Expected: $order_ref, Got: $reference" );
            return;
        }

        // Prevent duplicate processing
        if ( $order->get_meta( '_dokan_paystack_webhook_processed' ) ) {
            dokan_log( "[Paystack] Webhook: Webhook already processed  for order $order_id" );
            return;
        }

        // Check if payment is already completed
        if ( $order->is_paid() ) {
            return;
        }

        try {
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

            OrderController::process_verified_payment( $order, $transaction );

            // Mark as processed
			$order->update_meta_data( '_dokan_paystack_webhook_processed', time() );
			$order->save();

            do_action( 'dokan_paystack_charged_succeeded', $order, $transaction );
        } catch ( Exception $e ) {
            dokan_log( "Paystack Webhook: Error processing charge success for order $order_id. Error: " . $e->getMessage() );
            throw new Exception( 'Error processing charge success webhook.' );
        }
    }
}
