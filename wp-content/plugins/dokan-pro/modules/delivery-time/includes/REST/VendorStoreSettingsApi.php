<?php

namespace WeDevs\DokanPro\Modules\DeliveryTime\REST;

use WeDevs\Dokan\REST\DokanBaseVendorController;
use WeDevs\DokanPro\Modules\DeliveryTime\Helper as DHelper;
use WeDevs\DokanPro\Modules\DeliveryTime\StorePickup\Helper;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

class VendorStoreSettingsApi extends DokanBaseVendorController {

    /**
     * Endpoint namespace.
     *
     * @since 4.0.10
     *
     * @var string
     */
    protected $namespace = 'dokan/v1';

    /**
     * Route base.
     *
     * @since 4.0.10
     *
     * @var string
     */
    protected $rest_base = 'delivery-time';

    /**
     * Register routes
     *
     * @since 4.0.10
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/store-settings',
            [
                [
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => [ $this, 'get_settings' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                ],
                [
                    'methods'  => WP_REST_Server::EDITABLE,
                    'callback' => [ $this, 'save_settings' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
    }

    /**
     * Get vendor delivery time settings.
     *
     * @since 4.0.10
     *
     * @param WP_REST_Request $request
     *
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function get_settings( $request ) {
        $vendor_id = dokan_get_current_user_id();

        $vendor_delivery_time_settings = DHelper::get_delivery_time_settings( $vendor_id );
        $all_time_slots                = DHelper::get_all_delivery_time_slots();
        $vendor_can_override_settings  = DHelper::vendor_can_override_settings();
        $enable_store_pickup           = Helper::is_store_pickup_location_active( $vendor_id, false );
        $all_delivery_days             = dokan_get_translated_days();
        $delivery_opening_time         = isset( $vendor_delivery_time_settings['opening_time'] ) ? (array) $vendor_delivery_time_settings['opening_time'] : [];
        $delivery_closing_time         = isset( $vendor_delivery_time_settings['closing_time'] ) ? (array) $vendor_delivery_time_settings['closing_time'] : [];
        $selected_delivery_days        = isset( $vendor_delivery_time_settings['delivery_day'] ) ? $vendor_delivery_time_settings['delivery_day'] : [];
        $delivery_times                = [];

        foreach ( $all_delivery_days as $day_key => $day ) {
            $working_status = ! empty( $selected_delivery_days[ $day_key ] ) ? '1' : '0';
            $opening_time   = DHelper::get_delivery_times( $day_key, $delivery_opening_time );
            $closing_time   = DHelper::get_delivery_times( $day_key, $delivery_closing_time );
            $full_day       = false;
            $times          = [
                [
                    'index'        => 0,
                    'opening_time' => $opening_time,
                    'closing_time' => $closing_time,
                ],
            ];

            if ( $opening_time === '12:00 am' && $closing_time === '11:59 pm' ) {
                $full_day = true;
            }

            $times_length = count( (array) $delivery_opening_time[ $day_key ] );
            for ( $index = 1; $index < $times_length; $index++ ) {
                $times[] = [
                    'index'        => $index,
                    'opening_time' => DHelper::get_delivery_times( $day_key, $delivery_opening_time, $index ),
                    'closing_time' => DHelper::get_delivery_times( $day_key, $delivery_closing_time, $index ),
                ];
            }

            $delivery_times[ $day_key ] = [
                'day'            => $day,
                'day_short'      => dokan_get_translated_days( $day_key, 'short' ),
                'working_status' => $working_status,
                'full_day'       => $full_day,
                'times'          => $times,
            ];
        }

        $response = [
            'vendor_delivery_time_settings' => $vendor_delivery_time_settings,
            'all_time_slots'                => $all_time_slots,
            'vendor_can_override_settings'  => $vendor_can_override_settings,
            'enable_store_pickup'           => $enable_store_pickup,
            'delivery_times'                => $delivery_times,
        ];
        $response = $this->prepare_item_for_response( $response, $request );

        return rest_ensure_response( $response );
    }

    /**
     * Prepares the item for the REST response.
     *
     * @since 4.0.10
     *
     * @param array           $response The response data.
     * @param WP_REST_Request $request  The request object.
     *
     * @return array
     */
    public function prepare_item_for_response( $response, $request ) {
        return apply_filters( 'dokan_rest_prepare_delivery_time_store_settings_data', $response, $request );
    }

