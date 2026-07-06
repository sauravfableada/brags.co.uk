<?php

namespace WeDevs\DokanPro\Modules\StripeExpress\Controllers;

defined( 'ABSPATH' ) || exit; // Exit if called directly

use WC_Order;
use Exception;
use WeDevs\Dokan\Exceptions\DokanException;
use WeDevs\DokanPro\Modules\StripeExpress\Support\Helper;
use WeDevs\DokanPro\Modules\StripeExpress\Support\Settings;
use WeDevs\DokanPro\Modules\StripeExpress\Processors\Order;
use WeDevs\DokanPro\Modules\StripeExpress\Support\OrderMeta;
use WeDevs\DokanPro\Modules\StripeExpress\Support\UserMeta;
use WeDevs\DokanPro\Modules\StripeExpress\Api\PaymentIntent;
use WeDevs\DokanPro\Modules\StripeExpress\Processors\Payment;
use WeDevs\DokanPro\Modules\StripeExpress\Processors\Customer;
use WeDevs\DokanPro\Modules\StripeExpress\Processors\Subscription;

/**
 * Ajax controller class for checkout.
 *
 * Handles in-checkout AJAX calls, related to Payment Intents.
 *
 * @since 3.6.1
 *
 * @package WeDevs\DokanPro\Modules\StripeExpress\Controllers
 */
class Checkout {

    /**
     * Holds an instance of the gateway class.
     *
     * @since 3.6.1
     *
     * @var \WeDevs\DokanPro\Modules\StripeExpress\PaymentGateways\Stripe
     */
    protected $gateway;

    /**
     * Class constructor.
     *
     * @since 3.6.1
     *
     * @return void
     */
    public function __construct() {
        $this->hooks();
    }

    /**
     * Registers all necessary hooks.
     *
     * @since 3.6.1
     *
     * @return void
     */
    private function hooks() {
        add_action( 'wc_ajax_dokan_stripe_express_init_setup_intent', [ $this, 'init_setup_intent' ] );
        add_action( 'wc_ajax_dokan_stripe_express_create_payment_intent', [ $this, 'create_payment_intent' ] );
        add_action( 'wc_ajax_dokan_stripe_express_update_payment_intent', [ $this, 'update_payment_intent' ] );
        add_action( 'wc_ajax_dokan_stripe_express_verify_intent', [ $this, 'verify_intent' ] );
        add_action( 'wc_ajax_dokan_stripe_express_create_subscription', [ $this, 'create_subscription' ] );

        add_action( 'wc_ajax_dokan_stripe_express_update_order_status', [ $this, 'update_order_status' ] );
        add_action( 'wc_ajax_dokan_stripe_express_update_failed_order', [ $this, 'update_failed_order' ] );

        add_action( 'woocommerce_checkout_order_review', [ $this, 'maybe_set_subscription_data' ] );
        add_filter( 'woocommerce_available_payment_gateways', [ $this, 'filter_available_payment_gateways' ] );
    }

    /**
     * Returns an instantiated gateway.
     *
     * @since 3.6.1
     *
     * @return \WeDevs\DokanPro\Modules\StripeExpress\PaymentGateways\Stripe
     */
    protected function gateway() {
        if ( ! isset( $this->gateway ) ) { // @phpstan-ignore-line
            $this->gateway = Helper::get_gateway_instance();
        }

        return $this->gateway;
    }

