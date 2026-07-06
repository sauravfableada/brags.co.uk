<?php

namespace WeDevs\DokanPro\Modules\Razorpay\Requests;

use WeDevs\DokanPro\Modules\Razorpay\Interfaces\RequestHandler;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class VendorConnectRequest implements RequestHandler {
    /**
     * Vendor Connect Request Handler.
     *
     * @since 3.5.0
     *
     * @param array $data
     *
     * @return WP_Error|array
     */
    public static function handle( $data = [] ) {
        $errors = [];

        // Nonce Verification
        if ( ! isset( $data['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $data['_wpnonce'] ), 'dokan_razorpay_connect' ) ) {
            $errors[] = [
                'code'    => 'invalid_nonce',
                'message' => __( 'Are you cheating?', 'dokan' ),
                'detail'  => __( 'Nonce verification failed.', 'dokan' ),
            ];
        }

        // Check if existing user checkbox is checked,
        // then, we'll not check further
        if ( ! empty( $data['razorpay_existing_user'] ) ) {
            // check razorpay_account_id
            if ( empty( $data['razorpay_account_id'] ) ) {
                $errors[] = [
                    'code'    => 'empty_razorpay_account_id',
                    'message' => __( 'Please enter your Razorpay account ID.', 'dokan' ),
                    'detail'  => __( 'Please enter your Razorpay account ID.', 'dokan' ),
                ];
            }

            if ( ! empty( $errors ) && count( $errors ) ) {
                return new WP_Error( 'dokan_razorpay_connect_error', $errors );
            }

            // Sanitize and return data
            return wc_clean( wp_unslash( $data ) );
        }

        /*
        * Validate all required data
        */
        if ( empty( $data['razorpay_account_email'] ) ) {
            $errors[] = [
                'code'    => 'invalid_email',
                'message' => __( 'Please give your razorpay account email.', 'dokan' ),
                'detail'  => __( 'Invalid email address.', 'dokan' ),
            ];
        }
        if ( empty( $data['razorpay_account_phone'] ) ) {
            $errors[] = [
                'code'    => 'invalid_phone',
                'message' => __( 'Please give your phone number', 'dokan' ),
                'detail'  => __( 'Invalid phone number', 'dokan' ),
            ];
        }

        if ( empty( $data['razorpay_business_name'] ) ) {
            $errors[] = [
                'code'    => 'invalid_business_name',
                'message' => __( 'Please give your company name.', 'dokan' ),
                'detail'  => __( 'Invalid business name.', 'dokan' ),
            ];
        }

        if ( empty( $data['razorpay_business_type'] ) ) {
            $errors[] = [
                'code'    => 'invalid_business_type',
                'message' => __( 'Please give your company type.', 'dokan' ),
                'detail'  => __( 'Invalid business type.', 'dokan' ),
            ];
        }
        if ( empty( $data['razorpay_address_line1'] ) ) {
            $errors[] = [
                'code'    => 'invalid_address_line1',
                'message' => __( 'Please give your address line 1.', 'dokan' ),
                'detail'  => __( 'Invalid address line 1.', 'dokan' ),
            ];
        }

        if ( empty( $data['razorpay_address_line2'] ) ) {
            $errors[] = [
                'code'    => 'invalid_address_line2',
                'message' => __( 'Please give your address line 2.', 'dokan' ),
                'detail'  => __( 'Invalid address line 2.', 'dokan' ),
            ];
        }

        if ( empty( $data['razorpay_city'] ) ) {
            $errors[] = [
                'code'    => 'invalid_city',
                'message' => __( 'Please give your city name.', 'dokan' ),
                'detail'  => __( 'Invalid city name.', 'dokan' ),
            ];
        }
        if ( empty( $data['razorpay_state'] ) ) {
            $errors[] = [
                'code'    => 'invalid_state',
                'message' => __( 'Please give your state name.', 'dokan' ),
                'detail'  => __( 'Invalid state name.', 'dokan' ),
            ];
        }
        if ( empty( $data['razorpay_postal_code'] ) ) {
            $errors[] = [
                'code'    => 'razorpay_postal_code',
                'message' => __( 'Please give your postcode.', 'dokan' ),
                'detail'  => __( 'Invalid postcode.', 'dokan' ),
            ];
        }

        if ( empty( $data['razorpay_country'] ) ) {
            $errors[] = [
                'code'    => 'invalid_country',
                'message' => __( 'Please give your country name.', 'dokan' ),
                'detail'  => __( 'Invalid country name.', 'dokan' ),
            ];
        }

        // check if any error
        if ( ! empty( $errors ) && count( $errors ) ) {
            return new WP_Error( 'dokan_razorpay_connect_error', $errors );
        }

        // Sanitize and return data
        return wc_clean( wp_unslash( $data ) );
    }
}
