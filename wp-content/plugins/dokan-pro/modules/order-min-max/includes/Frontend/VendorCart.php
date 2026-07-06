<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax\Frontend;

use WC_Cart;

defined( 'ABSPATH' ) || exit;

/**
 * Segregates cart data for specific vendors
 *
 * @since 3.12.0
 */
class VendorCart {
	/**
	 * Products organized by vendor_id and product_id
	 *
	 * @since 3.12.0
	 *
	 * @var array
	 */
	protected array $products = array();

	/**
	 * Total amounts organized by vendor_id
	 *
	 * @since 3.12.0
	 *
	 * @var array
	 */
	protected array $vendor_totals = array();

	/**
	 * Parse cart by vendor
	 *
	 * @since 3.12.0
	 *
	 * @param WC_Cart $cart WooCommerce cart object
	 */
	protected function parse_cart( WC_Cart $cart ): void {
		$this->products      = array();
		$this->vendor_totals = array();

		if ( $cart->is_empty() ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$this->process_cart_item( $cart_item_key, $cart_item );
		}
	}

	/**
	 * Process individual cart item
	 *
	 * @since 3.12.0
	 *
	 * @param string $cart_item_key Unique cart item key
	 * @param array $cart_item Cart item data
	 */
	protected function process_cart_item( string $cart_item_key, array $cart_item ): void {
		$product    = $cart_item['data'];
		$product_id = $product->get_id();
		$vendor_id  = dokan_get_vendor_by_product( $product_id, true );
		$quantity   = $cart_item['quantity'];
		$line_total = $cart_item['line_total'] ?? $product->get_price() * $quantity;

		if ( ! isset( $this->products[ $vendor_id ][ $product_id ] ) ) {
			$this->products[ $vendor_id ][ $product_id ] = array();
		}

		$this->products[ $vendor_id ][ $product_id ][ $cart_item_key ] = array(
			'quantity'   => $quantity,
			'line_total' => $line_total,
			'product'    => $product,
		);

		$this->vendor_totals[ $vendor_id ] = ( $this->vendor_totals[ $vendor_id ] ?? 0 ) + $line_total;
	}

	/**
	 * Ensures cart is processed before accessing data
	 *
	 * @since 3.12.5
	 */
	protected function ensure_cart_processed(): void {
		$this->parse_cart( WC()->cart );
	}

	/**
	 * Fetches total amount of vendor in a customer's cart
	 *
	 * @since 3.12.0
	 *
	 * @param int $vendor_id Vendor ID to fetch total amount
	 *
	 * @return float
	 */
	public function get_cart_total_by_vendor( int $vendor_id ): float {
		$this->ensure_cart_processed();

		return $this->vendor_totals[ $vendor_id ] ?? 0.00;
	}

	/**
	 * Fetches quantity of a product already in cart of a specific vendor
	 *
	 * @since 3.12.0
	 *
	 * @param int $product_id Product ID to fetch quantity
	 *
	 * @return int
	 */
	public function get_product_quantity( int $product_id ): int {
		$this->ensure_cart_processed();

		$vendor_id = dokan_get_vendor_by_product( $product_id, true );

		if ( ! isset( $this->products[ $vendor_id ][ $product_id ] ) ) {
			return 0;
		}

		return array_sum( array_column( $this->products[ $vendor_id ][ $product_id ], 'quantity' ) );
	}

	/**
	 * Returns list of vendors available in the cart
	 *
	 * @since 3.12.0
	 *
	 * @return array
	 */
	public function get_vendor_id_list(): array {
		$this->ensure_cart_processed();

		return array_keys( $this->vendor_totals );
	}

	/**
	 * Returns list of products available in the cart
	 *
	 * @since 3.12.0
	 *
	 * @return array
	 */
	public function get_product_id_list(): array {
		$this->ensure_cart_processed();

		$product_ids = array();
		foreach ( $this->products as $vendor_products ) {
			$product_ids = array_merge( $product_ids, array_keys( $vendor_products ) );
		}

		return array_unique( $product_ids );
	}

	/**
	 * Gets cart grouped by vendor
	 *
	 * @since 3.12.0
	 *
	 * @return array
	 */
	public function get_cart_grouped_by_vendor(): array {
		$this->ensure_cart_processed();

		return $this->products;
	}

	/**
	 * Gets a specific vendor's cart product list
	 *
	 * @since 3.12.0
	 *
	 * @param int $vendor_id Vendor ID to fetch products
	 *
	 * @return array
	 */
	public function get_cart_products_by_vendor( int $vendor_id ): array {
		$this->ensure_cart_processed();

		return $this->products[ $vendor_id ] ?? array();
	}
}
