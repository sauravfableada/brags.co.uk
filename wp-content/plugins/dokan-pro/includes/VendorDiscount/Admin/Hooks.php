<?php

namespace WeDevs\DokanPro\VendorDiscount\Admin;

use WeDevs\DokanPro\VendorDiscount\Helper;
use WeDevs\DokanPro\VendorDiscount\OrderDiscount;
use WeDevs\DokanPro\VendorDiscount\ProductDiscount;
use WP_Query;

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
class Hooks {
    /**
     * Class constructor
     *
     * @since 3.9.4
     *
     * @return void
     */
    public function __construct() {
        add_action( 'load-edit.php', [ $this, 'remove_vendor_discount_coupons' ] );
        add_action( 'dokan_daily_midnight_cron', [ $this, 'delete_vendor_discount_coupons' ] );
        add_action( 'woocommerce_admin_order_totals_after_discount', [ $this, 'display_order_discounts' ], 10, 1 );
    }

    /**
     * Remove vendor discount coupons from admin coupon list table
     *
     * @since 3.9.4
     *
     * @return void
     */
    public function remove_vendor_discount_coupons() {
        global $typenow;

        if ( 'shop_coupon' !== $typenow ) {
            return;
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore
            return;
        }

        add_action(
            'pre_get_posts',
            function ( $query ) {
                if ( ! is_admin() || ! $query->is_main_query() ) {
                    return;
                }

                $meta_query = $query->get( 'meta_query' );
                $meta_query = ! empty( $meta_query ) ? $meta_query : [];

                $meta_query[][] = [
                    'relation' => 'AND',
                    [
                        'key'     => OrderDiscount::DISCOUNT_TYPE_KEY,
                        'compare' => 'NOT EXISTS',
                        'value'   => '',
                    ],
                    [
                        'key'     => ProductDiscount::DISCOUNT_TYPE_KEY,
                        'compare' => 'NOT EXISTS',
                        'value'   => '',
                    ],
                ];

                $query->set( 'meta_query', $meta_query );
            }
        );

        add_filter(
            'wp_count_posts',
            function ( $counts ) {
                $coupons         = $this->get_vendor_discount_coupons();
                $total           = count( $coupons );
                $counts->publish = $counts->publish - $total;

                return $counts;
            }
        );
    }

    /**
     * Delete vendor discount coupons
     *
     * @since 3.9.4
     *
     * @return void
     */
    public function delete_vendor_discount_coupons() {
        $coupons = $this->get_vendor_discount_coupons();

        foreach ( $coupons as $coupon_id ) {
            $coupon = new \WC_Coupon( $coupon_id );
            $coupon->delete( true );
        }
    }

    /**
     * Display vendor discounts data
     *
     * @since 3.9.4
     *
     * @param int $order_id
     *
     * @return void
     */
    public function display_order_discounts( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }
        $discounts = Helper::get_discounts_by_order( $order );

        if ( ! empty( $discounts['quantity_discount'] ) ) {
            ?>
            <tr>
                <td class=""><?php esc_html_e( 'Total Quantity Discount :', 'dokan' ); ?></td>
                <td></td>
                <td class="total">
                    <?php echo wc_price( $discounts['quantity_discount'], [ 'currency' => $order->get_currency() ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </td>
            </tr>
            <?php
        }

        if ( ! empty( $discounts['order_discount'] ) ) {
			?>
            <tr>
                <td class=""><?php esc_html_e( 'Total Order Discount :', 'dokan' ); ?></td>
                <td></td>
                <td class="total">
                    <?php echo wc_price( $discounts['order_discount'], [ 'currency' => $order->get_currency() ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </td>
            </tr>
            <?php
        }
    }

    /**
     * Get vendor discount coupons
     *
     * @since 3.9.4
     *
     * @return int[]
     */
    private function get_vendor_discount_coupons(): array {
        $query = new WP_Query(
            [
                'fields'         => 'ids',
                'post_type'      => 'shop_coupon',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_query'     => [ // phpcs:ignore
                    'relation' => 'OR',
                    [
                        'key'     => OrderDiscount::DISCOUNT_TYPE_KEY,
                        'compare' => '=',
                        'value'   => 'yes',
                    ],
                    [
                        'key'     => ProductDiscount::DISCOUNT_TYPE_KEY,
                        'compare' => '=',
                        'value'   => 'yes',
                    ],
                ],
            ]
        );

        return $query->get_posts();
    }
}
