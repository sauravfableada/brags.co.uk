<?php

namespace WeDevs\DokanPro\Upgrade\Upgraders;

use WeDevs\DokanPro\Abstracts\DokanProUpgrader;
use \WP_Roles;

/**
 * Dokan Pro V_3_8_4 Upgrader Class.
 *
 * @since 3.8.4
 */
class V_3_9_4 extends DokanProUpgrader {

    /**
     * Update Store Phone Verification Info.
     *
     * @since 3.9.4
     *
     * @return void
     */
    public static function dokan_update_auction_product_duplicate_permission() {
        global $wp_roles;

        if ( ! class_exists( 'WP_Roles' ) ) {
            return;
        }

        if ( ! isset( $wp_roles ) ) {
            // @codingStandardsIgnoreLine
            $wp_roles = new WP_Roles();
        }

        $wp_roles->add_cap( 'seller', 'dokan_duplicate_auction_product' );
        $wp_roles->add_cap( 'administrator', 'dokan_duplicate_auction_product' );
        $wp_roles->add_cap( 'shop_manager', 'dokan_duplicate_auction_product' );
    }
}
