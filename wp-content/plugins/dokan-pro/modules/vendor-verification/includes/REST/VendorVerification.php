<?php

namespace WeDevs\DokanPro\Modules\VendorVerification\REST;

use WeDevs\Dokan\REST\DokanBaseVendorController;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationMethod;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationRequest;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;

class VendorVerification extends DokanBaseVendorController {

    /**
     * Endpoint base.
     *
     * @var string
     */
    protected $rest_base = 'vendor-verification';

    /**
     * Register the routes for the controller.
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => 'GET',
                    'callback'            => [ $this, 'get_items' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => [
                        'context' => [
                            'default' => 'view',
                        ],
                    ],
                ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/send-otp',
            [
                [
                    'methods'             => 'POST',
                    'callback'            => [ $this, 'send_otp' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => [
                        'phone'   => [
                            'required' => true,
                            'type'     => 'string',
                        ],
                    ],
                ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/verify-otp',
            [
                [
                    'methods'             => 'POST',
                    'callback'            => [ $this, 'verify_otp' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => [
                        'sms_code'    => [
                            'required' => true,
                            'type'     => 'string',
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Verify OTP.
     *
     * @param WP_REST_Request $request
     *
     * @since 4.0.5
     *
     * @return WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function verify_otp( WP_REST_Request $request ) {
        $current_user   = dokan_get_current_user_id();
        $seller_profile = dokan_get_store_info( $current_user );

        // sanitize the SMS code input
        $sms_code = sanitize_text_field( $request->get_param( 'sms_code' ) );

        $is_verified = ( new \WeDevs_dokan_SMS_Gateways() )->verify(
            $seller_profile['dokan_verification']['info']['phone_no'],
            $sms_code,
            'dokan_verification'
        );

        if ( ! $is_verified ) {
            $response = [
                'success' => false,
                'message' => __( 'Your SMS code is not valid, please try again', 'dokan' ),
            ];
            return new WP_Error( 'dokan_verify_otp_error', $response['message'], $response );
        }

        $seller_profile['dokan_verification']['info']['phone_status']   = 'verified';
        $seller_profile['dokan_verification']['verified_info']['phone'] = $seller_profile['dokan_verification']['info']['phone_no'];
        update_user_meta( $current_user, 'dokan_profile_settings', $seller_profile );

        return rest_ensure_response(
            [
				'success' => true,
				'message' => __( 'Your Phone is verified now', 'dokan' ),
			]
        );
    }

    /**
     * Send OTP to the user.
     *
     * @param WP_REST_Request $request
     *
     * @since 4.0.5
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function send_otp( WP_REST_Request $request ) {
        $info['success'] = false;
        $phone = $request->get_param( 'phone' );
        $sms  = \WeDevs_dokan_SMS_Gateways::instance();
        $info = $sms->send( $phone, 'dokan_verification' );

        if ( (bool) $info['success'] === false ) {
            return new WP_Error( 'dokan_send_otp_error', $info['message'], $info );
        }

        $current_user   = dokan_get_current_user_id();
        $seller_profile = dokan_get_store_info( $current_user );

        $seller_profile['dokan_verification']['info']['phone_no']     = $phone;
        $seller_profile['dokan_verification']['info']['phone_status'] = 'pending';
        update_user_meta( $current_user, 'dokan_profile_settings', $seller_profile );
        return rest_ensure_response( $info );
    }

    /**
     * Get the list of verification methods.
     *
     * @param WP_REST_Request $request
     *
     * @since 4.0.5
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function get_items( $request ) {
        $verification_methods = ( new VerificationMethod() )->query( [ 'status' => VerificationMethod::STATUS_ENABLED ] );

        $verification_methods = array_map(
            function ( $method ) use ( $request ) {
                return $this->prepare_item_for_response( $method, $request );
            },
            $verification_methods
        );

        $seller_profile = dokan_get_store_info( dokan_get_current_user_id() );

        return rest_ensure_response(
            [
				'verification_methods'  => $verification_methods,
                'social_providers'      => $this->get_social_app_verification_methods( $seller_profile ),
                'phone_verification'    => $this->get_phone_verification( $seller_profile ),
			]
        );
    }
    /**
     * Get the phone verification methods.
     *
     * @param array $seller_profile
     *
     * @since 4.0.5
     *
     * @return array
     */
    public function get_phone_verification( array $seller_profile ): array {
        $active_gateway     = dokan_get_option( 'active_gateway', 'dokan_verification_sms_gateways' );
        $active_gw_username = trim( dokan_get_option( $active_gateway . '_username', 'dokan_verification_sms_gateways' ) );
        $active_gw_pass     = trim( dokan_get_option( $active_gateway . '_pass', 'dokan_verification_sms_gateways' ) );
        $is_configured      = ! empty( $active_gw_username ) || ! empty( $active_gw_pass );
        $verification_info  = $seller_profile['dokan_verification']['info'] ?? [];

		return [
			'is_configured'  => $is_configured,
            'active_gateway' => $active_gateway,
			'phone_status'   => $verification_info['phone_status'] ?? '',
			'phone_no'       => $verification_info['phone_no'] ?? '',
		];
    }

    /**
     * Get the social app verification methods.
     *
     * @param array $seller_profile
     *
     * @since 4.0.5
     *
     * @return array
     */

