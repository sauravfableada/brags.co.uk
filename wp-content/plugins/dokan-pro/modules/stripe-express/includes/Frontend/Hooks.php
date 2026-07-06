<?php

namespace WeDevs\DokanPro\Modules\StripeExpress\Frontend;

use WeDevs\DokanPro\Modules\StripeExpress\Processors\User;

defined( 'ABSPATH' ) || exit; // Exit if called directly

/**
 * Class for managing frontend hooks
 *
 * @since 3.7.25
 */
class Hooks {

    /**
     * Class constructor
     *
     * @since 3.7.25
     */
    public function __construct() {
        add_action( 'init', [ $this, 'maybe_redirect_user' ] );
    }

    /**
     * Redirects user to stripe onboarding page
     *
     * @since 3.7.25
     *
     * @return void
     */
    public function maybe_redirect_user() {
        if ( ! isset( $_GET['_onboarding_refresh_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_onboarding_refresh_nonce'] ), 'dokan_stripe_express_onboarding_refresh' ) ) {
            return;
        }

        $response = User::onboard( absint( $_GET['seller_id'] ), [] );
        if ( is_wp_error( $response ) ) {
            return;
        }

        wp_redirect( $response->url );
        exit();
    }
}
