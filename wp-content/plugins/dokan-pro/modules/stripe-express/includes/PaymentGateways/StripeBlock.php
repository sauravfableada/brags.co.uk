<?php

namespace WeDevs\DokanPro\Modules\StripeExpress\PaymentGateways;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use WC_AJAX;
use WeDevs\DokanPro\Modules\StripeExpress\Processors\Subscription;
use WeDevs\DokanPro\Modules\StripeExpress\Support\Helper;

/**
 * Stripe Block Payment Method
 *
 * @since 4.3.0
 *
 * @see https://developer.woocommerce.com/docs/cart-and-checkout-payment-method-integration-for-the-checkout-block/#4-server-side-integration
 */
class StripeBlock extends AbstractPaymentMethodType {

    /**
     * This property is a string used to reference your payment method. It is important to use the same name as in your
     * client-side JavaScript payment method registration.
     *
     * @var string
     */
    protected $name = 'dokan_stripe_express';

    /**
     * Initializes the payment method.
     *
     * This function will get called during the server side initialization process and is a good place to put any settings
     * population etc. Basically anything you need to do to initialize your gateway.
     *
     * Note, this will be called on every request so don't put anything expensive here.
     */
    public function initialize() {
        $this->settings = get_option( 'woocommerce_' . $this->name . '_settings', [] );
    }

    /**
     * This should return whether the payment method is active or not.
     *
     * If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active() {
        return filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * In this function you should register your payment method scripts (using `wp_register_script`) and then return the
     * script handles you registered with. This will be used to add your payment method as a dependency of the checkout script
     * and thus take sure of loading it correctly.
     *
     * Note that you should still make sure any other asset dependencies your script has are registered properly here, if
     * you're using Webpack to build your assets, you may want to use the WooCommerce Webpack Dependency Extraction Plugin
     * (https://www.npmjs.com/package/@woocommerce/dependency-extraction-webpack-plugin) to make this easier for you.
     *
     * @return array
     */
    public function get_payment_method_script_handles() {
        $checkout_block_file = require DOKAN_STRIPE_EXPRESS_PATH . '/assets/js/checkout-block.asset.php';
        wp_register_script(
            'dokan-stripe-express-payment-method-block',
            DOKAN_STRIPE_EXPRESS_ASSETS . 'js/checkout-block.js',
            $checkout_block_file['dependencies'],
            $checkout_block_file['version'],
            true
        );
        return [ 'dokan-stripe-express-payment-method-block' ];
    }

    /**
     * Returns an array of script handles to be enqueued for the admin.
     *
     * Include this if your payment method has a script you _only_ want to load in the editor context for the checkout block.
     * Include here any script from `get_payment_method_script_handles` that is also needed in the admin.
     */
    public function get_payment_method_script_handles_for_admin() {
        return $this->get_payment_method_script_handles();
    }

    public function get_supported_features() {
        return apply_filters(
            'dokan_stripe_express_gateway_support',
            [
                'products',
                'refunds',
                'tokenization',
                'add_payment_method',
			]
        );
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script client side.
     *
     * This data will be available client side via `wc.wcSettings.getSetting`. So for instance if you assigned `stripe` as the
     * value of the `name` property for this class, client side you can access any data via:
     * `wc.wcSettings.getSetting( 'stripe_data' )`. That would return an object matching the shape of the associative array
     * you returned from this function.
     *
     * @return array
     */
    public function get_payment_method_data() {
        // determine testmode, sandbox_mode, or live mode based on the settings.
        // give priority to the sandbox setting, if it is set to 'yes', then we are in test mode.
        $is_sandbox  = 'yes' === $this->get_setting( 'sandbox_mode' );
        $is_testmode = 'yes' === $this->get_setting( 'testmode' );
        $key_prefix  = '';

        if ( $is_sandbox ) {
            $key_prefix = 'sandbox_';
        } elseif ( $is_testmode ) {
            $key_prefix = 'test_';
        }
        $publishable_key = $this->get_setting( "{$key_prefix}publishable_key" );

        $is_saved_cards_enabled = ( 'yes' === $this->get_setting( 'saved_cards' ) ) && is_user_logged_in();
        $has_recurring_subscription = Subscription::cart_contains_recurring_vendor_subscription();

        return array(
            'id'                => Helper::get_gateway_id(),
            'title'             => Helper::get_gateway_title(),
            'description'       => Helper::get_gateway_description(),
            'supports'          => $this->get_supported_features(),
            'accountDescriptor' => $this->get_setting( 'statement_descriptor', 'stripe' ),
            'element_theme'     => $this->get_setting( 'element_theme', 'stripe' ),
            'capture'           => 'yes' === $this->get_setting( 'capture', 'yes' ),
            'testmode'          => $is_sandbox || $is_testmode,
            'show_save_option'  => $is_saved_cards_enabled && ! $has_recurring_subscription,
            'publishable_key'   => $publishable_key,
            'ajax_url'          => WC_AJAX::get_endpoint( '%%endpoint%%' ),
            'checkout'          => wp_create_nonce( 'dokan_stripe_express_checkout' ),
            'woo_checkout'      => wp_create_nonce( 'woocommerce-process_checkout' ),
            'locale'            => get_locale(),
            'error_prefix'      => esc_html__( 'Error:', 'dokan' ),
            'euCompliance'       => [
                'needTaxId' => class_exists( \WeDevs\DokanPro\Modules\Germanized\Helper::class ) && \WeDevs\DokanPro\Modules\Germanized\Helper::is_germanized_installed() && \WeDevs\DokanPro\Modules\Germanized\Helper::is_fields_enabled_for_customer()['billing_dokan_vat_number'],
                'taxIDFieldTitle' => class_exists( \WeDevs\DokanPro\Modules\Germanized\Helper::class ) && \WeDevs\DokanPro\Modules\Germanized\Helper::is_germanized_installed() ? \WeDevs\DokanPro\Modules\Germanized\Helper::get_customer_vat_number_label() : esc_html__( 'VAT Number', 'dokan' ),
            ],
            'has_recurring_subscription' => $has_recurring_subscription,
            'nonce' => array(
                'get_shipping_options'     => wp_create_nonce( 'dokan-stripe-express-payment-request-shipping' ),
                'update_shipping_method'   => wp_create_nonce( 'dokan-stripe-express-update-shipping-method' ),
                'update_payment_method'    => wp_create_nonce( 'dokan-stripe-express-update-payment-method' ),
            ),
        );
    }
}
