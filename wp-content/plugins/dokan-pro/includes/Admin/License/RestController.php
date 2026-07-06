<?php

namespace WeDevs\DokanPro\Admin\License;

use WeDevs\Dokan\REST\DokanBaseAdminController;
use WeDevs\DokanPro\Dependencies\Appsero\Client;
use WeDevs\DokanPro\Dependencies\Appsero\License as AppseroLicense;

/**
 * REST Controller for Dokan Pro License management
 */
class RestController extends DokanBaseAdminController {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'dokan-pro/v1';

    /**
     * Appsero License instance
     *
     * @since 4.2.0
     *
     * @var AppseroLicense
     */
    private $license;

    /**
     * Appsero Client instance
     *
     * @since 4.2.0
     *
     * @var Client
     */
    private $client;

    /**
     * Constructor
     *
     * @since 4.2.0
     *
     * @param AppseroLicense $license
     */
    public function __construct( AppseroLicense $license, Client $client ) {
        $this->license = $license;
        $this->client = $client;
    }

    /**
     * Register REST routes for license operations.
     *
     * @since 4.2.0
     *
     * @return void
     */
    public function register_routes(): void {
        register_rest_route(
            $this->namespace,
            '/license/activate',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'activate' ],
                'permission_callback' => [ $this, 'check_permission' ],
                'args'                => [
                    'license_key' => [
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/license/deactivate',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'deactivate' ],
                'permission_callback' => [ $this, 'check_permission' ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/license/status',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'status' ],
                'permission_callback' => [ $this, 'check_permission' ],
            ]
        );
    }

    /**
     * Activate license
     *
     * @since 4.2.0
     *
     * @param \WP_REST_Request $request The REST request object.
     *
     * @return \WP_REST_Response The REST response object.
     */
    public function activate( \WP_REST_Request $request ) {
        $key = $request->get_param( 'license_key' );

        if ( empty( $key ) ) {
            return new \WP_REST_Response(
                [
					'success' => false,
					$this->client->__trans( 'License key not found.' ),
				], 400
            );
        }

        $license = $this->activate_or_deactivate_license( $key );

        if ( $license->error ) {
            return new \WP_REST_Response(
                [
					'success' => false,
					'message' => $license->error,
				], 400
            );
        }

        return new \WP_REST_Response(
            [
				'success' => true,
				'message' => $license->success,
			], 200
        );
    }

    /**
     * Deactivate license
     *
     * @since 4.2.0
     *
     * @return \WP_REST_Response The REST response object.
     */
    public function deactivate() {
        $license = $this->license->get_license();

        if ( empty( $license['key'] ) ) {
            return new \WP_REST_Response(
                [
					'success' => false,
					$this->client->__trans( 'License key not found.' ),
				], 400
            );
        }

        $license = $this->activate_or_deactivate_license( $license['key'], false );

        if ( $license->error ) {
            return new \WP_REST_Response(
                [
					'success' => false,
					'message' => $license->error,
				], 400
            );
        }

        return new \WP_REST_Response(
            [
				'success' => true,
				'message' => $license->success,
			], 200
        );
    }

    /**
     * Get license status
     *
     * @since 4.2.0
     *
     * @return \WP_REST_Response The REST response object.
     */
    public function status() {
        $license = $this->license->get_license();
        $status  = [
            'is_valid'    => wc_string_to_bool( $this->license->is_valid() ),
            'data'        => $license,
            'expiry_days' => isset( $license['expiry_days'] ) ? (int) $license['expiry_days'] : 0,
            'source_id'   => isset( $license['source_id'] ) ? $license['source_id'] : '',
            'has_key'     => ! empty( $license['key'] ),
        ];

        return new \WP_REST_Response(
            [
				'success' => true,
				'status' => $status,
			], 200
        );
    }

    /**
     * Activates or deactivates a license.
     *
     * @since 4.2.0
     *
     * @param string $license_key The license key to be activated or deactivated.
     * @param bool   $activate    Optional. Whether to activate (true) or deactivate (false) the license. Default is true.
     *
     * @return AppseroLicense The license object after performing the action.
     */
    private function activate_or_deactivate_license( $license_key, $activate = true ) {
        $this->license->license_form_submit(
            [
				'license_key' => $license_key,
				'_action'     => $activate ? 'active' : 'deactive',
				'_nonce'      => wp_create_nonce( $this->client->name ),
			]
        );

        return $this->license;
    }
}
