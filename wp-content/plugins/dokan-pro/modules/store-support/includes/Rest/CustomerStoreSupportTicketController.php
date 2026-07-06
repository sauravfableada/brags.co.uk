<?php

namespace WeDevs\DokanPro\Modules\StoreSupport\Rest;

use WP_Error;
use WP_Query;
use WP_REST_Server;

class CustomerStoreSupportTicketController extends VendorStoreSupportTicketController {
    /*
     * Endpoint namespace.
     */
    protected $rest_base = 'customer/support-tickets';

    public function register_routes() {
        parent::register_routes();
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'create_item' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                    'args'                => [
                        'store_id' => [
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ],
                        'subject'     => [
                            'required'          => true,
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                        'message' => [
                            'required'          => true,
                            'type'              => 'string',
                            'sanitize_callback' => 'wp_kses_post',
                        ],
                        'order_id' => [
                            'required'          => false,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Get Current user id
     *
     * @return int
     */
    public function get_current_user_id(): int {
        return get_current_user_id();
    }

    protected function get_tickets_query( $args, $user_id, $request ): array {
        return [
            dokan_pro()->module->store_support->get_topics_by_customer( $user_id, $args ),
            dokan_pro()->module->store_support->topic_count( $user_id ),
        ];
    }

    /*
     * Create a new support ticket
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function create_item( $request ) {
        $data = $request->get_params();
        $store_id = $request->get_param( 'store_id' );
        $order_id = isset( $data['order_id'] ) ? absint( $data['order_id'] ) : 0;
        $subject = sanitize_text_field( $data['subject'] );
        $message = wp_kses_post( $data['message'] );
        $ticket = [
            'post_title'     => $subject,
            'post_content'   => $message,
            'post_status'    => 'open',
            'post_author'    => $this->get_current_user_id(),
            'post_type'      => 'dokan_store_support',
            'comment_status' => 'open',
        ];
        // insert the ticket
        $post_id = wp_insert_post( apply_filters( 'dss_new_ticket_insert_args', $ticket ) );
        if ( is_wp_error( $post_id ) ) {
            return new WP_Error( 'dss_ticket_creation_failed', __( 'Failed to create support ticket.', 'dokan' ), [ 'status' => 500 ] );
        }
        // set ticket meta
        update_post_meta( $post_id, 'store_id', $store_id );
        update_post_meta( $post_id, 'order_id', $order_id );

        do_action( 'dokan_new_ticket_created_notify', $store_id, $post_id );
        do_action( 'dss_new_ticket_created', $post_id, $store_id );

        return rest_ensure_response(
            [
				'code'    => 'dss_ticket_created',
				'message' => __( 'Support ticket created successfully.', 'dokan' ),
				'data'    => [
					'ticket_id' => $post_id,
					'store_id'  => $store_id,
					'order_id'  => $order_id,
					'subject'   => $subject,
					'message'   => $message,
					'status'    => 'open',
				],
			]
        );
    }

    /**
     * Get query single topic for user
     *
     * @return WP_Query
     */
    protected function get_single_topic_query( $topic_id, $user_id ) {
        return dokan_pro()->module->store_support->get_single_topic_by_customer( $topic_id, $user_id );
	}

    /*
     * Check if a given request has access to read support tickets
     *
     * @param WP_REST_Request $request
     *
     * @return bool|WP_Error
     */
    public function get_items_permissions_check( $request ): bool {
        return current_user_can( 'read' );
    }
}
