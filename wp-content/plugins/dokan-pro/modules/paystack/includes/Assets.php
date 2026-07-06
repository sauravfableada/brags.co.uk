<?php

namespace WeDevs\DokanPro\Modules\Paystack;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Assets {

    /**
     * Class constructor
     *
     * @since 4.1.1
     *
     * @return void
     */
	public function __construct() {
        add_action( 'init', [ $this, 'register_scripts' ] );
	}

    /**
     * Register scripts and styles for the Paystack module.
     *
     * @since 4.1.1
     */
    public function register_scripts() {
        // vendor scripts
        $vendor_asset = DOKAN_PAYSTACK_PATH . '/assets/js/dashboard.asset.php';

        if ( file_exists( $vendor_asset ) ) {
            $assets = require $vendor_asset;
            wp_register_script(
                'dokan-paystack-vendor',
                DOKAN_PAYSTACK_ASSETS . 'js/dashboard.js',
                $assets['dependencies'],
                $assets['version'],
                true
            );
            wp_register_style(
                'dokan-paystack-style',
                DOKAN_PAYSTACK_ASSETS . 'css/paystack.css',
                [],
                $assets['version']
            );
        }
        // admin scripts
        $admin_asset = DOKAN_PAYSTACK_PATH . '/assets/js/paystack-admin.asset.php';
        if ( file_exists( $admin_asset ) ) {
            $assets = require $admin_asset;
            wp_register_script(
                'dokan-paystack-admin',
                DOKAN_PAYSTACK_ASSETS . 'js/paystack-admin.js',
                $assets['dependencies'],
                $assets['version'],
                true
            );
        }

        // checkout scripts
        $checkout_asset = DOKAN_PAYSTACK_PATH . '/assets/js/paystack-checkout.asset.php';
        if ( file_exists( $checkout_asset ) ) {
            $assets = require $checkout_asset;
            wp_register_script(
                'dokan-paystack-checkout',
                DOKAN_PAYSTACK_ASSETS . 'js/paystack-checkout.js',
                array_merge( $assets['dependencies'], [ 'jquery' ] ),
                $assets['version'],
                true
            );
        }
    }
}
