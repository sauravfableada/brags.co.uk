<?php

namespace WeDevs\DokanPro\Modules\RMA\Api;

use WeDevs\Dokan\REST\DokanBaseVendorController;
use WeDevs\DokanPro\Modules\RMA\Traits\RMAApiControllerTrait;
use WeDevs\DokanPro\Modules\RMA\Utils;
use WeDevs\DokanPro\Modules\RMA\WarrantyRequest;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * CouponController class.
 *
 * REST controller for handling coupon-related operations for RMA warranty requests.
 *
 * @since 4.0.0
 */
class CouponController extends DokanBaseVendorController {
    use RMAApiControllerTrait;

    /**
     * Route name
     *
     * @var string
     */
    protected string $base = '/rma/warranty-requests/(?P<request_id>[\d]+)/send-coupon';

    /**
     * The warranty request query object
     *
     * @var WarrantyRequest
     */
    protected WarrantyRequest $warranty_request;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->warranty_request = new WarrantyRequest();
    }

    /**
     * Register all routes related to refund and coupon
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            $this->base,
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'send_coupon_request' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => $this->get_refund_coupon_args(),
                ],
            ]
        );
    }

    /**
     * Send coupon request
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function send_coupon_request( WP_REST_Request $request ) {
        $request_id       = $request['request_id'];
        $warranty_request = $this->validate_warranty_request_for_vendor( $request_id );
        if ( is_wp_error( $warranty_request ) ) {
            return $warranty_request;
        }

        $refund_amount = (float) $request['refund_amount'];
        $amount_validation = $this->validate_refund_amount( $refund_amount );
        if ( is_wp_error( $amount_validation ) ) {
            return $amount_validation;
        }

        try {
            $order_id = (int) $warranty_request['order_id'];
            $order    = wc_get_order( $order_id );

            $data = array(
                'refund_total_amount' => $refund_amount,
                'request_id'          => $request_id,
                'refund_vendor_id'    => $warranty_request['vendor']['store_id'],
            );

            // Create coupon
            Utils::create_coupon( $order, $data );

            return new WP_REST_Response(
                [ 'message' => esc_html__( 'Coupon has been created successfully and sent to customer email', 'dokan' ) ],
                200
            );
        } catch ( \Throwable $exception ) {
            return new WP_Error(
                'coupon-error',
                esc_html__( 'Something went wrong while creating the coupon. Please try again.', 'dokan' ),
                [ 'status' => 400 ]
            );
        }
    }

    /**
     * Check coupon request permissions
     *
     * @return bool|WP_Error
     */
    public function check_permission() {
        return $this->check_refund_coupon_permission();
    }
}
