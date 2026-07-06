<?php

namespace WeDevs\DokanPro\Modules\Paystack\Gateway;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use WeDevs\DokanPro\Modules\Paystack\Support\Helper;

class PaystackPaymentMethodType extends AbstractPaymentMethodType {

    /**
	 * Payment method name.
	 *
	 * @var string
	 */
	protected $name = 'dokan_paystack';

    /**
     * Initializes the payment method type.
     *
     * @since 4.1.1
     */
    public function initialize() {
        $this->settings = get_option( 'woocommerce_dokan_paystack_settings', [] );
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @since 4.1.1
     *
     * @return array
     */
    public function get_payment_method_script_handles(): array {
        $script_asset_path = DOKAN_PAYSTACK_PATH . '/assets/js/paystack-blocks.asset.php';

		if ( ! file_exists( $script_asset_path ) ) {
			return [];
		}

		$script_asset = require $script_asset_path;

        wp_register_script(
            'wc-dokan-paystack-blocks',
            DOKAN_PAYSTACK_ASSETS . 'js/paystack-blocks.js',
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );
        wp_set_script_translations( 'wc-dokan-paystack-blocks', 'dokan' );
        return array( 'wc-dokan-paystack-blocks' );
    }

    /**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @since 4.1.1
     *
	 * @return array
	 */
	public function get_payment_method_data(): array {
		$payment_gateways = WC()->payment_gateways()->payment_gateways();
		$gateway = $payment_gateways[ Helper::get_gateway_id() ] ?? null;

        if ( ! $gateway ) {
            return array();
        }

		return [
			'title'             => Helper::get_gateway_title(),
			'description'       => $this->get_setting( 'description' ),
			'supports'          => $gateway->supports,
            'logo_url'          => DOKAN_PAYSTACK_ASSETS . 'images/paystack-wc.png',
            'allow_saved_cards' => false,
        ];
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @since 4.1.1
     *
	 * @return boolean
	 */
	public function is_active(): bool {
		$payment_gateways = WC()->payment_gateways()->payment_gateways();
        $gateway = $payment_gateways[ Helper::get_gateway_id() ] ?? null;

        if ( ! $gateway ) {
            return false;
        }

		return $gateway->is_available();
	}
}
