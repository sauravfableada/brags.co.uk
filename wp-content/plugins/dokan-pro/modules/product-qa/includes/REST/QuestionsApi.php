<?php

namespace WeDevs\DokanPro\Modules\ProductQA\REST;

use Exception;
use WeDevs\DokanPro\Modules\ProductQA\Models\Question;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit();

/**
 * Vendor Questions API.
 *
 * @since 3.11.0
 */
class QuestionsApi extends \WP_REST_Controller {

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct() {
        $this->rest_base = 'product-questions';
        $this->namespace = 'dokan/v1';
    }

    /**
     * Register Rest Routes.
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
                    'args'                => $this->get_collection_params(),
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'create_item' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
                    'permission_callback' => [ $this, 'create_item_permissions_check' ],
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_item' ],
                    'permission_callback' => [ $this, 'get_item_permissions_check' ],
                    'args'                => [
                        'context' => [
                            'default' => 'view',
                        ],
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update_item' ],
                    'permission_callback' => [ $this, 'update_item_permissions_check' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'delete_item' ],
                    'permission_callback' => [ $this, 'delete_item_permissions_check' ],
                    'args'                => [
                        'force' => [
                            'default' => false,
                        ],
                    ],
                ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/bulk_action',
            [
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'bulk_action' ],
                    'permission_callback' => [ $this, 'bulk_action_permissions_check' ],
                    'args'                => [
                        'action' => [
                            'description' => __( 'Action to perform', 'dokan' ),
                            'required' => true,
                            'type'     => 'string',
                            'enum'     => [
                                'delete',
                                'read',
                                'unread',
                            ],
                        ],
                        'ids' => [
                            'description' => __( 'IDs on action to perform', 'dokan' ),
                            'required' => true,
                            'type'     => 'array',
                            'items'    => [
                                'type' => 'integer',
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Get a collection of Questions.
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function get_items( $request ) {
        $per_page = absint( $request->get_param( 'per_page' ) );
        $args     = [
            'limit'    => $per_page,
            'offset'   => ( $request->get_param( 'page' ) - 1 ) * $per_page,
            'order_by' => 'q.id', // q is table short name here. the usage of q is essential.
            'order'    => 'DESC',
        ];

        $status     = $request->get_param( 'status' );
        $read       = $request->get_param( 'read' );
        $answered   = $request->get_param( 'answered' );
        $search     = $request->get_param( 'search' );
        $product_id = $request->get_param( 'product_id' );
        $vendor_id  = $request->get_param( 'vendor_id' );
        $user_id    = $request->get_param( 'user_id' );

        $exclude_user_id = $request->get_param( 'exclude_user_id' );

        if ( isset( $status ) ) {
            $args['status'] = $status;
        }

        if ( isset( $read ) ) {
            $args['read'] = $read;
        }

        if ( isset( $answered ) ) {
            $args['answered'] = $answered;
        }

        if ( isset( $search ) ) {
            $args['search'] = $search;
            unset( $args['order_by'], $args['order'] );
        }

        if ( isset( $user_id ) ) {
            $args['user_id'] = $user_id;
        }

        if ( isset( $exclude_user_id ) ) {
            $args['exclude_user_id'] = $exclude_user_id;
        }

        if ( ! empty( $product_id ) ) {
            $args['product_id'] = $product_id;
        }

        if ( ! empty( $vendor_id ) ) {
            $args['vendor_id'] = $vendor_id;
        }

        $data = [];
        try {
            $question = new Question();
            $items    = $question->query( $args );
            $total    = $question->count( $args );

            $count = $question->count_status( $args );
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_api_error', $e->getMessage(), [ 'status' => 500 ] );
        }
        foreach ( $items as $item ) {
            $item_data = $this->prepare_item_for_response( $item, $request );
            $data[]    = $this->prepare_response_for_collection( $item_data );
        }

        $response = rest_ensure_response( $data );

        $response->header( 'X-Status-All', $count->total() );
        $response->header( 'X-Status-Unread', $count->unread() );
        $response->header( 'X-Status-Read', $count->read() );
        $response->header( 'X-Status-Answered', $count->answered() );
        $response->header( 'X-Status-Unanswered', $count->unanswered() );
        $response->header( 'X-Status-Visible', $count->visible() );
        $response->header( 'X-Status-Hidden', $count->hidden() );

        $response->header( 'X-WP-Total', $total );
        $response->header( 'X-WP-TotalPages', ceil( $total / $request['per_page'] ) );

        return $response;
    }

    /**
     * Create a new Questions.
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function create_item( $request ) {
        $question_text = $request->get_param( 'question' );
        $product_id    = $request->get_param( 'product_id' );
        $user_id       = get_current_user_id();
        $user_id       = current_user_can( 'administrator' ) ? $request->get_param( 'user_id' ) : $user_id; // phpcs:ignore

        if ( empty( trim( $question_text ) ) ) {
            return new WP_Error( 'dokan_pro_rest_invalid_question', __( 'Invalid Question Content.', 'dokan' ), [ 'status' => 400 ] );
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return new WP_Error( 'dokan_pro_rest_invalid_product', __( 'Invalid product', 'dokan' ), [ 'status' => 404 ] );
        }

        try {
            $question = new Question();
            $question
                ->set_question( $question_text )
                ->set_product_id( $product_id )
                ->set_user_id( $user_id )
                ->create();
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_cannot_create', $e->getMessage(), [ 'status' => 500 ] );
        }

        $response = $this->prepare_item_for_response( $question, $request );
        $response->set_status( 201 );

        return $response;
    }

    /**
     * Get a Questions.
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function get_item( $request ) {
        $id = $request->get_param( 'id' );
        try {
            $item = new Question( $id );
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_no_resource', $e->getMessage(), [ 'status' => 404 ] );
        }

        $response = $this->prepare_item_for_response( $item, $request );
        $response->set_status( 200 );

        return $response;
    }

    /**
     * Updates one item from the collection.
     *
     * @since 3.11.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function update_item( $request ) {
        $id = absint( $request->get_param( 'id' ) );

        try {
            $item = new Question( $id );
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_no_resource', $e->getMessage(), [ 'status' => 404 ] );
        }

        $is_administrator = current_user_can( 'administrator' ); // phpcs:ignore
        $is_seller        = current_user_can( 'seller' ) && dokan_is_product_author( $item->get_product_id() ); // phpcs:ignore

        $question_text = $request->get_param( 'question' );
        if ( ! empty( $question_text ) ) {
            $item->set_question( $question_text );
        }

        $product_id = $request->get_param( 'product_id' );
        if ( ! empty( $product_id ) && $is_administrator ) {
            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                return new WP_Error( 'dokan_pro_rest_invalid_product', __( 'Invalid product', 'dokan' ), [ 'status' => 404 ] );
            }
            $item->set_product_id( $product_id );
        }

        $status = $request->get_param( 'status' );
        if ( isset( $status ) && $is_administrator ) {
            $item->set_status( $status );
        }

        $user_id = $request->get_param( 'user_id' );
        if ( ! empty( $user_id ) && $is_administrator ) {
            $item->set_user_id( $user_id );
        }

        $read = $request->get_param( 'read' );
        if ( ( isset( $read ) && $is_administrator ) || ( isset( $read ) && $is_seller ) ) {
            $item->set_read( $read );
        }

        try {
            $item->update();
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_cannot_update', $e->getMessage(), [ 'status' => 500 ] );
        }

        $response = $this->prepare_item_for_response( $item, $request );
        $response->set_status( 200 );

        return $response;
    }

    /**
     * Deletes one item from the collection.
     *
     * @since 3.11.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function delete_item( $request ) {
        $id = absint( $request->get_param( 'id' ) );
        try {
            $question = new Question( $id );
            $question->get();
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_no_resource', $e->getMessage(), [ 'status' => 404 ] );
        }

        try {
            $question->delete();
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_cannot_delete', $e->getMessage(), [ 'status' => 500 ] );
        }

        $response = rest_ensure_response( [ 'message' => __( 'Resource deleted.', 'dokan' ) ] );
        $response->set_status( 204 );

        return $response;
    }

    /**
     * Do bulk action.
     *
     * @since 3.11.0
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function bulk_action( $request ) {
        $action = $request->get_param( 'action' );
        $ids    = $request->get_param( 'ids' );

        if ( empty( $action ) || empty( $ids ) ) {
            return new WP_Error( 'dokan_pro_rest_no_resource', __( 'No resource found', 'dokan' ), [ 'status' => 404 ] );
        }

        try {
            $question = new Question();
            $question->bulk_action( $ids, $action );
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_cannot_perform', $e->getMessage(), [ 'status' => 500 ] );
        }

        return rest_ensure_response( [ 'message' => __( 'Bulk action successful.', 'dokan' ) ] );
    }

    /**
     * Prepares the item for the REST response.
     *
     * @since 3.11.0
     *
     * @param Question $item WordPress representation of the item.
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function prepare_item_for_response( $item, $request ) {
        $item_data = $item->to_array();
        $product   = wc_get_product( $item->get_product_id() );
        $vendor    = dokan_get_vendor_by_product( $product );

        $item_data['product'] = [
            'id'    => $product->get_id(),
            'title' => $product->get_title(),
            'image' => $product->get_image_id() ? wp_get_attachment_image_src( $product->get_image_id() )[0]
                : wc_placeholder_img_src(),
        ];
        $item_data['vendor']  = [
            'id'     => $vendor->get_id(),
            'name'   => $vendor->get_shop_name(),
            'avatar' => $vendor->get_avatar(),
        ];

        $context   = ! empty( $request['context'] ) ? $request['context'] : 'view';
        $item_data = $this->add_additional_fields_to_object( $item_data, $request );
        $item_data = $this->filter_response_by_context( $item_data, $context );
        $response  = rest_ensure_response( $item_data );
        $response->add_links( $this->prepare_links( $item, $request ) );

        return $response;
    }

    /**
     * Prepare links for the request.
     *
     * @param Question $item Object data.
     * @param WP_REST_Request $request Request object.
     *
     * @return array Links for the given item.
     */
    protected function prepare_links( $item, $request ): array {
        return [
            'self'       => [
                'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $item->get_id() ) ),
            ],
            'collection' => [
                'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
            ],
        ];
    }

