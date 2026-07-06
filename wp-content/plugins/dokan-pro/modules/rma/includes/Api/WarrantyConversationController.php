<?php

namespace WeDevs\DokanPro\Modules\RMA\Api;

use WeDevs\Dokan\REST\DokanBaseController;
use WeDevs\DokanPro\Modules\RMA\Traits\RMAApiControllerTrait;
use WeDevs\DokanPro\Modules\RMA\WarrantyConversation;
use WeDevs\DokanPro\Modules\RMA\WarrantyRequest;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * WarrantyConversationController class.
 *
 * REST controller for handling conversations in RMA warranty requests.
 *
 * @since 4.0.0
 */
class WarrantyConversationController extends DokanBaseController {
    use RMAApiControllerTrait;

    /**
     * Route name
     *
     * @var string
     */
    protected string $base = '/rma/warranty-requests/(?P<request_id>[\d]+)/conversations';

    /**
     * The warranty request object
     *
     * @var WarrantyRequest
     */
    protected WarrantyRequest $warranty_request;

    /**
     * The conversation query object
     *
     * @var WarrantyConversation
     */
    protected WarrantyConversation $conversation;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->warranty_request = new WarrantyRequest();
        $this->conversation     = new WarrantyConversation();
    }

    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            $this->base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_items' ],
                    'permission_callback' => 'is_user_logged_in',
                    'args'                => $this->get_collection_params(),
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'create_item' ],
                    'permission_callback' => 'is_user_logged_in',
                    'args'                => array(
                        'request_id' => [
                            'description' => esc_html__( 'Warranty request ID', 'dokan' ),
                            'type'        => 'integer',
                            'required'    => true,
                        ],
                        'message' => [
                            'description' => esc_html__( 'Conversation message', 'dokan' ),
                            'type'        => 'string',
                            'required'    => true,
                        ],
                    ),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
    }

    /**
     * Get a collection of conversations
     *
     * @param WP_REST_Request $request Full data about the request
     *
     * @return WP_Error|object Response object on success, or WP_Error object on failure
     */
    public function get_items( $request ) {
        $request_id = $request['request_id'];

        $warranty_request = $this->validate_warranty_request( $request_id );
        if ( is_wp_error( $warranty_request ) ) {
            return $warranty_request;
        }

        $conversations = $this->conversation->get( [ 'request_id' => $request_id ] );
        if ( is_wp_error( $conversations ) ) {
            return $conversations;
        }

        $data_objects = array();
        $total_items = 0;

        if ( ! empty( $conversations ) ) {
            foreach ( $conversations as $conversation ) {
                $data           = $this->prepare_data_for_response( $conversation, $request );
                $data_objects[] = $this->prepare_response_for_collection( $data );
            }

            $total_items = count( $conversations );
        }

        $response = rest_ensure_response( $data_objects );
        return $this->format_collection_response( $response, $request, $total_items );
    }

    /**
     * Create a conversation
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function create_item( $request ) {
        $request_id = $request['request_id'];

        $warranty_request = $this->validate_warranty_request( $request_id );
        if ( is_wp_error( $warranty_request ) ) {
            return $warranty_request;
        }

        $current_user = dokan_get_current_user_id();
        $vendor_id    = (int) $warranty_request['vendor']['store_id'];
        $customer_id  = (int) $warranty_request['customer']['id'];
        $message_to   = dokan_is_user_seller( $current_user ) ? $customer_id : $vendor_id;

        // Prepare conversation data
        $conversation_data = [
            'request_id' => $request_id,
            'from'       => $current_user,
            'to'         => $message_to,
            'message'    => sanitize_textarea_field( $request['message'] ),
            'created_at' => current_time( 'mysql' ),
        ];

        // Create conversation
        $conversation_id = $this->conversation->insert( $conversation_data );
        if ( is_wp_error( $conversation_id ) ) {
            return $conversation_id;
        }

        // Get created conversation
        $new_conversation = $this->conversation->get_single( $conversation_id );
        if ( is_wp_error( $new_conversation ) ) {
            return $new_conversation;
        }

        $response = rest_ensure_response( $new_conversation );
        $response->set_status( 201 );

        return $response;
    }

    /**
     * Prepare a single order output for response.
     *
     * @since  4.0.0
     *
     * @param array           $conversation
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response
     */
    public function prepare_data_for_response( array $conversation, WP_REST_Request $request ): WP_REST_Response {
        $this->request = $request;
        $response = rest_ensure_response( $conversation );
        $response->add_links( $this->prepare_links( $conversation, $request ) );

        /**
         * Filter the response object for the conversation.
         *
         * @since 4.0.0
         *
         * @param WP_REST_Response $response    The response object.
         * @param array           $conversation The conversation data.
         * @param WP_REST_Request $request      Request object.
         */
        return apply_filters( 'dokan_rest_prepare_warranty_conversation_object', $response, $conversation, $request );
    }

    /**
     * Prepare links for the request.
     *
     * @since  4.0.0
     *
     * @param array           $conversation  Object data.
     * @param WP_REST_Request $request       Request object.
     *
     * @return array                   Links for the given post.
     */
    protected function prepare_links( array $conversation, WP_REST_Request $request ): array {
        return array(
            'self' => array(
                'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $conversation['id'] ) ),
            ),
            'collection' => array(
                'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
            ),
        );
    }

    /**
     * Get the conversation schema, conforming to JSON Schema
     *
     * @return array
     */
    public function get_item_schema(): array {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'warranty_request_conversation',
            'type'       => 'object',
            'properties' => [
                'id'         => [
                    'description' => esc_html__( 'Unique identifier for the conversation.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'request_id' => [
                    'description' => esc_html__( 'ID of the warranty request this conversation belongs to.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'required'    => true,
                ],
                'from'       => [
                    'description' => esc_html__( 'ID of the user who sent the message.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'to'         => [
                    'description' => esc_html__( 'ID of the user who received the message.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'message'    => [
                    'description' => esc_html__( 'Content of the conversation message.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'required'    => true,
                ],
                'created_at' => [
                    'description' => esc_html__( "The date the message was created, in the site's timezone.", 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
            ],
        ];

        return $this->add_additional_fields_schema( $schema );
    }

    /**
     * Get the query params for collections.
     *
     * @return array
     */
    public function get_collection_params(): array {
        $params = parent::get_collection_params();

        // Remove parameters not needed for conversations
        unset( $params['search'], $params['page'], $params['per_page'], $params['order'], $params['orderby'] );

        $params['context']['default'] = 'view';
        $params['from']                = [
            'description' => esc_html__( 'ID of the user who sent the message.', 'dokan' ),
            'type'        => 'integer',
            'required'    => false,
        ];

        return $params;
    }
}
