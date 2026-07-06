<?php

namespace WeDevs\DokanPro\Modules\Printful\Events;

use WeDevs\DokanPro\Dependencies\Printful\Structures\Webhook\WebhookItem;
use WeDevs\DokanPro\Modules\Printful\Auth;

/**
 * Class AbstractEvent.
 *
 * @since 3.13.0
 */
abstract class AbstractEvent {

    /**
     * @var WebhookItem
     */
    protected WebhookItem $event;

    protected Auth $auth;

    protected int $vendor_id = 0;

    /**
     * Constructor.
     */
    public function __construct( WebhookItem $event ) {
        set_time_limit( 0 );
        $this->event = $event;

        try {
            $this->auth = Auth::search( $this->event->store );
        } catch ( \Exception $e ) {
            dokan_log( $e->getMessage(), 'error' );
            return;
        }

        if ( ! $this->auth->is_connected() ) {
            return;
        }

        $this->vendor_id = $this->auth->get_vendor_id();

        $this->process();
    }

    /**
     * Process the event
     *
     * @since 3.13.0
     *
     * @return void
     */
    abstract public function process();
}
