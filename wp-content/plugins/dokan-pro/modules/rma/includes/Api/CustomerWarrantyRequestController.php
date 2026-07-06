<?php

namespace WeDevs\DokanPro\Modules\RMA\Api;

use Throwable;
use WeDevs\DokanPro\Modules\RMA\WarrantyItem;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class CustomerWarrantyRequestController extends WarrantyRequestController {

    /**
     * Route name
     *
     * @var string
     */
    protected string $base = 'customer/warranty-requests';

    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_items' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                    'args'                => $this->get_collection_params(),
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'create_item' ],
                    'permission_callback' => [ $this, 'create_item_permissions_check' ],
                    'args'                => $this->get_create_item_args(),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->base . '/(?P<id>[\d]+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_item' ],
                    'permission_callback' => 'is_user_logged_in',
                    'args'                => [
                        'id' => [
                            'description' => __( 'Unique identifier for the warranty request.', 'dokan' ),
                            'type'        => 'integer',
                        ],
                    ],
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
    }

    /**
     * Prepares query arguments for retrieving warranty requests for the current customer.
     *
     * Removes the `vendor_id` from the parent query arguments and adds the current user's ID as `customer_id`.
     *
     * @param WP_REST_Request $request REST request object containing query parameters.
     *
     * @return array Modified query arguments for fetching warranty requests.
     */
    protected function prepare_query_args( WP_REST_Request $request ): array {
        $args = parent::prepare_query_args( $request );
        unset( $args['vendor_id'] );
        $args['customer_id'] = get_current_user_id();

        return $args;
    }

    /**
     * Prepares a single warranty request item for database insertion or update.
     *
     * Adds the current user as `customer_id` and includes the `vendor_id` from the request.
     *
     * @param WP_REST_Request $request REST request object containing the warranty request data.
     *
     * @return array Sanitized and prepared warranty request data for database operations.
     */
    protected function prepare_item_for_database( $request ): array {
        $item                = parent::prepare_item_for_database( $request );
        $item['customer_id'] = get_current_user_id();
        $item['status']      = 'new';
        $item['vendor_id']   = dokan_get_seller_id_by_order( $request['order_id'] );

        return $item;
    }

    /**
     * Get collection params.
     *
     * @return array
     */
    protected function get_create_item_args(): array {
        $params = parent::get_create_item_args();
        unset( $params['customer_id'] );
        unset( $params['status'] );

        return $params;
    }

    /**
     * Get warranty items for a specific order
     *
     * Retrieves warranty-eligible items from a customer's order, similar to the request-warranty.php template.
     *
     * @param WP_REST_Request $request REST request object containing order_id and optionally vendor_id.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_item( $request ) {
        try {
            $order_id = absint( $request['id'] );

            // Get the order
            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                return new WP_Error(
                    'dokan_order_not_found',
                    __( 'Order not found.', 'dokan' ),
                    [ 'status' => 404 ]
                );
            }

            if ( $order->get_meta( 'has_sub_order' ) ) {
                return new WP_Error(
                    'dokan_sub_order_not_supported',
                    __( 'Warranty requests for orders with sub-orders are not supported via this endpoint.', 'dokan' ),
                    [ 'status' => 400 ]
                );
            }

            $vendor_id = dokan_get_seller_id_by_order( $order->get_id() );

            $warranty_items  = [];
            $default_reasons = dokan_rma_refund_reasons();
            $store_warranty  = get_user_meta( $vendor_id, '_dokan_rma_settings', true );
            $current_reasons = null;

            foreach ( $order->get_items() as $item ) {

                // Get product from the order item
                $product = null;
                if ( method_exists( $item, 'get_product' ) ) {
                    $product = $item->get_product();
                }

                if ( empty( $product ) ) {
                    continue;
                }

                $warranty_item = new WarrantyItem( $item->get_id() );
                $warranty      = dokan_get_order_item_warranty( $item );

                $warranty_data = [
                    'has_warranty'       => false,
                    'title'              => $product->get_title(),
                    'item_id'            => $item->get_id(),
                    'product_id'         => $product->get_id(),
                    'product_url'        => $product->get_permalink(),
                    'product_price'      => $product->get_price(),
                    'product_price_html' => $product->get_price_html(),
                    'thumbnail'          => wp_get_attachment_url( $product->get_image_id() ),
                    'quantity_remaining' => $warranty_item->get_quantity_remaining(),
                    'warranty_duration'  => dokan_get_warranty_duration_string( $warranty, $order ),
                ];

                if ( $warranty_item->has_warranty() ) {
                    $current_reasons               = $warranty_item->get_reasons();
                    $warranty_data['has_warranty'] = true;
                }
                $warranty_items[] = $warranty_data;
            }

            $store_reasons    = $store_warranty['reasons'] ?? array();
            $store_warranty   = ! empty( $current_reasons ) ? $current_reasons : $store_reasons;
            $warranty_reasons = [];
            foreach ( $default_reasons as $key => $reason ) {
                if ( in_array( $key, $store_warranty, true ) ) {
                    $warranty_reasons[ $key ] = apply_filters( 'dokan_pro_rma_reason', $reason );
                }
            }

            $response_data = apply_filters(
                'dokan_warranty_request_response', [
                    'order_id'        => $order_id,
                    'items'           => $warranty_items,
                    'request_types'   => dokan_warranty_request_type(),
                    'request_reasons' => $warranty_reasons,
                ]
            );

            return rest_ensure_response( $response_data );
        } catch ( Throwable $e ) {
            return new WP_Error(
                'dokan_warranty_request_error',
                __( 'An error occurred while retrieving warranty items.', 'dokan' ),
                [ 'status' => 500 ]
            );
        }
    }
}
