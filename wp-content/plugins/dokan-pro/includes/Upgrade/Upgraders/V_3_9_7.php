<?php

namespace WeDevs\DokanPro\Upgrade\Upgraders;

use WeDevs\DokanPro\Abstracts\DokanProUpgrader;
use WeDevs\DokanPro\Upgrade\Upgraders\BackgroundProcesses\V_3_9_7 as Processor;

/**
 * Dokan Pro V_3_9_7 Upgrader Class.
 *
 * @since 3.9.7
 */
class V_3_9_7 extends DokanProUpgrader {

    /**
     * Update Store Phone Verification Info.
     *
     * @since 3.9.7
     *
     * @return void
     */
    public static function update_stripe_express_account_info() {
        if ( ! dokan_pro()->module->is_active( 'stripe_express' ) ) {
            return;
        }

        // get 10 vendor ids on a while loop
        $i         = 0;
        $processor = new Processor();

        while ( true ) {
            $vendor_ids = dokan()->vendor->get_vendors(
                [
                    'number' => 10,
                    'offset' => $i * 10,
                    'fields' => 'ID',
                ]
            );

            if ( empty( $vendor_ids ) ) {
                break;
            }

            $data = [
                'task'       => 'update_stripe_express_account_info',
                'vendor_ids' => $vendor_ids,
            ];

            $processor->push_to_queue( $data );

            ++$i;
        }

        $processor->dispatch_process();
    }

    /**
     * Update Stripe Express Webhook Events.
     *
     * @since 3.9.7
     *
     * @return void
     */
    public static function update_stripe_express_webhook_events() {
        if ( ! dokan_pro()->module->is_active( 'stripe_express' ) ) {
            return;
        }

        dokan_pro()->module->stripe_express->webhook->register();
    }
}
