<?php
namespace WeDevs\DokanPro\Modules\RequestForQuotation\Api;

use WP_REST_Server;
use WeDevs\Dokan\Abstracts\DokanRESTController;

/**
 * Request A Quote Controller Class
 *
 * @since 3.6.0
 */
class RolesController extends DokanRESTController {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'dokan/v1/request-for-quote';

    /**
     * Route name
     *
     * @var string
     */
    protected $base = 'roles';

    /**
     * Register all request quote route
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace, '/' . $this->base, [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_dokan_roles' ],
                    'args'                => $this->get_collection_params(),
                    'permission_callback' => [ $this, 'get_roles_permissions_check' ],
                ],
                'schema' => [ $this, 'get_item_schema' ],
            ]
        );
    }

    /**
     * Get all request_quote
     *
     * @since 3.6.0
     *
     * @return object
     */
    public function get_dokan_roles( $request ) {
        global $wp_roles;
        $results = [];
        if ( ! empty( $wp_roles ) ) {
            foreach ( $wp_roles as $key => $value ) {
                $res           = $this->prepare_response_for_object( $value, $request );
                $results[ $key ] = $this->prepare_response_for_collection( $res );
            }
        }

        $response = rest_ensure_response( $results );

        return $this->format_collection_response( $response, $request, count( $wp_roles->get_names() ) );
    }

    /**
     * Create request_quote permissions check
     *
     * @since 3.6.0
     *
     * @return bool
     */
    public function get_roles_permissions_check() {
        return user_can( get_current_user_id(), 'manage_options' );
    }

    /**
     * Prepare data for response
     *
     * @since 2.8.0
     *
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function prepare_response_for_object( $object, $request ) {
        return rest_ensure_response( $object );
    }

    /**
     * Get collection params.
     *
     * @since 3.6.0
     *
     * @return array
     */
    public function get_collection_params() {
        $params = parent::get_collection_params();
        $params = array_merge(
            $params,
            [
                'status' => [
                    'type'        => 'string',
                    'description' => __( 'Request Quote status', 'dokan' ),
                    'required'    => false,
                ],
            ]
        );
        unset( $params['search'] );

        return $params;
    }

}