    /**
     * Handle AJAX requests for creating a payment intent for Stripe.
     *
     * @since 3.6.1
     *
     * @return void
     */
    public function create_payment_intent() {
        try {
            if ( ! check_ajax_referer( 'dokan_stripe_express_checkout', false, false ) ) {
                throw new DokanException( 'create_payment_intent_error', __( "We're not able to process this payment. Please refresh the page and try again.", 'dokan' ) );
            }

            // If paying from order, we need to get the total from the order instead of the cart.
            $order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : null;
            $order    = wc_get_order( $order_id );

            if ( ! $order ) {
                $amount   = WC()->cart->get_total( 'edit' );
                $currency = get_woocommerce_currency();
                $metadata = [];
            } else {
                $amount   = $order->get_total();
                $currency = $order->get_currency();
                $metadata = Payment::generate_data( $order )['metadata'];
            }

            $request_args = [
                'amount'               => Helper::get_stripe_amount( $amount, strtolower( $currency ) ),
                'currency'             => strtolower( $currency ),
                'capture_method'       => Settings::is_manual_capture_enabled() ? 'manual' : 'automatic',
                'metadata'             => $metadata,
            ];

            $pm_configuration = Settings::get_payment_method_configuration();
            if ( ! empty( $pm_configuration ) ) {
                $request_args['payment_method_configuration'] = $pm_configuration;
            }

            $payment_intent = PaymentIntent::create( $request_args );

            if ( ! empty( $payment_intent->error ) ) {
                throw new DokanException( 'create_payment_intent_error', $payment_intent->error->message );
            }

            wp_send_json_success(
                [
                    'id'            => $payment_intent->id,
                    'client_secret' => $payment_intent->client_secret,
                ],
                200
            );
        } catch ( DokanException $e ) {
            Helper::log( 'Create payment intent error: ' . $e->get_message() );
            // Send back error so it can be displayed to the customer.
            wp_send_json_error(
                [
                    'error' => [
                        'message' => $e->get_message(),
                    ],
                ]
            );
        }
    }

