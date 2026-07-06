<?php

namespace WeDevs\DokanPro\Modules\DeliveryTime\REST;

use WeDevs\Dokan\REST\DokanBaseVendorController;
use WeDevs\DokanPro\Modules\DeliveryTime\Helper;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class CalendarEventsApi extends DokanBaseVendorController {

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
            '/' . $this->rest_base . '/calendar-events',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_events' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => [
                        'start_date'  => [
                            'description'       => __( 'Start date for calendar events', 'dokan' ),
                            'type'              => 'string',
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                        'end_date'    => [
                            'description'       => __( 'End date for calendar events', 'dokan' ),
                            'type'              => 'string',
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                        'type_filter' => [
                            'description'       => __( 'Type filter for calendar events', 'dokan' ),
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
     * REST API callback: Gets dashboard calendar events
     *
     * @since 4.0.10
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function get_events( WP_REST_Request $request ) {
        $start_date  = $request->get_param( 'start_date' );
        $end_date    = $request->get_param( 'end_date' );
        $filter_type = $request->get_param( 'type_filter' );

        if ( ! strtotime( $start_date ) || ! strtotime( $end_date ) ) {
            return rest_ensure_response( new WP_Error( 'invalid_date', __( 'Invalid date for delivery time calendar', 'dokan' ), [ 'status' => 400 ] ) );
        }

        // Add after existing date validation
        $start_timestamp = strtotime( $start_date );
        $end_timestamp   = strtotime( $end_date );
        $max_range_days  = 365; // Maximum 1 year range

        if ( ( $end_timestamp - $start_timestamp ) > ( $max_range_days * DAY_IN_SECONDS ) ) {
            return rest_ensure_response( new WP_Error( 'date_range_too_large', __( 'Date range cannot exceed one year', 'dokan' ), [ 'status' => 400 ] ) );
        }

        $calendar_events = Helper::get_dashboard_calendar_event( $start_date, $end_date, $filter_type );
        $calendar_events = $this->prepare_item_for_response( $calendar_events, $request );

        return rest_ensure_response( $calendar_events );
    }

    /**
     * Prepares the item for the REST response.
     *
     * @since 4.0.10
     *
     * @param array           $calendar_events The calendar events data.
     * @param WP_REST_Request $request         The request object.
     *
     * @return array
     */
    public function prepare_item_for_response( $calendar_events, $request ): array {
        return apply_filters( 'dokan_rest_prepare_calendar_event_data', $calendar_events, $request );
    }
    /**
     * Retrieve the item schema for calendar events.
     *
     * @since 4.0.10
     *
     * @return array The JSON schema definition for calendar events.
     */
    public function get_item_schema(): array {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title'   => 'calendar_events',
            'type'    => 'array',
            'items'   => [
                'type'       => 'object',
                'properties' => [
                    'title' => [
                        'description' => __( 'Event title.', 'dokan' ),
                        'type'        => 'string',
                        'context'     => [ 'view' ],
                        'readonly'    => true,
                        'required'    => true,
                    ],
                    'start' => [
                        'description' => __( 'Event start date/time (Y-m-d\TH:i).', 'dokan' ),
                        'type'        => 'string',
                        'format'      => 'date-time',
                        'context'     => [ 'view' ],
                        'readonly'    => true,
                        'required'    => true,
                    ],
                    'end'   => [
                        'description' => __( 'Event end date/time (Y-m-d\TH:i).', 'dokan' ),
                        'type'        => 'string',
                        'format'      => 'date-time',
                        'context'     => [ 'view' ],
                        'readonly'    => true,
                        'required'    => true,
                    ],
                    'url'   => [
                        'description' => __( 'Event URL.', 'dokan' ),
                        'type'        => 'string',
                        'format'      => 'uri',
                        'context'     => [ 'view' ],
                        'readonly'    => true,
                        'required'    => true,
                    ],
                    'info'  => [
                        'description' => __( 'Additional event info.', 'dokan' ),
                        'type'        => 'object',
                        'context'     => [ 'view' ],
                        'readonly'    => true,
                        'properties'  => [
                            'body' => [
                                'description' => __( 'HTML body for event details.', 'dokan' ),
                                'type'        => 'string',
                                'context'     => [ 'view' ],
                                'readonly'    => true,
                                'required'    => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
