<?php

namespace WeDevs\DokanPro\Modules\VendorAnalytics;

/**
 * Assets class for Vendor Analytics module.
 *
 * Handles registration and enqueuing of React vendor dashboard scripts.
 *
 * @since 5.0.0
 */
class Assets {

    /**
     * Class constructor.
     *
     * @since 5.0.0
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_vendor_dashboard_script' ] );
    }

    /**
     * Register and enqueue the vendor dashboard React script.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function enqueue_vendor_dashboard_script() {
        if ( ! dokan_is_seller_dashboard() ) {
            return;
        }

        $script_assets_path = DOKAN_VENDOR_ANALYTICS_DIR . '/assets/js/dokan-vendor-analytics-dashboard.asset.php';

        if ( ! file_exists( $script_assets_path ) ) {
            return;
        }

        $asset        = require $script_assets_path;
        $dependencies = array_merge(
            $asset['dependencies'] ?? [],
            [ 'dokan-react-components', 'dokan-utilities', 'echarts-js', 'echarts-js-map-world' ]
        );
        $version = $asset['version'] ?? DOKAN_PRO_PLUGIN_VERSION;

        wp_register_script(
            'dokan-vendor-analytics-dashboard',
            DOKAN_VENDOR_ANALYTICS_ASSETS . '/js/dokan-vendor-analytics-dashboard.js',
            $dependencies,
            $version,
            true
        );

        wp_set_script_translations( 'dokan-vendor-analytics-dashboard', 'dokan', plugin_dir_path( DOKAN_FILE ) . 'languages' );
        wp_enqueue_script( 'dokan-vendor-analytics-dashboard' );
    }
}
