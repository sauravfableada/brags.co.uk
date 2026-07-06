<?php

namespace WeDevs\DokanPro\REST;

use WeDevs\DokanPro\Reports\StatementData;
use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WeDevs\DokanPro\Reports\ReportStatement;
use WeDevs\Dokan\REST\DokanBaseVendorController;

/**
 * REST Controller for reports statement.
 *
 * @since 4.0.0
 */
class ReportStatementController extends DokanBaseVendorController {

    /**
     * Statement manager instance.
     *
     * @since 4.0.0
     *
     * @var ReportStatement
     */
    protected ReportStatement $statement_manager;

    /**
     * Constructor.
     *
     * @since 4.0.0
     */
    public function __construct() {
        $this->statement_manager = new ReportStatement();
    }

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
            '/' . $this->rest_base . '/reports/statement',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_items' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => $this->get_collection_params(),
                ],
                'schema' => [ $this, 'get_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/reports/statement/summary',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_summary' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => $this->get_collection_params(),
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/reports/statement/export',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'export_statement' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => $this->get_collection_params(),
                ],
            ]
        );
    }

    /**
     * Get statement data.
     *
     * @param WP_REST_Request $request
     *
     * @return object|WP_Error
     */
    public function get_items( $request ) {
        $args = [
            'vendor_id'  => dokan_get_current_user_id(),
            'start_date' => $request['start_date'] ?? '',
            'end_date'   => $request['end_date'] ?? '',
            'per_page'   => $request['per_page'] ?? 10,
            'page'       => $request['page'] ?? 1,
        ];

        $result = $this->statement_manager->get_statement_data( $args );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $result      = $this->prepare_data_for_statement_items( $result, $request );
        $response    = rest_ensure_response( $result );
        $total_items = (int) ( $response->data['total_items'] ?? count( $response->data ?? [] ) );

        // Add pagination headers.
        return $this->format_collection_response( $response, $request, $total_items );
    }

    /**
     * Get summary data.
     * This is a separate endpoint that can be called independently.
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_summary( $request ) {
        $args = [
            'vendor_id'  => dokan_get_current_user_id(),
            'start_date' => $request['start_date'] ?? '',
            'end_date'   => $request['end_date'] ?? '',
        ];

        // Get statement data which already includes summary
        $summary_data = $this->statement_manager->get_summary_data( $args );
        if ( is_wp_error( $summary_data ) ) {
            return $summary_data;
        }

        // Return the statement summary data.
        return rest_ensure_response( (array) $summary_data );
    }

    /**
     * Export statement data.
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function export_statement( $request ) {
        $args = [
            'vendor_id'   => dokan_get_current_user_id(),
            'start_date'  => $request['start_date'] ?? '',
            'end_date'    => $request['end_date'] ?? '',
        ];

        $csv_data = $this->statement_manager->get_statement_csv_data( $args );
        if ( is_wp_error( $csv_data ) ) {
            return $csv_data;
        }

        $filename = 'Statement-' . dokan_current_datetime()->format( 'Y-m-d' );
        return rest_ensure_response(
            [
                'file_name' => $filename,
                'file_url'  => rest_url( sprintf( '%s/%s/export', $this->namespace, $this->rest_base ) ),
                'content'   => $csv_data,
                'success'   => true,
            ]
        );
    }

    /**
     * Check statement report view permission.
     *
     * @since 4.0.0
     *
     * @return bool
     */
    public function check_permission(): bool {
        return current_user_can( 'dokan_view_statement_report' );
    }

    /**
     * Prepare statement data for REST response.
     *
     * @since 4.0.0
     *
     * @param StatementData   $data
     * @param WP_REST_Request $request
     *
     * @return array
     */
    public function prepare_data_for_statement_items( $data, $request ) {
        // Prepare the base response structure
        $prepared_data = [];

        // Get processed entries and prepare each one individually
        foreach ( $data->get_entries() as $entry ) {
            $prepared_data[] = $this->prepare_item_for_response( $entry, $request );
        }

        /**
         * Filter the prepared statement data.
         *
         * @since 4.0.0
         *
         * @param array           $prepared_data Prepared statement data
         * @param array|object    $data          Original statement data
         * @param WP_REST_Request $request       Request object
         */
        return apply_filters( 'dokan_rest_prepare_vendor_statement_response', $prepared_data, $data, $request );
    }

    /**
     * Prepare statement data for REST response.
     *
     * @since 4.0.0
     *
     * @param array           $entry
     * @param WP_REST_Request $request
     *
     * @return array
     */
    public function prepare_item_for_response( $entry, $request ) {
        $data = [
            'id'           => absint( $entry['id'] ?? 0 ),
            'vendor_id'    => absint( $entry['vendor_id'] ?? 0 ),
            'trn_id'       => isset( $entry['trn_id'] ) ? absint( $entry['trn_id'] ) : null,
            'trn_type'     => sanitize_text_field( $entry['trn_type'] ?? '' ),
            'perticulars'  => sanitize_text_field( $entry['perticulars'] ?? '' ),
            'trn_title'    => sanitize_text_field( $entry['trn_title'] ?? '' ),
            'debit'        => floatval( $entry['debit'] ?? 0 ),
            'credit'       => floatval( $entry['credit'] ?? 0 ),
            'status'       => sanitize_text_field( $entry['status'] ?? '' ),
            'trn_date'     => sanitize_text_field( $entry['trn_date'] ?? null ),
            'balance_date' => sanitize_text_field( $entry['balance_date'] ?? '' ),
            'balance'      => floatval( $entry['balance'] ?? 0 ),
            'url'          => esc_url_raw( html_entity_decode( $entry['url'] ?? '' ) ),
        ];

        /**
         * Filter the prepared statement entries.
         *
         * @since 4.0.0
         *
         * @param array           $prepared_data Prepared statement data
         * @param array|object    $data          Original statement data
         * @param WP_REST_Request $request       Request object
         */
        return apply_filters( 'dokan_rest_prepare_statement_entries', $data, $request );
    }

    /**
     * Get collection params.
     *
     * @since 4.0.0
     *
     * @return array
     */
    public function get_collection_params(): array {
        $params = parent::get_collection_params();

        $params['start_date'] = [
            'description'       => esc_html__( 'Start date of statement period', 'dokan' ),
            'type'              => 'string',
            'format'            => 'date',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['end_date'] = [
            'description'       => esc_html__( 'End date of statement period', 'dokan' ),
            'type'              => 'string',
            'format'            => 'date',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        return $params;
    }

    /**
     * Get item schema.
     *
     * @since 4.0.0
     *
     * @return array
     */
    public function get_item_schema(): array {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'statement_entry',
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'description' => esc_html__( 'Entry ID', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                ],
                'vendor_id' => [
                    'description' => esc_html__( 'Vendor ID', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                ],
                'trn_id' => [
                    'description' => esc_html__( 'Transaction ID', 'dokan' ),
                    'type'        => [ 'integer', 'null' ],
                    'context'     => [ 'view' ],
                ],
                'trn_type' => [
                    'description' => esc_html__( 'Transaction type', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'perticulars' => [
                    'description' => esc_html__( 'Transaction details', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'trn_title' => [
                    'description' => esc_html__( 'Transaction title', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'debit' => [
                    'description' => esc_html__( 'Debit amount', 'dokan' ),
                    'type'        => 'number',
                    'context'     => [ 'view' ],
                ],
                'credit' => [
                    'description' => esc_html__( 'Credit amount', 'dokan' ),
                    'type'        => 'number',
                    'context'     => [ 'view' ],
                ],
                'status' => [
                    'description' => esc_html__( 'Transaction status', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'trn_date' => [
                    'description' => esc_html__( 'Transaction date', 'dokan' ),
                    'type'        => [ 'string', 'null' ],
                    'format'      => 'date-time',
                    'context'     => [ 'view' ],
                ],
                'balance_date' => [
                    'description' => esc_html__( 'Date of the balance entry', 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => [ 'view' ],
                ],
                'balance' => [
                    'description' => esc_html__( 'Running balance', 'dokan' ),
                    'type'        => 'number',
                    'context'     => [ 'view' ],
                ],
                'url' => [
                    'description' => esc_html__( 'URL for transaction details', 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'uri',
                    'context'     => [ 'view' ],
                ],
            ],
        ];
    }
}
