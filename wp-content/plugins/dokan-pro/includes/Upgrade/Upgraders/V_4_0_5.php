<?php

namespace WeDevs\DokanPro\Upgrade\Upgraders;

use WeDevs\DokanPro\Abstracts\DokanProUpgrader;

class V_4_0_5 extends DokanProUpgrader {

    /**
     * Removed duplicate rows from dokan_delivery_time table.
     *
     * @since 4.0.5
     *
     * @return void
     */
    public static function delete_dokan_delivery_time_duplicate_rows() {
        global $wpdb;

        $table = $wpdb->prefix . 'dokan_delivery_time';

        $wpdb->query(
            $wpdb->prepare(
                'DELETE t1 FROM %i t1
                 INNER JOIN %i t2
                 WHERE
                 t1.id > t2.id
                 AND t1.order_id = t2.order_id
                 AND t1.vendor_id = t2.vendor_id
                 AND t1.date = t2.date
                 AND t1.slot = t2.slot
                 AND t1.delivery_type = t2.delivery_type',
                $table,
                $table
            )
        );
    }
}
