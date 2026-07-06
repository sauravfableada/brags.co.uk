<?php

namespace WeDevs\DokanPro\Modules\StripeExpress\Subscriptions;

defined( 'ABSPATH' ) || exit; // Exit if called directly

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;
use WeDevs\DokanPro\Modules\StripeExpress\Processors\Subscription;

class ExtendCartEndpoint {
    /**
     * Stores Rest Extending instance.
     *
     * @var ExtendSchema
     */
    private static $extend;

    /**
     * Plugin Identifier, unique to each plugin.
     *
     * @var string
     */
    const IDENTIFIER = 'dokan_stripe_express';

    /**
     * Bootstraps the class and hooks required data.
     *
     * @param ExtendSchema $extend_rest_api An instance of the ExtendSchema class.
     *
     * @since 4.3.0
     */
    public static function init( ExtendSchema $extend_rest_api ) {
        self::$extend = $extend_rest_api;
        self::extend_store();
    }

    /**
     * Registers the actual data into each endpoint.
     *
     * @since 4.3.0
     */
    public static function extend_store() {
        // Register into `checkout`
        self::$extend->register_endpoint_data(
            array(
                'endpoint'        => CartItemSchema::IDENTIFIER,
                'namespace'       => self::IDENTIFIER,
                'data_callback'   => array( self::class, 'extend_cart_item_data' ),
                'schema_callback' => array( self::class, 'extend_cart_schema' ),
                'schema_type'       => ARRAY_A,
            )
        );
    }

    /**
     * Register subscription product data into checkout endpoint.
     *
     * @since 4.3.0
     *
     * @return array $item_data Registered data or empty array if condition is not satisfied.
     */
    public static function extend_cart_item_data( $cart_item ) {
        $product_id = $cart_item['product_id'];
        $is_recurring_subscription = Subscription::is_recurring_vendor_subscription_product( $product_id );
        return [
            'subscription' => [
                'recurring' => $is_recurring_subscription,
            ],
        ];
    }

    /**
     * Register subscription product schema into checkout endpoint.
     *
     * @since 4.3.0
     *
     * @return array Registered schema.
     */
    public static function extend_cart_schema() {
        return [
            'properties' => array(
                'subscription' => array(
                    'type' => 'object',
                ),
            ),
        ];
    }
}
