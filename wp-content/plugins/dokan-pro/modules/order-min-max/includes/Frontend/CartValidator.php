<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax\Frontend;

use WeDevs\DokanPro\Modules\OrderMinMax\DataSource\ProductMinMaxSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Manages cart validation
 *
 * @since 3.12.0
 */
class CartValidator {
	/**
	 * Additional quantity to check
	 *
	 * @var array
	 *
	 * @since 3.12.0
	 */
	protected array $add_quantity = array();

	/**
	 * Additional amount to check
	 *
	 * @var array
	 *
	 * @since 3.12.0
	 */
	protected array $add_amount = array();

	/**
	 * Sets additional quantity to be used when validating add to cart
	 *
	 * @param int $product_id Product ID
	 * @param int $quantity Quantity to be added
	 *
	 * @return $this
	 */
	public function set_additional_quantity( int $product_id, int $quantity ): CartValidator {
		$this->add_quantity[ $product_id ] = (int) $quantity;
		return $this;
	}

	/**
	 * Gets additional quantity by product if needed elsewhere
	 *
	 * @param int $product_id Product ID
	 *
	 * @return int
	 */
	public function get_additional_quantity( int $product_id ): int {
		return $this->add_quantity[ $product_id ] ?? 0;
	}

	/**
	 * Sets additional amount to be considered for vendor total cart amount
	 *
	 * @since 3.12.0
	 *
	 * @param int $vendor_id Vendor ID
	 * @param float $amount Additional amount to be considered
	 *
	 * @return $this
	 */
	public function set_additional_amount( int $vendor_id, float $amount ): CartValidator {
		$this->add_amount[ $vendor_id ] = (float) $amount;
		return $this;
	}

	/**
	 * Gets additional cart amount for vendor to be used elsewhere
	 *
	 * @since 3.12.0
	 *
	 * @param int $vendor_id Vendor ID
	 *
	 * @return float
	 */
	public function get_additional_amount( int $vendor_id ): float {
		return $this->add_amount[ $vendor_id ] ?? 0.00;
	}

	/**
	 * Validates the entire cart before moving forward
	 *
	 * @since 3.12.0
	 *
	 * @return bool
	 */
	public function validate_cart(): bool {
		return $this->validate_quantity() && $this->validate_amount();
	}

	/**
	 * Get the vendor cart instance
	 *
	 * @since 3.12.0
	 *
	 * @return VendorCart
	 */
	protected function get_vendor_cart(): VendorCart {
		return dokan_pro()->module->order_min_max->vendor_cart;
	}

	/**
	 * Validates quantity for all the cart products
	 *
	 * @since 3.12.0
	 *
	 * @return bool
	 */
	public function validate_quantity(): bool {
		$passed = true;
		foreach ( $this->get_vendor_cart()->get_product_id_list() as $product_id ) {
			$valid_for_product = $this->validate_max_quantity( $product_id ) && $this->validate_min_quantity( $product_id );
			$passed            = $passed && $valid_for_product; // If any one of the validation is not passed then false is returned
		}
		return $passed;
	}

	/**
	 * Validates amount for all the vendors in the cart
	 *
	 * @since 3.12.0
	 *
	 * @return bool
	 */
	public function validate_amount(): bool {
		$passed = true;
		foreach ( $this->get_vendor_cart()->get_vendor_id_list() as $vendor_id ) {
			$valid_for_vendor = $this->validate_max_amount( $vendor_id ) && $this->validate_min_amount( $vendor_id );
			$passed           = $passed && $valid_for_vendor; // If any one of the validation is not passed then false is returned
		}
		return $passed;
	}

	/**
	 * Validates max quantity by product
	 *
	 * @since 3.12.0
	 *
	 * @param int $product_id Product ID to validate
	 *
	 * @return bool
	 */
	public function validate_max_quantity( int $product_id ): bool {
		$passed = true;

		/**
		 * Filter to check if the order min max quantity rules are applicable
		 *
		 * @since 3.12.0
		 *
		 * @param bool $is_applicable True if the rules are applicable, false otherwise
		 * @param \WC_Product $product Product object
		 */
		if ( ! apply_filters( 'dokan_order_min_max_quantity_rules_applicable', true, wc_get_product( $product_id ) ) ) {
			return true;
		}

		$product_quantity_in_cart      = $this->get_vendor_cart()->get_product_quantity( $product_id ) + $this->get_additional_quantity( $product_id ); // Consider additional quantity
		$product_max_quantity_settings = ( new ProductMinMaxSettings( $product_id ) )->max_quantity();

		if ( ! empty( $product_max_quantity_settings ) && $product_quantity_in_cart > $product_max_quantity_settings ) {
			$passed = false;
			dokan_pro()->module->order_min_max->cart_notice->add_maximum_quantity_violation_notice( $product_id );
		}
		return $passed;
	}

