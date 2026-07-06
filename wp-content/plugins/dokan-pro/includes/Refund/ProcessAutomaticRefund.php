<?php


namespace WeDevs\DokanPro\Refund;

use WeDevs\Dokan\Traits\Singleton;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class AutomaticRefundProcess
 *
 * Processing refund request and refunding with API automatically for
 * non Dokan payment gateways.
 *
 * @since   3.3.7
 *
 * @package WeDevs\DokanPro\Refund
 */
class ProcessAutomaticRefund {
    use Singleton;

    /**
     * Payment gateways that are excluded from auto process API refund.
     *
     * @since  3.5.0
     *
     * @access private
     *
     * @var array
     */
    private $excluded_payment_gateways = [];

    /**
     * Private Constructor
     *
     * @access private
     *
     * @since  3.3.7
     * @since  3.5.0 Added `dokan_excluded_gateways_from_auto_process_api_refund` filter hook
     *                        while setting excluded payment gateways from auto process API refund.
     *
     * @return void
     */
    private function __construct() {
        add_action( 'dokan_refund_request_created', [ $this, 'auto_approve_api_refund_request' ] );
        add_filter( 'dokan_pro_auto_process_api_refund', [ $this, 'set_auto_process_api_refund' ], 10, 2 );
    }

    /**
     * Excludes payment gateways from auto process API refund.
     *
     * @since 3.10.2
     *
     * @return array
     */
    public function excluded_dokan_payment_gateways() {
        // return data from property cache
        if ( ! empty( $this->excluded_payment_gateways ) ) {
            return $this->excluded_payment_gateways;
        }

        $this->excluded_payment_gateways = apply_filters(
            'dokan_excluded_gateways_from_auto_process_api_refund',
            [
                'dokan-stripe-connect'  => __( 'Dokan Stripe Connect', 'dokan' ),
                'dokan_paypal_adaptive' => __( 'Dokan Paypal Adaptive Payment', 'dokan' ),
            ]
        );

        return $this->excluded_payment_gateways;
    }

    /**
     * Process Refund request after creation.
     *
     * If the refund auto process settings is `true` means it is a refund request for
     * API processing if the gateway allows it.
     *
     * @since 3.3.7
     * @since 3.4.2 Manual refund button support added. We are no longer automatically approving the refund request.
     *
     * @param Refund $refund Created refund request.
     *
     * @return void|WP_Error
     */
    public function auto_approve_api_refund_request( $refund ) {
        if ( $refund->is_manual() ) {
            return;
        }

        if ( ! $this->is_auto_refund_process_enabled() ) {
            return;
        }

        if ( ! $this->is_auto_refundable_gateway( $refund ) ) {
            return;
        }

        /**
         * Approve refund request after the request creation.
         *
         * @since 3.3.7
         *
         * @param bool   $approve_allowed
         * @param Refund $refund
         */
        $approve_api_refund = apply_filters( 'dokan_pro_auto_approve_api_refund_request', false, $refund );
        if ( $approve_api_refund ) {
            try {
                $refund->approve();
            } catch ( \Exception $exception ) {
                // translators: %s Error message from exception.
                dokan_log( sprintf( __( 'Refund request could not be approved. Error: %s', 'dokan' ), $exception->getMessage() ) );

                return new WP_Error( 'dokan_pro_refund_error_processing', __( 'This refund is failed to process.', 'dokan' ) );
            }
        }
    }

    /**
     * Is auto refund request processing enabled from admin.
     *
     * @since 3.3.7
     * @since 3.9.1 Value changed from admin settings to `true` and filter support added.
     *
     * @return bool
     */
    public function is_auto_refund_process_enabled(): bool {
        return apply_filters(
            'dokan_pro_automatic_process_api_refund_enabled',
            true
        );
    }

    /**
     * Check if gateway can process refund.
     *
     * @since 3.3.7
     *
     * @param Refund $refund
     *
     * @return bool
     */
    public function is_auto_refundable_gateway( $refund ) {
        $order_id = $refund->get_order_id();

        // get an order object.
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return false;
        }

        // Check if it is sub order.
        if ( $order->get_parent_id() ) {
            $order = wc_get_order( $order->get_parent_id() );
            if ( ! $order ) {
                return false;
            }
        }

        $all_gateways   = WC()->payment_gateways->get_available_payment_gateways();
        $payment_method = $order->get_payment_method();
        $gateway        = $all_gateways[ $payment_method ] ?? false;

        if ( ! $gateway || ! $gateway->supports( 'refunds' ) ) {
            return false;
        }

        /**
         * The payment methods that we do not want to auto approve the refund requests.
         *
         * @since 3.3.7
         *
         * @param array
         * @param Refund $refund
         */
        $not_allowed_payment_methods = apply_filters(
            'dokan_pro_exclude_auto_approve_api_refund_request',
            array_keys( $this->excluded_dokan_payment_gateways() ),
            $refund
        );

        return ! in_array( $payment_method, $not_allowed_payment_methods, true );
    }

    /**
     * Set api_refund in refund.
     *
     * @since 3.3.7
     * @since 3.4.2 Manual refund button support added.
     *
     * @param bool   $api_refund
     * @param Refund $refund
     *
     * @return bool
     */
    public function set_auto_process_api_refund( $api_refund, $refund ) {
        return $api_refund && $this->is_auto_refund_process_enabled() && $this->is_auto_refundable_gateway( $refund );
    }

    /**
     * Is excluded payment gateway.
     *
     * @since 3.9.1
     *
     * @param string $payment_method Payment method ID
     *
     * @return bool
     */
    public function is_excluded_payment_gateway( string $payment_method ): bool {
        return in_array( $payment_method, array_keys( $this->excluded_dokan_payment_gateways() ), true );
    }
}
