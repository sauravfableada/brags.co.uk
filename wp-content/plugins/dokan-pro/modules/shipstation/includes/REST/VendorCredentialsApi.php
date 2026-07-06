<?php

namespace WeDevs\DokanPro\Modules\ShipStation\REST;

use Exception;
use WeDevs\Dokan\REST\DokanBaseVendorController;
use WeDevs\DokanPro\Modules\ShipStation\VendorApiCredentials;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit();

/**
 * Shipstation Venodor Credentials API.
 *
 * @since 3.14.4
 */
class VendorCredentialsApi extends DokanBaseVendorController {

    /**
     * Class Constructor.
     *
     * @return void
     */
    public function __construct() {
        $this->rest_base = 'shipstation/credentials';
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
            '/' . $this->rest_base . '/create',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'create_item' ],
                    'permission_callback' => [ $this, 'create_item_permissions_check' ],
                    'args'                => [
                        'vendor_id' => [
                            'description'       => __( 'Vendor ID.', 'dokan' ),
                            'type'              => 'integer',
                            'required'          => true,
                            'sanitize_callback' => 'absint',
                            'minimum'           => 1,
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
                'args' => [
                    'id' => [
                        'description' => __( 'Vendor id', 'dokan' ),
                        'type'        => 'integer',
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_item' ],
                    'permission_callback' => [ $this, 'get_item_permissions_check' ],
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'delete_item' ],
                    'permission_callback' => [ $this, 'delete_item_permissions_check' ],
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
    }

    /**
     * Get Item Permission Check.
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return bool|WP_Error
     */
    public function get_item_permissions_check( $request ) {
        $vendor_id = absint( $request->get_param( 'id' ) );

        if ( ! $this->can_access_vendor_store( $vendor_id ) ) {
            return new WP_Error(
                'dokan_pro_permission_failure',
                __( 'Sorry! You are not permitted to do current action.', 'dokan' ),
                [ 'status' => 403 ]
            );
        }

        return true;
    }

    /**
     * Create Item Permission Check.
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|bool
     */
    public function create_item_permissions_check( $request ) {
        $vendor_id = absint( $request->get_param( 'vendor_id' ) );

        if ( ! $this->can_access_vendor_store( $vendor_id ) ) {
            return new WP_Error(
                'dokan_pro_permission_failure',
                __( 'Sorry! You are not permitted to do current action.', 'dokan' ),
                [ 'status' => 403 ]
            );
        }

        return true;
    }

    /**
     * Delete Item Permission Check.
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|bool
     */
    public function delete_item_permissions_check( $request ) {
        return $this->get_item_permissions_check( $request );
    }

    /**
     * Get A Credential.
     *
     * @since 3.14.4
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function get_item( $request ) {
        $vendor_id = $request->get_param( 'id' );

        try {
            $item = VendorApiCredentials::get( $vendor_id );

            if ( ! $item['key_id'] ) {
                throw new Exception( __( 'No resource found.', 'dokan' ) );
            }
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_no_resource', $e->getMessage(), [ 'status' => 404 ] );
        }

        $response = $this->prepare_item_for_response( $item, $request );
        $response->set_status( 200 );

        return $response;
    }

    /**
     * Create A New Credential.
     *
     * @since 3.14.4
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function create_item( $request ) {
        $vendor_id = $request->get_param( 'vendor_id' );

        try {
            $existing_credential = VendorApiCredentials::get( $vendor_id );

            if ( $existing_credential['key_id'] ) {
                throw new Exception( __( 'The vendor already has API credentials.', 'dokan' ) );
            }

            $created_item = VendorApiCredentials::create( $vendor_id );
        } catch ( Exception $e ) {
            dokan_log( esc_html__( 'Error creating vendor API credential: ', 'dokan' ) . $e->getMessage() );
            return new WP_Error( 'dokan_pro_rest_cannot_create', $e->getMessage(), [ 'status' => 500 ] );
        }

        $response = $this->prepare_item_for_response( $created_item, $request );
        $response->set_status( 201 );

        return $response;
    }

    /**
     * Delete A Credential.
     *
     * @since 3.14.4
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function delete_item( $request ) {
        $vendor_id = absint( $request->get_param( 'id' ) );

        try {
            $deleted_item = VendorApiCredentials::remove( $vendor_id );

            if ( ! $deleted_item ) {
                throw new Exception( __( 'Something went wrong to delete credential.', 'dokan' ) );
            }
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_cannot_delete', $e->getMessage(), [ 'status' => 500 ] );
        }

        $response = $this->prepare_item_for_response( $deleted_item, $request );
        $response->set_status( 200 );

        return $response;
    }

    /**
     * Prepares the item for the REST response.
     *
     * @since 3.14.4
     *
     * @param $item
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function prepare_item_for_response( $item, $request ) {
        $data   = [];
        $fields = $this->get_fields_for_response( $request );

        if ( in_array( 'key_id', $fields, true ) ) {
            $data['key_id'] = intval( $item['key_id'] );
        }

        if ( in_array( 'consumer_key', $fields, true ) ) {
            $data['consumer_key'] = (string) $item['consumer_key'];
        }

        if ( in_array( 'consumer_secret', $fields, true ) && $item['consumer_secret'] ) {
            $data['consumer_secret'] = (string) $item['consumer_secret'];
        }

        if ( in_array( 'dokan_auth_key', $fields, true ) ) {
            $data['dokan_auth_key'] = (string) $item['dokan_auth_key'];
        }

        $context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
        $data     = $this->filter_response_by_context( $data, $context );
        $response = rest_ensure_response( $data );

        return $response;
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
                'key_id'          => [
                    'description' => __( 'Key ID of the credential.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'required'    => true,
                ],
                'consumer_key'    => [
                    'description' => __( 'Consumer key for the credential.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'consumer_secret' => [
                    'description' => __( 'Consumer secret for the credential.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'dokan_auth_key'  => [
                    'description' => __( 'Dokan auth key for ShipStation.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
            ],
        ];

        return $schema;
    }
}
