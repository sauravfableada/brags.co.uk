<?php

namespace WeDevs\DokanPro\Shipping\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

defined( 'ABSPATH' ) || exit();

/**
 *  Checkout block support for dokan Shipping.
 *
 * @since 3.15.0
 */
class CheckoutBlockSupport implements IntegrationInterface {

	/**
	 * Get name of the integration
     *
     * @since 3.15.0
	 */
	public function get_name() {
		return 'dokan_shipping';
	}

	/**
     * Initialize.
     *
	 * @since 3.15.0
     *
     * @return void
	 */
	public function initialize() {
		$asset = require DOKAN_PRO_DIR . '/assets/blocks/shipping/index.asset.php';

        wp_register_script(
            'dokan-shipping-block-checkout-support',
            DOKAN_PRO_PLUGIN_ASSEST . '/blocks/shipping/index.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );

        wp_enqueue_style(
            'dokan-shipping-block-checkout-support',
            DOKAN_PRO_PLUGIN_ASSEST . '/blocks/shipping/index.css',
            [],
            $asset['version']
        );
	}

	/**
     * Get Script handle to enqueue.
     *
	 * @since 3.15.0
     *
     * @return array
	 */
	public function get_script_handles() {
		return ['dokan-shipping-block-checkout-support'];
	}

	/**
     * Get editor script handle.
     *
	 * @since 3.15.0
     *
     * @return array
	 */
	public function get_editor_script_handles() {
		return [];
	}

	/**
     * Get script data for frontend consumption.
     *
	 * @since 3.15.0
	 */
	public function get_script_data() {
		return [];
	}
}
