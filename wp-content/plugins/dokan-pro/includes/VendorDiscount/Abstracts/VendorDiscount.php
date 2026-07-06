<?php

namespace WeDevs\DokanPro\VendorDiscount\Abstracts;

use WC_Coupon;
use WeDevs\DokanPro\VendorDiscount\ProductDiscount;

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * Abstract Class VendorDiscount
 *
 * @since   3.9.4
 *
 * @package WeDevs\DokanPro\VendorDiscount\Abstracts
 */
abstract class VendorDiscount {

    /**
     * Cart item key.
     *
     * @since 3.9.4
     *
     * @var mixed|string
     */
    protected $cart_item_key = '';

    /**
     * Get coupon code
     *
     * @since 3.9.4
     *
     * @return string
     */
    abstract public function get_coupon_code(): string;

    /**
     * Check if coupon is capable to apply.
     *
     * @since 3.9.4
     *
     * @return bool
     */
    abstract public function is_applicable(): bool;

    /**
     * Is product or order discount being enabled
     *
     * @since 3.9.4
     *
     * @return bool
     */
    abstract public function enabled(): bool;

    /**
     * Apply coupon.
     *
     * @since 3.9.4
     *
     * @return bool
     */
    abstract public function apply(): bool;

    /**
     * Get a cart item key.
     *
     * @since 3.9.4
     *
     * @return mixed|string
     */
    public function get_cart_item_key() {
        return $this->cart_item_key;
    }

    /**
     * Sets a cart item key.
     *
     * @since 3.9.4
     *
     * @param $cart_item_key
     *
     * @return $this
     */
    public function set_cart_item_key( $cart_item_key ): self {
        $this->cart_item_key = $cart_item_key;

        return $this;
    }

    /**
     * If the generated coupon is already applied to the cart.
     *
     * @since 3.9.4
     *
     * @return bool
     */
    public function is_already_applied(): bool {
        return WC()->cart->has_discount( $this->get_coupon_code() );
    }

    /**
     * Remove coupon from cart.
     *
     * @since 3.9.4
     *
     * @return static
     */
    public function remove(): self {
        WC()->cart->remove_coupon( $this->get_coupon_code() );

        return $this;
    }

    /**
     * Delete coupon.
     *
     * @since 3.9.4
     *
     * @return static
     */
    public function delete_coupon(): self {
        $coupon = new WC_Coupon( $this->get_coupon_code() );
        $coupon->delete( true );

        return $this;
    }

    /**
     * Generate and get coupon.
     *
     * @since 3.9.4
     *
     * @param array $args
     *
     * @return WC_Coupon
     */
    public function generate_and_get_coupon( array $args = [] ): WC_Coupon {
        $defaults = [
            'coupon_type'   => 'percent',
            'amount'        => 0,
            'description'   => '',
            'discount_type' => ProductDiscount::DISCOUNT_TYPE_KEY,
            'product_ids'   => [],
            'vendor_ids'    => '',
            'meta_data'     => [],
        ];
        $args     = wp_parse_args( $args, $defaults );

        // check if coupon already exists
        if ( wc_get_coupon_id_by_code( $this->get_coupon_code() ) ) {
            return new WC_Coupon( $this->get_coupon_code() );
        }

        $coupon = new WC_Coupon();
        $coupon->set_code( $this->get_coupon_code() );
        $coupon->set_discount_type( $args['coupon_type'] );
        $coupon->set_amount( $args['amount'] );
        $coupon->set_description( $args['description'] );
        $coupon->add_meta_data( 'coupon_commissions_type', 'from_vendor', true );
        $coupon->add_meta_data( 'admin_coupons_show_on_stores', 'no', true );
        $coupon->add_meta_data( 'coupons_vendors_ids', $args['vendor_ids'], true );
        $coupon->add_meta_data( 'admin_coupons_send_notify_to_vendors', 'no', true );
        $coupon->add_meta_data( 'admin_coupons_enabled_for_vendor', 'yes', true );

        $coupon->set_product_ids( $args['product_ids'] );
        $coupon->set_usage_limit( 0 );
        $coupon->set_usage_limit_per_user( 0 );

        foreach ( $args['meta_data'] as $key => $value ) {
            $coupon->add_meta_data( $key, $value, true );
        }

        $coupon->save();

        return $coupon;
    }
}
