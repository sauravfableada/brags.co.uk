<?php

namespace WeDevs\DokanPro\Modules\DeliveryTime\REST;

use WeDevs\Dokan\REST\DokanBaseCustomerController;
use WeDevs\DokanPro\Modules\DeliveryTime\StorePickup\Helper;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class LocationSlotApi extends DokanBaseCustomerController {

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
            '/' . $this->rest_base . '/location-slot',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_vendor_delivery_location_slot' ],
                    'permission_callback' => '__return_true',
                ],
                'args' => [
                    'vendorId' => [
                        'description'       => __( 'Vendor id to get the time sot', 'dokan' ),
                        'type'              => 'integer',
                        'required'          => true,
                        'sanitize_callback' => 'absint',
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
    public function get_vendor_delivery_location_slot( $request ) {
        $vendor_id = $request->get_param( 'vendorId' );

        $vendor_store_locations = Helper::get_vendor_store_pickup_locations( $vendor_id, false, true );
        $vendor_store_locations = $this->prepare_item_for_response( $vendor_store_locations, $request );

        return rest_ensure_response( $vendor_store_locations );
    }

    /**
     * Prepare delivery times.
     *
     * @since 3.15.0
     *
     * @param $vendor_store_locations
     * @param $request
     *
     * @return array
     */
    public function prepare_item_for_response( $vendor_store_locations, $request ): array {
        $formatted_locations = [];

        foreach ( $vendor_store_locations as $location_name => $location ) {
            $formatted_locations[ $location_name ] = Helper::get_formatted_vendor_store_pickup_location( $location, ' ', $location['location_name'] );
        }

        return apply_filters( 'dokan_rest_prepare_delivery_time_location_data', $formatted_locations, $request );
    }

    /**
     * Retrieve the schema for a single item.
     *
     * @since 3.15.0
     *
     * @return array The item schema.
     */
    public function get_item_schema(): array {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'vendor_store_locations',
            'type'       => 'object',
            'additionalProperties' => [
                'type'        => 'string',
                'description' => __( 'Store pickup location description.', 'dokan' ),
            ],
            'context'     => [ 'view' ],
            'readonly'    => true,
        ];
    }
}
