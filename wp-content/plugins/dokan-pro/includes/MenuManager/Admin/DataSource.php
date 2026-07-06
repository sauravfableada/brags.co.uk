<?php
namespace WeDevs\DokanPro\MenuManager\Admin;

defined( 'ABSPATH' ) || exit;

use WeDevs\DokanPro\MenuManager\Constants;

/**
 * Manages Dashboard Menu Manager data
 *
 * @since 3.10.0
 */
class DataSource {

    /**
     * Initializes all hooks
     *
     * @since 3.10.0
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'dokan_save_settings_value', [ $this, 'save_admin_settings' ], 99, 2 );
    }

    /**
     * Filters to be saved data
     *
     * @since 3.10.0
     *
     * @param array  $option_value
     * @param string $option_name
     *
     * @return array
     */
    public function save_admin_settings( array $option_value, string $option_name ): array {
        $filtered_value = [];
        if ( Constants::DOKAN_MENU_MANAGER === $option_name && ! empty( $option_value[ Constants::MENU_MANAGER_OPTIONS ] ) ) {
            foreach ( $option_value[ Constants::MENU_MANAGER_OPTIONS ] as $key => $value ) {
                if ( in_array( $key, [ 'left_menus', 'settings_sub_menu' ], true ) ) {
                    $filtered_value[ $key ] = $value;
                }
            }
        }
        $option_value[ Constants::MENU_MANAGER_OPTIONS ] = $this->filter_title( $filtered_value );

        return $option_value;
    }

    /**
     * Filters user input title
     *
     * @since 3.10.0
     *
     * @param array $data
     *
     * @return array
     */
    protected function filter_title( array $data ): array {
        return array_map(
            function ( $menu_items ) {
                return array_map(
                    function ( $menu_item ) {
                        $title = trim( $menu_item['menu_manager_title'] );
                        $previous_title = trim( $menu_item['previous_title'] );

                        if ( ! empty( $title ) ) {
                            $menu_item['menu_manager_title'] = $title;
                        } elseif ( ! empty( $previous_title ) ) {
                            $menu_item['menu_manager_title'] = $previous_title;
                        } else {
                            $menu_item['menu_manager_title'] = $menu_item['title'];
                            $menu_item['previous_title'] = $menu_item['title'];
                        }

                        return $menu_item;
                    },
                    $menu_items
                );
            },
            $data
        );
    }
}
