<?php

namespace WeDevs\DokanPro\Modules\StripeExpress\Admin;

defined( 'ABSPATH' ) || exit; // Exit if called directly.

/**
 * Class to handle admin assets.
 *
 * @since 3.6.1
 */
class Assets {

    /**
     * Classs constructor.
     *
     * @since 3.6.1
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', [ $this, 'register_scripts' ] );
    }

    /**
     * Registers admin scripts
     *
     * @since 3.6.1
     *
     * @return void
     */
    public function register_scripts() {
        list( $suffix, $version ) = dokan_get_script_suffix_and_version();

        wp_register_script(
            'dokan-stripe-express-admin',
            DOKAN_STRIPE_EXPRESS_ASSETS . "js/admin{$suffix}.js",
            [ 'jquery' ],
            $version,
            true
        );
    }
}
