<?php

namespace WeDevs\DokanPro\Upgrade\Upgraders;

use WeDevs\DokanPro\Abstracts\DokanProUpgrader;
use WeDevs\DokanPro\Upgrade\Upgraders\BackgroundProcesses\V_3_8_3_UpdateStorePhoneVerificationInfo;

/**
 * Dokan Pro V_3_8_3 Upgrader Class.
 *
 * @since 3.8.3
 */
class V_3_8_3 extends DokanProUpgrader {

    /**
     * Update Store Phone Verification Info.
     *
     * @since 3.8.3
     *
     * @return void
     */
    public static function dokan_update_store_phone_verification_info() {
        $processor = new V_3_8_3_UpdateStorePhoneVerificationInfo();

        $args = [
            'updating' => 'store_phone_verification_info',
            'paged'    => 0,
        ];

        $processor->push_to_queue( $args )->dispatch_process();
    }
}
