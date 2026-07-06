<?php

namespace WeDevs\DokanPro\Modules\StripeExpress\Frontend;

defined( 'ABSPATH' ) || exit; // Exit if called directly

/**
 * Class for handling frontend assets
 *
 * @since 3.6.1
 *
 * @package WeDevs\DokanPro\Modules\StripeExpress\Frontend
 */
class Assets {

    /**
     * Class constructor
     *
     * @since 3.6.1
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );
    }

    /**
     * Registers necessary scripts
     *
     * @since 3.6.1
     *
     * @return void
     */
    public function register_scripts() {
        list( $suffix, $version ) = dokan_get_script_suffix_and_version();

        wp_register_script(
            'dokan-stripe-express-cdn',
            'https://js.stripe.com/v3',
            [],
            $version,
            true
        );

        wp_register_script(
            'dokan-stripe-express-payment-request',
            DOKAN_STRIPE_EXPRESS_ASSETS . "js/payment-request{$suffix}.js",
            [ 'jquery', 'dokan-stripe-express-cdn', 'dokan-sweetalert2' ],
            $version,
            true
        );

        wp_register_style(
            'dokan-stripe-express-checkout-block',
            DOKAN_STRIPE_EXPRESS_ASSETS . "js/checkout-block{$suffix}.css",
            [],
            $version
        );

        $checkout_classic_asset_file = DOKAN_STRIPE_EXPRESS_PATH . '/assets/js/checkout-classic.asset.php';
        $checkout_classic_js_deps = array();
        $checkout_classic_version = $version;
        if ( file_exists( $checkout_classic_asset_file ) ) {
            $stripe_checkout_classic_js_asset = include $checkout_classic_asset_file;
            $checkout_classic_js_deps = $stripe_checkout_classic_js_asset['dependencies'];
            $checkout_classic_version = $stripe_checkout_classic_js_asset['version'];
        }

        wp_register_script(
            'dokan-stripe-express-checkout-classic',
            DOKAN_STRIPE_EXPRESS_ASSETS . "js/checkout-classic{$suffix}.js",
            $checkout_classic_js_deps,
            $checkout_classic_version,
            true
        );

        $express_payment_classic_asset_file = DOKAN_STRIPE_EXPRESS_PATH . '/assets/js/express-payment-classic.asset.php';
        $express_payment_classic_js_deps = array();
        $express_payment_classic_version = $version;
        if ( file_exists( $express_payment_classic_asset_file ) ) {
            $express_payment_classic_js_asset = include $express_payment_classic_asset_file;
            $express_payment_classic_js_deps = $express_payment_classic_js_asset['dependencies'];
            $express_payment_classic_version = $express_payment_classic_js_asset['version'];
        }

        wp_register_script(
            'dokan-stripe-express-payment-request-classic',
            DOKAN_STRIPE_EXPRESS_ASSETS . "js/express-payment-classic{$suffix}.js",
            $express_payment_classic_js_deps,
            $express_payment_classic_version,
            true
        );

        wp_register_script(
            'dokan-stripe-express-vendor',
            DOKAN_STRIPE_EXPRESS_ASSETS . "js/vendor{$suffix}.js",
            [ 'jquery', 'dokan-sweetalert2' ],
            $version,
            true
        );

        wp_register_style(
            'dokan-stripe-express-vendor',
            DOKAN_STRIPE_EXPRESS_ASSETS . "css/vendor{$suffix}.css",
            [],
            $version
        );

        wp_localize_script(
            'dokan-stripe-express-vendor',
            'dokanStripeExpressData',
            [
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'dokan_stripe_express_vendor_payment_settings' ),
                'i18n'    => [
                    'country_select_error' => __( 'Please select your country to proceed.', 'dokan' ),
                    'cancel_onboarding'    => [
                        'is_setup_wizard'   => isset( $_GET['page'] ) && 'dokan-seller-setup' === sanitize_text_field( wp_unslash( $_GET['page'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        'title'             => __( 'Cancel Onboarding?', 'dokan' ),
                        'text'              => __( 'Are you sure you want to cancel the current onboarding process? Note that, this process is permanent and you can\'t undo this action. However, you\'ll be able to start the onboarding process again.', 'dokan' ),
                        'confirmButtonText' => __( 'Yes, cancel it!', 'dokan' ),
                        'cancelButtonText'  => __( 'No, keep it!', 'dokan' ),
                        'successTitle'      => __( 'Success', 'dokan' ),
                        'successMessage'    => __( 'Onboarding process has been cancelled successfully.', 'dokan' ),
                        'errorMessage'      => __( 'Something went wrong! Please try again.', 'dokan' ),
                    ],
                ],
            ]
        );
    }
}
