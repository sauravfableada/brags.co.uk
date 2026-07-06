<?php

namespace WeDevs\DokanPro\Modules\StripeExpress\Api;

defined( 'ABSPATH' ) || exit; // Exit if called directly

use Exception;
use WeDevs\Dokan\Exceptions\DokanException;
use WeDevs\DokanPro\Modules\StripeExpress\Support\Api;
use WeDevs\DokanPro\Modules\StripeExpress\Support\Helper;

/**
 * API handler class for paymrnt intent
 *
 * @since 3.6.1
 *
 * @package WeDevs\DokanPro\Modules\StripeExpress\Api
 */
class PaymentIntent extends Api {

    /**
     * Creates a payment intent.
     *
     * @since 3.6.1
     *
     * @param array $args
     *
     * @return \Stripe\PaymentIntent
     * @throws DokanException
     */
    public static function create( $args ) {
        $defaults = [
            'amount'   => 0,
            'currency' => strtolower( get_woocommerce_currency() ),
        ];

        // Use payment_method_types for saved cards; automatic_payment_methods for new/express payments.
        // Stripe API: these are mutually exclusive - cannot use both.
        if ( ! empty( $args['payment_method_types'] ) ) {
            $defaults['payment_method_types'] = $args['payment_method_types'];
        } else {
            $defaults['automatic_payment_methods'] = [
                'enabled' => true,
            ];
        }

        $args = wp_parse_args( $args, $defaults );

        // Remove automatic_payment_methods when payment_method_types is explicitly set.
        if ( ! empty( $args['payment_method_types'] ) ) {
            unset( $args['automatic_payment_methods'] );
        }

        // Stripe rejects empty string for 'customer' - remove if empty to avoid API error.
        if ( array_key_exists( 'customer', $args ) && empty( trim( (string) $args['customer'] ) ) ) {
            unset( $args['customer'] );
        }

        if ( (int) $args['amount'] <= 0 ) {
            throw new DokanException(
                'dokan-stripe-express-payment-intent-error',
                esc_html__( 'Could not create payment intent. Error: Amount cannot be negative.', 'dokan' )
            );
        }

        try {
            return self::api()->paymentIntents->create( $args );
        } catch ( Exception $e ) {
            Helper::log( sprintf( 'Could not create payment intent. Error: %s', $e->getMessage() ), 'Payment Intent' );
            Helper::log( 'Data: ' . print_r( $args, true ) );
            throw new DokanException(
                'dokan-stripe-express-payment-intent-error',
                /* translators: error message */
                sprintf( esc_html__( 'Could not create payment intent. Error: %s', 'dokan' ), esc_html( $e->getMessage() ) )
            );
        }
    }

    /**
     * Updates a payment intent.
     *
     * @since 3.6.1
     *
     * @param string $intent_id
     * @param array $data
     *
     * @return \Stripe\PaymentIntent
     * @throws DokanException
     */
    public static function update( $intent_id, $data ) {
        // Stripe rejects empty string for 'customer' - remove if empty.
        if ( array_key_exists( 'customer', $data ) && empty( trim( (string) $data['customer'] ) ) ) {
            unset( $data['customer'] );
        }

        try {
            return self::api()->paymentIntents->update( $intent_id, $data );
        } catch ( Exception $e ) {
            Helper::log( sprintf( 'Could not update payment intent: %1$s. Error: %2$s', $intent_id, $e->getMessage() ), 'Payment Intent' );
            Helper::log( 'Data: ' . print_r( $data, true ) );
            throw new DokanException( 'dokan-stripe-express-payment-intent-error', esc_html( $e->getMessage() ) );
        }
    }

    /**
     * Retrieves a Payment intent.
     *
     * @since 3.6.1
     *
     * @param string $intent_id
     * @param array  $args      (optional)
     *
     * @return \Stripe\PaymentIntent|false
     */
    public static function get( $intent_id, $args = [] ) {
        try {
            return self::api()->paymentIntents->retrieve( $intent_id, $args );
        } catch ( Exception $e ) {
            Helper::log( sprintf( 'Could not retrieve payment intent for id: %1$s. Error: %2$s', $intent_id, $e->getMessage() ) );
            return false;
        }
    }
}
