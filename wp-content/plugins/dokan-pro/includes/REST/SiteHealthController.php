<?php

namespace WeDevs\DokanPro\REST;

use WP_REST_Server;
use WP_REST_Response;
use WeDevs\Dokan\REST\DokanBaseAdminController;

/**
 * Site Health REST controller.
 *
 * Returns WordPress site health debug data for the support ticket modal.
 * Data is fetched lazily — only when the modal is actually opened.
 *
 * @since 5.0.0
 */
class SiteHealthController extends DokanBaseAdminController {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'dokan/v1';

    /**
     * Route name.
     *
     * @var string
     */
    protected $base = 'admin/support-ticket';

    /**
     * Register routes.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace, '/' . $this->base . '/site-health', [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_site_health' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
    }

    /**
     * Get site health debug data.
     *
     * @since 5.0.0
     *
     * @return WP_REST_Response
     */
    public function get_site_health(): WP_REST_Response {
        require_once ABSPATH . 'wp-admin/includes/admin.php';
        require_once ABSPATH . 'wp-admin/includes/update.php';

        if ( ! class_exists( 'WP_Debug_Data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
        }

        \WP_Debug_Data::check_for_updates();
        $info = \WP_Debug_Data::debug_data();

        return rest_ensure_response(
            [
                'origin'      => get_site_url(),
                'site_health' => \WP_Debug_Data::format( $info, 'debug' ),
            ]
        );
    }

    /**
     * Get the schema for the site health endpoint.
     *
     * @since 5.0.0
     *
     * @return array
     */
    public function get_public_item_schema(): array {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'support-ticket-site-health',
            'type'       => 'object',
            'properties' => [
                'origin'      => [
                    'description' => __( 'The site origin URL.', 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'uri',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'site_health' => [
                    'description' => __( 'Formatted site health debug data.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
            ],
        ];

        return $this->add_additional_fields_schema( $schema );
    }
}
