<?php

namespace WeDevs\DokanPro\Modules\RankMath\REST;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WeDevs\DokanPro\Modules\RankMath\Screen;
use WeDevs\DokanPro\Modules\RankMath\SchemaData;

defined( 'ABSPATH' ) || exit;

/**
 * Class Rank Math Controller
 *
 * @package WeDevs\DokanPro\Modules\RankMath\REST
 *
 * @since 3.7.13
 */
class RankMathController extends WP_REST_Controller {

    use SchemaData;

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'dokan/v2';

    /**
     * Route name.
     *
     * @var string
     */
    protected $base = 'rank-math';

    /**
     * Register all routes.
     *
     * @since 3.7.13
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace, '/' . $this->base . '/(?P<id>[\d]+)/store-current-editable-post', [
                'args' => [
                    'id' => [
                        'description'       => __( 'Unique identifier for the object.', 'dokan' ),
                        'type'              => 'integer',
                        'required'          => true,
                        'arg_options' => [
                            'validate_callback' => [ $this, 'product_exists' ],
                        ],
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'store_current_post_id' ],
                    'permission_callback' => [ $this, 'store_current_post_id_permissions_check' ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace, '/' . $this->base . '/(?P<id>[\d]+)/editor-data', [
                'args' => [
                    'id' => [
                        'description' => __( 'Unique identifier for the object.', 'dokan' ),
                        'type'        => 'integer',
                        'required'    => true,
                        'arg_options' => [
                            'validate_callback' => [ $this, 'product_exists' ],
                        ],
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_editor_data' ],
                    'permission_callback' => [ $this, 'store_current_post_id_permissions_check' ],
                ],
            ]
        );
    }

    /**
     * Checks the given ID resolves to an existing product.
     *
     * @param int             $product_id
     * @param WP_REST_Request $request
     *
     * @since 3.7.13
     *
     * @return bool
     */
    public function product_exists( $product_id, $request ) {
        return 'product' === get_post_type( $product_id );
    }

    /**
     * Checks if user has edit post permission.
     *
     * @since 3.7.13
     *
     * @param WP_REST_Request $request REST Request.
     *
     * @return bool True if the request has read access, false otherwise.
     */
    public function store_current_post_id_permissions_check( $request ) {
        $post_id = ! empty( $request['id'] ) ? absint( wp_unslash( $request['id'] ) ) : 0;
        return current_user_can( 'edit_post', $post_id );
    }

    /**
     * Store current edit post id for the user.
     *
     * @param WP_REST_Request $request
     *
     * @since 3.7.13
     *
     * @return WP_REST_Response
     */
    public function store_current_post_id( WP_REST_Request $request ) {
        $product_id = ! empty( $request['id'] ) ? absint( wp_unslash( $request['id'] ) ) : 0;
        $status     = false;

        if ( $product_id ) {
            update_user_meta( get_current_user_id(), 'dokan_rank_math_edit_post_id', $product_id );
            $status = true;
        }

        return rest_ensure_response( $status );
    }

    /**
     * Returns the Rank Math payload for a single product.
     *
     * Rank Math's metabox reads `window.rankMath`, localized once at page load,
     * so it goes stale on an SPA product switch. This returns the same payload
     * for the requested product so the editor re-seeds the store and remounts
     * the section without a reload. Schemas are appended because Rank Math
     * localizes them separately.
     *
     * @since 5.0.3
     *
     * @param WP_REST_Request $request REST Request.
     *
     * @return WP_REST_Response
     */
    public function get_editor_data( WP_REST_Request $request ) {
        $product_id = absint( wp_unslash( $request['id'] ) );

        // Screen::get_values() reads $_GET['product_id'] and the global $post, so swap both to the
        // requested product and always restore them — even if get_values() throws.
        global $post;
        $previous_post = $post;

        // @codingStandardsIgnoreStart
        $post               = get_post( $product_id );
        $_GET['product_id'] = $product_id;

        try {
            $payload            = ( new Screen() )->get_values();
            $payload['schemas'] = self::get_product_schema_data( $product_id );
        } finally {
            $post = $previous_post;
            unset( $_GET['product_id'] );
        }
        // @codingStandardsIgnoreEnd

        return rest_ensure_response( $payload );
    }
}
