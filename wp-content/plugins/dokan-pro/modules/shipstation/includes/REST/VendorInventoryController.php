<?php
/**
 * ShipStation REST API Inventory Controller file.
 *
 * @package WeDevs\DokanPro\Modules\ShipStation
 */

namespace WeDevs\DokanPro\Modules\ShipStation\REST;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WC_Product;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_REST_Controller;
use Automattic\WooCommerce\Enums\ProductType;
/**
 * VendorInventoryController class.
 */
class VendorInventoryController extends WP_REST_Controller {

	/**
	 * Vendor ID resolved from the authenticated WC API user.
	 *
	 * @var int
	 */
	private int $vendor_id = 0;

	public function __construct() {
		$this->rest_base = 'inventory';
		$this->namespace = 'wc-shipstation/v1';
	}

	/**
	 * Get the vendor ID lazily.
	 *
	 * REST authentication hasn't occurred when constructors run,
	 * so get_current_user_id() must be called lazily in request handlers.
	 *
	 * @return int
	 */
	private function get_vendor_id(): int {
		if ( 0 === $this->vendor_id ) {
			$this->vendor_id = get_current_user_id();
		}

		return $this->vendor_id;
	}

	/**
	 * Register the routes for the controller.
	 */
	public function register_routes(): void {

		// Register the endpoint for retrieving stock data.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_inventory' ),
				'permission_callback' => array( $this, 'check_get_permission' ),
				'args'                => array(
					'page'     => array(
						'description'       => __( 'Page number of the results to return.', 'dokan' ),
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => function ( $value ) {
							return max( 1, absint( $value ) );
						},
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
					'per_page' => array(
						'description'       => __( 'Maximum number of items to return per page (1–500).', 'dokan' ),
						'type'              => 'integer',
						'default'           => 100,
						'sanitize_callback' => function ( $value ) {
							return min( max( - 1, absint( $value ) ), 500 ); // Limit between 1 and 500.
						},
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		// Register the endpoint for retrieving stock data by product ID.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<product_id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_inventory_by_id' ),
				'permission_callback' => array( $this, 'check_get_permission' ),
				'args'                => array(
					'product_id' => array(
						'description'       => __( 'ID of the product to retrieve stock data for.', 'dokan' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		// Register the endpoint for updating stock data by SKU.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/sku/(?P<sku>[\w-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_inventory_by_sku' ),
				'permission_callback' => array( $this, 'check_get_permission' ),
				'args'                => array(
					'sku' => array(
						'description'       => __( 'SKU of the product to retrieve stock data for.', 'dokan' ),
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $param ) {
							return is_string( $param );
						},
					),
				),
			)
		);

		// Register the endpoint for updating stock data.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/update',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_inventory' ),
				'permission_callback' => array( $this, 'check_update_permission' ),
				'args'                => array(
					'items' => array(
						'description' => __( 'Array of inventory items to update. Each item must contain stock_quantity and either product_id or sku.', 'dokan' ),
						'type'        => 'array',
						'required'    => true,
						'minItems'    => 1,
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'product_id'     => array(
									'description' => __( 'Product ID to update stock for.', 'dokan' ),
									'type'        => 'integer',
								),
								'sku'            => array(
									'description' => __( 'SKU of the product to update stock for.', 'dokan' ),
									'type'        => 'string',
								),
								'stock_quantity' => array(
									'description' => __( 'New stock quantity to set.', 'dokan' ),
									'type'        => 'integer',
									'required'    => true,
								),
							),
						),
						'validate_callback' => array( $this, 'validate_inventory_items' ),
						'sanitize_callback' => array( $this, 'sanitize_inventory_items' ),
					),
				),
			)
		);
	}

	/**
	 * REST API permission callback.
	 *
	 * @return bool|WP_Error
	 */
	public function check_get_permission() {
		if ( current_user_can( 'manage_options' ) || current_user_can( 'dokandar' ) ) {
			return true;
		}

		return new \WP_Error(
			'dokan_pro_permission_failure',
			__( 'Sorry! You are not permitted to do current action.', 'dokan' ),
			[ 'status' => 403 ]
		);
	}

	/**
	 * REST API permission callback.
	 *
	 * @return bool|WP_Error
	 */
	public function check_update_permission() {
		return $this->check_get_permission();
	}

	/**
	 * Validate inventory items from the request body.
	 *
	 * @param mixed $items Items to validate.
	 *
	 * @return bool|WP_Error
	 */
	public function validate_inventory_items( $items ) {
		if ( ! is_array( $items ) || empty( $items ) ) {
			return new \WP_Error(
				'rest_invalid_param',
				__( 'Items must be a non-empty array.', 'dokan' ),
				array( 'status' => 400 )
			);
		}

		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				return new \WP_Error(
					'rest_invalid_param',
					/* translators: %d: item index */
					sprintf( __( 'Item at index %d must be an object.', 'dokan' ), $index ),
					array( 'status' => 400 )
				);
			}

			if ( empty( $item['product_id'] ) && empty( $item['sku'] ) ) {
				return new \WP_Error(
					'rest_invalid_param',
					/* translators: %d: item index */
					sprintf( __( 'Item at index %d must contain either product_id or sku.', 'dokan' ), $index ),
					array( 'status' => 400 )
				);
			}

			if ( ! empty( $item['product_id'] ) && ! is_numeric( $item['product_id'] ) ) {
				return new \WP_Error(
					'rest_invalid_param',
					/* translators: %d: item index */
					sprintf( __( 'Item at index %d has a non-numeric product_id.', 'dokan' ), $index ),
					array( 'status' => 400 )
				);
			}

			if ( ! isset( $item['stock_quantity'] ) || ! is_numeric( $item['stock_quantity'] ) ) {
				return new \WP_Error(
					'rest_invalid_param',
					/* translators: %d: item index */
					sprintf( __( 'Item at index %d must contain a numeric stock_quantity.', 'dokan' ), $index ),
					array( 'status' => 400 )
				);
			}
		}

		return true;
	}

	/**
	 * Sanitize inventory items from the request body.
	 *
	 * @param array $items Items to sanitize.
	 *
	 * @return array
	 */
	public function sanitize_inventory_items( array $items ): array {
		return array_map(
			function ( $item ) {
				$sanitized = array(
					'stock_quantity' => intval( $item['stock_quantity'] ),
				);

				if ( ! empty( $item['product_id'] ) ) {
					$sanitized['product_id'] = absint( $item['product_id'] );
				}

				if ( ! empty( $item['sku'] ) ) {
					$sanitized['sku'] = sanitize_text_field( wp_unslash( $item['sku'] ) );
				}

				return $sanitized;
			},
			$items
		);
	}

	/**
	 * Get product data for API response.
	 *
	 * @param WC_Product $product Product object.
	 *
	 * @return array Product data.
	 */
	private function get_product_data( WC_Product $product ): array {
		$product_data = array(
			'product_id'     => $product->get_id(),
			'sku'            => $product->get_sku(),
			'name'           => $product->get_name(),
			'stock_quantity' => $product->get_stock_quantity(),
			'stock_status'   => $product->get_stock_status(),
			'manage_stock'   => $product->get_manage_stock(),
			'backorders'     => $product->get_backorders(),
		);

		// Add the parent_id when relevant.
		$parent_id = $product->get_parent_id();
		if ( $parent_id ) {
			$product_data['parent_id'] = $parent_id;
		}

		return $product_data;
	}

	/**
	 * Get product data by product ID.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return array
	 */
	public function get_product_data_by_id( int $product_id ): array {
		if ( $product_id <= 0 ) {
			return array();
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return array();
		}

		// Verify the product belongs to the authenticated vendor.
		$product_vendor_id = (int) dokan_get_vendor_by_product( $product, true );
		if ( $product_vendor_id !== $this->get_vendor_id() ) {
			return array();
		}

		return $this->get_product_data( $product );
	}

	/**
	 * Get a REST response with the provided product data.
	 *
	 * @param array $product_data Product data array.
	 *
	 * @return WP_REST_Response
	 */
	private function get_product_response( array $product_data ): WP_REST_Response {
		if ( empty( $product_data ) ) {
			return new WP_REST_Response( array( 'message' => __( 'Product not found.', 'dokan' ) ), 404 );
		}

		return new WP_REST_Response( $product_data, 200 );
	}

	/**
	 * Retrieve the inventory stock data for a specific product by ID.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_inventory_by_id( WP_REST_Request $request ): WP_REST_Response {
		// Get the product ID from the request.
		$product_id   = (int) $request->get_param( 'product_id' );
		$product_data = $this->get_product_data_by_id( $product_id );

		return $this->get_product_response( $product_data );
	}

	/**
	 * Retrieve the inventory stock data for a specific product by SKU.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_inventory_by_sku( WP_REST_Request $request ): WP_REST_Response {
		// Get the SKU from the request.
		$sku          = (string) $request->get_param( 'sku' );
		$product_id   = wc_get_product_id_by_sku( wc_clean( wp_unslash( $sku ) ) );
		$product_data = $this->get_product_data_by_id( $product_id );

		return $this->get_product_response( $product_data );
	}

	/**
	 * Retrieve the inventory stock data for all products and variations.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_inventory( WP_REST_Request $request ): WP_REST_Response {
		$request_params = $request->get_params();

		// Get pagination parameters.
		$page     = absint( $request_params['page'] ); // Default to page 1.
		$per_page = intval( $request_params['per_page'] ); // Default to 100 items per page.

		$args = array(
			'type'     => array( ProductType::SIMPLE, ProductType::VARIABLE, ProductType::GROUPED, ProductType::EXTERNAL, ProductType::VARIATION ),
			'limit'    => $per_page,
			'page'     => $page,
			'paginate' => true,
			'author'   => $this->get_vendor_id(),
		);

		$results = wc_get_products( $args );

		if ( is_wp_error( $results ) ) {
			return new WP_REST_Response( array( 'message' => __( 'Error retrieving products.', 'dokan' ) ), 500 );
		}

		$total_products = $results->total;

		// Calculate pagination information.
		$total_pages = $results->max_num_pages;
		$has_more    = $page < $total_pages;

		// Prepare the response data.
		$inventory_data = array(
			'products'   => array(),
			'pagination' => array(
				'page'           => $page,
				'per_page'       => $per_page,
				'total_products' => $total_products,
				'total_pages'    => $total_pages,
				'has_more'       => $has_more,
			),
		);

		if ( empty( $results->products ) || empty( $results->total ) ) {
			// No products found, return an empty response.
			return new WP_REST_Response( $inventory_data, 200 );
		}

		foreach ( $results->products as $product ) {
			$inventory_data['products'][] = $this->get_product_data( $product );
		}

		return new WP_REST_Response( $inventory_data, 200 );
	}

	/**
	 * Update inventory stock for specified SKUs (both products and variations).
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function update_inventory( WP_REST_Request $request ): WP_REST_Response {
		$items = $request->get_param( 'items' );

		$updated = array();
		$errors  = array();

		foreach ( $items as $item ) {
			if ( ! empty( $item['product_id'] ) ) {
				$product = wc_get_product( $item['product_id'] );
			} else {
				$product = wc_get_product( wc_get_product_id_by_sku( $item['sku'] ) );
			}

			if ( ! $product ) {
				$errors[] = array(
					'item'    => $item,
					'message' => __( 'Product not found', 'dokan' ),
				);
				continue;
			}

			// Verify the product belongs to the authenticated vendor.
			$product_vendor_id = (int) dokan_get_vendor_by_product( $product, true );
			if ( $product_vendor_id !== $this->get_vendor_id() ) {
				$errors[] = array(
					'item'    => $item,
					'message' => __( 'Product does not belong to this vendor', 'dokan' ),
				);
				continue;
			}

			$stock_qty = $item['stock_quantity'];

			$product->set_manage_stock( true );
			$product->set_stock_quantity( $stock_qty );
			$product->set_stock_status( $stock_qty > 0 ? 'instock' : 'outofstock' );
			$product->save();

			$updated[] = array(
				'sku'        => $product->get_sku(),
				'product_id' => $product->get_id(),
				'stock'      => $stock_qty,
			);
		}

		$message = __( 'Inventory updated successfully.', 'dokan' );

		if ( count( $errors ) > 0 && count( $updated ) === 0 ) {
			// If there are errors and no successful updates, return the errors.
			$message = __( 'No inventory updated due to errors.', 'dokan' );
		}

		if ( count( $errors ) > 0 && count( $updated ) > 0 ) {
			// If there are errors but some updates were successful, return both.
			$message = __( 'Inventory updated with some errors.', 'dokan' );
		}

		if ( count( $errors ) === 0 && count( $updated ) === 0 ) {
			// If there are no errors and no updates, return a message indicating no changes.
			$message = __( 'No inventory changes made.', 'dokan' );
		}

		return new WP_REST_Response(
			array(
				'message'       => $message,
				'updated'       => $updated,
				'updated_count' => count( $updated ),
				'errors'        => $errors,
				'error_count'   => count( $errors ),
			),
			200
		);
	}
}
