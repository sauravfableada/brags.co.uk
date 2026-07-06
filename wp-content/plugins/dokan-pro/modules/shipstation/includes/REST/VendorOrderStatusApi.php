<?php

namespace WeDevs\DokanPro\Modules\ShipStation\REST;

use Exception;
use WeDevs\Dokan\REST\DokanBaseVendorController;
use WeDevs\DokanPro\Modules\ShipStation\VendorOrderStatusSettings;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit();

/**
 * Shipstation Venodor Order Status API.
 *
 * @since 3.14.4
 */
class VendorOrderStatusApi extends DokanBaseVendorController {

    /**
     * Class Constructor.
     *
     * @return void
     */
    public function __construct() {
        $this->rest_base = 'shipstation/order-statuses';
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
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update_item' ],
                    'permission_callback' => [ $this, 'update_item_permissions_check' ],
                    'args' => [
                        'vendor_id'       => [
                            'description'       => __('Vendor ID.', 'dokan'),
                            'type'              => 'integer',
                            'required'          => true,
                            'sanitize_callback' => 'absint',
                            'minimum'           => 1,
                        ],
                        'export_statuses' => [
                            'description'       => __('Export order statuses.', 'dokan'),
                            'type'              => 'array',
                            'required'          => true,
                            'items'             => [
                                'type'          => 'string',
                                'enum'          => array_keys( wc_get_order_statuses() ),
                            ],
                            'validate_callback' => function( $param ) {
                                $validation =  is_array( $param ) && count( $param ) >= 1;

                                if ( ! $validation ) {
                                    return new WP_Error( 'invalid_parameters', __( 'Invalid export order status, it must be an array of strings.', 'dokan' ), 400 );
                                }
                            },
                        ],
                        'shipped_status'  => [
                            'description'       => __('Shipped order status.', 'dokan'),
                            'type'              => 'string',
                            'required'          => true,
                            'enum'              => array_keys( wc_get_order_statuses() ),
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
     * Update Item Permission Check.
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|bool
     */
    public function update_item_permissions_check( $request ) {
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
     * Get A Order Status Settings.
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
            $item = VendorOrderStatusSettings::get( $vendor_id );

            if ( ! ( $item['shipped_status'] || $item['export_statuses'] ) ) {
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
     * Update A Order Status Settings.
     *
     * @since 3.14.4
     *
     * @param WP_REST_Request $request Rest Request.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function update_item( $request ) {
        $vendor_id       = $request->get_param( 'vendor_id' );
        $export_statuses = $request->get_param( 'export_statuses' );
        $shipped_status  = $request->get_param( 'shipped_status' );

        try {
            if ( ! dokan_is_user_seller( $vendor_id ) ) {
                throw new Exception( esc_html__( 'Invalid vendor ID', 'dokan' ) );
            }

            $updated_item = VendorOrderStatusSettings::update( $vendor_id, $export_statuses, $shipped_status );

            if ( ! $updated_item ) {
                throw new Exception( __( 'Something went wrong to create credential.', 'dokan' ) );
            }
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan_pro_rest_cannot_create', $e->getMessage(), [ 'status' => 500 ] );
        }

        $response = $this->prepare_item_for_response( $updated_item, $request );
        $response->set_status( 201 );

        return $response;
    }

    /**
     * Delete A Order Status Settings.
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
            $deleted_item = VendorOrderStatusSettings::remove( $vendor_id );

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

        if ( in_array( 'vendor_id', $fields, true ) ) {
            $data['vendor_id'] = (int) $item['vendor_id'];
        }

        if ( in_array( 'export_statuses', $fields, true ) ) {
            $data['export_statuses'] = (array) $item['export_statuses'];
        }

        if ( in_array( 'shipped_status', $fields, true ) ) {
            $data['shipped_status'] = (string) $item['shipped_status'];
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
                'vendor_id'       => [
                    'description' => __( 'Vendor ID.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'required'    => true,
                ],
                'export_statuses' => [
                    'description' => __( 'Export order statuses.', 'dokan' ),
                    'type'        => 'array',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'required'    => false,
                    'items'       => [
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => 'rest_validate_request_arg',
                    ],
                ],
                'shipped_status'  => [
                    'description'       => __( 'Shipped order status.', 'dokan' ),
                    'type'              => 'string',
                    'context'           => [ 'view', 'edit' ],
                    'readonly'          => true,
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
            ],
        ];

        return $schema;
    }
}
