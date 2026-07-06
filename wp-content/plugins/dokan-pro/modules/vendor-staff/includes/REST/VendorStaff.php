<?php

namespace WeDevs\DokanPro\Modules\VendorStaff\REST;

use DokanPro\Modules\VendorStaff\VendorStaffCache;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_REST_Users_Controller;

defined( 'ABSPATH' ) || exit();

/**
 * Vendor Staff API Controller.
 *
 * @since 3.9.0
 */
class VendorStaff extends WP_REST_Controller {

    /**
     * Version
     *
     * @var string
     */
    protected $version = 'v1';

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'dokan';

    /**
     * Route name
     *
     * @var string
     */
    protected $rest_base = 'vendor-staff';

    public function __construct() {
    }

    /**
     * Register vendor staff routes.
     *
     * @since 3.9.0
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace . '/' . $this->version,
            $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_items' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => $this->get_collection_params(),
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'create_item' ],
                    'permission_callback' => [ $this, 'create_item_permissions_check' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace . '/' . $this->version,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_item' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => [
                        'context' => [
                            'default' => 'view',
                        ],
                    ],
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
                    'args'                => [
                        'force' => [
                            'default' => false,
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace . '/' . $this->version,
            '/' . $this->rest_base . '/(?P<id>[\d]+)/capabilities',
            [
                'args'                => [
                    'id' => [
                        'description' => __( 'Unique identifier for the user.', 'dokan' ),
                        'type'        => 'integer',
                        'required'    => true,
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_capabilities' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => [],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update_capabilities' ],
                    'permission_callback' => [ $this, 'update_item_permissions_check' ],
                    'args'                => $this->get_capabilities_args(),
                ],
            ]
        );
        register_rest_route(
            $this->namespace . '/' . $this->version,
            '/' . $this->rest_base . '/capabilities',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_all_capabilities' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                    'args'                => [], // No args needed since we're just getting fixed lists
                ],
            ]
        );
    }

    /**
     * Get All The Staff
     *
     * @since 3.9.0
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function get_items( $request ) {
        $args   = [
            'number' => $request->get_param( 'per_page' ),
            'offset' => ( $request->get_param( 'page' ) - 1 ) * $request->get_param( 'per_page' ),
            'search' => $request->get_param( 'search' ),
            'orderby' => $request->get_param( 'orderby' ),
            'order'   => $request->get_param( 'order' ),
        ];
        $result = dokan_get_all_vendor_staffs( $args );

        $count  = absint( $result['total_users'] );
        $data   = [];
        foreach ( $result['staffs'] as $staff ) {
            $response = $this->prepare_item_for_response( $staff, $request );
            $data[]   = $this->prepare_response_for_collection( $response ); // @phpstan-ignore-line
        }

        $response = rest_ensure_response( $data );
        $response->header( 'X-WP-Total', (string) $count );
        $response->header( 'X-WP-TotalPages', (string) ceil( $count / $request->get_param( 'per_page' ) ) );

        return $response;
    }

    /**
     * Create a new Staff.
     *
     * @since 3.9.0
     *
     * @param WP_REST_Request $request Request
     *
     * @return int|WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function create_item( $request ) {
        $email      = $request->get_param( 'email' );
        $first_name = $request->get_param( 'first_name' ) ?? '';
        $last_name  = $request->get_param( 'last_name' ) ?? '';
        $username   = $request->get_param( 'username' ) ?? $email;
        $password   = $request->get_param( 'password' ) ?? wp_generate_password();
        $phone      = $request->get_param( 'phone' ) ?? '';

        $userdata = [
            'user_email' => $email,
            'user_login' => $username,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'role'       => 'vendor_staff',
            'user_pass'  => $password,
        ];

        $user = wp_insert_user( wp_slash( $userdata ) );

        if ( is_wp_error( $user ) ) {
            return $user;
        }

        update_user_meta( $user, '_staff_phone', $phone );
        update_user_meta( $user, '_vendor_id', get_current_user_id() );

        do_action( 'dokan_new_staff_created', $user );

        ( new \Dokan_Staffs() )->handle_staff_user_capabilities( $user );

        ( new VendorStaffCache() )->clear_cache( get_current_user_id() );

        /**
         * Dokan After Insert / Update Staff Hook
         *
         * @since 3.4.2
         *
         * @param int $vendor_id
         * @param int $user_id
         */
        do_action( 'dokan_after_save_staff', get_current_user_id(), $user );

