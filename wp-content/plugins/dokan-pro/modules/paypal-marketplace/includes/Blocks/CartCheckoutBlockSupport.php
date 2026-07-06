<?php

namespace WeDevs\DokanPro\Modules\PayPalMarketplace\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use WeDevs\DokanPro\Modules\PayPalMarketplace\Helper;

class CartCheckoutBlockSupport extends AbstractPaymentMethodType {

    /**
	 * The block supported gateway instance.
	 *
	 * @var \WeDevs\DokanPro\Modules\PayPalMarketplace\PaymentMethods\PayPal
	 */
	private $gateway;

    /**
     * Payment method name defined by payment methods extending this class.
     *
     * @since 4.0.0
     *
     * @var string
     */
    protected $name;

    /**
     * Payment method handle.
     *
     * @since 4.0.0
     *
     * @var string
     */
    protected $handle;

    /**
     * Class constructor.
     *
     * @since 3.15.0
     *
     * @return void
     */
	public function __construct() {
        $this->name = Helper::get_gateway_id();
        $this->handle = $this->name . '_blocks';

        $gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways[ $this->name ];
	}

    /**
     * This function will get called during the server side initialization process and is a good place to put any settings population etc.
     * Basically anything you need to do to initialize your gateway. Note, this will be called on every request so don't put anything expensive here.
     *
     * @since 3.15.0
     *
     * @return void
     */
    public function initialize() {
        $this->settings = Helper::get_settings();
    }

    /**
     * This should return whether the payment method is active or not.
     *
     * @since 3.15.0
     *
     * @return bool
     */
    public function is_active(): bool {
        return Helper::is_ready();
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @see https://github.com/woocommerce/woocommerce-blocks/blob/060f63c04f0f34f645200b5d4da9212125c49177/docs/third-party-developers/extensibility/checkout-payment-methods/payment-method-integration.md#registering-assets
     *
     * @since 3.15.0
     *
     * @return array
     */
    public function get_payment_method_script_handles(): array {
        $asset_path   = DOKAN_PAYPAL_MP_PATH . 'assets/blocks/payment-support/index.asset.php';
        $version      = dokan_pro()->version;

        $dependencies = [];

        if ( file_exists( $asset_path ) ) {
            $asset        = require $asset_path;
            $version      = $asset['version'] ?? $version;
            $dependencies = $asset['dependencies'] ?? $dependencies;
        }

        wp_register_script(
            $this->handle,
            DOKAN_PAYPAL_MP_ASSETS . 'blocks/payment-support/index.js',
            $dependencies,
            $version,
            true
        );

        return [ $this->handle ];
    }

    /**
     * Return associative array of data you want to be exposed for your payment method client side.
     * This data will be available client side via wc.wcSettings.getSetting.
     *
     * @since 3.15.0
     *
     * @return array
     */
    public function get_payment_method_data(): array {
        $payment_fields_data['title']          = $this->get_setting( 'title', __( 'Dokan PayPal Marketplace', 'dokan' ) );
        $payment_fields_data['sandbox_mode']   = 'yes' === $this->get_setting( 'test_mode' );
        $payment_fields_data['enabled']        = 'yes' === $this->get_setting( 'enabled' );
        $payment_fields_data['description']    = $this->get_setting( 'description', '' );
        $payment_fields_data['userId']         = is_user_logged_in() ? get_current_user_id() : 0;
        $payment_fields_data['supports']       = array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] );
        $payment_fields_data['gatewayOptions'] = $this->get_gateway_options();

        return $payment_fields_data;
    }

    /**
     * Retrieves the unique merchant IDs from all products in the cart.
     *
     * @since 4.0.0
     *
     * @return array An array of unique merchant IDs.
     */
    protected function get_merchant_ids(): array {
        $cart_items = array();
        if ( WC()->cart instanceof \WC_Cart ) {
            $cart_items = WC()->cart->get_cart_contents();
        }

        $merchant_ids = [];
        foreach ( $cart_items as $cart_item ) {
            if ( ! isset( $cart_item['product_id'], $cart_item['variation_id'] ) ) {
                continue;
            }

            $product_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];
            $vendor_id  = dokan_get_vendor_by_product( $product_id, true );

            if ( ! $vendor_id ) {
                continue;
            }

            $merchant_id = Helper::get_seller_merchant_id( $vendor_id );
            if ( ! empty( $merchant_id ) ) {
                /**
                 * Filter the merchant ID before adding it to the array.
                 *
                 * @since 4.0.0
                 *
                 * @param string $merchant_id The merchant ID.
                 * @param int    $product_id  The product ID.
                 */
                $merchant_ids[] = apply_filters( 'dokan_paypal_marketplace_merchant_id', $merchant_id, $product_id );
            }
        }

        /**
         * Filter the merchant IDs before returning them.
         *
         * @since 4.0.0
         *
         * @param array $merchant_ids The array of merchant IDs.
         * @param array $cart_items The cart items.
         * @param \WC_Cart $cart The WooCommerce cart instance.
         */
        return apply_filters( 'dokan_paypal_marketplace_get_merchant_ids', array_unique( $merchant_ids ), $cart_items, WC()->cart );
    }

    /**
     * Returns an associative array of options to be passed to the PayPal script.
     *
     * @see: https://developer.paypal.com/sdk/js/configuration/#query-parameters
     * @since 4.0.0
     *
     * @return array
     */
    protected function get_gateway_options(): array {
        // Initializing the PayPal script options
        $options = array(
            'clientId' => Helper::get_client_id(),
        );

        $merchant_ids    = $this->get_merchant_ids();
        $merchants_count = count( $merchant_ids );

        if ( $merchants_count ) {
            $is_multi_vendor = $merchants_count > 1;
            if ( $is_multi_vendor ) {
                $options['data-merchant-id'] = implode( ',', $merchant_ids );
            }

            $options['merchant-id'] = $is_multi_vendor ? '*' : $merchant_ids[0];
        }

        /**
         * Filter the PayPal script options before returning them.
         *
         * @since 4.0.0
         *
         * @param array $options The PayPal script options.
         * @param array $merchant_ids The array of merchant IDs.
         */
        return apply_filters( 'dokan_paypal_marketplace_get_gateway_options', $options, $merchant_ids );
    }
}
