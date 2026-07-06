<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax;

/**
 * A wrapper class to hold module specific constants
 *
 * @since 3.12.0
 */
class Constants {

	/**
	 * Script and style handle
	 */
	const ORDER_MIN_MAX_ADMIN_STYLE            = 'dokan-order-min-max-admin-style';
	const ORDER_MIN_MAX_ADMIN_SCRIPT           = 'dokan-order-min-max-admin-script';
	const ORDER_MIN_MAX_VENDOR_SCRIPT          = 'dokan-order-min-max-vendor-script';
	const ORDER_MIN_MAX_VENDOR_STYLE           = 'dokan-order-min-max-vendor-style';

	/**
	 * Admin metabox keys for simple products
	 */
	const SIMPLE_PRODUCT_MIN_MAX_WRAPPER = 'dokan_simple_product_min_max_wrapper';
	const SIMPLE_PRODUCT_MIN_QUANTITY    = 'min_quantity';
	const SIMPLE_PRODUCT_MAX_QUANTITY    = 'max_quantity';
	const SIMPLE_PRODUCT_MESSAGE_SECTION = 'dokan_simple_product_message_section';
	const SIMPLE_PRODUCT_MIN_MAX_NONCE   = 'dokan_simple_product_min_max_nonce';

	/**
	 * Admin metabox keys for variable products
	 */
	const VARIATION_PRODUCT_MIN_MAX_WRAPPER = 'dokan_variation_product_min_max_wrapper';
	const VARIATION_PRODUCT_MIN_QUANTITY    = 'variable_min_quantity';
	const VARIATION_PRODUCT_MAX_QUANTITY    = 'variable_max_quantity';
	const VARIATION_PRODUCT_MESSAGE_SECTION = 'dokan_variation_product_message_section';
	const VARIATION_PRODUCT_MIN_MAX_NONCE   = 'dokan_variation_product_min_max_nonce';

	/**
	 * Quick and bulk edit constants
	 */
	const QUICK_EDIT_MINIMUM_QUANTITY = 'dokan_order_min_max_quick_edit_minimum_quantity';
	const QUICK_EDIT_MAXIMUM_QUANTITY = 'dokan_order_min_max_quick_edit_maximum_quantity';
	const QUICK_EDIT_META_DATA        = 'order-min-max-quick-edit-meta-data';

	/**
	 * Vendor bulk edit constants
	 *
	 * @since 3.12.0
	 */
	const BULK_EDIT_VENDOR_MINIMUM_QUANTITY = 'dokan_order_min_max_vendor_bulk_edit_minimum_quantity';
	const BULK_EDIT_VENDOR_MAXIMUM_QUANTITY = 'dokan_order_min_max_vendor_bulk_edit_maximum_quantity';

	const VENDOR_SIMPLE_PRODUCT_METABOX_WRAPPER = 'dokan-order-min-max-product-metabox-wrapper';
	const VENDOR_VARIATION_PRODUCT_METABOX_WRAPPER = 'dokan-order-min-max-variation-product-metabox-wrapper';

	/**
	 * Frontend accessible object name
	 */
	const ORDER_MIN_MAX_JS_CONSTANT_OBJECT = 'dokan_order_min_max';

	/**
	 * Single product meta key
	 */
	const SINGLE_PRODUCT_META_KEY = '_dokan_min_max_meta';

	/**
	 * Declares Dynamic Constants
	 *
	 * @since 3.12.0
	 *
	 * @return void
	 */
	public static function dynamic_constants() {
		defined( 'ORDER_MIN_MAX_FILE' ) ||
			define( 'ORDER_MIN_MAX_FILE', __DIR__ );
		defined( 'DOKAN_DOKAN_ORDER_MIN_MAX_ASSETS_DIR' )
			|| define( 'DOKAN_DOKAN_ORDER_MIN_MAX_ASSETS_DIR', plugins_url( 'assets', __FILE__ ) );
		defined( 'DOKAN_ORDER_MIN_MAX_PATH' )
			|| define( 'DOKAN_ORDER_MIN_MAX_PATH', dirname( ORDER_MIN_MAX_FILE ) );
		defined( 'DOKAN_ORDER_MIN_MAX_INCLUDES' )
			|| define( 'DOKAN_ORDER_MIN_MAX_INCLUDES', DOKAN_ORDER_MIN_MAX_PATH . '/includes' );
		defined( 'DOKAN_ORDER_MIN_MAX_URL' )
			|| define( 'DOKAN_ORDER_MIN_MAX_URL', plugins_url( '', ORDER_MIN_MAX_FILE ) );
		defined( 'DOKAN_ORDER_MIN_MAX_ASSETS' )
			|| define( 'DOKAN_ORDER_MIN_MAX_ASSETS', DOKAN_ORDER_MIN_MAX_URL . '/assets' );
		defined( 'DOKAN_ORDER_MIN_MAX_TEMPLATE_PATH' )
			|| define( 'DOKAN_ORDER_MIN_MAX_TEMPLATE_PATH', DOKAN_ORDER_MIN_MAX_PATH . '/templates/' );
	}

	/**
	 * Exports all static constants of the class
	 *
	 * @since 3.12.0
	 *
	 * @return array
	 */
	public static function get_all_static_constants(): array {
		return ( new \ReflectionClass( __CLASS__ ) )->getConstants();
	}
}
