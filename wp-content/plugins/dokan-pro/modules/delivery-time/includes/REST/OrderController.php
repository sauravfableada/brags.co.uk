<?php

namespace WeDevs\DokanPro\Modules\DeliveryTime\REST;

use WeDevs\Dokan\REST\DokanBaseVendorController;
use WeDevs\DokanPro\Modules\DeliveryTime\Helper;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class OrderController extends DokanBaseVendorController {

    /**
     * Endpoint namespace.
     *
     * @since 4.3.2
     *
     * @var string
     */
    protected $namespace = 'dokan/v1';

    /**
     * Route base.
     *
     * @since 4.3.2
     *
     * @var string
     */
    protected $rest_base = 'delivery-time';

    /**
     * Register routes
     *
     * @since 4.3.2
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/order/(?P<order_id>[\d]+)/',
            [
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update_order_delivery_time' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => [
                        'order_id'              => [
                            'description'       => __( 'Order id to update the delivery time slot', 'dokan' ),
                            'type'              => 'integer',
                            'required'          => true,
                            'sanitize_callback' => 'absint',
                        ],
                        'delivery_type'         => [
                            'description'       => __( 'Selected delivery type', 'dokan' ),
                            'type'              => 'string',
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_text_field',
                            'enum'              => [ 'delivery', 'store-pickup' ],
                        ],
                        'delivery_date'         => [
                            'description'       => __( 'Delivery date in Y-m-d format', 'dokan' ),
                            'type'              => 'string',
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_text_field',
                            'validate_callback' => function ( $value ) {
                                return (bool) strtotime( $value );
                            },
                        ],
                        'delivery_date_slot'    => [
                            'description'       => __( 'Vendor selected current delivery date slot', 'dokan' ),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                            'default'           => '-',
                        ],
                        'delivery_time_slot'    => [
                            'description'       => __( 'Delivery time slot', 'dokan' ),
                            'type'              => 'string',
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                        'store_pickup_location' => [
                            'description'       => __( 'Store pickup location', 'dokan' ),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                    ],
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
    }

    /**
     * Update Order Delivery Time
     *
     * @since 4.3.2
     *
     * @param WP_REST_Request $request
     *
     * @return WP_Error|WP_REST_Response
     */
    public function update_order_delivery_time( WP_REST_Request $request ) {
        $order_id = $request->get_param( 'order_id' );

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return new WP_Error( 'dokan_rest_invalid_order_id', __( 'Invalid order ID.', 'dokan' ), [ 'status' => 404 ] );
        }

        $vendor_id = dokan_get_seller_id_by_order( $order_id );

        // Ensure vendor has permission to modify this order
        if ( ! $this->can_access_vendor_store( $vendor_id, dokan_get_current_user_id() ) ) {
            return new WP_Error( 'dokan_rest_order_permission_error', __( 'You do not have permission to manage this order.', 'dokan' ), [ 'status' => 403 ] );
        }

        $order_delivery_type                        = $request->get_param( 'delivery_type' );
        $delivery_date                              = $request->get_param( 'delivery_date' );
        $vendor_selected_current_delivery_date_slot = $request->get_param( 'delivery_date_slot' );
        $delivery_time_slot                         = $request->get_param( 'delivery_time_slot' );
        $location_data                              = $request->get_param( 'store_pickup_location' );

        if ( 'store-pickup' === $order_delivery_type && empty( $location_data ) ) {
            return new WP_Error(
                'dokan_rest_missing_pickup_location',
                __( 'Store pickup location is required when delivery type is store-pickup.', 'dokan' ),
                [ 'status' => 400 ]
            );
        }

        $prev_delivery_info = Helper::get_order_delivery_info( $vendor_id, $order_id );

        $data = [
            'order_id'                                   => $order_id,
            'delivery_date'                              => $delivery_date,
            'prev_delivery_info'                         => $prev_delivery_info,
            'delivery_time_slot'                         => $delivery_time_slot,
            'store_pickup_location'                      => $location_data,
            'selected_delivery_type'                     => $order_delivery_type,
            'vendor_selected_current_delivery_date_slot' => $vendor_selected_current_delivery_date_slot,
        ];

        Helper::update_delivery_time_date_slot( $data );

        /**
         * Fires before persisting delivery info so listeners can read previous DB state.
         * Matches Vendor.php AJAX handler ordering for backward compatibility.
         *
         * @since 3.7.8
         *
         * @param int   $vendor_id Vendor ID.
         * @param array $data      Delivery update data including order_id, delivery_date,
         *                         delivery_time_slot, selected_delivery_type,
         *                         store_pickup_location, and prev_delivery_info.
         */
        do_action( 'dokan_after_vendor_update_order_delivery_info', $vendor_id, $data );

        return rest_ensure_response(
            [
                'message' => __( 'Delivery time updated successfully.', 'dokan' ),
            ]
        );
    }

    /**
     * Get the item schema.
     *
     * @since 4.3.2
     *
     * @return array
     */
    public function get_item_schema(): array {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'delivery_time_order',
            'type'       => 'object',
            'properties' => [
                'message' => [
                    'description' => __( 'Success message.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
            ],
        ];
    }
}
