<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax;

defined( 'ABSPATH' ) || exit;

use WeDevs\DokanPro\Modules\OrderMinMax\DataSource\ProductMinMaxSettings;


/**
 * Saves admin min max data
 *
 * @since 3.12.0
 */
class DataSaver {

	/**
	 * Initializing necessary hooks
	 *
	 * @since 3.12.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'save_post_product', array( $this, 'save_simple_product' ) );
		add_action( 'dokan_process_product_meta', array( $this, 'save_simple_product' ) );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_min_max_single_variation_meta' ), 10, 2 );
	}

	/**
	 * Verifies nonce is valid
	 *
	 * @since 3.12.0
	 *
	 * @param string $action Nonce action name
	 *
	 * @return bool
	 */
	protected function valid_nonce( string $action ): bool {
		if ( ! isset( $_POST[ $action ] ) ) {
			return false;
		}

		$nonce = sanitize_key( wp_unslash( $_POST[ $action ] ) );
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Saving simple product data
	 *
	 * @since 3.12.0
	 *
	 * @param int $product_id Product ID
	 *
	 * @return void
	 */
	public function save_simple_product( int $product_id ): void {
		if ( ! $this->valid_nonce( Constants::SIMPLE_PRODUCT_MIN_MAX_NONCE ) ) {
			return;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product || 'simple' !== $product->get_type() ) {
			return;
		}

        $min_quantity = isset( $_POST[ Constants::SIMPLE_PRODUCT_MIN_QUANTITY ] ) // phpcs:ignore
            ? (int) sanitize_text_field( $_POST[ Constants::SIMPLE_PRODUCT_MIN_QUANTITY ] ) // phpcs:ignore
			: 0;
        $max_quantity = isset( $_POST[ Constants::SIMPLE_PRODUCT_MAX_QUANTITY ] ) // phpcs:ignore
            ? (int) sanitize_text_field( $_POST[ Constants::SIMPLE_PRODUCT_MAX_QUANTITY ] ) // phpcs:ignore
			: 0;

		$settings = new ProductMinMaxSettings( $product );
		$settings->set_data(
			array(
				ProductMinMaxSettings::MIN_QUANTITY => $min_quantity,
				ProductMinMaxSettings::MAX_QUANTITY => $max_quantity,
			)
		);
		$settings->save();
	}

	/**
	 * Save single variation min max product
	 *
	 * @since 3.12.0
	 *
	 * @param int $product_id Product ID
	 * @param int $loop Loop index
	 *
	 * @return void
	 */
	public function save_min_max_single_variation_meta( int $product_id, int $loop ): void {
		if ( ! $this->valid_nonce( Constants::VARIATION_PRODUCT_MIN_MAX_NONCE ) ) {
			return;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product || 'variation' !== $product->get_type() ) {
			return;
		}

		[ $min_quantity, $max_quantity ] = $this->validate_data( $loop );

		$setting = new ProductMinMaxSettings( $product );
		$setting->set_data(
			array(
				ProductMinMaxSettings::MIN_QUANTITY => $min_quantity,
				ProductMinMaxSettings::MAX_QUANTITY => $max_quantity,
			)
		);
		$setting->save();
	}

	/**
	 * Validates submitted data
	 *
	 * @since 3.12.0
	 *
	 * @param $loop
	 *
	 * @return array
	 */
	protected function validate_data( $loop ): array {
        $min_quantity = isset( $_POST[ Constants::VARIATION_PRODUCT_MIN_QUANTITY ][ $loop ] ) // phpcs:ignore
            ? (int) sanitize_text_field( $_POST[ Constants::VARIATION_PRODUCT_MIN_QUANTITY ][ $loop ] ) // phpcs:ignore
			: '';
        $max_quantity = isset( $_POST[ Constants::VARIATION_PRODUCT_MAX_QUANTITY ][ $loop ] ) // phpcs:ignore
            ? (int) sanitize_text_field( $_POST[ Constants::VARIATION_PRODUCT_MAX_QUANTITY ][ $loop ] ) // phpcs:ignore
			: '';
		return array( $min_quantity, $max_quantity );
	}
}
