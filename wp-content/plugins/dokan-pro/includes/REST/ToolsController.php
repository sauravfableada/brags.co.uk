<?php

namespace WeDevs\DokanPro\REST;

use WeDevs\Dokan\REST\DokanBaseAdminController;
use WeDevs\DokanPro\Admin\ToolsActions;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class ToolsController extends DokanBaseAdminController {

	/**
	 * Route base.
	 *
	 * @since 5.0.0
	 *
	 * @var string
	 */
	protected $base = 'tools';

	/**
	 * Tools actions service instance.
	 *
	 * @since 5.0.0
	 *
	 * @var ToolsActions
	 */
	private $tools;

	/**
	 * Constructor.
	 *
	 * @since 5.0.0
	 */
	public function __construct() {
		$this->tools = new ToolsActions();
	}

	/**
	 * Register routes.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace, '/' . $this->base . '/regenerate-order-commission', [
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'regenerate_order_commission' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
			]
		);

		register_rest_route(
			$this->namespace, '/' . $this->base . '/check-duplicate-suborders', [
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'check_duplicate_suborders' ],
					'permission_callback' => [ $this, 'check_permission' ],
					'args'                => [
						'limit' => [
							'type'              => 'integer',
							'required'          => false,
							'default'           => 0,
							'sanitize_callback' => 'absint',
						],
						'offset' => [
							'type'              => 'integer',
							'required'          => false,
							'default'           => 0,
							'sanitize_callback' => 'absint',
						],
						'done' => [
							'type'              => 'integer',
							'required'          => false,
							'default'           => 0,
							'sanitize_callback' => 'absint',
						],
						'total_orders' => [
							'type'              => 'integer',
							'required'          => false,
							'default'           => 0,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace, '/' . $this->base . '/rewrite-product-variations-author', [
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'rewrite_product_variations_author' ],
					'permission_callback' => [ $this, 'check_permission' ],
					'args'                => [
						'page' => [
							'type'              => 'integer',
							'required'          => false,
							'default'           => 1,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace, '/' . $this->base . '/get-distance-btwn-address', [
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'get_distance_btwn_address' ],
					'permission_callback' => [ $this, 'check_permission' ],
					'args'                => [
						'address1' => [
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						],
						'address2' => [
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
			]
		);

		// Create default pages
		register_rest_route(
			$this->namespace, '/' . $this->base . '/create-pages', [
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_pages' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
			]
		);

		// Check if all Dokan pages exist
		register_rest_route(
			$this->namespace, '/' . $this->base . '/check-all-dokan-pages-exists', [
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'check_all_dokan_pages_exists' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
			]
		);
	}

	/**
	 * Callback: regenerate order commission
	 *
	 * @since 5.0.0
	 *
	 * @return WP_REST_Response
	 */
	public function regenerate_order_commission( WP_REST_Request $request ) {
		$result = $this->tools->regenerate_order_commission();

		return $this->format_response( $result );
	}

	/**
	 * Callback: check duplicate suborders
	 *
	 * @since 5.0.0
	 *
	 * @return WP_REST_Response
	 */
	public function check_duplicate_suborders( WP_REST_Request $request ) {
		$result = $this->tools->check_duplicate_suborders(
			(int) $request->get_param( 'limit' ),
			(int) $request->get_param( 'offset' ),
			(int) $request->get_param( 'done' ),
			(int) $request->get_param( 'total_orders' )
		);

		return $this->format_response( $result );
	}

	/**
	 * Callback: rewrite product variations author
	 *
	 * @since 5.0.0
	 *
	 * @return WP_REST_Response
	 */
	public function rewrite_product_variations_author( WP_REST_Request $request ) {
		$result = $this->tools->rewrite_product_variations_author( (int) $request->get_param( 'page' ) );

		return $this->format_response( $result );
	}

	/**
	 * Callback: get distance between addresses
	 *
	 * @since 5.0.0
	 *
	 * @return WP_REST_Response
	 */
	public function get_distance_btwn_address( WP_REST_Request $request ) {
		$result = $this->tools->get_distance_btwn_address(
			(string) $request->get_param( 'address1' ),
			(string) $request->get_param( 'address2' )
		);

		if ( ! is_wp_error( $result ) && isset( $result['message'] ) ) {
			return new WP_REST_Response( $result['message'], 200 );
		}

		return $this->format_response( $result );
	}

	/**
	 * Normalize array|WP_Error to WP_REST_Response.
	 *
	 * @since 5.0.0
	 *
	 * @param array|WP_Error $result
	 *
	 * @return WP_REST_Response
	 */
	protected function format_response( $result ) {
		if ( is_wp_error( $result ) ) {
			$status = (int) ( $result->get_error_data()['status'] ?? 400 );
			return new WP_REST_Response( [ 'message' => $result->get_error_message() ], $status );
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Callback: create Dokan default pages
	 *
	 * @since 5.0.0
	 *
	 * @return WP_REST_Response
	 */
	public function create_pages( WP_REST_Request $request ) {
		$result = $this->tools->create_default_pages();

		if ( ! is_wp_error( $result ) ) {
			return new WP_REST_Response( $result, 201 );
		}

		return $this->format_response( $result );
	}

	/**
	 * Callback: check if all Dokan pages are created
	 *
	 * @since 5.0.0
	 *
	 * @return WP_REST_Response
	 */
	public function check_all_dokan_pages_exists( WP_REST_Request $request ) {
		$result = $this->tools->check_all_dokan_pages_exists();

		if ( ! is_wp_error( $result ) ) {
			return new WP_REST_Response( $result, 200 );
		}

		return $this->format_response( $result );
	}
}
