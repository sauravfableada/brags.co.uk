<?php

namespace WeDevs\DokanPro\Modules\ProductAdvertisement\Admin;

use WeDevs\Dokan\Admin\Dashboard\Pages\AbstractPage;

class AdminMenu extends AbstractPage {

    public function __construct() {
        $this->register_hooks();
    }

    /**
     * Menu id.
     *
     * @since 4.2.0
     *
     * @return string
     */
    public function get_id(): string {
        return 'product-advertising';
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
            'page_title' => __( 'Advertising', 'dokan' ),
            'menu_title' => __( 'Advertising', 'dokan' ),
            'route'      => 'product-advertising',
            'capability' => $capability,
            'position'   => 99,
        ];
    }

    /**
     * Menu settings.
     *
     * @since 4.2.0
     *
     * @return array
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
        return [ 'dokan-product-adv-admin-react' ];
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
        $asset_file = DOKAN_PRODUCT_ADV_DIR . '/assets/js/admin-product-adv.asset.php';
        if ( ! file_exists( $asset_file ) ) {
            return;
        }
        $asset = include $asset_file;

        $res = wp_register_script(
            'dokan-product-adv-admin-react',
            DOKAN_PRODUCT_ADV_ASSETS . '/js/admin-product-adv.js',
            array_merge( $asset['dependencies'], [] ),
            $asset['version'],
            true
        );

    }
}
