<div class="dokan-customer-shipment-list-area">
    <h2><?php esc_html_e( 'Shipments', 'dokan' ); ?></h2>
    <div class="customer-shipment-list-inner-area">
        <?php
        $incre = 1;
        foreach ( $shipment_info as $key => $shipment ) :
            $shipment_id           = $shipment->id;
            $order_id              = $shipment->order_id;
            $provider              = $shipment->provider_label;
            $number                = $shipment->number;
            $status_label          = $shipment->status_label;
            $shipping_status       = $shipment->shipping_status;
            $provider_url          = $shipment->provider_url;
            $item_qty              = json_decode( $shipment->item_qty );
            $shipment_timeline     = dokan_pro()->shipment->custom_get_order_notes( $order_id, $shipment_id );
            $recipient_status      = $order->get_meta( 'dokan_customer_order_receipt_status' );
            $shipment_mark_receive = \WeDevs\DokanPro\Shipping\Helper::is_order_marked_as_received( $order_id, $shipment_id );

            dokan_get_template_part(
                'orders/shipment/html-shipment-list', '', array(
                    'pro'                   => true,
                    'shipment_id'           => $shipment_id,
                    'order_id'              => $order_id,
                    'provider'              => $provider,
                    'number'                => $number,
                    'status_label'          => $status_label,
                    'shipping_status'       => $shipping_status,
                    'provider_url'          => $provider_url,
                    'item_qty'              => $item_qty,
                    'order'                 => $order,
                    'incre'                 => $incre,
                    'customer_status'       => $recipient_status,
                    'is_order_shipped'      => $is_order_shipped,
                    'shipment_timeline'     => $shipment_timeline,
                    'allowed_mark_receive'  => $allowed_mark_receive,
                    'shipment_mark_receive' => $shipment_mark_receive,
                )
            );

            $incre++;
        endforeach;
        ?>
    </div>
</div>
