<?php

namespace WeDevs\DokanPro\Modules\Printful;

use Exception;
use WeDevs\DokanPro\Modules\Printful\Processors\PrintfulProductProcessor;
use WeDevs\DokanPro\Modules\Printful\Providers\WebhookProvider;
use WeDevs\DokanPro\Modules\Printful\Vendor\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Handle ajax events.
 */
class Ajax {

    /**
     * Class constructor
     *
     * @since 3.13.0
     *
     * @return void
     */
    public function __construct() {
        add_action( 'wp_ajax_dokan_printful_connect_vendor_to_store', [ $this, 'connect_to_printful' ] );
        add_action( 'wp_ajax_dokan_printful_disconnect_vendor_to_store', [ $this, 'disconnect_to_printful' ] );
        add_action( 'wp_ajax_dokan_printful_enable_shipping_toggle', [ $this, 'enable_printful_shipping' ] );
        add_action( 'wp_ajax_dokan_printful_enable_rates_toggle', [ $this, 'enable_marketplace_rates' ] );
        add_action( 'wp_ajax_dokan_printful_add_size_guide', [ $this, 'add_printful_size_guide' ] );
    }

    /**
     * Connect vendor to printful.
     *
     * @since 3.13.0
     *
     * @return void
     */
    public function connect_to_printful() {
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_nonce'] ), 'dokan-printful-vendor-connect' ) ) {
            wp_send_json_error( esc_html__( 'Security verification failed.', 'dokan' ) );
        }

        $app_id       = trim( dokan_get_option( 'app_id', 'dokan_printful', '' ) );
        $app_secret   = trim( dokan_get_option( 'app_secret', 'dokan_printful', '' ) );

        if ( empty( $app_id ) || empty( $app_secret ) ) {
            wp_send_json_error( esc_html__( 'Printful Integration is disabled.', 'dokan' ) );
        }

        /**
         * Actions before URL translation in Dokan for WPML compatibility.
         *
         * @since 3.13.0
         */
        do_action( 'dokan_disable_url_translation' );

        $redirect_url = dokan_get_navigation_url( 'settings/' . Settings::PAGE_SLUG );

        /**
         * Actions after URL translation is re-enabled in Dokan for WPML compatibility.
         *
         * @since 3.13.0
         */
        do_action( 'dokan_enable_url_translation' );

        $url = add_query_arg(
            [
                'client_id' => $app_id,
                'redirect_url' => $redirect_url,
            ],
            'https://www.printful.com/oauth/authorize'
        );

