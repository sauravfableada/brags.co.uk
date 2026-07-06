<?php

namespace WeDevs\DokanPro\VendorDiscount\Frontend;

use WC_Order_Item_Coupon;
use WeDevs\DokanPro\VendorDiscount\OrderDiscount;
use WeDevs\DokanPro\VendorDiscount\OrderItemCouponMeta;
use WeDevs\DokanPro\VendorDiscount\ProductDiscount;

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * Class Dashboard
 *
 * @since   3.9.4
 *
 * @package WeDevs\DokanPro\VendorDiscount\Frontend
 */
class Dashboard {

    /**
     * Class constructor
     *
     * @since 3.9.4
     *
     * @return void
     */
    public function __construct() {
        add_action( 'dokan_product_edit_after_inventory_variants', [ $this, 'load_discount_content' ], 25, 2 );
        add_action( 'dokan_product_updated', [ $this, 'save_discount_data' ], 12, 2 );
        add_action( 'dokan_new_product_added', [ $this, 'save_discount_data' ], 12, 2 );
        add_filter( 'dokan_pro_dashboard_order_item_coupon_url', [ $this, 'order_item_coupon_url' ], 10, 2 );
    }

    /**
     * Render product lot discount options
     *
     * @since 2.6
     * @since 3.9.4 moved this method from load_lot_discount_content() method under includes/Products.php
     *
     * @param \WP_Post $post
     * @param int      $post_id
     *
     * @return void
     */
    public function load_discount_content( $post, $post_id ) {
        // return if admin didn't enabled product discount
        if ( ! dokan_pro()->vendor_discount->admin_settings->is_product_discount_enabled() ) {
            return;
        }

        $product = wc_get_product( $post_id );
        if ( ! $product ) {
            return;
        }

        $lot_discount_enabled      = $product->get_meta( ProductDiscount::IS_LOT_DISCOUNT );
        $product_discount_quantity = $product->get_meta( ProductDiscount::LOT_DISCOUNT_QUANTITY );
        $product_discount_amount   = $product->get_meta( ProductDiscount::LOT_DISCOUNT_AMOUNT );

        dokan_get_template_part(
            'products/product-vendor-discount',
            '',
            [
                'pro'                       => true,
                'product'                   => $product,
                'lot_discount_enabled'      => $lot_discount_enabled,
                'product_discount_quantity' => $product_discount_quantity,
                'product_discount_amount'   => $product_discount_amount,
            ]
        );
    }

    /**
     * Save discount data
     *
     * @since 2.6
     * @since 3.9.4 extracted this method from save_pro_product_data() method under includes/Products.php
     *
     * @param int $post_id
     * @param array|\WP_REST_Request $post_data
     *
     * @return void
     */
    public function save_discount_data( $post_id, $post_data ) {
        // return if admin didn't enabled product discount
        if ( ! dokan_pro()->vendor_discount->admin_settings->is_product_discount_enabled() ) {
            return;
        }

        $product = wc_get_product( $post_id );
        if ( ! $product ) {
            return;
        }

        // Save lot discount options
        $is_lot_discount   = isset( $post_data[ ProductDiscount::IS_LOT_DISCOUNT ] ) ? sanitize_text_field( $post_data[ ProductDiscount::IS_LOT_DISCOUNT ] ) : 'no';
        $discount_quantity = isset( $post_data[ ProductDiscount::LOT_DISCOUNT_QUANTITY ] ) ? absint( $post_data[ ProductDiscount::LOT_DISCOUNT_QUANTITY ] ) : 0;
        $discount_amount   = isset( $post_data[ ProductDiscount::LOT_DISCOUNT_AMOUNT ] ) ? wc_format_decimal( $post_data[ ProductDiscount::LOT_DISCOUNT_AMOUNT ], '' ) : 0;

        $product->add_meta_data( ProductDiscount::IS_LOT_DISCOUNT, $is_lot_discount, true );
        $product->add_meta_data( ProductDiscount::LOT_DISCOUNT_QUANTITY, absint( $discount_quantity ), true );
        $product->add_meta_data( ProductDiscount::LOT_DISCOUNT_AMOUNT, wc_format_decimal( $discount_amount, '' ), true );
        $product->save();
    }

    /**
     * Replace coupon name with the discount label.
     *
     * @since 3.9.4
     *
     * @param string               $item_link
     * @param WC_Order_Item_Coupon $item
     *
     * @return string
     */
    public function order_item_coupon_url( $item_link, $item ): string {
        $coupon_item_meta = new OrderItemCouponMeta( $item );
        if ( $coupon_item_meta->is_vendor_discount_coupon() ) {
            $item_link = '#';
        }

        return $item_link;
    }
}
