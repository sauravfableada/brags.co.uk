<?php

namespace WeDevs\DokanPro\Upgrade\Upgraders;

use WeDevs\DokanPro\Abstracts\DokanProUpgrader;
use WeDevs\DokanPro\Upgrade\Upgraders\BackgroundProcesses\V_3_12_6 as Processor;

defined( 'ABSPATH' ) || exit;

/**
 * V_3_12_6 Upgrader Class.
 *
 * @since 3.13.0
 */
class V_3_12_6 extends DokanProUpgrader {

    /**
     * Update Meta Data for Vendor Subscription Orders.
     *
     * @since 3.13.0
     *
     * @return void
     */
    public static function update_vendor_subscription_orders_meta_data() {
        $processor = new Processor();

        $args = [
            'task'  => 'update_vendor_subscription_orders_meta',
            'paged' => 1,
        ];

        $processor->push_to_queue( $args )->dispatch_process();
    }
}
