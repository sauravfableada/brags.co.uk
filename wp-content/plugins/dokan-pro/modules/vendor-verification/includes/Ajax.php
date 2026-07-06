<?php

namespace WeDevs\DokanPro\Modules\VendorVerification;

use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationMethod;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationRequest;

defined( 'ABSPATH' ) || exit;

/**
 * Ajax Class.
 *
 * @since 3.11.1
 */
class Ajax {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'wp_ajax_dokan_vendor_verification_request_creation', [ $this, 'verification_request_creation' ] );
        add_action( 'wp_ajax_dokan_vendor_verification_request_cancellation', [ $this, 'verification_request_cancellation' ] );

        add_action( 'wp_ajax_dokan_v_send_sms', [ $this, 'dokan_v_send_sms' ] );
        add_action( 'wp_ajax_dokan_v_verify_sms_code', [ $this, 'dokan_v_verify_sms_code' ] );
    }

    /**
     * Dokan Vendor Verification Request Creation.
     *
     * @since 3.11.1
     *
     * @return void
     */
    public function verification_request_creation() {
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_nonce'] ), 'dokan_vendor_verification_request_creation' ) ) {
            wp_send_json_error( __( 'Security Verification failed.', 'dokan' ) );
        }

        $current_user = dokan_get_current_user_id();

        if ( ! dokan_is_user_seller( $current_user ) ) {
            wp_send_json_error( __( 'You are not a vendor.', 'dokan' ) );
        }

        if ( ! isset( $_POST['vendor_verification_files_ids'] ) || ! is_array( $_POST['vendor_verification_files_ids'] ) || count( $_POST['vendor_verification_files_ids'] ) < 1 ) {
            wp_send_json_error( __( 'Please upload minimum one document.', 'dokan' ) );
        }

        $documents = wc_clean( wp_unslash( $_POST['vendor_verification_files_ids'] ) );

        $method_id = isset( $_POST['method_id'] ) ? absint( wp_unslash( $_POST['method_id'] ) ) : 0;

        if ( empty( $method_id ) ) {
            wp_send_json_error( __( 'Verification Method id required.', 'dokan' ) );
        }

        $note   = '';
        $method = new VerificationMethod( $method_id );
        $info   = [];

        if ( $method->get_kind() === VerificationMethod::TYPE_ADDRESS ) {
            $note            .= __( 'Address: ', 'dokan' ) . ' ' . str_replace( '<br/>', ', ', dokan_get_seller_address( $current_user ) );
            $info['address'] = dokan_get_seller_address( $current_user, true );
        }

        try {
            $verification_request = new VerificationRequest();
            $verification_request
                ->set_vendor_id( $current_user )
                ->set_method_id( $method_id )
                ->set_documents( $documents )
                ->set_note( $note )
                ->set_additional_info( $info )
                ->create();
        } catch ( \Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }

        wp_send_json_success( __( 'Verification Request Creation Successfully.', 'dokan' ) );
    }

    /**
     * Dokan Vendor Verification Request Cancellation.
     *
     * @since 3.11.1
     *
     * @return void
     */
    public function verification_request_cancellation() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'dokan-vendor-verification-cancel-request' ) ) {
            wp_send_json_error( __( 'Security Verification failed.', 'dokan' ) );
        }

        $current_user = dokan_get_current_user_id();

        if ( ! dokan_is_user_seller( $current_user ) ) {
            wp_send_json_error( __( 'You are not a vendor.', 'dokan' ) );
        }

        $request_id = isset( $_POST['request_id'] ) ? absint( wp_unslash( $_POST['request_id'] ) ) : 0;

        if ( empty( $request_id ) ) {
            wp_send_json_error( __( 'Verification request id required.', 'dokan' ) );
        }

        try {
            $verification_request = new VerificationRequest( $request_id );
            $verification_request
                ->set_status( VerificationRequest::STATUS_CANCELLED )
                ->update();
        } catch ( \Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }

        wp_send_json_success( __( 'Verification Request Cancelled Successfully.', 'dokan' ) );
    }

    /*
     * Sends SMS from verification template
     *
     * @since 1.0.0
     *
     * @return Ajax Success/fail
     *
     */
    public function dokan_v_send_sms() {
        // @codingStandardsIgnoreLine
        parse_str( $_POST['data'], $postdata );

        if ( ! wp_verify_nonce( $postdata['dokan_verify_action_nonce'], 'dokan_verify_action' ) ) {
            wp_send_json_error( __( 'Are you cheating?', 'dokan' ) );
        }
        $info['success'] = false;

        $sms  = \WeDevs_dokan_SMS_Gateways::instance();
        $info = $sms->send( $postdata['phone'] );

        // @codingStandardsIgnoreLine
        if ( $info['success'] == true ) {
            $current_user   = get_current_user_id();
            $seller_profile = dokan_get_store_info( $current_user );

            $seller_profile['dokan_verification']['info']['phone_no']     = $postdata['phone'];
            $seller_profile['dokan_verification']['info']['phone_code']   = $info['code'];
            $seller_profile['dokan_verification']['info']['phone_status'] = 'pending';

            update_user_meta( $current_user, 'dokan_profile_settings', $seller_profile );
        }
        wp_send_json_success( $info );
    }

    /*
     * Verify sent SMS code and update corresponding meta
     *
     * @since 1.0.0
     *
     * @return Ajax Success/fail
     *
     */
    public function dokan_v_verify_sms_code() {
        // @codingStandardsIgnoreLine
        parse_str( $_POST['data'], $postdata );

        if ( ! wp_verify_nonce( $postdata['dokan_verify_action_nonce'], 'dokan_verify_action' ) ) {
            wp_send_json_error( __( 'Are you cheating?', 'dokan' ) );
        }

        $current_user   = get_current_user_id();
        $seller_profile = dokan_get_store_info( $current_user );

        $saved_code = $seller_profile['dokan_verification']['info']['phone_code'];

        // @codingStandardsIgnoreLine
        if ( $saved_code == $postdata['sms_code'] ) {
            $seller_profile['dokan_verification']['info']['phone_status']   = 'verified';
            $seller_profile['dokan_verification']['verified_info']['phone'] = $seller_profile['dokan_verification']['info']['phone_no'];
            update_user_meta( $current_user, 'dokan_profile_settings', $seller_profile );

            $resp = [
                'success' => true,
                'message' => 'Your Phone is verified now',
            ];
            wp_send_json_success( $resp );
        } else {
            $resp = [
                'success' => false,
                'message' => 'Your SMS code is not valid, please try again',
            ];
            wp_send_json_success( $resp );
        }
    }
}