    /**
     * Get Items permission check.
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return bool
     */
    public function get_items_permissions_check( $request ) {
        return true;
    }

    /**
     * Get Item permission check.
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return bool
     */
    public function get_item_permissions_check( $request ) {
        return $this->get_items_permissions_check( $request );
    }

    /**
     * Create Item permission check.
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|bool
     */
    public function create_item_permissions_check( $request ) {
        if ( ! current_user_can( 'read' ) ) {
            return new WP_Error(
                'dokan_pro_permission_failure',
                __(
                    'Sorry! You are not permitted to do current action.',
                    'dokan'
                )
            );
        }

        return true;
    }

    /**
     * Update Item permission check.
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|bool
     */
    public function update_item_permissions_check( $request ) {
        if ( current_user_can( 'manage_options' )
            || ( current_user_can( 'read' )
                    && $this->is_question_owner( $request->get_param( 'id' ), dokan_get_current_user_id() ) )
        ) {
            return true;
        }

        return new WP_Error(
            'dokan_pro_permission_failure',
            __(
                'Sorry! You are not permitted to do current action.',
                'dokan'
            )
        );
    }

    /**
     * Bulk Update Item permission check.
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|bool
     */
    public function bulk_action_permissions_check( $request ) {
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        if ( ! current_user_can( 'dokandar' ) ) {
            return new WP_Error(
                'dokan_pro_permission_failure',
                __(
                    'Sorry! You are not permitted to do current action.',
                    'dokan'
                )
            );
        }

        $ids     = (array) $request->get_param( 'ids' );
        $user_id = dokan_get_current_user_id();

        foreach ( $ids as $id ) {
            if ( ! $this->is_question_owner( (int) $id, $user_id ) ) {
                return new WP_Error(
                    'dokan_pro_permission_failure',
                    __(
                        'Sorry! You are not permitted to do current action.',
                        'dokan'
                    )
                );
            }
        }

        return true;
    }

