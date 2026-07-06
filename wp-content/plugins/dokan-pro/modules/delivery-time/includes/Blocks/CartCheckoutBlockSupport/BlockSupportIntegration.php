<?php

namespace WeDevs\DokanPro\Modules\DeliveryTime\Blocks\CartCheckoutBlockSupport;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use WeDevs\DokanPro\Modules\DeliveryTime\Helper as DHplper;
use WeDevs\DokanPro\Modules\DeliveryTime\StorePickup\Helper;

class BlockSupportIntegration implements IntegrationInterface {

    /**
	 * The name of the integration.
	 *
	 * @since DOKAN_POR_SINCE
	 *
	 * @return string
	 */
	public function get_name() {
        return 'dokan_delivery_time';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 *
	 * @since DOKAN_POR_SINCE
	 *
	 * @return void
	 */
	public function initialize() {

    }

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @since DOKAN_POR_SINCE
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
        $script_path = '/blocks/checkout-support/index.js';
        $style_path = '/blocks/checkout-support/style-index.css';

        /**
         * The assets linked below should be a path to a file.
         */
        $script_url = DOKAN_DELIVERY_TIME_ASSETS_DIR . $script_path;
        $style_url = DOKAN_DELIVERY_TIME_ASSETS_DIR . $style_path;

        $script_asset_path = DOKAN_DELIVERY_TIME_DIR . '/assets/blocks/checkout-support/index.asset.php';
        $script_asset      = file_exists( $script_asset_path )
            ? require $script_asset_path
            : [
                'dependencies' => [],
                'version'      => $this->get_file_version( DOKAN_PRO_DIR . $script_path ),
            ];

        wp_enqueue_style(
            'dokan-delivery-time',
            $style_url,
            array_merge( [ 'dokan-delivery-time-flatpickr-style','dokan-delivery-time-vendor-style' ], [ 'wp-components' ] ),
            $this->get_file_version( DOKAN_PRO_DIR . $style_path )
        );

        $script_asset['dependencies'][] = 'dokan-delivery-time-flatpickr-script';
        $script_asset['dependencies'][] = 'dokan-util-helper';
        $script_asset['dependencies'][] = 'lodash';
        wp_register_script(
            'dokan-delivery-time',
            $script_url,
            array_merge( $script_asset['dependencies'], [ 'wp-components' ] ),
            $script_asset['version'],
            true
        );
		return [ 'dokan-delivery-time' ];
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
        return [ 'dokan-delivery-time' ];
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @since DOKAN_POR_SINCE
	 *
	 * @return array
	 */
	public function get_script_data() {
        $timeZone = wc_timezone_string() ? ', ' . wc_timezone_string() : '';
        $data     = [
            'vendorInfo' => [],
            'dateTime'   => dokan_format_datetime() . $timeZone,
        ];

        $vendor_infos = DHplper::get_vendor_delivery_time_info();

        $data['delivery_time_enabled'] = ! empty( $vendor_infos );
        $data['is_time_selection_required'] = 'on' === dokan_get_option( 'selection_required', 'dokan_delivery_time', 'on' );

        foreach ( $vendor_infos as $vendor_id => $vendor_info ) {
            $is_vendor_delivery_time_active  = ! empty( $vendor_info['is_delivery_time_active'] );
            $is_vendor_store_location_active = ! empty( $vendor_info['is_store_location_active'] );
            $vendor_infos[ $vendor_id ]['is_delivery_time_active'] = $is_vendor_delivery_time_active;
            $vendor_infos[ $vendor_id ]['is_store_location_active'] = $is_vendor_store_location_active;
        }

        $data['vendorInfo'] = $vendor_infos;

		return $data;
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 *
	 * @since DOKAN_POR_SINCE
	 *
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}

		// As above, let's assume that DOKAN_PRO_PLUGIN_VERSION resolves to some versioning number our
		return DOKAN_PRO_PLUGIN_VERSION;
	}
}
