<?php

namespace WeDevs\DokanPro\Modules\Printful\Events;

use WeDevs\DokanPro\Dependencies\Printful\Structures\Webhook\WebhookItem;
use WeDevs\DokanPro\Modules\Printful\Processors\PrintfulOrderProcessor;

class ShipmentDelivered extends AbstractEvent {

    /**
     * @var string $shipment_status
     *
     * @since 3.13.0
     */
    protected string $shipment_status;

    /**
     * Class constructor.
     *
     * @param WebhookItem $event
     */
    public function __construct( WebhookItem $event ) {
        $this->shipment_status = 'ss_on_the_way';
        parent::__construct( $event );
    }

    /**
     * Processes the shipment data received from an external source, typically Printful.
     *
     * This method handles the integration of incoming shipment data and creates a corresponding
     * Dokan shipment if it does not already exist. It is typically called as part of a webhook
     * handling mechanism or similar.
     *
     * @since 3.13.0
     *
     * @return void
     */
    public function process() {
        $order_id      = $this->event->rawData['order']['external_id'];
        $shipment_info = $this->event->rawData['shipment'] ?? [];

        $order = dokan()->order->get( $order_id ); // Collect order info.
        if ( ! $order ) { // If no order is found, log this occurrence and exit the method.
            dokan_log( 'No order found for ID: ' . $order_id );
            return;
        }

        $shipment_key         = PrintfulOrderProcessor::META_KEY_PRINTFUL_ORDER_SHIPMENT_DATA;
        $shipment_meta        = $order->get_meta( $shipment_key );
        $shipment_meta        = ! empty( $shipment_meta ) ? $shipment_meta : [];
        $printful_shipment_id = $shipment_info['id'] ?? 0;

        // Check if this Printful shipment has already been processed.
        if ( ! $printful_shipment_id || array_key_exists( $printful_shipment_id, $shipment_meta ) ) {
            return; // Skip processing if no new Printful shipment ID is present or it is already processed.
        }

        try {
            // Populate shipment data for delivery, allowing for modifications via filter.
            $shipment_data = $this->populate_shipment_data( $order_id, $shipment_info );
            $shipment_data = apply_filters( 'dokan_printful_delivered_shipment_data', $shipment_data, $order, $shipment_info );

            // Attempt to create a shipment entry in Dokan.
            $shipment_id = dokan_pro()->shipment->create( $order_id, $shipment_data, $this->vendor_id );

            // If a shipment was successfully created, update the shipment meta data.
            if ( $shipment_id ) {
                $shipment_meta[ $printful_shipment_id ] = [
                    'dokan_shipment_id'    => $shipment_id,
                    'printful_shipment_id' => $printful_shipment_id,
                ];

                // Save the updated shipment metadata to the order.
                $order->update_meta_data( $shipment_key, $shipment_meta );
                $order->save();
            }

            /**
             * Actions for allow to perform actions after Printful shipment processing.
             *
             * @since 3.13.0
             *
             * @param \WC_Order $order
             * @param string    $shipment_status
             * @param array     $shipment_info
             */
            do_action( 'dokan_printful_new_shipment_created', $order, $this->shipment_status, $shipment_info );
        } catch ( \Exception $e ) {
            dokan_log( $e->getMessage() );

            /**
             * Actions for allow to handle Printful shipment creation exceptions.
             *
             * @since 3.13.0
             *
             * @param \Exception $e
             * @param int        $order_id
             * @param string     $shipment_status
             */
            do_action( 'dokan_printful_new_shipment_process_error', $e, $order_id, $this->shipment_status );
        }
    }

    /**
     * Populates and returns shipment data for an order.
     *
     * This method collects the mapped shipment items, prepares the data needed
     * for shipment processing, and returns an array containing this information.
     *
     * @since 3.13.0
     *
     * @param int   $order_id      The ID of the order for which shipment data is being populated.
     * @param array $shipment_info An array containing shipment information.
     *
     * @return array An associative array containing shipment data to be processed.
     */
    protected function populate_shipment_data( int $order_id, array $shipment_info ): array {
        $shipment_item_list = $this->collect_prinful_order_shipment_items();

        $stub = [
            'post_id'           => $order_id,
            'item_id'           => array_keys( $shipment_item_list ),
            'item_qty'          => wp_json_encode( $shipment_item_list ),
            'is_notify'         => 'on',
            'other_p_url'       => $shipment_info['tracking_url'],
            'shipped_date'      => dokan_current_datetime()->setTimestamp( $shipment_info['shipped_at'] )->format( 'c' ),
            'shipped_status'    => $this->shipment_status,
            'other_provider'    => $shipment_info['service'],
            'shipping_number'   => $shipment_info['tracking_number'],
            'shipping_provider' => 'sp-other',
        ];

        // Filters for allow to modify the shipment data.
        return apply_filters( 'dokan_printful_shipment_creation_data', $stub, $order_id, $shipment_info, $this->shipment_status );
    }

    /**
     * Collect order shipment item list for printful product shipment.
     *
     * Maps between order & shipment items based on matching IDs and returns an associative array.
     * The array's key is the order's external ID and the value is the shipment item quantity.
     *
     * @since 3.13.0
     *
     * @return array An associative array where the key is the order's external ID and the value is the shipment item quantity.
     */
    protected function collect_prinful_order_shipment_items(): array {
        $order_items    = $this->event->rawData['order']['items'] ?? [];
        $shipment_items = $this->event->rawData['shipment']['items'] ?? [];

        // Create a map of item_id to quantity for shipment items.
        $shipment_quantities = array_column( $shipment_items, 'quantity', 'item_id' );

        // Match order items with shipment items and create the final array.
        $result = array_reduce(
            $order_items,
            function ( $result, $order_item ) use ( $shipment_quantities ) {
                if ( isset( $shipment_quantities[ $order_item['id'] ] ) ) {
                    $result[ $order_item['external_id'] ] = $shipment_quantities[ $order_item['id'] ];
                }
                return $result;
            },
            []
        );

        // Filters for allow to modify the collected items.
        return apply_filters( 'dokan_printful_collected_shipment_items', $result, $order_items, $shipment_items );
    }
}