    public function get_social_app_verification_methods( array $seller_profile ): array {
        $configured_providers = array();

        //facebook config from admin
        $fb_status = dokan_get_option( 'facebook_enable_status', 'dokan_verification', 'on' );
        $fb_id     = dokan_get_option( 'fb_app_id', 'dokan_verification' );
        $fb_secret = dokan_get_option( 'fb_app_secret', 'dokan_verification' );
		if ( $fb_status === 'on' && $fb_id !== '' && $fb_secret !== '' ) {
			$configured_providers [] = 'facebook';
		}
        //Google config from admin
        $g_status = dokan_get_option( 'google_enable_status', 'dokan_verification', 'on' );
        $g_id     = dokan_get_option( 'google_app_id', 'dokan_verification' );
        $g_secret = dokan_get_option( 'google_app_secret', 'dokan_verification' );
		if ( $g_status === 'on' && $g_id !== '' && $g_secret !== '' ) {
			$configured_providers [] = 'google';
		}
        // LinkedIn config from admin
        $l_status = dokan_get_option( 'linkedin_enable_status', 'dokan_verification', 'on' );
        $l_id     = dokan_get_option( 'linkedin_app_id', 'dokan_verification' );
        $l_secret = dokan_get_option( 'linkedin_app_secret', 'dokan_verification' );
		if ( $l_status === 'on' && $l_id !== '' && $l_secret !== '' ) {
			$configured_providers [] = 'linkedin';
		}
        // Twitter config from admin
        $twitter_status = dokan_get_option( 'twitter_enable_status', 'dokan_verification', 'on' );
        $twitter_id     = dokan_get_option( 'twitter_app_id', 'dokan_verification' );
        $twitter_secret = dokan_get_option( 'twitter_app_secret', 'dokan_verification' );
		if ( $twitter_status === 'on' && $twitter_id !== '' && $twitter_secret !== '' ) {
			$configured_providers [] = 'twitter';
		}

        /**
         * Filter the list of Providers connect links to display
         *
         * @since 4.0.5
         *
         * @param array $providers
         */
        $providers       = apply_filters( 'dokan_verify_provider_list', $configured_providers );
		$verification    = $seller_profile['dokan_verification'] ?? [];

		foreach ( $providers as $key => $provider ) {
			$is_connected = isset( $verification[ $provider ] ) && $verification[ $provider ] !== '';

			$providers[ $key ] = [
				'id'            => $provider,
				'title'         => ucwords( $provider ),
				'is_connected'  => $is_connected,
			];

			$url_args = [ $is_connected ? 'dokan_auth_dc' : 'dokan_auth' => $provider ];
			$url_key  = $is_connected ? 'disconnect_url' : 'connect_url';

			$providers[ $key ][ $url_key ] = add_query_arg( $url_args, dokan_get_navigation_url( 'settings/verification' ) );
            if ( $is_connected ) {
                $provider_info = $verification[ $provider ];
                $providers[ $key ]['photo_url']       = $provider_info['photoURL'];
                $providers[ $key ]['profile_url']     = $provider_info['profileURL'];
                $providers[ $key ]['display_name']    = $provider_info['displayName'];
                $providers[ $key ]['email']           = $provider_info['email'];
            }
		}

        return $providers;
    }

    /**
     * Prepares the item for the REST response.
     *
     * @since 4.0.5
     *
     * @param VerificationMethod $item    WordPress' representation of the item.
     * @param WP_REST_Request    $request Request object.
     *
     * @return array Response object on success, or WP_Error object on failure.
     */
    public function prepare_item_for_response( $item, $request ): array {
        $data                   = $item->to_array();
        $verification_request   = ( new VerificationRequest() )->query(
            [
                'method_id' => $item->get_id(),
                'vendor_id' => dokan_get_current_user_id(),
                'per_page'  => 1,
                'order_by'  => 'id',
                'order'     => 'DESC',
            ]
        );
        $last_verification_request = reset( $verification_request );
        if ( $last_verification_request ) {
            $documents = $last_verification_request->get_documents();
            foreach ( $documents as $key => $file_id ) {
                    $documents[ $key ] = [
                        'id'   => $file_id,
                        'name' => get_the_title( $file_id ),
                        'url'  => wp_get_attachment_url( $file_id ),
                    ];
			}
            $data['last_verification'] = [
                'id'     => $last_verification_request->get_id(),
                'status' => $last_verification_request->get_status(),
                'label'  => $last_verification_request->get_status_title(),
                'note'   => $last_verification_request->get_note(),
                'documents' => $documents,
            ];
        }

        if ( $item->get_kind() === VerificationMethod::TYPE_ADDRESS ) {
            $data['seller_address'] = dokan_get_seller_address( dokan_get_current_user_id() );
        }

        /**
         * Apply Filter the verification method data.
         *
         * @since 4.0.5
         */
        $method_title  = apply_filters( 'dokan_pro_vendor_verification_method_title', $item->get_title(), $item );
        $data['title'] = $method_title;

        /**
         * Apply Filter the verification method help text.
         *
         * @since 4.0.5
         */

        $method_help_text  = apply_filters( 'dokan_pro_vendor_verification_method_help_text', $item->get_help_text(), $item );
        $data['help_text'] = $method_help_text;

        $context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
        $data     = $this->add_additional_fields_to_object( $data, $request );

        return $this->filter_response_by_context( $data, $context );
    }
}