    /**
     * Handle AJAX request for updating a payment intent for Stripe.
     *
     * @since 3.6.1
     *
     * @return void
     */
    public function update_payment_intent() {
        try {
            if ( ! check_ajax_referer( 'dokan_stripe_express_checkout', false, false ) ) {
                throw new DokanException( 'update_payment_intent_error', __( "We're not able to process this payment. Please refresh the page and try again.", 'dokan' ) );
            }

            $order_id            = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : null;
            $payment_intent_id   = isset( $_POST['payment_intent_id'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_intent_id'] ) ) : '';
            $save_payment_method = isset( $_POST['save_payment_method'] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST['save_payment_method'] ) );
            $payment_type        = ! empty( $_POST['payment_type'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_type'] ) ) : '';

            // get the order object
            $order = wc_get_order( $order_id );

            if ( ! $order instanceof \WC_Order ) {
                $amount   = WC()->cart->get_total( 'edit' );
                $currency = get_woocommerce_currency();

                $payment_intent = PaymentIntent::update(
                    $payment_intent_id,
                    [
                        'amount'   => Helper::get_stripe_amount( $amount, strtolower( $currency ) ),
                        'currency' => strtolower( $currency ),
                    ]
                );
            } else {
                $payment_intent = Payment::update_intent( $payment_intent_id, $order, [], false, $save_payment_method, $payment_type );
            }

            wp_send_json_success( $payment_intent, 200 );
        } catch ( DokanException $e ) {
            // Send back error so it can be displayed to the customer.
            wp_send_json_error(
                [
                    'error' => [
                        'message' => $e->get_message(),
                    ],
                ]
            );
        }
    }

    /**
     * Verifies the payment intent.
     *
     * @since 3.7.8
     *
     * @return void
     */
    public function verify_intent() {
        try {
            if ( ! check_ajax_referer( 'dokan_stripe_express_confirm_pi', false, false ) ) {
                throw new DokanException(
                    'intent_verification_error',
                    __( "We're not able to process this payment. Please refresh the page and try again.", 'dokan' )
                );
            }

            $order = false;
            if ( ! empty( $_GET['order'] ) ) {
                $order = wc_get_order( absint( $_GET['order'] ) );
            }

            if ( ! $order ) {
                throw new DokanException( 'missing-order', __( 'Missing order ID for payment confirmation', 'dokan' ) );
            }
        } catch ( DokanException $e ) {
            /* translators: error message */
            $message = sprintf( __( 'Payment verification error: %s', 'dokan' ), $e->get_message() );
            wc_add_notice( esc_html( $message ), 'error' );

            Helper::log( $message, 'Order' );

            $redirect_url = WC()->cart->is_empty() ? get_permalink( wc_get_page_id( 'shop' ) ) : wc_get_checkout_url();

            wp_safe_redirect( $redirect_url );
            exit;
        }

        try {
            $gateway = $this->gateway();
            Payment::verify_intent( $order );

            if ( isset( $_GET['save_payment_method'] ) && ! empty( $_GET['save_payment_method'] ) ) {
                $intent   = Payment::get_intent( $order );
                $customer = Customer::set( get_current_user_id() );

                if ( isset( $intent->last_payment_error ) ) {
                    /*
                     * Currently, Stripe saves the payment method even if the authentication fails for 3DS cards.
                     * Though the card is not stored in DB we need to remove the source from the customer on Stripe
                     * in order to keep the sources in sync with the data in DB.
                     */
                    $customer->detach_payment_method( $intent->last_payment_error->payment_method->id );
                    $customer->detach_payment_method( $intent->last_payment_error->source->id ); // @phpstan-ignore-line
                } elseif ( isset( $intent->metadata->save_payment_method ) && '1' === $intent->metadata->save_payment_method ) {
                        $customer->attach_payment_method( $intent->payment_method );
                }
            }

            if ( ! isset( $_GET['is_ajax'] ) ) {
                $redirect_url = isset( $_GET['redirect_to'] )
                    ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) )
                    : $gateway->get_return_url( $order );

                wp_safe_redirect( $redirect_url );
            }

            exit;
        } catch ( DokanException $e ) {
            Helper::log( sprintf( 'Payment verification error: %s', $e->get_message() ), 'Order' );

            if ( isset( $_GET['is_ajax'] ) || ! isset( $gateway ) ) {
                exit;
            }

            wp_safe_redirect( $gateway->get_return_url( $order ) );
            exit;
        }
    }

    /**
     * Handle AJAX requests for creating a setup intent without confirmation for Stripe.
     *
     * @since 3.6.1
     *
     * @return mixed
     */
    public function init_setup_intent() {
        try {
            if ( ! check_ajax_referer( 'dokan_stripe_express_checkout', false, false ) ) {
                throw new DokanException( 'setup_intent_error', __( "We're not able to add this payment method. Please refresh the page and try again.", 'dokan' ) );
            }

            // Determine the customer managing the payment methods, create one if we don't have one already.
            $customer     = Customer::set( get_current_user_id() );
            $setup_intent = $customer->setup_intent();

            $intent = [
                'id'            => $setup_intent->id,
                'client_secret' => $setup_intent->client_secret,
            ];

            wp_send_json_success( $intent, 200 );
        } catch ( DokanException $e ) {
            $message = $e->get_message();

            /*
             * In case of no such customer error, the reason is
             * probably that the customer is removed from stripe end
             * or the stripe API setup in the payment gateway settings
             * has been changed.
             * So we need to create a new stripe customer to synchronize
             * and retry creating the setup intent.
             */
            if ( isset( $customer ) && Helper::is_no_such_customer_error( $e ) ) {
                try {
                    $customer->set_id( 0 )->setup_intent();
                } catch ( DokanException $e ) {
                    $message = $e->get_message();
                }
            }

            wp_send_json_error(
                [
                    'error' => [
                        'message' => $message,
                    ],
                ]
            );
        }
    }

    /**
     * Handle AJAX request after authenticating payment at checkout.
     *
     * This function is used to update the order status after the user has
     * been asked to authenticate their payment.
     *
     * This function is used for both:
     * - regular checkout
     * - Pay for Order page (in theory).
     *
     * @since 3.6.1
     *
     * @return mixed
     * @throws Exception
     */
    public function update_order_status() {
        try {
            if ( ! check_ajax_referer( 'dokan_stripe_express_update_order_status', false, false ) ) {
                throw new DokanException( 'nonce_verification_error', __( 'CSRF verification failed.', 'dokan' ) );
            }

            $order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : false;
            $order    = wc_get_order( $order_id );

            if ( ! $order ) {
                throw new DokanException( 'invalid_order', __( "We're not able to process this payment. Please try again later.", 'dokan' ) );
            }

            $save_payment_method = ! empty( sanitize_text_field( wp_unslash( $_POST['payment_method_id'] ) ) );
            $is_setup            = isset( $_POST['is_setup'] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST['is_setup'] ) );
            $intent_id           = ! $is_setup ? OrderMeta::get_payment_intent( $order ) : OrderMeta::get_setup_intent( $order );
            $intent_id_received  = isset( $_POST['intent_id'] ) ? sanitize_text_field( wp_unslash( $_POST['intent_id'] ) ) : null;

            if ( empty( $intent_id_received ) || $intent_id_received !== $intent_id ) {
                Order::add_note(
                    $order,
                    sprintf(
                        /* translators: %1: transaction ID of the payment or a translated string indicating an unknown ID. */
                        esc_html__( 'A payment with ID %s was used in an attempt to pay for this order. This payment intent ID does not match any payments for this order, so it was ignored and the order was not updated.', 'dokan' ),
                        $intent_id_received
                    )
                );
                throw new DokanException( 'duplicate_intent', __( "We're not able to process this payment. Please try again later.", 'dokan' ) );
            }

            Payment::process_confirmed_intent( $order, $intent_id_received, $save_payment_method );

            wp_send_json_success(
                [
                    'return_url' => $this->gateway()->get_return_url( $order ),
                ],
                200
            );
        } catch ( DokanException $e ) {
            wc_add_notice( $e->get_message(), 'error' );
            Helper::log( sprintf( 'Error: %s', $e->get_message() ) );

            if ( isset( $order ) && $order instanceof WC_Order ) {
                $order->update_status( 'failed' );
            }

            // Send back error so it can be displayed to the customer.
            wp_send_json_error(
                [
                    'error' => [
                        'message' => $e->get_message(),
                    ],
                ]
            );
        }
    }

