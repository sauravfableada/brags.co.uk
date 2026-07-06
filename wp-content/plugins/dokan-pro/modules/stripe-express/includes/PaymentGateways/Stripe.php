<?php

namespace WeDevs\DokanPro\Modules\StripeExpress\PaymentGateways;

defined( 'ABSPATH' ) || exit; // Exit if called directly

use Stripe\PaymentIntent;
use WC_AJAX;
use WC_Cart;
use WC_Order;
use WeDevs\Dokan\Cache;
use WeDevs\Dokan\Exceptions\DokanException;
use WeDevs\DokanPro\Modules\StripeExpress\Admin\StripeDisconnectAccount;
use WeDevs\DokanPro\Modules\StripeExpress\Support\Helper;
use WeDevs\DokanPro\Modules\StripeExpress\Support\Settings;
use WeDevs\DokanPro\Modules\StripeExpress\Processors\Token;
use WeDevs\DokanPro\Modules\StripeExpress\Processors\Order;
use WeDevs\DokanPro\Modules\StripeExpress\Processors\Customer;
use WeDevs\DokanPro\Modules\StripeExpress\Support\OrderMeta;
use WeDevs\DokanPro\Modules\StripeExpress\Support\UserMeta;
use WeDevs\DokanPro\Modules\StripeExpress\Api\PaymentMethod;
use WeDevs\DokanPro\Modules\StripeExpress\Processors\Payment;
use WeDevs\DokanPro\Modules\StripeExpress\Processors\Subscription;
use WeDevs\DokanPro\Modules\StripeExpress\Utilities\Abstracts\PaymentGateway;

/**
 * Gateway handler class.
 *
 * @since 3.6.1
 *
 * @package WeDevs\DokanPro\Modules\StripeExpress\PaymentGateways
 */
class Stripe extends PaymentGateway {

    /**
     * ID for the gateway
     *
     * @since 3.6.1
     *
     * @param string
     */
    const ID = 'dokan_stripe_express';

    /**
     * @var boolean $testmode
     */
    public $testmode;

    /**
     * @var boolean $sandbox_mode
     */
    public $sandbox_mode;

    /**
     * @var string $secret_key
     */
    public $secret_key;

    /**
     * @var string $publishable_key
     */
    public $publishable_key;

    /**
     * @var string $debug
     */
    public $debug;

    /**
     * @var boolean $capture
     */
    public $capture;

    /**
     * @var boolean $payment_request
     */
    public $payment_request;

    /**
     * @var boolean $saved_cards
     */
    public $saved_cards;

    /**
     * @var string $statement_descriptor
     */
    public $statement_descriptor;

    /**
     * Class constructor.
     *
     * @since 3.6.1
     *
     * @return void
     */
    public function __construct() {
        // Load necessary fields info
        $this->init_fields();
        // Load the settings
        $this->init_form_fields();
        $this->init_settings();
        // Load necessary hooks
        $this->hooks();
    }

    /**
     * Initiates all required info for payment gateway
     *
     * @since 3.6.1
     *
     * @return void
     */
    public function init_fields() {
        $this->has_fields               = true;
        $this->id                       = self::ID;
        $this->method_title             = Helper::get_gateway_title();
        $this->method_description       = __( 'Accept debit and credit cards in different currencies, methods such as iDEAL, and wallets like Google Pay or Apple Pay with one-touch checkout.', 'dokan' );
        $this->order_button_text        = Helper::get_order_button_text();
        $this->title                    = $this->get_option( 'title' );
        $this->debug                    = $this->get_option( 'debug' );
        $this->description              = $this->get_option( 'description', '' );
        $this->capture                  = 'yes' === $this->get_option( 'capture', 'no' );
        $this->payment_request          = 'yes' === $this->get_option( 'payment_request', 'yes' );
        $this->enabled                  = $this->get_option( 'enabled' );
        $this->saved_cards              = 'yes' === $this->get_option( 'saved_cards' );
        $this->icon                     = apply_filters( 'dokan_stripe_express_icon', '' );
        $this->statement_descriptor     = Helper::clean_statement_descriptor( $this->get_option( 'statement_descriptor', '' ) );

        /**
         * Filter the supported features of the Stripe Express gateway.
         *
         * @since 3.6.1
         *
         * @param array $supports
         */
        $this->supports = apply_filters(
            'dokan_stripe_express_gateway_support',
            [
                'products',
                'refunds',
                'tokenization',
                'add_payment_method',
                'subscriptions',
            ]
        );

        // Determine key prefix and mode based on settings.
        $this->testmode     = false;
        $this->sandbox_mode = false;
        $key_prefix         = '';

        if ( Settings::is_sandbox_mode() ) {
            $key_prefix         = 'sandbox_';
            $this->sandbox_mode = true;
        } elseif ( 'yes' === $this->get_option( 'testmode' ) ) {
            $key_prefix     = 'test_';
            $this->testmode = true;
        }

        $this->secret_key      = $this->get_option( "{$key_prefix}secret_key" );
        $this->publishable_key = $this->get_option( "{$key_prefix}publishable_key" );

        if ( empty( $this->title ) ) {
            $this->title = __( 'Stripe Express', 'dokan' );
        }
    }

