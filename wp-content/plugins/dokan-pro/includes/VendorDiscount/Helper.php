<?php

namespace WeDevs\DokanPro\VendorDiscount;

use WC_Coupon;
use WC_Order;

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * Class Helper
 *
 * @since   3.9.4
 *
 * @package WeDevs\DokanPro\VendorDiscount
 */
class Helper {

    /**
     * Get discounts by order
     *
     * @since 3.9.4
     *
     * @param WC_Order|int $order
     *
     * @return array
     */
    public static function get_discounts_by_order( $order ) {
        $discounts = [
            'order_discount'    => 0,
            'quantity_discount' => 0,
        ];

        if ( is_numeric( $order ) ) {
            $order = wc_get_order( $order );
        }

        if ( ! $order ) {
            return $discounts;
        }

        $is_child_order = $order->get_parent_id() > 0;
        $parent_order   = $is_child_order ? wc_get_order( $order->get_parent_id() ) : $order;

        foreach ( $parent_order->get_items( 'coupon' ) as $item ) {
            $coupon_item_meta = new OrderItemCouponMeta( $item );

            // check if coupon is a vendor discount coupon
            if ( ! $coupon_item_meta->is_vendor_discount_coupon() ) {
                continue;
            }

            // if coupon vendor id is not the same as order vendor id then skip
            $coupon_vendor_id = $coupon_item_meta->get_coupon_vendor_id();
            $order_vendor_id  = (int) $order->get_meta( '_dokan_vendor_id' );
            if ( $is_child_order && $order_vendor_id !== $coupon_vendor_id ) {
                continue;
            }

            if ( $coupon_item_meta->is_vendor_product_quantity_discount_coupon() ) {
                $discounts['quantity_discount'] += (float) $item->get_discount();
            } elseif ( $coupon_item_meta->is_vendor_order_discount_coupon() ) {
                $discounts['order_discount'] += (float) $item->get_discount();
            }
        }

        return apply_filters( 'dokan_pro_get_discount_by_order', $discounts, $order );
    }

    /**
     * Check if coupon is a vendor discount coupon
     *
     * @since 3.9.4
     *
     * @param WC_Coupon $coupon
     *
     * @return bool
     */
    public static function is_vendor_discount_coupon( WC_Coupon $coupon ) {
        if (
            self::is_vendor_order_discount_coupon( $coupon ) ||
            self::is_vendor_product_quantity_discount_coupon( $coupon )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check if coupon is a vendor order discount coupon
     *
     * @since 3.9.4
     *
     * @param WC_Coupon $coupon
     *
     * @return bool
     */
    public static function is_vendor_order_discount_coupon( WC_Coupon $coupon ) {
        if ( 'yes' === $coupon->get_meta( OrderDiscount::DISCOUNT_TYPE_KEY, true ) ) {
            return true;
        }

        return false;
    }

    /**
     * Check if coupon is a vendor product quantity discount coupon
     *
     * @since 3.9.4
     *
     * @param WC_Coupon $coupon
     *
     * @return bool
     */
    public static function is_vendor_product_quantity_discount_coupon( WC_Coupon $coupon ) {
        if ( 'yes' === $coupon->get_meta( ProductDiscount::DISCOUNT_TYPE_KEY, true ) ) {
            return true;
        }

        return false;
    }
}
