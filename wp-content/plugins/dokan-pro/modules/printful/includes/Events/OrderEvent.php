<?php

namespace WeDevs\DokanPro\Modules\Printful\Events;

use Exception;

/**
 * Class OrderEvent.
 *
 * @since 3.13.0
 *
 * @phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 */
class OrderEvent extends AbstractEvent {

    /**
     * @var string $order_note
     *
     * @since 3.13.0
     */
    protected string $order_note;

    /**
     * Process the event.
     *
     * @since 3.13.0
     *
     * @return void
     */
    public function process() {
        $order_id = $this->event->rawData['order']['external_id'];

        try {
            $order = dokan()->order->get( $order_id );
            if ( $order instanceof \WC_Order && ! empty( $this->order_note ) ) {
                $order->add_order_note( $this->order_note );
            }
        } catch ( Exception $e ) {
            dokan_log( $e->getMessage() );
            return;
        }
    }
}