    /**
     * Initiates all necessary hooks
     *
     * @since 3.6.1
     *
     * @uses add_action() To add action hooks
     * @uses add_filter() To add filter hooks
     *
     * @return void
     */
    private function hooks() {
        add_action( "woocommerce_update_options_payment_gateways_{$this->id}", [ $this, 'process_admin_options' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'payment_scripts' ] );
        add_filter( 'woocommerce_payment_successful_result', [ $this, 'modify_successful_payment_result' ], 99999, 2 );
        add_action( 'woocommerce_customer_save_address', [ $this, 'show_update_card_notice' ], 10, 2 );
    }

    /**
     * Retrieves the gateway ID.
     *
     * @since 3.7.8
     *
     * @return string
     */
    public function get_gateway_id() {
        return $this->id;
    }

    /**
     * Checks if the gateways is available for use.
     *
     * @since 3.6.1
     *
     * @return boolean
     */
    public function is_available() {
        $is_valid_for_payment = is_add_payment_method_page() || Order::validate_cart_items();
        $is_available = parent::is_available() && $is_valid_for_payment;

        /**
         * Filter to modify the availablity of the Stripe Express payment gateway.
         *
         * @since 3.7.8
         *
         * @param bool $is_available
         */
        return apply_filters( 'dokan_stripe_express_is_gateway_available', $is_available );
    }

    /**
     * Initiates form fields for admin settings
     *
     * @since 3.6.1
     *
     * @return void
     */
    public function init_form_fields() {
        $this->form_fields = require DOKAN_STRIPE_EXPRESS_TEMPLATE_PATH . 'admin/gateway-settings.php';
    }

    /**
     * Init settings for gateways.
     *
     * @since 3.6.1
     *
     * @return void
     */
    public function init_settings() {
        parent::init_settings();
        $this->enabled = ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';
    }

    /**
     * Processes the admin options.
     *
     * @since 3.6.1
     *
     * @return void
     */
    public function process_admin_options() {
        parent::process_admin_options();

        /**
         * @var \WeDevs\DokanPro\Modules\StripeExpress\Controllers\Webhook $webhook
         */
        $webhook = dokan_pro()->module->stripe_express->webhook;

        // Automatically create webhook if gateway is enabled. Delete otherwise
        if ( 'yes' === $this->enabled ) {
            $webhook->register();
        } else {
            $webhook->deregister();
        }

        Cache::invalidate_transient_group( 'stripe_express_platform_data' );
        Cache::invalidate_transient_group( 'stripe_express_country_specs' );

        // Set queue for disconnecting vendors.
        $this->set_queue_for_disconnecting_vendors();
    }