    /**
     * Delete Item permission check.
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|bool
     */
    public function delete_item_permissions_check( $request ) {
        return $this->update_item_permissions_check( $request );
    }

    /**
     * Item schema.
     *
     * @return array
     */
    public function get_item_schema(): array {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'product_question',
            'type'       => 'object',
            'properties' => [
                'id'         => [
                    'description' => __( 'Unique identifier for the resource.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'required'    => false,
                ],
                'question'   => [
                    'description' => __( 'Question description.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                    'required'    => true,
                ],
                'product_id' => [
                    'description' => __( 'The product ID for the question.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                    'required'    => true,
                ],
                'status'     => [
                    'description' => __( 'The Status of the question.', 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'text',
                    'context'     => [ 'view', 'edit' ],
                    'enum'        => [ Question::STATUS_VISIBLE, Question::STATUS_HIDDEN, Question::STATUS_DELETED ],
                    'readonly'    => false,
                    'required'    => false,
                    'default'     => Question::STATUS_VISIBLE,
                ],
                'read'       => [
                    'description' => __( 'The document type is required or not', 'dokan' ),
                    'type'        => 'boolean',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                    'required'    => false,
                ],
                'created_at' => [
                    'description' => __( 'The Question created date time.', 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'required'    => false,
                ],
                'updated_at' => [
                    'description' => __( 'The Question updated date time.', 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'required'    => false,
                ],
                'user_id'    => [
                    'description' => __( 'The id of the user who created the question.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                    'required'    => false,
                ],
                'created_by' => [
                    'description' => __( 'The name of the user who created the question.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'required'    => false,
                ],
                'answer'     => [
                    'description' => __( 'The name of the user who created the question.', 'dokan' ),
                    'type'        => 'object',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'required'    => false,
                    'properties'  => [
                        'id'          => [
                            'description' => __( 'Unique identifier for the resource.', 'dokan' ),
                            'type'        => 'integer',
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                            'required'    => false,
                        ],
                        'answer'      => [
                            'description' => __( 'Answer text.', 'dokan' ),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => false,
                            'required'    => true,
                        ],
                        'question_id' => [
                            'description' => __( 'Question ID.', 'dokan' ),
                            'type'        => 'integer',
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                            'required'    => false,
                        ],
                        'created_at'  => [
                            'description' => __( 'The answer created date time.', 'dokan' ),
                            'type'        => 'string',
                            'format'      => 'date-time',
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                            'required'    => false,
                        ],
                        'updated_at'  => [
                            'description' => __( 'The answer updated date time.', 'dokan' ),
                            'type'        => 'string',
                            'format'      => 'date-time',
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                            'required'    => false,
                        ],
                        'user_id'     => [
                            'description' => __( 'The id of the user who created the answer.', 'dokan' ),
                            'type'        => 'integer',
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => false,
                            'required'    => false,
                        ],
                        'created_by'  => [
                            'description' => __( 'The name of the user who created the answer.', 'dokan' ),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                            'required'    => false,
                        ],
                    ],
                ],
            ],
        ];

        return $schema;
    }

    /**
     * Retrieves the query params for the collections.
     *
     * @since 3.11.0
     * @return array Query parameters for the collection.
     */
    public function get_collection_params(): array {
        $params = parent::get_collection_params();
        unset( $params['status'] );

        $additional_params = [
            'search'          => [
                'description'       => __( 'Limit results to those matching a string.', 'dokan' ),
                'type'              => 'string',
                'sanitize_callback' => [ $this, 'tokenize_string_into_FTS_words' ],
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'status'          => [
                'description' => __( 'The question status', 'dokan' ),
                'type'        => 'string',
                'enum'        => [ Question::STATUS_VISIBLE, Question::STATUS_HIDDEN, Question::STATUS_DELETED ],
            ],
            'read'            => [
                'description'       => __( 'The question read status', 'dokan' ),
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'answered'        => [
                'description'       => __( 'The question answered?', 'dokan' ),
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'product_id'      => [
                'description'       => __( 'The product id.', 'dokan' ),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
                'minimum'           => 1,
            ],
            'vendor_id'       => [
                'description'       => __( 'The vendor id.', 'dokan' ),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
                'minimum'           => 1,
            ],
            'user_id'         => [
                'description'       => __( 'The user id.', 'dokan' ),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
                'minimum'           => 1,
            ],
            'exclude_user_id' => [
                'description'       => __( 'The user id.', 'dokan' ),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
                'minimum'           => 1,
            ],

        ];

        return array_merge( $params, $additional_params );
    }

    /**
     * Check if the current user is the owner of the question or answer.
     *
     * @param int $id
     * @param int $user_id User ID.
     *
     * @return bool
     */
    private function is_question_owner( int $id, int $user_id ): bool {
        try {
            $question = new Question( $id );

            return $question->get_user_id() === $user_id || dokan_is_product_author( $question->get_product_id() );
        } catch ( Exception $e ) {
            return false;
        }
    }

    /**
     * Method to take an input string and tokenize it into an array of words for Full Text Searching (FTS).
     * This method is used when an input string can be made up of multiple words (let's say, separated by space characters),
     * and we need to use different Boolean operators on each of the words. The tokenizing process is similar to extraction
     * of words by FTS parser in MySQL. The operators used for matching in Boolean condition are removed from the input $phrase.
     * These characters as of latest version of MySQL (8+) are: +-><()~*:""&|@  (@ is specific for InnoDB)
     * We can also execute the following query to get updated list: show variables like 'ft_boolean_syntax';
     * Afterwards, the modified string is split into individual words considering either space, comma, and, period (.) characters.
     * Details at: https://dev.mysql.com/doc/refman/8.0/en/fulltext-natural-language.html
     *
     * @since 3.11.0
     *
     * @param string $phrase Input statement/phrase consisting of words
     *
     * @return string Tokenized words
     */
    public function tokenize_string_into_FTS_words( string $phrase ): string {
        $phrase = sanitize_text_field( $phrase );

        $phrase_mod = trim( preg_replace( '/[><()~*:"&|@+-]/', ' ', trim( $phrase ) ) );
        $words_arr = preg_split( '/[\s,.]/', $phrase_mod, -1, PREG_SPLIT_NO_EMPTY );

        // filter out the fulltext stop words and words whose length is less than 3.
        $fts_words = array();
        $fulltext_stop_words = array(
            'about',
			'are',
			'com',
			'for',
			'from',
			'how',
			'that',
			'this',
			'was',
			'what',
            'when',
			'where',
			'who',
			'will',
			'with',
			'und',
			'the',
			'www',
        );
        foreach ( $words_arr as $word ) {
            // By default MySQL FULLTEXT index does not store words whose length is less than 3.
            // Check innodb_ft_min_token_size Ref: https://dev.mysql.com/doc/refman/8.0/en/innodb-parameters.html#sysvar_innodb_ft_min_token_size
            // So we need to ignore words whose length is less than 3.
            if ( strlen( $word ) < 3 ) {
				continue;
            }

            // Ignore the fulltext stop words, whose length is greater than 3 or equal to 3.
            // Ref: https://dev.mysql.com/doc/refman/8.0/en/fulltext-stopwords.html
            if ( in_array( $word, $fulltext_stop_words ) ) { // phpcs:ignore
				continue;
            }

            $fts_words[] = $word;
        }

        return implode( ' ', $fts_words );
    }
}