    /**
     * Handle AJAX request if error occurs while confirming intent.
     * We will log the error and update the order.
     *
     * @since 3.6.1
     *
     * @return void
     */
    public function update_failed_order(): void {
        try {
            if ( ! check_ajax_referer( 'dokan_stripe_express_checkout', false, false ) ) {
                throw new \RuntimeException( __( 'CSRF verification failed.', 'dokan' ) );
            }

            $intent_id = isset( $_POST['intent_id'] ) ? sanitize_text_field( wp_unslash( $_POST['intent_id'] ) ) : '';
            $order_id  = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : null;
            $order     = wc_get_order( $order_id );

            if ( is_null( $order_id ) || empty( $intent_id ) || ! ( $order instanceof WC_Order ) ) {
                throw new \RuntimeException( __( 'Invalid order or intent ID.', 'dokan' ) );
            }

            $payment_needed = Helper::is_payment_needed( $order_id );
            if ( ! $payment_needed ) {
                throw new \RuntimeException( __( 'Payment not needed for current order.', 'dokan' ) );
            }

            $intent = Payment::get_intent( null, $intent_id );
            if ( ! empty( $intent ) ) {
                $charge = Payment::get_latest_charge_from_intent( $intent );
                if ( ! $charge ) {
                    $charge = $intent;
                }

                Payment::process_response( $charge, $order );
                Payment::save_intent_data( $order, $intent );
                Payment::save_charge_data( $order, $intent );
            }

            // Send back success, so it can be displayed to the customer.
            wp_send_json_success( 'Payment failed order updated successfully' );
        } catch ( Exception $e ) {
            Helper::log( 'Unable to update failed order. Error: ' . $e->getMessage() );

            /**
             * Fires when an error occurs while updating a failed order.
             *
             * @since 3.6.1
             *
             * @param Exception $e
             * @param WC_Order  $order
             */
            do_action( 'dokan_stripe_express_process_payment_error', $e, $order ?? null );

            if ( isset( $order ) && $order instanceof WC_Order ) {
                $order->update_status( 'failed' );
                wc_add_notice( $e->getMessage(), 'error' );
            }

            // Send back error, so it can be displayed to the customer.
            wp_send_json_error( 'Unable to update failed order. Error: ' . $e->getMessage() );
        }
    }

