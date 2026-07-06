<?php

namespace WeDevs\DokanPro\Modules\ProductQA\REST;

use Exception;
use WeDevs\DokanPro\Modules\ProductQA\Models\Answer;
use WeDevs\DokanPro\Modules\ProductQA\Models\Question;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit();

/**
 * Vendor Answers API.
 *
 * @since 3.11.0
 */
class AnswersApi extends \WP_REST_Controller {
    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct() {
        $this->rest_base = 'product-answers';
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
    }

    /**
     * Get a collection of Answers.
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function get_items( $request ) {
        $per_page = absint( $request->get_param( 'per_page' ) );
        $args     = [
            'limit'  => $per_page,
            'offset' => ( $request->get_param( 'page' ) - 1 ) * $per_page,
        ];

        $search      = $request->get_param( 'search' );
        $question_id = $request->get_param( 'question_id' );
        $user_id     = $request->get_param( 'user_id' );


        if ( isset( $search ) ) {
            $args['search'] = $search;
        } else {
            $args['orderby'] = 'id';
            $args['order']   = 'DESC';
        }

        if ( isset( $user_id ) ) {
            $args['user_id'] = $user_id;
        }

        if ( isset( $question_id ) ) {
            $args['question_id'] = $question_id;
        }

        $data = [];
        try {
            $answer = new Answer();
            $items  = $answer->query( $args );
            $count  = $answer->count( $args );
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_api_error', $e->getMessage(), [ 'status' => 500 ] );
        }
        foreach ( $items as $item ) {
            $item_data = $this->prepare_item_for_response( $item, $request );
            $data[]    = $this->prepare_response_for_collection( $item_data );
        }

        $response = rest_ensure_response( $data );

        $response->header( 'X-WP-Total', $count );
        $response->header( 'X-WP-TotalPages', ceil( $count / $request['per_page'] ) );

        return $response;
    }

    /**
     * Create a new Answers.
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function create_item( $request ) {
        $answer_text = $request->get_param( 'answer' );
        $question_id = $request->get_param( 'question_id' );
        $user_id     = dokan_get_current_user_id();

        $override_user_id = $request->get_param( 'user_id' ) ?? 0;
        if ( current_user_can( 'administrator' ) && ! empty( $override_user_id ) ) {
            $user_id = $override_user_id;
        }

        try {
            $answer = new Answer();
            $answer
                ->set_question_id( $question_id )
                ->set_answer( $answer_text )
                ->set_user_id( $user_id )
                ->create();
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_cannot_create', $e->getMessage(), [ 'status' => 500 ] );
        }

        $response = $this->prepare_item_for_response( $answer, $request );
        $response->set_status( 201 );

        return $response;
    }

    /**
     * Get a Answers.
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function get_item( $request ) {
        $id = $request->get_param( 'id' );
        try {
            $item = new Answer( $id );
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
            $item = new Answer( $id );
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_no_resource', $e->getMessage(), [ 'status' => 404 ] );
        }

        $is_administrator = current_user_can( 'administrator' );

        $answer_text = $request->get_param( 'answer' );
        if ( ! empty( $answer_text ) ) {
            $item->set_answer( $answer_text );
        }

        $question_id = $request->get_param( 'question_id' );
        if ( ! empty( $question_id ) && $is_administrator ) {
            $item->set_question_id( $question_id );
        }

        $user_id = $request->get_param( 'user_id' );
        if ( ! empty( $user_id ) && $is_administrator ) {
            $item->set_user_id( $user_id );
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
            $answer = new Answer( $id );
            $answer->get();
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_no_resource', $e->getMessage(), [ 'status' => 404 ] );
        }

        try {
            $answer->delete();
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_cannot_delete', $e->getMessage(), [ 'status' => 500 ] );
        }

        $response = rest_ensure_response( [ 'message' => __( 'Resource deleted.', 'dokan' ) ] );
        $response->set_status( 204 );

        return $response;
    }

    /**
     * Prepares the item for the REST response.
     *
     * @since 3.11.0
     *
     * @param Answer $item WordPress representation of the item.
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function prepare_item_for_response( $item, $request ) {
        $item_data = $item->to_array();
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
     * @param Answer $item Object data.
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
     * @return bool|WP_Error
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
        $question_id = absint( $request->get_param( 'question_id' ) );
        try {
            $question = new Question( $question_id );
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_no_resource', $e->getMessage(), [ 'status' => 404 ] );
        }

        if ( current_user_can( 'manage_options' )
             || ( current_user_can( 'dokandar' )
                  && dokan_is_product_author( $question->get_product_id() ) )
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
     * Update Item permission check.
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|bool
     */
    public function update_item_permissions_check( $request ) {
        try {
            $answer   = new Answer( $request->get_param( 'id' ) );
            $question = new Question( $answer->get_question_id() );
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_no_resource', $e->getMessage(), [ 'status' => 404 ] );
        }

        if ( current_user_can( 'manage_options' )
             || ( current_user_can( 'dokandar' )
                  && dokan_is_product_author( $question->get_product_id() ) )
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
            'title'      => 'product_answer',
            'type'       => 'object',
            'properties' => [
                'id'                => [
                    'description' => __( 'Unique identifier for the resource.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'required'    => false,
                ],
                'answer'            => [
                    'description' => __( 'Answer text.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                    'required'    => true,
                ],
                'question_id'       => [
                    'description' => __( 'Question ID.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'required'    => true,
                ],
                'created_at'        => [
                    'description' => __( 'The answer created date time.', 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'required'    => false,
                ],
                'updated_at'        => [
                    'description' => __( 'The answer updated date time.', 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'required'    => false,
                ],
                'user_id'           => [
                    'description' => __( 'The id of the user who created the answer.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                    'required'    => false,
                ],
                'user_display_name' => [
                    'description' => __( 'The name of the user who created the answer.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'required'    => false,
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

        $additional_params = [
            'question_id' => [
                'description'       => __( 'The Question id.', 'dokan' ),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
                'minimum'           => 1,
            ],
            'user_id'     => [
                'description'       => __( 'The user id.', 'dokan' ),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
                'minimum'           => 1,
            ],

        ];

        return array_merge( $params, $additional_params );
    }
}
