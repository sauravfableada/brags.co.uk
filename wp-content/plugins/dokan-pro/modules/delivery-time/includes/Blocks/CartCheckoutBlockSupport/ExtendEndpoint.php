<?php

namespace WeDevs\DokanPro\Modules\DeliveryTime\Blocks\CartCheckoutBlockSupport;

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use WeDevs\DokanPro\Modules\DeliveryTime\Helper as DHelper;
use WeDevs\DokanPro\Modules\DeliveryTime\StorePickup\Helper as StorePickupHelper;

defined( 'ABSPATH' ) || exit();

/**
 * Extend checkout endpoint of StoreAPI
 *
 * @since 3.15.0
 */
class ExtendEndpoint {
    /**
     * Stores Rest Extending instance.
     *
     * @var ExtendSchema
     */
    private static $extend;

    /**
     * Stores processing order instance.
     *
     * @var \WC_Order|null
     */
    public static ?\WC_Order $processing_order = null;

    /**
     * Plugin Identifier, unique to each plugin.
     *
     * @var string
     */
    const IDENTIFIER = 'dokan_delivery_time';

    /**
     * Bootstraps the class and hooks required data.
     *
     * @param ExtendSchema $extend_rest_api An instance of the ExtendSchema class.
     *
     * @since 3.15.0
     */
    public static function init( ExtendSchema $extend_rest_api ) {
        self::$extend = $extend_rest_api;
        self::extend_store();
    }

    /**
     * Registers the actual data into each endpoint.
     *
     * @since 3.15.0
     */
    public static function extend_store() {
        // Register into `checkout`
        self::$extend->register_endpoint_data(
            array(
                'endpoint'        => CheckoutSchema::IDENTIFIER,
                'namespace'       => self::IDENTIFIER,
                'data_callback'   => array( self::class, 'extend_checkout_data' ),
                'schema_callback' => array( self::class, 'extend_checkout_schema' ),
                'schema_type'       => ARRAY_A,
            )
        );

        // Register into `cart`
        self::$extend->register_endpoint_data(
            array(
                'endpoint'        => CartSchema::IDENTIFIER,
                'namespace'       => self::IDENTIFIER,
                'data_callback'   => array( self::class, 'extend_cart_data' ),
                'schema_callback' => array( self::class, 'extend_cart_schema' ),
                'schema_type'     => ARRAY_A,
            )
        );
    }

    /**
     * Get data for checkout extension.
     *
     * @since 4.3.2
     *
     * @return array
     */
    public static function extend_checkout_data() {
        $order = self::$processing_order;

        if ( ! $order ) {
            return [
                'vendor_delivery_time' => [],
            ];
        }

        $data                 = $order->get_meta( 'dokan_cart_checkout_block_delivery_time' );
        $vendor_delivery_data = [];

        if ( ! empty( $data ) && is_array( $data ) ) {
            foreach ( $data as $v_id => $details ) {
                $vendor_delivery_data[] = [
                    'vendor_id'              => (int) $v_id,
                    'store_name'             => isset( $details['store_name'] ) ? $details['store_name'] : '',
                    'delivery_date'          => isset( $details['delivery_date'] ) ? $details['delivery_date'] : '',
                    'selected_delivery_type' => isset( $details['selected_delivery_type'] ) ? $details['selected_delivery_type'] : '',
                    'delivery_time_slot'     => isset( $details['delivery_time_slot'] ) ? $details['delivery_time_slot'] : '',
                    'store_pickup_location'  => isset( $details['store_pickup_location'] ) ? $details['store_pickup_location'] : '',
                ];
            }
        }

        return [
            'vendor_delivery_time' => $vendor_delivery_data,
        ];
    }

