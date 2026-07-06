<?php

namespace WeDevs\DokanPro\Modules\MangoPay\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use WeDevs\DokanPro\Modules\MangoPay\Support\Helper;

class CartCheckoutBlockSupport extends AbstractPaymentMethodType {

    /**
     * Payment method name defined by payment methods extending this class.
     *
     * @since 4.0.0
     *
     * @var string
     */
    protected $name = 'dokan_mangopay';

    /**
     * Payment method handle.
     *
     * @since 4.0.0
     *
     * @var string
     */
    protected $handle = 'mangopay-checkout-blocks-integration';

    /**
     * Class constructor.
     *
     * @since 3.15.0
     *
     * @return void
     */
	public function __construct() {
        $this->name = Helper::get_gateway_id();
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
        $this->settings = get_option( 'dokan_mangopay_settings', [] );
    }

    /**
     * This should return whether the payment method is active or not.
     *
     * @since 3.15.0
     *
     * @return bool
     */
    public function is_active(): bool {
        $payment_activated = false;

        $gateways = WC()->payment_gateways->payment_gateways();

        if ( isset( $gateways[ $this->name ] ) ) {
            $payment_activated = 'yes' === $gateways[ $this->name ]->enabled;
        }

        $module_active = dokan_pro()->module->is_active( 'mangopay' );

        if ( $payment_activated && $module_active ) {
            return true;
        }

        return false;
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
        $asset_path   = DOKAN_MANGOPAY_PATH . 'assets/blocks/payment-support/index.asset.php';
        $version      = dokan_pro()->version;

        $dependencies = [];
        if ( file_exists( $asset_path ) ) {
            $asset        = require $asset_path;
            $version      = is_array( $asset ) && isset( $asset['version'] )
                ? $asset['version']
                : $version;
            $dependencies = is_array( $asset ) && isset( $asset['dependencies'] )
                ? $asset['dependencies']
                : $dependencies;
        }

        $dependencies[] = 'dokan-mangopay-kit';

        wp_register_script(
            $this->handle,
            DOKAN_MANGOPAY_ASSETS . 'blocks/payment-support/index.js',
            $dependencies,
            $version,
            true
        );

        wp_register_style(
            $this->handle,
            DOKAN_MANGOPAY_ASSETS . 'blocks/payment-support/style-index.css',
            [],
            $version
        );

        wp_enqueue_style( $this->handle );

        return [ $this->handle ];
    }

    /**
     * Returns an array of script handles to be enqueued for the admin.
     *
     * Include this if your payment method has a script you _only_ want to load in the editor context for the checkout block.
     * Include here any script from `get_payment_method_script_handles` that is also needed in the admin.
     *
     * @since 4.0.0
     *
     * @return array
     */
    public function get_payment_method_script_handles_for_admin(): array {
        list( $suffix, $version ) = dokan_get_script_suffix_and_version();

        if ( ! wp_script_is( $this->handle, 'registered' ) ) {
            wp_register_script(
                'dokan-mangopay-kit',
                DOKAN_MANGOPAY_ASSETS . "vendor/mangopay-kit{$suffix}.js",
                array( 'jquery' ),
                $version,
                true
            );
        }

        return $this->get_payment_method_script_handles();
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
        /**
         * @var $mangopay \WeDevs\DokanPro\Modules\MangoPay\PaymentMethod\Gateway
         */
		$mangopay            = WC()->payment_gateways()->payment_gateways()['dokan_mangopay'];
	    $payment_fields_data = Helper::get_payment_fields_data();

	    $payment_fields_data['title']               = $this->get_setting( 'title', __( 'MangoPay', 'dokan' ) );
	    $payment_fields_data['icon']                = apply_filters( 'woocommerce_dokan_mangopay_icon', DOKAN_MANGOPAY_ASSETS . 'images/mangopay.svg' );
	    $payment_fields_data['saved_cards_enabled'] = ! empty( $mangopay->settings['saved_cards'] ) && 'yes' === $mangopay->settings['saved_cards'];
	    $payment_fields_data['sandbox_mode']        = ! empty( $mangopay->settings['sandbox_mode'] ) && 'yes' === $mangopay->settings['sandbox_mode'];
	    $payment_fields_data['enabled']             = ! empty( $mangopay->settings['enabled'] ) && 'yes' === $mangopay->settings['enabled'];
	    $payment_fields_data['description']         = $this->get_setting( 'description', '' );
	    $payment_fields_data['supports']            = $this->get_supported_features();
	    $payment_fields_data['userId']              = get_current_user_id();

		return $payment_fields_data;
    }
}
