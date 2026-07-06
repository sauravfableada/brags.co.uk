<?php

namespace WeDevs\DokanPro\Modules\TableRate\REST;

use WeDevs\Dokan\REST\DokanBaseVendorController;
use WeDevs\DokanPro\Modules\TableRate\Hooks;
use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

class TableRateController extends DokanBaseVendorController {

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'shipping/table-rate/rates';

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
                    'callback'            => [ $this, 'get_shipping_rates' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => $this->get_collection_params(),
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'save_shipping_rates' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'delete_shipping_rates' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::DELETABLE ),
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
     * @param array           $item    WordPress representation of the item.
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function prepare_item_for_response( $item, $request ) {
        // Get zone ID and instance ID from the request.
        $zone_id     = $request['zone_id'] ?? 0;
        $instance_id = $request['instance_id'] ?? 0;

        $data = [
            'rate_id'                   => $item['rate_id'] ?? 0,
            'zone_id'                   => $zone_id,
            'instance_id'               => $instance_id,
            'vendor_id'                 => $item['vendor_id'] ?? dokan_get_current_user_id(),
            'rate_class'                => $item['rate_class'] ?? '',
            'rate_condition'            => $item['rate_condition'] ?? '',
            'rate_min'                  => $item['rate_min'] ?? '',
            'rate_max'                  => $item['rate_max'] ?? '',
            'rate_cost'                 => $item['rate_cost'] ?? '',
            'rate_cost_per_item'        => $item['rate_cost_per_item'] ?? '',
            'rate_cost_per_weight_unit' => $item['rate_cost_per_weight_unit'] ?? '',
            'rate_cost_percent'         => $item['rate_cost_percent'] ?? '',
            'rate_label'                => $item['rate_label'] ?? '',
            'rate_priority'             => $item['rate_priority'] ?? 0,
            'rate_abort'                => $item['rate_abort'] ?? 0,
            'rate_abort_reason'         => $item['rate_abort_reason'] ?? '',
            'rate_order'                => $item['rate_order'] ?? 0,
        ];

        /**
         * Filter the table rate shipping rate data.
         *
         * @since 4.0.0
         *
         * @param array           $data        The formatted shipping rate data.
         * @param array           $item        The original item data.
         * @param WP_REST_Request $request     Full details about the request.
         * @param int             $zone_id     The shipping zone ID.
         * @param int             $instance_id The shipping method instance ID.
         */
        $data = apply_filters( 'dokan_table_rate_prepare_shipping_rate_response', $data, $item, $request, $zone_id, $instance_id );

        return rest_ensure_response( $data );
    }

    /**
     * Get table rates
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function get_shipping_rates( $request ) {
        $instance_id = $request['instance_id'] ?? 0;

        if ( ! $instance_id ) {
            return new WP_Error(
                'dokan_table_rate_invalid_instance',
                esc_html__( 'Invalid instance ID', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        try {
            $prepared_rates = [];
            $zone_id        = $request['zone_id'] ?? 0;
            $shipping_rates = dokan_pro()->module->table_rate_shipping->get_normalized_shipping_rates( $instance_id );

            foreach ( $shipping_rates as $rate ) {
                $prepared_response = $this->prepare_item_for_response( $rate, $request );
                $prepared_rates[]  = $prepared_response->get_data();
            }

            /**
             * Filters for the collection of table rate shipping rates.
             *
             * @since 4.0.0
             *
             * @param array           $prepared_rates The prepared shipping rates.
             * @param array           $shipping_rates The original shipping rates.
             * @param WP_REST_Request $request        Full details about the request.
             * @param int             $zone_id        The shipping zone ID.
             * @param int             $instance_id    The shipping method instance ID.
             */
            $prepared_rates = apply_filters(
                'dokan_table_rate_prepare_shipping_rates_collection',
                $prepared_rates,
                $shipping_rates,
                $request,
                $zone_id,
                $instance_id
            );

