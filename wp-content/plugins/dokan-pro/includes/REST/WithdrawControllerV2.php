<?php

namespace WeDevs\DokanPro\REST;

use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WeDevs\Dokan\REST\WithdrawControllerV2 as WithdrawControllerV2Lite;
use WeDevs\DokanPro\Withdraw\Helper;

class WithdrawControllerV2 extends WithdrawControllerV2Lite {

    /**
     * Register all routes releated with stores.
     *
     * @since 3.7.23
     *
     * @return void
     */
    public function register_routes() {
        // Returns withdraw disbursement.
        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/disbursement', [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_withdraw_disbursement' ],
                    'permission_callback' => [ $this, 'get_permissions_check' ],
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
                    'callback'            => [ $this, 'save_withdraw_disbursement' ],
                    'permission_callback' => [ $this, 'get_permissions_check' ],
                ],
                'schema' => [ $this, 'get_item_schema' ],
            ]
        );

        // Returns withdraw disbursement.
        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/disbursement/disable', [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'save_withdraw_disbursement_disable_status' ],
                    'permission_callback' => [ $this, 'get_permissions_check' ],
                ],
                'schema' => [ $this, 'get_item_schema_for_enable_status' ],
            ]
        );
    }

    /**
     * Checks endpoint access permission.
     *
     * @since 3.7.23
     *
     * @return bool
     */
    public function get_permissions_check() {
        return current_user_can( 'dokan_manage_withdraw' ) && Helper::is_withdraw_disbursement_enabled();
    }

    /**
     * Returns withdraw disbursement data.
     *
     * @param WP_REST_Request $request
     *
     * @since 3.7.23
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_withdraw_disbursement( WP_REST_Request $request ) {
        // Withdraw disbursement data.
        $schedule_data = dokan_pro()->withdraw->withdraw_schedule_data();

        // If disbursement is not enabled from dokan setting.
        if ( empty( $schedule_data['enabled'] ) ) {
            return new WP_Error( 'withdraw-disbursement', __( 'Withdraw disbursement is disabled.', 'dokan' ) );
        }

        return $this->prepare_item_for_response( $schedule_data, $request );
    }

    /**
     * Prepares withdraw disbursement data.
     *
     * @since 3.7.23
     *
     * @param array $schedule_data The schedule data.
     * @param WP_REST_Request $request Request.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function prepare_item_for_response( $schedule_data, $request ) {
        $data = [];
        $fields = $this->get_fields_for_response( $request );

        $data['enabled']                  = in_array( 'enabled', $fields, true ) ? $schedule_data['enabled'] : false;
        $data['selected_schedule']        = in_array( 'selected_schedule', $fields, true ) ? sanitize_text_field( $schedule_data['selected_schedule'] ) : '';
        $data['minimum_amount_list']      = in_array( 'minimum_amount_list', $fields, true ) ? array_map( 'absint', $schedule_data['minimum_amount_list'] ) : [];
        $data['minimum_amount_selected']  = in_array( 'minimum_amount_selected', $fields, true ) ? (int) $schedule_data['minimum_amount_selected'] : 0;
        $data['reserve_balance_list']     = in_array( 'reserve_balance_list', $fields, true ) ? array_map( 'absint', $schedule_data['reserve_balance_list'] ) : '';
        $data['reserve_balance_selected'] = in_array( 'reserve_balance_selected', $fields, true ) ? (int) $schedule_data['reserve_balance_selected'] : 1;
        $data['default_method']           = in_array( 'default_method', $fields, true ) ? sanitize_text_field( $schedule_data['default_method'] ) : '';

        $data['schedules'] = [];
        if ( in_array( 'schedules', $fields, true ) ) {
            foreach ( $schedule_data['schedules'] as $key => $schedule ) {
                $data['schedules'][ sanitize_key( $key ) ] = [
                    'next'        => sanitize_text_field( $schedule['next'] ),
                    'title'       => sanitize_text_field( $schedule['title'] ),
                    'description' => sanitize_text_field( $schedule['description'] ),
                ];
            }
        }

        $data['active_methods'] = [];
        if ( in_array( 'active_methods', $fields, true ) ) {
            $data['active_methods'] = array_map(
                function( $method_key ) {
                    return sanitize_text_field( dokan_withdraw_get_method_title( $method_key ) );
                },
                $schedule_data['active_methods']
            );
        }

        $data['method_additional_info'] = [];
        if ( in_array( 'method_additional_info', $fields, true ) ) {
            foreach ( $schedule_data['active_methods'] as $key => $value ) {
                $data['method_additional_info'][ sanitize_key( $key ) ] = sanitize_text_field( $value . ' ' . dokan_withdraw_get_method_additional_info( $key ) );
            }
        }

        $data['minimum_amount_needed'] = in_array( 'minimum_amount_needed', $fields, true ) ? (int) Helper::get_selected_minimum_withdraw_amount() : 0;

        $saved_schedule               = get_user_meta( dokan_get_current_user_id(), 'dokan_withdraw_selected_schedule', true );
        $data['is_schedule_selected'] = ! empty( $saved_schedule ) && in_array( $saved_schedule, Helper::get_active_schedules(), true );

        $data = $this->filter_response_by_context( $data, $request['context'] ?? 'view' );

        return rest_ensure_response( $data );
    }

    /**
     * Saves withdraw disbursement.
     *
     * @since 3.7.23
     *
     * @param WP_REST_Request $requests Request object.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function save_withdraw_disbursement( $requests ) {
        $schedule = $requests->get_param( 'schedule' );
        $minimum  = $requests->get_param( 'minimum' );
        $reserve  = $requests->get_param( 'reserve' );
        $method   = $requests->get_param( 'method' );

        Helper::save_withdraw_schedule( $schedule, $minimum, $reserve, $method );

        return rest_ensure_response( [ 'success' => true ] );
    }

    /**
     * Saves withdraw disbursement enable status.
     *
     * @since 3.7.23
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function save_withdraw_disbursement_disable_status() {
        update_user_meta( dokan_get_current_user_id(), 'dokan_withdraw_selected_schedule', '' );

        return rest_ensure_response( [ 'success' => true ] );
    }

    /**
     * Withdraw disbursement REST request item schema.
     *
     * @since 3.7.23
     *
     * @return array
     */
    public function get_item_schema() {
        // Returned cached copy whenever available.
        if ( $this->schema ) {
            return $this->add_additional_fields_schema( $this->schema );
        }

        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => __( 'Withdraw disbursement', 'dokan' ),
            'type'       => 'array',
            'sanitize_callback' => 'wc_clean',
            'properties' => [
                'default_method' => [
                    'description'       => __( 'Default payment method', 'dokan' ),
                    'type'              => 'string',
                    'context'           => [ 'view' ],
                    'default'           => 'paypal',
                    'readonly'          => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'selected_schedule' => [
                    'description'       => __( 'Schedule selected', 'dokan' ),
                    'type'              => 'string',
                    'context'           => [ 'view' ],
                    'readonly'          => true,
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'enabled' => [
                    'description'       => __( 'Is withdraw disbursement enabled', 'dokan' ),
                    'type'              => 'boolean',
                    'context'           => [ 'view' ],
                    'readonly'          => true,
                    'default'           => false,
                    'sanitize_callback' => 'rest_sanitize_boolean',
                ],
                'minimum_amount_list' => [
                    'description'       => __( 'Minimum amount list', 'dokan' ),
                    'type'              => 'array',
                    'context'           => [ 'view' ],
                    'readonly'          => true,
                    'default'           => [],
                    'sanitize_callback' => 'wc_clean',
                    'items' => array(
                        'type' => 'number',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ],
                'minimum_amount_needed' => [
                    'description'       => __( 'Minimum amount needed', 'dokan' ),
                    'type'              => 'number',
                    'context'           => [ 'view' ],
                    'readonly'          => true,
                    'default'           => '0',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'minimum_amount_selected' => [
                    'description'       => __( 'Minimum amount selected', 'dokan' ),
                    'type'              => 'number',
                    'context'           => [ 'view' ],
                    'readonly'          => true,
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'reserve_balance_selected' => [
                    'description'       => __( 'Reserve balance selected', 'dokan' ),
                    'type'              => 'number',
                    'context'           => [ 'view' ],
                    'readonly'          => true,
                    'default'           => '0',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'reserve_balance_list' => [
                    'description'       => __( 'Minimum amount list', 'dokan' ),
                    'type'              => 'array',
                    'context'           => [ 'view' ],
                    'readonly'          => true,
                    'default'           => [],
                    'sanitize_callback' => 'wc_clean',
                    'items' => array(
                        'type' => 'number',
                    ),
                ],
                'active_methods' => [
                    'description'       => __( 'Active payment methods.', 'dokan' ),
                    'type'              => 'object',
                    'readonly'          => true,
                    'context'           => [ 'view' ],
                    'default'           => [],
                ],
                'method_additional_info' => [
                    'description'       => __( 'Payment methods additional information.', 'dokan' ),
                    'type'              => 'object',
                    'context'           => [ 'view' ],
                    'readonly'          => true,
                    'default'           => [],
                ],
                'schedules' => [
                    'description' => __( 'All schedules', 'dokan' ),
                    'type'        => 'array',
                    'context'     => [ 'view' ],
                    'readonly'          => true,
                    'default'           => [],
                    'items'       => [
                        'biweekly' => [
                            'description' => __( 'Schedule', 'dokan' ),
                            'type'        => 'object',
                            'context'     => [ 'view' ],
                            'items'       => [
                                'description' => [
                                    'description'       => __( 'Schedule description', 'dokan' ),
                                    'type'              => 'string',
                                    'context'           => [ 'view' ],
                                    'required'          => true,
                                    'sanitize_callback' => 'sanitize_text_field',
                                ],
                                'next' => [
                                    'description'       => __( 'Next schedule', 'dokan' ),
                                    'type'              => 'string',
                                    'context'           => [ 'view', 'edit' ],
                                    'required'          => true,
                                    'sanitize_callback' => 'sanitize_text_field',
                                ],
                                'title' => [
                                    'description'       => __( 'Schedule title', 'dokan' ),
                                    'type'              => 'string',
                                    'context'           => [ 'view', 'edit' ],
                                    'required'          => true,
                                    'sanitize_callback' => 'sanitize_text_field',
                                ],
                            ],
                        ],
                        'monthly' => [
                            'description' => __( 'Schedule', 'dokan' ),
                            'type'        => 'object',
                            'context'     => [ 'view' ],
                            'items'       => [
                                'description' => [
                                    'description'       => __( 'Schedule description', 'dokan' ),
                                    'type'              => 'string',
                                    'context'           => [ 'view' ],
                                    'required'          => true,
                                    'sanitize_callback' => 'sanitize_text_field',
                                ],
                                'next' => [
                                    'description'       => __( 'Next schedule', 'dokan' ),
                                    'type'              => 'string',
                                    'context'           => [ 'view', 'edit' ],
                                    'required'          => true,
                                    'sanitize_callback' => 'sanitize_text_field',
                                ],
                                'title' => [
                                    'description'       => __( 'Schedule title', 'dokan' ),
                                    'type'              => 'string',
                                    'context'           => [ 'view', 'edit' ],
                                    'required'          => true,
                                    'sanitize_callback' => 'sanitize_text_field',
                                ],
                            ],
                        ],
                        'quarterly' => [
                            'description' => __( 'Schedule', 'dokan' ),
                            'type'        => 'object',
                            'context'     => [ 'view' ],
                            'items'       => [
                                'description' => [
                                    'description'       => __( 'Schedule description', 'dokan' ),
                                    'type'              => 'string',
                                    'context'           => [ 'view' ],
                                    'required'          => true,
                                    'sanitize_callback' => 'sanitize_text_field',
                                ],
                                'next' => [
                                    'description'       => __( 'Next schedule', 'dokan' ),
                                    'type'              => 'string',
                                    'context'           => [ 'view', 'edit' ],
                                    'required'          => true,
                                    'sanitize_callback' => 'sanitize_text_field',
                                ],
                                'title' => [
                                    'description'       => __( 'Schedule title', 'dokan' ),
                                    'type'              => 'string',
                                    'context'           => [ 'view', 'edit' ],
                                    'required'          => true,
                                    'sanitize_callback' => 'sanitize_text_field',
                                ],
                            ],
                        ],
                        'weekly' => [
                            'description' => __( 'Schedule', 'dokan' ),
                            'type'        => 'object',
                            'context'     => [ 'view' ],
                            'items'       => [
                                'description' => [
                                    'description'       => __( 'Schedule description', 'dokan' ),
                                    'type'              => 'string',
                                    'context'           => [ 'view' ],
                                    'required'          => true,
                                    'sanitize_callback' => 'sanitize_text_field',
                                ],
                                'next' => [
                                    'description'       => __( 'Next schedule', 'dokan' ),
                                    'type'              => 'string',
                                    'context'           => [ 'view', 'edit' ],
                                    'required'          => true,
                                    'sanitize_callback' => 'sanitize_text_field',
                                ],
                                'title' => [
                                    'description'       => __( 'Schedule title', 'dokan' ),
                                    'type'              => 'string',
                                    'context'           => [ 'view', 'edit' ],
                                    'required'          => true,
                                    'sanitize_callback' => 'sanitize_text_field',
                                ],
                            ],
                        ],
                    ],
                ],
                'schedule' => [
                    'description'       => __( 'Preferred Payment Schedule', 'dokan' ),
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'enum'              => Helper::get_active_schedules(),
                    'required'          => true,
                    'context'           => [ 'edit' ],
                ],
                'minimum' => [
                    'description'       => __( 'Only When Balance Is', 'dokan' ),
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'enum'              => Helper::get_nearest_minimum_withdraw_amount_list( Helper::get_minimum_withdraw_amount() ),
                    'required'          => true,
                    'context'           => [ 'edit' ],
                ],
                'reserve' => [
                    'description'       => __( 'Maintain Reserve Balance', 'dokan' ),
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'enum'              => Helper::get_minimum_reserve_balance_list(),
                    'required'          => true,
                    'context'           => [ 'edit' ],
                ],
                'method' => [
                    'description'       => __( 'Preferred Payment Method', 'dokan' ),
                    'type'              => 'string',
                    'enum'              => array_keys( dokan_withdraw_get_withdrawable_active_methods() ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'required'          => true,
                    'context'           => [ 'edit' ],
                ],
            ],
        ];

        return $this->add_additional_fields_schema( $schema );
    }

    /**
     * Withdraw disbursement REST request item schema
     *
     * @since 3.7.23
     *
     * @return array
     */
    public function get_item_schema_for_enable_status() {
        // Returned cached copy whenever available.
        if ( $this->schema ) {
            return $this->add_additional_fields_schema( $this->schema );
        }

        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => __( 'Withdraw disbursement enable status', 'dokan' ),
            'type'       => 'array',
            'properties' => [
                'success' => [
                    'description'       => __( 'Success status', 'dokan' ),
                    'type'              => 'boolean',
                    'context'           => [ 'view' ],
                    'required'          => false,
                ],
            ],
        ];

        return $this->add_additional_fields_schema( $schema );
    }
}