    /**
     * Add delivery slot data at the cart level, grouped by vendor.
     *
     * @since 4.3.2
     *
     * @return array
     */
    public static function extend_cart_data() {
        $cart = WC()->cart;

        if ( ! $cart ) { // @phpstan-ignore-line -- cart is null until initialized.
            return [
                'vendor_delivery_time'    => [],
                'pickup_shipping_vendors' => [],
            ];
        }

        $vendors_data        = [];
        $delivery_date_label = dokan_get_option( 'delivery_date_label', 'dokan_delivery_time', 'Select Delivery Date' );

        foreach ( $cart->get_cart() as $cart_item ) {
            $product = $cart_item['data'];

            if ( ! $product ) {
                continue;
            }

            $vendor = dokan_get_vendor_by_product( $product );

            if ( ! $vendor || ! $vendor->get_id() ) {
                continue;
            }

            $vendor_id = (int) $vendor->get_id();

            // Only process each vendor once
            if ( isset( $vendors_data[ $vendor_id ] ) ) {
                continue;
            }

            // Get vendor delivery settings
            $settings      = DHelper::get_delivery_time_settings( $vendor_id );
            $delivery_days = isset( $settings['delivery_day'] ) ? $settings['delivery_day'] : [];
            $buffer_unit   = isset( $settings['delivery_buffer_unit'] ) ? $settings['delivery_buffer_unit'] : 'days';
            $buffer_value  = isset( $settings['delivery_buffer_value'] ) ? (int) $settings['delivery_buffer_value'] : 0;

            // Get vendor name
            $vendor_info = dokan_get_store_info( $vendor_id );
            $vendor_name = isset( $vendor_info['store_name'] ) ? $vendor_info['store_name'] : '';

            // Get available types
            $is_delivery_active = isset( $settings['delivery_support'] ) && 'on' === $settings['delivery_support'];
            $is_pickup_active   = (bool) StorePickupHelper::is_store_pickup_location_active( $vendor_id );

            $vendors_data[ $vendor_id ] = [
                'vendor_id'           => $vendor_id,
                'vendor_name'         => $vendor_name,
                'available_types'     => [
                    'delivery' => $is_delivery_active,
                    'pickup'   => $is_pickup_active,
                ],
                'buffer'              => [
                    'unit'  => $buffer_unit,
                    'value' => $buffer_value,
                ],
                'delivery_date_label' => $delivery_date_label,
                'delivery_day'        => $delivery_days,
            ];
        }

        return [
            'vendor_delivery_time'    => array_values( $vendors_data ),
            'pickup_shipping_vendors' => StorePickupShipping::get_pickup_vendor_ids(),
        ];
    }

    /**
     * Register cart-level delivery slot schema.
     *
     * @since 4.3.2
     *
     * @return array
     */
    public static function extend_cart_schema() {
        return [
            'vendor_delivery_time' => [
                'description' => 'Available delivery time settings per vendor in cart',
                'context'     => [ 'view' ],
                'type'        => 'array',
                'items'       => [
                    'type'       => 'object',
                    'properties' => [
                        'vendor_id'           => [
                            'type' => 'integer',
                        ],
                        'vendor_name'         => [
                            'type' => 'string',
                        ],
                        'available_types'     => [
                            'type'       => 'object',
                            'properties' => [
                                'delivery' => [
                                    'type' => 'boolean',
                                ],
                                'pickup'   => [
                                    'type' => 'boolean',
                                ],
                            ],
                        ],
                        'buffer'              => [
                            'type'       => 'object',
                            'properties' => [
                                'unit'  => [
                                    'type' => 'string',
                                ],
                                'value' => [
                                    'type' => 'integer',
                                ],
                            ],
                        ],
                        'delivery_date_label' => [
                            'type' => 'string',
                        ],
                        'delivery_day'        => [
                            'type' => 'object',
                        ],
                    ],
                ],
            ],
            'pickup_shipping_vendors' => [
                'description' => 'Vendor ids whose shipping is currently replaced by store pickup in this session',
                'context'     => [ 'view' ],
                'type'        => 'array',
                'items'       => [
                    'type' => 'integer',
                ],
            ],
        ];
    }

    /**
     * Register subscription product schema into checkout endpoint.
     *
     * @since 3.15.0
     *
     * @return array Registered schema.
     */
    public static function extend_checkout_schema() {
        return [
            'vendor_delivery_time' => [
                'description' => 'Customers delivery time data of vendors',
                'context'     => [ 'view', 'edit' ],
                'type'        => 'array',
                'items'       => [
                    'type'       => 'object',
                    'properties' => [
                        'vendor_id' => [
                            'type'     => 'integer',
                            'required' => true,
                        ],
                        'store_name' => [
                            'type' => 'string',
                            'required' => true,
                        ],
                        'delivery_date' => [
                            'type'   => 'string',
                            'format' => 'date',
                            'required' => true,
                        ],
                        'selected_delivery_type' => [
                            'type' => 'string',
                            'required' => true,
                        ],
                        'delivery_time_slot' => [
                            'type' => 'string',
                            'required' => true,
                        ],
                        'store_pickup_location' => [
                            'type' => 'string',
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ];
    }
}
