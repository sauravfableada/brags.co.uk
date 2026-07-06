<?php

namespace WeDevs\DokanPro\Modules\Printful\Vendor;

use WeDevs\DokanPro\Modules\Printful\Auth;

defined( 'ABSPATH' ) || exit;

/**
 * Vendor Printful Settings
 *
 * @since 3.13.0
 */
class Settings {

    /**
     * Page Slug
     *
     * @var string
     */
    const PAGE_SLUG = 'printful';

    /**
     * Constructor.
     */
    public function __construct() {
        add_filter( 'dokan_get_dashboard_settings_nav', [ $this, 'register_settings_menu' ], 12 );
        add_filter( 'dokan_dashboard_settings_heading_title', [ $this, 'load_settings_header' ], 12, 2 );
        add_filter( 'dokan_dashboard_settings_helper_text', [ $this, 'load_settings_helper_text' ], 12, 2 );
        add_action( 'dokan_render_settings_content', [ $this, 'load_settings_content' ], 12 );
    }

    /**
     * Register settings menu in dashboard
     *
     * @since 3.13.0
     *
     * @param array $settings_menu Dokan settings menu.
     *
     * @return array
     */
    public function register_settings_menu( array $settings_menu ): array {
        $icon = '<svg width="22" height="11" viewBox="0 0 22 11" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6.29945 0.988281L0.78125 10.459H5.12793L8.47827 4.72183L6.29945 0.988281Z" fill="white"/>
            <path d="M10.7447 10.459H11.8177L11.2812 9.53926L10.7447 10.459Z" fill="white"/>
            <path d="M8.79578 5.26923L5.76296 10.459H10.1206L10.9636 9.00277L8.79578 5.26923Z" fill="white"/>
            <path d="M11.2813 8.45537L13.4601 4.72183L11.2813 0.988281L9.11347 4.72183L11.1281 8.1926L11.2813 8.45537Z" fill="white"/>
            <path d="M11.5992 9.00277L12.4532 10.459H16.7999L13.778 5.26923L11.5992 9.00277Z" fill="white"/>
            <path d="M14.0952 4.72183L17.4346 10.459H21.7813L16.263 0.988281L14.0952 4.72183Z" fill="white"/>
        </svg>';

        $settings_menu[ self::PAGE_SLUG ] = [
            'title'      => __( 'Printful', 'dokan' ),
            'icon'       => $icon,
            'url'        => dokan_get_navigation_url( 'settings/' . self::PAGE_SLUG ),
            'pos'        => 94,
            'permission' => 'dokan_view_store_printful_settings_menu',
        ];

        return $settings_menu;
    }

    /**
     * Load Settings Header
     *
     * @since 3.13.0
     *
     * @param  string $header Header String.
     * @param  array $query_vars Query vars.
     *
     * @return string
     */
    public function load_settings_header( $header, $query_vars ) {
        if ( self::PAGE_SLUG === $query_vars ) {
            $header = esc_html__( 'Printful', 'dokan' );
        }

        return $header;
    }

    /**
     * Load Settings page helper
     *
     * @since 3.13.0
     *
     * @param string $help_text Helper text.
     * @param string  $query_vars Query vars.
     *
     * @return string
     */
    public function load_settings_helper_text( string $help_text, string $query_vars ) {
        if ( self::PAGE_SLUG === $query_vars ) {
            $help_text = esc_html__( 'Set your settings for Printful Integration for your store.', 'dokan' );
        }

        return $help_text;
    }

    /**
     * Load Settings Content
     *
     * @since 3.13.0
     *
     * @param array $query_vars Query vars.
     *
     * @return void
     */
    public function load_settings_content( array $query_vars ) {
        if ( ! ( isset( $query_vars['settings'] ) && self::PAGE_SLUG === $query_vars['settings'] ) ) {
            return;
        }

        if ( ! current_user_can( 'dokan_view_store_printful_settings_menu' ) ) {
            dokan_get_template_part(
                'global/dokan-error', '', [
                    'deleted' => false,
                    'message' => __( 'You have no permission to view this page', 'dokan' ),
                ]
            );

            return;
        }

        $vendor_id = dokan_get_current_user_id();
        $auth      = new Auth( $vendor_id );
        $connected = false;
        $store     = [];

        if ( $auth->is_connected() ) {
            $connected = true;
            try {
                $store = $auth->get_store_info();
            } catch ( \Exception $exception ) {
                dokan_log( $exception->getMessage() );
            }
        }

        dokan_get_template_part(
            'printful-vendor', 'settings', [
                'is_printful'      => true,
                'connected'        => $connected,
                'store'            => $store,
                'site_currency'    => get_woocommerce_currency(),
                'shipping_enabled' => get_user_meta( $vendor_id, 'dokan_printful_shipping_enabled', true ),
                'rates_enabled'    => get_user_meta( $vendor_id, 'dokan_printful_enable_marketplace_rates', true ),
            ]
        );
    }
}
