<?php

namespace WeDevs\DokanPro\Modules\Booking\REST;

use Dokan_WC_Booking_Helper;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Booking REST Controller.
 *
 * Extends WC_Bookings_REST_Booking_Controller with vendor scoping,
 * Dokan permission checks, and UI-ready response fields.
 *
 * @since 5.0.0
 */
class BookingController extends \WC_Bookings_REST_Booking_Controller {

    /**
     * Endpoint namespace (Dokan vendor API).
     *
     * @var string
     */
    protected $namespace = 'dokan/v1';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'booking/bookings';

    /**
     * Register only the routes the vendor dashboard needs,
     * plus Dokan-specific endpoints (status-counts, confirm).
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
                    'callback'            => [ $this, 'get_items' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                    'args'                => $this->get_collection_params(),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/status-counts',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_status_counts' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)/confirm',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'confirm_booking' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                    'args'                => [
                        'id' => [
                            'description' => __( 'Unique identifier for the booking.', 'dokan' ),
                            'type'        => 'integer',
                            'required'    => true,
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Dokan permission check — vendor must have booking capability.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return bool|WP_Error
     */
    public function get_items_permissions_check( $request ) {
        if ( ! current_user_can( 'dokan_manage_bookings' ) ) {
            return new WP_Error(
                'dokan_rest_cannot_list_bookings',
                __( 'Sorry, you are not allowed to list bookings.', 'dokan' ),
                [ 'status' => rest_authorization_required_code() ]
            );
        }

        return true;
    }

    /**
     * Vendor-scoped booking query: only return bookings belonging to the current vendor.
     *
     * @since 5.0.0
     *
     * @param array $query_args WP_Query args.
     *
     * @return array
     */
    protected function get_objects( $query_args ) {
        $query_args['meta_query']   = isset( $query_args['meta_query'] ) && is_array( $query_args['meta_query'] )
            ? $query_args['meta_query']
            : [];
        $query_args['meta_query'][] = [
            'key'   => '_booking_seller_id',
            'value' => dokan_get_current_user_id(),
        ];

        return parent::get_objects( $query_args );
    }

    /**
     * Transform WC_Booking into the flat UI shape the React dashboard expects.
     *
     * @since 5.0.0
     *
     * @param \WC_Booking     $object  Booking object.
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response
     */
    public function prepare_object_for_response( $object, $request ) {
        $product  = $object->get_product();
        $customer = $object->get_customer();
        $resource = $object->get_resource();
        $order    = $object->get_order();

        $persons_count = 0;

        if ( is_object( $product ) && $product->has_persons() ) {
            $persons = get_post_meta( $object->get_id(), '_booking_persons', true );

            if ( ! empty( $persons ) && is_array( $persons ) ) {
                foreach ( $persons as $person_count ) {
                    $persons_count += (int) $person_count;
                }
            }
        }

        $order_url = '';

        if ( $order ) {
            $order_url = add_query_arg(
                [
                    'order_id'  => $order->get_id(),
                    '_wpnonce' => wp_create_nonce( 'dokan_view_order' ),
                ],
                dokan_get_navigation_url( 'orders' )
            );
        }

        $product_edit_url = '';

        if ( $product ) {
            $product_edit_url = dokan_get_navigation_url( 'booking/edit/?product_id=' . $product->get_id() );
        }

        $data = [
            'id'               => $object->get_id(),
            'status'           => $object->get_status(),
            'product_id'       => $product ? $product->get_id() : 0,
            'product_title'    => $product ? $product->get_name() : '',
            'product_edit_url' => $product_edit_url,
            'resource_id'      => $resource ? $resource->get_id() : 0,
            'resource_title'   => $resource ? $resource->get_title() : '',
            'customer_name'    => $customer ? $customer->name : '',
            'customer_email'   => $customer ? $customer->email : '',
            'persons'          => $persons_count,
            'order_id'         => $order ? $order->get_id() : 0,
            'order_status'     => $order ? $order->get_status() : '',
            'order_url'        => $order_url,
            'start_date'       => $object->get_start_date(),
            'end_date'         => $object->get_end_date(),
            'details_url'      => dokan_get_navigation_url( 'booking/booking-details/' . $object->get_id() ),
        ];

        return rest_ensure_response( $data );
    }