    /**
     * Creates initial subscription.
     *
     * @since 3.7.8
     *
     * @return void
     */
    public function create_subscription() {
        try {
            if ( ! check_ajax_referer( 'dokan_stripe_express_checkout', false, false ) ) {
                throw new Exception( __( "We're not able to process this payment. Please refresh the page and try again.", 'dokan' ) );
            }

            if ( ! WC()->session instanceof \WC_Session ) {
                throw new Exception( __( "We're not able to process this payment as no session exists. Please refresh the page and try again.", 'dokan' ) );
            }

            $product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
            if ( ! $product_id || ! Subscription::is_recurring_vendor_subscription_product( $product_id ) ) {
                throw new Exception( __( "We're not able to process this payment. Please try again later.", 'dokan' ) );
            }

            $order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : null;

            /**
             * Filters the subscription data before processing with Stripe.
             *
             * This filter allows modification of subscription parameters before creating
             * or updating a subscription in Stripe.
             *
             * @since 3.7.8
             *
             * @param array $subscription_data Subscription parameters for Stripe API.
             * @param int   $product_id        ID of the subscription product.
             * @param int   $order_id          WooCommerce order ID or null.
             *
             * @return array Modified subscription data.
             */
            $subscription_data = apply_filters(
                'dokan_stripe_express_process_subscription_data',
                [
                    'payment_behavior' => 'default_incomplete',
                    'payment_settings' => [
                        'save_default_payment_method' => 'on_subscription',
                    ],
                ],
                $product_id,
                $order_id
            );

            $user_id                = get_current_user_id();
            $stripe_subscription_id = Subscription::get_temporary_subscription_id( $user_id );

            if ( ! $stripe_subscription_id ) {
                $subscription = Subscription::create( $subscription_data );
            } else {
                $subscription = Subscription::get( $stripe_subscription_id );

                if ( is_wp_error( $subscription ) ) {
                    $subscription = Subscription::create( $subscription_data );
                } else {
                    switch ( $subscription->status ) {
                        case 'incomplete':
                            try {
                                $subscription->cancel();
                            } catch ( Exception $exception ) {
                                throw new Exception( $exception->getMessage() );
                            }
                            $subscription = Subscription::create( $subscription_data );
                            break;

                        case 'incomplete_expired':
                            $subscription = Subscription::create( $subscription_data );
                            break;

                        default:
                            $subscription = Subscription::update( $subscription_data, $stripe_subscription_id, $user_id, $order_id );
                            if ( is_wp_error( $subscription ) ) {
                                $subscription = Subscription::create( $subscription_data );
                            } else {
                                $subscription = Subscription::get( $stripe_subscription_id );
                            }
                            break;
                    }
                }
            }

            if ( is_wp_error( $subscription ) ) {
                throw new Exception( $subscription->get_error_message() );
            }

            if ( ! $subscription instanceof \Stripe\Subscription ) {
                throw new Exception( __( "We're not able to process this payment. Please try again later.", 'dokan' ) );
            }

            UserMeta::update_stripe_temp_subscription_id( $user_id, $subscription->id );

            if ( ! empty( $subscription->latest_invoice->confirmation_secret->client_secret ) ) {
                $client_secret = $subscription->latest_invoice->confirmation_secret->client_secret;
                $intent_id     = Helper::get_intent_id_by_client_secret( $client_secret );
            } else {
                $client_secret = $subscription->pending_setup_intent->client_secret;
                $intent_id     = $subscription->pending_setup_intent->id;
            }

            wp_send_json_success(
                [
                    'subscription_id' => $subscription->id,
                    'client_secret'   => $client_secret,
                    'id'              => $intent_id,
                ]
            );
        } catch ( Exception $e ) {
            wp_send_json_error(
                [
                    'error' => [
                        'message' => $e->getMessage(),
                    ],
                ]
            );
        }
    }

    /**
     * Sets subscription data if needed.
     *
     * @since 3.7.8
     *
     * @return void
     */
    public function maybe_set_subscription_data() {
        if ( ! WC()->session instanceof \WC_Session ) {
            return;
        }

        $session = WC()->session;
        if ( empty( $session->cart ) || ! is_array( $session->cart ) ) {
            return;
        }

        foreach ( $session->cart as $data ) {
            if ( empty( $data['product_id'] ) ) {
                return;
            }
            $product_id = $data['product_id'];
            break;
        }

        if ( ! Subscription::is_recurring_vendor_subscription_product( $product_id ) ) {
            return;
        }

        ?>
        <div class="dokan-stripe-express-subscription">
            <input type="hidden" name="subscription_product_id" id="dokan-stripe-express-subscription-product-id" value="<?php echo esc_attr( $product_id ); ?>">
        </div>
        <?php
    }

    /**
     * Filters available payment gateways as necessary.
     *
     * In this case, we are disabling Stripe Connect from
     * checkout page when Stripe Express is available for use.
     *
     * @since 3.7.8
     *
     * @param array $available_gateways
     *
     * @return array
     */
    public function filter_available_payment_gateways( $available_gateways ) {
        if ( ! is_checkout() ) {
            return $available_gateways;
        }

        if ( ! $this->gateway()->is_available() ) {
            return $available_gateways;
        }

        if ( class_exists( 'WeDevs\DokanPro\Modules\Stripe\Helper' ) ) {
            unset( $available_gateways[ \WeDevs\DokanPro\Modules\Stripe\Helper::get_gateway_id() ] );
        }

        return $available_gateways;
    }
}
