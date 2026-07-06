<?php

namespace WeDevs\DokanPro\VendorDiscount;

use WeDevs\Dokan\Traits\ChainableContainer;
use WeDevs\DokanPro\VendorDiscount\Admin\Discount;

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * Class Controller
 *
 * @since   3.9.4
 *
 * @package WeDevs\DokanPro\VendorDiscount
 *
 * @property Admin\Settings         $admin_settings     Admin settings controller
 * @property Admin\Hooks            $admin_hooks        Admin settings controller
 * @property Frontend\Dashboard     $frontend_dashboard Frontend dashboard controller
 * @property Frontend\StoreSettings $store_settings     Frontend store controller
 * @property Hooks                  $woocommerce_hooks  WooCommerce hooks controller
 * @property DeprecatedMethods      $deprecated_methods Deprecated methods controller
 */
class Controller {

    use ChainableContainer;

    /**
     * Class constructor
     *
     * @since 3.9.4
     *
     * @return void
     */
    public function __construct() {
        $this->set_controllers();
    }

    /**
     * Set controllers
     *
     * @since 3.9.4
     *
     * @return void
     */
    private function set_controllers() {
        $this->container['admin_settings']     = new Admin\Settings();
        $this->container['admin_hooks']        = new Admin\Hooks();
        $this->container['frontend_dashboard'] = new Frontend\Dashboard();
        $this->container['store_settings']     = new Frontend\StoreSettings();
        $this->container['woocommerce_hooks']  = new Hooks();
        $this->container['deprecated_methods'] = new DeprecatedMethods();
        $this->container['dokan_admin_discount'] = new Discount();
        $this->container['product_editor_fields'] = new ProductEditorFields();
    }
}
