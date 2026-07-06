<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax\DataSource;

defined( 'ABSPATH' ) || exit;

use WC_Product;
use WeDevs\DokanPro\Modules\OrderMinMax\Constants;

/**
 * Manages product wise min max settings
 *
 * @since 3.12.0
 */
class ProductMinMaxSettings {

    const MIN_QUANTITY = 'min_quantity';
    const MAX_QUANTITY = 'max_quantity';

    /**
     * Settings will be pulled for this product
     *
     * @since 3.12.0
     *
     * @var \WC_Product|null
     */
    protected $product = null;

    /**
     * Product specific settings
     *
     * @since 3.12.0
     *
     * @var array
     */
    protected $settings = array();

    /**
     * Initializes the instance
     *
     * @param int|WC_Product $product
     *
     * @since 3.12.0
     */
    public function __construct( $product ) {
        if ( $product instanceof WC_Product ) {
            $this->product = $product;
        } else {
            $this->product = wc_get_product( $product );
        }
    }

    /**
     * Minimum Quantity for current product
     *
     * @param string $context Context for which the value is being fetched, default is 'view', possible values are 'view', 'edit'.
     *
     * @return int
     *
     * @since 3.12.0
     */
    public function min_quantity( string $context = 'view' ): int {
        if ( empty( $this->settings ) ) {
            $this->fetch_data();
        }

        $min_quantity = ! empty( $this->settings[ self::MIN_QUANTITY ] ) ? (int) $this->settings[ self::MIN_QUANTITY ] : 0;
        if ( 'view' === $context ) {
            /**
             * Filters the minimum quantity for the product
             *
             * @since 3.12.0
             *
             * @param int $min_quantity Minimum quantity for the product
             * @param \WC_Product $product Product object
             */
            $min_quantity = apply_filters( 'dokan_order_min_max_settings_min_quantity', $min_quantity, $this->product );
        }

        return $min_quantity;
    }

    /**
     * Maximum Quantity for current product
     *
     * @return int Maximum quantity for the product
     *
     * @since 3.12.0
     */
    public function max_quantity( string $context = 'view' ): int {
        if ( empty( $this->settings ) ) {
            $this->fetch_data();
        }

        $max_quantity = ! empty( $this->settings[ self::MAX_QUANTITY ] ) ? (int) $this->settings[ self::MAX_QUANTITY ] : 0;
        if ( 'view' === $context ) {
            /**
             * Filters the maximum quantity for the product
             *
             * @since 3.12.0
             *
             * @param int $max_quantity
             * @param \WC_Product $product
             */
            $max_quantity = apply_filters( 'dokan_order_min_max_settings_max_quantity', $max_quantity, $this->product );
        }

        return $max_quantity;
    }

    /**
     * Sets data for single product
     *
     * @param array $data
     *
     * @return void
     *
     * @since 3.12.0
     */
    public function set_data( array $data = array() ) {
        $data = empty( $data ) ? $this->get_default_settings() : $this->sanitize_data( $data );

        /**
         * Filters the min max product quantity settings
         *
         * @since 3.12.0
         *
         * @param array $data
         * @param WC_Product $product
         */
        $data = apply_filters( 'dokan_order_min_max_settings_product_quantity', $data, $this->product );

        $this->settings = $data;
    }

    /**
     * Fetches data from product meta
     *
     * @return void
     *
     * @since 3.12.0
     */
    public function fetch_data(): void {
        if ( empty( $this->product ) ) {
            return;
        }

        $data = $this->product->get_meta( Constants::SINGLE_PRODUCT_META_KEY );
        $data = is_array( $data ) ? $data : array(); // Fixes parameter mismatch issue
        $this->set_data( $data );
    }

    /**
     * Sanitizes the data
     *
     * @param array $data
     *
     * @return array
     *
     * @since 3.12.0
     */
    protected function sanitize_data( array $data ): array {
        $filtered_data                       = array();
        $filtered_data[ self::MIN_QUANTITY ] =
            ! empty( $data[ self::MIN_QUANTITY ] ) && is_int( $data[ self::MIN_QUANTITY ] )
                ? $data[ self::MIN_QUANTITY ]
                : '';
        $filtered_data[ self::MAX_QUANTITY ] =
            ! empty( $data[ self::MAX_QUANTITY ] ) && is_int( $data[ self::MAX_QUANTITY ] )
                ? $data[ self::MAX_QUANTITY ]
                : '';

        // Min quantity can not be greater than max quantity
        if (
            '' !== $filtered_data[ self::MIN_QUANTITY ] && '' !== $filtered_data[ self::MAX_QUANTITY ]
            && $filtered_data[ self::MIN_QUANTITY ] > $filtered_data[ self::MAX_QUANTITY ]
        ) {
            $filtered_data[ self::MAX_QUANTITY ] = $filtered_data[ self::MIN_QUANTITY ];
        }

        return $filtered_data;
    }

    /**
     * Generates default settings data
     *
     * @return array
     *
     * @since 3.12.0
     */
    public function get_default_settings(): array {
        return array(
            self::MIN_QUANTITY => 0,
            self::MAX_QUANTITY => 0,
        );
    }

    /**
     * Saves settings
     *
     * @return bool
     *
     * @since 3.12.0
     */
    public function save(): bool {
        if ( ! empty( $this->settings ) && ! empty( $this->product ) ) {
            $this->product->update_meta_data( Constants::SINGLE_PRODUCT_META_KEY, $this->settings );
            $this->product->save();
            return true;
        }
        return false;
    }
}
