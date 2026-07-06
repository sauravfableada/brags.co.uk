<?php
// this file contains methods that are introduced in Dokan Lite on later on.
// there are some cases where users can update Dokan Pro but not Dokan lite,
// in that case user will get fatal errors since introduced methods are not available.
// so we will add those methods here and will remove them from Dokan Pro on next major release.

if ( ! function_exists( 'dokan_rest_validate_store_id' ) ) {
    /**
     * This method will verify per page item value, will be used only with rest api validate callback
     *
     * @since 3.8.0
     *
     * @param $value
     * @param $request WP_REST_Request
     * @param $key
     *
     * @return bool|WP_Error
     */
    function dokan_rest_validate_store_id( $value, $request, $key ) {
        $attributes = $request->get_attributes();

        if ( isset( $attributes['args'][ $key ] ) ) {
            $argument = $attributes['args'][ $key ];
            // Check to make sure our argument is an int.
            if ( 'integer' === $argument['type'] && ! is_numeric( $value ) ) {
                // translators: 1) argument name, 2) argument value
                return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%1$s is not of type %2$s', 'dokan' ), $key, 'integer' ), [ 'status' => 400 ] );
            }
        } else {
            // this code won't execute because we have specified this argument as required.
            // if we reused this validation callback and did not have required args then this would fire.
            // translators: 1) argument name
            return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s was not registered as a request argument.', 'dokan' ), $key ), [ 'status' => 400 ] );
        }

        $vendor = dokan()->vendor->get( intval( $value ) );
        if ( $vendor->get_id() && $vendor->is_vendor() ) {
            return true;
        }

        // translators: 1) rest api endpoint key name
        return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( 'No store found with given store id', 'dokan' ), $key ), [ 'status' => 400 ] );
    }
}

if ( ! function_exists( 'dokan_admin_menu_capability' ) ) {
    /**
     * Dokan Admin menu capability
     *
     * @since 3.9.4
     *
     * @return string
     */
    function dokan_admin_menu_capability() {
        return apply_filters( 'dokan_menu_capability', 'manage_woocommerce' );
    }
}

/**
 * This method will check if the given variable is empty or not
 *
 * @since 3.11.1
 *
 * @param $var
 *
 * @return bool
 */
if ( ! function_exists( 'dokan_is_empty' ) ) {
    function dokan_is_empty( $var ) {
        if ( empty( $var ) ) {
            return true;
        }

        if ( isset( $var[0] ) && intval( $var[0] === 0 ) ) {
            return true;
        }

        return false;
    }
}

/**
 * Register custom post status "vacation".
 *
 * @since 3.9.0
 *
 * @return void
 */
function dokan_register_custom_post_status_vacation() {
    register_post_status('vacation' );
}

add_action( 'init', 'dokan_register_custom_post_status_vacation', 10 );
