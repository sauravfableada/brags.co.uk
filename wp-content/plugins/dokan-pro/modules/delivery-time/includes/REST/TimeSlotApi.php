<?php

namespace WeDevs\DokanPro\Modules\DeliveryTime\REST;

use WeDevs\Dokan\REST\DokanBaseCustomerController;
use WeDevs\DokanPro\Modules\DeliveryTime\Helper as DHelper;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;


class TimeSlotApi extends DokanBaseCustomerController {

    /**
     * Endpoint namespace.
     *
     * @since 3.15.0
     *
     * @var string
     */
    protected $namespace = 'dokan/v1';

    /**
     * Route base.
     *
     * @since 3.15.0
     *
     * @var string
     */
    protected $rest_base = 'delivery-time';

    /**
     * Register routes
     *
     * @since 3.15.0
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/time-slot',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_vendor_delivery_time_slot' ],
                    'permission_callback' => '__return_true',
                ],
                'args' => [
                    'vendorId' => [
                        'description'       => __( 'Vendor id to get the time sot', 'dokan' ),
                        'type'              => 'integer',
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                    ],
                    'date'     => [
                        'description'       => __( 'Date to get the time sot', 'dokan' ),
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
    }

    /**
     * Gets vendor delivery time slot from ajax request.
     *
     * @since 3.15.0
     *
     * @param WP_REST_Request $request
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_vendor_delivery_time_slot( $request ) {
        $vendor_id = $request->get_param( 'vendorId' );
        $date      = $request->get_param( 'date' );

        $delivery_time_slots = DHelper::get_current_date_active_delivery_time_slots( $vendor_id, $date );
        $delivery_time_slots = $this->prepare_item_for_response( $delivery_time_slots, $request );

        return rest_ensure_response( $delivery_time_slots );
    }

    /**
     * Prepares the item for the REST response.
     *
     * @since 3.15.0
     *
     * @param array           $delivery_time_slots The delivery time slots data.
     * @param WP_REST_Request $request             The request object.
     *
     * @return array
     */
    public function prepare_item_for_response( $delivery_time_slots, $request ): array {
        return apply_filters( 'dokan_rest_prepare_delivery_time_slot_data', $delivery_time_slots, $request );
    }

    /**
     * Get the public item schema.
     *
     * @since 3.15.0
     *
     * @return array
     */
    public function get_item_schema(): array {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'vendor_time_slots',
            'type'       => 'object',
            'additionalProperties' => [
                'type'       => 'object',
                'properties' => [
                    'start' => [
                        'description' => __( 'Start time of the slot.', 'dokan' ),
                        'type'        => 'string',
                        'context'     => [ 'view' ],
                        'readonly'    => true,
                    ],
                    'end' => [
                        'description' => __( 'End time of the slot.', 'dokan' ),
                        'type'        => 'string',
                        'context'     => [ 'view' ],
                        'readonly'    => true,
                    ],
                ],
                'required' => [ 'start', 'end' ],
            ],
            'context'     => [ 'view' ],
            'readonly'    => true,
        ];
    }
}