        $response = rest_ensure_response( $this->prepare_item_for_response( new \WP_User( $user ), $request ) );
        $response->set_status( 201 );

        return $response;
    }

    /**
     * Get a Single Staff.
     *
     * @since 3.9.0
     *
     * @param WP_REST_Request $request Request.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function get_item( $request ) {
        $id    = $request->get_param( 'id' );
        $staff = new \WP_User( $id );

        // check vendor id
        $vendor_id = $staff->get( '_vendor_id' );

        if ( ! $staff->exists() || (int) $vendor_id !== dokan_get_current_user_id() ) {
            return new WP_Error( 403, __( 'Staff access denied', 'dokan' ), [ 'status' => 403 ] );
        }

        return rest_ensure_response( $this->prepare_item_for_response( $staff, $request ) );
    }

    /**
     * Update a Single Staff.
     *
     * @since 3.9.0
     *
     * @param WP_REST_Request $request Request.
     *
     * @return int|WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function update_item( $request ) {
        $id    = $request->get_param( 'id' );
        $staff = new \WP_User( $id );

        if ( ! $staff->exists() ) {
            return new WP_Error( 404, __( 'Staff does not exist', 'dokan' ) );
        }

        // vendor ownership validation
        $vendor_id = $staff->get( '_vendor_id' );
        if ( ! in_array( 'vendor_staff', $staff->roles, true ) || (int) $vendor_id !== dokan_get_current_user_id() ) {
            return new WP_Error( 403, __( 'Staff access denied', 'dokan' ), [ 'status' => 403 ] );
        }

        $email      = $request->get_param( 'email' ) ?? $staff->user_email;
        $first_name = $request->get_param( 'first_name' ) ?? $staff->first_name;
        $last_name  = $request->get_param( 'last_name' ) ?? $staff->last_name;
        $password   = $request->get_param( 'password' );
        $phone      = $request->get_param( 'phone' ) ?? get_user_meta( $id, '_staff_phone', true );

        $userdata = array(
            'ID'           => $id,
            'user_email'   => $email,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
        );

        if ( ! empty( $password ) ) {
            $userdata['user_pass'] = $password;
        }

        $user = wp_update_user( wp_slash( $userdata ) );

        if ( is_wp_error( $user ) ) {
            return $user;
        }

        if ( ! empty( $password ) ) {
            \WC_Emails::instance()->get_emails()['Dokan_Staff_Password_Update']->trigger( $user ); // @phpstan-ignore-line
        }

        update_user_meta( $id, '_staff_phone', $phone );

        ( new VendorStaffCache() )->clear_cache( get_current_user_id() );

        /**
         * Dokan After Insert / Update Staff Hook
         *
         * @since 3.4.2
         *
         * @param int $vendor_id
         * @param int $user_id
         */
        do_action( 'dokan_after_save_staff', get_current_user_id(), $user );

        return rest_ensure_response( $this->prepare_item_for_response( new \WP_User( $id ), $request ) );
    }

    /**
     * Delete a Staff.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function delete_item( $request ) {
        $id    = $request->get_param( 'id' );
        $force = $request->get_param( 'force' );

        if ( ! $force ) {
            return rest_ensure_response(
                [
					'status' => false,
					'message' => esc_html__( 'Staff can not moved to trash.', 'dokan' ),
				]
            );
        }

        if ( ! ( new \WP_User( $id ) )->exists() ) {
            return new WP_Error( 404, __( 'Staff does not exist', 'dokan' ) );
        }

        /**
         * Action: Dokan Before Delete Staff Hook.
         *
         * @since 3.4.2
         *
         * @param int $vendor_id
         * @param int $user_id
         */
        do_action( 'dokan_before_delete_staff', get_current_user_id(), $id );

        $deleted = wp_delete_user( $id );

        if ( ! $deleted ) {
            return new \WP_Error( 'dokan_pro_error_delete_staff', esc_html__( 'Error deleting vendor staff.', 'dokan' ) );
        }

        $response = new WP_REST_Response();
        $response->set_status( 204 );

        return $response;
    }


    /**
     * Get capabilities.
     *
     * @since 3.9.0
     *
     * @param WP_REST_Request $request Request.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function get_capabilities( WP_REST_Request $request ) {
        $id = $request->get_param( 'id' );

        $staff = new \WP_User( $id );

        // check vendor id
        $vendor_id = $staff->get( '_vendor_id' );

        if ( ! $staff->exists() || ! in_array( 'vendor_staff', $staff->roles, true ) || (int) $vendor_id !== dokan_get_current_user_id() ) {
            return new WP_Error( 403, __( 'Staff access denied', 'dokan' ), [ 'status' => 403 ] );
        }

        return rest_ensure_response( $this->format_capabilities( $staff->allcaps ) );
    }


    /**
     * Get all and default capabilities.
     *
     * @since 3.9.0
     *
     * @param WP_REST_Request $request Request.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function get_all_capabilities( WP_REST_Request $request ) {
        return rest_ensure_response(
            [
                'all'     => dokan_get_all_caps(),
                'default' => dokan_get_staff_capabilities(),
            ]
        );
    }


    /**
     * Update capabilities.
     *
     * @since 3.9.0
     *
     * @param WP_REST_Request $request Request.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function update_capabilities( WP_REST_Request $request ) {
        $id           = $request->get_param( 'id' );
        $capabilities = $request->get_param( 'capabilities' ) ?? array();

        $staff = new \WP_User( $id );

        // Only the owning vendor may change capabilities, and only for their own staff.
        $vendor_id = $staff->get( '_vendor_id' );
        if ( ! $staff->exists() || ! in_array( 'vendor_staff', (array) $staff->roles, true ) || (int) $vendor_id !== dokan_get_current_user_id() ) {
            return new WP_Error( 403, __( 'Staff access denied', 'dokan' ), [ 'status' => 403 ] );
        }

        // Restrict assignment to Dokan's own staff capabilities so privileged WP caps (e.g. administrator) can never be granted.
        $allowed_caps = $this->get_assignable_capabilities();

        foreach ( $capabilities as $capability ) {
            // Skip anything outside the allowlist (also covers a missing/empty capability key).
            if ( ! in_array( $capability['capability'] ?? '', $allowed_caps, true ) ) {
                continue;
            }

            $capability['access'] ? $staff->add_cap( $capability['capability'] ) : $staff->remove_cap( $capability['capability'] );
        }

        return rest_ensure_response( $this->format_capabilities( $staff->allcaps ) );
    }

    /**
     * Capabilities a vendor is allowed to assign to their staff.
     *
     * @since 5.0.5
     *
     * @return string[]
     */
    protected function get_assignable_capabilities(): array {
        // dokan_get_all_caps() groups caps by section; flatten to the cap keys the staff UI actually exposes.
        $allowed = dokan_get_staff_capabilities();

        foreach ( dokan_get_all_caps() as $group ) {
            $allowed = array_merge( $allowed, array_keys( (array) $group ) );
        }

        return array_values( array_unique( $allowed ) );
    }

    /**
     * Get Items Permission check.
     *
     * @since 3.9.0
     *
     * @param WP_REST_Request $request Request.
     *
     * @return bool|WP_Error
     */
    public function create_item_permissions_check( $request ) {
        return $this->check_permission( $request );
    }

    /**
     * Get Item Permission check.
     *
     * @since 3.9.0
     *
     * @param WP_REST_Request $request Request.
     *
     * @return bool|WP_Error
     */
    public function check_permission( $request ) {
		if ( ! current_user_can( 'dokandar' ) || current_user_can( 'vendor_staff' ) ) {
			return new WP_Error( 'dokan_pro_permission_failure', esc_html__( 'You do not have necessary permission.', 'dokan' ), [ 'status' => 403 ] );
		}
        return true;
    }

    /**
     * Update Item Permission check.
     *
     * @since 3.9.0
     *
     * @param WP_REST_Request $request Request.
     *
     * @return bool|WP_Error
     */
    public function update_item_permissions_check( $request ) {
        return $this->check_permission( $request );
    }

    /**
     * Delete Item Permission check.
     *
     * @since 3.9.0
     *
     * @param WP_REST_Request $request Request.
     *
     * @return bool|WP_Error
     */
    public function delete_item_permissions_check( $request ) {
        return $this->check_permission( $request );
    }

    /**
     * Prepare Item For Response.
     *
     * @since 3.9.0
     *
     * @param \WP_User        $item Staff.
     * @param WP_REST_Request $request Request.
     *
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    public function prepare_item_for_response( $item, $request ) {
        // if item role is not vendor_staff then return error.
        if ( ! in_array( 'vendor_staff', (array) $item->roles, true ) ) {
            return new WP_Error( 403, __( 'Staff access denied', 'dokan' ), [ 'status' => 403 ] );
        }
        $staff_data = $item->to_array();
        unset( $staff_data['user_pass'] );

        $now        = dokan_current_datetime()->getTimestamp();
        $registered = dokan_current_datetime()->modify( $item->user_registered )->getTimestamp();

        $staff_data['phone']           = get_user_meta( $item->ID, '_staff_phone', true );
        $staff_data['first_name']      = $item->first_name;
        $staff_data['last_name']       = $item->last_name;
        $staff_data['user_registered'] = dokan_format_datetime( $item->user_registered );
        $staff_data['registered_at']   = sprintf(
            // translators: %s registration time deference. e.g. 1 minute
            __( '%s ago', 'dokan' ),
            human_time_diff( $registered, $now )
        );
        $staff_data['avatar']          = $this->get_gravatar( $item->user_email, 200 );
        $staff_data['capabilities']    = $item->allcaps;

        return rest_ensure_response( $staff_data );
    }

    /**
     * Get Staff schema.
     *
     * @since 3.9.0
     *
     * @return array The Staff schema.
     */
    public function get_item_schema() {
        if ( $this->schema ) {
            // Since WordPress 5.3, the schema can be cached in the $schema property.
            return $this->schema;
        }

        $this->schema = array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'vendor-stuff',
            'type'       => 'object',
            'properties' => array(
                'id'                 => array(
                    'description' => __( 'Unique identifier for the user.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => array( 'embed', 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'username'           => array(
                    'description' => __( 'Login name for the user.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'required'    => false,
                    'arg_options' => array(
                        'sanitize_callback' => array( $this, 'check_username' ),
                    ),
                ),
                'name'               => array(
                    'description' => __( 'Display name for the user.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => array( 'embed', 'view', 'edit' ),
                    'arg_options' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
                'first_name'         => array(
                    'description' => __( 'First name for the user.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'arg_options' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
                'last_name'          => array(
                    'description' => __( 'Last name for the user.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'arg_options' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
                'phone'          => array(
                    'description' => __( 'Phone number for the user.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'arg_options' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
                'email'              => array(
                    'description' => __( 'The email address for the user.', 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'email',
                    'context'     => array( 'view', 'edit' ),
                    'required'    => true,
                ),
                'nickname'           => array(
                    'description' => __( 'The nickname for the user.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'arg_options' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
                'registered_date'    => array(
                    'description' => __( 'Registration date for the user.', 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'registered_at'    => array(
                    'description' => __( 'Registration date for the user at human readable format.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'avatar'    => array(
                    'description' => __( 'User gravatar url', 'dokan' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'password'           => array(
                    'description' => __( 'Password for the user (never included).', 'dokan' ),
                    'type'        => 'string',
                    'context'     => array(), // Password is never displayed.
                    'required'    => false,
                    'arg_options' => array(
                        'sanitize_callback' => array( $this, 'check_user_password' ),
                    ),
                ),
                'capabilities'       => array(
                    'description' => __( 'All capabilities assigned to the user.', 'dokan' ),
                    'type'        => 'object',
                    'context'     => array( 'edit' ),
                    'readonly'    => true,
                ),
            ),
        );

        return $this->schema;
    }

    /**
     * Check a username for the REST API.
     *
     * Performs a couple of checks like edit_user() in wp-admin/includes/user.php.
     *
     * @since 3.9.0
     *
     * @param string          $value   The username submitted in the request.
     * @param WP_REST_Request $request Full details about the request.
     * @param string          $param   The parameter name.
     *
     * @return string|WP_Error The sanitized username, if valid, otherwise an error.
     */
    public function check_username( string $value, WP_REST_Request $request, string $param ) {
        return ( new WP_REST_Users_Controller() )->check_username( $value, $request, $param );
    }

    /**
     * Check a user password for the REST API.
     *
     * Performs a couple of checks like edit_user() in wp-admin/includes/user.php.
     *
     * @since 3.9.0
     *
     * @param string          $value   The password submitted in the request.
     * @param WP_REST_Request $request Full details about the request.
     * @param string          $param   The parameter name.
     *
     * @return string|WP_Error The sanitized password, if valid, otherwise an error.
     */
    public function check_user_password( string $value, WP_REST_Request $request, string $param ) {
        return ( new WP_REST_Users_Controller() )->check_user_password( $value, $request, $param );
    }

    /**
     * Get Capability Arguments.
     *
     * @since 3.9.0
     *
     * @return array[]
     */
    public function get_capabilities_args(): array {
        return [
            'capabilities' => [
                'type'     => 'array',
                'required' => true,
                'items'    => [
                    'type'       => 'object',
                    'properties' => [
                        'capability' => [
                            'description' => __( 'Capability name', 'dokan' ),
                            'type'        => 'string',
                            'required'    => true,
                        ],
                        'access'     => [
                            'description' => __( 'Has access to the  Capability', 'dokan' ),
                            'type'        => 'boolean',
                            'required'    => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Format the capabilities.
     *
     * @since 3.9.0
     *
     * @param array $caps Capabilities.
     *
     * @return array
     */
    protected function format_capabilities( array $caps ): array {
        $default_caps = array_fill_keys( dokan_get_staff_capabilities(), false );

        return wp_parse_args( $caps, $default_caps );
    }

    /**
     * Get either a Gravatar URL or complete image tag for a specified email address.
     *
     * @since 3.9.0
     *
     * @param string $email The email address
     * @param int $s Size in pixels, defaults to 80px [ 1 - 2048 ]
     * @param string $d Default imageset to use [ 404 | mp | identicon | monsterid | wavatar ]
     * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
     * @param bool $img True to return a complete IMG tag False for just the URL
     * @param array $atts Optional, additional key/value attributes to include in the IMG tag
     *
     * @return string containing either just a URL or a complete image tag
     * @source https://gravatar.com/site/implement/images/php/
     */
    protected function get_gravatar( $email, $s = 80, $d = 'mp', $r = 'g', $img = false, $atts = array() ): string {
        $url = 'https://www.gravatar.com/avatar/';
        $url .= md5( strtolower( trim( $email ) ) );
        $url .= "?s=$s&d=$d&r=$r";
        if ( $img ) {
            $url = '<img src="' . $url . '"';
            foreach ( $atts as $key => $val ) {
                $url .= ' ' . $key . '="' . $val . '"';
            }
            $url .= ' />';
        }
        return $url;
    }
}
