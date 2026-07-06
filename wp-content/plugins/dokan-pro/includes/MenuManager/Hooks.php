<?php
namespace WeDevs\DokanPro\MenuManager;

defined( 'ABSPATH' ) || exit;

/**
 * Menu Manager settings field manager
 *
 * @since 3.10.0
 */
class Hooks {

    /**
     * Invokes settings hook
     *
     * @since 3.10.0
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'dokan_get_dashboard_nav', [ $this, 'dashboard_menu_navigation_update_data' ], 99, 1 );
        add_action( 'dokan_dashboard_shortcode_query_vars', [ AccessRestriction::class, 'restrict_access' ], 9, 1 );
        add_action( 'dokan_dashboard_widget_applicable', [ AccessRestriction::class, 'validateWidgetRestriction' ], 99, 2 );
    }

    /**
     * Updates navigation array data to accommodate menu Manager.
     *
     * @since 3.10.0
     *
     * @param array $navigations
     *
     * @return array
     */
    public function dashboard_menu_navigation_update_data( $navigations ) {
        $menu_manager_settings = dokan_get_option( Constants::MENU_MANAGER_OPTIONS, Constants::DOKAN_MENU_MANAGER, [] );

        if ( empty( $menu_manager_settings ) ) {
            return $navigations;
        }

        $saved_left_menu_settings = $menu_manager_settings['left_menus'] ?? [];
        $saved_settings_sub_menu  = $menu_manager_settings['settings_sub_menu'] ?? [];

        foreach ( $navigations as $key => &$navigation ) {
            if ( 'settings' !== $key ) {
                if ( ! isset( $saved_left_menu_settings[ $key ] ) ) {
                    $navigation['is_switched_on']        = true; // switched on by default
                    $navigation['menu_manager_position'] = $navigation['pos'];
                    $navigation['static_pos']            = $navigation['pos'];
                    continue;
                }

                // Switched on
                $navigation['is_switched_on']        = wc_string_to_bool( $saved_left_menu_settings[ $key ]['is_switched_on'] );

                // Position
                $navigation['menu_manager_position'] = $saved_left_menu_settings[ $key ]['menu_manager_position'];
                $navigation['static_pos']            = $navigation['pos'];
                $navigation['pos']                   = $saved_left_menu_settings[ $key ]['menu_manager_position'];

                // Menu Manager Title
                $navigations[ $key ]['menu_manager_title'] = $saved_left_menu_settings[ $key ]['menu_manager_title'] ?? '';
            } else {
                $position_value = PHP_INT_MAX;
                foreach ( $navigation[ Constants::SUBMENU ] as $settings_submenu_key => &$settings_submenu_item ) {
                    // check if submenu is available
                    if ( ! isset( $saved_settings_sub_menu[ $settings_submenu_key ] ) ) {
                        $settings_submenu_item['is_switched_on']        = true;
                        $settings_submenu_item['menu_manager_position'] = $settings_submenu_item['pos'];
                        $settings_submenu_item['static_pos']            = $settings_submenu_item['pos'];
                        continue;
                    }

                    // Switched On
                    $settings_submenu_item['is_switched_on'] = wc_string_to_bool( $saved_settings_sub_menu[ $settings_submenu_key ]['is_switched_on'] );

                    // Position
                    $settings_submenu_item['menu_manager_position'] = $saved_settings_sub_menu[ $settings_submenu_key ]['menu_manager_position'];
                    $settings_submenu_item['static_pos']            = $settings_submenu_item['pos'];
                    $settings_submenu_item['pos']                   = $saved_settings_sub_menu[ $settings_submenu_key ]['menu_manager_position'];

                    // Menu Manager Title
                    $settings_submenu_item['menu_manager_title'] = $saved_settings_sub_menu[ $settings_submenu_key ]['menu_manager_title'] ?? '';

                    if (
                        $settings_submenu_item['is_switched_on']
                        && $position_value > $settings_submenu_item['pos']
                    ) {
                        $navigations[ $key ]['url'] = $settings_submenu_item['url'];
                        $position_value             = $settings_submenu_item['pos'];
                    }
                }
            }
        }

        return $navigations;
    }
}
