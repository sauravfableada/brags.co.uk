<?php

namespace WeDevs\DokanPro\Modules\VendorVerification\REST;

use Exception;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationMethod;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit();

/**
 * Verification Document Type API.
 *
 * @since 3.11.1
 */
class VerificationMethodsApi extends \WP_REST_Controller {

    /**
     * API namespace.
     *
     * @var string $namespace
     */
    protected $namespace = 'dokan/v1';

    /**
     * API rest base.
     *
     * @var string $rest_base
     */
    protected $rest_base = 'verification-methods';

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct() {
        // empty for now
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
     * Get a collection of Document Types.
     *
     * @since 3.11.1
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
            'order_by' => 'id',
            'order'    => 'ASC',
        ];

        $status   = $request->get_param( 'status' );
        $required = $request->get_param( 'required' );
        $search   = $request->get_param( 'search' );

        if ( ! empty( $search  ) ) {
            $args['search'] = $search;
        }

        if ( isset( $status ) ) {
            $args['status'] = $status;
        }

        if ( isset( $required ) ) {
            $args['required'] = $required;
        }

        $data = [];
        try {
            $document_type = new VerificationMethod();
            $items         = $document_type->query( $args );
            $count         = $document_type->count( $args );
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
     * Create a new Document Type.
     *
     * @since 3.11.1
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function create_item( $request ) {
        $title     = $request->get_param( 'title' );
        $help_text = $request->get_param( 'help_text' ) ?? '';
        $status    = wc_string_to_bool( $request->get_param( 'status' ) );
        $kind      = $request->get_param( 'kind' ) ?? VerificationMethod::TYPE_CUSTOM;
        $required  = wc_string_to_bool( $request->get_param( 'required' ) );

        try {
            $document_type = new VerificationMethod();
            $document_type
                ->set_title( $title )
                ->set_help_text( $help_text )
                ->set_enabled( $status )
                ->set_kind( $kind )
                ->set_required( $required )
                ->save();
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_cannot_create', $e->getMessage(), [ 'status' => 500 ] );
        }

        $response = $this->prepare_item_for_response( $document_type, $request );
        $response->set_status( 201 );

        return $response;
    }

    /**
     * Get a Document Type.
     *
     * @since 3.11.1
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function get_item( $request ) {
        $item = new VerificationMethod( $request->get_param( 'id' ) );
        if ( ! $item->get_id() ) {
            return new WP_Error( 'dokan_pro_rest_no_resource',
                __( 'No resource found.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        return $this->prepare_item_for_response( $item, $request );
    }

    /**
     * Updates one item from the collection.
     *
     * @since 3.11.1
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function update_item( $request ) {
        $item = new VerificationMethod( $request->get_param( 'id' ) );
        if ( ! $item->get_id() ) {
            return new WP_Error( 'dokan_pro_rest_no_resource',
                __( 'No resource found.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        $title = $request->get_param( 'title' );
        if ( isset( $title ) ) {
            $title = trim( $title );

            if ( empty( $title ) ) {
                return new WP_Error( 'dokan_pro_rest_cannot_update', esc_html__(  'Verification Method title is required', 'dokan' ), [ 'status' => 500 ] );
            }

            $item->set_title( $title );
        }

        $help_text = $request->get_param( 'help_text' );
        if ( ! empty( $help_text ) ) {
            $item->set_help_text( $help_text );
        }

        $status = $request->get_param( 'status' );
        if ( isset( $status ) ) {
            $item->set_enabled( $status );
        }

        $kind = $request->get_param( 'kind' );
        if ( ! empty( $kind ) ) {
            $item->set_kind( $kind );
        }

        $required = $request->get_param( 'required' );
        if ( isset( $required ) ) {
            $item->set_required( $required );
        }

        try {
            $item->save();
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_cannot_update', $e->getMessage(), [ 'status' => 500 ] );
        }

        return $this->prepare_item_for_response( $item, $request );
    }

    /**
     * Deletes one item from the collection.
     *
     * @since 3.11.1
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function delete_item( $request ) {
        try {
            $method = new VerificationMethod( $request->get_param( 'id' ) );
            $method->delete();
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
     * @since 3.11.1
     *
     * @param VerificationMethod $item    WordPress' representation of the item.
     * @param WP_REST_Request    $request Request object.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function prepare_item_for_response( $item, $request ) {
        $data     = $item->to_array();
        $context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
        $data     = $this->add_additional_fields_to_object( $data, $request );
        $data     = $this->filter_response_by_context( $data, $context );
        $response = rest_ensure_response( $data );
        $response->add_links( $this->prepare_links( $item, $request ) );

        return $response;
    }

    /**
     * Prepare links for the request.
     *
     * @param VerificationMethod $item    Object data.
     * @param WP_REST_Request    $request Request object.
     *
     * @return array                   Links for the given post.
     */
    protected function prepare_links( $item, $request ) {
        $links = [
            'self'       => [
                'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $item->get_id() ) ),
            ],
            'collection' => [
                'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
            ],
        ];

        return $links;
    }

    /**
     * Get Item permission check.
     *
     * @since 3.11.1
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return bool|WP_Error
     */
    public function get_items_permissions_check( $request ) {
        if ( current_user_can( 'manage_options' ) || current_user_can( 'seller' ) ) {
            return true;
        }

        return new WP_Error(
            'dokan_pro_permission_failure',
            __( 'Sorry! You are not permitted to do current action.', 'dokan' )
        );
    }

    /**
     * Get Item permission check.
     *
     * @since 3.11.1
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
     * @since 3.11.1
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|bool
     */
    public function create_item_permissions_check( $request ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error(
                'dokan_pro_permission_failure',
                __( 'Sorry! You are not permitted to do current action.', 'dokan' )
            );
        }

        return true;
    }

