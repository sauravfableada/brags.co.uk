<?php

namespace WeDevs\DokanPro\Modules\Printful\Processors;

use WC_Order;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;
use WeDevs\DokanPro\Dependencies\Printful\PrintfulApiClient;
use WeDevs\DokanPro\Modules\Printful\Auth;

/**
 * Printful Order Processor.
 *
 * @since 3.13.0
 */
class PrintfulOrderProcessor implements OrderProcessorInterface {

    const META_KEY_VARIANT_ID = '_dokan_printful_product_variation_id';
    const META_KEY_EXTERNAL_VARIANT_ID = '_dokan_printful_product_external_variation_id';
    const META_KEY_STORE_ID = '_dokan_printful_store_id';
    const META_KEY_PRINTFUL_ORDER_ID = '_dokan_printful_order_id';
    const META_KEY_PRINTFUL_ORDER_FAILURE_ID = '_dokan_printful_order_request_failed';
    const META_KEY_PRINTFUL_ORDER_SHIPMENT_DATA = '_dokan_printful_order_shipment_data';
    const META_KEY_PRINTFUL_PACKAGE = '_dokan_printful_shipping_package';

    /**
     * Creates a new order in printful.
     *
     * @since 3.13.0
     *
     * @param WC_Order $order Order object.
     *
     * @return void
     */
    public function create( WC_Order $order ) {
        $printful_items = [];
        $shipping       = 'STANDARD';
        $retail_costs   = [
            'currency' => get_woocommerce_currency(),
            'subtotal' => 0.00,
            'discount' => 0.00,
            'shipping' => 0.00,
            'tax'      => 0.00,
        ];

        // Parse Order items for printful products.
        foreach ( $order->get_items() as $line_item_key => $line_item ) {
            /**
             * @var WC_Order_Item_Product $line_item
             */
            if ( empty( $line_item->get_meta( self::META_KEY_VARIANT_ID ) ) ) {
                continue;
            }

            $printful_items[] = [
                'id'              => $line_item_key,
                'external_id'     => $line_item->get_id(),
                'quantity'        => $line_item->get_quantity(),
                'sync_variant_id' => $line_item->get_meta( self::META_KEY_VARIANT_ID ),
                'name'            => $line_item->get_name(),
                'retail_price'    => (float) $line_item->get_total() / $line_item->get_quantity(),
            ];

            // Add to retail costs
            $retail_costs['subtotal'] += (float) $line_item->get_subtotal();
            $retail_costs['tax']      += (float) $line_item->get_total_tax();
            $retail_costs['discount'] += ( (float) $line_item->get_subtotal() - (float) $line_item->get_total() );
        }

        if ( empty( $printful_items ) ) {
            return;
        }

        // Determine shipping method. Only wuen printful shipping method is used.
        foreach ( $order->get_items( 'shipping' ) as $line_item_key => $shipping_line_item ) {
            /**
             * @var WC_Order_Item_Shipping $shipping_line_item
             */
            if ( 'dokan_printful_shipping' === $shipping_line_item->get_method_id() ) {
                $shipping = $shipping_line_item->get_meta( 'printful_rate_id' );
                break;
            }
        }

        // Get retail shipping costs for printful order. Otherwise, it will be old shipping price.
        foreach ( $order->get_items( 'shipping' ) as $line_item_key => $shipping_line_item ) {
            /**
             * @var WC_Order_Item_Shipping $shipping_line_item
             */
            if ( ! empty( $shipping_line_item->get_meta( self::META_KEY_PRINTFUL_PACKAGE ) ) ) {
                $retail_costs['tax']      += (float) $shipping_line_item->get_total_tax();
                $retail_costs['shipping'] += (float) $shipping_line_item->get_total();
                break;
            }
        }

        // Get recipient data & vendor id.
        $recipient = $this->get_recipient_data( $order );
        $vendor_id = dokan_get_seller_id_by_order( $order );

        if ( ! $vendor_id ) {
            return;
        }

        $auth = $this->get_auth( $vendor_id );

        if ( ! $auth->is_connected() ) {
            return;
        }

        // Create Order in Printful.
        try {
            // Retrieve the store's tax number and check the germanized module activation status.
            $store_tax_number            = get_user_meta( $vendor_id, 'dokan_vat_number', true );
            $is_germanized_module_active = dokan_pro()->module->is_active( 'germanized' );

            // If the Germanized module is active and the store tax number exists, add it to the recipient's information.
            if ( $is_germanized_module_active && $store_tax_number ) {
                $recipient['tax_number'] = $store_tax_number;
            }

            $client         = $this->get_printful_api_client( $auth );
            $printful_order = $client->post(
                'orders',
                [
                    'external_id'  => $order->get_id(),
                    'recipient'    => $recipient,
                    'items'        => $printful_items,
                    'shipping'     => $shipping,
                    'retail_costs' => $retail_costs,
                ],
                [
                    'confirm' => $order->is_paid(),
                ]
            );

            // Remove order failure meta if exists.
            if ( $order->meta_exists( self::META_KEY_PRINTFUL_ORDER_FAILURE_ID ) ) {
                $order->delete_meta_data( self::META_KEY_PRINTFUL_ORDER_FAILURE_ID );
            }

			/* translators: %s: Printful order id. */
            $order->add_order_note( sprintf( esc_html__( 'Printful Order ID: %s', 'dokan' ), $printful_order['id'] ) );
            $order->update_meta_data( self::META_KEY_PRINTFUL_ORDER_ID, $printful_order['id'] );
            $order->update_meta_data( '_dokan_printful_order_response', maybe_serialize( $printful_order ) );
            $order->save();
        } catch ( \Exception $e ) {
            $request_failed = $order->get_meta( self::META_KEY_PRINTFUL_ORDER_FAILURE_ID );
            $request_failed = ! empty( $request_failed ) ? absint( $request_failed ) : 0;

            // Retry to create order max twice.
            if ( $request_failed >= 2 ) {
                return;
            }

            // Update order creation attempts & add notes for every attempt.
            $order->update_meta_data( self::META_KEY_PRINTFUL_ORDER_FAILURE_ID, (string) ++$request_failed );
            $order->add_order_note(
                sprintf(
                    /* translators: 1) Request failure no, 2) Attempt failure error */
                    esc_html__( 'Order creation attempt %1$s has been failed for: %2$s', 'dokan' ),
                    $request_failed,
                    $e->getMessage()
                )
            );
            $order->save();

            // Schedule an order re-creation event for retry to create printful order after 5 minutes.
            WC()->queue()->schedule_single(
                time() + 300,
                'dokan_pro_printful_order_processor',
                [
                    'dokan_order_id'  => $order->get_id(),
                ],
                'dokan_pro_printful_order_processor'
            );

            return;
        }
	}

