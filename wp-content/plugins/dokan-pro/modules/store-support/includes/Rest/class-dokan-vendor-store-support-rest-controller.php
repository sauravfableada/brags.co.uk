<?php

namespace WeDevs\DokanPro\Modules\StoreSupport\Rest;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WeDevs\Dokan\REST\DokanBaseVendorController;
use WeDevs\DokanPro\Modules\StoreSupport\Module;

/**
 * Vendor Store Support REST Controller
 *
 * Handles REST API endpoints for vendors to manage their store support tickets.
 * Extends DokanBaseVendorController for proper vendor authorization and base functionality.
 *
 * @since   4.1.1
 *
 * @package WeDevs\DokanPro\Modules\StoreSupport\Rest
 */
class VendorStoreSupportTicketController extends DokanBaseVendorController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'vendor/support-tickets';

	/**
	 * Store Support Module instance
	 *
	 * @var Module
	 */
	protected $store_support;

	/**
	 * Constructor
	 *
	 * @since 4.1.1
	 */
	public function __construct() {
		$this->store_support = dokan_pro()->module->store_support;
	}

	/**
	 * Register the routes for vendor store support.
	 *
	 * @since 4.1.1
	 *
	 * @return void
	 */
	public function register_routes() {
		// Get all support tickets for current vendor
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_support_tickets' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => $this->get_collection_params(),
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		// Get single support ticket with replies
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			[
				'args' => [
					'id' => [
						'description' => __( 'Unique identifier for the support ticket.', 'dokan' ),
						'type'        => 'integer',
						'required'    => true,
					],
				],
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_support_ticket' ],
					'permission_callback' => [ $this, 'get_item_permissions_check' ],
				],
			]
		);

		// Update support ticket status
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/status',
			[
				'args' => [
					'id' => [
						'description' => __( 'Unique identifier for the support ticket.', 'dokan' ),
						'type'        => 'integer',
						'required'    => true,
					],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_status' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => [
						'status' => [
							'description' => __( 'Support ticket status (open or closed).', 'dokan' ),
							'type'        => 'string',
							'enum'        => [ 'open', 'closed' ],
							'required'    => true,
						],
					],
				],
			]
		);

		// Add reply to support ticket
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/replies',
			[
				'args' => [
					'id' => [
						'description' => __( 'Unique identifier for the support ticket.', 'dokan' ),
						'type'        => 'integer',
						'required'    => true,
					],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_reply' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'message'      => [
							'description'       => __( 'Reply message content.', 'dokan' ),
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'wp_kses_post',
						],
						'close_ticket' => [
							'description' => __( 'Whether to close the ticket after replying.', 'dokan' ),
							'type'        => 'boolean',
							'default'     => false,
						],
					],
				],
			]
		);

		// Get support ticket statistics
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/stats',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_stats' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			]
		);
	}

    protected function get_tickets_query( $args, $user_id, $request ): array {
        return [
            dokan_pro()->module->store_support->get_support_topics( $args ),
            dokan_pro()->module->store_support->topic_count( $user_id ),
        ];
    }

	/**
	 * Get collection of support tickets
	 *
	 * @since 4.1.1
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return object Response object on success, WP_Error object on failure.
	 */
	public function get_support_tickets( WP_REST_Request $request ) {
		$vendor_id = $this->get_current_user_id();

		// Prepare query arguments based on template logic from support.php
		$args = [
			'vendor_id'         => $vendor_id,
            'customer_id'       => $request['customer_id'] ?? 0,
            'ticket_keyword'    => $request['search'] ?? '',
            'ticket_start_date' => $request['start_date'] ?? '',
            'ticket_end_date'   => $request['end_date'] ?? '',
            'ticket_status'     => $request['status'] ?? 'all',
            'pagenum'           => $request['page'] ?? 1,
            'posts_per_page'    => $request['per_page'] ?? 20,
		];

		// Get support topics using the module method
		[$query, $counts] = $this->get_tickets_query( $args, $vendor_id, $request );
		$items = [];

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

                $data    = $this->prepare_item_for_response( get_post(), $request );
				$items[] = $this->prepare_response_for_collection( $data );
			}
			wp_reset_postdata();
		}

		$response = rest_ensure_response( $items );

		// Add pagination headers
		$total_items = $query->found_posts;
        $page        = (string) $args['pagenum'];

        $response->header( 'X-WP-CurrentPage', $page );// Add status counts to headers (like in template)

        $counts = dokan_pro()->module->store_support->topic_count( $vendor_id );
        if ( is_array( $counts ) ) {
            $count_data = wp_list_pluck( $counts, 'count', 'post_status' );
            $response->header( 'X-Status-Open', (string) ( $count_data['open'] ?? 0 ) );
            $response->header( 'X-Status-Closed', (string) ( $count_data['closed'] ?? 0 ) );
        }

		return $this->format_collection_response( $response, $request, $total_items );
	}

    /**
     * Get query single topic for user
     *
     * @return \WP_Query
     */
    protected function get_single_topic_query( $topic_id, $user_id ) {
        return dokan_pro()->module->store_support->get_single_topic( $topic_id, $user_id );
    }

	/**
	 * Get one support ticket with replies
	 *
	 * @since 4.1.1
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, WP_Error object on failure.
	 */
    public function get_support_ticket( WP_REST_Request $request ) {
		$topic_id  = (int) $request['id'];
		$vendor_id = $this->get_current_user_id();

		// Verify vendor owns this ticket using module method
		$query = $this->get_single_topic_query( $topic_id, $vendor_id );

		if ( ! $query->have_posts() ) {
			return new WP_Error(
				'dokan_rest_ticket_not_found',
				__( 'Support ticket not found or access denied.', 'dokan' ),
				[ 'status' => 404 ]
			);
		}

		$query->the_post();
		$ticket = get_post();
		wp_reset_postdata();

		// Get comments/replies (following the template pattern)
		$comments = get_comments(
			[
				'post_id' => $topic_id,
				'status'  => 'approve',
				'orderby' => 'comment_date',
				'order'   => 'ASC',
			]
		);

		$data                  = $this->prepare_item_for_response( $ticket, $request );
		$data->data['replies'] = $this->prepare_replies_for_response( $comments, $vendor_id );

		// Get order reference if exists (from template logic)
		$order_id = get_post_meta( $topic_id, 'order_id', true );
		if ( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$data->data['order'] = [
					'id'     => $order_id,
					'number' => $order->get_order_number(),
					'url'    => wp_nonce_url(
						add_query_arg( [ 'order_id' => $order_id ], dokan_get_navigation_url( 'orders' ) ),
						'dokan_view_order'
					),
					'status' => $order->get_status(),
                    'total' => $order->get_total(),
				];
			}
		}

		return $data;
	}

	/**
	 * Update support ticket status
	 *
	 * @since 4.1.1
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, WP_Error object on failure.
	 */
    public function update_status( WP_REST_Request $request ) {
		$topic_id   = (int) $request['id'];
		$vendor_id  = $this->get_current_user_id();
		$new_status = sanitize_text_field( $request['status'] );

		// Verify vendor owns this ticket
		$query = dokan_pro()->module->store_support->get_single_topic( $topic_id, $vendor_id );

		if ( ! $query->have_posts() ) {
			return new WP_Error(
				'dokan_rest_ticket_not_found',
				__( 'Support ticket not found or access denied.', 'dokan' ),
				[ 'status' => 404 ]
			);
		}

		// Update post status (following module pattern)
		$result = wp_update_post(
			[
				'ID'          => $topic_id,
				'post_status' => $new_status,
			]
		);

		if ( is_wp_error( $result ) ) {
			return new WP_Error(
				'dokan_rest_ticket_update_failed',
				__( 'Failed to update support ticket status.', 'dokan' ),
				[ 'status' => 500 ]
			);
		}

		/**
		 * Fires after a support ticket status is changed via REST API.
		 * This hook is used in the module for notifications.
		 *
		 * @since 4.1.1
		 *
		 * @param int    $topic_id   Support ticket ID.
		 * @param string $new_status New status.
		 */
		do_action( 'dokan_support_topic_status_changed', $topic_id, $new_status );

		return rest_ensure_response(
			[
				'id'      => $topic_id,
				'status'  => $new_status,
				'message' => sprintf(
				/* translators: %s: ticket status */
					__( 'Support ticket status updated to %s.', 'dokan' ),
					$new_status
				),
			]
		);
	}

	/**
	 * Create a reply to support ticket
	 *
	 * @since 4.1.1
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, WP_Error object on failure.
	 */
    public function create_reply( WP_REST_Request $request ) {
		$topic_id     = (int) $request['id'];
		$vendor_id    = $this->get_current_user_id();
		$message      = wp_kses_post( $request['message'] );
		$close_ticket = rest_sanitize_boolean( $request['close_ticket'] );

		// Verify vendor owns this ticket
		$query = $this->get_single_topic_query( $topic_id, $vendor_id );

		if ( ! $query->have_posts() ) {
			return new WP_Error(
				'dokan_rest_ticket_not_found',
				__( 'Support ticket not found or access denied.', 'dokan' ),
				[ 'status' => 404 ]
			);
		}

		$current_user = wp_get_current_user();
		$current_post = get_post( $topic_id );

		// Create comment using module pattern (from change_topic_status_on_comment method)
		$comment_data = [
			'comment_post_ID'      => $topic_id,
			'comment_author'       => $current_user->display_name,
			'comment_author_email' => $current_user->user_email,
			'comment_author_url'   => dokan_get_store_url( $vendor_id ),
			'comment_author_IP'    => dokan_get_client_ip(),
			'comment_date'         => current_time( 'mysql' ),
			'comment_date_gmt'     => get_gmt_from_date( current_time( 'mysql' ) ),
			'comment_content'      => $message,
			'comment_approved'     => 1,
			'user_id'              => $vendor_id,
			'comment_agent'        => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
		];

		$comment_id = wp_insert_comment( $comment_data );

		if ( ! $comment_id ) {
			return new WP_Error(
				'dokan_rest_reply_creation_failed',
				__( 'Failed to create reply.', 'dokan' ),
				[ 'status' => 500 ]
			);
		}

		// Handle ticket status changes (following module logic)
		$ticket_reopened = false;

		if ( $close_ticket ) {
			wp_update_post(
				[
					'ID'          => $topic_id,
					'post_status' => 'closed',
				]
			);
			do_action( 'dokan_support_topic_status_changed', $topic_id, 'closed' );
		} elseif ( 'closed' === $current_post->post_status ) {
			// If ticket was closed and vendor is replying, reopen it (module behavior)
			wp_update_post(
				[
					'ID'          => $topic_id,
					'post_status' => 'open',
				]
			);
			do_action( 'dokan_support_topic_status_changed', $topic_id, 'open' );
			$ticket_reopened = true;
		}

		// Trigger email notification (following module notify_ticket_author method)
		$comment = get_comment( $comment_id );
		dokan_pro()->module->store_support->notify_ticket_author( $comment, $current_post );

		return rest_ensure_response(
			[
				'id'              => $comment_id,
				'ticket_id'       => $topic_id,
				'message'         => $message,
				'author'          => $comment->comment_author,
				'date'            => wc_rest_prepare_date_response( $comment->comment_date ),
				'ticket_closed'   => $close_ticket,
				'ticket_reopened' => $ticket_reopened,
			]
		);
	}

	/**
	 * Get support ticket statistics
	 *
	 * @since 4.1.1
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response object.
	 */
    public function get_stats( WP_REST_Request $request ) {
		$vendor_id = $this->get_current_user_id();
		$counts    = dokan_pro()->module->store_support->topic_count( $vendor_id );

		$stats = [
			'open'   => 0,
			'closed' => 0,
			'total'  => 0,
		];

		if ( is_array( $counts ) ) {
			$count_data      = wp_list_pluck( $counts, 'count', 'post_status' );
			$stats['open']   = (int) ( $count_data['open'] ?? 0 );
			$stats['closed'] = (int) ( $count_data['closed'] ?? 0 );
			$stats['total']  = $stats['open'] + $stats['closed'];
		}

		return rest_ensure_response( $stats );
	}

    /**
     * Get Current user id
     *
     * @return int
     */
    public function get_current_user_id(): int {
        return dokan_get_current_user_id();
    }

	/**
	 * Check if a given request has access to get items
	 *
	 * @since 4.1.1
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'dokan_manage_support_tickets' ) ) {
			return new WP_Error(
				'dokan_rest_cannot_view',
				__( 'Sorry, you cannot view support tickets.', 'dokan' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

        return $this->check_permission();
	}

	/**
	 * Check if a given request has access to get a specific item
	 *
	 * @since 4.1.1
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to update a specific item
	 *
	 * @since 4.1.1
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to create items
	 *
	 * @since 4.1.1
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @since 4.1.1
	 *
	 * @param \WP_Post        $item    WordPress post object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $item, $request ) {
		// Get customer info
		$customer      = get_user_by( 'id', $item->post_author );
		$customer_name = $customer ? $customer->display_name : __( 'Unknown', 'dokan' );

		// Get order info if available
		$order_id   = get_post_meta( $item->ID, 'order_id', true );
		$order_info = null;
		if ( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$order_info = [
					'id'     => $order_id,
					'number' => $order->get_order_number(),
					'status' => $order->get_status(),
                    'total' => $order->get_total(),
				];
			}
		}

		// Get comment count
		$comment_count = wp_count_comments( $item->ID );
		$replies_count = $comment_count->approved;

		// Generate topic URL (like in template)
		$topic_url = dokan_get_navigation_url( "support/{$item->ID}/" );

		// Prepare response data (following template structure)
		$data = [
			'id'             => $item->ID,
			'title'          => $item->post_title,
			'content'        => $item->post_content,
			'status'         => $item->post_status,
			'date_created'   => wc_rest_prepare_date_response( $item->post_date ),
			'date_modified'  => wc_rest_prepare_date_response( $item->post_modified ),
			'date_formatted' => dokan_format_datetime( dokan_get_timestamp( $item->post_date_gmt, true ) ),
			'customer'       => [
				'id'     => $item->post_author,
				'name'   => $customer_name,
				'email'  => $customer ? $customer->user_email : '',
				'avatar' => $customer ? get_avatar_url( $customer->ID, [ 'size' => 50 ] ) : '',
			],
			'order'          => $order_info,
			'vendor_id'      => get_post_meta( $item->ID, 'store_id', true ),
			'replies_count'  => (int) $replies_count,
			'topic_url'      => $topic_url,
		];

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		/**
		 * Filters the support ticket data for a REST API response.
		 *
		 * @since 4.1.1
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param \WP_Post         $item     Support ticket post object.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( 'dokan_rest_prepare_support_ticket_object', $response, $item, $request );
	}

	/**
	 * Prepare replies for response
	 *
	 * @since 4.1.1
	 *
	 * @param array $comments  Array of comment objects.
	 * @param int   $vendor_id Vendor ID for context.
	 *
	 * @return array Prepared replies data.
	 */
	protected function prepare_replies_for_response( $comments, $vendor_id ) {
		$replies = [];

		foreach ( $comments as $comment ) {
			$user       = get_user_by( 'id', $comment->user_id );
			$user_type  = 'customer';
			$user_image = get_avatar_url( $comment->user_id );
			$site_name  = '';

			// Determine user type (following support_comment_format method logic)
			if ( $user ) {
				if ( user_can( $user, 'manage_options' ) && dokan_is_user_seller( $user->ID ) ) {
					$user_type = 'admin';

					// For admin, use site logo if available
					$custom_logo_id = get_theme_mod( 'custom_logo' );
					$logo           = wp_get_attachment_image_src( $custom_logo_id, 'full' );
					$user_image     = has_custom_logo() ? $logo[0] : get_avatar_url( 0 );
					$site_name      = get_bloginfo( 'name', 'display' );
				} elseif ( dokan_is_user_seller( $user->ID ) ) {
					$user_type = 'vendor';
				}
			}

			$replies[] = apply_filters(
                'dokan_rest_prepare_support_ticket_reply', [
					'id'              => $comment->comment_ID,
					'content'         => $comment->comment_content,
					'date'            => wc_rest_prepare_date_response( $comment->comment_date ),
					'date_formatted'  => dokan_format_datetime( dokan_get_timestamp( $comment->comment_date_gmt, true ) ),
					'author'          => [
						'id'        => $comment->user_id,
						'name'      => $comment->comment_author,
						'type'      => $user_type,
						'avatar'    => $user_image,
						'site_name' => $site_name,
					],
					'human_time_diff' => sprintf(
					/* translators: %s: time difference */
						__( '%s ago', 'dokan' ),
						human_time_diff(
							dokan_get_timestamp( $comment->comment_date_gmt, true ),
							time()
						)
					),
				],
				$comment,
				$vendor_id
			);
		}

		return $replies;
	}

	/**
	 * Get the query params for collections
	 *
	 * @since 4.1.1
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['status'] = [
			'description' => __( 'Limit result set to tickets with specific status.', 'dokan' ),
			'type'        => 'string',
			'enum'        => [ 'open', 'closed', 'all' ],
			'default'     => 'open',
		];

		$params['customer_id'] = [
			'description' => __( 'Limit result set to tickets from specific customer.', 'dokan' ),
			'type'        => 'integer',
            'minimum' => 0,
		];

		$params['start_date'] = [
			'description' => __( 'Limit result set to tickets created after given date.', 'dokan' ),
			'type'        => 'string',
			'format'      => 'date',
		];

		$params['end_date'] = [
			'description' => __( 'Limit result set to tickets created before given date.', 'dokan' ),
			'type'        => 'string',
			'format'      => 'date',
		];

		$params['orderby'] = [
			'description' => __( 'Sort collection by object attribute.', 'dokan' ),
			'type'        => 'string',
			'default'     => 'date',
			'enum'        => [
				'date',
				'id',
				'title',
				'status',
			],
		];

		return $params;
	}

	/**
	 * Get the Support Ticket schema, conforming to JSON Schema.
	 *
	 * @since 4.1.1
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'support_ticket',
			'type'       => 'object',
			'properties' => [
				'id'            => [
					'description' => __( 'Unique identifier for the support ticket.', 'dokan' ),
					'type'        => 'integer',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
				'title'         => [
					'description' => __( 'Support ticket title.', 'dokan' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
				],
				'content'       => [
					'description' => __( 'Support ticket content/description.', 'dokan' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
				],
				'status'        => [
					'description' => __( 'Support ticket status.', 'dokan' ),
					'type'        => 'string',
					'enum'        => [ 'open', 'closed' ],
					'context'     => [ 'view', 'edit' ],
				],
				'date_created'  => [
					'description' => __( 'The date the support ticket was created, in the site\'s timezone.', 'dokan' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
				'date_modified' => [
					'description' => __( 'The date the support ticket was last modified, in the site\'s timezone.', 'dokan' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
				'customer'      => [
					'description' => __( 'Customer information.', 'dokan' ),
					'type'        => 'object',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
					'properties'  => [
						'id'     => [
							'description' => __( 'Customer ID.', 'dokan' ),
							'type'        => 'integer',
							'context'     => [ 'view', 'edit' ],
						],
						'name'   => [
							'description' => __( 'Customer display name.', 'dokan' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
						],
						'email'  => [
							'description' => __( 'Customer email address.', 'dokan' ),
							'type'        => 'string',
							'format'      => 'email',
							'context'     => [ 'view', 'edit' ],
						],
						'avatar' => [
							'description' => __( 'Customer avatar URL.', 'dokan' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => [ 'view', 'edit' ],
						],
					],
				],
				'order'         => [
					'description' => __( 'Related order information (if applicable).', 'dokan' ),
					'type'        => 'object',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
					'properties'  => [
						'id'     => [
							'description' => __( 'Order ID.', 'dokan' ),
							'type'        => 'integer',
							'context'     => [ 'view', 'edit' ],
						],
						'number' => [
							'description' => __( 'Order number.', 'dokan' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
						],
						'status' => [
							'description' => __( 'Order status.', 'dokan' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
						],
						'total'  => [
							'description' => __( 'Order total (formatted).', 'dokan' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
						],
					],
				],
				'vendor_id'     => [
					'description' => __( 'Vendor/Store ID.', 'dokan' ),
					'type'        => 'integer',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
				'replies_count' => [
					'description' => __( 'Number of replies to this ticket.', 'dokan' ),
					'type'        => 'integer',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
				'replies'       => [
					'description' => __( 'Support ticket replies (only available when fetching single ticket).', 'dokan' ),
					'type'        => 'array',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'id'      => [
								'description' => __( 'Reply ID.', 'dokan' ),
								'type'        => 'integer',
							],
							'content' => [
								'description' => __( 'Reply content.', 'dokan' ),
								'type'        => 'string',
							],
							'date'    => [
								'description' => __( 'Reply date.', 'dokan' ),
								'type'        => 'string',
								'format'      => 'date-time',
							],
							'author'  => [
								'description' => __( 'Reply author information.', 'dokan' ),
								'type'        => 'object',
								'properties'  => [
									'id'     => [
										'description' => __( 'Author ID.', 'dokan' ),
										'type'        => 'integer',
									],
									'name'   => [
										'description' => __( 'Author name.', 'dokan' ),
										'type'        => 'string',
									],
									'type'   => [
										'description' => __( 'Author type (customer, vendor, admin).', 'dokan' ),
										'type'        => 'string',
										'enum'        => [ 'customer', 'vendor', 'admin' ],
									],
									'avatar' => [
										'description' => __( 'Author avatar URL.', 'dokan' ),
										'type'        => 'string',
										'format'      => 'uri',
									],
								],
							],
						],
					],
				],
			],
		];

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}
}
