<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax\Frontend;

use WeDevs\DokanPro\Modules\OrderMinMax\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Manages hooks for order minimum and maximum quantities and amounts
 *
 * @since 3.12.0
 */
class IntegrationHooks {

    /**
     * IntegrationHooks constructor
     */
    public function __construct() {
        add_filter( 'dokan_order_min_max_settings_min_quantity', array( $this, 'filter_min_max_quantity' ), 10, 2 );
        add_filter( 'dokan_order_min_max_settings_max_quantity', array( $this, 'filter_min_max_quantity' ), 10, 2 );
        add_filter( 'dokan_order_min_max_store_min_amount', array( $this, 'filter_min_max_amount' ), 10, 2 );
        add_filter( 'dokan_order_min_max_store_max_amount', array( $this, 'filter_min_max_amount' ), 10, 2 );
    }

    /**
     * Filters min max quantity for product
     *
     * @param int         $quantity Order quantity
     * @param \WC_Product $product  Product object
     *
     * @return int
     */
    public function filter_min_max_quantity( int $quantity, \WC_Product $product ): int {
        if ( ! $this->validate_product( $product ) ) {
            $quantity = 0;
        }

        /**
         * Filters the min max quantity for product
         *
         * @param int         $quantity          Original order quantity
         * @param \WC_Product $product           Product object
         *
         * @since 3.12.0
         */
        return apply_filters( 'dokan_order_min_max_validate_min_max_quantity', $quantity, $product );
    }

    /**
     * Filters min max amount on checkout page
     *
     * @param float $amount    Order amount
     * @param int   $vendor_id Vendor ID
     *
     * @return float
     */
    public function filter_min_max_amount( float $amount, int $vendor_id ): float {
        $product_id = filter_input( INPUT_POST, 'add-to-cart', FILTER_SANITIZE_NUMBER_INT ) ?? 0;

        if ( $product_id ) {
            $product = wc_get_product( $product_id );
            if ( $product && ! $this->validate_product( $product ) ) {
                $amount = 0;
            }
        } elseif ( ! $this->validate_cart_for_vendor( $vendor_id ) ) {
            $amount = 0;
        }

        /**
         * Filters the min max amount for vendor
         *
         * @param float $amount    Original order amount
         * @param int   $vendor_id Vendor ID
         *
         * @since 3.12.0
         */
        return apply_filters( 'dokan_order_min_max_validate_min_max_amount', $amount, $vendor_id );
    }

    /**
     * Validates the product for min max quantity and amount rules
     *
     * @param \WC_Product $product Product object
     *
     * @return bool
     */
    protected function validate_product( \WC_Product $product ): bool {
        return $this->validate_product_type( $product )
                && $this->validate_product_settings( $product )
                && $this->validate_cart_for_product( $product );
    }

    /**
     * Validates the product type
     *
     * @param \WC_Product $product Product object
     *
     * @return bool
     */
    protected function validate_product_type( \WC_Product $product ): bool {
        $allowed_types = $this->get_allowed_product_types( $product );
        $is_valid_type = in_array( $product->get_type(), $allowed_types, true );

        /**
         * Filters the product type validation
         *
         * @param bool        $is_valid_type Is valid or not for min max amount
         * @param \WC_Product $product       Product object
         *
         * @since 3.12.0
         */
        return apply_filters( 'dokan_order_min_max_validate_product_type', $is_valid_type, $product );
    }

    /**
     * Validates the wholesale status
     *
     * When customer are not checkout then it will validate the wholesale status
     *
     * @param \WC_Product $product Product object
     *
     * @return bool
     */
    protected function validate_product_settings( \WC_Product $product ): bool {
        $product_id = isset( $_REQUEST['add-to-cart'] ) ? absint( $_REQUEST['add-to-cart'] ) : 0;

        $is_valid = true;
        if ( $this->should_validate_product_settings( $product_id ) && $this->validate_wholesale_settings( $product ) ) {
            $is_valid = false;
        }

        /**
         * Filters the min max quantity rules applicable
         *
         * @param bool        $is_valid Is valid or not for min max quantity rules
         * @param \WC_Product $product  Product object
         *
         * @since 3.12.0
         */
        return apply_filters( 'dokan_order_min_max_validate_product_settings', $is_valid, $product );
    }