        wp_send_json_success( $url );
    }

    /**
     * Disconnect vendor to printful.
     *
     * @since 3.13.0
     *
     * @return void
     */
    public function disconnect_to_printful() {
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_nonce'] ), 'dokan-printful-vendor-disconnect' ) ) {
            wp_send_json_error( esc_html__( 'Security verification failed.', 'dokan' ) );
        }

        $app_id       = trim( dokan_get_option( 'app_id', 'dokan_printful', '' ) );
        $app_secret   = trim( dokan_get_option( 'app_secret', 'dokan_printful', '' ) );

        if ( empty( $app_id ) || empty( $app_secret ) ) {
            wp_send_json_error( esc_html__( 'Printful Integration is disabled.', 'dokan' ) );
        }

        $vendor_id = dokan_get_current_user_id();

        $auth = new Auth( $vendor_id );

        if ( ! $auth->is_connected() ) {
            wp_send_json_error( esc_html__( 'Vendor is not connected to Printful.', 'dokan' ) );
        }

        $webhook_deregistered = WebhookProvider::deregister();

        if ( ! $webhook_deregistered ) {
            wp_send_json_error( esc_html__( 'Error Removing Printful webhook.', 'dokan' ) );
        }

        $disconnected = $auth->disconnect();
        if ( ! $disconnected ) {
            wp_send_json_error( esc_html__( 'Error disconnecting from Printful.', 'dokan' ) );
        }

		// Add queue to updating products status to draft.
	    WC()->queue()->add(
	    	'dokan_printful_process_products_status_update_queue',
		    [
			    'page' => 1,
		    ],
	    	'dokan_printful'
	    );

        wp_send_json_success( esc_html__( 'Disconnecting from Printful Successfully.', 'dokan' ) );
    }

    /**
     * Enable Printful Shipping.
     *
     * @since 3.13.0
     *
     * @return void
     */
    public function enable_printful_shipping() {
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_nonce'] ) ), 'dokan-printful-enable-shipping' ) ) {
            wp_send_json_error( esc_html__( 'Security verification failed', 'dokan' ) );
        }

        $vendor_id   = dokan_get_current_user_id();
        $input_value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';

        if ( empty( $vendor_id ) ) {
            wp_send_json_error( esc_html__( 'Invalid value for Printful shipping enable input', 'dokan' ) );
        }

        $updated = update_user_meta( $vendor_id, 'dokan_printful_shipping_enabled', $input_value );

        if ( ! $updated ) {
            wp_send_json_error( esc_html__( 'Failed to update Printful shipping enable input data', 'dokan' ) );
        }

        wp_send_json_success( esc_html__( 'Printful shipping enable input data updated successfully', 'dokan' ) );
    }

    /**
     * Disable Marketplace Rates.
     *
     * @since 3.13.0
     *
     * @return void
     */
    public function enable_marketplace_rates() {
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_nonce'] ) ), 'dokan-printful-enable-rates' ) ) {
            wp_send_json_error( esc_html__( 'Security verification failed', 'dokan' ) );
        }

        $vendor_id   = dokan_get_current_user_id();
        $input_value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';

        if ( empty( $vendor_id ) ) {
            wp_send_json_error( esc_html__( 'Invalid value for Printful enable marketplace rates input', 'dokan' ) );
        }

        $updated = update_user_meta( $vendor_id, 'dokan_printful_enable_marketplace_rates', $input_value );

        if ( ! $updated ) {
            wp_send_json_error( esc_html__( 'Failed to update Printful enable marketplace rates input data', 'dokan' ) );
        }

        wp_send_json_success( esc_html__( 'Printful enable marketplace rates input data updated successfully', 'dokan' ) );
    }

    /**
     * Add Printful Size Guide.
     *
     * @since 3.13.0
     *
     * @throws Exception
     * @return void
     */
    public function add_printful_size_guide() {
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_nonce'] ) ), 'dokan-printful-add-product-size-guide' ) ) {
            wp_send_json_error( esc_html__( 'Security verification failed', 'dokan' ) );
        }

        $product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;;
        $catalog_id = isset( $_POST['catalog_id'] ) ? absint( wp_unslash( $_POST['catalog_id'] ) ) : 0;;
        $vendor_id  = isset( $_POST['vendor_id'] ) ? absint( wp_unslash( $_POST['vendor_id'] ) ) : 0;;

        if ( empty( $product_id ) || empty( $catalog_id ) || empty( $vendor_id ) ) {
            wp_send_json_error( esc_html__( 'Invalid value for adding Printful product size guide', 'dokan' ) );
        }

        try {
            dokan_pro()->module->printful->product->add_printful_product_size_guide( $product_id, $catalog_id, $vendor_id );

            wp_send_json_success( esc_html__( 'Printful size guide data imported successfully', 'dokan' ) );
        } catch ( Exception $e ) {
            if ( 404 === $e->getCode() ) {
                $product = wc_get_product( $product_id );

                // Remove size guide fetching failure meta if exists.
                if ( $product->meta_exists( PrintfulProductProcessor::META_KEY_PRINTFUL_SIZE_GUIDE_FAILURE_COUNT ) ) {
                    $product->delete_meta_data( PrintfulProductProcessor::META_KEY_PRINTFUL_SIZE_GUIDE_FAILURE_COUNT );
                    $product->save();
                }
            }

            wp_send_json_error(
                sprintf(
                    /* translators %s: Error message */
                    esc_html__( 'Printful size guide data import failed for reason: %s', 'dokan' ),
                    $e->getMessage()
                )
            );
        }
    }
}
