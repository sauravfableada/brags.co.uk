<?php

namespace WeDevs\DokanPro\Modules\Printful\Events;

use WeDevs\DokanPro\Dependencies\Printful\Structures\Webhook\WebhookItem;
use WeDevs\DokanPro\Modules\Printful\Processors\PrintfulOrderProcessor;

class ShipmentCancelled extends AbstractEvent {

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
        $this->shipment_status = 'ss_cancelled';
        parent::__construct( $event );
    }

	/**
	 * Processes shipment cancellation based on external data from Printful.
	 *
	 * This method checks for shipment cancellation data from Printful and updates the
	 * corresponding Dokan shipment status accordingly. It handles the shipment data
	 * to ensure proper status updates and performs cleanup actions if necessary.
	 *
	 * @since 3.13.0
	 *
	 * @return void
	 * @throws \Exception
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

        // Early exit if there's no new shipment data or if it's already processed.
        if ( ! $printful_shipment_id || ! array_key_exists( $printful_shipment_id, $shipment_meta ) ) {
            return;
        }

        // Retrieve existing shipment tracking information.
        $shipment_id            = $shipment_meta[ $printful_shipment_id ]['dokan_shipment_id'] ?? 0;
        $shipment_tracking_info = dokan_pro()->shipment->get_shipping_tracking_info( $shipment_id, 'shipment_item' );

        // Check if the shipment status needs to be updated.
        $old_shipment_status = $shipment_tracking_info->shipping_status ?? '';
        if ( $old_shipment_status === $this->shipment_status ) {
            return;
        }

        try {
            // Prepare the new shipment data, allowing modification through filters.
            $shipment_data = $this->populate_shipment_data( $order_id, $shipment_info );
            $shipment_data = apply_filters( 'dokan_printful_cancelled_shipment_data', $shipment_data, $order, $shipment_info );

            // Attempt to update the shipment and check if it has been cancelled.
            $tracking_info = dokan_pro()->shipment->update( $shipment_id, $order_id, $shipment_data, $this->vendor_id );
            if ( $tracking_info->shipping_status === $this->shipment_status ) {
                unset( $shipment_meta[ $printful_shipment_id ] ); // Remove shipment data if the shipment is cancelled.
                $order->update_meta_data( $shipment_key, $shipment_meta );
                $order->save();
            } else {
	            dokan_log( esc_html__( 'Shipment tracking status need to be cancelled.', 'dokan' ) );
            }

            /**
             * Fires after a shipment is processed, allowing further actions to be triggered.
             *
             * @since 3.13.0
             *
             * @param \WC_Order $order
             * @param string    $shipment_status
             * @param array     $shipment_info
             */
            do_action( 'dokan_printful_shipment_cancelled', $order, $this->shipment_status, $shipment_info );
        } catch ( \Exception $e ) {
            dokan_log( $e->getMessage() );

            /**
             * Actions for allow to handle Printful shipment exceptions.
             *
             * @since 3.13.0
             *
             * @param \Exception $e
             * @param int        $order_id
             * @param string     $shipment_status
             */
            do_action( 'dokan_printful_shipment_cancelled_process_error', $e, $order_id, $this->shipment_status );
        }
    }

    /**
     * Populates and returns shipment data for an order.
     *
     * This method collects the mapped shipment items, prepares the data needed
     * for shipment processing|cancelling, and returns an array containing this information.
     *
     * @since 3.13.0
     *
     * @param int   $order_id      The ID of the order for which shipment data is being populated.
     * @param array $shipment_info An array containing shipment information.
     *
     * @return array An associative array containing shipment data to be processed.
     */
    protected function populate_shipment_data( int $order_id, array $shipment_info ): array {
        $stub = [
            'is_notify'              => 'on',
            'shipping_provider'      => 'sp-other',
            'shipped_status'         => $this->shipment_status,
            'status_other_provider'  => $shipment_info['service'],
            'status_other_p_url'     => $shipment_info['tracking_url'],
            'tracking_status_number' => $shipment_info['tracking_number'],
            'shipped_status_date'    => dokan_current_datetime()->setTimestamp( $shipment_info['shipped_at'] )->format( 'c' ),
        ];

        // Filters for allow to modify the shipment data.
        return apply_filters( 'dokan_printful_shipment_cancellation_data', $stub, $order_id, $shipment_info, $this->shipment_status );
    }
}