    /**
     * Save vendor delivery time settings.
     *
     * @since 4.0.10
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function save_settings( WP_REST_Request $request ) {
        $data      = $request->get_json_params();
        $vendor_id = dokan_get_current_user_id();

        // Validate and sanitize as in your original function
        $delivery_support             = isset( $data['delivery_support'] ) ? sanitize_text_field( $data['delivery_support'] ) : 'off';
        $vendor_can_override_settings = dokan_get_option( 'allow_vendor_override_settings', 'dokan_delivery_time', 'off' );

        if ( empty( $vendor_can_override_settings ) || 'off' === $vendor_can_override_settings ) {
            $user_settings = [ 'delivery_support' => $delivery_support ];
            update_user_meta( $vendor_id, '_dokan_vendor_delivery_time_settings', $user_settings );
            do_action( 'dokan_delivery_time_disabled_override' );

            return rest_ensure_response(
                [
                    'success' => true,
                    'message' => 'Settings saved.',
                ]
            );
        }

        // Continue with the rest of your logic, adapting $_POST to $data
        $delivery_day                 = isset( $data['delivery_day'] ) && is_array( $data['delivery_day'] ) ? array_map( 'sanitize_text_field', $data['delivery_day'] ) : [];
        $preorder_date                = isset( $data['preorder_date'] ) ? sanitize_text_field( $data['preorder_date'] ) : 0;
        $order_per_slot               = isset( $data['order_per_slot'] ) ? sanitize_text_field( $data['order_per_slot'] ) : 0;
        $time_slot_minutes            = isset( $data['time_slot_minutes'] ) ? sanitize_text_field( $data['time_slot_minutes'] ) : '';
        $delivery_prep_date           = isset( $data['delivery_prep_date'] ) ? sanitize_text_field( $data['delivery_prep_date'] ) : 0;
        $enable_delivery_notification = isset( $data['enable_delivery_notification'] ) ? sanitize_text_field( $data['enable_delivery_notification'] ) : 'off';
        // New vendor buffer settings
        $delivery_buffer_unit         = isset( $data['delivery_buffer_unit'] ) ? sanitize_text_field( $data['delivery_buffer_unit'] ) : 'days';
        $delivery_buffer_value        = isset( $data['delivery_buffer_value'] ) ? absint( $data['delivery_buffer_value'] ) : 0;

        if ( empty( $delivery_day ) ) {
            return new WP_Error( 'empty_delivery_day', __( 'Delivery day is required.', 'dokan' ), [ 'status' => 400 ] );
        }
        if ( (int) $time_slot_minutes < 10 || (int) $time_slot_minutes > 1440 ) {
            return new WP_Error( 'invalid_time_slot', __( 'Time slot minutes must be between 10 and 1440.', 'dokan' ), [ 'status' => 400 ] );
        }
        if ( null === $order_per_slot || (int) $order_per_slot < 0 ) {
            return new WP_Error( 'invalid_order_per_slot', __( 'Order per slot must be 0 or greater.', 'dokan' ), [ 'status' => 400 ] );
        }

        // Validate buffer unit/value
        $allowed_units = [ 'days', 'hours' ];
        if ( ! in_array( $delivery_buffer_unit, $allowed_units, true ) ) {
            return new WP_Error( 'invalid_delivery_buffer_unit', __( 'Invalid delivery buffer unit. Please select Days or Hours.', 'dokan' ), [ 'status' => 400 ] );
        }
        if ( 'hours' === $delivery_buffer_unit && ( null === $delivery_buffer_value || (int) $delivery_buffer_value < 0 ) ) {
            return new WP_Error( 'invalid_delivery_buffer_value', __( 'Delivery buffer hours can not be empty or less than 0', 'dokan' ), [ 'status' => 400 ] );
        }

        // Handle opening/closing times and time slots
        $opening_time = [];
        $closing_time = [];
        foreach ( $delivery_day as $day => $value ) {
            if ( empty( $value ) ) {
                $opening_time[ $day ] = [];
                $closing_time[ $day ] = [];
                continue;
            }
            $opening_time[ $day ] = ! empty( $data['opening_time'][ $day ] ) ? array_map( 'sanitize_text_field', $data['opening_time'][ $day ] ) : [];
            $closing_time[ $day ] = ! empty( $data['closing_time'][ $day ] ) ? array_map( 'sanitize_text_field', $data['closing_time'][ $day ] ) : [];
        }

        $time_slots = [];
        foreach ( $delivery_day as $day => $value ) {
            if ( empty( $value ) ) {
                continue;
            }
            $opening_time[ $day ] = (array) $opening_time[ $day ];
            $closing_time[ $day ] = (array) $closing_time[ $day ];
            foreach ( $opening_time[ $day ] as $index => $time ) {
                if (
                    empty( $opening_time[ $day ][ $index ] ) ||
                    empty( $closing_time[ $day ][ $index ] ) ||
                    strtotime( $opening_time[ $day ][ $index ] ) > strtotime( $closing_time[ $day ][ $index ] )
                ) {
                    return new WP_Error( 'time_mismatch', __( 'Opening time must be before closing time.', 'dokan' ), [ 'status' => 400 ] );
                }
            }
            $time_slots[ $day ] = DHelper::generate_delivery_time_slots( $time_slot_minutes, $opening_time[ $day ], $closing_time[ $day ] );
        }

        $save_data = [
            'delivery_support'             => $delivery_support,
            'delivery_day'                 => $delivery_day,
            'preorder_date'                => $preorder_date,
            'order_per_slot'               => $order_per_slot,
            'time_slot_minutes'            => $time_slot_minutes,
            'delivery_prep_date'           => $delivery_prep_date,
            'enable_delivery_notification' => $enable_delivery_notification,
            'opening_time'                 => $opening_time,
            'closing_time'                 => $closing_time,
            // Persist new buffer settings
            'delivery_buffer_unit'         => $delivery_buffer_unit,
            'delivery_buffer_value'        => $delivery_buffer_value,
        ];

        update_user_meta( $vendor_id, '_dokan_vendor_delivery_time_settings', $save_data );
        update_user_meta( $vendor_id, '_dokan_vendor_delivery_time_slots', $time_slots );
        do_action( 'dokan_rest_delivery_time_after_save_settings', $request, $vendor_id );

        return rest_ensure_response(
            [
                'success' => true,
                'message' => 'Settings saved.',
            ]
        );
    }

    /**
     * Get the schema for a single item's data.
     *
     * This schema defines the structure and types of data that will be returned
     * for a single item within the context of this component.
     *
     * @since 4.0.10
     *
     * @return array The item schema definition in a structured format.
     */
    public function get_item_schema(): array {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'vendor_store_delivery_time_settings',
            'type'       => 'object',
            'properties' => [
                'vendor_delivery_time_settings' => [
                    'description' => __( 'Vendor delivery time settings.', 'dokan' ),
                    'type'        => 'object',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                    'properties'  => [
                        'delivery_support' => [
                            'description' => __( 'Delivery support status.', 'dokan' ),
                            'type'        => 'string',
                            'enum'        => [ 'on', 'off' ],
                        ],
                        'delivery_day' => [
                            'description' => __( 'Delivery days by week day.', 'dokan' ),
                            'type'        => 'object',
                            'properties'  => [
                                'saturday'   => [ 'type' => 'string' ],
                                'sunday'     => [ 'type' => 'string' ],
                                'monday'     => [ 'type' => 'string' ],
                                'tuesday'    => [ 'type' => 'string' ],
                                'wednesday'  => [ 'type' => 'string' ],
                                'thursday'   => [ 'type' => 'string' ],
                                'friday'     => [ 'type' => 'string' ],
                            ],
                        ],
                        'preorder_date' => [
                            'description' => __( 'Preorder date support.', 'dokan' ),
                            'type'        => 'string',
                        ],
                        'order_per_slot' => [
                            'description' => __( 'Orders allowed per slot.', 'dokan' ),
                            'type'        => 'string',
                        ],
                        'time_slot_minutes' => [
                            'description' => __( 'Time slot duration in minutes.', 'dokan' ),
                            'type'        => 'string',
                        ],
                        'delivery_prep_date' => [
                            'description' => __( 'Delivery preparation date.', 'dokan' ),
                            'type'        => 'string',
                        ],
                        'enable_delivery_notification' => [
                            'description' => __( 'Enable delivery notification.', 'dokan' ),
                            'type'        => 'string',
                            'enum'        => [ 'on', 'off' ],
                        ],
                        'opening_time' => [
                            'description' => __( 'Opening times by day.', 'dokan' ),
                            'type'        => 'object',
                            'properties'  => [
                                'saturday'   => [
                                    'type' => 'array',
                                    'items' => [ 'type' => 'string' ],
                                ],
                                'sunday'     => [
                                    'type' => 'array',
                                    'items' => [ 'type' => 'string' ],
                                ],
                                'monday'     => [
                                    'type' => 'array',
                                    'items' => [ 'type' => 'string' ],
                                ],
                                'tuesday'    => [
                                    'type' => 'array',
                                    'items' => [ 'type' => 'string' ],
                                ],
                                'wednesday'  => [
                                    'type' => 'array',
                                    'items' => [ 'type' => 'string' ],
                                ],
                                'thursday'   => [
                                    'type' => 'array',
                                    'items' => [ 'type' => 'string' ],
                                ],
                                'friday'     => [
                                    'type' => 'array',
                                    'items' => [ 'type' => 'string' ],
                                ],
                            ],
                        ],
                        'closing_time' => [
                            'description' => __( 'Closing times by day.', 'dokan' ),
                            'type'        => 'object',
                            'properties'  => [
                                'saturday'   => [
                                    'type' => 'array',
                                    'items' => [ 'type' => 'string' ],
                                ],
                                'sunday'     => [
                                    'type' => 'array',
                                    'items' => [ 'type' => 'string' ],
                                ],
                                'monday'     => [
                                    'type' => 'array',
                                    'items' => [ 'type' => 'string' ],
                                ],
                                'tuesday'    => [
                                    'type' => 'array',
                                    'items' => [ 'type' => 'string' ],
                                ],
                                'wednesday'  => [
                                    'type' => 'array',
                                    'items' => [ 'type' => 'string' ],
                                ],
                                'thursday'   => [
                                    'type' => 'array',
                                    'items' => [ 'type' => 'string' ],
                                ],
                                'friday'     => [
                                    'type' => 'array',
                                    'items' => [ 'type' => 'string' ],
                                ],
                            ],
                        ],
                    ],
                ],
                'all_time_slots' => [
                    'description' => __( 'All available time slots.', 'dokan' ),
                    'type'        => 'object',
                    'additionalProperties' => [ 'type' => 'string' ],
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'vendor_can_override_settings' => [
                    'description' => __( 'Whether vendor can override settings.', 'dokan' ),
                    'type'        => 'boolean',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'enable_store_pickup' => [
                    'description' => __( 'Store pickup enabled.', 'dokan' ),
                    'type'        => 'string',
                    'enum'        => [ 'yes', 'no' ],
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'delivery_times' => [
                    'description' => __( 'Delivery times by day.', 'dokan' ),
                    'type'        => 'object',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                    'properties'  => [
                        'saturday'   => [ '$ref' => '#/definitions/delivery_time_day' ],
                        'sunday'     => [ '$ref' => '#/definitions/delivery_time_day' ],
                        'monday'     => [ '$ref' => '#/definitions/delivery_time_day' ],
                        'tuesday'    => [ '$ref' => '#/definitions/delivery_time_day' ],
                        'wednesday'  => [ '$ref' => '#/definitions/delivery_time_day' ],
                        'thursday'   => [ '$ref' => '#/definitions/delivery_time_day' ],
                        'friday'     => [ '$ref' => '#/definitions/delivery_time_day' ],
                    ],
                ],
            ],
            'definitions' => [
                'delivery_time_day' => [
                    'type'       => 'object',
                    'properties' => [
                        'day'            => [ 'type' => 'string' ],
                        'day_short'      => [ 'type' => 'string' ],
                        'working_status' => [ 'type' => 'string' ],
                        'full_day'       => [ 'type' => 'boolean' ],
                        'times'          => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'properties' => [
                                    'index'        => [ 'type' => 'integer' ],
                                    'opening_time' => [ 'type' => 'string' ],
                                    'closing_time' => [ 'type' => 'string' ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
