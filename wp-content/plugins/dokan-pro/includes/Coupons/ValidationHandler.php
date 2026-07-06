<?php

namespace WeDevs\DokanPro\Coupons;

use Exception;
use WC_Coupon;
use WC_Discounts;
use WC_Product;
use WeDevs\Dokan\Contracts\Hookable;

/**
 * Coupon validation hooks handler.
 *
 * @since 4.0.0
 */
class ValidationHandler implements Hookable {
    public function __construct() {
        $this->register_hooks();
    }

    /**
     * @inheritDoc
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function register_hooks(): void {
        add_action( 'woocommerce_coupon_is_valid_for_product', [ $this, 'coupon_is_valid_for_product' ], 15, 3 );

        // Bypass the woocommerce minimum and maximum amount validation for coupons. These are handled by dokan filter hooks.
        add_filter( 'woocommerce_coupon_validate_minimum_amount', array( $this, 'validate_coupon_minimum_amount_for_wc' ) );
        add_filter( 'woocommerce_coupon_validate_maximum_amount', array( $this, 'validate_coupon_maximum_amount_for_wc' ) );

        // Handle Dokan Validation Logic.
        add_filter( 'dokan_coupon_is_valid', array( $this, 'ensure_coupon_is_valid' ), 15, 3 );
        add_filter( 'dokan_coupon_is_minimum_amount_valid', array( $this, 'is_minimum_amount_valid' ), 10, 3 );
        add_filter( 'dokan_coupon_is_maximum_amount_valid', array( $this, 'is_maximum_amount_valid' ), 10, 3 );
    }

    /**
     * Coupon is valid for vendor current product when coupon is applied,
     * Works for both vendor issued coupon and admin issued coupon.
     *
     * @since 3.4.0
     *
     * @param boolean $valid
     * @param WC_Product $product
     * @param WC_Coupon $coupon
     *
     * @return boolean
     */
    public function coupon_is_valid_for_product( bool $valid, WC_Product $product, WC_Coupon $coupon ): bool {
        if ( false === $valid ) {
            return false;
        }

        return dokan_pro()->coupon->is_coupon_valid_for_product( $coupon, $product->get_id() );
    }

    /**
     * Bypass WooCommerce coupon validation for minimum spend amount.
     *
     * This method always returns false to bypass the WooCommerce validation because the minimum spend amount
     * will be handled by the Dokan `dokan_coupon_is_minimum_amount_valid` filter hook.
     *
     * @param bool $is_valid Validation status of the coupon.
     *
     * @return bool Always returns false to bypass the validation.
     */
    public function validate_coupon_minimum_amount_for_wc( bool $is_valid ): bool {
        return false;
    }

    /**
     * Bypass WooCommerce coupon validation for maximum spend amount.
     *
     * This method always returns false to bypass the WooCommerce validation because the maximum spend amount
     * will be handled by the Dokan `dokan_coupon_is_maximum_amount_valid` filter hook.
     *
     * @param bool $is_valid Validation status of the coupon.
     *
     * @return bool Always returns false to bypass the validation.
     */
    public function validate_coupon_maximum_amount_for_wc( bool $is_valid ): bool {
        return false;
    }