	/**
	 * Validate min quantity by product
	 *
	 * @since 3.12.0
	 *
	 * @param int $product_id Product ID to validate
	 *
	 * @return bool
	 */
	public function validate_min_quantity( int $product_id ): bool {
		$passed = true;

		/**
		 * Filter to check if the order min max quantity rules are applicable
		 *
		 * @since 3.12.0
		 *
		 * @param bool $is_applicable True if the rules are applicable, false otherwise
		 * @param \WC_Product $product Product object
		 */
		if ( ! apply_filters( 'dokan_order_min_max_quantity_rules_applicable', true, wc_get_product( $product_id ) ) ) {
			return true;
		}

		$product_quantity_in_cart      = $this->get_vendor_cart()->get_product_quantity( $product_id );
		$product_min_quantity_settings = ( new ProductMinMaxSettings( $product_id ) )->min_quantity();

		if ( ! empty( $product_min_quantity_settings ) && $product_quantity_in_cart < $product_min_quantity_settings ) {
			$passed = false;
			dokan_pro()->module->order_min_max->cart_notice->add_minimum_quantity_violation_notice( $product_id );
		}
		return $passed;
	}

	/**
	 * Validates max amount for vendor
	 *
	 * @since 3.12.0
	 *
	 * @param int $vendor_id
	 *
	 * @return bool
	 */
	public function validate_max_amount( int $vendor_id ): bool {
		$passed = true;

		/**
		 * Filter to check if the order min max amount rules are applicable
		 *
		 * @since 3.12.0
		 *
		 * @param bool $is_applicable True if the rules are applicable, false otherwise
		 * @param int $vendor_id Vendor ID
		 */
		if ( ! apply_filters( 'dokan_order_min_max_amount_rules_applicable', true, $vendor_id ) ) {
			return true;
		}

		$vendor_amount_in_cart      = $this->get_vendor_cart()->get_cart_total_by_vendor( $vendor_id ) + $this->get_additional_amount( $vendor_id ); // Considering additional amount;
		$vendor_max_amount_settings = dokan_pro()->module->order_min_max->store_min_max_settings->get_max_amount_for_order( $vendor_id );

		if ( ! empty( $vendor_max_amount_settings ) && $vendor_amount_in_cart > $vendor_max_amount_settings ) {
			$passed = false;
			dokan_pro()->module->order_min_max->cart_notice->add_maximum_amount_violation_notice( $vendor_id );
		}
		return $passed;
	}

	/**
	 * Validate min amount for product
	 *
	 * @since 3.12.0
	 *
	 * @param $vendor_id
	 *
	 * @return bool
	 */
	public function validate_min_amount( $vendor_id ): bool {
		$passed = true;

		/**
		 * Filter to check if the order min max amount rules are applicable
		 *
		 * @since 3.12.0
		 *
		 * @param bool $is_applicable True if the rules are applicable, false otherwise
		 * @param int $vendor_id Vendor ID
		 */
		if ( ! apply_filters( 'dokan_order_min_max_amount_rules_applicable', true, $vendor_id ) ) {
			return true;
		}

		$vendor_amount_in_cart      = $this->get_vendor_cart()->get_cart_total_by_vendor( $vendor_id );
		$vendor_min_amount_settings = dokan_pro()->module->order_min_max->store_min_max_settings->get_min_amount_for_order( $vendor_id );

		if ( ! empty( $vendor_min_amount_settings ) && $vendor_amount_in_cart < $vendor_min_amount_settings ) {
			$passed = false;
			dokan_pro()->module->order_min_max->cart_notice->add_minimum_amount_violation_notice( $vendor_id );
		}
		return $passed;
	}

	/**
	 * Empty all additional amount and quantity
	 *
	 * @since 3.12.0
	 *
	 * @return CartValidator
	 */
	public function flush(): self {
		$this->add_amount   = array();
		$this->add_quantity = array();

		return new self();
	}
}