    /**
     * Update Item permission check.
     *
     * @since 3.11.1
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|bool
     */
    public function update_item_permissions_check( $request ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error(
                'dokan_pro_permission_failure',
                __( 'Sorry! You are not permitted to do current action.', 'dokan' )
            );
        }

        return true;
    }

    /**
     * Delete Item permission check.
     *
     * @since 3.11.1
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|bool
     */
    public function delete_item_permissions_check( $request ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error(
                'dokan_pro_permission_failure',
                __( 'Sorry! You are not permitted to do current action.', 'dokan' )
            );
        }

        return true;
    }

    /**
     * Item schema.
     *
     * @since 3.11.1
     *
     * @return array
     */
    public function get_item_schema(): array {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'vendor_verification_document_type',
            'type'       => 'object',
            'properties' => [
                'id'        => [
                    'description' => __( 'Unique identifier for the resource.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'required'    => false,
                ],
                'title'     => [
                    'description' => __( 'Title of the resource.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                    'required'    => true,
                    'maxLength'   => 255,
                    'minLength'   => 1,
                ],
                'help_text' => [
                    'description' => __( 'Help Text of the resource.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                    'required'    => false,
                ],
                'status'    => [
                    'description' => __( 'The document type status is enabled or not', 'dokan' ),
                    'type'        => 'boolean',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                    'required'    => false,
                    'default'     => false,
                ],
                'kind'      => [
                    'description' => __( 'The document type kind', 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'text',
                    'enum'        => [ VerificationMethod::TYPE_ADDRESS, VerificationMethod::TYPE_CUSTOM ],
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                    'required'    => false,
                ],
                'required'  => [
                    'description' => __( 'The document type is required or not', 'dokan' ),
                    'type'        => 'boolean',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                    'required'    => false,
                    'default'     => false,
                ],
            ],
        ];

        return $schema;
    }

    /**
     * Retrieves the query params for the collections.
     *
     * @since 3.11.1
     *
     * @return array Query parameters for the collection.
     */
    public function get_collection_params(): array {
        $params = parent::get_collection_params();

        unset( $params['search'] );

        $additional_params = [
            'required' => [
                'description'       => __( 'The document type is required or not', 'dokan' ),
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'status'   => [
                'description'       => __( 'The document type is enabled or not', 'dokan' ),
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'validate_callback' => 'rest_validate_request_arg',
            ],
        ];

        return array_merge( $params, $additional_params );
    }
}
