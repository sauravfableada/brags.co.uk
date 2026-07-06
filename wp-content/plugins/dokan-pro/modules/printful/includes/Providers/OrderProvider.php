<?php

namespace WeDevs\DokanPro\Modules\Printful\Providers;

use WC_Order;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;
use WeDevs\DokanPro\Dependencies\Printful\PrintfulApiClient;
use WeDevs\DokanPro\Modules\Printful\Auth;
use WeDevs\DokanPro\Modules\Printful\Processors\OrderProcessorInterface;
use WeDevs\DokanPro\Modules\Printful\Processors\PrintfulOrderProcessor;
use WeDevs\DokanPro\Modules\Printful\Processors\PrintfulProductProcessor;
use WeDevs\DokanPro\Modules\Printful\Shipping\PrintfulShippingPackageSplitter;

/**
 * Class OrderProvider.
 *
 * @since 3.13.0
 */
class OrderProvider {

    /**
     * OrderProcessor instance.
     *
     * @since 3.13.0
     *
     * @var OrderProcessorInterface[] $processors OrderProcessor instance.
     */
    protected array $processors;

    /**
     * Class constructor.
     */
    public function __construct() {
        $this->register_processors();

        add_action( 'dokan_checkout_update_order_meta', [ $this, 'create_order' ], 99, 2 );
        add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_cart_metas' ], 10, 3 );
        add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'add_order_metas' ], 10, 4 );
        add_action( 'woocommerce_checkout_create_order_shipping_item', [ $this, 'add_order_metas_for_shipping' ], 10, 4 );
        add_filter( 'dokan_pro_printful_order_processor', [ $this, 'schedule_printful_order_creation' ] );
        add_action( 'woocommerce_order_status_changed', [ $this, 'confirm_printful_order_fulfillment' ], 10, 4 );
        add_filter( 'dokan_delivery_time_should_render_delivery_box', [ $this, 'check_printful_products_in_order' ], 10, 2 );
    }

    /**
     * Get processors.
     *
     * @since 3.13.0
     *
     * @return OrderProcessorInterface[]
     */
    public function get_processors(): array {
        return $this->processors;
    }

    /**
     * Register processors.
     *
     * @since 3.13.0
     *
     * @return void
     */
    protected function register_processors() {
        $processors = [
            'printful' => new PrintfulOrderProcessor(),
        ];

        $this->processors = apply_filters( 'dokan_pro_print_on_demand_order_processors', $processors );
    }


    /**
     * Create order.
     *
     * @since 3.13.0
     *
     * @param int $order_id Order ID.
     *
     * @return void
     */
    public function create_order( int $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        foreach ( $this->get_processors() as $key => $processor ) {
            $processor->create( $order );
        }
    }

    /**
     * Schedule the creation of a Printful order.
     *
     * This function schedules the creation of a Printful order
     * for the given order ID and vendor ID.
     *
     * @since 3.13.0
     *
     * @param int $order_id The ID of the order to be sent to Printful.
     *
     * @return void
     */
    public function schedule_printful_order_creation( $order_id ) {
        $this->processors['printful']->create( wc_get_order( $order_id ) );
    }

    /**
     * Confirms the fulfillment of a Printful order for a given WooCommerce order.
     *
     * This function checks if the order has been paid, ensures that the Printful order
     * hasn't already been confirmed, retrieves the Printful order details, and confirms
     * the fulfillment of the order via the Printful API.
     *
     * @param int      $order_id    The ID of the WooCommerce order.
     * @param string   $old_status  The previous status of the order.
     * @param string   $new_status  The new status of the order.
     * @param WC_Order $order       The WooCommerce order object.
     *
     * @return void
     */
    public function confirm_printful_order_fulfillment( $order_id, $old_status, $new_status, $order ) {
        if ( ! $order->is_paid() ) {
            return;
        }

        $is_fulfilled = $order->get_meta( '_dokan_printful_order_confirm' );
        if ( $is_fulfilled ) {
            return;
        }

        $printful_order_response = $order->get_meta( '_dokan_printful_order_response' );
        $printful_order_response = maybe_unserialize( $printful_order_response );
        $printful_order_id       = ! empty( $printful_order_response['id'] ) ? $printful_order_response['id'] : 0;
        if ( ! $printful_order_response || ! $printful_order_id ) {
            return;
        }

        $vendor_id = dokan_get_seller_id_by_order( $order );
        if ( ! $vendor_id ) {
            return;
        }

        $auth = new Auth( $vendor_id );
        if ( ! $auth->is_connected() ) {
            return;
        }

        // Update Order in Printful.
        try {
            $client                 = PrintfulApiClient::createOauthClient( $auth->get_access_token() );
            $fulfill_printful_order = $client->post( "orders/{$printful_order_id}/confirm" );
            $fulfill_order_status   = $fulfill_printful_order['status'] ?? 'draft';

            if ( $fulfill_order_status === 'pending' ) {
                $order->update_meta_data( '_dokan_printful_order_confirm', true );
                $order->save();
            }
        } catch ( \Exception $e ) {
            dokan_log( $e->getMessage() );
            return;
        }
    }

    /**
     * Check if the order contains any Printful products.
     *
     * @since 3.13.0
     *
     * @param bool     $should_render Whether to render the vendor delivery box.
     * @param WC_Order $order         The order object.
     *
     * @return bool Returns false if a Printful product is found, otherwise returns the original value.
     */
    public function check_printful_products_in_order( bool $should_render, WC_Order $order ): bool {
        foreach ( $order->get_items() as $item ) {
            $variant_id = $item->get_meta( PrintfulOrderProcessor::META_KEY_VARIANT_ID, true );

            // Check if the order item is a Printful product.
            if ( ! empty( $variant_id ) ) {
                return false; // Don't render the vendor delivery box for Printful products
            }
        }

        return $should_render;
    }

    /**
     * Add Cart items metas if needed for Printful or other processor.
     *
     * @since 3.13.0
     *
     * @param array $cart_item_data Cart Item data.
     * @param int   $product_id Product id.
     * @param int   $variation_id Variation ID.
     *
     * @return array
     */
    public function add_cart_metas( array $cart_item_data, int $product_id, int $variation_id ): array {
        // check if it is a variation. if not return.
        if ( ! $variation_id ) {
            return $cart_item_data;
        }

        $variation = wc_get_product( $variation_id );

        $printful_variation_id = $variation->get_meta( PrintfulProductProcessor::META_KEY_PRODUCT_VARIATION_ID, true );
        $printful_external_variation_id = $variation->get_meta( PrintfulProductProcessor::META_KEY_PRODUCT_EXTERNAL_VARIATION_ID, true );
        $printful_store_id = $variation->get_meta( PrintfulProductProcessor::META_KEY_STORE_ID, true );

        if ( ! empty( $printful_variation_id ) ) {
            $cart_item_data[ PrintfulOrderProcessor::META_KEY_VARIANT_ID ] = $printful_variation_id;
            $cart_item_data[ PrintfulOrderProcessor::META_KEY_EXTERNAL_VARIANT_ID ] = $printful_external_variation_id;
            $cart_item_data[ PrintfulOrderProcessor::META_KEY_STORE_ID ] = $printful_store_id;
        }

        return $cart_item_data;
    }

    /**
     * Add Order items metas if needed for Printful or other processor.
     *
     * @since 3.13.0
     *
     * @param WC_Order_Item_Product $item
     * @param                       $cart_item_key
     * @param                       $values
     *
     * @return void
     */
    public function add_order_metas( WC_Order_Item_Product $item, $cart_item_key, $values ) {
        if ( ! isset( $values[ PrintfulOrderProcessor::META_KEY_VARIANT_ID ] ) ) {
            return;
        }
        $item->add_meta_data(
            PrintfulOrderProcessor::META_KEY_VARIANT_ID,
            $values[ PrintfulOrderProcessor::META_KEY_VARIANT_ID ],
            true
        );
        $item->add_meta_data(
            PrintfulOrderProcessor::META_KEY_EXTERNAL_VARIANT_ID,
            $values[ PrintfulOrderProcessor::META_KEY_EXTERNAL_VARIANT_ID ],
            true
        );
        $item->add_meta_data(
            PrintfulOrderProcessor::META_KEY_STORE_ID,
            $values[ PrintfulOrderProcessor::META_KEY_STORE_ID ],
            true
        );
    }

    /**
     * Add Order items metas if needed for Printful or other processor.
     *
     * @param WC_Order_Item_Shipping $item
     * @param string                  $package_key
     * @param array                   $package
     *
     * @return void
     */
    public function add_order_metas_for_shipping( WC_Order_Item_Shipping $item, string $package_key, array $package ) {
        if ( empty( $package[ PrintfulShippingPackageSplitter::PACKAGE_KEY ] ) ) {
            return;
        }

        $item->add_meta_data(
            PrintfulOrderProcessor::META_KEY_PRINTFUL_PACKAGE,
            true,
            true
        );
    }
}
