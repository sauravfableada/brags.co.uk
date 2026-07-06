<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Implies restriction based on settings of min max
 *
 * @since 3.12.0
 */
class CartRestriction {

	/**
	 * Initializes the object
	 *
	 * @since 3.12.0
	 */
	public function __construct() {
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_to_cart' ), 10, 4 );
		add_action( 'woocommerce_before_cart', array( $this, 'cart_page_restriction' ), 9 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'after_checkout_validation' ), 10, 2 );
		add_action( 'woocommerce_store_api_cart_errors', array( $this, 'woocommerce_block_api_validation_restriction' ) );
	}

	/**
	 * Pushes block enabled notices
	 *
	 * @param \WP_Error $cart_error_object WP_Error object.
	 *
	 * @return void
	 * @since 3.12.0
	 */
	protected function push_block_cart_notice( \WP_Error $cart_error_object ): void {
		$notices = dokan_pro()->module->order_min_max->cart_notice->get_notices();
		foreach ( $notices as $notice ) {
			$message = $notice['message'] ?? '';
			$type    = $notice['type'] ?? 'error';
			$cart_error_object->add( $type, $message );
		}
		dokan_pro()->module->order_min_max->cart_notice->flush();
	}

	/**
	 * Pushes legacy cart notices
	 *
	 * @return void
	 * @since 3.12.0
	 */
	protected function push_legacy_notices(): void {
		$notices = dokan_pro()->module->order_min_max->cart_notice->get_notices();
		foreach ( $notices as $notice ) {
			$message = $notice['message'] ?? '';
			$type    = $notice['type'] ?? 'error';
			wc_add_notice( $message, $type );
		}
		dokan_pro()->module->order_min_max->cart_notice->flush();
	}

	/**
	 * Validates the entire cart
	 *
	 * @return bool
	 * @since 3.12.0
	 */
	protected function validate_entire_cart(): bool {
		return dokan_pro()->module->order_min_max->cart_validator->flush()->validate_cart();
	}

	/**
	 * Adds validation for WooCommerce block cart page
	 *
	 * @param \WP_Error $cart_errors WP_Error object.
	 *
	 * @return void
	 * @since 3.12.0
	 */
	public function woocommerce_block_api_validation_restriction( \WP_Error $cart_errors ): void {
		if ( ! $this->validate_entire_cart() ) {
			$this->push_block_cart_notice( $cart_errors );
		}
	}

	/**
	 * Place order validation check added
	 *
	 * @since 3.12.0
	 *
	 * @param array $data Array of data.
	 * @param \WP_Error $errors WP_Error object.
	 *
	 * @return void
	 */
	public function after_checkout_validation( array $data, \WP_Error $errors ) {
		if ( ! $this->validate_entire_cart() ) {
			$this->push_block_cart_notice( $errors );
		}
	}

	/**
	 * Adds notice and checkout restriction to cart page
	 *
	 * @since 3.12.0
	 *
	 * @return void
	 */
	public function cart_page_restriction(): void {
		if ( is_cart() && ! $this->validate_entire_cart() ) {
			remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
			$this->push_legacy_notices();
		}
	}

	/**
	 * Validate add to cart for customers
	 *
	 * @param bool $passed_validation True if the item passed validation.
	 * @param int $product_id Product ID being validated.
	 * @param int $quantity Quantity added to the cart.
	 * @param int $variation_id Variation ID being added to the cart.
	 *
	 * @return bool
	 */
	public function validate_add_to_cart( bool $passed_validation, int $product_id, int $quantity, int $variation_id = 0 ): bool {
		if ( ! $passed_validation ) {
			return false;
		}

		$product_id     = 0 !== $variation_id ? $variation_id : $product_id;
		$product        = wc_get_product( $product_id );
		$vendor_id      = dokan_get_vendor_by_product( $product_id, true );
		$cart_validator = dokan_pro()->module->order_min_max->cart_validator;

		$cart_validator->flush();
		$cart_validator->set_additional_quantity( $product_id, $quantity );
		$cart_validator->set_additional_amount( $vendor_id, ( (float) $product->get_price() ) * $quantity );

		$is_valid = $cart_validator->validate_max_quantity( $product_id ) && $cart_validator->validate_max_amount( $vendor_id );

		if ( ! $is_valid ) {
			$this->push_legacy_notices();
		}

		return $is_valid;
	}
}
