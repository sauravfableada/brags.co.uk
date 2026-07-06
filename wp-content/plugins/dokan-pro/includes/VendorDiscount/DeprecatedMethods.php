<?php

namespace WeDevs\DokanPro\VendorDiscount;

use WC_Order;
use WC_Order_Item;

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * After rewriting the discount system, we have to keep the old discount system to keep the backward compatibility.
 *
 * @since 3.9.4
 */
class DeprecatedMethods {

    /**
     * Class constructor
     *
     * @since 3.9.4
     *
     * @return void
     */
    public function __construct() {
        // Displays old discount on customer dashboard.
        add_filter( 'woocommerce_get_order_item_totals', [ $this, 'display_order_discounts' ], 10, 2 );
        // Displays old discount on admin and vendor dashboard.
        add_action( 'woocommerce_admin_order_totals_after_tax', [ $this, 'display_order_discounts_on_wc_admin_order' ] );
        // Calculates commissions for order discount for old discounts.
        add_filter( 'dokan_earning_by_order_item_price', [ $this, 'update_item_price_for_discount' ], 15, 3 );
    }

    /**
     * Display order discounts on orders
     *
     * @since 2.9.13
     * @since 3.9.4 Moved here
     *
     * @param array    $table_rows
     * @param WC_Order $order
     *
     * @return array
     */
    public function display_order_discounts( $table_rows, $order ) {
        $discounts = $this->dokan_get_discount_by_order( $order->get_id() );

        if ( ! empty( $discounts['quantity_discount'] ) ) {
            $table_rows = dokan_array_after(
                $table_rows, 'cart_subtotal',
                [
                    'quantity_discount' => [
                        'label' => __( 'Quantity Discount:', 'dokan' ),
                        'value' => wc_price( $discounts['quantity_discount'] ),
                    ],
                ]
            );
        }

        if ( ! empty( $discounts['order_discount'] ) ) {
            $table_rows = dokan_array_after(
                $table_rows, 'cart_subtotal',
                [
                    'order_discount' => [
                        'label' => __( 'Order Discount:', 'dokan' ),
                        'value' => wc_price( $discounts['order_discount'] ),
                    ],
                ]
            );
        }

        return $table_rows;
    }

    /**
     * Display order discounts on wc admin order table
     *
     * @since 2.9.13
     * @since 3.9.4 Moved here
     *
     * @param int $order_id
     *
     * @return void
     */
    public function display_order_discounts_on_wc_admin_order( $order_id ) {
        $order     = wc_get_order( $order_id );
        $discounts = $this->dokan_get_discount_by_order( $order->get_id() );

        if ( empty( $discounts['order_discount'] ) && empty( $discounts['quantity_discount'] ) ) {
            return;
        }

        $html = '';

        if ( ! empty( $discounts['order_discount'] ) ) {
            $html = '<tr>';
            $html .= '<td class="label dokan-order-discount">' . __( 'Order Discount:', 'dokan' ) . '</td>';
            $html .= '<td class="dokan-hide"></td>';
            $html .= '<td class="dokan-order-discount">' . wc_price( $discounts['order_discount'] ) . '</td>';
            $html .= '</tr>';
        }

        if ( ! empty( $discounts['quantity_discount'] ) ) {
            $html .= '<tr>';
            $html .= '<td class="label dokan-quantity-discount">' . __( 'Quantity Discount:', 'dokan' ) . '</td>';
            $html .= '<td class="dokan-hide"></td>';
            $html .= '<td class="dokan-quantity-discount">' . wc_price( $discounts['quantity_discount'] ) . '</td>';
            $html .= '</tr>';
        }

        echo $html;
    }

    /**
     * Get discount by order
     *
     * @since 2.9.13
     * @since 3.9.4 Moved here
     *
     * @param int $order_id
     *
     * @return array
     */
    public function dokan_get_discount_by_order( $order_id ) {
        $discounts = [
            'order_discount'    => 0,
            'quantity_discount' => 0,
        ];

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return $discounts;
        }

        if ( ! $order->get_meta( 'has_sub_order' ) ) {
            $order_discount    = $order->get_meta( 'dokan_order_discount' );
            $quantity_discount = $order->get_meta( 'dokan_quantity_discount' );
        } else {
            $sub_orders        = dokan_get_suborder_ids_by( $order->get_id() );
            $order_discount    = 0;
            $quantity_discount = 0;

            foreach ( $sub_orders as $sub_order ) {
                $wc_sub_order = wc_get_order( $sub_order );
                if ( ! $wc_sub_order ) {
                    continue;
                }

                $order_discount    += (float) $wc_sub_order->get_meta( 'dokan_order_discount' );
                $quantity_discount += (float) $wc_sub_order->get_meta( 'dokan_quantity_discount' );
            }
        }

        $discounts['order_discount']    = $order_discount ? $order_discount : 0;
        $discounts['quantity_discount'] = $quantity_discount ? $quantity_discount : 0;

        return apply_filters( 'dokan_get_discount_by_order', $discounts, $order );
    }

    /**
     * Update item total price if product-based discount is applied
     *
     * @since 3.4.0
     * @since 3.9.4 moved here
     *
     * @param float         $get_total
     * @param WC_Order_Item $item
     * @param WC_Order      $order
     *
     * @return float $get_total
     */
    public function update_item_price_for_discount( $get_total, $item, $order ) {
        if ( empty( $item ) ) {
            return $get_total;
        }

        // return if new coupon discount meta is available
        if ( 'yes' === $order->get_meta( ProductDiscount::DISCOUNT_TYPE_KEY ) || 'yes' === $order->get_meta( OrderDiscount::DISCOUNT_TYPE_KEY ) ) {
            return $get_total;
        }

        // return if discount meta is not available
        if ( empty( $order->get_meta( 'dokan_quantity_discount' ) ) && empty( $order->get_meta( 'dokan_order_discount' ) ) ) {
            return $get_total;
        }

        $product_discount = 0;
        $order_discount   = 0;

        if ( ! empty( $order->get_meta( 'dokan_quantity_discount' ) ) ) {
            $product_discount = $order->get_meta( 'dokan_quantity_discount' );
        }

        if ( ! empty( $order->get_meta( 'dokan_order_discount' ) ) ) {
            $order_discount = $order->get_meta( 'dokan_order_discount' );
        }

        return $get_total - $product_discount - $order_discount;
    }

    /**
     * Get product quantity discount
     *
     * @since 3.9.4
     *
     * @param WC_Order $order
     *
     * @return float
     */
    public function get_product_quantity_discount( WC_Order $order ): float {
        // check discount exists on order meta
        $quantity_discount = $order->get_meta( 'dokan_quantity_discount' );
        if ( $quantity_discount ) {
            return floatval( $quantity_discount );
        }

        return 0;
    }

    /**
     * Get order discount
     *
     * @since 3.9.4
     *
     * @param WC_Order $order
     *
     * @return float
     */
    public function get_order_discount( WC_Order $order ): float {
        // check discount exists on order meta
        $order_discount = $order->get_meta( 'dokan_order_discount' );
        if ( $order_discount ) {
            return floatval( $order_discount );
        }

        return 0;
    }
}
