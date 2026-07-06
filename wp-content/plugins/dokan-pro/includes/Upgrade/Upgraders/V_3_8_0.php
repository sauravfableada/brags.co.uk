<?php

namespace WeDevs\DokanPro\Upgrade\Upgraders;

use WeDevs\DokanPro\Abstracts\DokanProUpgrader;
use WeDevs\DokanPro\Modules\StripeExpress\Processors\Webhook as WebhookProcessor;

class V_3_8_0 extends DokanProUpgrader {

    /**
     * Add _dokan_vendor_id metadata if not exists for subscription orders.
     *
     * @since 3.8.0
     *
     * @return void
     */
    public static function add_vendor_id_for_vps_recurring_orders() {
        if ( ! dokan_pro()->module->is_active( 'vsp' ) ) {
            return;
        }

        if ( ! function_exists( 'wcs_get_subscriptions' ) ) {
            return;
        }
        $i         = 0;
        $processor = new BackgroundProcesses\V_3_8_0();
        $args      = [
            'subscriptions_per_page' => 5,
            'posts_per_page'         => 5,
        ];

        while ( 1 ) {
            $args['offset'] = 5 * $i++;
            $subscriptions  = wcs_get_subscriptions( $args );
            if ( empty( $subscriptions ) ) {
                break;
            }
            $processor->push_to_queue( $subscriptions );
        }
        $processor->dispatch_process();
    }

    /**
     * Store webhook secret for stripe express under payment gateway settings.
     *
     * @since 3.8.0
     *
     * @return void
     */
    public static function add_stripe_express_webhook_secret() {
        if ( ! dokan_pro()->module->is_active( 'stripe_express' ) ) {
            return;
        }

        WebhookProcessor::create();
    }
}
