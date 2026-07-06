<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax;

defined( 'ABSPATH' ) || exit;

use WeDevs\DokanPro\Modules\OrderMinMax\DataSource\ProductMinMaxSettings;


/**
 * OrderMinMax Module block data.
 *
 * @since 3.7.13
 */
class BlockData {

	/**
	 * Block Section name.
	 *
	 * @since 3.7.13
	 *
	 * @var string
	 */
	public $section;

	/**
	 * Constructor class.
	 *
	 * @since 3.7.13
	 */
	public function __construct() {
		$this->section = 'order_min_max';

		// Get configuration.
		add_filter( 'dokan_get_product_block_configurations', array( $this, 'get_product_block_configurations' ) );

		// Product block get and set.
		add_filter( 'dokan_rest_get_product_block_data', array( $this, 'get_product_block_data' ), 10, 2 );
		add_filter( 'dokan_rest_get_product_variable_block_data', array( $this, 'get_variable_product_block_data' ), 10, 3 );
		add_action( 'dokan_rest_insert_product_object', array( $this, 'set_product_block_data' ), 10, 2 );
	}

	/**
	 * Get order-min-max module product block configurations.
	 *
	 * @since 3.7.13
	 *
	 * @param array $configuration Configuration array.
	 *
	 * @return array
	 */
	public function get_product_block_configurations( array $configuration = array() ): array {
		$configuration[ $this->section ] = array(
            Constants::VARIATION_PRODUCT_MIN_MAX_NONCE  => wp_create_nonce( Constants::VARIATION_PRODUCT_MIN_MAX_NONCE ),
		);

		return $configuration;
	}

	/**
	 * Get order-min-max product data for Dokan-pro.
	 *
	 * @since 3.7.13
	 *
	 * @param array      $block  Product block data.
	 * @param \WC_Product $product Product object.
	 *
	 * @return array
	 */
	public function get_product_block_data( array $block, $product ): array {
		if ( ! $product instanceof \WC_Product ) {
			return $block;
		}

		$min_max_settings = new ProductMinMaxSettings( $product );

		$block[ $this->section ] = array(
            Constants::SIMPLE_PRODUCT_MIN_QUANTITY => $min_max_settings->min_quantity(),
            Constants::SIMPLE_PRODUCT_MAX_QUANTITY => $min_max_settings->max_quantity(),
		);
		return $block;
	}

	/**
	 * Get order-min-max product data for Dokan-pro.
	 *
	 * @since 3.7.13
	 *
	 * @param array      $block  Product block data.
	 * @param \WC_Product $product Product object.
	 * @param string     $context Context of the request.
	 *
	 * @return array
	 */
	public function get_variable_product_block_data( array $block, $product, string $context ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$block = $this->get_product_block_data( $block, $product );

		$block[ $this->section ][ Constants::VARIATION_PRODUCT_MIN_MAX_NONCE ] = wp_create_nonce( Constants::VARIATION_PRODUCT_MIN_MAX_NONCE );

		return $block;
	}

	/**
	 * Save order-min-max data after REST-API insert or update.
	 *
	 * @since 3.7.13
	 *
	 * @param \WC_Product      $product  Inserted object.
	 * @param \WP_REST_Request $request  Request object.
	 *
	 * @return void
	 */
	public function set_product_block_data( $product, \WP_REST_Request $request ): void {
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$min_quantity = isset( $request[Constants::SIMPLE_PRODUCT_MIN_QUANTITY] ) && $request[Constants::SIMPLE_PRODUCT_MIN_QUANTITY] > 0 ? absint( wp_unslash( $request[Constants::SIMPLE_PRODUCT_MIN_QUANTITY] ) ) : 0;
		$max_quantity = isset( $request[Constants::SIMPLE_PRODUCT_MAX_QUANTITY] ) && $request[Constants::SIMPLE_PRODUCT_MAX_QUANTITY] > 0 ? absint( wp_unslash( $request[Constants::SIMPLE_PRODUCT_MAX_QUANTITY] ) ) : 0;

		$min_max_settings = new ProductMinMaxSettings( $product );
		$min_max_settings->set_data(
			array(
				ProductMinMaxSettings::MIN_QUANTITY => $min_quantity,
				ProductMinMaxSettings::MAX_QUANTITY => $max_quantity,
			)
		);
		$min_max_settings->save();
	}
}
