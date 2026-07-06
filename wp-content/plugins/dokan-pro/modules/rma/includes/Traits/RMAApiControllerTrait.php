<?php

namespace WeDevs\DokanPro\Modules\RMA\Traits;

use WP_Error;
use WP_REST_Request;

/**
 * Trait for common RMA controller functionality.
 *
 * Contains shared methods used across RMA API controllers to
 * reduce code duplication and standardize API behavior.
 *
 * @since 4.0.0
 */
trait RMAApiControllerTrait {

    /**
     * Validates a warranty request and checks user permissions
     *
     * @param int $request_id The warranty request ID
     *
     * @return array|WP_Error The warranty request data on success, WP_Error on failure
     */
    protected function validate_warranty_request( int $request_id ) {
        $warranty_request = $this->warranty_request->get( $request_id );
        if ( ! $this->is_valid_warranty_request( $warranty_request ) ) {
            return new WP_Error(
                'dokan_rest_no_warranty_request',
                esc_html__( 'No warranty request found.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        // Check if the user has access to the warranty request
        $check_request_permission = $this->check_request_permission( $warranty_request );
        if ( is_wp_error( $check_request_permission ) ) {
            return $check_request_permission;
        }

        return $warranty_request;
    }

    /**
     * Validates warranty request and checks vendor permissions
     * Common functionality for both refund and coupon controllers
     *
     * @param int $request_id The warranty request ID
     *
     * @return array|WP_Error Array containing validated data on success, WP_Error on failure
     */
    protected function validate_warranty_request_for_vendor( int $request_id ) {
        $warranty_request = $this->warranty_request->get( $request_id );

        if ( ! $this->is_valid_warranty_request( $warranty_request ) ) {
            return new WP_Error(
                'dokan_rest_no_warranty_request',
                esc_html__( 'No warranty request found.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        $vendor_id = $warranty_request['vendor']['store_id'];
        $user_id   = dokan_get_current_user_id();

        if ( $user_id !== $vendor_id ) {
            return new WP_Error(
                'dokan_rest_no_permission',
                esc_html__( 'You do not have permission to perform this action for this warranty request.', 'dokan' ),
                [ 'status' => 403 ]
            );
        }

        return $warranty_request;
    }
    /**
     * Check if a warranty request is valid
     *
     * @param WP_Error|array $warranty_request_data Warranty request data
     *
     * @return bool
     */
    protected function is_valid_warranty_request( $warranty_request_data ): bool {
        return ! is_wp_error( $warranty_request_data ) && count( $warranty_request_data ) > 0;
    }

    /**
     * Check user authentication
     *
     * @return bool|WP_Error True if authenticated, WP_Error if not
     */
    protected function check_authentication() {
        if ( ! is_user_logged_in() ) {
            return new WP_Error(
                'dokan_rest_unauthorized',
                esc_html__( 'You must be logged in to access this resource.', 'dokan' ),
                [ 'status' => 401 ]
            );
        }

        return true;
    }

    /**
     * Check general permission for accessing warranty requests
     *
     * @return bool|WP_Error True if has permission, WP_Error if not
     */
    protected function check_general_permission() {
        $user_id = dokan_get_current_user_id();

        if ( dokan_is_user_seller( $user_id, true ) ) {
            if ( ! dokan_is_seller_enabled( $user_id ) ) {
                return new WP_Error(
                    'dokan_rest_seller_disabled',
                    esc_html__( 'Your seller account is not enabled.', 'dokan' ),
                    [ 'status' => 403 ]
                );
            }

            if ( ! current_user_can( 'dokan_view_store_rma_menu' ) ) {
                return new WP_Error(
                    'dokan_rest_seller_no_permission',
                    esc_html__( 'You do not have permission to access this resource.', 'dokan' ),
                    [ 'status' => 403 ]
                );
            }

            return true;
        }

        if ( dokan_is_user_customer( $user_id ) ) {
            return true;
        }

        return new WP_Error(
            'dokan_rest_unauthorized',
            esc_html__( 'You are not authorized to access this resource.', 'dokan' ),
            [ 'status' => 403 ]
        );
    }

    /**
     * Check if the user has permission for refund and coupon operations
     *
     * @return bool|WP_Error
     */
    public function check_refund_coupon_permission() {
        if ( $this->check_authentication() !== true ) {
            return $this->check_authentication();
        }

        if ( current_user_can( 'dokan_view_store_rma_menu' ) ) {
            return true;
        }

        return new WP_Error(
            'dokan_rest_cannot_create',
            esc_html__( 'Sorry, you are not allowed to perform this operation.', 'dokan' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    /**
     * Check if the user has permission to access a specific warranty request
     *
     * @param array $warranty_request Warranty request data
     *
     * @return bool|WP_Error
     */
    protected function check_request_permission( array $warranty_request ) {
        $user_id     = dokan_get_current_user_id();
        $vendor_id   = (int) $warranty_request['vendor']['store_id'];
        $customer_id = (int) $warranty_request['customer']['id'];

        if ( $user_id === $customer_id ) {
            return true;
        }

        if ( $user_id === $vendor_id && dokan_is_seller_enabled( $user_id ) && current_user_can( 'dokan_view_store_rma_menu' ) ) {
            return true;
        }

        return new WP_Error(
            'dokan_rest_unauthorized_warranty_request',
            esc_html__( 'You do not have permission to access this warranty request', 'dokan' ),
            [ 'status' => 403 ]
        );
    }

    /**
     * Get arguments for refund and coupon endpoints
     *
     * @return array
     */
    protected function get_refund_coupon_args(): array {
        return [
            'request_id' => [
                'description' => esc_html__( 'Warranty request ID', 'dokan' ),
                'type'        => 'integer',
                'required'    => true,
            ],
            'refund_amount' => [
                'description' => esc_html__( 'Refund amount', 'dokan' ),
                'type'        => 'number',
                'required'    => true,
            ],
            'line_item_qtys' => [
                'description' => esc_html__( 'Line item quantities', 'dokan' ),
                'type'        => 'object',
                'required'    => false,
            ],
            'line_item_totals' => [
                'description' => esc_html__( 'Line item totals', 'dokan' ),
                'type'        => 'object',
                'required'    => false,
            ],
            'line_item_tax_totals' => [
                'description' => esc_html__( 'Line item tax totals', 'dokan' ),
                'type'        => 'object',
                'required'    => false,
            ],
        ];
    }

    /**
     * Validate refund amount (must be positive)
     *
     * @param float $refund_amount Refund amount to validate
     *
     * @return bool|WP_Error True if valid, WP_Error otherwise
     */
    protected function validate_refund_amount( float $refund_amount ) {
        if ( ! is_numeric( $refund_amount ) || $refund_amount <= 0 ) {
            return new WP_Error(
                'dokan_invalid_refund_amount',
                esc_html__( 'Refund amount must be greater than 0.', 'dokan' ),
                [ 'status' => 400 ]
            );
        }

        return true;
    }
}
