<?php

namespace WeDevs\DokanPro\VendorDiscount;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Class for integrating with WooCommerce Blocks
 *
 * @since DOKAN_POR_SINCE
 */
class BlockSupportIntegration implements IntegrationInterface {
    /**
     * The name of the integration.
     *
     * @since DOKAN_POR_SINCE
     *
     * @return string
     */
    public function get_name() {
        return 'dokan_vendor_discount';
    }

    /**
     * When called invokes any initialization/setup for the integration.
     *
     * @since DOKAN_POR_SINCE
     *
     * @return void
     */
    public function initialize() {
        $script_path = '/blocks/vendor-discount/index.js';

        /**
         * The assets linked below should be a path to a file.
         */
        $script_url = DOKAN_PRO_PLUGIN_ASSEST . $script_path;

        $script_asset_path = DOKAN_PRO_DIR . '/assets/blocks/index.asset.php';
        $script_asset      = file_exists( $script_asset_path )
            ? require $script_asset_path
            : [
                'dependencies' => [],
                'version'      => $this->get_file_version( DOKAN_PRO_DIR . $script_path ),
            ];

        wp_register_script(
            'dokan-vendor-discount',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );
    }

    /**
     * Returns an array of script handles to enqueue in the frontend context.
     *
     * @since DOKAN_POR_SINCE
     *
     * @return string[]
     */
    public function get_script_handles() {
        return ['dokan-vendor-discount'];
    }

    /**
     * Returns an array of script handles to enqueue in the editor context.
     *
     * @return string[]
     */
    public function get_editor_script_handles() {
        return [''];
    }

    /**
     * An array of key, value pairs of data made available to the block on the client side.
     *
     * @since DOKAN_POR_SINCE
     *
     * @return array
     */
    public function get_script_data() {
		if ( ( ! is_cart() && ! is_checkout() ) || empty(wc()->cart) ) {
			return [];
		}

        /**
         * @var \WC_Coupon[] $coupons
         */
        $coupons = wc()->cart->get_coupons();

        $data = [
            'settings'       => [
                'orderDiscountEnabled'   => dokan_pro()->vendor_discount->admin_settings->is_order_discount_enabled(),
                'productDiscountEnabled' => dokan_pro()->vendor_discount->admin_settings->is_product_discount_enabled(),
            ],
            'couponSettings' => [],
        ];


        foreach ( $coupons as $coupon ) {
            // Getting the order discount form coupon.
            $orderDiscountApplied = Helper::is_vendor_order_discount_coupon( $coupon );
            $percentage           = 0;
            $min_order            = 0;
            $order_discount_label = '';

            if ( $orderDiscountApplied ) {
                $percentage           = (float) $coupon->get_meta( OrderDiscount::SETTING_ORDER_PERCENTAGE );
                $min_order            = (float) $coupon->get_meta( OrderDiscount::SETTING_MINIMUM_ORDER_AMOUNT );
                $vendor               = dokan()->vendor->get( $coupon->get_meta( 'coupons_vendors_ids', true ) );
                $order_discount_label =  OrderDiscount::get_coupon_label( $percentage, $min_order, $vendor->get_shop_name() );
            }

            // Getting the product quantity discount from coupon.
            $produceDiscountApplied      = Helper::is_vendor_product_quantity_discount_coupon( $coupon );
            $product_discount_percentage = 0;
            $product_discount_quantity   = 0;
            $product_discount_label      = '';

            if ( $produceDiscountApplied ) {
                $product_discount_percentage = (float) $coupon->get_meta( ProductDiscount::LOT_DISCOUNT_AMOUNT );
                $product_discount_quantity   = (float) $coupon->get_meta( ProductDiscount::LOT_DISCOUNT_QUANTITY );
                $product_label               = $coupon->get_meta( ProductDiscount::DISCOUNT_ITEM_TITLE );
                $product_discount_label      = ProductDiscount::get_coupon_label( $product_discount_percentage, $product_label, $product_discount_quantity );
            }

            // Passing the discount values into the frontend.
            $data['couponSettings'][ $coupon->get_code() ] = [
                'code'            => $coupon->get_code(),
                'hasDiscount'     => Helper::is_vendor_discount_coupon( $coupon ),
                'orderDiscount'   => [
                    'hasApplied'                                => $orderDiscountApplied,
                    OrderDiscount::SETTING_ORDER_PERCENTAGE     => $percentage,
                    OrderDiscount::SETTING_MINIMUM_ORDER_AMOUNT => $min_order,
                    'label'                                     => $order_discount_label,
                ],
                'productDiscount' => [
                    'hasApplied'                           => $produceDiscountApplied,
                    'label'                                => $product_discount_label,
                    ProductDiscount::LOT_DISCOUNT_QUANTITY => $product_discount_quantity,
                    ProductDiscount::LOT_DISCOUNT_AMOUNT   => $product_discount_percentage,
                ],
            ];
        }
        return $data;
    }

    /**
     * Get the file modified time as a cache buster if we're in dev mode.
     *
     * @param string $file Local path to the file.
     *
     * @since DOKAN_POR_SINCE
     *
     * @return string The cache buster value to use for the given file.
     */
    protected function get_file_version( $file ) {
        if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
            return filemtime( $file );
        }

        // As above, let's assume that DOKAN_PRO_PLUGIN_VERSION resolves to some versioning number our
        return DOKAN_PRO_PLUGIN_VERSION;
    }
}
