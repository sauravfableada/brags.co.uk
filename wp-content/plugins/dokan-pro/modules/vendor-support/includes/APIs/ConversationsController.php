<?php

namespace WeDevs\DokanPro\Modules\VendorSupport\APIs;

use Exception;
use WeDevs\Dokan\REST\DokanBaseController;
use WeDevs\DokanPro\Modules\VendorSupport\Models\Conversation;
use WeDevs\DokanPro\Modules\VendorSupport\Models\Ticket;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Class ConversationsController
 *
 * REST API controller for vendor support conversations.
 *
 * @since 4.1.2
 *
 * @package WeDevs\DokanPro\Modules\VendorSupport\APIs
 */
class ConversationsController extends DokanBaseController {

    /**
     * Route base.
     *
     * @since 4.1.2
     *
     * @var string
     */
    protected $rest_base = 'vendor-support';

    /**
     * Register the routes for conversations.
     *
     * @since 4.1.2
     *
     * @return void
     */
    public function register_routes() {
        // Conversations for a specific ticket
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/tickets/(?P<ticket_id>[\d]+)/conversations',
            [
                'args' => [
                    'ticket_id' => [
                        'description' => __( 'Unique identifier for the ticket.', 'dokan' ),
                        'type'        => 'integer',
                    ],
                ],
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

        // Individual conversation operations
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/conversations/(?P<id>[\d]+)',
            [
                'args' => [
                    'id' => [
                        'description' => __( 'Unique identifier for the conversation.', 'dokan' ),
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

        // Mark conversation as read
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/conversations/(?P<id>[\d]+)/read',
            [
                'args' => [
                    'id' => [
                        'description' => __( 'Unique identifier for the conversation.', 'dokan' ),
                        'type'        => 'integer',
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'mark_as_read' ],
                    'permission_callback' => [ $this, 'update_item_permissions_check' ],
                ],
                'schema' => [ $this, 'get_item_schema' ],
            ]
        );
    }

    /**
     * Get a collection of conversations for a ticket.
     *
     * @since 4.1.2
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_items( $request ) {
        try {
            $ticket = new Ticket( $request['ticket_id'] );
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
                    __( 'Sorry, you are not allowed to view conversations for this ticket.', 'dokan' ),
                    [ 'status' => rest_authorization_required_code() ]
                );
            }
        }

        $args = [
            'limit'   => $request['per_page'],
            'offset'  => ( $request['page'] - 1 ) * $request['per_page'],
            'orderby' => $request['orderby'],
            'order'   => $request['order'],
        ];

        $conversations = $ticket->get_conversations( $args );
        $total         = $ticket->get_conversations_count( [] );

        $data = [];
        foreach ( $conversations as $conversation ) {
            $conversation_data = $this->prepare_item_for_response( $conversation, $request );
            $data[]            = $this->prepare_response_for_collection( $conversation_data );
        }

        $response = rest_ensure_response( $data );
        $response->header( 'X-WP-Total', $total );
        $response->header( 'X-WP-TotalPages', ceil( $total / $request['per_page'] ) );

        return $response;
    }

    /**
     * Create a single conversation.
     *
     * @since 4.1.2
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function create_item( $request ) {
        try {
            $ticket = new Ticket( $request['ticket_id'] );

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
                        'dokan_rest_cannot_create',
                        __( 'Sorry, you are not allowed to create conversations for this ticket.', 'dokan' ),
                        [ 'status' => rest_authorization_required_code() ]
                    );
                }
            }

            $conversation_data = [
                'message'     => $request['message'],
                'sender_type' => current_user_can( 'manage_options' ) ? Conversation::SENDER_ADMIN : Conversation::SENDER_VENDOR,
                'sender_id'   => get_current_user_id(),
            ];

            $conversation = $ticket->add_conversation( $conversation_data );

            if ( is_wp_error( $conversation ) ) {
                return $conversation;
            }

            $response = $this->prepare_item_for_response( $conversation, $request );
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
     * Get a single conversation.
     *
     * @since 4.1.2
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_item( $request ) {
        try {
            $conversation = new Conversation( $request['id'] );
        } catch ( Exception $e ) {
            return new WP_Error(
                'dokan_rest_conversation_invalid_id',
                __( 'Invalid conversation ID.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        if ( ! $conversation->get_id() ) {
            return new WP_Error(
                'dokan_rest_conversation_invalid_id',
                __( 'Invalid conversation ID.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        // Ownership check moved from permission callback
        $ticket = new Ticket( $conversation->get_ticket_id() );
        if ( ! $ticket->get_id() ) {
            return new WP_Error(
                'dokan_rest_ticket_invalid_id',
                __( 'Invalid ticket ID.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            $vendor_id = dokan_get_current_user_id();
            if ( ! dokan_is_user_seller( $vendor_id ) || $ticket->get_vendor_id() !== $vendor_id ) {
                return new WP_Error(
                    'dokan_rest_cannot_view',
                    __( 'Sorry, you are not allowed to view this conversation.', 'dokan' ),
                    [ 'status' => rest_authorization_required_code() ]
                );
            }
        }

        $data = $this->prepare_item_for_response( $conversation, $request );
        return rest_ensure_response( $data );
    }

    /**
     * Update a single conversation.
     *
     * @since 4.1.2
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function update_item( $request ) {
        try {
            $conversation = new Conversation( $request['id'] );

            if ( ! $conversation->get_id() ) {
                return new WP_Error(
                    'dokan_rest_conversation_invalid_id',
                    __( 'Invalid conversation ID.', 'dokan' ),
                    [ 'status' => 404 ]
                );
            }

            // Ownership and action checks moved from permission callback
            $ticket = new Ticket( $conversation->get_ticket_id() );
            if ( ! $ticket->get_id() ) {
                return new WP_Error(
                    'dokan_rest_ticket_invalid_id',
                    __( 'Invalid ticket ID.', 'dokan' ),
                    [ 'status' => 404 ]
                );
            }

            if ( ! current_user_can( 'manage_options' ) ) {
                $vendor_id = dokan_get_current_user_id();
                if ( ! dokan_is_user_seller( $vendor_id ) || $ticket->get_vendor_id() !== $vendor_id ) {
                    return new WP_Error(
                        'dokan_rest_cannot_edit',
                        __( 'Sorry, you are not allowed to edit this conversation.', 'dokan' ),
                        [ 'status' => rest_authorization_required_code() ]
                    );
                }

                // Allow vendors to mark as read without being the sender
                if ( isset( $request['is_read'] ) && ! isset( $request['message'] ) ) {
                    // pass
                } else {
                    // For editing message, vendors can only edit their own conversation
                    if ( $conversation->get_sender_id() !== $vendor_id ) {
                        return new WP_Error(
                            'dokan_rest_cannot_edit',
                            __( 'Sorry, you are not allowed to edit this conversation.', 'dokan' ),
                            [ 'status' => rest_authorization_required_code() ]
                        );
                    }
                }
            }

            if ( isset( $request['message'] ) ) {
                $conversation->set_message( $request['message'] );
            }

            if ( isset( $request['is_read'] ) ) {
                $conversation->set_is_read( $request['is_read'] );
            }

            $conversation->save();

            $response = $this->prepare_item_for_response( $conversation, $request );
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
     * Delete a single conversation.
     *
     * @since 4.1.2
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function delete_item( $request ) {
        try {
            $conversation = new Conversation( $request['id'] );
        } catch ( Exception $e ) {
            return new WP_Error(
                'dokan_rest_conversation_invalid_id',
                __( 'Invalid conversation ID.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        if ( ! $conversation->get_id() ) {
            return new WP_Error(
                'dokan_rest_conversation_invalid_id',
                __( 'Invalid conversation ID.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        // Ownership check moved from permission callback
        $ticket = new Ticket( $conversation->get_ticket_id() );
        if ( ! $ticket->get_id() ) {
            return new WP_Error(
                'dokan_rest_ticket_invalid_id',
                __( 'Invalid ticket ID.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            $vendor_id = dokan_get_current_user_id();
            if ( ! dokan_is_user_seller( $vendor_id ) || $ticket->get_vendor_id() !== $vendor_id || $conversation->get_sender_id() !== $vendor_id ) {
                return new WP_Error(
                    'dokan_rest_cannot_delete',
                    __( 'Sorry, you are not allowed to delete this conversation.', 'dokan' ),
                    [ 'status' => rest_authorization_required_code() ]
                );
            }
        }

        $previous = $this->prepare_item_for_response( $conversation, $request );
        $result   = $conversation->delete();

        if ( ! $result ) {
            return new WP_Error(
                'dokan_rest_cannot_delete',
                __( 'The conversation cannot be deleted.', 'dokan' ),
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
     * Mark a conversation as read.
     *
     * @since 4.1.2
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function mark_as_read( $request ) {
        try {
            $conversation = new Conversation( $request['id'] );

            if ( ! $conversation->get_id() ) {
                return new WP_Error(
                    'dokan_rest_conversation_invalid_id',
                    __( 'Invalid conversation ID.', 'dokan' ),
                    [ 'status' => 404 ]
                );
            }

            // Ownership check moved from permission callback
            $ticket = new Ticket( $conversation->get_ticket_id() );
            if ( ! $ticket->get_id() ) {
                return new WP_Error(
                    'dokan_rest_ticket_invalid_id',
                    __( 'Invalid ticket ID.', 'dokan' ),
                    [ 'status' => 404 ]
                );
            }

            if ( ! current_user_can( 'manage_options' ) ) {
                $vendor_id = dokan_get_current_user_id();
                if ( ! dokan_is_user_seller( $vendor_id ) || $ticket->get_vendor_id() !== $vendor_id ) {
                    return new WP_Error(
                        'dokan_rest_cannot_edit',
                        __( 'Sorry, you are not allowed to update this conversation.', 'dokan' ),
                        [ 'status' => rest_authorization_required_code() ]
                    );
                }
            }

            $conversation->set_is_read( true );
            $conversation->save();

            $response = $this->prepare_item_for_response( $conversation, $request );
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
     * Prepare a single conversation output for response.
     *
     * @since 4.1.2
     *
     * @param Conversation    $conversation Conversation object.
     * @param WP_REST_Request $request      Request object.
     * @return WP_REST_Response Response object.
     */
    public function prepare_item_for_response( $conversation, $request ) {
        $data = [
            'id'           => $conversation->get_id(),
            'ticket_id'    => $conversation->get_ticket_id(),
            'message'      => $conversation->get_message(),
            'sender_type'  => $conversation->get_sender_type(),
            'sender_id'    => $conversation->get_sender_id(),
            'is_read'      => $conversation->get_is_read(),
            'date_created' => $conversation->get_date_created() ? $conversation->get_date_created()->format( 'c' ) : null,
        ];

        // Add sender information
        $sender = get_user_by( 'id', $conversation->get_sender_id() );
        if ( $sender ) {
            $data['sender'] = [
                'id'           => $sender->ID,
                'name'         => $sender->display_name,
                'email'        => $sender->user_email,
                'avatar_url'   => get_avatar_url( $sender->ID ),
            ];
        }

        $context = ! empty( $request['context'] ) ? $request['context'] : 'view';
        $data    = $this->add_additional_fields_to_object( $data, $request );
        $data    = $this->filter_response_by_context( $data, $context );

        $response = rest_ensure_response( $data );
        $response->add_links( $this->prepare_links( $conversation, $request ) );

        return apply_filters( 'dokan_rest_prepare_vendor_support_conversation', $response, $conversation, $request );
    }

    /**
     * Prepare links for the request.
     *
     * @since 4.1.2
     *
     * @param Conversation    $conversation Conversation object.
     * @param WP_REST_Request $request      Request object.
     * @return array Links for the given conversation.
     */
    protected function prepare_links( $conversation, $request ) {
        $links = [
            'self' => [
                'href' => rest_url( sprintf( '%s/%s/conversations/%d', $this->namespace, $this->rest_base, $conversation->get_id() ) ),
            ],
            'collection' => [
                'href' => rest_url( sprintf( '%s/%s/tickets/%d/conversations', $this->namespace, $this->rest_base, $conversation->get_ticket_id() ) ),
            ],
            'ticket' => [
                'href' => rest_url( sprintf( '%s/%s/tickets/%d', $this->namespace, $this->rest_base, $conversation->get_ticket_id() ) ),
            ],
        ];

        return $links;
    }

    /**
     * Check if a given request has access to read conversations.
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
            __( 'Sorry, you are not allowed to view conversations.', 'dokan' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    /**
     * Check if a given request has access to read a specific conversation.
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
            __( 'Sorry, you are not allowed to view this conversation.', 'dokan' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    /**
     * Check if a given request has access to create conversations.
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
            __( 'Sorry, you are not allowed to create conversations.', 'dokan' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    /**
     * Check if a given request has access to update a specific conversation.
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
            __( 'Sorry, you are not allowed to edit conversations.', 'dokan' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    /**
     * Check if a given request has access to delete a specific conversation.
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
            __( 'Sorry, you are not allowed to delete conversations.', 'dokan' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    /**
     * Get the conversation schema, conforming to JSON Schema.
     *
     * @since 4.1.2
     *
     * @return array Item schema data.
     */
    public function get_item_schema() {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'vendor_support_conversation',
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'description' => __( 'Unique identifier for the conversation.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'ticket_id' => [
                    'description' => __( 'Ticket ID associated with the conversation.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'message' => [
                    'description' => __( 'Conversation message content.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'required'    => true,
                ],
                'sender_type' => [
                    'description' => __( 'Type of sender (admin or vendor).', 'dokan' ),
                    'type'        => 'string',
                    'enum'        => [ Conversation::SENDER_ADMIN, Conversation::SENDER_VENDOR ],
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'sender_id' => [
                    'description' => __( 'ID of the user who sent the message.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'sender' => [
                    'description' => __( 'Sender information.', 'dokan' ),
                    'type'        => 'object',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'properties'  => [
                        'id' => [
                            'description' => __( 'Sender user ID.', 'dokan' ),
                            'type'        => 'integer',
                        ],
                        'name' => [
                            'description' => __( 'Sender display name.', 'dokan' ),
                            'type'        => 'string',
                        ],
                        'email' => [
                            'description' => __( 'Sender email address.', 'dokan' ),
                            'type'        => 'string',
                        ],
                        'avatar_url' => [
                            'description' => __( 'Sender avatar URL.', 'dokan' ),
                            'type'        => 'string',
                        ],
                    ],
                ],
                'is_read' => [
                    'description' => __( 'Whether the conversation is read.', 'dokan' ),
                    'type'        => 'boolean',
                    'context'     => [ 'view', 'edit' ],
                ],
                'date_created' => [
                    'description' => __( 'The date the conversation was created, in the site\'s timezone.', 'dokan' ),
                    'type'        => [ 'null', 'string' ],
                    'format'      => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
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
                'sender_type',
            ],
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['order'] = [
            'description'       => __( 'Order sort attribute ascending or descending.', 'dokan' ),
            'type'              => 'string',
            'default'           => 'asc',
            'enum'              => [ 'asc', 'desc' ],
            'validate_callback' => 'rest_validate_request_arg',
        ];

        return $params;
    }
}