    /**
     * Set queue for disconnecting vendors
     *
     * @since 3.11.2
     *
     * @return void
     */
    private function set_queue_for_disconnecting_vendors() {
        if ( ! Settings::is_gateway_enabled() ) {
            return;
        }

        if ( ! Settings::is_cross_border_transfer_enabled()
            && ! Settings::is_disconnect_connected_vendors_enabled() ) {
            return;
        }

        if ( Settings::is_cross_border_transfer_enabled()
            && ! Settings::is_disconnect_vendors_enabled() ) {
            return;
        }

        if ( Settings::is_cross_border_transfer_enabled()
            && Settings::is_disconnect_vendors_enabled()
            && empty( Settings::get_restricted_countries() ) ) {
            return;
        }

        // Set the queue for collecting vendor's id to disconnect
        StripeDisconnectAccount::start_disconnect_queue();
    }

    /**
     * Renders the input fields needed
     * to get the user's payment information
     * on the checkout page.
     *
     * @since 3.6.1
     *
     * @return void
     */
    public function payment_fields() {
        try {
            global $wp;
            $user_email           = '';
            $first_name           = '';
            $last_name            = '';
            $total                = 0;
            $user                 = wp_get_current_user();
            $display_tokenization = $this->supports( 'tokenization' ) && is_checkout();

            // If paying from order, we need to get total from order not cart.
            if ( isset( $_GET['pay_for_order'] ) && ! empty( $_GET['key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $order      = wc_get_order( wc_clean( $wp->query_vars['order-pay'] ) );
                $total      = $order->get_total();
                $user_email = $order->get_billing_email();
            } elseif ( $user->ID ) {
                    $user_email = get_user_meta( $user->ID, 'billing_email', true );
                    $user_email = $user_email ? $user_email : $user->user_email;
            }

            if ( is_add_payment_method_page() ) {
                $first_name = $user->user_firstname;
                $last_name  = $user->user_lastname;
            }

            ob_start();

            ?>
            <div
                id="dokan-stripe-express-payment-data"
                data-email="<?php echo esc_attr( $user_email ); ?>"
                data-full-name="<?php echo esc_attr( "$first_name $last_name" ); ?>"
                data-order-total="<?php echo esc_attr( (string) $total ); ?>"
                data-currency="<?php echo esc_attr( strtolower( get_woocommerce_currency() ) ); ?>"
            >
            <?php

            $this->maybe_show_description();

            if ( $display_tokenization ) {
                $this->tokenization_script();
                $this->saved_payment_methods();
            }

            $this->element_form();

            if ( $this->saved_cards && is_user_logged_in() ) {
                $force_save_payment = Subscription::cart_contains_recurring_vendor_subscription() ||
                    is_add_payment_method_page() ||
                    (
                        $display_tokenization &&
                        ! apply_filters( 'dokan_stripe_express_display_save_payment_method_checkbox', $display_tokenization )
                    );

                $this->save_payment_method_checkbox( $force_save_payment );
            }

            do_action( 'dokan_stripe_express_payment_fields', $this->id );

            ?>
            </div>
            <?php

            ob_end_flush();
        } catch ( \Exception $e ) {
            // Output the error message.
            Helper::log( 'Error: ' . $e->getMessage() );
            /* translators: 1) opening div tag, 2) closing div tag */
            echo esc_html( sprintf( __( '%1$sAn error was encountered when preparing the payment form. Please try again later.%2$s', 'dokan' ), '<div>', '</div>' ) );
        }
    }

    /**
     * Enqueues payment scripts.
     *
     * @since 3.6.1
     *
     * @return void
     */
    public function payment_scripts() {
        if ( ! is_product() && ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) && ! is_add_payment_method_page() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        if ( ! $this->is_available() ) {
            return;
        }

        wp_localize_script(
            'dokan-stripe-express-checkout-classic',
            'dokanStripeExpress',
            $this->localized_params()
        );

        wp_enqueue_script( 'dokan-stripe-express-checkout-classic' );
        wp_enqueue_style( 'dokan-stripe-express-checkout-block' );
    }

    /**
     * Generates localized javascript parameters
     *
     * @since 3.6.1
     *
     * @return array
     */
    private function localized_params() {
        $stripe_params = [
            'title'                => $this->title,
            'key'                  => $this->publishable_key,
            'locale'               => Helper::convert_locale( get_locale() ),
            'billingFields'        => Helper::get_enabled_billing_fields(),
            'isTestMode'           => $this->testmode,
            'isCheckout'           => is_checkout() && empty( $_GET['pay_for_order'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            'isAddPaymentMethod'   => is_add_payment_method_page(),
            'errors'               => Helper::get_error_message(),
            'messages'             => Helper::get_payment_message(),
            'ajaxurl'              => WC_AJAX::get_endpoint( '%%endpoint%%' ),
            'nonce'                => wp_create_nonce( 'dokan_stripe_express_checkout' ),
            'addPaymentReturnURL'  => wc_get_account_endpoint_url( 'payment-methods' ),
            'accountDescriptor'    => $this->statement_descriptor,
            'genericErrorMessage'  => __( 'There was a problem processing the payment. Please check your email inbox and refresh the page to try again.', 'dokan' ),
            'isSandboxMode'        => Settings::is_sandbox_mode(),
            'assets'               => [
                'applePayLogo'  => DOKAN_STRIPE_EXPRESS_ASSETS . 'images/apple-pay.svg',
                'googlePayLogo' => DOKAN_STRIPE_EXPRESS_ASSETS . 'images/google-pay.svg',
            ],
            'i18n'                 => [
                'confirmApplePayment'  => __( 'Proceed to payment via Apple Pay?', 'dokan' ),
                'confirmGooglePayment' => __( 'Proceed to payment via Google Pay?', 'dokan' ),
                'proceed'             => __( 'Yes, Proceed', 'dokan' ),
                'decline'             => __( 'Decline', 'dokan' ),
                'emptyFields'         => __( 'Please fill all the fields', 'dokan' ),
                'paymentDismissed'    => __( 'Payment process dismissed', 'dokan' ),
                'tryAgain'            => __( 'An error was encountered when preparing the payment form. Please try again later.', 'dokan' ),
                'incompleteInfo'      => __( 'Your payment information is incomplete.', 'dokan' ),
            ],
            'sepaElementsOptions'  => apply_filters( // todo: need to remove in the future
                'dokan_stripe_express_sepa_elements_options',
                [
                    'supportedCountries' => [ 'SEPA' ],
                    'placeholderCountry' => WC()->countries->get_base_country(),
                ]
            ),
            'appearance'           => apply_filters(
                'dokan_stripe_express_payment_element_appearance',
                [
                    'theme' => $this->get_option( 'element_theme', 'stripe' ),
                ]
            ),
        ];

        $order_id = null;

        if ( is_wc_endpoint_url( 'order-pay' ) ) {
            if ( Subscription::has_wc_subscription() && Subscription::is_changing_payment_method() ) {
                $stripe_params['isChangingPayment']   = true;
                $stripe_params['addPaymentReturnURL'] = esc_url_raw( home_url( add_query_arg( [] ) ) );

                if ( Helper::is_setup_intent_success_creation_redirection() && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( wc_clean( wp_unslash( $_GET['_wpnonce'] ) ) ) ) {
                    $setup_intent_id = isset( $_GET['setup_intent'] ) ? wc_clean( wp_unslash( $_GET['setup_intent'] ) ) : '';
                    $token           = $this->create_token_from_setup_intent( $setup_intent_id, get_current_user_id() );

                    if ( ! empty( $token ) && $token instanceof \WC_Payment_Token ) {
                        $stripe_params['newTokenFormId'] = '#wc-' . $token->get_gateway_id() . '-payment-token-' . $token->get_id();
                        $stripe_params['stripeToken']    = $token->get_token();
                    }
                }

                return $stripe_params;
            }

            $order_id = absint( get_query_var( 'order-pay' ) );
            $order    = wc_get_order( $order_id );

            if ( $order ) {
                $return_url_params = [
                    'order_id'          => $order_id,
                    'wc_payment_method' => Helper::get_gateway_id(),
                    '_wpnonce'          => wp_create_nonce( 'dokan_stripe_express_process_redirect_order' ),
                ];
                $subscription_id = OrderMeta::get_stripe_subscription_id( $order );
                if ( empty( $subscription_id ) && $order->get_customer_id() ) {
                    $subscription_id = UserMeta::get_stripe_temp_subscription_id( $order->get_customer_id() );
                }
                if ( ! empty( $subscription_id ) && Subscription::has_vendor_subscription_module() ) {
                    $return_url_params['subscription_id']     = $subscription_id;
                    $return_url_params['save_payment_method'] = 'no';
                }
                $stripe_params['orderReturnURL'] = esc_url_raw(
                    add_query_arg( $return_url_params, $this->get_return_url( $order ) )
                );
            }

            $stripe_params['orderId']    = $order_id;
            $stripe_params['isOrderPay'] = true;
        }

        $stripe_params['isPaymentNeeded'] = Helper::is_payment_needed( $order_id );

        return $stripe_params;
    }

    /**
     * Process the payment for a given order.
     *
     * @since 3.6.1
     *
     * @param int   $order_id          ID of the order being processed.
     * @param bool  $retry             Should we retry on fail.
     * @param bool  $force_save_source Force save the payment source.
     * @param mixed $previous_error    Any error message from previous request.
     * @param bool  $use_order_source  Whether to use the source, which should already be attached to the order.
     *
     * @return array|null An array with result of payment and redirect URL, or nothing.
     */
    public function process_payment( $order_id, $retry = true, $force_save_source = false, $previous_error = false, $use_order_source = false ) {
        // phpcs:disable WordPress.Security.NonceVerification.Missing

        if ( Subscription::subcription_payment_method_needs_change( $order_id ) ) {
            return $this->change_subscription_payment_method( $order_id );
        }

        if ( Subscription::is_recurring_vendor_subscription_order( $order_id ) ) {
            $subscription_id = ! empty( $_POST['subscription_id'] ) ? sanitize_text_field( wp_unslash( $_POST['subscription_id'] ) ) : '';

            $order = wc_get_order( $order_id );

            if ( empty( $subscription_id ) ) {
                $subscription_id = OrderMeta::get_stripe_subscription_id( $order );
            }

            if ( empty( $subscription_id ) && $order->get_customer_id() ) {
                $subscription_id = UserMeta::get_stripe_temp_subscription_id( get_current_user_id() );
            }

            if ( ! empty( $subscription_id ) ) {
                return $this->process_subscription( $order_id );
            }
        }

        $payment_intent_id = ! empty( $_POST['payment_intent_id'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_intent_id'] ) ) : '';

        if ( Helper::is_using_saved_payment_method() ) {
            return $this->process_payment_with_saved_payment_method( $order_id, true, $payment_intent_id );
        }

        if ( empty( $payment_intent_id ) ) {
            return parent::process_payment( $order_id, $retry, $force_save_source, $previous_error, $use_order_source );
        }

        $order                 = wc_get_order( $order_id );
        $save_payment_method   = Subscription::is_subscription_order( $order_id ) || ! empty( $_POST[ 'wc-' . self::ID . '-new-payment-method' ] );
        $payment_needed        = Helper::is_payment_needed( $order_id );

        OrderMeta::update_debug_payment_intent( $order, $payment_intent_id );
        OrderMeta::update_save_payment_method( $order, $save_payment_method ? 'yes' : 'no' );
        OrderMeta::save( $order );

        if ( $payment_needed ) {
            $intent = Payment::update_intent( $payment_intent_id, $order, [], false, $save_payment_method );

            // If save_payment_method was not detected from POST (e.g. Block Checkout), check if the intent has it set
            if ( ! $save_payment_method && ! empty( $intent->setup_future_usage ) && 'off_session' === $intent->setup_future_usage ) {
                $save_payment_method = true;
                // Update local meta for consistency
                OrderMeta::update_save_payment_method( $order, 'yes' );
                OrderMeta::save( $order );
            }
        }

        return [
            'result'         => 'success',
            'payment_needed' => $payment_needed,
            'order_id'       => $order_id,
            'redirect'       => wp_sanitize_redirect(
                esc_url_raw(
                    add_query_arg(
                        [
                            'order_id'            => $order_id,
                            'wc_payment_method'   => self::ID,
                            '_wpnonce'            => wp_create_nonce( 'dokan_stripe_express_process_redirect_order' ),
                            'save_payment_method' => $save_payment_method ? 'yes' : 'no',
                        ],
                        $this->get_return_url( $order )
                    )
                )
            ),
        ];
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }

    /**
     * Process payment using saved payment method.
     * This follows Stripe::process_payment,
     * but uses Payment Methods instead of Sources.
     *
     * @since 3.6.1
     *
     * @param int    $order_id          The order ID being processed.
     * @param bool   $can_retry         Should we retry on fail.
     * @param string $payment_intent_id The payment intent ID.
     *
     * @return mixed
     */
    public function process_payment_with_saved_payment_method( $order_id, $can_retry = true, $payment_intent_id = null ) {
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        try {
            $token = Token::parse_from_request();
            if ( $token === null ) {
                throw new DokanException(
                    'dokan_stripe_express_invalid_payment_method',
                    __( 'The payment method cannot be used to place the order. Please choose another and try again.', 'dokan' )
                );
            }

            $payment_method = PaymentMethod::get( $token->get_token() );
            if ( ! $payment_method ) {
                throw new DokanException(
                    'dokan_stripe_express_invalid_payment_method',
                    __( 'The payment method cannot be used to place the order. Please choose another and try again.', 'dokan' )
                );
            }

            $payment_needed = Helper::is_payment_needed( $order_id );
            $order          = wc_get_order( $order_id );

            Helper::maybe_disallow_prepaid_card( $payment_method );
            Payment::save_payment_method_data( $order, $payment_method );

            // Customer MUST be attached when using a saved PaymentMethod - Stripe rejects reuse otherwise.
            $customer_id = ! empty( $payment_method->customer ) ? $payment_method->customer : null;
            if ( empty( $customer_id ) ) {
                $customer_id = Order::get_stripe_customer_id_from_order( $order );
                if ( empty( $customer_id ) ) {
                    $user        = Order::get_user_from_order( $order );
                    $customer    = Customer::set( $user->ID );
                    $customer_id = $customer->get_id();
                    if ( empty( $customer_id ) ) {
                        $customer_id = $customer->update_or_create();
                    }
                }
            }
            if ( empty( $customer_id ) || is_wp_error( $customer_id ) ) {
                throw new DokanException(
                    'dokan_stripe_express_invalid_payment_method',
                    __( 'Unable to use saved card. Please add a new payment method and try again.', 'dokan' )
                );
            }

            // If we are retrying request, maybe intent has been saved to order.
            $intent      = Payment::get_intent( $order, $payment_intent_id, [], ! $payment_needed );
            $intent_data = [
                'payment_method'       => $payment_method->id,
                'customer'             => $customer_id,
                // Use payment_method_types for saved cards - automatic_payment_methods causes issues.
                'payment_method_types' => [ $payment_method->type ],
            ];

            if ( $payment_needed ) {
                // This will throw exception if not valid.
                $this->validate_minimum_order_amount( $order );

                $intent_data['capture_method'] = Settings::is_manual_capture_enabled() ? 'manual' : 'automatic';
            }

            // Common setup for both payment and non-payment cases
            // @see https://docs.stripe.com/api/payment_intents/create
            if ( ! $intent ) {
				//              if ( $payment_needed && 'automatic' === $intent_data['capture_method'] ) {
				//                  $intent_data['confirm']    = 'true';
				//                  $intent_data['return_url'] = $this->get_return_url( $order );
				//              }

                // SEPA-specific setup for non-payment cases
                if ( ! $payment_needed && Helper::get_sepa_payment_method_type() === $payment_method->type ) {
                    $intent_data['mandate_data'] = [
                        'customer_acceptance' => [
                            'type'   => 'online',
                            'online' => [
                                'ip_address' => dokan_get_client_ip(),
                                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '', // phpcs:ignore
                            ],
                        ],
                    ];
                }

                Helper::log( '$intent' . print_r( $intent_data, true ), 'Order', 'info' );

                $intent = Payment::create_intent( $order, $intent_data, ! $payment_needed );
            }

            if ( ! $intent instanceof PaymentIntent ) {
                throw new DokanException( $intent, 'Invalid Payment Intent.' );
            }

            // When using automatic_payment_methods, payment_method_types may be empty in response.
            $intent_pm_types = $intent->payment_method_types ?? [];
            if ( ! empty( $intent_pm_types ) && ! in_array( $payment_method->type, $intent_pm_types, true ) ) {
                throw new DokanException(
                    'dokan_stripe_express_invalid_payment_method',
                    __( 'The payment method cannot be used to place the order. Please choose another and try again.', 'dokan' )
                );
            }

            if ( 'succeeded' === $intent->status ) {
                OrderMeta::update_payment_type( $order, $payment_method->type );
                OrderMeta::save( $order );
                // When using saved payment method, we don't save a new one (third param = false).
                Payment::process_confirmed_intent( $order, $intent->id, false );

                return [
                    'result'   => 'success',
                    'redirect' => wp_sanitize_redirect(
                        esc_url_raw(
                            add_query_arg(
                                [
                                    'order_id'                     => $order_id,
                                    'payment_intent'               => $intent->id,
                                    'payment_intent_client_secret' => $intent->client_secret,
                                    'wc_payment_method'            => self::ID,
                                    '_wpnonce'                     => wp_create_nonce( 'dokan_stripe_express_process_redirect_order' ),
                                    'redirect_status'              => 'success',
                                    'save_payment_method'          => 'no',
                                ],
                                $this->get_return_url( $order )
                            )
                        )
                    ),
                ];
            }

            if ( Helper::get_sepa_payment_method_type() !== $payment_method->type ) {
                // You may only update the capture_method of a PaymentIntent with one of the following statuses: requires_payment_method, requires_confirmation.
				if ( 'requires_action' === $intent->status ) {
					unset( $intent_data['capture_method'] );
				}

                // Could not update payment intent: pi_xxxxxxxxxxxxxxxxxx. Error: Received unknown parameters: return_url, confirm
				//                if ( in_array( $intent->status, array( 'requires_payment_method', 'payment_intent.requires_action' ), true ) ) {
				//                    unset( $intent_data['return_url'], $intent_data['confirm'] );
				//                }

                $intent = Payment::update_intent( $intent->id, $order, $intent_data );
            }
            // phpcs:enable WordPress.Security.NonceVerification.Missing

            /**
             * Process payment when needed.
             *
             * @since 3.7.8
             *
             * @param WC_Order $order             The order being processed.
             * @param string   $payment_method_id The source of the payment.
             */
            do_action( 'dokan_stripe_express_process_payment', $order, $payment_method->id );

            if ( ! empty( $intent->error ) ) {
                $this->maybe_remove_non_existent_customer( $intent->error, $order );

                // We want to retry (apparently).
                if ( Helper::is_retryable_error( $intent->error ) ) {
                    return $this->retry_after_error( $intent, $order, $can_retry );
                }

                $this->throw_error_message( $intent, $order );
            }

            OrderMeta::update_payment_type( $order, $payment_method->type );
            OrderMeta::save( $order );

            if ( 'requires_action' === $intent->status || 'requires_confirmation' === $intent->status ) {
                if (
                    isset( $intent->next_action->type ) &&
                    'redirect_to_url' === $intent->next_action->type &&
                    ! empty( $intent->next_action->redirect_to_url->url )
                ) {
                    return [
                        'result'   => 'success',
                        'redirect' => $intent->next_action->redirect_to_url->url,
                    ];
                }

                if ( 'requires_confirmation' === $intent->status ) {
                    Payment::confirm_intent( $intent, $payment_method );
                }

                dokan_log( '$intent->next_action: ' . print_r( $intent->next_action, true ) );

                // Payment::process_confirmed_intent( $order, $intent->id, $payment_method->id );

                // https://dokan-development.test/checkout/order-received/7630/?key=wc_order_ktOjCnZS3Eldy
                // https://dokan-development.test/checkout/order-received/7630/?_wpnonce=159d91cab4&key=wc_order_ktOjCnZS3Eldy&order_id=7630&payment_intent=pi_3Rh35hGRPBbXrqLa2cKKGrob&payment_intent_client_secret=pi_3Rh35hGRPBbXrqLa2cKKGrob_secret_EacPy9oS47nxhNfT9GUEovoYl&redirect_status=succeeded&save_payment_method=no&wc_payment_method=dokan_stripe_express

                return [
                    'result'   => 'success',
                    'redirect' => wp_sanitize_redirect(
                        esc_url_raw(
                            add_query_arg(
                                [
                                    'order_id'                     => $order_id,
                                    'payment_intent'               => $intent->id,
                                    'payment_intent_client_secret' => $intent->client_secret,
                                    'wc_payment_method'            => self::ID,
                                    '_wpnonce'                     => wp_create_nonce( 'dokan_stripe_express_process_redirect_order' ),
                                    'redirect_status'              => 'success',
                                    'save_payment_method'          => 'no',
                                ],
                                $this->get_return_url( $order )
                            )
                        )
                    ),
                ];
            }

            if ( $payment_needed ) {
                Order::lock_processing( $order->get_id(), 'intent', $intent->id );

                // Use the last charge within the intent to proceed or the original response in case of SEPA
                $response = Payment::get_latest_charge_from_intent( $intent );
                if ( ! $response ) {
                    $response = $intent;
                }
                Payment::process_response( $response, $order );
                Order::unlock_processing( $order->get_id(), 'intent' );
            } else {
                $order->payment_complete();
                do_action( 'dokan_stripe_express_payment_completed', $order, $intent );
            }

            [ $payment_method_type ] = Payment::get_method_data_from_intent( $intent );

            Payment::set_method_title( $order, $payment_method_type );

            // Remove cart.
            if ( WC()->cart instanceof WC_Cart && ! WC()->cart->is_empty() ) {
                WC()->cart->empty_cart();
            }

            // Return thank you page redirect.
            return [
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order ),
            ];
        } catch ( DokanException $e ) {
            wc_add_notice( $e->get_message(), 'error' );
            Helper::log( 'Error: ' . $e->get_message() );

            if ( isset( $order ) && $order instanceof WC_Order ) {
                do_action( 'dokan_stripe_express_process_payment_error', $e, $order );

                /* translators: error message */
                $order->update_status( 'failed' );
            }

            return [
                'result'   => 'fail',
                'redirect' => '',
                'messages' => $e->get_message(),
            ];
        }
    }

    /**
     * Checks whether an order is refundable.
     *
     * @since 3.6.1
     *
     * @param WC_Order $order
     *
     * @return boolean
     */
    public function can_refund_order( $order ) {
        // Check if the default refund method is enabled.
        if ( ! parent::can_refund_order( $order ) ) {
            return false;
        }

        // Check whether order is processed or completed
        if ( ! $order->has_status( [ 'processing', 'completed' ] ) ) {
            return false;
        }

        /**
         * We will not allow refund from the parent order.
         * The refund should always be given from the
         * sub orders if exists.
         * If it is a parent order, the refund button for
         * Stripe Express will not be shown.
         */
        if ( $order->get_meta( 'has_sub_order' ) ) {
            return false;
        }

        return true;
    }

    /**
     * Adds a notice for customer when they update their billing address.
     *
     * @since 3.7.8
     *
     * @param int    $user_id      The ID of the current user.
     * @param string $load_address The address to load.
     *
     * @return void
     */
    public function show_update_card_notice( $user_id, $load_address ) {
        if ( ! $this->saved_cards || ! $this->customer_has_saved_methods( $user_id ) || 'billing' !== $load_address ) {
            return;
        }

        if ( ! function_exists( 'wc_add_notice' ) ) {
            return;
        }

        wc_add_notice(
            sprintf(
                /* translators: 1) opening anchor tag with link, 2) closing anchor tag */
                __(
                    'If your billing address has been changed for saved payment methods, be sure to remove any %1$ssaved payment methods%2$s on file and re-add them.',
                    'dokan'
                ),
                sprintf(
                    '<a href="%s" class="dokan-stripe-express-update-card-notice" style="text-decoration:underline;">',
                    esc_url( wc_get_endpoint_url( 'payment-methods' ) )
                ),
                '</a>'
            ),
            'notice'
        );
    }
}
