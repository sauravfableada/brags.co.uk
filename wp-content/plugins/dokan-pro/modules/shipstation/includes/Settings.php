<?php

namespace WeDevs\DokanPro\Modules\ShipStation;

class Settings {

    /**
     * Class constructor
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'dokan_settings_general_site_options', array( $this, 'add_admin_settings_fields' ) );
        add_filter( 'dokan_admin_settings_rearrange_map', array( $this, 'admin_settings_rearrange_map' ) );
        add_action( 'dokan_dashboard_content_before', array( $this, 'enqueue_scripts' ) );
        add_action( 'dokan_get_dashboard_settings_nav', array( $this, 'add_settings_nav' ) );
        add_filter( 'dokan_dashboard_settings_heading_title', array( $this, 'add_heading_title' ), 10, 2 );
        add_action( 'dokan_dashboard_settings_helper_text', array( $this, 'add_helper_text' ), 10, 2 );
        add_action( 'dokan_settings_content', array( $this, 'add_settings_content' ) );
        add_action( 'init', array( $this, 'register_scripts' ) );
    }

    /**
     * Add admin settings fields
     *
     * @since 1.0.0
     *
     * @param array $settings_fields
     */
    public function add_admin_settings_fields( $settings_fields ) {
        $settings_fields['enable_shipstation_logging'] = array(
            'name'    => 'enable_shipstation_logging',
            'label'   => __( 'Log ShipStation API Request', 'dokan' ),
            'desc'    => __( 'Log all ShipStation API interactions.', 'dokan' ),
            'type'    => 'switcher',
            'default' => 'off',
            'tooltip' => __( 'Keep track of ShipStation API requests made by vendors.', 'dokan' ),
        );

        return $settings_fields;
    }

    /**
     * Backward compatible settings option map
     *
     * @since 2.9.13
     *
     * @param array $map
     *
     * @return array
     */
    public function admin_settings_rearrange_map( $map ) {
        return array_merge(
            $map, array(
                'enable_shipstation_logging_dokan_selling' => array( 'enable_shipstation_logging', 'dokan_general' ),
            )
        );
    }

    /**
     * Register Scripts
     *
     * @since 3.7.4
     */
    public function register_scripts() {
        list( $suffix, $version ) = dokan_get_script_suffix_and_version();

        wp_register_style( 'dokan-shipstation-settings', DOKAN_SHIPSTATION_ASSETS . '/css/dokan-shipstation-settings.css', [], $version );
        wp_register_script( 'dokan-shipstation-settings', DOKAN_SHIPSTATION_ASSETS . '/js/dokan-shipstation-settings.js', array( 'jquery' ), $version, true );

        wp_set_script_translations( 'dokan-shipstation-settings', 'dokan', plugin_dir_path( DOKAN_PRO_FILE ) . 'languages' );

        $script_assets = DOKAN_SHIPSTATION_PATH . '/assets/js/script-components.asset.php';

        if ( file_exists( $script_assets ) ) {
            $assets = include $script_assets;

            wp_register_script(
                'dokan-shipstation-dashboard-settings',
                DOKAN_SHIPSTATION_ASSETS . '/js/script-components.js',
                array_merge( $assets['dependencies'], [ 'dokan-react-components' ] ),
                $assets['version'],
                true
            );

            wp_set_script_translations( 'dokan-shipstation-dashboard-settings', 'dokan', plugin_dir_path( DOKAN_PRO_FILE ) . 'languages' );
        }
    }

