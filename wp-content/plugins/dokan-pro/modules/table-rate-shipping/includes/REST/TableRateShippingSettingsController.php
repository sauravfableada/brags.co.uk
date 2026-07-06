<?php

namespace WeDevs\DokanPro\Modules\TableRate\REST;

use WeDevs\Dokan\REST\DokanBaseVendorController;
use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

class TableRateShippingSettingsController extends DokanBaseVendorController {

    /**
     * Route name.
     *
     * @since 4.0.0
     *
     * @var string
     */
    protected $rest_base = 'shipping/table-rate/settings';

    /**
     * Register routes.
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/zone/(?P<zone_id>[\d]+)/instance/(?P<instance_id>[\d]+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_settings' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => $this->get_collection_params(),
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update_settings' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                ],
                'schema' => [ $this, 'get_item_schema' ],
            ]
        );
    }

    /**
     * Prepares the item for the REST response.
     *
     * @since 4.0.0
     *
     * @param mixed           $item    WordPress representation of the item.
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function prepare_item_for_response( $item, $request ) {
        $data = [
            'title'              => $item['title'] ?? '',
            'tax_status'         => $item['tax_status'] ?? 'none',
            'prices_include_tax' => $item['prices_include_tax'] ?? '',
            'handling_fee'       => $item['handling_fee'] ?? '',
            'max_shipping_cost'  => $item['max_shipping_cost'] ?? '',
            'calculation_type'   => $item['calculation_type'] ?? '',
            'order_handling_fee' => $item['order_handling_fee'] ?? '',
            'min_cost'           => $item['min_cost'] ?? '',
            'max_cost'           => $item['max_cost'] ?? '',
            'classes_priorities' => $item['classes_priorities'] ?? [],
            'default_priority'   => $item['default_priority'] ?? 0,
        ];

        // Get zone ID and instance ID from the request.
        $zone_id     = $request['zone_id'] ?? 0;
        $instance_id = $request['instance_id'] ?? 0;

        /**
         * Filters for the table rate shipping settings data.
         *
         * @since 4.0.0
         *
         * @param array           $data        The formatted settings data.
         * @param mixed           $item        The original item data.
         * @param WP_REST_Request $request     Full details about the request.
         * @param int             $zone_id     The shipping zone ID.
         * @param int             $instance_id The shipping method instance ID.
         */
        $data = apply_filters( 'dokan_table_rate_shipping_prepare_settings_response', $data, $item, $request, $zone_id, $instance_id );

