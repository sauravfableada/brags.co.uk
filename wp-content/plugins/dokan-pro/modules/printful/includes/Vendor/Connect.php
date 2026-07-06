<?php

namespace WeDevs\DokanPro\Modules\Printful\Vendor;

use WeDevs\DokanPro\Modules\Printful\Auth;
use WeDevs\DokanPro\Modules\Printful\Providers\WebhookProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Printful Connect class.
 *
 * @since 3.13.0
 */
class Connect {

    /**
     * Class constructor.
     *
     * @since 3.13.0
     */
    public function __construct() {
        add_action( 'template_redirect', [ $this, 'process' ] );
    }

    /**
     * Process redirection.
     *
     * @since 3.13.0
     *
     * @phpcs:disable WordPress.Security.NonceVerification.Recommended
     *
     * @return void
     */
    public function process() {
        global $wp;

        $vendor = dokan_get_current_user_id();

        if (
            ! isset( $wp->query_vars['settings'] )
            || Settings::PAGE_SLUG !== $wp->query_vars['settings']
            || ! $vendor
            || ! isset( $_GET['code'] )
            || ! isset( $_GET['success'] )
        ) {
            return;
        }

        $code    = trim( sanitize_text_field( wp_unslash( $_GET['code'] ) ) );
        $success = absint( wp_unslash( $_GET['success'] ) );

        if ( ! $success || empty( $code ) ) {
            $this->display_error();
            return;
        }

        try {
            $auth = new Auth( $vendor );
            $auth->connect( $code );

            WebhookProvider::register();

            wp_safe_redirect( dokan_get_navigation_url( 'settings/' . Settings::PAGE_SLUG ) );
        } catch ( \Exception $e ) {
            dokan_log( $e->getMessage() );
            $this->display_error();
        }
    }

    /**
     * Display error.
     *
     * @since 3.13.0
     *
     * @return void
     */
    protected function display_error() {
        add_filter( 'dokan_pro_printful_vendor_settings_section_before', [ $this, 'error_template' ] );
    }

    /**
     * Error template display.
     *
     * @since 3.13.0
     *
     * @return void
     */
    public function error_template() {
        dokan_get_template_part(
            'global/dokan-error', '', [
                'deleted' => false,
                'message' => esc_html__( 'Could not connect your store to Printful. Please try again.', 'dokan' ),
            ]
        );
    }
}
