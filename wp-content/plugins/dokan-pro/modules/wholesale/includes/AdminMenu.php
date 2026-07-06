<?php

namespace WeDevs\DokanPro\Modules\Wholesale;

use WeDevs\Dokan\Admin\Dashboard\Pages\AbstractPage;

class AdminMenu extends AbstractPage {

    /**
     * Menu id.
     *
     * @since 4.2.0
     *
     * @return string
     */
    public function get_id(): string {
        return 'wholesale-customer';
    }

    /**
     * Menu name.
     *
     * @since 4.2.0
     *
     * @param string $capability
     * @param string $position
     *
     * @return array
     */
    public function menu( string $capability, string $position ): array {
        return [
            'page_title' => __( 'Wholesale Customer', 'dokan' ),
            'menu_title' => __( 'Wholesale Customer', 'dokan' ),
            'route'      => 'wholesale-customer',
            'capability' => $capability,
            'position'   => 99,
        ];
    }

    /**
     * Menu settings.
     *
     * @since 4.2.0
     *
     * @return array|mixed[]
     */
    public function settings(): array {
        return [];
    }

    /**
     * Script handles.
     *
     * @since 4.2.0
     *
     * @return string[]
     */
    public function scripts(): array {
        return [ 'dokan-admin-wholesale-customer' ];
    }

    /**
     * Style handles.
     *
     * @since 4.2.0
     *
     * @return string[]
     */
    public function styles(): array {
        return [];
    }

    /**
     * Register the script.
     *
     * @since 4.2.0
     *
     * @return void
     */
    public function register(): void {
        $asset_file = DOKAN_WHOLESALE_DIR . '/assets/js/admin-wholesale-customer.asset.php';
        if ( ! file_exists( $asset_file ) ) {
            return;
        }
        $asset = include $asset_file;

        wp_register_script(
            'dokan-admin-wholesale-customer',
            DOKAN_WHOLESALE_ASSETS_DIR . '/js/admin-wholesale-customer.js',
            array_merge( $asset['dependencies'], [ 'moment', 'dokan-util-helper', 'dokan-accounting', 'dokan-react-components', 'wc-components' ] ),
            $asset['version'],
            [
                'strategy' => 'defer',
                'in_footer' => true,
            ]
        );

    }
}