        return rest_ensure_response( $data );
    }

    /**
     * Get settings.
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function get_settings( WP_REST_Request $request ) {
        $zone_id     = $request['zone_id'] ?? 0;
        $instance_id = $request['instance_id'] ?? 0;

        try {
            $method_info = dokan_pro()->module->table_rate_shipping->get_shipping_method( $instance_id );
            $settings    = $method_info['settings'] ?? [];

            if ( ! $settings ) {
	            return new WP_Error(
		            'dokan_rest_table_rate_settings_data_not_found',
		            esc_html__( 'Shipping method data not found', 'dokan' ),
		            [ 'status' => 404 ]
	            );
            }
        } catch ( \Exception $e ) {
            return new WP_Error(
                'dokan_rest_table_rate_settings_not_found',
	            esc_html__( 'Shipping method not found', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        /**
         * Filter table rate settings data
         *
         * @since 4.0.0
         *
         * @param array $settings
         * @param int   $zone_id
         * @param int   $instance_id
         */
        $settings = apply_filters( 'dokan_table_rate_shipping_settings_data', $settings, $zone_id, $instance_id );

        return rest_ensure_response( $settings );
    }

    /**
     * Update settings.
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function update_settings( WP_REST_Request $request ) {
        global $wpdb;

        $zone_id     = $request['zone_id'] ?? 0;
        $instance_id = $request['instance_id'] ?? 0;
        $vendor_id   = dokan_get_current_user_id();
        $method_info = dokan_pro()->module->table_rate_shipping->get_shipping_method( $instance_id );

        $sanitized_data = [
            'title'              => ! empty( $request['title'] ) ? sanitize_text_field( wp_unslash( $request['title'] ) ) : '',
            'tax_status'         => ! empty( $request['tax_status'] ) ? sanitize_text_field( wp_unslash( $request['tax_status'] ) ) : 'none',
            'prices_include_tax' => ! empty( $request['prices_include_tax'] ) ? sanitize_text_field( wp_unslash( $request['prices_include_tax'] ) ) : '',
            'handling_fee'       => ! empty( $request['handling_fee'] ) ? sanitize_text_field( wp_unslash( $request['handling_fee'] ) ) : '',
            'max_shipping_cost'  => ! empty( $request['max_shipping_cost'] ) ? sanitize_text_field( wp_unslash( $request['max_shipping_cost'] ) ) : '',
            'calculation_type'   => ! empty( $request['calculation_type'] ) ? sanitize_text_field( wp_unslash( $request['calculation_type'] ) ) : '',
            'order_handling_fee' => ! empty( $request['order_handling_fee'] ) ? sanitize_text_field( wp_unslash( $request['order_handling_fee'] ) ) : '',
            'min_cost'           => ! empty( $request['min_cost'] ) ? sanitize_text_field( wp_unslash( $request['min_cost'] ) ) : '',
            'max_cost'           => ! empty( $request['max_cost'] ) ? sanitize_text_field( wp_unslash( $request['max_cost'] ) ) : '',
            'classes_priorities' => ! empty( $request['classes_priorities'] ) ? array_map( 'intval', (array) $request['classes_priorities'] ) : array(),
            'default_priority'   => ! empty( $request['default_priority'] ) ? sanitize_text_field( wp_unslash( $request['default_priority'] ) ) : 0,
        ];

        /**
         * Filter table rate shipping settings data before save.
         *
         * @since 4.0.0
         *
         * @param array $sanitized_data
         * @param int   $zone_id
         * @param int   $instance_id
         */
        $sanitized_data = apply_filters( 'dokan_table_rate_shipping_settings_before_save', $sanitized_data, $zone_id, $instance_id );

        $updated = $wpdb->update(
            $wpdb->prefix . 'dokan_shipping_zone_methods',
            [
                'method_id'  => 'dokan_table_rate_shipping',
                'zone_id'    => $zone_id,
                'seller_id'  => $vendor_id,
                'is_enabled' => $method_info['enabled'] === 'yes' ? 1 : 0,
                'settings'   => maybe_serialize( $sanitized_data ),
            ],
            [
                'instance_id' => $instance_id,
                'seller_id'   => $vendor_id,
            ],
            [ '%s', '%d', '%d', '%d', '%s' ],
            [ '%d', '%d' ]
        );

        if ( is_wp_error( $updated ) ) {
            return new WP_Error(
                'dokan_table_rate_settings_update_failed',
                esc_html__( 'Failed to update settings', 'dokan' ),
                [ 'status' => 400 ]
            );
        }

        /**
         * Fires after table rate shipping settings update
         *
         * @since 4.0.0
         *
         * @param array $settings
         * @param int   $zone_id
         * @param int   $instance_id
         */
        do_action( 'dokan_table_rate_shipping_settings_updated', $sanitized_data, $zone_id, $instance_id );

        // Format the response data.
        $response_data = $this->prepare_item_for_response( $sanitized_data, $request );

        return rest_ensure_response(
            [
                'message'  => esc_html__( 'Settings updated successfully', 'dokan' ),
                'settings' => $response_data->get_data(),
            ]
        );
    }

    /**
     * Check if a given request has access to get items.
     *
     * @since 4.0.0
     *
     * @return bool
     */
    public function check_permission(): bool {
        return current_user_can( 'dokan_view_store_shipping_menu' );
    }

    /**
     * Get collection params.
     *
     * @since 4.0.0
     *
     * @return array
     */
    public function get_collection_params(): array {
        return [
            'zone_id'     => [
                'description'       => esc_html__( 'Shipping Zone ID.', 'dokan' ),
                'type'              => 'integer',
                'required'          => true,
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'instance_id' => [
                'description'       => esc_html__( 'Shipping Method Instance ID.', 'dokan' ),
                'type'              => 'integer',
                'required'          => true,
                'validate_callback' => 'rest_validate_request_arg',
            ],
        ];
    }

    /**
     * Get settings schema.
     *
     * @since 4.0.0
     *
     * @return array
     */
    public function get_item_schema(): array {
        $this->schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'table_rate_shipping_settings',
            'type'       => 'object',
            'properties' => [
                'title'              => [
                    'description' => esc_html__( 'Shipping Method title', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                ],
                'tax_status'         => [
                    'description' => esc_html__( 'Tax status', 'dokan' ),
                    'type'        => 'string',
                    'enum'        => [ 'taxable', 'none' ],
                    'context'     => [ 'view', 'edit' ],
                ],
                'calculation_type'   => [
                    'description' => esc_html__( 'Calculation type', 'dokan' ),
                    'type'        => 'string',
                    'enum'        => [ '', 'item', 'line', 'class' ],
                    'context'     => [ 'view', 'edit' ],
                ],
                'description'        => [
                    'description' => esc_html__( 'Method description', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                ],
                'prices_include_tax' => [
                    'description' => esc_html__( 'Whether prices include tax', 'dokan' ),
                    'type'        => 'string',
                    'enum'        => [ 'yes', 'no' ],
                    'context'     => [ 'view', 'edit' ],
                ],
                'order_handling_fee' => [
                    'description' => esc_html__( 'Order handling fee', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                ],
                'classes_priorities' => [
                    'description' => esc_html__( 'Shipping class priorities', 'dokan' ),
                    'type'        => 'object',
                    'context'     => [ 'view', 'edit' ],
                ],
                'default_priority'   => [
                    'description' => esc_html__( 'Default priority', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                ],
            ],
        ];

        return $this->schema;
    }
}
