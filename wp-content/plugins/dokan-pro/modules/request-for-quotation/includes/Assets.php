<?php

namespace WeDevs\DokanPro\Modules\RequestForQuotation;

class Assets {

    /**
     * Class constructor for hooks class.
     */
    public function __construct() {
        add_filter( 'dokan_localized_args', [ $this, 'add_nonce_to_dokan_localized_args' ] );
        add_filter( 'dokan_admin_localize_script', [ $this, 'add_admin_data_to_localize_script' ] );

        // Loads frontend scripts and styles.
        add_action( 'init', [ $this, 'register_scripts' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        // Loads admin scripts and styles.
        add_action( 'dokan-vue-admin-scripts', [ $this, 'enqueue_admin_script' ] );

        // Loads vendor dashboard React app.
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_vendor_dashboard_script' ] );
    }

    /**
     * Add nonce to login form popup response
     *
     * @since 3.12.3
     *
     * @param array $default_script
     *
     * @return array
     */
    public function add_nonce_to_dokan_localized_args( array $default_script ): array {
        $default_script['dokan_request_quote_nonce'] = wp_create_nonce( 'dokan_request_quote_nonce' );
        $default_script['reject_confirmation_msg']   = __( 'Are you sure want to Reject the deal?', 'dokan' );
        $default_script['cancel_confirmation_msg']   = __( 'Are you sure want to Cancel the deal?', 'dokan' );
        $default_script['trash_confirmation_msg']    = __( 'Are you sure want to Trash the deal?', 'dokan' );

        if ( dokan_is_seller_dashboard() ) {
            $default_script['rfq_vendor_quote_url'] = wc_get_endpoint_url( DOKAN_VENDOR_ENDPOINT, '', dokan_get_navigation_url() );
        }

        return $default_script;
    }

    /**
     * Add admin data to handle admin actions.
     *
     * @since 3.12.3
     *
     * @param array $args
     *
     * @return array
     */
    public function add_admin_data_to_localize_script( array $args ): array {
        $args['request_quote']['status_list'] = (array) Helper::get_quote_bulk_action_list_for_new();

        return $args;
    }

    /**
     * Register scripts.
     *
     * @since 3.12.3
     *
     * @return void
     */
    public function register_scripts() {
        list( $suffix, $version ) = dokan_get_script_suffix_and_version();

        wp_register_script(
            'dokan-request-a-quote-frontend',
            DOKAN_RAQ_ASSETS . '/js/index.js',
            [ 'jquery' ],
            $version,
            true
        );
        wp_register_style(
            'dokan-request-a-quote-frontend',
            DOKAN_RAQ_ASSETS . '/css/request-a-quote-front.css',
            [],
            $version
        );

        wp_register_script(
            'dokan-request-a-quote-admin',
            DOKAN_RAQ_ASSETS . '/js/dokan-request-a-quote-admin' . $suffix . '.js',
            [ 'jquery', 'dokan-vue-vendor', 'dokan-vue-bootstrap', 'selectWoo' ],
            $version,
            true
        );

        wp_register_style(
            'dokan-request-a-quote-admin-css',
            DOKAN_RAQ_ASSETS . '/js/dokan-request-a-quote-admin' . $suffix . '.css',
            [],
            $version,
            'all'
        );
    }

    /**
     * Enqueue scripts.
     *
     * @since 3.12.3
     *
     * @return void
     */
    public function enqueue_scripts() {
        global $wp;

        if (
            ( dokan_is_seller_dashboard() && isset( $wp->query_vars['requested-quotes'] ) )
            || ( is_account_page() && isset( $wp->query_vars['request-a-quote'] ) )
            || is_page( Helper::get_quote_page_id() )
            || dokan_is_store_page()
            || is_checkout()
            || is_product()
            || is_shop()
        ) {
            wp_enqueue_script( 'dokan-vendor-address' );
            wp_enqueue_style( 'dokan-request-a-quote-frontend' );
        }

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'dokan-request-a-quote-frontend' );
    }

    /**
     * Enqueue admin script
     *
     * @since 3.12.3
     *
     * @return void
     */
    public function enqueue_admin_script() {
        wp_enqueue_script( 'dokan-request-a-quote-admin' );
        wp_enqueue_style( 'dokan-request-a-quote-admin-css' );
    }

    /**
     * Register and enqueue the vendor dashboard React script.
     *
     * @since 3.13.0
     *
     * @return void
     */
    public function enqueue_vendor_dashboard_script() {
        if ( ! dokan_is_seller_dashboard() ) {
            return;
        }

        $script_assets_path = DOKAN_RAQ_PATH . '/assets/js/dokan-rfq-vendor-dashboard.asset.php';

        if ( ! file_exists( $script_assets_path ) ) {
            return;
        }

        $asset        = require $script_assets_path;
        $dependencies = array_merge( $asset['dependencies'] ?? [], [ 'dokan-react-components', 'dokan-utilities' ] );
        $version      = $asset['version'] ?? DOKAN_PRO_PLUGIN_VERSION;

        wp_register_script(
            'dokan-rfq-vendor-dashboard',
            DOKAN_RAQ_ASSETS . '/js/dokan-rfq-vendor-dashboard.js',
            $dependencies,
            $version,
            true
        );

        wp_set_script_translations( 'dokan-rfq-vendor-dashboard', 'dokan', plugin_dir_path( DOKAN_FILE ) . 'languages' );
        wp_enqueue_script( 'dokan-rfq-vendor-dashboard' );
    }
}
