<?php

namespace WeDevs\DokanPro\Modules\VendorSupport\APIs;

use Exception;
use WeDevs\Dokan\REST\DokanBaseController;
use WeDevs\Dokan\Vendor\Vendor;
use WeDevs\DokanPro\Modules\VendorSupport\Models\Ticket;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Class TicketsController
 *
 * REST API controller for vendor support tickets.
 *
 * @since 4.1.2
 *
 * @package WeDevs\DokanPro\Modules\VendorSupport\APIs
 */
class TicketsController extends DokanBaseController {

    /**
     * Route base.
     *
     * @since 4.1.2
     *
     * @var string
     */
    protected $rest_base = 'vendor-support/tickets';

    /**
     * Register the routes for tickets.
     *
     * @since 4.1.2
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
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                    'args'                => $this->get_collection_params(),
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'create_item' ],
                    'permission_callback' => [ $this, 'create_item_permissions_check' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
                ],
                'schema' => [ $this, 'get_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            [
                'args' => [
                    'id' => [
                        'description' => __( 'Unique identifier for the ticket.', 'dokan' ),
                        'type'        => 'integer',
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_item' ],
                    'permission_callback' => [ $this, 'get_item_permissions_check' ],
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
                ],
                'schema' => [ $this, 'get_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/batch',
            [
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'batch_items' ],
                    'permission_callback' => [ $this, 'batch_items_permissions_check' ],
                    'args'                => $this->get_batch_collection_params(),
                ],
                'schema' => [ $this, 'get_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/counts',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_counts' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                    'args'                => $this->get_counts_collection_params(),
                ],
            ]
        );
    }

    /**
     * Get a collection of tickets.
     *
     * @since 4.1.2
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_items( $request ) {
        $args = [
            'limit'    => $request['per_page'],
            'offset'   => ( $request['page'] - 1 ) * $request['per_page'],
            'orderby'  => $request['orderby'],
            'order'    => $request['order'],
            'search'   => $request['search'],
            'status'   => $request['status'],
            'priority' => $request['priority'],
        ];

        // Add date filtering
        if ( ! empty( $request['date_from'] ) ) {
            $args['date_from'] = $request['date_from'];
        }

        if ( ! empty( $request['date_to'] ) ) {
            $args['date_to'] = $request['date_to'];
        }

        // Add conversation status filtering
        if ( ! empty( $request['conversation_status'] ) ) {
            $args['conversation_status'] = $request['conversation_status'];
        }

        // Filter by vendor if not admin
        if ( ! current_user_can( 'manage_options' ) ) {
            $vendor_id = dokan_get_current_user_id();
            if ( ! dokan_is_user_seller( $vendor_id ) ) {
                return new WP_Error(
                    'dokan_rest_cannot_view',
                    __( 'Sorry, you are not allowed to view tickets.', 'dokan' ),
                    [ 'status' => rest_authorization_required_code() ]
                );
            }
            $tickets = Ticket::query( $vendor_id, $args );
            $total   = Ticket::count( $vendor_id, $args );
        } else {
            // Admin can see all tickets
            if ( ! empty( $request['vendor_id'] ) ) {
                $args['vendor_id'] = $request['vendor_id'];
            }
            $tickets = Ticket::query_all( $args );
            $total   = Ticket::count_all( $args );
        }

        $data = [];
        foreach ( $tickets as $ticket ) {
            $ticket_data = $this->prepare_item_for_response( $ticket, $request );
            $data[]      = $this->prepare_response_for_collection( $ticket_data );
        }

        $response = rest_ensure_response( $data );
        $response->header( 'X-WP-Total', $total );
        $response->header( 'X-WP-TotalPages', ceil( $total / $request['per_page'] ) );

        return $response;
    }

    /**
     * Create a single ticket.
     *
     * @since 4.1.2
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function create_item( $request ) {
        try {
            $ticket = new Ticket();

            // Set vendor ID
            if ( current_user_can( 'manage_options' ) && ! empty( $request['vendor_id'] ) ) {
                $ticket->set_vendor_id( $request['vendor_id'] );
            } else {
                $vendor_id = dokan_get_current_user_id();
                $ticket->set_vendor_id( $vendor_id );
            }

            $ticket->set_subject( $request['subject'] );
            $ticket->set_status( $request['status'] ?? Ticket::STATUS_OPEN );
            $ticket->set_priority( $request['priority'] ?? Ticket::PRIORITY_NORMAL );

            $ticket->save();

            // Add initial conversation if message provided
            if ( ! empty( $request['message'] ) ) {
                $conversation_data = [
                    'message'     => $request['message'],
                    'sender_type' => current_user_can( 'manage_options' ) && ! empty( $request['vendor_id'] ) ? 'admin' : 'vendor',
                    'sender_id'   => get_current_user_id(),
                ];
                $ticket->add_conversation( $conversation_data );
            }

            $ticket = new Ticket( $ticket->get_id() ); // Reload ticket to get updated data

            $response = $this->prepare_item_for_response( $ticket, $request );
            $response = rest_ensure_response( $response );
            $response->set_status( 201 );

            return $response;
        } catch ( Exception $e ) {
            return new WP_Error(
                'dokan_rest_cannot_create',
                $e->getMessage(),
                [ 'status' => 400 ]
            );
        }
    }

    /**
     * Get a single ticket.
     *
     * @since 4.1.2
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_item( $request ) {
        try {
            $ticket = new Ticket( $request['id'] );
        } catch ( Exception $e ) {
            return new WP_Error(
                'dokan_rest_ticket_invalid_id',
                __( 'Invalid ticket ID.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        if ( ! $ticket->get_id() ) {
            return new WP_Error(
                'dokan_rest_ticket_invalid_id',
                __( 'Invalid ticket ID.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        // Ownership check moved from permission callback
        if ( ! current_user_can( 'manage_options' ) ) {
            $vendor_id = dokan_get_current_user_id();
            if ( ! dokan_is_user_seller( $vendor_id ) || $ticket->get_vendor_id() !== $vendor_id ) {
                return new WP_Error(
                    'dokan_rest_cannot_view',
                    __( 'Sorry, you are not allowed to view this ticket.', 'dokan' ),
                    [ 'status' => rest_authorization_required_code() ]
                );
            }
        }

        $data = $this->prepare_item_for_response( $ticket, $request );
        return rest_ensure_response( $data );
    }

    /**
     * Update a single ticket.
     *
     * @since 4.1.2
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function update_item( $request ) {
        try {
            $ticket = new Ticket( $request['id'] );

            if ( ! $ticket->get_id() ) {
                return new WP_Error(
                    'dokan_rest_ticket_invalid_id',
                    __( 'Invalid ticket ID.', 'dokan' ),
                    [ 'status' => 404 ]
                );
            }

            // Ownership check moved from permission callback
            if ( ! current_user_can( 'manage_options' ) ) {
                $vendor_id = dokan_get_current_user_id();
                if ( ! dokan_is_user_seller( $vendor_id ) || $ticket->get_vendor_id() !== $vendor_id ) {
                    return new WP_Error(
                        'dokan_rest_cannot_edit',
                        __( 'Sorry, you are not allowed to edit this ticket.', 'dokan' ),
                        [ 'status' => rest_authorization_required_code() ]
                    );
                }
            }

            if ( isset( $request['subject'] ) ) {
                $ticket->set_subject( $request['subject'] );
            }

            if ( isset( $request['status'] ) ) {
                $ticket->set_status( $request['status'] );
            }

            if ( isset( $request['priority'] ) ) {
                $ticket->set_priority( $request['priority'] );
            }

            if ( isset( $request['is_read_by_admin'] ) ) {
                $ticket->set_is_read_by_admin( $request['is_read_by_admin'] );
            }

            if ( isset( $request['is_read_by_vendor'] ) ) {
                $ticket->set_is_read_by_vendor( $request['is_read_by_vendor'] );
            }

            $ticket->save();

            $response = $this->prepare_item_for_response( $ticket, $request );
            return rest_ensure_response( $response );
        } catch ( Exception $e ) {
            return new WP_Error(
                'dokan_rest_cannot_update',
                $e->getMessage(),
                [ 'status' => 400 ]
            );
        }
    }

    /**
     * Delete a single ticket.
     *
     * @since 4.1.2
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function delete_item( $request ) {
        try {
            $ticket = new Ticket( $request['id'] );
        } catch ( Exception $e ) {
            return new WP_Error(
                'dokan_rest_ticket_invalid_id',
                __( 'Invalid ticket ID.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        if ( ! $ticket->get_id() ) {
            return new WP_Error(
                'dokan_rest_ticket_invalid_id',
                __( 'Invalid ticket ID.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        // Ownership check moved from permission callback
        if ( ! current_user_can( 'manage_options' ) ) {
            $vendor_id = dokan_get_current_user_id();
            if ( ! dokan_is_user_seller( $vendor_id ) || $ticket->get_vendor_id() !== $vendor_id ) {
                return new WP_Error(
                    'dokan_rest_cannot_delete',
                    __( 'Sorry, you are not allowed to delete this ticket.', 'dokan' ),
                    [ 'status' => rest_authorization_required_code() ]
                );
            }
        }

        $previous = $this->prepare_item_for_response( $ticket, $request );
        $result   = $ticket->delete();

        if ( ! $result ) {
            return new WP_Error(
                'dokan_rest_cannot_delete',
                __( 'The ticket cannot be deleted.', 'dokan' ),
                [ 'status' => 500 ]
            );
        }

        $response = new WP_REST_Response();
        $response->set_data(
            [
                'deleted'  => true,
                'previous' => $previous->get_data(),
            ]
        );

        return $response;
    }

    /**
     * Batch create, update and delete items.
     *
     * @since 4.1.2
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function batch_items( $request ) {
        // Restrict batch delete by date range to administrators only.
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error(
                'dokan_rest_forbidden',
                __( 'Only administrators can perform batch operation.', 'dokan' ),
                [ 'status' => rest_authorization_required_code() ]
            );
        }

        $items    = array_filter( $request->get_params() );
        $response = [];

        if ( ! empty( $items['create'] ) ) {
            foreach ( $items['create'] as $item ) {
                $create_request = new WP_REST_Request( 'POST' );
                $create_request->set_body_params( $item );
                $create_response = $this->create_item( $create_request );
                $response['create'][] = $create_response;
            }
        }

        if ( ! empty( $items['update'] ) ) {
            foreach ( $items['update'] as $item ) {
                $update_request = new WP_REST_Request( 'PUT' );
                $update_request->set_body_params( $item );
                $update_response = $this->update_item( $update_request );
                $response['update'][] = $update_response;
            }
        }

        if ( ! empty( $items['delete'] ) ) {
            foreach ( $items['delete'] as $id ) {
                $delete_request = new WP_REST_Request( 'DELETE' );
                $delete_request->set_param( 'id', $id );
                $delete_response = $this->delete_item( $delete_request );
                $response['delete'][] = $delete_response;
            }
        }

        if ( ! empty( $items['delete_by_date_range'] ) ) {
            $date_range = $items['delete_by_date_range'];
            $delete_args = [];

            if ( ! empty( $date_range['date_from'] ) ) {
                $delete_args['date_from'] = $date_range['date_from'];
            }

            if ( ! empty( $date_range['date_to'] ) ) {
                $delete_args['date_to'] = $date_range['date_to'];
            }

            // Restrict vendors to deleting only their own tickets
            if ( ! current_user_can( 'manage_options' ) ) {
                $date_range['vendor_id'] = dokan_get_current_user_id();
            }

            if ( ! empty( $date_range['vendor_id'] ) ) {
                $delete_args['vendor_id'] = $date_range['vendor_id'];
                $tickets_to_delete = Ticket::query( $date_range['vendor_id'], $delete_args );
            } else {
                $tickets_to_delete = Ticket::query_all( $delete_args );
            }

            $response['delete_by_date_range'] = [
                'deleted_count' => 0,
                'deleted_ids' => [],
            ];

            foreach ( $tickets_to_delete as $ticket ) {
                $delete_result = $ticket->delete();
                if ( $delete_result ) {
                    ++$response['delete_by_date_range']['deleted_count'];
                    $response['delete_by_date_range']['deleted_ids'][] = $ticket->get_id();
                }
            }
        }

        return rest_ensure_response( $response );
    }

    /**
     * Get counts of tickets by different statuses.
     *
     * @since 4.1.2
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_counts( $request ) {
        $args = [
            'vendor_id' => $request['vendor_id'],
        ];

        // Filter by vendor if not admin
        if ( ! current_user_can( 'manage_options' ) ) {
            $vendor_id = dokan_get_current_user_id();
            if ( ! dokan_is_user_seller( $vendor_id ) ) {
                return new WP_Error(
                    'dokan_rest_cannot_view',
                    __( 'Sorry, you are not allowed to view ticket counts.', 'dokan' ),
                    [ 'status' => rest_authorization_required_code() ]
                );
            }
            $args['vendor_id'] = $vendor_id;
        }

        $counts = [
            'open'    => 0,
            'closed'  => 0,
            'pending' => 0,
            'active'  => 0,
            'unread'  => 0,
        ];

        // Get counts for different statuses
        if ( ! current_user_can( 'manage_options' ) ) {
            $vendor_id = $args['vendor_id'];

            // Count open tickets
            $open_args = array_merge( $args, [ 'status' => Ticket::STATUS_OPEN ] );
            $counts['open'] = Ticket::count( $vendor_id, $open_args );

            // Count closed tickets
            $closed_args = array_merge( $args, [ 'status' => Ticket::STATUS_CLOSED ] );
            $counts['closed'] = Ticket::count( $vendor_id, $closed_args );

            // Get all open tickets to check pending/active status
            $open_tickets = Ticket::query( $vendor_id, $open_args );
            foreach ( $open_tickets as $ticket ) {
                if ( $ticket->is_pending() ) {
                    ++$counts['pending'];
                } else {
                    ++$counts['active'];
                }

                // Check if ticket has unread conversations
                if ( current_user_can( 'manage_options' ) ) {
                    if ( ! $ticket->get_is_read_by_admin() ) {
                        ++$counts['unread'];
                    }
                } elseif ( ! $ticket->get_is_read_by_vendor() ) {
                        ++$counts['unread'];
                }
            }
        } else {
            // Admin can see all tickets
            if ( ! empty( $args['vendor_id'] ) ) {
                $vendor_id = $args['vendor_id'];

                // Count open tickets
                $open_args = array_merge( $args, [ 'status' => Ticket::STATUS_OPEN ] );
                $counts['open'] = Ticket::count( $vendor_id, $open_args );

                // Count closed tickets
                $closed_args = array_merge( $args, [ 'status' => Ticket::STATUS_CLOSED ] );
                $counts['closed'] = Ticket::count( $vendor_id, $closed_args );

                // Get all open tickets to check pending/active status
                $open_tickets = Ticket::query( $vendor_id, $open_args );
                foreach ( $open_tickets as $ticket ) {
                    if ( $ticket->is_pending() ) {
                        ++$counts['pending'];
                    } else {
                        ++$counts['active'];
                    }

                    if ( ! $ticket->get_is_read_by_admin() ) {
                        ++$counts['unread'];
                    }
                }
            } else {
                // Count all tickets
                $open_args = [ 'status' => Ticket::STATUS_OPEN ];
                $counts['open'] = Ticket::count_all( $open_args );

                $closed_args = [ 'status' => Ticket::STATUS_CLOSED ];
                $counts['closed'] = Ticket::count_all( $closed_args );

                // Get all open tickets to check pending/active status
                $open_tickets = Ticket::query_all( $open_args );
                foreach ( $open_tickets as $ticket ) {
                    if ( $ticket->is_pending() ) {
                        ++$counts['pending'];
                    } else {
                        ++$counts['active'];
                    }

                    if ( ! $ticket->get_is_read_by_admin() ) {
                        ++$counts['unread'];
                    }
                }
            }
        }

        return rest_ensure_response( $counts );
    }

    /**
     * Prepare a single ticket output for response.
     *
     * @since 4.1.2
     *
     * @param Ticket          $ticket  Ticket object.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function prepare_item_for_response( $ticket, $request ) {
        $vendor = new Vendor( $ticket->get_vendor_id() );
        $data = [
            'id'                  => $ticket->get_id(),
            'vendor_id'           => $ticket->get_vendor_id(),
            'vendor'              => $vendor->to_array(),
            'subject'             => $ticket->get_subject(),
            'status'              => $ticket->get_status(),
            'is_pending'          => $ticket->is_pending(),
            'priority'            => $ticket->get_priority(),
            'last_reply_by'       => $ticket->get_last_reply_by(),
            'last_reply_at'       => $ticket->get_last_reply_at() ? $ticket->get_last_reply_at()->format( 'c' ) : null,
            'is_read_by_admin'    => $ticket->get_is_read_by_admin(),
            'is_read_by_vendor'   => $ticket->get_is_read_by_vendor(),
            'date_created'        => $ticket->get_date_created() ? $ticket->get_date_created()->format( 'c' ) : null,
            'date_updated'        => $ticket->get_date_updated() ? $ticket->get_date_updated()->format( 'c' ) : null,
            'conversations_count' => $ticket->get_conversations_count( [] ),
            'conversations'       => $this->prepare_conversations_for_response( $ticket ),
            'status_logs'         => $this->prepare_status_logs_for_response( $ticket ),
        ];

        $context = ! empty( $request['context'] ) ? $request['context'] : 'view';
        $data    = $this->add_additional_fields_to_object( $data, $request );
        $data    = $this->filter_response_by_context( $data, $context );

        $response = rest_ensure_response( $data );
        $response->add_links( $this->prepare_links( $ticket, $request ) );

        return apply_filters( 'dokan_rest_prepare_vendor_support_ticket', $response, $ticket, $request );
    }

    /**
     * Prepare conversations for response.
     *
     * @since 4.1.2
     *
     * @param Ticket $ticket Ticket object.
     * @return array Array of formatted conversations.
     */
    protected function prepare_conversations_for_response( $ticket ) {
        $conversations = $ticket->get_conversations( [] );
        $formatted_conversations = [];

        foreach ( $conversations as $conversation ) {
            $formatted_conversations[] = [
                'id'            => $conversation->get_id(),
                'message'       => $conversation->get_message(),
                'sender_type'   => $conversation->get_sender_type(),
                'sender_id'     => $conversation->get_sender_id(),
                'sender_name'   => $conversation->get_sender_name(),
                'sender_email'  => $conversation->get_sender_email(),
                'sender_avatar' => get_avatar_url( $conversation->get_sender_email() ),
                'is_read'       => $conversation->get_is_read(),
                'date_created'  => $conversation->get_date_created() ? $conversation->get_date_created()->format( 'c' ) : null,
            ];
        }

        return $formatted_conversations;
    }