    /**
     * Validates the cart for product
     *
     * @param \WC_Product $product Product object
     *
     * @return bool
     */
    protected function validate_cart_for_product( \WC_Product $product ): bool {
        if ( ! WC()->cart instanceof \WC_Cart || empty( WC()->cart->get_cart_contents() ) ) {
            return true;
        }

        $cart_items = WC()->cart->get_cart_contents();
        foreach ( $cart_items as $cart_item ) {
            $cart_product = $cart_item['data'];
            if ( $cart_product->get_id() === $product->get_id() && ! $this->is_valid_cart_item( $cart_item ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validates the cart for vendor
     *
     * @param int $vendor_id Vendor ID
     *
     * @return bool
     */
    protected function validate_cart_for_vendor( int $vendor_id ): bool {
        if ( ! WC()->cart instanceof \WC_Cart || empty( WC()->cart->get_cart_contents() ) ) {
            return true;
        }

        $cart_validator = dokan_pro()->module->order_min_max->vendor_cart;
        $cart_items     = $cart_validator->get_cart_products_by_vendor( $vendor_id );

        foreach ( $cart_items as $cart_item ) {
            if ( ! $this->is_valid_cart_item( $cart_item ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validates the cart item
     *
     * @param array $cart_item Cart item
     *
     * @return bool
     */
    protected function is_valid_cart_item( array $cart_item ): bool {
        $is_valid = true;

        if ( $this->is_quote_item( $cart_item ) || $this->is_wholesale_item( $cart_item ) ) {
            $is_valid = false;
        }

        /**
         * Filters the cart item validation
         *
         * @param bool  $is_valid  Is valid or not
         * @param array $cart_item Cart item
         *
         * @since 3.12.0
         */
        return apply_filters( 'dokan_order_min_max_is_valid_cart_item', $is_valid, $cart_item );
    }

    /**
     * Determines if the cart item is a quote item
     *
     * @param array $cart_item Cart item data
     *
     * @return bool
     */
    protected function is_quote_item( array $cart_item ): bool {
        return isset( $cart_item['qoutes'] );
    }

    /**
     * Validates the wholesale quantity based on min or max
     *
     * @param array  $cart_item  Cart item data
     *
     * @return bool
     */
    protected function is_wholesale_item( array $cart_item ): bool {
        if ( ! isset( $cart_item['wholesale'] ) ) {
            return false;
        }

        $meta = $cart_item['wholesale'];
        if ( ! isset( $meta['enable_wholesale'] ) || 'yes' !== $meta['enable_wholesale'] ) {
            return false;
        }

        $cart_quantity      = $cart_item['quantity'] ?? 0;
        $wholesale_quantity = $meta['quantity'] ?? 0;

        // If the cart quantity is greater than or equal to the wholesale quantity, return true
        if ( $cart_quantity >= $wholesale_quantity ) {
            return true;
        }

        return false;
    }

    /**
     * Returns the allowed product types
     *
     * @param \WC_Product $product Product object
     *
     * @return array
     */
    protected function get_allowed_product_types( \WC_Product $product ): array {
        /**
         * Filters the allowed product types for min max amount
         *
         * @param array       $allowed_types Allowed product types
         * @param \WC_Product $product       Product object
         *
         * @since 3.12.0
         */
        return apply_filters( 'dokan_order_min_max_allowed_product_types', array( 'simple', 'variable', 'variation' ), $product );
    }

    /**
     * Determines if validation should be performed
     *
     * @param int $product_id Product ID
     * @return bool
     */
    protected function should_validate_product_settings( int $product_id ): bool {
        return ! $this->is_attempt_to_checkout() || $product_id;
    }

    /**
     * Validates the wholesale settings for a product
     *
     * @param \WC_Product $product
     * @return bool
     */
    protected function validate_wholesale_settings( \WC_Product $product ): bool {
        if ( ! dokan_pro()->module->is_active( 'wholesale' ) ) {
            return false;
        }

        if ( ! is_user_logged_in() ) {
            return false;
        }

        $user_id = dokan_get_current_user_id();
        if ( ! metadata_exists( 'user', $user_id, '_is_dokan_wholesale_customer' ) ) {
            return false;
        }

        /**
         * Filters the min max quantity rules applicable
         *
         * @since 3.12.0
         *
         * @param bool        $is_applicable Is applicable or not for min max quantity rules
         * @param \WC_Product $product       Product object
         */
        $exclude_wholesale = apply_filters( 'dokan_order_min_max_exclude_wholesale_product', true, $product );

        if ( ! $exclude_wholesale ) {
            return false;
        }

        $wholesale_settings = $product->get_meta( '_dokan_wholesale_meta' );
        return ( isset( $wholesale_settings['enable_wholesale'] ) && 'yes' === $wholesale_settings['enable_wholesale'] );
    }

    /**
     * Determines if an attempt is being made to checkout
     *
     * @return bool
     */
    protected function is_attempt_to_checkout(): bool {
        return is_checkout();
    }
}
