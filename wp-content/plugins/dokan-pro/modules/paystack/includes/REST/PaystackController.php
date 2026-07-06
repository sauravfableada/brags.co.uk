<?php

namespace WeDevs\DokanPro\Modules\Paystack\REST;

use Exception;
use WeDevs\Dokan\REST\DokanBaseVendorController;
use WeDevs\Dokan\Traits\VendorAuthorizable;
use WeDevs\DokanPro\Modules\Paystack\Api\Bank;
use WeDevs\DokanPro\Modules\Paystack\Api\SubAccount;
use WeDevs\DokanPro\Modules\Paystack\Support\Helper;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class PaystackController extends DokanBaseVendorController {
    use VendorAuthorizable;

    /**
     * Endpoint base.
     *
     * @var string
     */
    protected $rest_base = 'paystack';

    /**
     * Register the routes for the controller.
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/banks',
            [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_banks' ],
                'permission_callback' => [ $this, 'check_permission' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/connect',
            [
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => [ $this, 'connect' ],
                'permission_callback' => [ $this, 'check_permission' ],
                'args' => [
                    'business_name' => [
                        'required' => true,
                        'type' => 'string',
                    ],
                    'account_number' => [
                        'required' => true,
                        'type' => 'string',
                    ],
                    'bank_code' => [
                        'required' => true,
                        'type' => 'string',
                    ],
                ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/reconnect',
            [
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => [ $this, 'reconnect' ],
                'permission_callback' => [ $this, 'check_permission' ],
                'args' => [
                    'subaccount_code' => [
                        'required' => true,
                        'type' => 'string',
                    ],
                ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/disconnect',
            [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'disconnect' ],
                'permission_callback' => [ $this, 'check_permission' ],
            ]
        );
    }

    /**
     * Get the list of banks.
     *
     * @return WP_HTTP_Response|WP_Error
     */
    public function get_banks() {
        try {
			$banks = Bank::list();
			return rest_ensure_response( $banks );
        } catch ( Exception $e ) {
            return new WP_Error(
                'paystack_bank_error', __( 'Failed to retrieve banks from Paystack.', 'dokan' ), [
					'status' => 500,
					'error' => $e->getMessage(),
				]
            );
        }
    }

    /**
     * Connect to Paystack and create a subaccount.
     *
     * @param WP_REST_Request $request
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function connect( WP_REST_Request $request ) {
        $params = $request->get_params();

        try {
            $subaccount = SubAccount::create(
                [
					'business_name' => sanitize_text_field( $params['business_name'] ),
					'account_number' => sanitize_text_field( $params['account_number'] ),
					'bank_code' => sanitize_text_field( $params['bank_code'] ),
                    'percentage_charge' => 0, // Value required by Paystack, but can be set to 0 if not applicable
				]
            );

            // Save the subaccount ID to the vendor's meta
            if ( ! isset( $subaccount['data']['subaccount_code'] ) ) {
                return new WP_Error(
                    'paystack_subaccount_error', __( 'Subaccount creation failed.', 'dokan' ), [
						'status' => 500,
						'error' => __( 'Subaccount code not found in response.', 'dokan' ),
					]
                );
            }

            $vendor_id = dokan_get_current_user_id();
            update_user_meta( $vendor_id, Helper::get_seller_account_id_key(), $subaccount['data']['subaccount_code'] );
            update_user_meta( $vendor_id, Helper::get_seller_enabled_for_received_payment_key(), 1 );

            return rest_ensure_response( $subaccount );
        } catch ( Exception $e ) {
            return new WP_Error(
                'paystack_subaccount_error', __( 'Failed to create subaccount.', 'dokan' ), [
					'status' => 500,
					'error' => $e->getMessage(),
				]
            );
        }
    }

    public function reconnect( WP_REST_Request $request ) {
        $vendor_id = dokan_get_current_user_id();
        $subaccount_code = sanitize_text_field( $request['subaccount_code'] );

        if ( empty( $subaccount_code ) ) {
            return new WP_Error(
                'paystack_reconnect_error', __( 'No Paystack account connected.', 'dokan' ), [
                    'status' => 400,
                ]
            );
        }

        try {
            $subaccount = SubAccount::get( $subaccount_code );
            if ( ! isset( $subaccount['data']['subaccount_code'] ) ) {
                return new WP_Error(
                    'paystack_reconnect_error', __( 'Subaccount not found.', 'dokan' ), [
                        'status' => 404,
                    ]
                );
            }
            // Update the subaccount code in user meta
            update_user_meta( $vendor_id, Helper::get_seller_account_id_key(), $subaccount['data']['subaccount_code'] );
            update_user_meta( $vendor_id, Helper::get_seller_enabled_for_received_payment_key(), 1 );
			return rest_ensure_response( $subaccount );
        } catch ( Exception $e ) {
            return new WP_Error(
                'paystack_reconnect_error', __( 'Failed to reconnect to Paystack.', 'dokan' ), [
                    'status' => 500,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Disconnect from Paystack by removing the subaccount code.
     *
     * @param WP_REST_Request $request
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function disconnect( WP_REST_Request $request ) {
        $vendor_id = dokan_get_current_user_id();
        $subaccount_code = get_user_meta( $vendor_id, Helper::get_seller_account_id_key(), true );

        if ( empty( $subaccount_code ) ) {
            return new WP_Error(
                'paystack_disconnect_error', __( 'No Paystack account connected.', 'dokan' ), [
                    'status' => 400,
                ]
            );
        }

        // Clear the subaccount code and enabled status
        delete_user_meta( $vendor_id, Helper::get_seller_account_id_key() );
        delete_user_meta( $vendor_id, Helper::get_seller_enabled_for_received_payment_key() );

        return rest_ensure_response( [ 'message' => __( 'Disconnected from Paystack successfully.', 'dokan' ) ] );
    }
}
