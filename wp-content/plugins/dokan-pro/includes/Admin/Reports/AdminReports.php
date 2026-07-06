<?php

namespace WeDevs\DokanPro\Admin\Reports;

use WeDevs\Dokan\Admin\Dashboard\Pages\AbstractPage;
use WeDevs\DokanPro\REST\AdminReportEarningsController;
use WeDevs\DokanPro\REST\AdminReportLogsController;

/**
 * Reports Page Class.
 *
 * @since 5.0.0
 */
class AdminReports extends AbstractPage {

    /**
     * Registers hooks to handle the admin notice.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register_hooks(): void {
        parent::register_hooks();

        add_filter( 'dokan_admin_notices', [ $this, 'render_admin_reports_data_migration_notice' ] );
        add_action( 'wp_ajax_dokan_ask_for_report_notice_action', [ $this, 'handle_admin_report_notice_dismiss' ] );
    }

    /**
     * Get the ID of the page.
     *
     * @since 5.0.0
     *
     * @return string
     */
    public function get_id(): string {
        return 'reports';
    }

    /**
     * Get the menu configuration.
     *
     * @since 5.0.0
     *
     * @param string $capability The capability required to view the menu.
     * @param string $position   The position of the menu.
     *
     * @return array
     */
    public function menu( string $capability, string $position ): array {
        return [
            'page_title' => esc_html__( 'Reports', 'dokan' ),
            'menu_title' => esc_html__( 'Reports', 'dokan' ),
            'route'      => 'reports',
            'capability' => $capability,
            'position'   => 60,
        ];
    }

    /**
     * Get the settings.
     *
     * @since 5.0.0
     *
     * @return array
     */
    public function settings(): array {
        return apply_filters(
            'dokan_admin_reports_settings',
            [
                'earnings' => ( new AdminReportEarningsController() )->get_export_columns(),
                'logs'     => ( new AdminReportLogsController() )->get_export_columns(),
            ]
        );
    }

    /**
     * Get the scripts.
     *
     * @since 5.0.0
     *
     * @return array<string> An array of script handles.
     */
    public function scripts(): array {
        return [ 'dokan-admin-reports' ];
    }

    /**
     * Get the styles.
     *
     * @since 5.0.0
     *
     * @return array<string> An array of style handles.
     */
    public function styles(): array {
        return [ 'dokan-admin-reports' ];
    }

    /**
     * Register the page scripts and styles.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register(): void {
        $asset_file_path = DOKAN_PRO_DIR . '/assets/js/admin/reports/index.asset.php';

        if ( file_exists( $asset_file_path ) ) {
            $asset_file = include $asset_file_path;

            wp_register_script(
                'dokan-admin-reports',
                DOKAN_PRO_PLUGIN_ASSEST . '/js/admin/reports/index.js',
                $asset_file['dependencies'],
                $asset_file['version'],
                [
                    'strategy'  => 'defer',
                    'in_footer' => true,
                ]
            );

            wp_register_style(
                'dokan-admin-reports',
                DOKAN_PRO_PLUGIN_ASSEST . '/js/admin/reports/style-index.css',
                [ 'dokan-react-components' ],
                $asset_file['version']
            );

            wp_set_script_translations(
                'dokan-admin-reports',
                'dokan'
            );
        }
    }

    /**
     * Render admin reports data migration notice.
     *
     * Adds an informational notice to the Dokan admin notice board,
     * prompting the admin to migrate historical "Admin Earning" report
     * data via the WooCommerce importer. The admin can permanently
     * dismiss the notice, after which it will no longer be displayed.
     *
     * @since 5.0.0
     *
     * @param array $notices Existing list of admin notices.
     *
     * @return array Modified list of admin notices with the migration notice appended.
     */
    public function render_admin_reports_data_migration_notice( array $notices ): array {
        // Check if the notice was hidden, then bail early.
        if ( 'yes' === get_option( 'dokan_admin_report_notice_hidden', 'no' ) ) {
            return $notices;
        }

        $notices[] = [
            'type'              => 'info',
            'title'             => esc_html__( 'Admin Reports - Data Migrate (Recommendation)', 'dokan' ),
            'description'       => esc_html__( '"Admin Earning" report now can be found in Reports > Admin Earning. If you want to check and update your historic data it is recommended that you kindly proceed to run the importer from WooCommerce.', 'dokan' ),
            'priority'          => 4,
            'show_close_button' => true,
            'ajax_data'         => [
                'key'    => 'dokan-notice-dismiss',
                'action' => 'dokan_ask_for_report_notice_action',
                'nonce'  => wp_create_nonce( 'dokan_admin_report_notice_nonce' ),
            ],
            'actions'           => [
                [
                    'type'   => 'primary',
                    'text'   => esc_html__( 'Check Importer', 'dokan' ),
                    'action' => wc_admin_url( '&path=/analytics/settings' ),
                    'target' => '_blank',
                ],
            ],
        ];

        return $notices;
    }

    /**
     * Handle admin report notice dismiss AJAX request.
     *
     * Verifies the request nonce and user capability, then permanently
     * dismisses the admin report data migration notice by updating the
     * corresponding option in the database. Once dismissed, the notice
     * will no longer appear in the Dokan admin notice board.
     *
     * @since 5.0.0
     *
     * @return void Sends a JSON response and exits.
     */
    public function handle_admin_report_notice_dismiss(): void {
        // Verify the nonce to protect attacks.
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'dokan_admin_report_notice_nonce' ) ) {
            wp_send_json_error( esc_html__( 'Invalid nonce', 'dokan' ) );
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( esc_html__( 'You have no permission to do that', 'dokan' ) );
        }

        // Sanitize and validate the dismiss action.
        $key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
        if ( 'dokan-notice-dismiss' !== $key ) {
            wp_send_json_error( esc_html__( 'Invalid request', 'dokan' ) );
        }

        // Persist the dismissal so the notice is not shown again.
        update_option( 'dokan_admin_report_notice_hidden', 'yes' );
        wp_send_json_success();
    }
}
