<?php

namespace WeDevs\DokanPro\Upgrade\Upgraders;

use WeDevs\DokanPro\Abstracts\DokanProUpgrader;
use WeDevs\DokanPro\Upgrade\Upgraders\BackgroundProcesses\V_3_11_1 as Processor;

defined( 'ABSPATH' ) || exit;

/**
 * Migrate Previously submitted Verification requests.
 *
 * @since 3.11.1
 */
class V_3_11_1 extends DokanProUpgrader {

    /**
     * Migrate the Previous submissions.
     *
     * @since 3.11.1
     *
     * @return void
     */
    public static function migrate_verification_submissions() {
        if ( ! dokan_pro()->module->is_active( 'vendor_verification' ) ) {
            return;
        }

        // Reinitializing the module to run installer hooks and process.
        dokan_pro()->module->vendor_verification->installer->run();

        $processor = new Processor();

        $args = [
            'task'  => 'create_verification_submission',
            'paged' => 1,
        ];

        $processor->push_to_queue( $args )->dispatch_process();
    }
}
