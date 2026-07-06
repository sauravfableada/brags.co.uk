<?php

namespace WeDevs\DokanPro\Modules\Booking\REST;

use WeDevs\Dokan\Abstracts\DokanRESTController;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Booking Calendar REST Controller.
 *
 * Returns FullCalendar-compatible events for the React booking calendar.
 *
 * @since 5.0.0
 */
class BookingCalendarController extends DokanRESTController {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'dokan/v1';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'booking/calendar-events';

    /**
     * Color palette for product-based color coding.
     *
     * @var string[]
     */
    private $colors = [
        '#3498db',
        '#1abc9c',
        '#2ecc71',
        '#f1c40f',
        '#e67e22',
        '#e74c3c',
        '#9b59b6',
        '#2980b9',
        '#16a085',
        '#27ae60',
        '#f39c12',
        '#d35400',
        '#c0392b',
        '#8e44ad',
        '#34495e',
    ];

    /**
     * Register routes.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_events' ],
                    'permission_callback' => [ $this, 'get_events_permissions_check' ],
                    'args'                => [
                        'start_date'       => [
                            'description'       => __( 'Start date (Y-m-d).', 'dokan' ),
                            'type'              => 'string',
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                        'end_date'         => [
                            'description'       => __( 'End date (Y-m-d).', 'dokan' ),
                            'type'              => 'string',
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                        'filter_bookings'  => [
                            'description'       => __( 'Filter by product or resource ID.', 'dokan' ),
                            'type'              => 'integer',
                            'required'          => false,
                            'sanitize_callback' => 'absint',
                        ],
                    ],
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/filters',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_filters' ],
                    'permission_callback' => [ $this, 'get_events_permissions_check' ],
                ],
            ]
        );
    }

    /**
     * Check permissions.
     *
     * @param WP_REST_Request $request Request object.
     *
     * @since 5.0.0
     *
     * @return bool|WP_Error
     */
    public function get_events_permissions_check( $request ) {
        if ( ! current_user_can( 'dokan_manage_booking_calendar' ) ) {
            return new WP_Error(
                'dokan_rest_cannot_view_calendar',
                __( 'You do not have permission to view the booking calendar.', 'dokan' ),
                [ 'status' => rest_authorization_required_code() ]
            );
        }

        return true;
    }

    /**
     * Get calendar events.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function get_events( WP_REST_Request $request ) {
        $start_date     = $request->get_param( 'start_date' );
        $end_date       = $request->get_param( 'end_date' );
        $product_filter = $request->get_param( 'filter_bookings' );

        if ( ! strtotime( $start_date ) || ! strtotime( $end_date ) ) {
            return new WP_Error(
                'invalid_date',
                __( 'Invalid date format.', 'dokan' ),
                [ 'status' => 400 ]
            );
        }

        $start_timestamp = strtotime( 'midnight', strtotime( $start_date ) );
        $end_timestamp   = strtotime( 'midnight +1 day -1 min', strtotime( $end_date ) );
        $max_range_days  = 365;

        if ( ( $end_timestamp - $start_timestamp ) > ( $max_range_days * DAY_IN_SECONDS ) ) {
            return new WP_Error(
                'date_range_too_large',
                __( 'Date range cannot exceed one year.', 'dokan' ),
                [ 'status' => 400 ]
            );
        }

        $bookings = $this->get_bookings_in_range( $start_timestamp, $end_timestamp, $product_filter );
        $events   = $this->transform_bookings_to_events( $bookings );

        return rest_ensure_response( $events );
    }

    /**
     * Get filter options (products and resources) for the current vendor.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response
     */
    public function get_filters( WP_REST_Request $request ) {
        $filters = [];

        // Products.
        $products = get_posts(
            [
                'post_status'      => 'publish',
                'post_type'        => 'product',
                'author'           => dokan_get_current_user_id(),
                'posts_per_page'   => -1,
                'tax_query'        => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                    [
                        'taxonomy' => 'product_type',
                        'field'    => 'slug',
                        'terms'    => 'booking',
                    ],
                ],
                'suppress_filters' => true,
            ]
        );

        $product_options = [];
        foreach ( $products as $product ) {
            $product_options[] = [
                'value' => $product->ID,
                'label' => $product->post_title,
            ];
        }

        if ( ! empty( $product_options ) ) {
            $filters[] = [
                'label'   => __( 'By Bookable Product', 'dokan' ),
                'options' => $product_options,
            ];
        }

