<?php

namespace WeDevs\DokanPro\Modules\ShipStation;

use Exception;

/**
 * Vendor Dashboard Settings Class.
 *
 * @since 3.14.4
 */
class VendorApiCredentials {

    /**
     * Get WooCommerce API Credential.
     *
     * @since 3.14.4
     *
     * @param int $vendor_id Vendor ID
     *
     * @return array API Credential
     */
    public static function get( $vendor_id = null ) {
        global $wpdb;

        $vendor_id = $vendor_id ? $vendor_id : dokan_get_current_user_id();

        $api_credential['key_id']          = get_user_meta( $vendor_id, 'dokan_shipstation_wc_api_key_id', true ) ?: 0;
        $api_credential['consumer_key']    = get_user_meta( $vendor_id, 'dokan_shipstation_wc_api_consumer_key', true ) ?: '';
        $api_credential['consumer_secret'] = get_transient( "dokan_shipstation_wc_api_consumer_secret_Key_for_vendor_{$vendor_id}" ) ?: '';
        $api_credential['dokan_auth_key']  = get_user_meta( $vendor_id, 'shipstation_auth_key', true ) ?: '';

        if ( ! empty( $api_credential['key_id'] ) ) {
            $exist = (bool) $wpdb->get_var( $wpdb->prepare( "SELECT EXISTS( SELECT * FROM {$wpdb->prefix}woocommerce_api_keys WHERE key_id = %d AND user_id = %d LIMIT 1 )", $api_credential['key_id'], $vendor_id ) );

            if ( ! $exist ) {
                self::remove_keys( $vendor_id );

                $api_credential = [
                    'key_id'          => 0,
                    'consumer_key'    => '',
                    'consumer_secret' => '',
                    'dokan_auth_key'  => '',
                ];
            }
        }

        return apply_filters( 'dokan_shipstation_vendor_order_status_settings', $api_credential, $vendor_id );
    }

    /**
     * Create WooCommerce API Credential by Vendor ID.
     * @see \WC_Ajax::update_api_key();
     *
     * @since 3.14.4
     *
     * @param int $vendor_id Vendor ID
     *
     * @throws Exception
     * @return array Newly Created API Credential
     */
    public static function create( $vendor_id ) {
        global $wpdb;

        if ( empty( $vendor_id ) ) {
            throw new Exception( esc_html__( 'Invalid vendor ID', 'dokan' ) );
        }

        if ( ! dokan_is_user_seller( $vendor_id ) ) {
            throw new Exception( esc_html__( 'User does not have the required seller capability', 'dokan' ) );
        }

        $consumer_key    = 'ck_' . wc_rand_hash();
        $consumer_secret = 'cs_' . wc_rand_hash();

        $data = [
            'user_id'         => $vendor_id,
            // translators: %s: Vendor ID
            'description'     => sprintf( __( 'Dokan ShipStation API Key for Vendor ID: %s', 'dokan' ), $vendor_id ),
            'permissions'     => 'read_write',
            'consumer_key'    => wc_api_hash( $consumer_key ),
            'consumer_secret' => $consumer_secret,
            'truncated_key'   => substr( $consumer_key, -7 ),
        ];

        $wpdb->insert(
            $wpdb->prefix . 'woocommerce_api_keys',
            $data,
            array(
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );

        $inserted_id = $wpdb->insert_id;

        if ( 0 === $inserted_id ) {
            // translators: %d: Vendor ID
            throw new Exception( sprintf( esc_html__( 'Failed to insert API credentials to database for vendor ID: %d.', 'dokan' ), absint( $vendor_id ) ) );
        }

        update_user_meta( $vendor_id, 'dokan_shipstation_wc_api_key_id', $inserted_id );
        update_user_meta( $vendor_id, 'dokan_shipstation_wc_api_consumer_key', $consumer_key );

        // Save secrete key temporarily.
        set_transient( "dokan_shipstation_wc_api_consumer_secret_Key_for_vendor_{$vendor_id}", $consumer_secret, MINUTE_IN_SECONDS );

        $auth_key = get_user_meta( $vendor_id, 'shipstation_auth_key', true );

        if ( ! $auth_key ) {
            $settings = new Settings();
            $auth_key = $settings->generate_key( $vendor_id );

            update_user_meta( $vendor_id, 'shipstation_auth_key', $auth_key );
        }

        $output                    = $data;
        $output['key_id']          = $inserted_id;
        $output['consumer_key']    = $consumer_key;
        $output['dokan_auth_key']  = $auth_key;

        do_action( 'dokan_shipstation_api_credentials_created', $vendor_id, $output );

        return $output;
    }

    /**
     * Remove WooCommerce API Credential.
     *
     * @since 3.14.4
     *
     * @param int $vendor_id Vendor ID
     *
     * @return array|false
     */
    public static function remove( $vendor_id = null ) {
        global $wpdb;

        $vendor_id  = $vendor_id ? $vendor_id : dokan_get_current_user_id();
        $credential = self::get( $vendor_id );

        $deleted = $wpdb->delete(
            $wpdb->prefix . 'woocommerce_api_keys',
            [
                'user_id' => $vendor_id,
                'key_id'  => $credential['key_id'],
            ],
            [ '%d', '%d' ]
        );

        if ( false === $deleted ) {
            return false;
        }

        self::remove_keys( $vendor_id );

        do_action( 'dokan_shipstation_api_credentials_removed', $vendor_id, $credential );

        return $credential;
    }

    /**
     * Remove API keys from storage.
     *
     * @since 3.16.2
     *
     * @param int $vendor_id Vendor ID.
     *
     * @return void
     */
    public static function remove_keys( int $vendor_id ): void {
        delete_user_meta( $vendor_id, 'dokan_shipstation_wc_api_key_id' );
        delete_user_meta( $vendor_id, 'dokan_shipstation_wc_api_consumer_key' );
        delete_transient( "dokan_shipstation_wc_api_consumer_secret_Key_for_vendor_{$vendor_id}" );
    }
}
