<?php

namespace WeDevs\DokanPro\Modules\StripeExpress\Processors;

defined( 'ABSPATH' ) || exit; // Exit if called directly

use WeDevs\DokanPro\Modules\StripeExpress\Support\Helper;

/**
 * Class for processing WooCommerce payment tokens.
 *
 * @since 3.7.8
 *
 * @package WeDevs\DokanPro\Modules\StripeExpress\Processors
 */
class Token {

    /**
     * Extract the payment token from the provided request.
     *
     * @since 3.7.8
     *
     * @return \WC_Payment_Token|NULL
     */
    public static function parse_from_request() {
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $payment_method    = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : Helper::get_gateway_id();
        $token_request_key = "wc-$payment_method-payment-token";

        if ( ! isset( $_POST[ $token_request_key ] ) || 'new' === $_POST[ $token_request_key ] ) {
            return null;
        }

        $token = \WC_Payment_Tokens::get( sanitize_text_field( wp_unslash( $_POST[ $token_request_key ] ) ) );
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        // If the token doesn't belong to this gateway or the current user it's invalid.
        if ( ! $token || $payment_method !== $token->get_gateway_id() || $token->get_user_id() !== get_current_user_id() ) {
            return null;
        }

        return $token;
    }
}