        // Resources.
        $resources = get_posts(
            [
                'post_status'      => 'publish',
                'post_type'        => 'bookable_resource',
                'posts_per_page'   => -1,
                'orderby'          => 'menu_order',
                'order'            => 'asc',
                'author'           => dokan_get_current_user_id(),
                'suppress_filters' => true,
            ]
        );

        $resource_options = [];
        foreach ( $resources as $resource ) {
            $resource_options[] = [
                'value' => $resource->ID,
                'label' => $resource->post_title,
            ];
        }

        if ( ! empty( $resource_options ) ) {
            $filters[] = [
                'label'   => __( 'By Resource', 'dokan' ),
                'options' => $resource_options,
            ];
        }

        return rest_ensure_response( $filters );
    }

    /**
     * Fetch bookings in a date range for the current vendor.
     *
     * @since 5.0.0
     *
     * @param int $start_timestamp Start timestamp.
     * @param int $end_timestamp   End timestamp.
     * @param int $product_filter  Optional product/resource ID filter.
     *
     * @return array
     */
    private function get_bookings_in_range( $start_timestamp, $end_timestamp, $product_filter = 0 ) {
        if ( ! class_exists( 'WC_Booking_Data_Store' ) && ! class_exists( 'WC_Bookings_Controller' ) ) {
            return [];
        }

        if ( class_exists( 'WC_Booking_Data_Store' ) && version_compare( WC_BOOKINGS_VERSION, '1.15.0', '>=' ) ) {
            $bookings = \WC_Booking_Data_Store::get_bookings_in_date_range(
                $start_timestamp,
                $end_timestamp,
                $product_filter ? $product_filter : '',
                false
            );
        } else {
            $bookings = \WC_Bookings_Controller::get_bookings_in_date_range(
                $start_timestamp,
                $end_timestamp,
                $product_filter ? $product_filter : '',
                false
            );
        }

        // Filter to current vendor's bookings only.
        $vendor_id = dokan_get_current_user_id();

        return array_filter(
            $bookings,
            function ( $booking ) use ( $vendor_id ) {
                return (int) get_post_field( 'post_author', $booking->get_product_id() ) === $vendor_id;
            }
        );
    }

    /**
     * Transform WC_Booking objects into FullCalendar event format.
     *
     * @since 5.0.0
     *
     * @param array $bookings Array of WC_Booking objects.
     *
     * @return array
     */
    private function transform_bookings_to_events( $bookings ) {
        $events         = [];
        $product_colors = [];
        $color_index    = 0;
        $seen_ids       = [];

        foreach ( $bookings as $booking ) {
            // Deduplicate — WC_Booking_Data_Store can return the same booking multiple times.
            $booking_id = $booking->get_id();

            if ( isset( $seen_ids[ $booking_id ] ) ) {
                continue;
            }

            $seen_ids[ $booking_id ] = true;
            $product_id = $booking->get_product_id();
            $product    = $booking->get_product();

            // Assign a consistent color per product.
            if ( ! isset( $product_colors[ $product_id ] ) ) {
                $product_colors[ $product_id ] = $this->colors[ $color_index % count( $this->colors ) ];
                ++$color_index;
            }

            $title = $product ? $product->get_title() : __( 'Booking', 'dokan' );
            $color = $product_colors[ $product_id ];

            // Build tooltip body HTML.
            $tooltip = $this->build_tooltip( $booking, $product );

            // Determine start/end for FullCalendar.
            if ( $booking->is_all_day() ) {
                $events[] = [
                    'id'              => $booking->get_id(),
                    'title'           => '#' . $booking->get_id() . ' ' . $title,
                    'start'           => gmdate( 'Y-m-d', $booking->get_start() ),
                    'end'             => gmdate( 'Y-m-d', $booking->get_end() + DAY_IN_SECONDS ),
                    'allDay'          => true,
                    'backgroundColor' => $color,
                    'borderColor'     => $color,
                    'textColor'       => $this->get_font_color( $color ),
                    'url'             => '',
                    'info'            => [ 'body' => $tooltip ],
                ];
            } else {
                $events[] = [
                    'id'              => $booking->get_id(),
                    'title'           => '#' . $booking->get_id() . ' ' . $title,
                    'start'           => gmdate( 'Y-m-d\TH:i:s', $booking->get_start() ),
                    'end'             => gmdate( 'Y-m-d\TH:i:s', $booking->get_end() ),
                    'allDay'          => false,
                    'backgroundColor' => $color,
                    'borderColor'     => $color,
                    'textColor'       => $this->get_font_color( $color ),
                    'url'             => '',
                    'info'            => [ 'body' => $tooltip ],
                ];
            }
        }

        return $events;
    }

    /**
     * Build HTML tooltip for a booking event.
     *
     * @since 5.0.0
     *
     * @param \WC_Booking          $booking The booking object.
     * @param \WC_Product_Booking|false $product The product object.
     *
     * @return string
     */
    private function build_tooltip( $booking, $product ) {
        $lines = [];

        $lines[] = '<strong>#' . $booking->get_id() . '</strong>';

        if ( $product ) {
            $lines[] = '<strong>' . esc_html( $product->get_title() ) . '</strong>';
        }

        $customer = $booking->get_customer();
        if ( $customer && ! empty( $customer->name ) ) {
            $lines[] = esc_html__( 'Booked by', 'dokan' ) . ' ' . esc_html( $customer->name );
        }

        if ( $booking->is_all_day() ) {
            $lines[] = esc_html__( 'All Day', 'dokan' );
        } else {
            $lines[] = $booking->get_start_date( '', 'g:ia' ) . ' &mdash; ' . $booking->get_end_date( '', 'g:ia' );
        }

        $resource = $booking->get_resource();
        if ( $resource ) {
            $lines[] = esc_html__( 'Resource:', 'dokan' ) . ' ' . esc_html( $resource->post_title );
        }

        if ( $booking->has_persons() ) {
            $person_parts = [];
            foreach ( $booking->get_persons() as $id => $qty ) {
                if ( 0 === $qty ) {
                    continue;
                }

                $person_type    = ( 0 < $id ) ? get_the_title( $id ) : __( 'Person(s)', 'dokan' );
                $person_parts[] = esc_html( $person_type ) . ': ' . intval( $qty );
            }
            if ( ! empty( $person_parts ) ) {
                $lines[] = implode( ', ', $person_parts );
            }
        }

        $lines[] = '<em>' . esc_html( ucfirst( $booking->get_status() ) ) . '</em>';

        return implode( '<br/>', $lines );
    }

    /**
     * Determine font color based on background color for readability.
     *
     * @since 5.0.0
     *
     * @param string $bg_color Background color as hex.
     *
     * @return string Font color as hex.
     */
    private function get_font_color( $bg_color ) {
        $bg_color  = hexdec( str_replace( '#', '', $bg_color ) );
        $red       = 0xFF & ( $bg_color >> 0x10 );
        $green     = 0xFF & ( $bg_color >> 0x8 );
        $blue      = 0xFF & $bg_color;
        $luminance = 1 - ( 0.299 * $red + 0.587 * $green + 0.114 * $blue ) / 255;

        return $luminance < 0.5 ? '#000000' : '#ffffff';
    }

    /**
     * Get item schema.
     *
     * @since 5.0.0
     *
     * @return array
     */
    public function get_public_item_schema() {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title'   => 'booking_calendar_event',
            'type'    => 'array',
            'items'   => [
                'type'       => 'object',
                'properties' => [
                    'id'              => [
                        'description' => __( 'Booking ID.', 'dokan' ),
                        'type'        => 'integer',
                    ],
                    'title'           => [
                        'description' => __( 'Event title.', 'dokan' ),
                        'type'        => 'string',
                    ],
                    'start'           => [
                        'description' => __( 'Event start date/time.', 'dokan' ),
                        'type'        => 'string',
                    ],
                    'end'             => [
                        'description' => __( 'Event end date/time.', 'dokan' ),
                        'type'        => 'string',
                    ],
                    'allDay'          => [
                        'description' => __( 'Whether the event is all day.', 'dokan' ),
                        'type'        => 'boolean',
                    ],
                    'backgroundColor' => [
                        'description' => __( 'Background color.', 'dokan' ),
                        'type'        => 'string',
                    ],
                    'borderColor'     => [
                        'description' => __( 'Border color.', 'dokan' ),
                        'type'        => 'string',
                    ],
                    'textColor'       => [
                        'description' => __( 'Text color.', 'dokan' ),
                        'type'        => 'string',
                    ],
                    'info'            => [
                        'description' => __( 'Additional event info.', 'dokan' ),
                        'type'        => 'object',
                        'properties'  => [
                            'body' => [
                                'description' => __( 'HTML body for tooltip.', 'dokan' ),
                                'type'        => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
