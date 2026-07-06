<?php

namespace WeDevs\DokanPro\REST;

use RuntimeException;
use Throwable;
use WC_Product;
use WeDevs\Dokan\Abstracts\DokanRESTAdminController;
use WeDevs\DokanPro\ProductRejection\ProductStatusService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Product Rejection REST API Controller
 *
 * Handles REST API endpoints for managing product rejections in Dokan.
 * Provides endpoints for:
 * - Rejecting products
 *
 * @since 3.16.0
 *
 * @package dokan
 */
class ProductRejectionController extends DokanRESTAdminController {
    /**
     * API namespace
     *
     * @since 3.16.0
     *
     * @var string
     */
    protected $namespace = 'dokan/v1';

    /**
     * Route base
     *
     * @since 3.16.0
     *
     * @var string
     */
    protected $rest_base = 'product-rejection';

    /**
     * Product status manager instance
     *
     * @since 3.16.0
     *
     * @var ProductStatusService
     */
    private ProductStatusService $product_status_service;

    /**
     * Constructor
     *
     * @since 3.16.0
     */
    public function __construct() {
        $this->product_status_service = new ProductStatusService();
    }

    /**
     * Register REST API routes
     *
     * @since 3.16.0
     *
     * @return void
     */
    public function register_routes(): void {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/reject',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'reject_product' ],
                    'permission_callback' => [ $this, 'can_manage_products' ],
                    'args'                => $this->get_rejection_params(),
                    'schema'              => [ $this, 'get_rejection_schema' ],
                ],
            ]
        );
    }

    /**
     * Check if user can manage products
     *
     * @since 3.16.0
     *
     * @return bool|WP_Error
     */
    public function can_manage_products() {
        if ( ! $this->check_permission() ) {
            return new WP_Error(
                'dokan_rest_cannot_manage_products',
                __( 'You do not have permission to manage products.', 'dokan' ),
                [ 'status' => rest_authorization_required_code() ]
            );
        }

        return true;
    }

    /**
     * Reject a product
     *
     * @since 3.16.0
     *
     * @param WP_REST_Request $request Request object
     *
     * @return WP_REST_Response|WP_Error
     */
    public function reject_product( WP_REST_Request $request ) {
        try {
            $product_id = absint( $request->get_param( 'product_id' ) );
            $reason     = wp_strip_all_tags( $request->get_param( 'reason' ) );

            // Validate product
            $product = wc_get_product( $product_id );
            if ( ! $product instanceof WC_Product ) {
                throw new RuntimeException( __( 'Invalid product ID', 'dokan' ) );
            }

            /**
             * Fires before product rejection via REST API
             *
             * @since 3.16.0
             *
             * @param WC_Product $product Product being rejected
             * @param string     $reason  Rejection reason
             * @param array      $request Request object
             */
            do_action( 'dokan_pre_product_reject', $product, $reason, $request );

            // Save rejection
            $result = $this->product_status_service->save_rejection_meta( $product, $reason );
            if ( is_wp_error( $result ) ) {
                throw new RuntimeException( $result->get_error_message() );
            }

            $rejection_date    = $this->product_status_service->get_rejection_date( $product_id );
            $rejection_message = $this->product_status_service->get_rejection_message( $product_id );

            /**
             * Fires after product rejection via REST API
             *
             * @since 3.16.0
             *
             * @param WC_Product      $product              Product that was rejected
             * @param string          $rejection_date       Rejection date
             * @param string          $rejection_message    Rejection reason
             * @param WP_REST_Request $request              Request object
             */
            do_action( 'dokan_after_product_reject', $product, $rejection_date, $rejection_message, $request );

            return rest_ensure_response(
                [
                    'message'     => __( 'Product rejected successfully', 'dokan' ),
                    'product_id'  => $product_id,
                    'status'      => 'reject',
                ]
            );
        } catch ( Throwable $e ) {
            dokan_log(
                sprintf(
                    'Error rejecting product via REST API: %s',
                    $e->getMessage()
                )
            );
            return new WP_Error(
                'dokan_rest_reject_product_error',
                $e->getMessage(),
                [ 'status' => 400 ]
            );
        }
    }

    /**
     * Get rejection endpoint parameters
     *
     * @since 3.16.0
     *
     * @return array
     */
    protected function get_rejection_params(): array {
        return [
            'product_id' => [
                'required'          => true,
                'type'             => 'integer',
                'description'      => __( 'Product ID to reject.', 'dokan' ),
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'reason' => [
                'required'          => true,
                'type'             => 'string',
                'description'       => __( 'Reason for rejecting the product.', 'dokan' ),
                'maxLength'            => 500,
            ],
        ];
    }

    /**
     * Get rejection endpoint schema
     *
     * @since 3.16.0
     *
     * @return array
     */
    public function get_rejection_schema(): array {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'product_rejection',
            'type'       => 'object',
            'properties' => [
                'message' => [
                    'description' => __( 'Response message.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'product_id' => [
                    'description' => __( 'Product ID.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'status' => [
                    'description' => __( 'Product status after rejection.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
            ],
        ];
    }
}
