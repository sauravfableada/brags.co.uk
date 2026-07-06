<?php
/**
 * ShipStation REST API Diagnostics Controller file.
 *
 * @package WeDevs\DokanPro\Modules\ShipStation
 */

namespace WeDevs\DokanPro\Modules\ShipStation\REST;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_REST_Controller;
use Automattic\WooCommerce\Utilities\RestApiUtil;

/**
 * VendorDiagnosticsController class.
 */
class VendorDiagnosticsController extends WP_REST_Controller {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->rest_base = 'diagnostics';
		$this->namespace = 'wc-shipstation/v1';
	}

	/**
	 * Register the routes for the controller.
	 */
	public function register_routes(): void {

		// Register the endpoint for retrieving site details.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/details',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_details' ),
				'permission_callback' => array( $this, 'check_get_permission' ),
			)
		);

		// Register the endpoint for site validation.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/validate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'validate_site' ),
				'permission_callback' => array( $this, 'check_creatable_permission' ),
			)
		);
	}

	/**
	 * REST API permission callback.
	 *
	 * @return bool|WP_Error
	 */
	public function check_get_permission() {
		if ( current_user_can( 'manage_options' ) || current_user_can( 'dokandar' ) ) {
			return true;
		}

		return new \WP_Error(
			'dokan_pro_permission_failure',
			__( 'Sorry! You are not permitted to do current action.', 'dokan' ),
			[ 'status' => 403 ]
		);
	}

	/**
	 * REST API permission callback.
	 *
	 * @return bool|WP_Error
	 */
	public function check_creatable_permission() {
		return $this->check_get_permission();
	}

	/**
	 * Retrieve the site information.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_details( WP_REST_Request $request ): WP_REST_Response {
		$report      = wc_get_container()->get( RestApiUtil::class )->get_endpoint_data( '/wc/v3/system_status' );
		$environment = isset( $report['environment'] ) && is_array( $report['environment'] ) ? $report['environment'] : array();

		if ( ! empty( $report['active_plugins'] ) && is_array( $report['active_plugins'] ) ) {
			$active_plugins = array_map(
				function ( $plugin_info ) {
					$info = array();
					if ( ! empty( $plugin_info['name'] ) ) {
						$info[] = $plugin_info['name'];
					}
					if ( ! empty( $plugin_info['version'] ) ) {
						$info[] = $plugin_info['version'];
					}
					return implode( ' ', $info );
				},
				$report['active_plugins']
			);
		} else {
			$active_plugins = array();
		}

		// Prepare the response data.
		$site_info = array(
			'source_details' => array(
				'plugin_version'      => DOKAN_PRO_PLUGIN_VERSION,
				'woocommerce_version' => isset( $environment['version'] ) ? esc_html( $environment['version'] ) : '',
				'php_version'         => isset( $environment['php_version'] ) ? esc_html( $environment['php_version'] ) : '',
				'wordpress_version'   => isset( $environment['wp_version'] ) ? esc_html( $environment['wp_version'] ) : '',
				'memory_limit'        => isset( $environment['wp_memory_limit'] ) ? esc_html( size_format( $environment['wp_memory_limit'] ) ) : '',
				'active_plugins'      => implode( ', ', $active_plugins ),
			),
		);

		/**
		 * Filters the site information.
		 *
		 * @param array           $site_info The site information.
		 * @param WP_REST_Request $request   The request object.
		 *
		 * @since 5.0.0
		 */
		return new WP_REST_Response( apply_filters( 'dokan_rest_shipstation_diagnostics_details', $site_info, $request ), 200 );
	}

	/**
	 * Validating the site.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function validate_site( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'valid' => true,
			),
			200
		);
	}
}
