<?php

namespace WeDevs\DokanPro\VendorDiscount;

use WC_Meta_Data;
use WC_Order_Item_Coupon;

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * Class OrderItemCouponMeta
 *
 * @since   3.9.4
 *
 * @package WeDevs\DokanPro\VendorDiscount
 */
class OrderItemCouponMeta extends WC_Order_Item_Coupon {

    /**
     * Coupon id.
     *
     * @since 3.9.4
     *
     * @var int
     */
    protected $coupon_id = 0;

    /**
     * Coupon code.
     *
     * @since 3.9.4
     *
     * @var string
     */
    protected $coupon_code = '';

    /**
     * Coupon meta data's.
     *
     * @since 3.9.4
     *
     * @var array $coupon_meta_datas
     */
    private $coupon_meta_datas = [];

    /**
     * OrderItemCouponMeta constructor.
     *
     * @since 3.9.4
     *
     * @param WC_Order_Item_Coupon $item
     */
    public function __construct( WC_Order_Item_Coupon $item ) {
        parent::__construct( $item );

        $this->extract_coupon_meta_data();
    }

    /**
     * Get coupon id.
     *
     * @since 3.9.4
     *
     * @return int
     */
    public function get_coupon_id() {
        return $this->coupon_id;
    }

    /**
     * Get coupon code.
     *
     * @since 3.9.4
     *
     * @return string
     */
    public function get_coupon_code() {
        return $this->coupon_code;
    }

    /**
     * Extract coupon meta data.
     *
     * We need to extract coupon meta data because all coupon data including coupon meta data is stored in order item meta data.
     *
     * @since 3.9.4
     *
     * @return void
     */
    private function extract_coupon_meta_data() {
        $coupon_metas = [];

        foreach ( $this->get_meta_data() as $item ) {
            /**
             * @var WC_Meta_Data $item
             * @var array        $coupon_data  {
             * @type string      $id
             * @type string      $key
             * @type string      $value
             *                                 }
             */
            $coupon_data = $item->get_data();
            if ( 'coupon_data' === $coupon_data['key'] ) {
                $coupon_metas      = $coupon_data['value']['meta_data'] ?? [];
                $this->coupon_id   = $coupon_data['value']['id'];
                $this->coupon_code = $coupon_data['value']['code'];
                break;
            }
        }

        foreach ( $coupon_metas as $meta ) {
            /**
             * @var WC_Meta_Data $meta
             */
            $this->coupon_meta_datas[ $meta->get_data()['key'] ] = $meta->get_data()['value'];
        }
    }

    /**
     * Get coupon meta data's.
     *
     * @since 3.9.4
     *
     * @return array
     */
    public function get_coupon_meta_datas(): array {
        return $this->coupon_meta_datas;
    }

    /**
     * Get meta value.
     *
     * @since 3.9.4
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get_coupon_meta( string $key, $default = false ) {
        return $this->coupon_meta_datas[ $key ] ?? $default;
    }

    /**
     * Check if coupon is vendor discount coupon.
     *
     * @since 3.9.4
     *
     * @return bool
     */
    public function is_vendor_discount_coupon() {
        return $this->is_vendor_order_discount_coupon() || $this->is_vendor_product_quantity_discount_coupon();
    }

    /**
     * Check if coupon is vendor order discount coupon.
     *
     * @since 3.9.4
     *
     * @return bool
     */
    public function is_vendor_order_discount_coupon() {
        return 'yes' === $this->get_coupon_meta( OrderDiscount::DISCOUNT_TYPE_KEY );
    }

    /**
     * Check if coupon is vendor product discount coupon.
     *
     * @since 3.9.4
     *
     * @return bool
     */
    public function is_vendor_product_quantity_discount_coupon() {
        return 'yes' === $this->get_coupon_meta( ProductDiscount::DISCOUNT_TYPE_KEY );
    }

    /**
     * Get coupon vendor id.
     *
     * @since 3.9.4
     *
     * @return int
     */
    public function get_coupon_vendor_id() {
        // for vendor discount coupon, only a single vendor id is stored in the meta.
        return (int) $this->get_coupon_meta( 'coupons_vendors_ids', true );
    }
}
