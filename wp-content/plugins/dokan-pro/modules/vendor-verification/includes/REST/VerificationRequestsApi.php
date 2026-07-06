<?php

namespace WeDevs\DokanPro\Modules\VendorVerification\REST;

use Exception;
use WeDevs\Dokan\Vendor\Vendor;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationMethod;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationRequest;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit();

/**
 * Verification Request API.
 *
 * @since 3.11.1
 */
class VerificationRequestsApi extends \WP_REST_Controller {

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
    protected $rest_base = 'verification-requests';

    /**
     * Constructor.
     *
     * @since 3.11.1
     *
     * @return void
     */
    public function __construct() {
        // empty for now
    }

    /**
     * Register Rest Routes.
     *
     * @since 3.11.1
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

        // Bulk operations endpoint
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/batch',
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [ $this, 'batch_items' ],
                'permission_callback' => [ $this, 'batch_items_permissions_check' ],
                'args'                => [
                    'update' => [
                        'type'        => 'array',
                        'description' => esc_html__( 'Array of verification requests to update.', 'dokan' ),
                        'items'       => [
                            'type'       => 'object',
                            'properties' => [
                                'id'     => [
                                    'type'        => 'integer',
                                    'description' => esc_html__( 'Verification request ID.', 'dokan' ),
                                ],
                                'status' => [
                                    'type'        => 'string',
                                    'description' => esc_html__( 'Verification request status.', 'dokan' ),
                                    'enum'        => [ 'pending', 'approved', 'rejected', 'cancelled' ],
                                ],
                                'note'   => [
                                    'type'        => 'string',
                                    'description' => esc_html__( 'Admin note for the verification request.', 'dokan' ),
                                ],
                            ],
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
        $args = $request->get_params();
        $data = [];
        try {
            $verification_request = new VerificationRequest();
            $items                = $verification_request->query( $args );
            $count                = $verification_request->count( $args );
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_api_error', $e->getMessage(), [ 'status' => 500 ] );
        }
        foreach ( $items as $item ) {
            $item_data = $this->prepare_item_for_response( $item, $request );
            $data[]    = $this->prepare_response_for_collection( $item_data );
        }

        $response = rest_ensure_response( $data );
        $total    = ! empty( $args['status'] ) ? $count[ $args['status'] ] : $count['total'];

        $response->header( 'X-Status-Pending', $count[ VerificationRequest::STATUS_PENDING ] );
        $response->header( 'X-Status-Approved', $count[ VerificationRequest::STATUS_APPROVED ] );
        $response->header( 'X-Status-Cancelled', $count[ VerificationRequest::STATUS_CANCELLED ] );
        $response->header( 'X-Status-Rejected', $count[ VerificationRequest::STATUS_REJECTED ] );

        $response->header( 'X-WP-Total', $count['total'] );
        $response->header( 'X-WP-TotalPages', ceil( $total / $request['per_page'] ) );

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
        $vendor_id       = absint( $request->get_param( 'vendor_id' ) );
        $method_id       = absint( $request->get_param( 'method_id' ) );
        $status          = $request->get_param( 'status' ) ?? VerificationRequest::STATUS_PENDING;
        $documents       = $request->get_param( 'documents' );
        $note            = $request->get_param( 'note' ) ?? '';
        $additional_info = $request->get_param( 'additional_info' ) ?? [];
        $vendor          = new Vendor( $vendor_id );

        if ( ! $vendor->is_vendor() ) {
            return new WP_Error('dokan_pro_rest_cannot_create', esc_html__( 'Vendor not found.', 'dokan' ), [ 'status' => 500 ] );
        }

        $method = new VerificationMethod( $method_id );
        if ( ! $method->get_id() ) {
            return new WP_Error('dokan_pro_rest_cannot_create', esc_html__( 'Verification Method not found.', 'dokan' ), [ 'status' => 500 ] );
        }

        $documents = $this->validate_document_ids( $documents );

        if ( empty( $documents ) ) {
            return new WP_Error('dokan_pro_rest_cannot_create', esc_html__( 'Documents IDs are not valid.', 'dokan' ), [ 'status' => 500 ] );
        }

        try {
            $verification_request = new VerificationRequest();
            $verification_request
                ->set_documents( $documents )
                ->set_method_id( $method_id )
                ->set_status( $status )
                ->set_vendor_id( $vendor_id )
                ->set_note( $note )
                ->set_additional_info( $additional_info )
                ->save();
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_cannot_create', $e->getMessage(), [ 'status' => 500 ] );
        }

        $response = $this->prepare_item_for_response( $verification_request, $request );
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
        $id = $request->get_param( 'id' );
        try {
            $item = new VerificationRequest( $id );
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_no_resource', $e->getMessage(), [ 'status' => 404 ] );
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
        $id = absint( $request->get_param( 'id' ) );

        try {
            $item = new VerificationRequest( $id );
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_no_resource', $e->getMessage(), [ 'status' => 404 ] );
        }

        $vendor_id       = absint( $request->get_param( 'vendor_id' ) );
        $method_id       = absint( $request->get_param( 'method_id' ) );
        $status          = $request->get_param( 'status' );
        $documents       = $request->get_param( 'documents' );
        $note            = $request->get_param( 'note' );
        $additional_info = $request->get_param( 'additional_info' );

        if ( ! empty( $vendor_id ) && current_user_can( 'manage_options' ) ) {
            $vendor = new Vendor( $vendor_id );

            if ( ! $vendor->is_vendor() ) {
                return new WP_Error('dokan_pro_rest_cannot_create', esc_html__( 'Vendor not found.', 'dokan' ), [ 'status' => 500 ] );
            }
            $item->set_vendor_id( $vendor_id );
        }

        if ( ! empty( $method_id ) && current_user_can( 'manage_options' ) ) {
            $method = new VerificationMethod( $method_id );
            if ( ! $method->get_id() ) {
                return new WP_Error('dokan_pro_rest_cannot_create', esc_html__( 'Verification Method not found.', 'dokan' ), [ 'status' => 500 ] );
            }
            $item->set_method_id( $method_id );
        }

        if ( ! empty( $status ) ) {
            if ( ! current_user_can( 'manage_options' ) ) {
                $status = VerificationRequest::STATUS_CANCELLED;
            }
            $item->set_status( $status );
        }

        if ( isset( $documents ) && is_array( $documents ) && current_user_can( 'manage_options' ) ) {
            $documents = $this->validate_document_ids( $documents );

            if ( empty( $documents ) ) {
                return new WP_Error('dokan_pro_rest_cannot_create', esc_html__( 'Documents IDs are not valid.', 'dokan' ), [ 'status' => 500 ] );
            }
            $item->set_documents( $documents );
        }

        if ( isset( $note ) && current_user_can( 'manage_options' ) ) {
            $item->set_note( $note );
        }

        if ( isset( $additional_info ) && current_user_can( 'manage_options' ) ) {
            $item->set_additional_info( $additional_info );
        }

        if ( current_user_can( 'manage_options' ) ) {
            $item->set_checked_by( get_current_user_id() );
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
     * @since 3.11.1
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function delete_item( $request ) {
        $id = absint( $request->get_param( 'id' ) );
        try {
            $delivery = new VerificationRequest( $id );
            $delivery->get();
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_no_resource', $e->getMessage(), [ 'status' => 404 ] );
        }

        try {
            $delivery->delete();
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
     * @param VerificationRequest $item    WordPress' representation of the item.
     * @param WP_REST_Request     $request Request object.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function prepare_item_for_response( $item, $request ) {
        $item_data           = $item->to_array();
        $item_data['vendor'] = ( new Vendor( $item->get_vendor_id() ) )->get_shop_info();
        $item_data['method'] = ( new VerificationMethod( $item->get_method_id() ) )->to_array();

        $file_urls = [];
        foreach ( $item->get_documents() as $doc ) {
            // todo: this should b included to the schema
            $file_urls[ $doc ] = [
                'url'   => wp_get_attachment_url( $doc ),
                'title' => get_the_title( $doc ),
            ];
        }

        $item_data['document_urls'] = $file_urls;

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
     * @param VerificationRequest $item    Object data.
     * @param WP_REST_Request     $request Request object.
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
     * Get Items permission check.
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
            __(
                'Sorry! You are not permitted to do current action.',
                'dokan'
            )
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
        if (
            current_user_can( 'manage_options' )
            || (
                current_user_can( 'seller' )
                && $this->is_author( $request->get_param( 'id' ), dokan_get_current_user_id() )
            )
        ) {
            return true;
        }

        return new WP_Error(
            'dokan_pro_permission_failure',
            __( 'Sorry! You are not permitted to do current action.', 'dokan' )
        );

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
        if ( current_user_can( 'manage_options' ) || current_user_can( 'seller' ) ) {
            return true;
        }

        return new WP_Error(
            'dokan_pro_permission_failure',
            __( 'Sorry! You are not permitted to do current action.', 'dokan' )
        );
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
        return $this->get_item_permissions_check( $request );
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
            'title'      => 'vendor_verification_request',
            'type'       => 'object',
            'properties' => [
                'id'              => [
                    'description' => __( 'Unique identifier for the resource.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'required'    => false,
                ],
                'vendor_id'       => [
                    'description' => __( 'Vendor id associated with verification request.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                    'required'    => true,
                ],
                'method_id'       => [
                    'description' => __( 'Verification Method id associated with verification request.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                    'required'    => true,
                ],
                'status'          => [
                    'description' => __( 'The verification request status.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                    'required'    => false,
                ],
                'status_title'    => [
                    'description' => __( 'The verification request status title.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'required'    => false,
                ],
                'note'            => [
                    'description' => __( 'The verification request note for administrator.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                    'required'    => false,
                ],
                'additional_info' => [
                    'description' => __( 'The documents additional information.', 'dokan' ),
                    'type'        => 'object',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                    'required'    => false,
                ],
                'documents'       => [
                    'description' => __( 'The documents for verification', 'dokan' ),
                    'type'        => 'array',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                    'required'    => true,
                    'minItems'    => 1,
                    'items'       => [
                        'type' => 'integer',
                    ],
                ],
                'checked_by'      => [
                    'description' => __( 'The documents checker id.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'required'    => false,
                ],
                'created_at'      => [
                    'description' => __( 'The verification request creation time.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'required'    => false,
                ],
                'updated_at'      => [
                    'description' => __( 'The verification request updated time.', 'dokan' ),
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
     * @since 3.11.1
     *
     * @return array Query parameters for the collection.
     */
    public function get_collection_params(): array {
        $params = parent::get_collection_params();

        unset( $params['search'] );

        $additional_params = [
            'vendor_id' => [
                'description'       => __( 'Vendor ID associated with the verification request.', 'dokan' ),
                'type'              => 'integer',
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'method_id' => [
                'description'       => __( 'Verification Method ID associated with the verification request.', 'dokan' ),
                'type'              => 'integer',
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'status'    => [
                'description'       => __( 'The document type is enabled or not', 'dokan' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ],
        ];

        return array_merge( $params, $additional_params );
    }

    /**
     * Is verification request author.
     *
     * @since 3.11.1
     *
     * @param int $verification_request_id Request ID.
     * @param int $author_id               Author ID to compare.
     *
     * @return bool
     */
    public function is_author( int $verification_request_id = 0, int $author_id = 0 ): bool {
        try {
            $verification = new VerificationRequest( $verification_request_id );
        } catch ( Exception $e ) {
            return false;
        }

        return $verification->get_vendor_id() === $author_id;
    }

    /**
     * Validate document ids.
     *
     * @since 3.11.1
     *
     * @param array $documents Document IDs.
     *
     * @return array
     */
    protected function validate_document_ids( array $documents ): array {
        return array_filter(
            array_map(
                function ( $id ) {
                    return wp_get_attachment_url( $id ) ? $id : false;
                },
                $documents
            )
        );
    }

    /**
     * Batch items operation.
     *
     * @since 4.1.4
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function batch_items( $request ) {
        $items_to_update = $request->get_param( 'update' );
        $results = [
            'update' => [],
        ];

        if ( ! empty( $items_to_update ) && is_array( $items_to_update ) ) {
            foreach ( $items_to_update as $item_data ) {
                if ( empty( $item_data['id'] ) ) {
                    continue;
                }

                $id = absint( $item_data['id'] );

                try {
                    $item = new VerificationRequest( $id );
                } catch ( Exception $e ) {
                    $results['update'][] = new WP_Error( 'dokan_pro_rest_no_resource', $e->getMessage(), [ 'status' => 404 ] );
                    continue;
                }

                // Update status if provided
                if ( ! empty( $item_data['status'] ) ) {
                    $item->set_status( $item_data['status'] );
                }

                // Update note if provided
                if ( isset( $item_data['note'] ) ) {
                    $item->set_note( $item_data['note'] );
                }

                // Set checked_by to current admin user
                $item->set_checked_by( get_current_user_id() );

                try {
                    $item->update();
                    $results['update'][] = $this->prepare_item_for_response( $item, $request );
                } catch ( Exception $e ) {
                    $results['update'][] = new WP_Error( 'dokan_pro_rest_cannot_update', $e->getMessage(), [ 'status' => 500 ] );
                }
            }
        }

        return rest_ensure_response( $results );
    }

    /**
     * Check if a given request has access to batch items.
     *
     * @since 4.1.4
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return bool|WP_Error True if the request has access, WP_Error object otherwise.
     */
    public function batch_items_permissions_check( $request ) {
        return current_user_can( 'manage_options' );
    }
}
