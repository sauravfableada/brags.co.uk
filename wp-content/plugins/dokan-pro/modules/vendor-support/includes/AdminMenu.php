<?php

namespace WeDevs\DokanPro\Modules\VendorSupport;

use WeDevs\Dokan\Admin\Dashboard\Pages\AbstractPage;

class AdminMenu extends AbstractPage {

    /**
     * Menu id.
     *
     * @since 4.1.2
     *
     * @return string
     */
    public function get_id(): string {
        return 'vendor-support';
    }

    /**
     * Menu name.
     *
     * @since 4.1.2
     *
     * @param string $capability
     * @param string $position
     *
     * @return array
     */
    public function menu( string $capability, string $position ): array {
        return [
            'page_title' => __( 'Dokan Vendor Support', 'dokan' ),
            'menu_title' => __( 'Vendor Support', 'dokan' ),
            'route'      => 'vendor-support',
            'capability' => $capability,
            'position'   => 99,
        ];
    }

    /**
     * Menu settings.
     *
     * @since 4.1.2
     *
     * @return array|mixed[]
     */
    public function settings(): array {
        return [];
    }

    /**
     * Script handles.
     *
     * @since 4.1.2
     *
     * @return string[]
     */
    public function scripts(): array {
        return [ 'dokan-vendor-support' ];
    }

    /**
     * Style handles.
     *
     * @since 4.1.2
     *
     * @return string[]
     */
    public function styles(): array {
        return [ 'style-dokan-vendor-support' ];
    }

    /**
     * Register the script.
     *
     * @since 4.1.2
     *
     * @return void
     */
    public function register(): void {
        $asset_file = DOKAN_VENDOR_SUPPORT_DIR . '/assets/js/admin-vendor-support.asset.php';
        if ( ! file_exists( $asset_file ) ) {
            return;
        }
        $asset = include $asset_file;

        wp_register_script(
            'dokan-vendor-support',
            DOKAN_VENDOR_SUPPORT_ASSETS . '/js/admin-vendor-support.js',
            array_merge( $asset['dependencies'], [ 'moment', 'dokan-util-helper', 'dokan-accounting', 'dokan-react-components', 'wc-components' ] ),
            $asset['version'],
            [
                'strategy' => 'defer',
                'in_footer' => true,
            ]
        );

        wp_register_style( 'style-dokan-vendor-support', DOKAN_VENDOR_SUPPORT_ASSETS . '/js/style-admin-vendor-support.css', [ 'wp-components', 'wc-components', 'dokan-react-components' ], $asset['version'] );
    }
}
