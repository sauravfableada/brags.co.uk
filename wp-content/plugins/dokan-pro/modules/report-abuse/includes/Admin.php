<?php

namespace WeDevs\DokanPro\Modules\ReportAbuse;

class Admin {

    /**
     * Class constructor
     *
     * @since 2.9.8
     *
     * @return void
     */
    public function __construct() {
        add_action( 'dokan_admin_menu', [ self::class, 'add_admin_menu' ] );
        add_filter( 'dokan-admin-routes', [ self::class, 'add_admin_route' ] );
        add_action( 'dokan-vue-admin-scripts', [ self::class, 'enqueue_admin_script' ] );
        add_action( 'init', [ self::class, 'register_scripts' ] );
        add_action( 'dokan_after_saving_settings', [ $this, 'after_save_settings' ], 10, 3 );
        add_filter( 'dokan_admin_dashboard_pages_settings', [ $this, 'load_most_reported_vendors' ] );
    }

    /**
     * Add Dokan submenu
     *
     * @since 2.9.8
     *
     * @param string $capability
     *
     * @return void
     */
    public static function add_admin_menu( $capability ) {
        if ( current_user_can( $capability ) ) {
            global $submenu;

            $title = esc_html__( 'Abuse Reports', 'dokan' );
            $slug  = 'dokan';

            $submenu[ $slug ][] = [ $title, $capability, 'admin.php?page=' . $slug . '#/abuse-reports' ];
        }
    }

    /**
     * Add admin page Route
     *
     * @since 2.9.8
     *
     * @param array $routes
     *
     * @return array
     */
    public static function add_admin_route( $routes ) {
        $routes[] = [
            'path'      => '/abuse-reports',
            'name'      => 'AbuseReports',
            'component' => 'AbuseReports'
        ];

        $routes[] = [
            'path'      => '/abuse-reports/:id',
            'name'      => 'AbuseReportsSingle',
            'component' => 'AbuseReportsSingle'
        ];

        return $routes;
    }

    /**
     * Register scripts
     *
     * @since 3.7.4
     */
    public static function register_scripts() {
        list( $suffix, $version ) = dokan_get_script_suffix_and_version();

        wp_register_style( 'woocommerce_select2', WC()->plugin_url() . '/assets/css/select2.css', [], WC_VERSION );
        wp_register_script(
            'dokan-report-abuse-admin-vue',
            DOKAN_REPORT_ABUSE_ASSETS . '/js/dokan-report-abuse-admin' . $suffix . '.js',
            [ 'jquery', 'dokan-vue-vendor', 'dokan-vue-bootstrap', 'selectWoo' ],
            $version,
            true
        );
    }

    /**
     * Enqueue admin script
     *
     * @since 2.9.8
     *
     * @return void
     */
    public static function enqueue_admin_script() {
        wp_enqueue_style( 'woocommerce_select2' );
        wp_enqueue_script( 'dokan-report-abuse-admin-vue' );
    }

    /**
     * After Save Admin Settings.
     *
     * @since 3.10.0
     *
     * @param string $option_name Option Key (Section Key).
     * @param array $option_value Option value.
     * @param array $old_options Option Previous value.
     *
     * @return void
     */
    public function after_save_settings( $option_name, $option_value, $old_options ) {
        if ( 'dokan_report_abuse' !== $option_name ) {
            return;
        }

        foreach ( $option_value['abuse_reasons'] as $key => $status ) {
            do_action( 'dokan_pro_register_abuse_report_reason', $status['value'] );
        }
    }

    /**
     * Load most reported vendors data into localized data for the admin dashboard.
     *
     * @since 4.1.0
     *
     * @param array $localized_data
     *
     * @return array
     */
    public function load_most_reported_vendors( array $localized_data ): array {
        $localized_data['show_most_reported_vendors'] = true;

        return $localized_data;
    }
}