    /**
     * Validates if a coupon can be used by a vendor based on various criteria.
     *
     * This method checks if a coupon created by admin is valid for vendor usage by validating:
     * - Commission type settings - whether the coupon is applicable to the vendor's commission type
     * - Product restrictions - whether the coupon is applicable to the vendor's products
     * - Vendor restrictions - whether the coupon is applicable to the vendor
     * - Minimum and maximum order amount requirements - whether the order line sub-total meets the coupon's requirements
     * - Usages limit - is validated by WooCommerce at woocommerce/includes/class-wc-discounts.php:620
     * - Usage limit per user - is validated by WooCommerce at woocommerce/includes/class-wc-discounts.php:669
     *
     * @since 4.0.0 Refactored to properly validate coupon for vendor usage
     *
     * @param bool         $is_valid  Initial validation status of the coupon
     * @param WC_Coupon    $coupon    The coupon object being validated
     * @param WC_Discounts $discounts WC_Discounts object containing cart/order items
     *
     * @return bool True if the coupon is valid for vendor usage, false otherwise
     * @throws Exception If the coupon is not valid for the vendor
     */
    public function ensure_coupon_is_valid( bool $is_valid, WC_Coupon $coupon, WC_Discounts $discounts ): bool {
        /**
         * Filter to customize minimum amount validation for vendor coupons.
         *
         * Allows modifying whether a coupon should be valid based on the minimum order amount requirement.
         *
         * @since 4.0.0
         *
         * @param bool         $is_valid  True if the minimum amount requirement is met
         * @param WC_Coupon    $coupon    The coupon object being validated
         * @param WC_Discounts $discounts The WC_Discounts object containing cart/order details
         */
        if ( ! apply_filters( 'dokan_coupon_is_minimum_amount_valid', true, $coupon, $discounts ) ) {
            throw new Exception(
                sprintf(
                /* translators: %s: minimum spend amount for coupon */
                    esc_html__( 'The minimum spend for this coupon is %s', 'dokan' ),
                    esc_html( wc_price( $coupon->get_minimum_amount() ) )
                ),
                108
            );
        }

        /**
         * Filter to customize maximum amount validation for vendor coupons.
         *
         * Allows modifying whether a coupon should be valid based on the maximum order amount requirement.
         *
         * @since 4.0.0
         *
         * @param bool         $is_valid  True if the maximum amount requirement is met
         * @param WC_Coupon    $coupon    The coupon object being validated
         * @param WC_Discounts $discounts The WC_Discounts object containing cart/order details
         */
        if ( ! apply_filters( 'dokan_coupon_is_maximum_amount_valid', true, $coupon, $discounts ) ) {
            throw new Exception(
                sprintf(
                /* translators: %s: maximum spend amount for coupon */
                    esc_html__( 'The maximum spend for this coupon is %s', 'dokan' ),
                    esc_html( wc_price( $coupon->get_maximum_amount() ) )
                ),
                108
            );
        }

        return $is_valid;
    }

    /**
     * Validates if the coupon meets minimum amount requirements.
     *
     * Checks if the total number of eligible items in cart/order meets the minimum spent
     * requirement set for the coupon. Only consider products that are valid for the coupon
     * when calculating the total.
     *
     * @since 2.9.10
     * @since 4.0.0 Refactored to properly calculate line item totals for validation
     *
     * @param bool          $is_valid     Current validation status of the coupon
     * @param WC_Coupon     $coupon    The coupon object being validated
     * @param WC_Discounts  $discounts WC_Discounts object containing cart/order items
     *
     * @throws Exception If the total amount is less than coupon's minimum spent requirement
     * @return bool True if the minimum amount requirement is met
     */
    public function is_minimum_amount_valid( bool $is_valid, WC_Coupon $coupon, WC_Discounts $discounts ): bool {
        $minimum_amount = $coupon->get_minimum_amount();
		if ( ! $minimum_amount > 0 ) {
			return $is_valid;
		}

        $line_item_total = $this->get_line_item_total_for_vendor( $discounts, $coupon );

        $sub_total = (float) array_reduce(
            $line_item_total,
            function ( $carry, $item ) {
				return $carry + $item['subtotal'];
            },
            0
        );

        $total_tax = (float) array_reduce(
            $line_item_total,
            function ( $carry, $item ) {
				return $carry + $item['subtotal_tax'];
            },
            0
        );

        if ( $this->is_subtotal_included_tax( $discounts ) ) {
            $sub_total += $total_tax;
        }

        if ( $minimum_amount > 0 && $minimum_amount > $sub_total ) {
            throw new Exception(
                $this->generate_coupon_validation_min_max_vendor_message( $line_item_total, $minimum_amount ), // phpcs:ignore
                108
            );
        }

        return $is_valid;
    }

    /**
     * Validates if the coupon meets maximum amount requirements.
     *
     * Checks if the total number of eligible items in cart/order is within the maximum spend
     * limit set for the coupon. Only consider products that are valid for the coupon
     * when calculating the total.
     *
     * @since 4.0.0
     *
     * @param bool          $is_valid     Current validation status of the coupon
     * @param WC_Coupon     $coupon    The coupon object being validated
     * @param WC_Discounts  $discounts WC_Discounts object containing cart/order items
     *
     * @throws Exception If the total amount exceeds coupon's maximum spent limit
     * @return bool True if the maximum amount requirement is met
     */
    public function is_maximum_amount_valid( bool $is_valid, WC_Coupon $coupon, WC_Discounts $discounts ): bool {
        if ( ! $coupon->get_maximum_amount() > 0 ) {
            return $is_valid;
        }

        $line_item_total = $this->get_line_item_total_for_vendor( $discounts, $coupon );

        $sub_total = (float) array_reduce(
            $line_item_total,
            function ( $carry, $item ) {
				return $carry + $item['subtotal'];
            },
            0
        );

        $total_tax = (float) array_reduce(
            $line_item_total,
            function ( $carry, $item ) {
				return $carry + $item['subtotal_tax'];
            },
            0
        );

        if ( $this->is_subtotal_included_tax( $discounts ) ) {
            $sub_total += $total_tax;
        }

        if ( $coupon->get_maximum_amount() > 0 && $coupon->get_maximum_amount() < $sub_total ) {
            throw new Exception(
                $this->generate_coupon_validation_min_max_vendor_message( $line_item_total, $coupon->get_maximum_amount(), 'max' ), // phpcs:ignore
                108
            );
        }

        return $is_valid;
    }