            return rest_ensure_response( $prepared_rates );
        } catch ( \Exception $e ) {
            return new WP_Error(
                'dokan_table_rate_error',
                $e->getMessage(),
                [ 'status' => 404 ]
            );
        }
    }

    /**
     * Save table rates
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function save_shipping_rates( $request ) {
        $instance_id = $request['instance_id'] ?? 0;
        if ( ! $instance_id ) {
            return new WP_Error(
                'dokan_table_rate_invalid_instance',
                esc_html__( 'Invalid instance ID', 'dokan' ),
                [ 'status' => 400 ]
            );
        }

        $rate_data = $request['preparedData'] ?? [];
        if ( empty( $rate_data ) ) {
            return new WP_Error(
                'dokan_table_rate_empty_data',
                esc_html__( 'No shipping rate data provided', 'dokan' ),
                [ 'status' => 400 ]
            );
        }

        $zone_id       = $request['zone_id'] ?? 0;
        $prepared_data = [
            'zone_id'                  => $zone_id,
            'instance_id'              => $instance_id,
            'rate_ids'                 => array_map( 'intval', $rate_data['rate_ids'] ?? [] ),
            'shipping_class'           => array_map( 'wc_clean', $rate_data['shipping_class'] ?? [] ),
            'shipping_condition'       => array_map( 'wc_clean', $rate_data['shipping_condition'] ?? [] ),
            'shipping_min'             => array_map( 'wc_clean', $rate_data['shipping_min'] ?? [] ),
            'shipping_max'             => array_map( 'wc_clean', $rate_data['shipping_max'] ?? [] ),
            'shipping_cost'            => array_map( 'wc_clean', $rate_data['shipping_cost'] ?? [] ),
            'shipping_per_item'        => array_map( 'wc_clean', $rate_data['shipping_per_item'] ?? [] ),
            'shipping_cost_per_weight' => array_map( 'wc_clean', $rate_data['shipping_cost_per_weight'] ?? [] ),
            'cost_percent'             => array_map( 'wc_clean', $rate_data['cost_percent'] ?? [] ),
            'shipping_label'           => array_map( 'wc_clean', $rate_data['shipping_label'] ?? [] ),
            'shipping_priority'        => array_map( 'wc_clean', $rate_data['shipping_priority'] ?? [] ),
            'shipping_abort'           => array_map( 'wc_clean', $rate_data['shipping_abort'] ?? [] ),
            'shipping_abort_reason'    => array_map( 'wc_clean', $rate_data['shipping_abort_reason'] ?? [] ),
        ];

        try {
            Hooks::save_table_rate_data( $prepared_data );
            return rest_ensure_response(
                [
                    'message' => __( 'Table rates saved successfully', 'dokan' ),
                    'rates'   => $this->get_shipping_rates( $request ),
                ]
            );
        } catch ( \Exception $e ) {
            return new WP_Error(
                'dokan_table_rate_save_error',
                $e->getMessage(),
                [ 'status' => 400 ]
            );
        }
    }

    /**
     * Delete table rates
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function delete_shipping_rates( $request ) {
        $instance_id = $request['instance_id'] ?? 0;
        if ( ! $instance_id ) {
            return new WP_Error(
                'dokan_table_rate_invalid_instance',
                esc_html__( 'Invalid instance ID', 'dokan' ),
                [ 'status' => 400 ]
            );
        }

        $zone_id  = $request['zone_id'] ?? 0;
        $rate_ids = $request['rate_ids'] ?? [];
        if ( empty( $rate_ids ) ) {
            return new WP_Error(
                'dokan_table_rate_empty_rates',
                esc_html__( 'No rate IDs provided for deletion', 'dokan' ),
                [ 'status' => 400 ]
            );
        }

        try {
            $delete_rates = dokan_pro()->module->table_rate_shipping->delete_table_rates( $rate_ids, $zone_id, $instance_id );
            if ( ! $delete_rates ) {
                throw new \Exception( esc_html__( 'Failed to delete shipping rates', 'dokan' ) );
            }
        } catch ( \Exception $e ) {
            return new WP_Error(
                'dokan_table_rate_delete_error',
                $e->getMessage(),
                [ 'status' => 400 ]
            );
        }

        return rest_ensure_response( $delete_rates );
    }

    /**
     * Check permission
     *
     * @return bool
     */
    public function check_permission(): bool {
        return current_user_can( 'dokan_view_store_shipping_menu' );
    }

    /**
     * Get collection params
     *
     * @return array
     */
    public function get_collection_params(): array {
        return [
            'zone_id'     => [
                'description'       => __( 'Shipping Zone ID.', 'dokan' ),
                'type'              => 'integer',
                'required'          => true,
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'instance_id' => [
                'description'       => __( 'Shipping Method Instance ID.', 'dokan' ),
                'type'              => 'integer',
                'required'          => true,
                'validate_callback' => 'rest_validate_request_arg',
            ],
        ];
    }

    /**
     * Get item schema
     *
     * @return array
     */
    public function get_item_schema(): array {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'table_rate_shipping',
            'type'       => 'object',
            'properties' => [
                'rate_class'                => [
                    'description' => __( 'Shipping class', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                ],
                'rate_condition'            => [
                    'description' => __( 'Rate condition', 'dokan' ),
                    'type'        => 'string',
                    'enum'        => [ 'weight', 'price', 'items', 'items_in_class' ],
                    'context'     => [ 'view', 'edit' ],
                ],
                'rate_min'                  => [
                    'description' => __( 'Minimum rate', 'dokan' ),
                    'type'        => 'number',
                    'context'     => [ 'view', 'edit' ],
                ],
                'rate_max'                  => [
                    'description' => __( 'Maximum rate', 'dokan' ),
                    'type'        => 'number',
                    'context'     => [ 'view', 'edit' ],
                ],
                'rate_cost'                 => [
                    'description' => __( 'Rate cost', 'dokan' ),
                    'type'        => 'number',
                    'context'     => [ 'view', 'edit' ],
                ],
                'rate_cost_per_item'        => [
                    'description' => __( 'Rate cost per item', 'dokan' ),
                    'type'        => 'number',
                    'context'     => [ 'view', 'edit' ],
                ],
                'rate_cost_per_weight_unit' => [
                    'description' => __( 'Rate cost per weight', 'dokan' ),
                    'type'        => 'number',
                    'context'     => [ 'view', 'edit' ],
                ],
                'rate_cost_percent'         => [
                    'description' => __( 'Rate cost percent', 'dokan' ),
                    'type'        => 'number',
                    'context'     => [ 'view', 'edit' ],
                ],
                'rate_label'                => [
                    'description' => __( 'Rate label', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                ],
                'rate_priority'             => [
                    'description' => __( 'Rate priority', 'dokan' ),
                    'type'        => 'boolean',
                    'context'     => [ 'view', 'edit' ],
                ],
                'rate_abort'                => [
                    'description' => __( 'Rate abort', 'dokan' ),
                    'type'        => 'boolean',
                    'context'     => [ 'view', 'edit' ],
                ],
                'rate_abort_reason'         => [
                    'description' => __( 'Rate abort reason', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                ],
                'rate_order'                => [
                    'description' => __( 'Rate order', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                ],
            ],
        ];
    }
}
