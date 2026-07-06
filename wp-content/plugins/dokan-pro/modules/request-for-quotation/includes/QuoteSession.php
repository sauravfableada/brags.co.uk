<?php

namespace WeDevs\DokanPro\Modules\RequestForQuotation;

use WeDevs\DokanPro\Storage\Session;

/**
 * Class QuoteSession
 *
 * This class extends the session storage mechanism and manages storing quote data in WordPress transients.
 * It also sets and retrieves the transient key from cookies, ensuring session persistence.
 *
 * @package WeDevs\DokanPro\Storage
 */
class QuoteSession extends Session {

    /**
     * Get the session cookie, if set. Otherwise, return false.
     *
     * Session cookies without a customer ID are invalid.
     *
     * @since 3.12.4
     *
     * @return bool|array
     */
    public function get_session_cookie() {
        // Retrieve the cookie value if set, otherwise return false.
        $cookie_value = isset( $_COOKIE[ $this->cookie ] ) ? wp_unslash( $_COOKIE[ $this->cookie ] ) : false; // @codingStandardsIgnoreLine.

        // Validate the cookie value.
        if ( empty( $cookie_value ) || ! is_string( $cookie_value ) ) {
            return false;
        }

        // Retrieve the transient data using the cookie value.
        $transient_data = get_transient( $cookie_value );

        // Extract data from the transient.
        $customer_id        = $transient_data['customer_id'] ?? '';
        $session_expiration = $transient_data['session_expiration'] ?? '';
        $cookie_hash        = $transient_data['cookie_hash'] ?? '';
        $data               = $transient_data['data'] ?? [];

        // Validate the customer ID.
        if ( empty( $customer_id ) ) {
            return false;
        }

        // Validate the hash.
        $to_hash = $customer_id . '|' . $session_expiration . '|' . maybe_serialize( $data );
        $hash    = hash_hmac( 'sha256', $to_hash, wp_hash( $to_hash ) );

        if ( empty( $cookie_hash ) || ! hash_equals( $hash, $cookie_hash ) ) {
            return false;
        }

        // Return the session data.
        return array( $customer_id, $session_expiration, $cookie_hash, $data );
    }

    /**
     * Sets the session cookie on-demand (usually after adding an item to the cart).
     *
     * Warning: Cookies will only be set if this is called before the headers are sent.
     *
     * @param bool $set Should the session cookie be set.
     *
     * @since 3.3.6
     *
     * @return void
     */
    public function set_customer_session_cookie( $set ) {
        if ( $set ) {
            // Create the hash for the cookie.
            $to_hash         = $this->customer_id . '|' . $this->session_expiration . '|' . maybe_serialize( $this->data );
            $cookie_hash     = hash_hmac( 'sha256', $to_hash, wp_hash( $to_hash ) );
            $cookie_value    = $cookie_hash;
            $transaction_key = $cookie_hash;

            // Prepare the transient data.
            $transient_data = [
                'customer_id'        => $this->customer_id,
                'session_expiration' => $this->session_expiration,
                'cookie_hash'        => $cookie_hash,
                'data'               => $this->data,
            ];

            // Set the transient with the session data.
            set_transient( $transaction_key, $transient_data, (int) $this->session_expiration - time() );

            // Set the cookie if not already set or if the value has changed.
            if ( ! isset( $_COOKIE[ $this->cookie ] ) || $_COOKIE[ $this->cookie ] !== $cookie_value ) {
                setcookie( $this->cookie, $cookie_value, (int) $this->session_expiration, defined( 'COOKIEPATH' ) ? COOKIEPATH : '/', COOKIE_DOMAIN, $this->use_secure_cookie(), true );
            }
        }
    }
}
