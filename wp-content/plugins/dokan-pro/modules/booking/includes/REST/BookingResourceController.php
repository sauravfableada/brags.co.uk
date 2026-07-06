<?php

namespace WeDevs\DokanPro\Modules\Booking\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Booking Resource REST Controller.
 *
 * @since 5.0.0
 */
class BookingResourceController extends \WC_Bookings_REST_Resources_Controller {

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
    protected $rest_base = 'booking/resources';

    /**
     * Register only the routes the vendor dashboard needs.
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
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'create_resource' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                    'args'                => [
                        'name' => [
                            'description'       => __( 'Name for the new resource.', 'dokan' ),
                            'type'              => 'string',
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                    ],
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            [
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'delete_resource' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                    'args'                => [
                        'id' => [
                            'description' => __( 'Unique identifier for the resource.', 'dokan' ),
                            'type'        => 'integer',
                            'required'    => true,
                        ],
                    ],
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
    }

    /**
     * Dokan permission check.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return bool|WP_Error
     */
    public function get_items_permissions_check( $request ) {
        if ( ! current_user_can( 'dokan_manage_booking_resource' ) ) {
            return new WP_Error(
                'dokan_rest_cannot_manage_resources',
                __( 'Sorry, you are not allowed to manage resources.', 'dokan' ),
                [ 'status' => rest_authorization_required_code() ]
            );
        }

        return true;
    }

    /**
     * Vendor-scoped resource query.
     *
     * @since 5.0.0
     *
     * @param array $query_args WP_Query args.
     *
     * @return array
     */
    protected function get_objects( $query_args ) {
        $query_args['author'] = dokan_get_current_user_id();

        return parent::get_objects( $query_args );
    }

    /**
     * Transform resource into the UI shape the React dashboard expects.
     *
     * @since 5.0.0
     *
     * @param \WC_Product_Booking_Resource $object  Resource object.
     * @param WP_REST_Request              $request Request object.
     *
     * @return WP_REST_Response
     */
    public function prepare_object_for_response( $object, $request ) {
        global $wpdb;

        $parent_products = [];

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $relationships = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT product_id FROM {$wpdb->prefix}wc_booking_relationships WHERE resource_id = %d ORDER BY sort_order",
                $object->get_id()
            )
        );

        if ( $relationships ) {
            foreach ( $relationships as $rel ) {
                $product = wc_get_product( $rel->product_id );

                if ( $product ) {
                    $parent_products[] = [
                        'id'    => $rel->product_id,
                        'title' => $product->get_name(),
                        'url'   => $product->get_permalink(),
                    ];
                }
            }
        }

        $data = [
            'id'              => $object->get_id(),
            'name'            => $object->get_name(),
            'parent_products' => $parent_products,
            'edit_url'        => add_query_arg( 'id', $object->get_id(), dokan_get_navigation_url( 'booking/resources/edit' ) ),
        ];

        return rest_ensure_response( $data );
    }

    /**
     * Create a new booking resource.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function create_resource( $request ) {
        $name = $request->get_param( 'name' );

        if ( empty( $name ) ) {
            return new WP_Error(
                'dokan_rest_resource_name_required',
                __( 'Resource name is required.', 'dokan' ),
                [ 'status' => 400 ]
            );
        }

        $resource_id = wp_insert_post(
            [
                'post_title'  => $name,
                'post_type'   => 'bookable_resource',
                'post_status' => 'publish',
                'post_author' => dokan_get_current_user_id(),
            ],
            true
        );

        if ( is_wp_error( $resource_id ) ) {
            return new WP_Error(
                'dokan_rest_cannot_create_resource',
                __( 'The resource could not be created.', 'dokan' ),
                [ 'status' => 500 ]
            );
        }

        return rest_ensure_response(
            [
                'id'              => $resource_id,
                'name'            => $name,
                'parent_products' => [],
                'edit_url'        => add_query_arg( 'id', $resource_id, dokan_get_navigation_url( 'booking/resources/edit' ) ),
            ]
        );
    }

    /**
     * Delete a booking resource.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function delete_resource( $request ) {
        $resource_id = absint( $request->get_param( 'id' ) );
        $post        = get_post( $resource_id );

        if ( ! $post || 'bookable_resource' !== $post->post_type ) {
            return new WP_Error(
                'dokan_rest_resource_not_found',
                __( 'Resource not found.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        if ( (int) $post->post_author !== dokan_get_current_user_id() ) {
            return new WP_Error(
                'dokan_rest_cannot_delete_resource',
                __( 'You do not have permission to delete this resource.', 'dokan' ),
                [ 'status' => rest_authorization_required_code() ]
            );
        }

        $result = wp_delete_post( $resource_id, true );

        if ( ! $result ) {
            return new WP_Error(
                'dokan_rest_cannot_delete_resource',
                __( 'The resource could not be deleted.', 'dokan' ),
                [ 'status' => 500 ]
            );
        }

        return rest_ensure_response(
            [
                'id'      => $resource_id,
                'deleted' => true,
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
            'search'   => [
                'description'       => __( 'Search term.', 'dokan' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ],
        ];
    }
}