    /**
     * Prepare status logs for response.
     *
     * @since 4.1.2
     *
     * @param Ticket $ticket Ticket object.
     * @return array Array of formatted status logs.
     */
    protected function prepare_status_logs_for_response( $ticket ) {
        $status_logs = $ticket->get_status_logs( [] );
        $formatted_status_logs = [];

        foreach ( $status_logs as $status_log ) {
            $formatted_status_logs[] = [
                'id'               => $status_log->get_id(),
                'old_status'       => $status_log->get_old_status(),
                'new_status'       => $status_log->get_new_status(),
                'changed_by_type'  => $status_log->get_changed_by_type(),
                'changed_by_id'    => $status_log->get_changed_by_id(),
                'changed_by_name'  => $status_log->get_changed_by_name(),
                'changed_by_email' => $status_log->get_changed_by_email(),
                'note'             => $status_log->get_note(),
                'date_created'     => $status_log->get_date_created() ? $status_log->get_date_created()->format( 'c' ) : null,
            ];
        }

        return $formatted_status_logs;
    }

    /**
     * Prepare links for the request.
     *
     * @since 4.1.2
     *
     * @param Ticket          $ticket  Ticket object.
     * @param WP_REST_Request $request Request object.
     * @return array Links for the given ticket.
     */
    protected function prepare_links( $ticket, $request ) {
        $links = [
            'self' => [
                'href' => rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $ticket->get_id() ) ),
            ],
            'collection' => [
                'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
            ],
            'conversations' => [
                'href' => rest_url( sprintf( '%s/%s/%d/conversations', $this->namespace, $this->rest_base, $ticket->get_id() ) ),
            ],
        ];

        return $links;
    }

    /**
     * Check if a given request has access to read tickets.
     *
     * @since 4.1.2
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
     */
    public function get_items_permissions_check( $request ) {
        if ( current_user_can( 'manage_options' ) || current_user_can( 'dokandar' ) ) {
            return true;
        }

        return new WP_Error(
            'dokan_rest_cannot_view',
            __( 'Sorry, you are not allowed to view tickets.', 'dokan' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    /**
     * Check if a given request has access to read a specific ticket.
     *
     * @since 4.1.2
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
     */
    public function get_item_permissions_check( $request ) {
        if ( current_user_can( 'manage_options' ) || current_user_can( 'dokandar' ) ) {
            return true;
        }

        return new WP_Error(
            'dokan_rest_cannot_view',
            __( 'Sorry, you are not allowed to view this ticket.', 'dokan' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    /**
     * Check if a given request has access to create tickets.
     *
     * @since 4.1.2
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has create access, WP_Error object otherwise.
     */
    public function create_item_permissions_check( $request ) {
        if ( current_user_can( 'manage_options' ) || current_user_can( 'dokandar' ) ) {
            return true;
        }

        return new WP_Error(
            'dokan_rest_cannot_create',
            __( 'Sorry, you are not allowed to create tickets.', 'dokan' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    /**
     * Check if a given request has access to update a specific ticket.
     *
     * @since 4.1.2
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has update access, WP_Error object otherwise.
     */
    public function update_item_permissions_check( $request ) {
        if ( current_user_can( 'manage_options' ) || current_user_can( 'dokandar' ) ) {
            return true;
        }

        return new WP_Error(
            'dokan_rest_cannot_edit',
            __( 'Sorry, you are not allowed to edit tickets.', 'dokan' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    /**
     * Check if a given request has access to delete a specific ticket.
     *
     * @since 4.1.2
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has delete access, WP_Error object otherwise.
     */
    public function delete_item_permissions_check( $request ) {
        if ( current_user_can( 'manage_options' ) || current_user_can( 'dokandar' ) ) {
            return true;
        }

        return new WP_Error(
            'dokan_rest_cannot_delete',
            __( 'Sorry, you are not allowed to delete tickets.', 'dokan' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    /**
     * Check if a given request has access to batch manipulate tickets.
     *
     * @since 4.1.2
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has batch access, WP_Error object otherwise.
     */
    public function batch_items_permissions_check( $request ) {
        if ( current_user_can( 'manage_options' ) || current_user_can( 'dokandar' ) ) {
            return true;
        }

        return new WP_Error(
            'dokan_rest_cannot_batch',
            __( 'Sorry, you are not allowed to batch manipulate tickets.', 'dokan' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    /**
     * Get the ticket schema, conforming to JSON Schema.
     *
     * @since 4.1.2
     *
     * @return array Item schema data.
     */
    public function get_item_schema() {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'vendor_support_ticket',
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'description' => __( 'Unique identifier for the ticket.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'vendor_id' => [
                    'description' => __( 'Vendor ID associated with the ticket.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                ],
                'subject' => [
                    'description' => __( 'Ticket subject.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'required'    => true,
                ],
                'status' => [
                    'description' => __( 'Ticket status.', 'dokan' ),
                    'type'        => 'string',
                    'enum'        => array_values( ( new Ticket() )->get_valid_statuses() ),
                    'context'     => [ 'view', 'edit' ],
                ],
                'priority' => [
                    'description' => __( 'Ticket priority.', 'dokan' ),
                    'type'        => 'string',
                    'enum'        => array_values( ( new Ticket() )->get_valid_priorities() ),
                    'context'     => [ 'view', 'edit' ],
                ],
                'last_reply_by' => [
                    'description' => __( 'Last reply sender type.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'last_reply_at' => [
                    'description' => __( 'Last reply date.', 'dokan' ),
                    'type'        => [ 'null', 'string' ],
                    'format'      => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'is_read_by_admin' => [
                    'description' => __( 'Whether the ticket is read by admin.', 'dokan' ),
                    'type'        => 'boolean',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'is_read_by_vendor' => [
                    'description' => __( 'Whether the ticket is read by vendor.', 'dokan' ),
                    'type'        => 'boolean',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'date_created' => [
                    'description' => __( 'The date the ticket was created, in the site\'s timezone.', 'dokan' ),
                    'type'        => [ 'null', 'string' ],
                    'format'      => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'date_updated' => [
                    'description' => __( 'The date the ticket was last updated, in the site\'s timezone.', 'dokan' ),
                    'type'        => [ 'null', 'string' ],
                    'format'      => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'conversations_count' => [
                    'description' => __( 'Number of conversations in the ticket.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'message' => [
                    'description' => __( 'Initial message for the ticket (used only when creating).', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'edit' ],
                ],
            ],
        ];

        return $this->add_additional_fields_schema( $schema );
    }

    /**
     * Get the query params for collections.
     *
     * @since 4.1.2
     *
     * @return array Collection parameters.
     */
    public function get_collection_params() {
        $params = parent::get_collection_params();

        $params['orderby'] = [
            'description'       => __( 'Sort collection by object attribute.', 'dokan' ),
            'type'              => 'string',
            'default'           => 'date_created',
            'enum'              => [
                'id',
                'date_created',
                'date_updated',
                'subject',
                'status',
                'priority',
            ],
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['order'] = [
            'description'       => __( 'Order sort attribute ascending or descending.', 'dokan' ),
            'type'              => 'string',
            'default'           => 'desc',
            'enum'              => [ 'asc', 'desc' ],
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['status'] = [
            'description'       => __( 'Limit result set to tickets with specific status.', 'dokan' ),
            'type'              => 'string',
            'enum'              => array_values( ( new Ticket() )->get_valid_statuses() ),
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['priority'] = [
            'description'       => __( 'Limit result set to tickets with specific priority.', 'dokan' ),
            'type'              => 'string',
            'enum'              => array_values( ( new Ticket() )->get_valid_priorities() ),
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['vendor_id'] = [
            'description'       => __( 'Limit result set to tickets for specific vendor (admin only).', 'dokan' ),
            'type'              => 'integer',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['date_from'] = [
            'description'       => __( 'Limit result set to tickets created after this date.', 'dokan' ),
            'type'              => 'string',
            'format'            => 'date-time',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['date_to'] = [
            'description'       => __( 'Limit result set to tickets created before this date.', 'dokan' ),
            'type'              => 'string',
            'format'            => 'date-time',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['conversation_status'] = [
            'description'       => __( 'Limit result set to tickets with specific conversation status.', 'dokan' ),
            'type'              => 'string',
            'enum'              => [ 'active', 'pending' ],
            'validate_callback' => 'rest_validate_request_arg',
        ];

        return $params;
    }

    /**
     * Get the batch collection params.
     *
     * @since 4.1.2
     *
     * @return array Batch collection parameters.
     */
    public function get_batch_collection_params() {
        $params = [
            'create' => [
                'description' => __( 'List of tickets to create.', 'dokan' ),
                'type'        => 'array',
                'items'       => [
                    'type' => 'object',
                ],
            ],
            'update' => [
                'description' => __( 'List of tickets to update.', 'dokan' ),
                'type'        => 'array',
                'items'       => [
                    'type' => 'object',
                ],
            ],
            'delete' => [
                'description' => __( 'List of ticket IDs to delete.', 'dokan' ),
                'type'        => 'array',
                'items'       => [
                    'type' => 'integer',
                ],
            ],
            'delete_by_date_range' => [
                'description' => __( 'Delete tickets by date range.', 'dokan' ),
                'type'        => 'object',
                'properties'  => [
                    'date_from' => [
                        'description' => __( 'Delete tickets created after this date.', 'dokan' ),
                        'type'        => 'string',
                        'format'      => 'date-time',
                    ],
                    'date_to' => [
                        'description' => __( 'Delete tickets created before this date.', 'dokan' ),
                        'type'        => 'string',
                        'format'      => 'date-time',
                    ],
                    'vendor_id' => [
                        'description' => __( 'Limit deletion to specific vendor (optional).', 'dokan' ),
                        'type'        => 'integer',
                    ],
                ],
            ],
        ];

        return $params;
    }

    /**
     * Get the counts collection params.
     *
     * @since 4.1.2
     *
     * @return array Counts collection parameters.
     */
    public function get_counts_collection_params() {
        $params = [
            'vendor_id' => [
                'description'       => __( 'Limit result set to tickets for specific vendor (admin only).', 'dokan' ),
                'type'              => 'integer',
                'validate_callback' => 'rest_validate_request_arg',
            ],
        ];

        return $params;
    }
}
