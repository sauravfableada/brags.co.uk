<?php

namespace WeDevs\DokanPro\Upgrade\Upgraders;

use WeDevs\DokanPro\Abstracts\DokanProUpgrader;
use WeDevs\DokanPro\Upgrade\Upgraders\BackgroundProcesses\V_3_9_0 as Processor;

/**
 * Dokan Pro V_3_9_0 Upgrader Class.
 *
 * @since 3.9.0
 */
class V_3_9_0 extends DokanProUpgrader {

    /**
     * Update Product Status to Publish from Vacation.
     *
     * @since 3.9.0
     *
     * @return void
     */
    public static function update_product_status_to_publish_from_vacation() {
        $processor = new Processor();

        $args = [
            'task'  => 'set_product_status_to_publish_from_vacation',
            'paged' => 0,
        ];

        $processor->push_to_queue( $args )->dispatch_process();
    }

    /**
     * Adds id key in woocommerce product addon.
     *
     * @since 3.9.0
     *
     * @return void
     */
    public static function add_id_in_woocommerce_product_addon() {
        global $wpdb;
        $i         = 0;
        $limit     = 20;
        $processor = new Processor();

        while ( true ) {
            $data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = '_product_addons' ORDER BY meta_id DESC LIMIT %d OFFSET %d;", $limit, $i ), ARRAY_A );

            if ( empty( $data ) ) {
                break;
            }

            $args = [
                'task' => 'add_id_in_woocommerce_product_addon',
                'data' => $data,
            ];

            // phpcs:ignore Universal.Operators.DisallowStandalonePostIncrementDecrement.PostIncrementFound
            $i += $limit;
            $processor->push_to_queue( $args );
        }

        $processor->dispatch_process();
    }
}
