<?php

namespace WeDevs\DokanPro\VendorDiscount\Frontend;

use WC_Coupon;
use WeDevs\DokanPro\VendorDiscount\OrderDiscount;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * Class StoreSettings
 *
 * @since   3.9.4
 *
 * @package WeDevs\DokanPro\VendorDiscount\Frontend
 */
class StoreSettings {

    /**
     * Class constructor
     *
     * @since 3.9.4
     *
     * @return void
     */
    public function __construct() {
        add_action( 'dokan_settings_form_bottom', [ $this, 'add_order_discount' ], 10, 2 );

        // save settings data
        add_filter( 'dokan_store_profile_settings_args', [ $this, 'save_order_discount_settings' ], 10, 1 );

        // update cart discount value after admin updates order discount settings
        add_action( 'dokan_store_profile_saved', [ $this, 'update_order_discount_coupon_value' ], 99, 1 );
    }

    /**
     * Render discount options
     *
     * @since 2.6
     * @since 3.9.4 moved this method from includes/Settings.php
     *
     * @param \WP_User $current_user
     * @param array    $profile_info
     *
     * @return void
     **/
    public function add_order_discount( $current_user, $profile_info ) {
        // return from here if admin didn't enable order discount
        if ( ! dokan_pro()->vendor_discount->admin_settings->is_order_discount_enabled() ) {
            return;
        }

        $is_enable_order_discount     = $profile_info[ OrderDiscount::SHOW_MIN_ORDER_DISCOUNT ] ?? 'no';
        $setting_minimum_order_amount = $profile_info[ OrderDiscount::SETTING_MINIMUM_ORDER_AMOUNT ] ?? '';
        $setting_order_percentage     = $profile_info[ OrderDiscount::SETTING_ORDER_PERCENTAGE ] ?? '';

        dokan_get_template_part(
            'settings/vendor-order-discount', '', [
                'pro'                          => true,
                'is_enable_order_discount'     => $is_enable_order_discount,
                'setting_minimum_order_amount' => $setting_minimum_order_amount,
                'setting_order_percentage'     => $setting_order_percentage,
            ]
        );
    }

    /**
     * Save discount settings data
     *
     * @since 2.6
     * @since 3.9.4 moved this file from includes/Settings.php
     *
     * @param array $dokan_settings
     * @param int   $store_id
     *
     * @return array
     **/
    public function save_order_discount_settings( $dokan_settings ) {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'dokan_store_settings_nonce' ) ) {
            return $dokan_settings;
        }

        $enable_order_discount = isset( $_POST[ OrderDiscount::SETTING_SHOW_MIN_ORDER_DISCOUNT ] ) ? wc_clean( wp_unslash( $_POST[ OrderDiscount::SETTING_SHOW_MIN_ORDER_DISCOUNT ] ) ) : 'no';
        $minimum_order_amount  = isset( $_POST[ OrderDiscount::SETTING_MINIMUM_ORDER_AMOUNT ] ) ? wc_clean( wp_unslash( $_POST[ OrderDiscount::SETTING_MINIMUM_ORDER_AMOUNT ] ) ) : '';
        $order_percentage      = isset( $_POST[ OrderDiscount::SETTING_ORDER_PERCENTAGE ] ) ? wc_clean( wp_unslash( $_POST[ OrderDiscount::SETTING_ORDER_PERCENTAGE ] ) ) : '';

        // Set discount data in seller profile
        $dokan_settings[ OrderDiscount::SHOW_MIN_ORDER_DISCOUNT ] = $enable_order_discount;

        if ( 'yes' === $enable_order_discount ) {
            $dokan_settings[ OrderDiscount::SETTING_MINIMUM_ORDER_AMOUNT ] = wc_format_decimal( $minimum_order_amount, '' );
            $dokan_settings[ OrderDiscount::SETTING_ORDER_PERCENTAGE ]     = wc_format_decimal( $order_percentage, '' );
        }

        return $dokan_settings;
    }

    /**
     * Update order discount coupon value when order discount value is updated in vendor settings.
     *
     * @since 3.9.4
     *
     * @param int $store_id
     *
     * @return void
     */
    public function update_order_discount_coupon_value( int $store_id ) {
        // return from here if admin didn't enable order discount
        if ( ! dokan_pro()->vendor_discount->admin_settings->is_order_discount_enabled() ) {
            return;
        }

        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'dokan_store_settings_nonce' ) ) {
            return;
        }

        if (
            ! isset( $_POST[ OrderDiscount::SETTING_SHOW_MIN_ORDER_DISCOUNT ] ) ||
            isset( $_POST[ OrderDiscount::SETTING_SHOW_MIN_ORDER_DISCOUNT ] ) && 'no' === $_POST[ OrderDiscount::SETTING_SHOW_MIN_ORDER_DISCOUNT ] ||
            ! isset( $_POST[ OrderDiscount::SETTING_ORDER_PERCENTAGE ] ) ||
            ! isset( $_POST[ OrderDiscount::SETTING_MINIMUM_ORDER_AMOUNT ] )
        ) {
            return;
        }

        $discount = new OrderDiscount();

        $query = new WP_Query(
            [
                'fields'         => 'ids',
                'post_type'      => 'shop_coupon',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_query'     => [ // phpcs:ignore
                    'relation' => 'AND',
                    [
                        'key'     => OrderDiscount::DISCOUNT_TYPE_KEY,
                        'value'   => 'yes',
                        'compare' => '=',
                    ],
                    [
                        'key'     => 'coupons_vendors_ids',
                        'value'   => $store_id,
                        'compare' => '=',
                    ],
                ],
            ]
        );

        $order_amount = isset( $_POST[ $discount::SETTING_MINIMUM_ORDER_AMOUNT ] ) ? wc_clean( wp_unslash( $_POST[ $discount::SETTING_MINIMUM_ORDER_AMOUNT ] ) ) : 0;
        $percentage   = isset( $_POST[ $discount::SETTING_ORDER_PERCENTAGE ] ) ? wc_clean( wp_unslash( $_POST[ $discount::SETTING_ORDER_PERCENTAGE ] ) ) : 0;

        foreach ( $query->get_posts() as $coupon_id ) {
            $coupon = new WC_Coupon( $coupon_id );

            $coupon->set_amount( $percentage );
            $coupon->add_meta_data( $discount::SETTING_ORDER_PERCENTAGE, wc_format_decimal( $percentage, '' ), true );
            $coupon->add_meta_data( $discount::SETTING_MINIMUM_ORDER_AMOUNT, wc_format_decimal( $order_amount, '' ), true );
            $coupon->save();
        }
    }
}