	public function update( $order ) {
		// TODO: Implement update() method.
	}

	public function delete( $order ) {
		// TODO: Implement delete() method.
	}

    /**
     * @param int $vendor_id
     *
     * @return Auth
     */
    protected function get_auth( int $vendor_id ): Auth {
        return new Auth( $vendor_id );
    }

    /**
     * @param Auth $auth
     *
     * @return PrintfulApiClient
     * @throws \WeDevs\DokanPro\Dependencies\Printful\Exceptions\PrintfulException
     */
    protected function get_printful_api_client( Auth $auth ): PrintfulApiClient {
        $client = PrintfulApiClient::createOauthClient( $auth->get_access_token() );

        return $client;
    }

    /**
     * Get recipient data based on the presence of a shipping address.
     *
     * @since 3.13.0
     *
     * @param WC_Order $order The WooCommerce order object.
     *
     * @return array The recipient data.
     */
    protected function get_recipient_data( WC_Order $order ): array {
        if ( $order->has_shipping_address() ) {
            // Generate recipient data from shipping address.
            $recipient = [
                'name'         => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                'address1'     => $order->get_shipping_address_1(),
                'address2'     => $order->get_shipping_address_2(),
                'city'         => $order->get_shipping_city(),
                'state_code'   => $order->get_shipping_state(),
                'country_code' => $order->get_shipping_country(),
                'zip'          => $order->get_shipping_postcode(),
                'phone'        => $order->get_shipping_phone(),
                'email'        => $order->get_billing_email(), // Email is usually from billing info.
                'company'      => $order->get_shipping_company(),
            ];
        } else {
            // Generate recipient data from billing address.
            $recipient = [
                'name'         => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'address1'     => $order->get_billing_address_1(),
                'address2'     => $order->get_billing_address_2(),
                'city'         => $order->get_billing_city(),
                'state_code'   => $order->get_billing_state(),
                'country_code' => $order->get_billing_country(),
                'zip'          => $order->get_billing_postcode(),
                'phone'        => $order->get_billing_phone(),
                'email'        => $order->get_billing_email(),
                'company'      => $order->get_billing_company(),
            ];
        }

        return $recipient;
    }
}
