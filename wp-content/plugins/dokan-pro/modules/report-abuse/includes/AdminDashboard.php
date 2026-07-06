<?php

namespace WeDevs\DokanPro\Modules\ReportAbuse;

use WeDevs\Dokan\Admin\Dashboard\Pages\AbstractPage;

class AdminDashboard extends AbstractPage {

    /**
     * Get the ID of the page.
     *
     * @since @4.2.0
     *
     * @return string
     */
    public function get_id(): string {
        return 'abuse-reports';
    }

    /**
     * Get the menu arguments.
     *
     * @since @4.2.0
     *
     * @param  string  $capability Menu capability.
     * @param  string  $position Menu position.
     *
     * @return array<string, string|int> An array of associative arrays with keys 'route', 'page_title', 'menu_title', 'capability', 'position'.
     */
    public function menu( string $capability, string $position ): array {
        return [
            'page_title' => __( 'Abuse Reports', 'dokan' ),
            'menu_title' => __( 'Abuse Reports', 'dokan' ),
            'route'      => 'abuse-reports',
            'capability' => $capability,
            'position'   => 30,
        ];
    }

    /**
     * Get the settings values.
     *
     * @since @4.2.0
     *
     * @return array<string,mixed> An array of settings values.
     */
    public function settings(): array {
        return [];
    }

    /**
     * Get the scripts.
     *
     * @since @4.2.0
     *
     * @return array<string> An array of script handles.
     */
    public function scripts(): array {
        return [ 'dokan-report-abuse-admin-dashboard' ];
    }

    /**
     * Get the styles.
     *
     * @since @4.2.0
     *
     * @return array<string> An array of style handles.
     */
    public function styles(): array {
        return [];
    }

    /**
     * Register the page scripts and styles.
     *
     * @since @4.2.0
     *
     * @return void
     */
    public function register(): void {
        $asset_file = include DOKAN_REPORT_ABUSE_PATH . '/assets/js/admin/dashboard/index.asset.php';

        wp_register_script(
            'dokan-report-abuse-admin-dashboard',
            DOKAN_REPORT_ABUSE_ASSETS . '/js/admin/dashboard/index.js',
            $asset_file['dependencies'] ?? [],
            $asset_file['version'] ?? DOKAN_PRO_PLUGIN_VERSION,
            [
                'strategy'  => 'defer',
                'in_footer' => true,
            ]
        );

    }
}
