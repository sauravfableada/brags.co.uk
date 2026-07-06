<?php

namespace WeDevs\DokanPro\Modules\Paystack\Gateway;

use Exception;
use WC_Payment_Gateway_CC;
use WC_AJAX;
use WeDevs\DokanPro\Modules\Paystack\Api\Transaction;
use WeDevs\DokanPro\Modules\Paystack\Orders\OrderManager;
use WeDevs\DokanPro\Modules\Paystack\Support\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class Paystack Payment Gateway.
 *
 * @package WeDevs\DokanPro\Modules\Paystack\PaymentMethod
 *
 * @since 4.1.1
 */
class Paystack extends WC_Payment_Gateway_CC {

    /**
     * Test API Mode.
     *
     * @since 4.1.1
     *
     * @var bool
     */
    public bool $test_mode;

    /**
     * Live API Key.
     *
     * @since 4.1.1
     *
     * @var string
     */
    public string $live_api_key;

    /**
     * Live API Secret.
     *
     * @since 4.1.1
     *
     * @var string
     */
    public string $live_api_secret;

    /**
     * Test API Key.
     *
     * @since 4.1.1
     *
     * @var string
     */
    public string $test_api_key;

    /**
     * Test API Secret.
     *
     * @since 4.1.1
     *
     * @var string
     */
    public string $test_api_secret;

    /**
     * Payment Page Option.
     *
     * @since 4.1.1
     *
     * @var string
     */
    public string $payment_page;

    /**
     * Debug Mode.
     *
     * @since 4.1.1
     *
     * @var bool
     */
    public bool $debug;

    /**
     * Constructor for the paystack gateway.
     *
     * @since 4.1.1
     *
     * @return void
     */
    public function __construct() {
        $this->supports = [
            'products',
			'refunds',
        ];

        $this->init_fields();
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        $this->init_hooks();

        if ( ! $this->is_valid_for_use() ) {
            $this->enabled = 'no';
        }
    }

    /**
     * Init essential fields.
     *
     * @since 4.1.1
     *
     * @return void
     */
    public function init_fields() {
        $this->id                   = Helper::get_gateway_id();
        $this->has_fields           = true;
        $this->method_title         = __( 'Dokan Paystack', 'dokan' );
        $this->method_description   = $this->method_description();
        $this->icon                 = apply_filters( 'woocommerce_paystack_icon', DOKAN_PAYSTACK_ASSETS . 'images/paystack.svg' );
        $this->title                = $this->get_option( 'title' ) ?? __( 'Dokan Paystack', 'dokan' );
        $this->description          = $this->get_option( 'description' );
        $this->test_mode            = $this->get_option( 'test_mode' );
        $this->live_api_key         = $this->get_option( 'live_api_key' );
        $this->live_api_secret      = $this->get_option( 'live_api_secret' );
        $this->test_api_key         = $this->get_option( 'test_api_key' );
        $this->test_api_secret      = $this->get_option( 'test_api_secret' );
        $this->payment_page         = $this->get_option( 'payment_page', 'inline' );
        $this->debug                = $this->get_option( 'debug' );
    }

    /**
     * Initialise Gateway Settings Form Fields.
     *
     * @since 4.1.1
     *
     * @return void
     */
    public function init_form_fields() {
        $this->form_fields = require DOKAN_PAYSTACK_TEMPLATE_PATH . 'admin-settings-fields.php';
    }

    /**
     * Initialize necessary action hooks.
     *
     * @since 4.1.1
     *
     * @return void
     */
    public function init_hooks() {
        add_action( "woocommerce_update_options_payment_gateways_$this->id", [ $this, 'process_admin_options' ] );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        add_action( 'wp_enqueue_scripts', [ $this, 'payment_scripts' ] );
    }

    /**
     * Check if this gateway is enabled and available in the user's country.
     *
     * @since 4.1.1
     *
     * @return bool
     */
    public function is_valid_for_use(): bool {
        if ( ! in_array( get_woocommerce_currency(), Helper::get_supported_currencies(), true ) ) {
            return false;
        }

        return true;
    }

