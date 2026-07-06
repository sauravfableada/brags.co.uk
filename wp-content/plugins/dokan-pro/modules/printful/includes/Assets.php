<?php

namespace WeDevs\DokanPro\Modules\Printful;



use WeDevs\DokanPro\Modules\Printful\Processors\PrintfulProductProcessor;
use WeDevs\DokanPro\Modules\Printful\Vendor\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Assets class.
 *
 * @since 3.13.0
 */
class Assets {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'init', [ $this, 'register' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'load' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'load' ] );
    }

    /**
     * Register all scripts and styles.
     *
     * @since 3.13.0
     *
     * @return void
     */
    public function register() {
        list( $suffix, $version ) = dokan_get_script_suffix_and_version();

        // Register all js
        wp_register_script( 'dokan-printful-vendor', DOKAN_PRINTFUL_ASSETS . '/js/vendor.js',
            [ 'jquery', 'dokan-sweetalert2', 'dokan-frontend' ], $version, true );
        wp_register_script( 'dokan-printful-frontend', DOKAN_PRINTFUL_ASSETS . '/js/frontend.js',
            [ 'jquery', 'dokan-frontend', 'jquery-ui-tabs'  ], $version, true );

        // register all css
        wp_register_style( 'dokan-printful-admin', DOKAN_PRINTFUL_ASSETS . '/css/admin.css', [], $version );
        wp_register_style( 'dokan-printful-vendor', DOKAN_PRINTFUL_ASSETS . '/css/vendor.css', [], $version );
        wp_register_style( 'dokan-printful-frontend', DOKAN_PRINTFUL_ASSETS . '/css/frontend.css', [ 'jquery-ui-style' ], $version );
        wp_register_style( 'jquery-ui-style', DOKAN_PRINTFUL_ASSETS . '/vendor/jquery-ui.css', [], $version );

        // Add translations.
        wp_set_script_translations(
            'dokan-printful-vendor',
            'dokan',
            plugin_dir_path( DOKAN_PRO_FILE ) . 'languages'
        );
    }

    /**
     * Load scripts and styles.
     *
     * @return void
     */
    public function load() {
        global $wp, $post;

        if ( is_admin() ) {
            wp_enqueue_style( 'dokan-printful-admin' );

            return;
        }

        if ( dokan_is_seller_dashboard() && ( isset( $wp->query_vars[ 'settings' ] ) && $wp->query_vars[ 'settings' ] === Settings::PAGE_SLUG ) || isset( $wp->query_vars[ 'products' ] ) ) {
            wp_enqueue_script( 'dokan-printful-vendor' );
            wp_enqueue_style( 'dokan-printful-vendor' );

            wp_localize_script(
                'dokan-printful-vendor', 'DokanPrintful', [
                    'vendor_disconnect_alert_msg' => esc_html__( 'Are you sure you want to disconnect Printful? All Printful product will go to draft and orders will not be synced.', 'dokan' ),
                ]
            );

            return;
        }

        $product = ! empty( $post->ID ) ? wc_get_product( $post->ID ) : '';
        // Check if product is valid and size guide available for the product.
        if ( $product && $product->meta_exists( PrintfulProductProcessor::META_KEY_PRODUCT_SIZE_GUIDE ) ) {
            wp_enqueue_style( 'dokan-printful-frontend' );
            wp_enqueue_script( 'dokan-printful-frontend' );

            wp_localize_script(
                'dokan-printful-frontend', 'DokanPrintfulPopup', [
                    'popup_title'              => ! empty( trim( dokan_get_option( 'popup_title', 'dokan_printful' ) ) ) ? dokan_get_option( 'popup_title', 'dokan_printful' ) : esc_html__( 'Size Guide', 'dokan' ),
                    'primary_measurement_unit' => dokan_get_option( 'primary_measurement_unit', 'dokan_printful' ) ?: 'inches',
                ]
            );
        }
    }
}