    /**
     * Enqueue ShipStation scripts in vendor settings page
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function enqueue_scripts() {
        if ( dokan_is_seller_dashboard() ) {
            wp_enqueue_script( 'dokan-shipstation-dashboard-settings' );

            wp_add_inline_script(
                'dokan-shipstation-dashboard-settings',
                'window.dokanShipStation = ' . wp_json_encode(
                    [
                        'orderStatuses' => wc_get_order_statuses(),
                    ]
                ) . ';',
                'before'
            );
        }

        $settings = get_query_var( 'settings' );

        if ( 'shipstation' !== $settings ) {
            return;
        }

        wp_enqueue_style( 'dokan-shipstation-settings' );
        wp_enqueue_script( 'dokan-shipstation-settings' );
        wp_enqueue_script( 'dokan-tooltip' );

        wp_add_inline_script(
            'dokan-shipstation-settings',
            'window.DokanShipStation = ' . wp_json_encode(
                [
                    'save_settings_success_msg'                => esc_html__( 'Settings saved successfully.', 'dokan' ),
                    'generate_credential_success_msg'          => esc_html__( 'API Key generated successfully. Make sure to copy your new keys now as the secret key will be hidden once you leave this page.', 'dokan' ),
                    'generate_credential_warning_button_label' => esc_html__( 'Confirm', 'dokan' ),
                    'revoke_credential_success_msg'            => esc_html__( 'API credentials revoked successfully.', 'dokan' ),
                    'revoke_warning_title'                     => esc_html__( 'Are you sure you want to revoke current credentials?', 'dokan' ),
                    'revoke_warning_text'                      => esc_html__( 'Revoking will immediately disconnect your store from ShipStation, potentially affecting ongoing shipments. You won\'t be able to manage orders until new credentials are set up.', 'dokan' ),
                    'revoke_confirm_button_label'              => esc_html__( 'Confirm', 'dokan' ),
                ]
            ) . ';',
            'before'
        );
    }

    /**
     * Add settings nav in settings page
     *
     * @since 1.0.0
     *
     * @param array $settings
     */
    public function add_settings_nav( $settings ) {
        $settings['shipstation'] = array(
            'title'       => __( 'ShipStation', 'dokan' ),
            'icon'        => '<i class="fas fa-cog"></i>',
            'url'         => dokan_get_navigation_url( 'settings/shipstation' ),
            'pos'         => 72,
            'permission'  => 'dokan_view_store_shipping_menu',
            'react_route' => 'settings/shipstation',
        );

        return $settings;
    }

    /**
     * Add heading title in settings page
     *
     * @since 1.0.0
     *
     * @param string $header
     * @param string $query_vars
     */
    public function add_heading_title( $header, $query_vars ) {
        if ( 'shipstation' === $query_vars ) {
            $header = __( 'ShipStation', 'dokan' );
        }

        return $header;
    }

    /**
     * Add helper text in settings page
     *
     * @since 1.0.0
     *
     * @param string $help_text
     * @param string $query_vars
     */
    public function add_helper_text( $help_text, $query_vars ) {
        if ( 'shipstation' === $query_vars ) {
            $help_text = __( 'ShipStation allows you to retrieve & manage orders, then print labels & packing slips with ease.', 'dokan' );
        }

        return $help_text;
    }

    /**
     * Add settings form in settings page
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function add_settings_content() {
        $settings = get_query_var( 'settings' );

        if ( 'shipstation' !== $settings ) {
            return;
        }

        $seller_id = dokan_get_current_user_id();

        $auth_key = get_user_meta( $seller_id, 'shipstation_auth_key', true );

        if ( ! $auth_key ) {
            $auth_key = $this->generate_key( $seller_id );

            update_user_meta( $seller_id, 'shipstation_auth_key', $auth_key );
        }

        $api_credential  = VendorApiCredentials::get( $seller_id );
        $order_statuses  = VendorOrderStatusSettings::get( $seller_id );
        $export_statuses = $order_statuses['export_statuses'] ?: [];
        $shipped_status  = $order_statuses['shipped_status'] ?: 'wc-completed';
        $statuses        = wc_get_order_statuses();

        $args = array(
            'vendor_id'       => $seller_id,
            'auth_key'        => $auth_key,
            'api_credential'  => $api_credential,
            'statuses'        => $statuses,
            'export_statuses' => $export_statuses,
            'shipped_status'  => $shipped_status,
        );

        dokan_shipstation_get_template( 'settings', $args );
    }

    /**
     * Generate read-only auth key for ShipStation
     *
     * @since 1.0.0
     *
     * @param int $seller_id
     *
     * @return string
     */
    public function generate_key( $seller_id ) {
        $to_hash = $seller_id . time() . wp_generate_password( 32, true, true );
        return 'DOKANSS-' . hash_hmac( 'sha256', $to_hash, wp_hash( $to_hash ) );
    }
}
