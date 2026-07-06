<?php

namespace WeDevs\DokanPro\Admin\License;

use WeDevs\Dokan\Admin\Dashboard\Pages\AbstractPage;

/**
 * Class AdminMenu
 *
 * Handles the administration menu functionalities for the Dokan plugin's license feature.
 */
class AdminMenu extends AbstractPage {

    /**
     * Registers hooks for the license admin menu.
     *
     * @since 4.2.0
     *
     * @return void
     */
    public function register_hooks(): void {
        parent::register_hooks();
    }

    /**
     * Retrieves the identifier.
     *
     * @since 4.2.0
     *
     * @return string The identifier string.
     */
    public function get_id(): string {
        return 'license';
    }

    /**
     * Generates the menu configuration for the license manager.
     *
     * @since 4.2.0
     *
     * @param string $capability The capability required to access this menu.
     * @param string $position   The position of the menu item in the WordPress admin menu.
     *
     * @return array The menu configuration array.
     */
    public function menu( string $capability, string $position ): array {
        return [
            'page_title' => __( 'Dokan License Manager', 'dokan' ),
            'menu_title' => __( 'License', 'dokan' ),
            'route'      => 'license',
            'capability' => 'manage_woocommerce',
            'position'   => 100,
        ];
    }

    /**
     * Retrieves the settings array.
     *
     * @since 4.2.0
     *
     * @return array The settings data.
     */
    public function settings(): array {
        return [];
    }

    /**
     * Retrieves the list of script handles.
     *
     * @since 4.2.0
     *
     * @return array The array of script handles.
     */
    public function scripts(): array {
        return [ 'dokan-license-manager' ];
    }

    /**
     * Retrieves the list of styles.
     *
     * @since 4.2.0
     *
     * @return array The array containing style identifiers.
     */
    public function styles(): array {
        return [ 'dokan-license-manager' ];
    }

    /**
     * Registers the scripts and styles required for the license manager.
     *
     * This method ensures that the necessary JavaScript and CSS assets
     * for the admin license manager are properly registered with WordPress.
     *
     * @since 4.2.0
     *
     * @return void
     */
    public function register(): void {
        $asset_file = plugin_dir_path( DOKAN_PRO_FILE ) . 'assets/js/admin-license-manager.asset.php';
        if ( ! file_exists( $asset_file ) ) {
            return;
        }
        $asset = include $asset_file;

        wp_register_script(
            'dokan-license-manager',
            DOKAN_PRO_PLUGIN_ASSEST . '/js/admin-license-manager.js',
            array_merge( $asset['dependencies'], [ 'dokan-react-components', 'wc-components' ] ),
            $asset['version'],
            [
                'strategy'  => 'defer',
                'in_footer' => true,
            ]
        );

        $css = plugin_dir_path( DOKAN_PRO_FILE ) . 'assets/js/style-admin-license-manager.css';
        if ( file_exists( $css ) ) {
            wp_register_style(
                'dokan-license-manager',
                DOKAN_PRO_PLUGIN_ASSEST . '/js/style-admin-license-manager.css',
                [ 'wp-components', 'wc-components', 'dokan-react-components' ],
                $asset['version']
            );
        }
    }
}
