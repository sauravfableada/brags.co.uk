<?php

/**
 * Dokan Order Min Max Module
 *
 * @since 3.5.0
 */

namespace WeDevs\DokanPro\Modules\OrderMinMax;

use WeDevs\Dokan\Traits\ChainableContainer;
use WeDevs\DokanPro\Modules\OrderMinMax\Admin\MetaFields;
use WeDevs\DokanPro\Modules\OrderMinMax\Admin\QuickAndBulkEdit;
use WeDevs\DokanPro\Modules\OrderMinMax\DataSource\StoreMinMaxSettings;
use WeDevs\DokanPro\Modules\OrderMinMax\Frontend\CartRestriction;
use WeDevs\DokanPro\Modules\OrderMinMax\Frontend\CartValidator;
use WeDevs\DokanPro\Modules\OrderMinMax\Frontend\IntegrationHooks;
use WeDevs\DokanPro\Modules\OrderMinMax\Frontend\Notice;
use WeDevs\DokanPro\Modules\OrderMinMax\Frontend\VendorCart;
use WeDevs\DokanPro\Modules\OrderMinMax\SettingsApi\Store;
use WeDevs\DokanPro\Modules\OrderMinMax\Vendor\MetaFields as VendorMetaFields;
use WeDevs\DokanPro\Modules\OrderMinMax\Vendor\QuickAndBulkEdit as VendorQuickAndBulkEdit;

defined( 'ABSPATH' ) || exit;

/**
 * Class for Request A Quote module integration.
 *
 * @since 3.5.0
 *
 * @property-read MetaFields $admin_meta_fields Admin Meta Fields
 * @property-read QuickAndBulkEdit $quick_and_bulk_edit Quick and Bulk Edit
 * @property-read Assets $assets Assets
 * @property-read VendorQuickAndBulkEdit $vendor_quick_and_bulk_edit Vendor Quick and Bulk Edit
 * @property-read VendorMetaFields $vendor_meta_fields Vendor Meta Fields
 * @property-read DataSaver $data_saver Data Saver
 * @property-read Vendor $vendor Vendor
 * @property-read Frontend $frontend Frontend
 * @property-read IntegrationHooks $integration_hooks Frontend Hooks
 * @property-read BlockData $block_data Block Data
 * @property-read Store $store Store
 * @property-read StoreMinMaxSettings $store_min_max_settings Store Min Max Settings
 * @property-read VendorCart $vendor_cart Vendor Cart
 * @property-read CartValidator $cart_validator Cart Validator
 * @property-read CartRestriction $cart_restriction Cart Restriction
 * @property-read Notice $cart_notice Cart Notice
 */
class Module {

	use ChainableContainer;

	/**
	 * Class constructor.
	 *
	 * @since 3.5.0
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Init the module.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	public function init() {
		Constants::dynamic_constants();
		$this->create_instances();
		$this->admin_only_instances();
	}

	/**
	 * Executes admin only logic
	 *
	 * @since 3.12.0
	 */
	public function admin_only_instances() {
		if ( is_admin() ) {
			$this->container['admin_meta_fields']   = new MetaFields();
			$this->container['quick_and_bulk_edit'] = new QuickAndBulkEdit();
		}
	}

	/**
	 * Initiate all classes
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	public function create_instances() {
		$this->container['assets']                     = new Assets();
		$this->container['vendor_quick_and_bulk_edit'] = new VendorQuickAndBulkEdit();
		$this->container['vendor_meta_fields']         = new VendorMetaFields();
		$this->container['data_saver']                 = new DataSaver();
		$this->container['vendor']                     = new Vendor();
		$this->container['frontend']                   = new Frontend();
		$this->container['integration_hooks']          = new IntegrationHooks();
		$this->container['block_data']                 = new BlockData();
		$this->container['store']                      = new Store();
		$this->container['store_min_max_settings']     = new StoreMinMaxSettings();
		$this->container['vendor_cart']                = new VendorCart();
		$this->container['cart_validator']             = new CartValidator();
		$this->container['cart_restriction']           = new CartRestriction();
		$this->container['cart_notice']                = new Notice();
        $this->container['product_editor_fields']        = new ProductEditorFields();
	}
}
