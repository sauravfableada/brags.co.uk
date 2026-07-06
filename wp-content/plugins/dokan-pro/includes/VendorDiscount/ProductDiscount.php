<?php

namespace WeDevs\DokanPro\VendorDiscount;

use WC_Product;
use WeDevs\DokanPro\VendorDiscount\Abstracts\VendorDiscount;

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * Class ProductDiscount
 *
 * @since   3.9.4
 *
 * @package WeDevs\DokanPro\VendorDiscount
 */
class ProductDiscount extends VendorDiscount {

    /**
     * Product ids
     *
     * @since 3.9.4
     *
     * @var mixed
     */
    private $product_id = 0;

    /**
     * Product quantity.
     *
     * @since 3.9.4
     *
     * @var mixed
     */
    private $quantity = 0;

    /**
     * Item product.
     *
     * @since 3.9.4
     *
     * @var false|WC_Product|null
     */
    private $item_product = null;

    /**
     * Product discount type.
     *
     * @since 3.9.4
     *
     * @var string
     */
    const DISCOUNT_TYPE_KEY = 'dokan_product_quantity_discount';

    /**
     * Product discount meta keys.
     *
     * @since 3.9.4
     *
     * @var string
     */
    const LOT_DISCOUNT_QUANTITY = '_lot_discount_quantity';

    /**
     * Product discount meta keys.
     *
     * @since 3.9.4
     *
     * @var string
     */
    const LOT_DISCOUNT_AMOUNT = '_lot_discount_amount';

    /**
     * Product discount meta keys.
     *
     * @since 3.9.4
     *
     * @var string
     */
    const IS_LOT_DISCOUNT = '_is_lot_discount';

    /**
     * Product discount item title.
     *
     * @since 3.15.0
     *
     * @var string
     */
    const DISCOUNT_ITEM_TITLE = 'discount_item_title';

    /**
     * Returns coupon label
     *
     * @since 3.9.4
     */
    public static function get_coupon_label( $percentage, $product_name, $product_count ): string {
        $text = '<span>' . $percentage . '% <br> ' . $product_name . '</span>';

        return __( 'Quantity Discount: ', 'dokan' ) . $text;
    }

    /**
     * Apply coupon for those products that has discount.
     *
     * @since 3.9.4
     *
     * @return bool
     */
    public function apply(): bool {
        $args   = [
            'coupon_type'   => 'percent',
            'amount'        => $this->get_discount_percentage(),
            'description'   => __( 'This coupon is a discount offered by a vendor on their products based on the quantity purchased.', 'dokan' ),
            'discount_type' => self::DISCOUNT_TYPE_KEY,
            'product_ids'   => [ $this->get_product_id() ],
            'vendor_ids'    => dokan_get_vendor_by_product( $this->get_product_id(), true ),
            'meta_data'     => [
                self::LOT_DISCOUNT_QUANTITY => $this->get_discount_quantity(),
                self::LOT_DISCOUNT_AMOUNT   => $this->get_discount_percentage(),
                self::DISCOUNT_ITEM_TITLE       => $this->get_item_product()->get_name(),
            ],
        ];
        $coupon = $this->generate_and_get_coupon( $args );

        return WC()->cart->add_discount( $coupon->get_code() );
    }

    /**
     * Is the discount applicable to product?
     *
     * @since 3.9.4
     *
     * @return bool
     */
    public function is_applicable(): bool {
        // If the current product is not found or product discount is not enabled.
        if ( ! $this->get_item_product() || ! $this->enabled() || $this->get_quantity() < $this->get_discount_quantity() ) {
            return false;
        }

        return true;
    }

    /**
     * Is product discount being enabled?
     *
     * @since 3.9.4
     *
     * @return bool
     */
    public function enabled(): bool {
        // check if admin enabled product discount for vendors
        if ( ! dokan_pro()->vendor_discount->admin_settings->is_product_discount_enabled() ) {
            return false;
        }

        // check if vendor enabled product discount
        return 'yes' === $this->get_item_product()->get_meta( self::IS_LOT_DISCOUNT, true );
    }

    /**
     * Generate and get coupon.
     *
     * @since 3.9.4
     *
     * @return string
     */
    public function get_coupon_code(): string {
        return md5(
            'dokan_product_discount_' .
            $this->get_product_id() . '_' .
            $this->get_cart_item_key() . '_' .
            get_current_user_id()
        );
    }

    /**
     * Get product discount values.
     *
     * @since 3.9.4
     *
     * @return int
     */
    public function get_discount_quantity(): int {
        return absint( $this->get_item_product()->get_meta( self::LOT_DISCOUNT_QUANTITY, true ) );
    }

    /**
     * Get product discount percentage.
     *
     * @since 3.9.4
     *
     * @return float
     */
    public function get_discount_percentage(): float {
        return (float) $this->get_item_product()->get_meta( self::LOT_DISCOUNT_AMOUNT, true );
    }

    /**
     * Get product id.
     *
     * @since 3.9.4
     *
     * @return int|mixed
     */
    public function get_product_id() {
        return $this->product_id;
    }

    /**
     * Get product quantity.
     *
     * @since 3.9.4
     *
     * @return int|mixed
     */
    public function get_quantity() {
        return $this->quantity;
    }

    /**
     * Get item product.
     *
     * @since 3.9.4
     *
     * @return false|WC_Product|null
     */
    public function get_item_product(): WC_Product {
        if ( is_null( $this->item_product ) ) {
            $this->item_product = dokan()->product->get( $this->get_product_id() );

            // If current product is variation, get parent product
            if ( $this->item_product && 'variation' === $this->item_product->get_type() ) {
                $this->item_product = wc_get_product( $this->item_product->get_parent_id() );
            }
        }

        return $this->item_product;
    }

    /**
     * Set item product.
     *
     * @since 3.9.4
     *
     * @param false|WC_Product|null $item_product
     *
     * @return $this
     */
    public function set_item_product( $item_product ): ProductDiscount {
        $this->item_product = $item_product;

        return $this;
    }

    /**
     * Set product quantity.
     *
     * @since 3.9.4
     *
     * @param $quantity
     *
     * @return $this
     */
    public function set_quantity( $quantity ): self {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Set product id.
     *
     * @since 3.9.4
     *
     * @param int|mixed $product_id
     */
    public function set_product_id( $product_id ): self {
        $this->product_id = $product_id;

        return $this;
    }
}
