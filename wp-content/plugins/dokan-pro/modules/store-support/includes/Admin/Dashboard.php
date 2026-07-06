<?php

namespace WeDevs\DokanPro\Modules\StoreSupport\Admin;

use StoreSupportHelper;
use WeDevs\Dokan\Admin\Dashboard\Pages\AbstractPage;

/**
 * Admin Dashboard page for Store Support.
 *
 * Inspired by Dokan Lite Withdraw page implementation.
 */
class Dashboard extends AbstractPage {

    /**
     * Get the ID of the page.
     *
     * @since 4.2.1
     *
     * @return string
     */
    public function get_id(): string {
        // Must match the route used elsewhere: admin.php?page=dokan#/admin-store-support
        return 'admin-store-support';
    }

    /**
     * Configure the menu entry.
     *
     * @param string $capability Capability required to access the page.
     * @param string $position   Menu position.
     *
     * @return array
     */
    public function menu( string $capability, string $position ): array {
        $counts        = StoreSupportHelper::get_unread_support_topic_count();
        $unread_ticket = $counts < 1 ? 'display:none;' : '';
        $counts        = $counts > 999 ? __( '999+', 'dokan' ) : $counts;
        $title = sprintf(
        /* translators: 1) two opening span tags, 2) unread tickets count, 3) two closing span tags */
            __( 'Store Support %1$s%2$s%3$s', 'dokan' ),
            '<span class = "awaiting-mod count-1 dokan-unread-ticket-count-in-list" style="' . $unread_ticket . '"><span class="pending-count dokan-unread-ticket-count-badge-in-list">',
            $counts,
            '</span></span>'
        );

        return [
            'page_title' => esc_html__( 'Store Support', 'dokan' ),
            'menu_title' => $title,
            'route'      => 'admin-store-support',
            'capability' => $capability,
            // Keep a later position so it appears after core pages.
            'position'   => 50,
        ];
    }

    /**
     * Settings for the page; none required.
     *
     * @return array
     */
    public function settings(): array {
        return [
            'order_url' => admin_url( 'post.php?action=edit&post=' ),
        ];
    }

    /**
     * Script handles to enqueue for this page.
     *
     * We keep it empty as the Store Support admin app is enqueued centrally
     * via the existing Dokan Vue admin scripts hook.
     *
     * @return array<string>
     */
    public function scripts(): array {
        return [ 'dokan-store-support-admin' ];
    }

    /**
     * Style handles to enqueue for this page.
     *
     * @return array<string>
     */
    public function styles(): array {
        return [];
    }

    /**
     * Register the page scripts and styles.
     *
     * No registration needed here; scripts/styles are handled elsewhere.
     *
     * @return void
     */
    public function register(): void {
        $asset_file_path = DOKAN_STORE_SUPPORT_DIR . '/assets/dist/admin/dashboard/index.asset.php';

        if ( file_exists( $asset_file_path ) ) {
            $asset_file = include $asset_file_path;

            // Register script
            wp_register_script(
                'dokan-store-support-admin',
                DOKAN_STORE_SUPPORT_PLUGIN_ASSEST . '/dist/admin/dashboard/index.js',
                $asset_file['dependencies'] ?? [],
                $asset_file['version'] ?? DOKAN_STORE_SUPPORT_PLUGIN_VERSION,
                [
                    'strategy'  => 'defer',
                    'in_footer' => true,
                ]
            );

            // Set translations
            wp_set_script_translations( 'dokan-store-support-admin', 'dokan' );
        }
    }
}
