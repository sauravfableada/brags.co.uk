<?php

namespace WeDevs\DokanPro\Dashboard\ManualOrders;

/**
 * Class Menus
 *
 * Handles navigation menu for manual orders functionality
 *
 * @since 4.0.0
 */
class Menus {
    /**
     * Constructor.
     *
     * Sets up hooks for navigation.
     */
    public function __construct() {
        add_filter( 'dokan_get_dashboard_nav', [ $this, 'add_new_order_submenu' ], 10, 2 );
        add_filter( 'dokan_dashboard_nav_active', [ $this, 'update_dashboard_active_nav' ] );
    }

    /**
     * Adds a new order menu item to the Dokan dashboard navigation.
     *
     * @since 4.0.0
     *
     * @param array $menus Existing submenu items.
     *
     * @return array Modified submenu items.
     */
    public function add_new_order_submenu( array $menus ): array {
        if ( ! isset( $menus['orders'] ) ) {
            return $menus;
        }

        // Check if the 'all' submenu item exists
        if ( ! isset( $menus['orders']['submenu']['all'] ) ) {
            // If it doesn't exist, create a new 'orders' submenu item
            $menus['orders']['submenu']['all'] = $menus['orders'];

            // Set the title and URL for the 'all' submenu item
            $menus['orders']['submenu']['all']['pos']   = 30;
            $menus['orders']['submenu']['all']['title'] = esc_html__( 'All Orders', 'dokan' );
        }

        // Add the new order submenu item
        $menus['orders']['submenu']['order-new'] = [
            'title'       => esc_html__( 'Add New Order', 'dokan' ),
            'icon'        => '<i class="fas fa-cart-plus"></i>',
            'url'         => dokan_get_navigation_url( 'orders/new' ),
            'pos'         => 50,
            'permission'  => 'dokan_manage_manual_order',
            'react_route' => 'orders/new',
        ];

        return $menus;
    }

    /**
     * Updates the active navigation item for the dashboard.
     *
     * @since 4.0.0
     *
     * @param string $active_menu Active menu item.
     *
     * @return string Updated active menu item.
     */
    public function update_dashboard_active_nav( string $active_menu ): string {
        if ( 'orders' === $active_menu ) {
            return 'orders/all';
        }

        return $active_menu;
    }
}
