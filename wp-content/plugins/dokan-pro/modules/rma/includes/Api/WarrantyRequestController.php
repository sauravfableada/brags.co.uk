<?php

namespace WeDevs\DokanPro\Modules\RMA\Api;

use WeDevs\Dokan\REST\DokanBaseController;
use WeDevs\DokanPro\Modules\RMA\Traits\RMAApiControllerTrait;
use WeDevs\DokanPro\Modules\RMA\WarrantyRequest;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * WarrantyRequestController class.
 *
 * REST controller for managing RMA warranty requests.
 * Handles CRUD operations and permissions for warranty requests.
 *
 * @since 4.0.0
 */
class WarrantyRequestController extends DokanBaseController {
    use RMAApiControllerTrait;

    /**
     * Route name
     *
     * @var string
     */
    protected string $base = 'rma/warranty-requests';

    /**
     * The warranty request query object
     *
     * @var WarrantyRequest
     */
    protected WarrantyRequest $warranty_request;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->warranty_request = new WarrantyRequest();
    }

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
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update_item' ],
                    'permission_callback' => 'is_user_logged_in',
                    'args'                => [
                        'status' => [
                            'description' => __( 'The status of the warranty request.', 'dokan' ),
                            'type'        => 'string',
                            'enum'        => array_keys( dokan_warranty_request_status() ),
                        ],
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'delete_item' ],
                    'permission_callback' => 'is_user_logged_in',
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->base . '/statuses',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_statuses' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->base . '/statuses-filter',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_statuses_filter' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                ],
            ]
        );
    }

    /**
     * Get a collection of warranty requests
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return object|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_items( $request ) {
        try {
            $query_args = $this->prepare_query_args( $request );

            $warranty_requests = $this->warranty_request->all( $query_args );
            if ( empty( $warranty_requests ) ) {
                return rest_ensure_response( [] );
            }

            $data_objects = array();
            foreach ( $warranty_requests as $warranty_request ) {
                $data           = $this->prepare_data_for_response( $warranty_request, $request );
                $data_objects[] = $this->prepare_response_for_collection( $data );
            }

            // Get total count for pagination
            $count_args     = array_merge(
                $query_args,
                [
                    'count'  => true,
                    'number' => - 1,
                ]
            );
            $requests_count = dokan_get_warranty_request( $count_args );

            $total_items = $requests_count['total_count'];

            $response = rest_ensure_response( $data_objects );

            $request->set_param( 'per_page', $query_args['number'] );

            return $this->format_collection_response( $response, $request, $total_items );
        } catch ( \Throwable $e ) {
            return new WP_Error(
                'dokan_warranty_request_error',
                $e->getMessage(),
                [ 'status' => 500 ]
            );
        }
    }

    /**
     * Get a single warranty request
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_item( $request ) {
        $warranty_request = $this->validate_warranty_request( $request['id'] );
        if ( is_wp_error( $warranty_request ) ) {
            return $warranty_request;
        }

        return $this->prepare_data_for_response( $warranty_request, $request );
    }

    /**
     * Create a warranty request
     *
     * @param WP_REST_Request $request
     *
     * @return WP_Error|WP_REST_Response
     */
    public function create_item( $request ) {
        $params = $this->prepare_item_for_database( $request );
        $result = $this->warranty_request->create( $params );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $response = rest_ensure_response(
            array(
                'message' => __( 'Warranty request created successfully.', 'dokan' ),
                'data'    => $params,
            )
        );
        $response->set_status( 201 );

        return $response;
    }

    /**
     * Update a warranty request
     *
     * @param WP_REST_Request $request
     *
     * @return WP_Error|WP_REST_Response
     */
    public function update_item( $request ) {
        $warranty_request = $this->validate_warranty_request_for_vendor( $request['id'] );
        if ( is_wp_error( $warranty_request ) ) {
            return $warranty_request;
        }

        $params = [
            'id'     => $request['id'],
            'status' => isset( $request['status'] ) ? sanitize_textarea_field( $request['status'] ) : '',
        ];

        $result = $this->warranty_request->update( $params );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $updated_request = $this->warranty_request->get( $request['id'] );
        if ( is_wp_error( $updated_request ) ) {
            return $updated_request;
        }

        return $this->prepare_data_for_response( $updated_request, $request );
    }

    /**
     * Delete a warranty request
     *
     * @param WP_REST_Request $request
     *
     * @return WP_Error|WP_REST_Response
     */
    public function delete_item( $request ) {
        $warranty_request = $this->validate_warranty_request_for_vendor( $request['id'] );
        if ( is_wp_error( $warranty_request ) ) {
            return $warranty_request;
        }

        $vendor_id = (int) $warranty_request['vendor']['store_id'];
        $result    = $this->warranty_request->delete( $request['id'], $vendor_id );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response(
            array(
                'deleted' => true,
                'message' => __( 'Warranty request created successfully.', 'dokan' ),
                'data' => [
                    'status' => 200,
                ],
            )
        );
    }

    /**
     * Get warranty request statuses
     *
     * @return WP_REST_Response
     */
    public function get_statuses(): WP_REST_Response {
        $statuses = dokan_warranty_request_status();

        return rest_ensure_response( $statuses );
    }

    /**
     * Get warranty request statuses filter
     *
     * @since 3.7.4
     *
     * @return WP_REST_Response
     */
    public function get_statuses_filter(): WP_REST_Response {
        $post_counts = dokan_warranty_request_status_count();
        $statuses    = dokan_warranty_request_status();
        $response    = [];

        // Add 'All' filter first
        $response[] = [
            'label' => __( 'All', 'dokan' ),
            'name'  => 'all',
            'count' => 0,
        ];

        // Add remaining status filters
        foreach ( $post_counts as $status_key => $count ) {
            if ( 'total' === $status_key || ! isset( $statuses[ $status_key ] ) ) {
                continue;
            }

            $response[] = [
                // translators: %1$s: Warranty request status, %2$d: Warranty requests count
                'label' => sprintf( __( '%1$s (%2$d)', 'dokan' ), $statuses[ $status_key ], $count ),
                'name'  => $status_key,
                'count' => (int) $count,
            ];
        }

        return rest_ensure_response( $response );
    }

    /**
     * Prepare a single order output for response.
     *
     * @since 4.0.0
     *
     * @param array $warranty_request The warranty request data.
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response
     */
    public function prepare_data_for_response( array $warranty_request, WP_REST_Request $request ): WP_REST_Response {
        $this->request = $request;
        $response      = rest_ensure_response( $warranty_request );
        $response->add_links( $this->prepare_links( $warranty_request, $request ) );

        /**
         * Filter the response object for the warranty request.
         *
         * @since 4.0.0
         *
         * @param WP_REST_Response $response The response object.
         * @param array $warranty_request The warranty request data.
         * @param WP_REST_Request $request Request object.
         */
        return apply_filters( 'dokan_rest_prepare_warranty_request_object', $response, $warranty_request, $request );
    }

    /**
     * Prepare links for the request.
     *
     * @since  4.0.0
     *
     * @param array $warranty_request Object data.
     * @param WP_REST_Request $request Request object.
     *
     * @return array                   Links for the given post.
     */
    protected function prepare_links( array $warranty_request, WP_REST_Request $request ): array {
        return array(
            'self'       => array(
                'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $warranty_request['id'] ) ),
            ),
            'collection' => array(
                'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
            ),
        );
    }

    /**
     * Check if a given request has access to get items
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request Full details about the request
     *
     * @return bool|WP_Error
     */
    public function get_items_permissions_check( $request ) {
        // Check authentication
        $auth_check = $this->check_authentication();
        if ( is_wp_error( $auth_check ) ) {
            return $auth_check;
        }

        return $this->check_general_permission();
    }

    /**
     * Check if a given request has access to create items
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request Full details about the request
     *
     * @return bool|WP_Error
     */
    public function create_item_permissions_check( $request ) {
        // Check authentication
        $auth_check = $this->check_authentication();
        if ( is_wp_error( $auth_check ) ) {
            return $auth_check;
        }

        // Check if user is a customer
        if ( ! dokan_is_user_customer( get_current_user_id() ) ) {
            return new WP_Error(
                'dokan_rest_forbidden',
                esc_html__( 'Only customers can create warranty requests.', 'dokan' ),
                [ 'status' => 403 ]
            );
        }

        return true;
    }

    /**
     * Prepare item for database
     *
     * @param WP_REST_Request $request Request object
     *
     * @return array Prepared item data
     */
    protected function prepare_item_for_database( $request ): array {
        $prepared_fields = [];

        $prepared_fields['order_id']    = isset( $request['order_id'] ) ? absint( $request['order_id'] ) : 0;
        $prepared_fields['customer_id'] = isset( $request['customer_id'] ) ? absint( $request['customer_id'] ) : 0;
        $prepared_fields['type']        = isset( $request['type'] ) ? sanitize_textarea_field( $request['type'] ) : '';
        $prepared_fields['status']      = isset( $request['status'] ) ? sanitize_textarea_field( $request['status'] ) : '';
        $prepared_fields['reasons']     = isset( $request['reasons'] ) ? sanitize_textarea_field( $request['reasons'] ) : '';
        $prepared_fields['details']     = isset( $request['details'] ) ? sanitize_textarea_field( $request['details'] ) : '';

        // Handle items separately since it's an array
        if ( isset( $request['items'] ) && is_array( $request['items'] ) ) {
            $prepared_fields['items'] = array_map(
                static function ( $item ) {
                    return [
                        'product_id' => isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0,
                        'item_id'    => isset( $item['item_id'] ) ? absint( $item['item_id'] ) : 0,
                        'quantity'   => isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 0,
                    ];
                }, $request['items']
            );
        }

        // Set vendor ID
        if ( dokan_is_user_seller( get_current_user_id() ) ) {
            $prepared_fields['vendor_id'] = dokan_get_current_user_id();
        } elseif ( isset( $request['vendor_id'] ) ) {
            $prepared_fields['vendor_id'] = absint( $request['vendor_id'] );
        }

        // Add created_at for new requests
        if ( ! isset( $request['id'] ) ) {
            $prepared_fields['created_at'] = current_time( 'mysql' );
        }

        return $prepared_fields;
    }

    /**
     * Prepare query arguments
     *
     * @param WP_REST_Request $request Request object
     *
     * @return array Query arguments
     */
    protected function prepare_query_args( WP_REST_Request $request ): array {
        $args = [
            'id'      => 0,
            'number'  => 20,
            'orderby' => 'created_at',
            'order'   => 'desc',
            'count'   => false,
        ];

        // Handle pagination
        $args['number'] = isset( $request['per_page'] ) ? absint( $request['per_page'] ) : $args['number'];
        $args['offset'] = isset( $request['page'] ) ? ( absint( $request['page'] ) - 1 ) * $args['number'] : 0;

        // Handle sorting
        if ( isset( $request['orderby'] ) ) {
            $args['orderby'] = $request['orderby'];
        }

        if ( isset( $request['order'] ) ) {
            $args['order'] = strtolower( $request['order'] );
        }

        $filter_fields = [
            'type'        => 'string',
            'order_id'    => 'int',
            'customer_id' => 'int',
            'vendor_id'   => 'int',
            'reasons'     => 'string',
        ];

        foreach ( $filter_fields as $field => $type ) {
            if ( ! empty( $request[ $field ] ) ) {
                $args[ $field ] = 'int' === $type ? absint( $request[ $field ] ) : $request[ $field ];
            }
        }

        // Special handling for status
        if ( 'all' !== ( $request['status'] ?? 'all' ) ) {
            $args['status'] = $request['status'];
        }

        // Add vendor filter for sellers if not already set
        if ( ! isset( $args['vendor_id'] ) && dokan_is_user_seller( get_current_user_id() ) ) {
            $args['vendor_id'] = dokan_get_current_user_id();
        }

        return $args;
    }

    /**
     * Get collection parameters
     *
     * @return array
     */
    public function get_collection_params(): array {
        $params = parent::get_collection_params();

        $params['context']['default'] = 'view';

        $params['status'] = [
            'description'       => esc_html__( 'Limit result set to warranty requests with specific status.', 'dokan' ),
            'type'              => 'string',
            'enum'              => array_merge( array_keys( dokan_warranty_request_status() ), [ 'all' ] ),
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['type'] = [
            'description'       => esc_html__( 'Limit result set to warranty requests with specific type.', 'dokan' ),
            'type'              => 'string',
            'enum'              => array_keys( dokan_warranty_request_type() ),
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['order_id'] = [
            'description'       => esc_html__( 'Limit result set to warranty requests for specific order.', 'dokan' ),
            'type'              => 'integer',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['customer_id'] = [
            'description'       => esc_html__( 'Limit result set to warranty requests from specific customer.', 'dokan' ),
            'type'              => 'integer',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['orderby'] = [
            'description'       => esc_html__( 'Sort collection by object attribute.', 'dokan' ),
            'type'              => 'string',
            'default'           => 'id',
            'enum'              => [
                'id',
                'created_at',
                'order_id',
            ],
            'validate_callback' => 'rest_validate_request_arg',
        ];

        return $params;
    }

    /**
     * Get item schema
     *
     * @return array
     */
    public function get_item_schema(): array {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'warranty_request',
            'type'       => 'object',
            'properties' => [
                'id'          => [
                    'description' => esc_html__( 'Unique identifier for the warranty request.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'order_id'    => [
                    'description' => esc_html__( 'The order ID associated with this warranty request.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'required'    => true,
                ],
                'vendor_id'   => [
                    'description' => esc_html__( 'The vendor ID associated with this warranty request.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'required'    => true,
                ],
                'customer_id' => [
                    'description' => esc_html__( 'The customer ID associated with this warranty request.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'required'    => true,
                ],
                'type'        => [
                    'description' => esc_html__( 'The type of warranty request.', 'dokan' ),
                    'type'        => 'string',
                    'enum'        => array_keys( dokan_warranty_request_type() ),
                    'context'     => [ 'view', 'edit' ],
                    'required'    => true,
                ],
                'status'      => [
                    'description' => esc_html__( 'The status of the warranty request.', 'dokan' ),
                    'type'        => 'string',
                    'enum'        => array_keys( dokan_warranty_request_status() ),
                    'context'     => [ 'view', 'edit' ],
                    'required'    => true,
                ],
                'reasons'     => [
                    'description' => esc_html__( 'The reasons for the warranty request.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                ],
                'details'     => [
                    'description' => esc_html__( 'Additional details for the warranty request.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                ],
                'items'       => [
                    'description' => esc_html__( 'The items included in the warranty request.', 'dokan' ),
                    'type'        => 'array',
                    'context'     => [ 'view', 'edit' ],
                    'required'    => true,
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'product_id' => [
                                'description' => esc_html__( 'Product ID.', 'dokan' ),
                                'type'        => 'integer',
                                'required'    => true,
                            ],
                            'item_id'    => [
                                'description' => esc_html__( 'Order item ID.', 'dokan' ),
                                'type'        => 'integer',
                                'required'    => true,
                            ],
                            'quantity'   => [
                                'description' => esc_html__( 'Item quantity.', 'dokan' ),
                                'type'        => 'integer',
                                'required'    => true,
                            ],
                        ],
                    ],
                ],
                'created_at'  => [
                    'description' => esc_html__( "The date the warranty request was created, in the site's timezone.", 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
            ],
        ];

        return $this->add_additional_fields_schema( $schema );
    }

    /**
     * Get arguments for create item endpoint
     *
     * @return array
     */
    protected function get_create_item_args() {
        return [
            'order_id'    => [
                'description' => esc_html__( 'The order ID associated with this warranty request.', 'dokan' ),
                'type'        => 'integer',
                'required'    => true,
            ],
            'customer_id' => [
                'description' => esc_html__( 'The customer ID associated with this warranty request.', 'dokan' ),
                'type'        => 'integer',
                'required'    => true,
            ],
            'type'        => [
                'description' => esc_html__( 'The type of warranty request.', 'dokan' ),
                'type'        => 'string',
                'enum'        => array_keys( dokan_warranty_request_type() ),
                'required'    => true,
            ],
            'status'      => [
                'description' => esc_html__( 'The status of the warranty request.', 'dokan' ),
                'type'        => 'string',
                'enum'        => array_keys( dokan_warranty_request_status() ),
                'required'    => true,
            ],
            'reasons'     => [
                'description' => esc_html__( 'The reasons for the warranty request.', 'dokan' ),
                'type'        => 'string',
            ],
            'details'     => [
                'description' => esc_html__( 'Additional details for the warranty request.', 'dokan' ),
                'type'        => 'string',
            ],
            'items'       => [
                'description' => esc_html__( 'The items included in the warranty request.', 'dokan' ),
                'type'        => 'array',
                'items'       => [
                    'type'       => 'object',
                    'properties' => [
                        'product_id' => [
                            'description' => esc_html__( 'Product ID.', 'dokan' ),
                            'type'        => 'integer',
                        ],
                        'item_id'    => [
                            'description' => esc_html__( 'Order item ID.', 'dokan' ),
                            'type'        => 'integer',
                        ],
                        'quantity'   => [
                            'description' => esc_html__( 'Item quantity.', 'dokan' ),
                            'type'        => 'integer',
                        ],
                    ],
                ],
            ],
        ];
    }
}
