<?php

namespace WeDevs\DokanPro\Modules\Printful;

use WeDevs\DokanPro\Dependencies\Printful\Structures\Webhook\WebhookItem;
use WeDevs\DokanPro\Modules\Printful\Events\OrderCancel;
use WeDevs\DokanPro\Modules\Printful\Events\OrderCreate;
use WeDevs\DokanPro\Modules\Printful\Events\OrderFailed;
use WeDevs\DokanPro\Modules\Printful\Events\OrderHold;
use WeDevs\DokanPro\Modules\Printful\Events\OrderNeedApprove;
use WeDevs\DokanPro\Modules\Printful\Events\OrderRemoveHold;
use WeDevs\DokanPro\Modules\Printful\Events\OrderUpdate;
use WeDevs\DokanPro\Modules\Printful\Events\ProductDeleted;
use WeDevs\DokanPro\Modules\Printful\Events\ProductSync;
use WeDevs\DokanPro\Modules\Printful\Events\ProductUpdated;
use WeDevs\DokanPro\Modules\Printful\Events\ShipmentCancelled;
use WeDevs\DokanPro\Modules\Printful\Events\ShipmentDelivered;

/**
 * Webhook Handler.
 *
 * @since 3.13.0
 */
class EventsHandler {

    /**
     * @param WebhookItem $event Webhook event.
     *
     * @return void
     */
    public static function handle( WebhookItem $event ) {
        do_action( 'dokan_pro_printful_webhook_handler_before', $event );

        switch ( $event->type ) {
            case WebhookItem::TYPE_PRODUCT_SYNCED:
                new ProductSync( $event );
                break;
            case 'product_updated':
                new ProductUpdated( $event );
                break;
            case 'product_deleted':
                new ProductDeleted( $event );
                break;
            case 'order_created':
                new OrderCreate( $event );
                break;
            case 'order_updated':
                new OrderUpdate( $event );
                break;
            case WebhookItem::TYPE_ORDER_CANCELED:
                new OrderCancel( $event );
                break;
            case WebhookItem::TYPE_ORDER_FAILED:
                new OrderFailed( $event );
                break;
            case WebhookItem::TYPE_ORDER_PUT_HOLD:
                new OrderHold( $event );
                break;
            case WebhookItem::TYPE_ORDER_REMOVE_HOLD:
                new OrderNeedApprove( $event );
                break;
            case 'order_remove_hold':
                new OrderRemoveHold( $event );
                break;
            case 'package_shipped':
                new ShipmentDelivered( $event );
                break;
            case 'package_returned':
                new ShipmentCancelled( $event );
                break;
            default:
                dokan_log( 'no_webhook handler ' . $event->type );
        }

        do_action( 'dokan_pro_printful_webhook_handler_after', $event );
    }
}
