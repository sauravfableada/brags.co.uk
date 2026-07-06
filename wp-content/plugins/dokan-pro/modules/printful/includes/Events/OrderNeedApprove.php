<?php

namespace WeDevs\DokanPro\Modules\Printful\Events;

use WeDevs\DokanPro\Dependencies\Printful\Structures\Webhook\WebhookItem;

class OrderNeedApprove extends OrderEvent {

    /**
     * @var string $order_note
     *
     * @since 3.13.0
     */
    protected string $order_note;

    /**
     * Class constructor.
     *
     * @param WebhookItem $event
     */
    public function __construct( WebhookItem $event ) {
        $this->order_note = esc_html__( 'Printful order was put on hold, now it needs approval.', 'dokan' );
        parent::__construct( $event );
    }
}