    /**
     * Get booking status counts for the current vendor.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response
     */
    public function get_status_counts( $request ) {
        $seller_id = dokan_get_current_user_id();
        $counts    = (array) Dokan_WC_Booking_Helper::get_booking_status_counts_by( $seller_id );

        $status_labels = [
            'total'                => __( 'All', 'dokan' ),
            'pending-confirmation' => __( 'Pending Confirmation', 'dokan' ),
            'confirmed'            => __( 'Confirmed', 'dokan' ),
            'paid'                 => __( 'Paid & Confirmed', 'dokan' ),
            'unpaid'               => __( 'Unpaid', 'dokan' ),
            'in-cart'              => __( 'In Cart', 'dokan' ),
            'complete'             => __( 'Complete', 'dokan' ),
            'cancelled'            => __( 'Cancelled', 'dokan' ),
        ];

        $data   = [];
        $data[] = [
            'key'   => 'all',
            'label' => $status_labels['total'],
            'count' => isset( $counts['total'] ) ? (int) $counts['total'] : 0,
        ];

        foreach ( $status_labels as $status_key => $label ) {
            if ( 'total' === $status_key ) {
                continue;
            }

            $count = isset( $counts[ $status_key ] ) ? (int) $counts[ $status_key ] : 0;

            if ( $count > 0 ) {
                $data[] = [
                    'key'   => $status_key,
                    'label' => $label,
                    'count' => $count,
                ];
            }
        }

        return rest_ensure_response( $data );
    }

    /**
     * Confirm a pending booking.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function confirm_booking( $request ) {
        $booking_id = absint( $request->get_param( 'id' ) );
        $booking    = $this->get_object( $booking_id );

        if ( ! $booking ) {
            return new WP_Error(
                'dokan_rest_booking_not_found',
                __( 'Booking not found.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        // Verify vendor owns this booking.
        $seller = get_post_meta( $booking_id, '_booking_seller_id', true );

        if ( (int) $seller !== dokan_get_current_user_id() ) {
            return new WP_Error(
                'dokan_rest_cannot_confirm_booking',
                __( 'You do not have permission to confirm this booking.', 'dokan' ),
                [ 'status' => rest_authorization_required_code() ]
            );
        }

        if ( 'pending-confirmation' !== $booking->get_status() ) {
            return new WP_Error(
                'dokan_rest_booking_not_pending',
                __( 'Only pending bookings can be confirmed.', 'dokan' ),
                [ 'status' => 400 ]
            );
        }

        $booking->update_status( 'confirmed' );

        do_action( 'dokan_after_booking_confirmed', $booking );

        return rest_ensure_response(
            [
                'id'      => $booking_id,
                'status'  => 'confirmed',
                'message' => __( 'Booking confirmed successfully.', 'dokan' ),
            ]
        );
    }

    /**
     * Get collection params.
     *
     * @since 5.0.0
     *
     * @return array
     */
    public function get_collection_params() {
        return [
            'per_page' => [
                'description'       => __( 'Maximum number of items to be returned in result set.', 'dokan' ),
                'type'              => 'integer',
                'default'           => 10,
                'minimum'           => 1,
                'maximum'           => 100,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'page'     => [
                'description'       => __( 'Current page of the collection.', 'dokan' ),
                'type'              => 'integer',
                'default'           => 1,
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'status'   => [
                'description'       => __( 'Booking status to filter by.', 'dokan' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'search'   => [
                'description'       => __( 'Search term.', 'dokan' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ],
        ];
    }
}
