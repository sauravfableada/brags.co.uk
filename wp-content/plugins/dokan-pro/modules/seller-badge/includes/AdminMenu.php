<?php

namespace WeDevs\DokanPro\Modules\SellerBadge;

use WeDevs\Dokan\Admin\Dashboard\Pages\AbstractPage;

/**
 * Admin Dashboard menu for Seller Badges (React list page).
 *
 * This registers a sidebar menu entry under Dokan > Dashboard and points
 * to our React route `/dokan-seller-badge` which is registered from JS.
 */
class AdminMenu extends AbstractPage {

    /**
     * Menu id.
     *
     * @since 4.2.0
     *
     * @return string
     */
    public function get_id(): string {
        return 'seller-badges';
    }

    /**
     * Menu parameters.
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
            'page_title' => __( 'Seller Badge', 'dokan' ),
            'menu_title' => __( 'Seller Badge', 'dokan' ),
            'route'      => 'dokan-seller-badge',
            'capability' => $capability,
            'position'   => 98,
        ];
    }

    /**
     * Settings for the page. Not used.
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
     * Return the admin script handle so Dokan dashboard can enqueue it for this page.
     *
     * @since 4.2.0
     *
     * @return array
     */
    public function scripts(): array {
        return [ 'dokan-seller-badges-admin' ];
    }

    /**
     * Style handles.
     *
     * @since 4.2.0
     *
     * @return array
     */
    public function styles(): array {
        return [ 'style-dokan-seller-badges-admin' ];
    }

    /**
     * Register assets.
     *
     * @since 4.2.0
     *
     * @return void
     */
    public function register(): void {
        $asset_file = DOKAN_SELLER_BADGE_DIR . '/assets/js/admin-seller-badges.asset.php';
        if ( ! file_exists( $asset_file ) ) {
            return;
        }

        $asset = include $asset_file;

        wp_register_script(
            'dokan-seller-badges-admin',
            DOKAN_SELLER_BADGE_ASSETS . '/js/admin-seller-badges.js',
            array_merge( $asset['dependencies'], [ 'moment', 'dokan-util-helper', 'dokan-accounting', 'dokan-react-components', 'wc-components' ] ),
            $asset['version'],
            [
                'strategy' => 'defer',
                'in_footer' => true,
            ]
        );

        wp_register_style( 'style-dokan-seller-badges-admin', DOKAN_SELLER_BADGE_ASSETS . '/js/style-admin-seller-badges.css', [ 'wp-components', 'wc-components', 'dokan-react-components' ], $asset['version'] );

    }
}