    /**
     * Calculates total amount of items eligible subtotals for coupon.
     *
     * Iterates through cart/order items and sums up the subtotal of only those items
     * that are valid for the given coupon based on vendor and product restrictions.
     * Takes into account tax settings when calculating totals.
     *
     * @since 4.0.0
     *
     * @param WC_Discounts $discounts The WC_Discounts object containing cart/order items
     * @param WC_Coupon    $coupon    The Coupon object to validate items against
     *
     * @return array Associative array of vendor IDs and their respective line item totals
     */
    protected function get_line_item_total_for_vendor( WC_Discounts $discounts, WC_Coupon $coupon ): array {
        $line_item_total = [];

        foreach ( $discounts->get_items() as $item ) {
            if ( ! isset( $item->product ) || ! $item->product instanceof WC_Product ) {
                continue;
            }

            /**
             * Get the vendor for the product
             *
             * @var \WeDevs\Dokan\Vendor\Vendor $vendor
             */
            $vendor = dokan_get_vendor_by_product( $item->product->get_id() );

            $is_valid_for_vendor = dokan_pro()->coupon->is_coupon_valid_for_vendor( $coupon, $vendor->get_id(), [] );

            if ( ! $is_valid_for_vendor ) {
                continue;
            }

            if ( ! isset( $line_item_total[ $vendor->get_id() ] ) ) {
                $line_item_total[ $vendor->get_id() ] = [
                    'subtotal'     => 0,
                    'subtotal_tax' => 0,
                    'vendor'       => $vendor,
                ];
            }

            if ( ! empty( $item->object['line_subtotal'] ) ) {
                $line_item_total[ $vendor->get_id() ] = [
                    'subtotal'     => $line_item_total[ $vendor->get_id() ]['subtotal'] ? $line_item_total[ $vendor->get_id() ]['subtotal'] + (float) $item->object['line_subtotal'] : (float) $item->object['line_subtotal'],
                    'subtotal_tax' => $line_item_total[ $vendor->get_id() ]['subtotal_tax'] ? $line_item_total[ $vendor->get_id() ]['subtotal_tax'] + (float) $item->object['line_subtotal_tax'] : (float) $item->object['line_subtotal_tax'],
                    'vendor'       => $vendor,
                ];
            }
        }

        return apply_filters(
            'dokan_coupon_line_item_total_for_vendor',
            $line_item_total,
            $discounts, $coupon
        );
    }

    /**
     * Get the object subtotal and subtotal tax.
     *
     * @since 4.0.0
     *
     * @return bool
     */
    protected function is_subtotal_included_tax( WC_Discounts $discounts ) {
        $including_tax = true;

        if ( is_a( $discounts->get_object(), 'WC_Cart' ) ) {
            /**
             * @var \WC_Cart $cart
             */
            $cart = $discounts->get_object();
            $including_tax = $cart->display_prices_including_tax();
        } elseif ( is_a( $discounts->get_object(), 'WC_Order' ) ) {
            /**
             * @var \WC_Order $order
             */
            $order = $discounts->get_object();
            $including_tax = $order->get_prices_include_tax();
        }

        return $including_tax;
    }


    protected function generate_coupon_validation_min_max_vendor_message( $line_item_total, $minimum_amount, $type = 'min' ) {
        $vendors = '';
        foreach ( $line_item_total as $vendor_id => $total ) {
            if ( ! empty( $vendors ) ) {
                $vendors .= ', ';
            }
            $vendors .= esc_html( $total['vendor']->get_shop_name() );
        }

        /* translators: %s: minimum or maximum spend amount for coupon */
        $message = 'min' === $type ? __( 'The minimum spend for this coupon from %1$s is %2$s', 'dokan' ) : __( 'The maximum spend for this coupon from %1$s is %2$s', 'dokan' );

        return apply_filters(
            'dokan_coupon_validation_min_max_vendor_message', sprintf(
                $message,
                $vendors,
                wc_price( $minimum_amount )
            ), $type, $vendors
        );
    }
}
