<?php
namespace WeDevs\DokanPro\MenuManager\Admin;

defined( 'ABSPATH' ) || exit;

use WeDevs\DokanPro\MenuManager\Constants;

/**
 * Handles Menu manager admin settings configurations
 *
 * @since 3.10.0
 */
class Settings {

    /**
     * Initializes all hooks
     *
     * @since 3.10.0
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'dokan_settings_sections', [ $this, 'add_menu_manager_section' ], 11 );
        add_filter( 'dokan_settings_fields', [ $this, 'add_menu_manager_section_fields' ], 99 );
    }

    /**
     * Adds menu manager section in dokan admin dashboard settings
     *
     * @since 3.10.0
     *
     * @param array $settings_sections
     *
     * @return array
     */
    public function add_menu_manager_section( array $settings_sections ): array {
        $menu_manager_section = [
            'id'                   => Constants::DOKAN_MENU_MANAGER,
            'title'                => __( 'Menu Manager', 'dokan' ),
            'icon_url'             => DOKAN_PRO_PLUGIN_ASSEST . '/images/admin-settings-icons/menu-manager.svg',
            'description'          => __( 'Vendor Dashboard Menu Appearance', 'dokan' ),
            'document_link'        => 'https://dokan.co/docs/wordpress/dokan-dashboard/vendor-dashboard-menu-manager/',
            'settings_title'       => __( 'Menu Manager Settings', 'dokan' ),
            'settings_description' => __( 'Reorder, Rename, Activate, and Deactivate menus for your vendor dashboard.', 'dokan' ),
        ];

        // Adding Menu Manager after Appearance Settings
        $new_settings = [];
        foreach ( $settings_sections as $value ) {
            $new_settings[] = $value;
            if ( Constants::APPEARANCE === $value['id'] ) {
                $new_settings[] = $menu_manager_section;
            }
        }

        return $new_settings;
    }

    /**
     * Adds Fields under menu manager settings fields
     *
     * @since 3.10.0
     *
     * @param array $settings_fields
     *
     * @return array
     */
    public function add_menu_manager_section_fields( array $settings_fields ): array {
        $filter = function ( bool $status ) {
            return true;
        };
        add_filter( 'dokan_is_seller_enabled', $filter );
        $nav_menu_list = dokan_get_dashboard_nav();
        remove_filter( 'dokan_is_seller_enabled', $filter );

        $settings_menu_list = $nav_menu_list['settings'][ Constants::SUBMENU ];
        $left_menu_list     = $nav_menu_list;
        unset( $left_menu_list[ Constants::SUBMENU ] );

        // Removing settings menu from left menu portion
        unset( $left_menu_list['settings'] );

        // Dashboard is not switchable or sortable
        $left_menu_list['dashboard']['switchable']  = false;
        $left_menu_list['dashboard']['is_sortable'] = false;

        // Settings/Store is not switchable or sortable
        $settings_menu_list['store']['switchable']  = false;
        $settings_menu_list['store']['is_sortable'] = false;

        $settings_fields[ Constants::DOKAN_MENU_MANAGER ] = [
            'menu_manager_menu_tab' => [
                'name'               => 'menu_manager_menu_tab',
                'type'               => 'sub_tab',
                'content_class'      => 'sub-tab-styles',
                'tabs'               => [
                    'left_menus'        => [
                        'name'     => 'left_menus',
                        'label'    => __( 'Left Menu', 'dokan' ),
                        'selected' => true,
                        'fields'   => $left_menu_list,

                    ],
                    'settings_sub_menu' => [
                        'name'     => 'settings_sub_menu',
                        'label'    => __( 'Settings Sub Menu', 'dokan' ),
                        'selected' => false,
                        'fields'   => $settings_menu_list,
                    ],
                ],
                'refresh_after_save' => true,
            ],
        ];

        return apply_filters( 'dashboard_menu_manager_before_admin_settings', $settings_fields );
    }
}
