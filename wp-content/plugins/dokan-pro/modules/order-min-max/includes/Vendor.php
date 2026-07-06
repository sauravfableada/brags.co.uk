<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax;

defined( 'ABSPATH' ) || exit;

use WeDevs\DokanPro\Modules\OrderMinMax\{DataSource\StoreMinMaxSettings};

/**
 * OrderMinMax Class.
 *
 * @since 3.5.0
 */
class Vendor {
	/**
	 * OrderMinMax Class Constructor.
	 *
	 * @since 3.5.0
	 */
	public function __construct() {
		add_action( 'dokan_settings_form_bottom', array( $this, 'vendor_settings' ), 20 );
		add_action( 'dokan_save_product_variation', array( $this, 'save_variation_min_max_data' ), 10, 2 );
		add_action( 'dokan_ajax_save_product_variations', array( $this, 'save_variation_min_max_ajax_data' ), 12 );
	}

	/**
	 * Add min max vendor settings.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	public function vendor_settings(): void {
		$amount_settings = dokan_pro()->module->order_min_max->store_min_max_settings;
        $min_max_args    = array();
        $vendor_id       = dokan_get_current_user_id();

		$min_max_args[ StoreMinMaxSettings::MIN_AMOUNT_KEY ] = $amount_settings->get_min_amount_for_order( $vendor_id, 'edit' );
		$min_max_args[ StoreMinMaxSettings::MAX_AMOUNT_KEY ] = $amount_settings->get_max_amount_for_order( $vendor_id, 'edit' );

		dokan_get_template_part(
			'vendor-dashboard/order-min-max-settings',
			'',
			array(
				'order_min_max_template' => true,
				'min_max_args'           => $min_max_args,
			)
		);
	}

	/**
	 * Min max meta save.
	 *
	 * @since 3.5.0
	 *
	 * @param int $product_id Product ID
	 * @param int $loop      Loop index
	 *
	 * @return void
	 */
	public function save_variation_min_max_data( int $product_id, int $loop ): void {
		// Save data from OrderMinMax file.
		dokan_pro()->module->order_min_max->data_saver->save_min_max_single_variation_meta( $product_id, $loop );
	}

	/**
	 * Min max meta save.
	 *
	 * @since 3.5.0
	 *
	 * @param int $product_id Product ID
	 *
	 * @return void
	 */
	public function save_variation_min_max_ajax_data( int $product_id ): void {
		if ( ! $product_id ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		foreach ( $_POST['variable_min_quantity'] as $loop => $data ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
			// Save data from OrderMinMax file.
			dokan_pro()->module->order_min_max->data_saver->save_min_max_single_variation_meta( $product_id, $loop );
		}
	}

	/**
	 * Dokan get store info by product id. A wrapper for dokan_get_store_info.
	 *
	 * @since 3.5.0
	 *
	 * @param int $product_id Product ID
	 *
	 * @return array
	 */
	public function dokan_get_store_info_by_product_id( int $product_id ): array {
		$store_id = dokan_get_vendor_by_product( $product_id, true );

		return dokan_get_store_info( $store_id );
	}
}