    /**
     * Check if this payment method is available with conditions.
     *
     * @since 4.1.1
     *
     * @return bool
     */
    public function is_available(): bool {
        $is_available = parent::is_available();

        if ( ! $is_available ) {
            return false;
        }

        // check if admin provided all the api information right
        if ( ! Helper::is_ready() ) {
            return false;
        }

        if ( is_checkout_pay_page() ) {
            global $wp;

            //get order id if this is an order review page
            $order_id = $wp->query_vars['order-pay'] ?? null;

            $order = wc_get_order( $order_id );

            // return if this is not an order object
            if ( ! is_object( $order ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return whether this gateway still requires setup to function.
     *
     * When this gateway is toggled on via AJAX, if this returns true, a
     * redirect will occur to the settings page instead.
     *
     * @since 4.1.1
     *
     * @return bool
     */
    public function needs_setup(): bool {
        if (
            empty( Helper::get_public_api_key() ) ||
            empty( Helper::get_api_secret() ) ) {
            return true;
        }

        return false;
    }

	/**
     * Get method description for Paystack.
     *
     * @since 4.1.1
     *
     * @return string
     */
    public function method_description(): string {
        $paystack_docs  = 'https://dashboard.paystack.com/#/settings/developer';
		$webhook_url    = WebhookHandler::get_webhook_url();
        return sprintf(
            // translators: 1: Paystack dashboard url, 2: Paystack API keys url, 3: Webhook url as link, 4: Webhook url as copy to clipboard button
            __( 'Paystack provides merchants with the tools and services needed to accept online payments from local and international customers using Mastercard, Visa, Verve Cards and Bank Accounts. <a href="%1$s" target="_blank">Sign up</a> for a Paystack account, <a href="%2$s" target="_blank">get your API keys</a>, and set your webhook URL <span><code>%3$s</code><span class="dokan-copy-to-clipboard" data-copy="%4$s"></span></span>', 'dokan' ),
            'https://paystack.com',
            $paystack_docs,
            $webhook_url,
	        $webhook_url
        );
    }

    /**
     * Get the icon for this payment gateway.
     *
     * @return mixed|string|null
     */
    public function get_icon() {
		return apply_filters( 'woocommerce_gateway_icon', '', $this->id );
	}

    /**
     * Display information in frontend after checkout process button.
     *
     * @since 4.1.1
     *
     * @return void
     */
    public function payment_fields() {
        if ( $this->description ) {
			echo wpautop( wptexturize( $this->description ) );
		}
    }

    public function payment_scripts() {
        if ( ! is_product() && ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) && ! is_add_payment_method_page() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        if ( ! $this->is_available() ) {
            return;
        }

        $args = [
            'public_key' => Helper::get_public_api_key(),
		];

        $order_id = absint( get_query_var( 'order-pay' ) );
        $order    = wc_get_order( $order_id );

        if ( $order ) {
            $args['order_id'] = $order_id;
            $args['payment_needed'] = (bool) $order->needs_payment();
        }
        if ( is_wc_endpoint_url( 'order-pay' ) ) {
            $args['is_order_pay'] = true;
            $args['pay_nonce'] = wp_create_nonce( 'dokan_paystack_order_pay' );
            $args['order_pay_ajax_url'] = WC_AJAX::get_endpoint( 'dokan_paystack_order_pay' );
        }

        wp_localize_script(
            'dokan-paystack-checkout',
            'dokanPaystackParams',
            $args
        );

        wp_enqueue_script( 'dokan-paystack-checkout' );
    }

    public function admin_scripts() {
        wp_enqueue_script( 'dokan-paystack-admin' );
    }

	public function admin_notices() {
		if ( ! Helper::is_ready() && 'yes' === $this->enabled ) {
			$message = __( 'Paystack is not ready to use, please configure the settings properly.', 'dokan' );
			echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
		}
	}

    /**
     * Process the payment and return the result.
     *
     * @since 4.1.1
     *
     * @param int $order_id
     *
     * @return array
     */
    public function process_payment( $order_id ): array {
        $order = wc_get_order( $order_id );

        // split the payments
        $args = OrderManager::payment_args( $order );
        $redirect_url = $args['callback_url'] ?? $this->get_return_url( $order );

        try {
            $args = OrderManager::maybe_split_payment( $args, $order );

            if ( $this->payment_page === 'redirect' ) {
                $reference_no = $order->get_meta( '_dokan_paystack_reference' );
                // verify the payment if reference number is already generated for the order
                $should_generate_new = empty( $reference_no );

                if ( ! $should_generate_new ) {
                    $verified_redirect_url = $this->get_verified_transaction_redirect_url( $order, $reference_no, $redirect_url );

                    if ( $verified_redirect_url ) {
                        return [
                            'result'   => 'success',
                            'redirect' => $verified_redirect_url,
                        ];
                    }

                    $should_generate_new = true;
                }

                $redirect_url = $this->initialize_redirect_payment( $order, $args, $should_generate_new );
            }
        } catch ( Exception $e ) {
            wc_add_notice( esc_html( $e->getMessage() ), 'error' );
            return [
                'result' => 'failure',
                'redirect' => $order->get_checkout_payment_url(),
                'message' => $e->getMessage(),
            ];
        }
        return [
            'result'   => 'success',
            'redirect' => $redirect_url,
            'payment_args' => wp_json_encode( $args ),
            'payment_type' => $this->payment_page,
        ];
    }

    /**
     * Verify an existing transaction and return callback redirect URL when completed.
     *
     * @since 5.0.1
     *
     * @param \WC_Order $order
     * @param string     $reference_no
     * @param string     $fallback_redirect_url
     *
     * @return string|null
     */
    private function get_verified_transaction_redirect_url( $order, string $reference_no, string $fallback_redirect_url ): ?string {
        try {
            $verify = Transaction::verify( $reference_no );

            if ( ( $verify['data']['status'] ?? '' ) === 'success' ) {
                return $order->get_meta( '_dokan_paystack_callback_url', true ) ?? $fallback_redirect_url;
            }
        } catch ( Exception $e ) {
            dokan_log(
                sprintf(
                    'Order Paystack transaction verification failed: %s',
                    $e->getMessage()
                ),
                'error'
            );
        }

        return null;
    }

    /**
     * Initialize redirect payment and persist related order metadata.
     *
     * @since 5.0.1
     *
     * @param \WC_Order $order
     * @param array      $args
     * @param bool       $should_generate_new
     *
     * @return string
     */
    private function initialize_redirect_payment( $order, array &$args, bool $should_generate_new ): string {
        $reference_no = Helper::generate_reference( $order, $should_generate_new );

        $args['reference']    = $reference_no;
        $args['callback_url'] = OrderManager::get_callback_url( $order, $reference_no );

        $transaction = Transaction::initialize( $args );

        $order->update_meta_data( '_dokan_paystack_reference', $reference_no );
        $order->save();

        return $transaction['data']['authorization_url'];
    }
}
