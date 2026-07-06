<?php

namespace WeDevs\DokanPro\Modules\Printful;

/**
 * Class Installer
 *
 * @since 3.13.0
 */
class Installer {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->add_capabilities();
    }

    /**
     * Add capabilities to roles.
     *
     * @since 3.13.0
     *
     * @return void
     */
    protected function add_capabilities() {
        global $wp_roles;

        if ( class_exists( 'WP_Roles' ) && ! isset( $wp_roles ) ) {
            $wp_roles = new \WP_Roles();
        }

        $wp_roles->add_cap( 'seller', 'dokan_view_store_printful_menu' );
        $wp_roles->add_cap( 'administrator', 'dokan_view_store_printful_menu' );
        $wp_roles->add_cap( 'shop_manager', 'dokan_view_store_printful_menu' );

        $wp_roles->add_cap( 'seller', 'dokan_view_store_printful_settings_menu' );
        $wp_roles->add_cap( 'administrator', 'dokan_view_store_printful_settings_menu' );
        $wp_roles->add_cap( 'shop_manager', 'dokan_view_store_printful_settings_menu' );
    }
}
