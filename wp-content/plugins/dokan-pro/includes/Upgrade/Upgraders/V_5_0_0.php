<?php

namespace WeDevs\DokanPro\Upgrade\Upgraders;

use WeDevs\DokanPro\Abstracts\DokanProUpgrader;

class V_5_0_0 extends DokanProUpgrader {

    /**
     * Grant delivery time capabilities to seller and administrator roles
     * for sites that already have the module active.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public static function add_delivery_time_capabilities() {
        if ( ! dokan_pro()->module->is_active( 'delivery_time' ) ) {
            return;
        }

        dokan_pro()->module->delivery_time->dt_vendor->add_role_capabilities();
    }
}
