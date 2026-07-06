<?php

namespace WeDevs\DokanPro\VendorDiscount\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * Class Settings
 *
 * @since   3.9.4
 *
 * @package WeDevs\DokanPro\VendorDiscount\Admin
 */
class Settings {

    /**
     * Class constructor
     *
     * @since 3.9.4
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'dokan_settings_selling_option_vendor_capability', [ $this, 'add_discount_settings' ], 11, 1 );
    }

    /**
     * Add discount settings
     *
     * @since 2.9.13
     * @since 3.9.4 extracted this method from add_settings_selling_option_vendor_capability() under includes/Admin/Admin.php to here
     *
     * @param array $settings_fields
     *
     * @return array
     */
    public function add_discount_settings( $settings_fields ) {
        // New subsection for discount settings
        $settings_fields['discount_edit_section'] = [
            'name'          => 'discount_edit_section',
            'type'          => 'sub_section',
            'label'         => __( 'Vendor Quantity and Order Discount', 'dokan' ),
            'description'   => __( 'This setting enables vendors to set discounts based on the quantity of items ordered from a specific vendor. Also, vendor can set a discount percentage which will be applied to the total order amount.', 'dokan' ),
            'content_class' => 'sub-section-styles',
        ];

        // discount settings field
        $settings_fields['discount_edit'] = [
            'name'    => 'discount_edit',
            'label'   => __( 'Discount Editing', 'dokan' ),
            'desc'    => __( 'Vendor can add order and product quantity discount', 'dokan' ),
            'type'    => 'multicheck',
            'default' => [
                'order-discount'   => '',
                'product-discount' => '',
            ],
            'options' => [
                'order-discount'   => __( 'Order Discount', 'dokan' ),
                'product-discount' => __( 'Product Quantity Discount', 'dokan' ),
            ],
            'is_lite' => false,
        ];

        return $settings_fields;
    }

    /**
     * Check if admin enabled order discount for vendors
     *
     * @since 3.9.4
     *
     * @return bool
     */
    public function is_order_discount_enabled() {
        $discount_settings = dokan_get_option( 'discount_edit', 'dokan_selling', [] );

        return isset( $discount_settings['order-discount'] ) && $discount_settings['order-discount'] === 'order-discount';
    }

    /**
     * Check if admin enabled product quantity discount for vendors
     *
     * @since 3.9.4
     *
     * @return bool
     */
    public function is_product_discount_enabled() {
        $discount_settings = dokan_get_option( 'discount_edit', 'dokan_selling', [] );

        return isset( $discount_settings['product-discount'] ) && $discount_settings['product-discount'] === 'product-discount';
    }
}
