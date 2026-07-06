<?php

namespace WeDevs\DokanPro\Admin;

use WeDevs\DokanPro\Modules\TableRate\DokanGoogleDistanceMatrixAPI;
use WeDevs\DokanPro\Storage\Session;
use WP_Error;

/**
 * Ajax handling for Dokan in Admin area
 *
 * @since  2.2
 *
 * @author weDevs <info@wedevs.com>
 */
class Ajax {

    /**
     * Tools actions service instance.
     *
     * @since 5.0.0
     *
     * @var ToolsActions
     */
    protected $tools;

    /**
     * Load automatically all actions
     */
    public function __construct() {
        $this->tools = new ToolsActions();
        add_action( 'wp_ajax_regenerate_order_commission', [ $this, 'regenerate_order_commission' ] );
        add_action( 'wp_ajax_check_duplicate_suborders', [ $this, 'check_duplicate_suborders' ] );
        add_action( 'wp_ajax_rewrite_product_variations_author', [ $this, 'rewrite_product_variations_author' ] );
        add_action( 'wp_ajax_dokan_get_distance_btwn_address', [ $this, 'get_distance_btwn_address' ] );
    }

    /**
     * Regenerate order commission data.
     *
     * @since 3.9.3
     *
     * @return void
     */
    public function regenerate_order_commission() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dokan_admin' ) ) {
            wp_send_json_error( __( 'Nonce verification failed', 'dokan' ), 403 );
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore
            wp_send_json_error( __( 'You don\'t have enough permission', 'dokan' ), 403 );
        }
        $result = $this->tools->regenerate_order_commission();

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message(), $result->get_error_data()['status'] ?? 400 );
        }

        wp_send_json_success( $result );
    }

    /**
     * Remove duplicate sub-orders if found
     *
     * @since 2.4.4
     *
     * @return void
     */
    public function check_duplicate_suborders() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dokan_admin' ) ) {
            wp_send_json_error( __( 'Nonce verification failed', 'dokan' ), 403 );
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'You don\'t have enough permission', 'dokan' ), 403 );
        }
        $limit        = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 0;
        $offset       = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
        $prev_done    = isset( $_POST['done'] ) ? absint( $_POST['done'] ) : 0;
        $total_orders = isset( $_POST['total_orders'] ) ? absint( $_POST['total_orders'] ) : 0;

        $result = $this->tools->check_duplicate_suborders( $limit, $offset, $prev_done, $total_orders );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message(), $result->get_error_data()['status'] ?? 400 );
        }

        // Format dashboard URL as HTML link for AJAX response
        if ( isset( $result['dashboard_url'] ) ) {
            /* translators: 1) dashboard link 2) dashboard text */
            $dashboard_link    = sprintf( '<a href="%1$s">%2$s</a>', $result['dashboard_url'], esc_html__( 'Go to Dashboard →', 'dokan' ) );
            $result['message'] = $result['message'] . ' ' . $dashboard_link;
            unset( $result['dashboard_url'] );
        }

        wp_send_json_success( $result );
    }

    /**
     * Rewrite product variations author via ajax.
     *
     * @since 3.7.13
     *
     * @return void
     */
    public function rewrite_product_variations_author() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dokan_admin' ) ) {
            wp_send_json_error( __( 'Nonce verification failed', 'dokan' ), 403 );
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'You don\'t have enough permission', 'dokan' ), 403 );
        }
        $page   = ! empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $result = $this->tools->rewrite_product_variations_author( $page );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message(), $result->get_error_data()['status'] ?? 400 );
        }

        wp_send_json_success( $result );
    }

    /**
     * Get distance between two address to check if Distance Matrix API is working or not
     *
     * @since 3.7.21
     *
     * @return void
     */
    public function get_distance_btwn_address() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dokan_admin' ) ) {
            wp_send_json_error( __( 'Nonce verification failed', 'dokan' ), 403 );
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'You don\'t have enough permission', 'dokan' ), 403 );
        }
        $address1 = isset( $_POST['address1'] ) ? sanitize_text_field( wp_unslash( $_POST['address1'] ) ) : '';
        $address2 = isset( $_POST['address2'] ) ? sanitize_text_field( wp_unslash( $_POST['address2'] ) ) : '';

        $result = $this->tools->get_distance_btwn_address( $address1, $address2 );

        if ( is_wp_error( $result ) ) {
            $error_data = $result->get_error_data();

            // Format structured error data as HTML for AJAX response
            if ( 'distance_error' === $result->get_error_code() && isset( $error_data['error_code'] ) ) {
                $message = sprintf(
                    '<strong>%s:</strong> %s, <strong>%s:</strong> %s',
                    __( 'Error Code', 'dokan' ),
                    $error_data['error_code'],
                    __( 'Error Message', 'dokan' ),
                    $error_data['error_message']
                );
                wp_send_json_error( $message, $error_data['status'] ?? 400 );
            }

            wp_send_json_error( $result->get_error_message(), $error_data['status'] ?? 400 );
        }

        // For success we return the message directly like previous implementation
        if ( isset( $result['message'] ) ) {
            wp_send_json_success( $result['message'] );
        }

        wp_send_json_success( $result );
    }
}
