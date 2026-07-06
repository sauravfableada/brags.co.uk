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
 * @since 4.3.0
 *
 * @package WeDevs\DokanPro\Modules\StripeExpress\Api
 */
class PaymentMethodConfiguration extends Api {

    /**
     * Creates a payment method.
     *
     * @since 3.6.1
     *
     * @param array $args
     *
     * @return \Stripe\PaymentMethodConfiguration
     * @throws DokanException
     */
    public static function create( array $args ): \Stripe\PaymentMethodConfiguration {
        try {
            return static::api()->paymentMethodConfigurations->create( $args );
        } catch ( Exception $e ) {
            Helper::log( sprintf( 'Could not create payment method configuration. Error: %s', $e->getMessage() ), 'Payment Method Configuration' );
            Helper::log( 'Data: ' . print_r( $args, true ), 'Payment method configuration' );
            throw new DokanException(
                'dokan-stripe-express-payment-method-error',
                // translators: error message
                sprintf( esc_html__( 'Could not create payment method configuration. Error: %s', 'dokan' ), esc_html( $e->getMessage() ) )
            );
        }
    }

    /**
     * Updates a setup intent.
     *
     * @since 3.6.1
     *
     * @param string $method_id
     * @param array $data
     *
     * @return \Stripe\PaymentMethodConfiguration
     * @throws DokanException
     */
    public static function update( string $method_id, array $data ): \Stripe\PaymentMethodConfiguration {
        try {
            return static::api()->paymentMethodConfigurations->update( $method_id, $data );
        } catch ( Exception $e ) {
            Helper::log( sprintf( 'Could not update payment method configuration: %1$s. Error: %2$s', $method_id, $e->getMessage() ), 'Payment Method Configuration' );
            Helper::log( 'setup intent Data: ' . print_r( $data, true ) );
            throw new DokanException( 'dokan-stripe-express-payment-method-configuration-error', esc_html( $e->getMessage() ) );
        }
    }

    /**
     * Retrieves a payment method configuration.
     *
     * @since 3.6.1
     * @since 3.7.8 Added additional `$args` parameter.
     *
     * @param string $method_id
     * @param array $args      (Optional)
     *
     * @return \Stripe\PaymentMethodConfiguration|false
     */
    public static function get( string $method_id, array $args = [] ) {
        try {
            return static::api()->paymentMethodConfigurations->retrieve( $method_id, $args );
        } catch ( Exception $e ) {
            Helper::log( sprintf( 'Could not retrieve payment method configuration for id: %1$s. Error: %2$s', $method_id, $e->getMessage() ), 'Payment Method Configuration' );
            return false;
        }
    }

    /**
     * Retrieves all payment method configurations.
     *
     * @since 4.3.0
     *
     * @param array $args (Optional)
     *
     * @return \Stripe\PaymentMethodConfiguration[]
     */
    public static function all( array $args = [] ): array {
        try {
            $response = static::api()->paymentMethodConfigurations->all( $args );
            if ( ! empty( $response->error ) ) {
                return [];
            }
            return $response->data;
        } catch ( Exception $e ) {
            Helper::log( sprintf( 'Could not retrieve payment method configurations. Error: %s', $e->getMessage() ), 'Payment Method Configuration' );
            return [];
        }
    }

    public static function get_default_payment_method_configuration(): ?\Stripe\PaymentMethodConfiguration {
        $payment_method_configurations = self::all();
        foreach ( $payment_method_configurations as $config ) {
            if ( $config->is_default ) {
                return $config;
            }
        }

        return null;
    }
}
