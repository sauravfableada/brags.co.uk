<?php

namespace WeDevs\DokanPro\Modules\RMA\Api;

use WeDevs\Dokan\REST\DokanBaseVendorController;
use WeDevs\Dokan\Exceptions\DokanException;
use WeDevs\DokanPro\Modules\RMA\Traits\RMAApiControllerTrait;
use WeDevs\DokanPro\Modules\RMA\WarrantyRequest;
use WeDevs\DokanPro\Refund\Ajax;
use WeDevs\DokanPro\Refund\Refund;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * RefundController class.
 *
 * REST controller for handling refund operations for RMA warranty requests.
 *
 * @since 4.0.0
 */
class RefundController extends DokanBaseVendorController {
    use RMAApiControllerTrait;

    /**
     * Route name
     *
     * @var string
     */
    protected string $base = '/rma/warranty-requests/(?P<request_id>[\d]+)/send-refund';

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
                    'callback'            => [ $this, 'send_refund_request' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => $this->get_refund_coupon_args(),
                ],
            ]
        );
    }

    /**
     * Send refund request
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function send_refund_request( WP_REST_Request $request ) {
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

        $order_id = (int) $warranty_request['order_id'];
        if ( dokan_pro()->refund->has_pending_request( $order_id ) ) {
            return new WP_Error(
                'dokan_rma_pending_request',
                esc_html__( 'You have already a processing refund request for this order.', 'dokan' ),
                [ 'status' => 400 ]
            );
        }

        $vendor_id = (int) $warranty_request['vendor']['store_id'];

        // translators: %s: request id
        $refund_reason = sprintf( esc_html__( 'Warranty Request from Customer for RMA request #%1$s', 'dokan' ), $request_id );

        $data = [
            'order_id'               => $order_id,
            'seller_id'              => $vendor_id,
            'refund_amount'          => $refund_amount,
            'refund_reason'          => $refund_reason,
            'line_item_qtys'         => wp_json_encode( $request->get_param( 'line_item_qtys' ) ),
            'line_item_totals'       => wp_json_encode( $request->get_param( 'line_item_totals' ) ),
            'line_item_tax_totals'   => wp_json_encode( $request->get_param( 'line_item_tax_totals' ) ),
            'api_refund'             => '1',
            'restock_refunded_items' => null,
            'status'                 => 0,
        ];

        try {
            $refund = Ajax::create_refund_request( $data );

            /**
             * Fires after a refund request is created through RMA
             *
             * @since 4.0.0
             *
             * @param int    $order_id The order ID
             * @param array  $data The refund request data
             * @param Refund $refund  The refund object
             */
            do_action( 'dokan_rma_requested', $order_id, $data, $refund );

            /**
             * Fires after a refund amount is requested through RMA
             *
             * @since 4.0.0
             *
             * @param int   $order_id      The order ID
             * @param float $refund_amount The refund amount
             * @param array $data      The refund request data
             */
            do_action( 'dokan_rma_requested_amount', $data['order_id'], $data['refund_amount'], $data );

            return new WP_REST_Response( [ 'message' => esc_html__( 'Refund request successfully sent for admin approval.', 'dokan' ) ], 200 );
        } catch ( \Throwable $e ) {
            $message = $e->getMessage();
            if ( $e instanceof DokanException ) {
                $error_code = $e->get_error_code();

                if ( $error_code instanceof WP_Error ) {
                    $message = implode( ' ', $error_code->get_error_messages() );
                } else {
                    $message = $e->get_message();
                }
            }

            return new WP_Error( 'dokan_rma_refund_error', $message, [ 'status' => 400 ] );
        }
    }

    /**
     * Check refund request permissions
     *
     * @return bool|WP_Error
     */
    public function check_permission() {
        return $this->check_refund_coupon_permission();
    }
}
