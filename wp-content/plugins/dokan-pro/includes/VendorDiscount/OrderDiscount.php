<?php

namespace WeDevs\DokanPro\VendorDiscount;

use WeDevs\Dokan\Vendor\Vendor;
use WeDevs\DokanPro\VendorDiscount\Abstracts\VendorDiscount;

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * Class OrderDiscount
 *
 * @since   3.9.4
 *
 * @package WeDevs\DokanPro\VendorDiscount
 */
class OrderDiscount extends VendorDiscount {

    /**
     * Dokan vendor object.
     *
     * @since 3.9.4
     *
     * @var Vendor
     */
    private $vendor;

    /**
     * Is the minimum order discount being enabled or not?
     *
     * @since 3.9.4
     *
     * @var string
     */
    const SHOW_MIN_ORDER_DISCOUNT = 'show_min_order_discount';

    /**
     * Enable `minimum order discount` setting key.
     *
     * @since 3.9.4
     *
     * @var string
     */
    const SETTING_SHOW_MIN_ORDER_DISCOUNT = 'setting_show_minimum_order_discount_option';

    /**
     * Minimum order amount setting key.
     *
     * @since 3.9.4
     *
     * @var string
     */
    const SETTING_MINIMUM_ORDER_AMOUNT = 'setting_minimum_order_amount';

    /**
     * Order percentage setting key.
     *
     * @since 3.9.4
     *
     * @var string
     */
    const SETTING_ORDER_PERCENTAGE = 'setting_order_percentage';

    /**
     * Order discount-type key.
     *
     * @since 3.9.4
     *
     * @var string
     */
    const DISCOUNT_TYPE_KEY = 'dokan_order_total_discount';

    /**
     * Total order amount.
     *
     * @since 3.9.4
     *
     * @var mixed
     */
    private $total_amount;

    /**
     * Product ids.
     *
     * @since 3.9.4
     *
     * @var string|array|mixed
     */
    private $product_ids;

    /**
     * Returns coupon label
     *
     * @since 3.9.4
     *
     * @param        $percentage
     * @param        $min_order
     * @param string $store_name
     *
     * @return string
     */
    public static function get_coupon_label( $percentage, $min_order, string $store_name = '' ): string {
        $currency = get_woocommerce_currency_symbol();

        // translators: %1$d: percentage, %2$s: vendor name, %3$s: total orders
        $text = '<span>' . sprintf( __( '%1$d%% <br>for %2$s <br> on minimum order amount of %3$s', 'dokan' ), $percentage, $store_name, $currency . $min_order ) . '</span>';

        return __( 'Order Discount: ', 'dokan' ) . $text;
    }

    /**
     * Returns order ids.
     *
     * @since 3.9.4
     *
     * @return string|array|mixed
     */
    public function get_product_ids() {
        return $this->product_ids;
    }

    /**
     * Returns total amount.
     *
     * @since 3.9.4
     *
     * @return float
     */
    public function get_total_amount(): float {
        return (float) $this->total_amount;
    }

    /**
     * Sets vendor.
     *
     * @since 3.9.4
     *
     * @param Vendor $vendor
     *
     * @return $this
     */
    public function set_vendor( Vendor $vendor ): self {
        $this->vendor = $vendor;

        return $this;
    }

    /**
     * Sets total amount.
     *
     * @since 3.9.4
     *
     * @param float $total_amount
     *
     * @return $this
     */
    public function set_total_amount( float $total_amount ): self {
        $this->total_amount = $total_amount;

        return $this;
    }

    /**
     * Get vendor.
     *
     * @since 3.9.4
     *
     * @return Vendor
     */
    public function get_vendor(): Vendor {
        return $this->vendor;
    }

    /**
     * Get minimum order amount.
     *
     * @since 3.9.4
     *
     * @return float
     */
    public function get_minimum_order_amount(): float {
        $shop_info = $this->get_vendor()->get_shop_info();

        return isset( $shop_info[ self::SETTING_MINIMUM_ORDER_AMOUNT ] ) ? (float) $shop_info[ self::SETTING_MINIMUM_ORDER_AMOUNT ] : 0;
    }

    /**
     * Get discount percentage.
     *
     * @since 3.9.4
     *
     * @return float
     */
    public function get_discount_percentage(): float {
        $shop_info = $this->get_vendor()->get_shop_info();

        return isset( $shop_info[ self::SETTING_ORDER_PERCENTAGE ] ) ? (float) $shop_info[ self::SETTING_ORDER_PERCENTAGE ] : 0;
    }

    /**
     * The minimum order discount is enabled or not.
     *
     * @since 3.9.4
     *
     * @return bool
     */
    public function enabled(): bool {
        // check if admin enabled order discount for vendors
        if ( ! dokan_pro()->vendor_discount->admin_settings->is_order_discount_enabled() ) {
            return false;
        }

        // check if vendor enabled order discount
        $shop_info = $this->get_vendor()->get_shop_info();

        return isset( $shop_info[ self::SHOW_MIN_ORDER_DISCOUNT ] ) && 'yes' === $shop_info[ self::SHOW_MIN_ORDER_DISCOUNT ];
    }

    /**
     * Is the discount applicable or not?
     *
     * @since 3.9.4
     *
     * @return bool
     */
    public function is_applicable(): bool {
        if ( $this->enabled() && $this->get_minimum_order_amount() <= $this->get_total_amount() && $this->get_discount_percentage() > 0 ) {
            return true;
        }

        return false;
    }

    /**
     * Apply discount.
     *
     * @since 3.9.4
     *
     * @return bool
     */
    public function apply(): bool {
        $args   = [
            'coupon_type'   => 'percent',
            'amount'        => $this->get_discount_percentage(),
            'description'   => __( 'This coupon is a discount offered by a vendor based on order totals.', 'dokan' ),
            'discount_type' => self::DISCOUNT_TYPE_KEY,
            'product_ids'   => $this->get_product_ids(),
            'vendor_ids'    => $this->get_vendor()->get_id(),
            'meta_data'     => [
                self::SETTING_MINIMUM_ORDER_AMOUNT => $this->get_minimum_order_amount(),
                self::SETTING_ORDER_PERCENTAGE     => $this->get_discount_percentage(),
            ],
        ];
        $coupon = $this->generate_and_get_coupon( $args );

        return WC()->cart->add_discount( $coupon->get_code() );
    }

    /**
     * Get coupon code.
     *
     * @since 3.9.4
     *
     * @return string
     */
    public function get_coupon_code(): string {
        return md5(
            'dokan_order_discount_' .
            $this->get_vendor()->get_id() . '_' .
            $this->get_cart_item_key() . '_' .
            get_current_user_id()
        );
    }

    /**
     * Sets product ids.
     *
     * @since 3.9.4
     *
     * @param int|array|mixed $product_ids
     *
     * @return $this
     */
    public function set_product_ids( $product_ids ): self {
        $this->product_ids = $product_ids;

        return $this;
    }
}
