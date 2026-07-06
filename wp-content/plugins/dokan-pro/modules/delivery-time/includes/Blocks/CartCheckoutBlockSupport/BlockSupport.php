<?php

namespace WeDevs\DokanPro\Modules\DeliveryTime\Blocks\CartCheckoutBlockSupport;

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;
use WC_Order;
use WeDevs\DokanPro\Modules\DeliveryTime\Helper;
use WP_REST_Request;

class BlockSupport {

    public function __construct() {
        add_action( 'woocommerce_blocks_loaded', [ $this, 'register_extend_schema' ] );
        add_action( 'woocommerce_blocks_mini-cart_block_registration', [ $this, 'cart_checkout_block_support' ] );
        add_action( 'woocommerce_blocks_cart_block_registration', [ $this, 'cart_checkout_block_support' ] );
        add_action( 'woocommerce_blocks_checkout_block_registration', [ $this, 'cart_checkout_block_support' ] );
        add_action( 'woocommerce_store_api_checkout_update_order_from_request', [ $this, 'handle_delivery_time_data' ], 10, 2 );

        new StorePickupShipping();
    }

    /**
     * Register the woocommerce checkout extend schema for delivery time.
     *
     * @since 3.15.0
     *
     * @throws \Exception
     *
     * @return void
     */
    public function register_extend_schema() {
        $extend = StoreApi::container()->get( ExtendSchema::class );
        ExtendEndpoint::init( $extend );
    }

    /**
     * Delivery time integration in woocommerce cart checkout block.
     *
     * @since 3.15.0
     *
     * @param \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry $integration_registry
     *
     * @return void
     */
    public function cart_checkout_block_support( $integration_registry ) {
        $integration_registry->register( new BlockSupportIntegration() );
    }

    /**
     * Extracts and prepares delivery time data to save.
     *
     * @since 3.15.0
     *
     * @param WC_Order $order
     * @param WP_REST_Request $request
     *
     * @return void
     */
    public function handle_delivery_time_data( WC_Order $order, WP_REST_Request $request ) {
        if ( empty( $order->get_parent_id() ) ) {
            ExtendEndpoint::$processing_order = $order;
        }
        if ( ! isset( $request['extensions']['dokan_delivery_time'] ) || ! isset( $request['extensions']['dokan_delivery_time']['vendor_delivery_time'] ) || ! is_array( $request['extensions']['dokan_delivery_time']['vendor_delivery_time'] ) ) {
            return;
        }

        $request_data          = $request['extensions']['dokan_delivery_time']['vendor_delivery_time'];
        $vendor_delivery_times = [];

        // Extracting delivery time data.
        foreach ( $request_data as $vendor_delivery_time ) {
            $vendor_id = $vendor_delivery_time['vendor_id'];

            $vendor_delivery_times[ $vendor_id ]['vendor_id'] = $vendor_delivery_time['vendor_id'];
            $vendor_delivery_times[ $vendor_id ]['store_name'] = $vendor_delivery_time['store_name'];
            $vendor_delivery_times[ $vendor_id ]['delivery_date'] = $vendor_delivery_time['delivery_date'];
            $vendor_delivery_times[ $vendor_id ]['selected_delivery_type'] = $vendor_delivery_time['selected_delivery_type'];
            $vendor_delivery_times[ $vendor_id ]['delivery_time_slot'] = $vendor_delivery_time['delivery_time_slot'];
            $vendor_delivery_times[ $vendor_id ]['store_pickup_location'] = $vendor_delivery_time['store_pickup_location'];
        }

        if ( empty( $order->get_parent_id() ) ) {
            $order->update_meta_data( 'dokan_cart_checkout_block_delivery_time', $vendor_delivery_times );
            $order->save_meta_data();
            $order->save();
        }
    }

    /**
     * Saving delivery time to database.
     *
     * @since 3.15.0
     *
     * @param $delivery_time
     * @param $vendor_delivery_times
     * @param $vendor_id
     * @param $order
     *
     * @return void
     */
    private function save_delivery_time( $delivery_time, $vendor_delivery_times, $vendor_id, $order ) {
        $data = apply_filters( 'dokan_delivery_time_checkout_args', $delivery_time, $vendor_delivery_times, $vendor_id );

        Helper::save_delivery_time_date_slot( $data, $order );
        $order->save();
    }
}
