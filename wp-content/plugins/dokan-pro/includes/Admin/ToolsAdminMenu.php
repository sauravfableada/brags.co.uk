<?php

namespace WeDevs\DokanPro\Admin;

use WeDevs\Dokan\Admin\Dashboard\Pages\AbstractPage;

/**
 * Admin Tools menu for Dokan Pro (React based dashboard)
 */
class ToolsAdminMenu extends AbstractPage {

    /**
     * Menu id.
     */
    public function get_id(): string {
        return 'tools';
    }

    /**
     * Menu configuration.
     *
     * @param string $capability
     * @param string $position
     *
     * @return array
     */
    public function menu( string $capability, string $position ): array {
        return [
            'page_title' => __( 'Tools', 'dokan' ),
            'menu_title' => __( 'Tools', 'dokan' ),
            'route'      => 'tools',
            'capability' => $capability,
            // keep at a stable position similar to existing Tools page
            'position'   => 98,
        ];
    }

    /**
     * Menu settings.
     *
     * @return array
     */
    public function settings(): array {
        return [];
    }

    /**
     * Script handles required for the page.
     * We reuse the core admin dashboard bundle which injects the React routes.
     *
     * @return string[]
     */
    public function scripts(): array {
        return [ 'dokan-pro-admin-dashboard' ];
    }

    /**
     * Style handles required for the page.
     *
     * @return string[]
     */
    public function styles(): array {
        return [ 'dokan-pro-admin-dashboard' ];
    }

    /**
     * Register scripts/styles for this page.
     * For core bundle registration is already handled in Assets.php, so nothing to do here.
     */
    public function register(): void {
        // Intentionally left blank.
    }
}
