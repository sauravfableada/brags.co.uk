<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax\Vendor;

defined( 'ABSPATH' ) || exit;

use WeDevs\DokanPro\Modules\OrderMinMax\Constants;
use WeDevs\DokanPro\Modules\OrderMinMax\DataSource\ProductMinMaxSettings;
use WeDevs\DokanPro\Modules\OrderMinMax\Helper;

class MetaFields {

	/**
	 * Initializes the meta fields for vendor
	 *
	 * @since 3.12.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'dokan_product_edit_after_inventory_variants', array( $this, 'add_simple_product_meta_fields' ), 31 );
		add_action( 'dokan_product_after_variable_attributes', array( $this, 'add_variation_product_meta_fields' ), 31, 3 );
	}

	/**
	 * Adds meta field to the vendor product edit page
	 *
	 * @since 3.12.0
	 *
	 * @param \WP_Post $post Product post object
	 *
	 * @return void
	 */
	public function add_simple_product_meta_fields( \WP_Post $post ) {
		$product = wc_get_product( $post->ID );

		if ( ! $product || 'simple' !== ( $product->get_type() ) ) {
			return;
		}
		$min_max_settings = new ProductMinMaxSettings( $post->ID );
		$message          = Helper::get_quantity_min_max_notice();
		$message_class    = 'dokan-min-max-warning-message ' . Constants::SIMPLE_PRODUCT_MESSAGE_SECTION;
		dokan_get_template_part(
			'vendor-dashboard/simple-product',
			'',
			array(
				'order_min_max_template' => true,
				'post_id'                => $post->ID,
				'min_max_settings'       => $min_max_settings,
				'message_class'          => $message_class,
				'message'                => $message,
			)
		);
	}


	/**
	 * Adds meta field to the vendor product edit page
	 *
	 * @param int $loop Loop index
	 * @param array $variation_data Variation data array
	 * @param \WP_Post $variation Variation post object
	 *
	 * @return void
	 */
	public function add_variation_product_meta_fields( int $loop, array $variation_data, \WP_Post $variation ): void {
		$min_max_settings = new ProductMinMaxSettings( $variation->ID );
		$message          = Helper::get_quantity_min_max_notice();
		$message_class    = 'dokan-min-max-warning-message ' . Constants::VARIATION_PRODUCT_MESSAGE_SECTION;
		dokan_get_template_part(
			'vendor-dashboard/variation-product',
			'',
			array(
				'order_min_max_template' => true,
				'post_id'                => $variation->ID,
				'loop'                   => $loop,
				'min_max_settings'       => $min_max_settings,
				'message_class'          => $message_class,
				'message'                => $message,
			)
		);
	}
}
