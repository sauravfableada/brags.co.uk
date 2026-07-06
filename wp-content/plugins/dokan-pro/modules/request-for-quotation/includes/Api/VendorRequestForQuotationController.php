<?php
namespace WeDevs\DokanPro\Modules\RequestForQuotation\Api;

use WeDevs\DokanPro\Modules\RequestForQuotation\Model\Quote;
use WP_Error;
use WP_REST_Server;
use WeDevs\Dokan\REST\DokanBaseVendorController;
use WeDevs\DokanPro\Modules\RequestForQuotation\Helper;

/**
 * Vendor Request For Quote Controller Class
 *
 * Handles vendor-facing REST API endpoints for the Request For Quotation module.
 *
 * @since 5.0.0
 */
class VendorRequestForQuotationController extends DokanBaseVendorController {

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'vendor/request-for-quote';

    /**
     * Register all vendor request quote routes.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace, '/' . $this->rest_base, [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_vendor_request_quotes' ],
                    'args'                => $this->get_collection_params(),
                    'permission_callback' => [ $this, 'check_permission' ],
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
                'args' => [
                    'id' => [
                        'description' => __( 'Unique identifier for the object.', 'dokan' ),
                        'type'        => 'integer',
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_vendor_request_single_quote' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update_vendor_request_quote' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => [
                        'status' => [
                            'description' => __( 'Status to set for the quote.', 'dokan' ),
                            'type'        => 'string',
                            'enum'        => [ 'approve', 'reject', 'trash', 'pending' ],
                        ],
                    ],
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/batch', [
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'vendor_batch_update_quotes' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => [
                        'action' => [
                            'description' => __( 'Bulk action to perform.', 'dokan' ),
                            'type'        => 'string',
                            'enum'        => [ 'trash', 'pending' ],
                            'required'    => true,
                        ],
                        'items' => [
                            'description' => __( 'List of quote IDs to act upon.', 'dokan' ),
                            'type'        => 'array',
                            'items'       => [ 'type' => 'integer' ],
                            'required'    => true,
                        ],
                    ],
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
    }

    /**
     * Get all request quotes for the current vendor.
     *
     * @since 5.0.0
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_vendor_request_quotes( $request ) {
        $vendor_id        = $this->get_vendor_id_for_user();
        $allowed_statuses = [ 'pending', 'approve', 'expired', 'updated', 'accepted', 'reject', 'converted', 'cancel', 'trash' ];
        $status_raw       = $request['status'] ?? '';
        $status           = ( empty( $status_raw ) || 'all' === $status_raw ) ? '' : $status_raw;

        if ( ! empty( $status ) && ! in_array( $status, $allowed_statuses, true ) ) {
            $status = '';
        }

        $limit           = empty( $request['per_page'] ) ? 10 : absint( $request['per_page'] );
        $page            = empty( $request['page'] ) ? 1 : absint( $request['page'] );
        $offset          = ( $page - 1 ) * $limit;
        $allowed_orderby = [ 'id', 'quote_title', 'status', 'created_at' ];
        $orderby_raw     = ! empty( $request['orderby'] ) ? $request['orderby'] : 'id';
        $orderby         = in_array( $orderby_raw, $allowed_orderby, true ) ? $orderby_raw : 'id';
        $order_raw       = ! empty( $request['order'] ) ? strtoupper( $request['order'] ) : 'DESC';
        $order           = in_array( $order_raw, [ 'ASC', 'DESC' ], true ) ? $order_raw : 'DESC';

        $args = [
            'posts_per_page' => $limit,
            'offset'         => $offset,
            'status'         => $status,
            'author_id'      => $vendor_id,
            'order'          => $order,
            'orderby'        => $orderby,
        ];

        if ( ! empty( $request['search'] ) ) {
            $args['s'] = sanitize_text_field( $request['search'] );
        }

        $quotes = Helper::get_request_quote_for_vendor( $args );
        $result = [];

        if ( ! empty( $quotes ) ) {
            foreach ( $quotes as $quote ) {
                $result[] = $this->prepare_vendor_quote_response( $quote );
            }
        }

        // Get status counts for this vendor.
        // Note: counts are intentionally fetched without the active search/status filter so that
        // tab badges always reflect the full vendor quote totals, not just the current filtered view.
        $count_args    = [ 'author_id' => $vendor_id ];
        $counts        = Helper::count_request_quote_for_vendor( $count_args );
        $status_totals = [];

        if ( ! empty( $counts ) ) {
            foreach ( $counts as $row ) {
                $s = $row->status ?? '';
                if ( $s ) {
                    $status_totals[ $s ] = ( $status_totals[ $s ] ?? 0 ) + 1;
                }
            }
        }

        $all_count = array_sum(
            array_filter(
                $status_totals,
                function ( $s ) {
                    return 'trash' !== $s;
                },
                ARRAY_FILTER_USE_KEY
            )
        );

        // Calculate total items for the current status filter.
        if ( '' === $status ) {
            $total_items = $all_count;
        } else {
            $total_items = $status_totals[ $status ] ?? 0;
        }

        $total_pages = $total_items > 0 ? (int) ceil( $total_items / $limit ) : 1;

        $response = rest_ensure_response( $result );
        $response->header( 'X-WP-Total', $total_items );
        $response->header( 'X-WP-TotalPages', $total_pages );
        $response->header( 'X-Status-All', $all_count );
        $response->header( 'X-Status-Pending', $status_totals['pending'] ?? 0 );
        $response->header( 'X-Status-Approved', $status_totals['approve'] ?? 0 );
        $response->header( 'X-Status-Expired', $status_totals['expired'] ?? 0 );
        $response->header( 'X-Status-Updated', $status_totals['updated'] ?? 0 );
        $response->header( 'X-Status-Accepted', $status_totals['accepted'] ?? 0 );
        $response->header( 'X-Status-Rejected', $status_totals['reject'] ?? 0 );
        $response->header( 'X-Status-Converted', $status_totals['converted'] ?? 0 );
        $response->header( 'X-Status-Cancelled', $status_totals['cancel'] ?? 0 );
        $response->header( 'X-Status-Trash', $status_totals['trash'] ?? 0 );

        return $response;
    }

    /**
     * Get a single quote for the current vendor.
     *
     * @since 5.0.0
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_vendor_request_single_quote( $request ) {
        $quote_id  = absint( $request['id'] );
        $vendor_id = $this->get_vendor_id_for_user();

        if ( empty( $quote_id ) ) {
            return new WP_Error( 'no_quote_found', __( 'No quote found.', 'dokan' ), [ 'status' => 404 ] );
        }

        $quote = Helper::get_request_quote_vendor_by_id( $quote_id, $vendor_id );

        if ( empty( $quote ) ) {
            return new WP_Error(
                'dokan_rest_cannot_view',
                __( 'Quote not found or you do not have permission to view it.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        return rest_ensure_response( $this->prepare_vendor_quote_response( (object) $quote ) );
    }

    /**
     * Update a quote status as a vendor (approve/reject/trash/restore).
     *
     * @since 5.0.0
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public function update_vendor_request_quote( $request ) {
        $quote_id  = absint( $request['id'] );
        $vendor_id = $this->get_vendor_id_for_user();
        $status    = ! empty( $request['status'] ) ? sanitize_text_field( $request['status'] ) : '';

        if ( empty( $quote_id ) ) {
            return new WP_Error( 'no_quote_found', __( 'No quote found.', 'dokan' ), [ 'status' => 404 ] );
        }

        $quote = Helper::get_request_quote_vendor_by_id( $quote_id, $vendor_id );

        if ( empty( $quote ) ) {
            return new WP_Error(
                'dokan_rest_cannot_view',
                __( 'Quote not found or you do not have permission to update it.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        // Vendors may only approve, reject, trash, or restore (pending) quotes.
        $allowed_vendor_statuses = [
            Quote::STATUS_APPROVED,
            Quote::STATUS_REJECT,
            Quote::STATUS_TRASH,
            Quote::STATUS_PENDING,
        ];

        if ( ! in_array( $status, $allowed_vendor_statuses, true ) ) {
            return new WP_Error(
                'invalid_status',
                /* translators: %s: comma-separated list of allowed statuses */
                sprintf( __( 'Invalid status. Allowed: %s.', 'dokan' ), implode( ', ', $allowed_vendor_statuses ) ),
                [ 'status' => 400 ]
            );
        }

        Helper::change_status( 'dokan_request_quotes', $quote_id, $status );

        $updated_quote = Helper::get_request_quote_vendor_by_id( $quote_id, $vendor_id );

        return rest_ensure_response( $this->prepare_vendor_quote_response( (object) $updated_quote ) );
    }

    /**
     * Bulk update quote statuses for the current vendor (trash / restore).
     *
     * Request body: { "action": "trash"|"pending", "items": [1, 2, 3] }
     *
     * @since 5.0.0
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public function vendor_batch_update_quotes( $request ) {
        $vendor_id = $this->get_vendor_id_for_user();
        $action    = ! empty( $request['action'] ) ? sanitize_text_field( $request['action'] ) : '';
        $items     = ! empty( $request['items'] ) ? array_map( 'absint', (array) $request['items'] ) : [];

        if ( empty( $action ) ) {
            return new WP_Error( 'no_action', __( 'No bulk action specified.', 'dokan' ), [ 'status' => 400 ] );
        }

        if ( empty( $items ) ) {
            return new WP_Error( 'no_items', __( 'No items specified.', 'dokan' ), [ 'status' => 400 ] );
        }

        $allowed_actions = [ Quote::STATUS_TRASH, Quote::STATUS_PENDING ];

        if ( ! in_array( $action, $allowed_actions, true ) ) {
            return new WP_Error(
                'invalid_action',
                __( 'Invalid action. Allowed: trash, pending (restore).', 'dokan' ),
                [ 'status' => 400 ]
            );
        }

        foreach ( $items as $quote_id ) {
            // Verify vendor owns this quote before acting.
            $quote = Helper::get_request_quote_vendor_by_id( $quote_id, $vendor_id );
            if ( ! empty( $quote ) ) {
                Helper::change_status( 'dokan_request_quotes', $quote_id, $action );
            }
        }

        return rest_ensure_response( [ 'success' => true ] );
    }

    /**
     * Prepare a vendor-facing quote response object.
     *
     * @since 5.0.0
     *
     * @param object $quote Raw quote object from the database.
     *
     * @return array
     */
    protected function prepare_vendor_quote_response( $quote ) {
        $customer_info  = ! empty( $quote->customer_info ) ? maybe_unserialize( $quote->customer_info ) : [];
        $customer_name  = ! empty( $customer_info['name_field'] )
            ? $customer_info['name_field']
            : ( $quote->quote_title ?? '' );
        $expiry_date    = ! empty( $quote->expiry_date ) ? (int) $quote->expiry_date : 0;
        $expiry_display = '';

        if ( $expiry_date > 0 ) {
            $expiry_display = dokan_current_datetime()->setTimestamp( $expiry_date )->format( 'jS M Y' );
        }

        $data = [
            'id'             => (int) $quote->id,
            'title'          => $quote->quote_title ?? '',
            'status'         => $quote->status ?? '',
            'created_at'     => ! empty( $quote->created_at )
                ? dokan_current_datetime()->setTimestamp( (int) $quote->created_at )->format( get_option( 'date_format' ) )
                : '',
            'customer_name'  => $customer_name,
            'order_url'      => ! empty( $quote->order_id )
                ? dokan_get_navigation_url( 'orders' ) . absint( $quote->order_id ) . '/'
                : '',
            'expiry_date'    => $expiry_date,
            'expiry_display' => $expiry_display,
        ];

        /**
         * Filter the vendor-facing quote response data.
         *
         * @since 5.0.0
         *
         * @param array  $data  Response data.
         * @param object $quote Raw quote object from the database.
         */
        return apply_filters( 'dokan_rest_prepare_vendor_quote_object', $data, $quote );
    }

    /**
     * Retrieves the item schema for the vendor quote response, conforming to JSON Schema.
     *
     * @since 5.0.0
     *
     * @return array
     */
    public function get_item_schema(): array {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'vendor-request-quote',
            'type'       => 'object',
            'properties' => [
                'id'             => [
                    'description' => __( 'Unique identifier for the quote.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'title'          => [
                    'description' => __( 'Title of the quote.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'status'         => [
                    'description' => __( 'Current status of the quote.', 'dokan' ),
                    'type'        => 'string',
                    'enum'        => [ 'pending', 'approve', 'updated', 'accepted', 'reject', 'converted', 'expired', 'cancel', 'trash' ],
                    'context'     => [ 'view', 'edit' ],
                ],
                'created_at'     => [
                    'description' => __( 'Human-readable creation date of the quote.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'customer_name'  => [
                    'description' => __( 'Name of the customer who submitted the quote.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'order_url'      => [
                    'description' => __( 'URL of the associated order, if any.', 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'uri',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'expiry_date'    => [
                    'description' => __( 'Expiry timestamp of the quote (0 if none).', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'expiry_display' => [
                    'description' => __( 'Human-readable expiry date of the quote.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
            ],
        ];

        return $this->add_additional_fields_schema( $schema );
    }
}
