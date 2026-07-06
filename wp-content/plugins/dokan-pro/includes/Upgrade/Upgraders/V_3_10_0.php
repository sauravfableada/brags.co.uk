<?php

namespace WeDevs\DokanPro\Upgrade\Upgraders;

use WeDevs\DokanPro\Abstracts\DokanProUpgrader;
use WP_Roles;

/**
 * Dokan Pro V_3_9_11 Upgrader Class.
 *
 * @since 3.10.0
 */
class V_3_10_0 extends DokanProUpgrader {

    /**
     * Updates inbox menu permission for administrator
     *
     * @since 3.10.0
     *
     * @return void
     */
    public static function dokanUpdateMenuManagerInboxMenuPermission() {
        global $wp_roles;

        if ( ! class_exists( 'WP_Roles' ) ) {
            return;
        }

        if ( ! isset( $wp_roles ) ) {
            // @codingStandardsIgnoreLine
            $wp_roles = new WP_Roles();
        }

        $wp_roles->add_cap( 'administrator', 'dokan_view_inbox_menu' );
    }

    /**
     * Update Stripe Express Webhook Events.
     *
     * @since 3.10.0
     *
     * @return void
     */
    public static function update_stripe_express_webhook_events() {
        if ( ! dokan_pro()->module->is_active( 'stripe_express' ) ) {
            return;
        }

        // delete current webhook
        dokan_pro()->module->stripe_express->webhook->deregister();

        // create new webhook
        dokan_pro()->module->stripe_express->webhook->register();
    }
}
