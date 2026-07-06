<?php

namespace WeDevs\DokanPro\Modules\ProductAddon\Rest;

use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WeDevs\Dokan\REST\DokanBaseVendorController;
use WC_Product_Addons_Groups;

/**
 * Vendor Product Addon REST Controller
 *
 * Handles REST API endpoints for vendors to manage their global product addons.
 *
 * @since DOKAN_VERSION
 *
 * @package WeDevs\DokanPro\Modules\ProductAddon\Rest
 */
class VendorProductAddonController extends DokanBaseVendorController {

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'vendor/product-addons';

    /**
     * Register the routes for vendor product addons.
     *
     * @since DOKAN_VERSION
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_items' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                    'args'                => $this->get_collection_params(),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            [
                'args' => [
                    'id' => [
                        'description' => __( 'Unique identifier for the product addon.', 'dokan' ),
                        'type'        => 'integer',
                        'required'    => true,
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_item' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update_item' ],
                    'permission_callback' => [ $this, 'update_item_permissions_check' ],
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'delete_item' ],
                    'permission_callback' => [ $this, 'delete_item_permissions_check' ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/serialize',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'serialize_addons' ],
                'permission_callback' => [ $this, 'update_item_permissions_check' ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/unserialize',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'unserialize_addons' ],
                'permission_callback' => [ $this, 'update_item_permissions_check' ],
            ]
        );
    }

    /**
     * Encodes an addons array to legacy PHP serialize format.
     *
     * @since 5.0.3
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function serialize_addons( $request ) {
        $addons = $request->get_param( 'addons' );
        if ( ! is_array( $addons ) ) {
            return new WP_Error(
                'invalid_addons',
                __( 'Expected an addons array.', 'dokan' ),
                [ 'status' => 400 ]
            );
        }
        /**
         * Filters the serialized add-ons export payload.
         *
         * @since 5.0.3
         *
         * @param string $serialized Serialized add-ons string.
         * @param array  $addons     Add-ons array being exported.
         */
        $serialized = apply_filters( 'dokan_product_addons_export', maybe_serialize( $addons ), $addons );

        return rest_ensure_response( [ 'data' => $serialized ] );
    }

    /**
     * Decodes legacy PHP-serialized (or JSON) addons text back into an array.
     *
     * Delegates to the shared dokan_pa_decode_addons_string() helper (also used
     * by the legacy importer) so the classic editor's export round-trips identically.
     *
     * @since 5.0.3
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function unserialize_addons( $request ) {
        $data = $request->get_param( 'data' );
        if ( ! is_string( $data ) || '' === trim( $data ) ) {
            return new WP_Error(
                'invalid_data',
                __( 'Expected a non-empty string.', 'dokan' ),
                [ 'status' => 400 ]
            );
        }

        $decoded = dokan_pa_decode_addons_string( $data );

        if ( false === $decoded ) {
            return new WP_Error(
                'parse_failed',
                __( 'Could not parse the input as legacy serialize or JSON.', 'dokan' ),
                [ 'status' => 400 ]
            );
        }

        /**
         * Filters the decoded add-ons import payload before it returns to the editor.
         *
         * @since 5.0.3
         *
         * @param array  $addons Decoded add-ons (re-indexed).
         * @param string $data   Raw input string that was decoded.
         */
        $addons = apply_filters( 'dokan_product_addons_import', array_values( $decoded ), $data );

        return rest_ensure_response( [ 'addons' => $addons ] );
    }

    /**
     * Get collection of product addons for the current vendor.
     *
     * @since DOKAN_VERSION
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response Response object.
     */
    public function get_items( $request ) {
        $global_addons = dokan_pa_get_global_addons_for_vendor_staff_vendor();

        $items = [];
        foreach ( $global_addons as $addon ) {
            $addon_post = get_post( $addon['id'] );
            if ( $addon_post ) {
                $data = $this->prepare_item_for_response( $addon_post, $request );
                $items[] = $this->prepare_response_for_collection( $data );
            }
        }

        $response = rest_ensure_response( $items );
        $total_items = count( $items );

        return $this->format_collection_response( $response, $request, $total_items );
    }

    /**
     * Get a single product addon.
     *
     * @since DOKAN_VERSION
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error Response object on success, WP_Error on failure.
     */
    public function get_item( $request ) {
        $addon_id = (int) $request['id'];
        $addon    = get_post( $addon_id );

        if ( ! $addon || 'global_product_addon' !== $addon->post_type ) {
            return new WP_Error(
                'dokan_rest_addon_not_found',
                __( 'Product addon not found.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        if ( ! $this->user_can_access_addon( $addon ) ) {
            return new WP_Error(
                'dokan_rest_addon_forbidden',
                __( 'You do not have permission to access this addon.', 'dokan' ),
                [ 'status' => 403 ]
            );
        }

        $result = WC_Product_Addons_Groups::get_group( $addon_id );
        if ( is_wp_error( $result ) ) {
            return new WP_Error(
                'dokan_rest_addon_not_found',
                $result->get_error_message(),
                [ 'status' => 404 ]
            );
        }

        return $this->prepare_item_for_response( $addon, $request );
    }

    /**
     * Delete a product addon.
     *
     * @since DOKAN_VERSION
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error Response object on success, WP_Error on failure.
     */
    public function delete_item( $request ) {
        $addon_id = (int) $request['id'];
        $addon    = get_post( $addon_id );

        if ( ! $addon || 'global_product_addon' !== $addon->post_type ) {
            return new WP_Error(
                'dokan_rest_addon_not_found',
                __( 'Product addon not found.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        if ( ! $this->user_can_access_addon( $addon ) ) {
            return new WP_Error(
                'dokan_rest_addon_forbidden',
                __( 'You do not have permission to delete this addon.', 'dokan' ),
                [ 'status' => 403 ]
            );
        }
        $result = WC_Product_Addons_Groups::delete_group( $addon_id );

        if ( is_wp_error( $result ) ) {
            return new WP_Error(
                'dokan_rest_addon_delete_failed',
                $result->get_error_message(),
                [ 'status' => 500 ]
            );
        }

        return rest_ensure_response(
            [
                'id'      => $addon_id,
                'deleted' => true,
                'message' => __( 'Product addon deleted successfully.', 'dokan' ),
            ]
        );
    }

    /**
     * Check if a given request has access to get items.
     *
     * @since DOKAN_VERSION
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return bool|WP_Error True if the request has read access, WP_Error otherwise.
     */
    public function get_items_permissions_check( $request ) {
        return $this->check_permission();
    }

    /**
     * Update a product addon.
     *
     * @since DOKAN_VERSION
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error Response object on success, WP_Error on failure.
     */
    public function update_item( $request ) {
        $addon_id = (int) $request['id'];
        $addon    = get_post( $addon_id );

        if ( ! $addon || 'global_product_addon' !== $addon->post_type ) {
            return new WP_Error(
                'dokan_rest_addon_not_found',
                __( 'Product addon not found.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        if ( ! $this->user_can_access_addon( $addon ) ) {
            return new WP_Error(
                'dokan_rest_addon_forbidden',
                __( 'You do not have permission to update this addon.', 'dokan' ),
                [ 'status' => 403 ]
            );
        }

        $allowed_keys     = [ 'name', 'priority', 'restrict_to_categories', 'fields', 'exclude_global_add_ons' ];
        $filtered_request = array_intersect_key( $request->get_params(), array_flip( $allowed_keys ) );
        $result           = WC_Product_Addons_Groups::update_group( $addon_id, wc_clean( $filtered_request ) );

        if ( is_wp_error( $result ) ) {
            return new WP_Error(
                'dokan_rest_addon_update_failed',
                $result->get_error_message(),
                [ 'status' => 400 ]
            );
        }

        return $this->prepare_item_for_response( get_post( $addon_id ), $request );
    }

    /**
     * Check if a given request has access to update an item.
     *
     * @since DOKAN_VERSION
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return bool|WP_Error True if the request has update access, WP_Error otherwise.
     */
    public function update_item_permissions_check( $request ) {
        return $this->check_permission();
    }

    /**
     * Check if a given request has access to delete an item.
     *
     * @since DOKAN_VERSION
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return bool|WP_Error True if the request has delete access, WP_Error otherwise.
     */
    public function delete_item_permissions_check( $request ) {
        return $this->check_permission();
    }

    /**
     * Check if the current user can access a specific addon.
     *
     * @since DOKAN_VERSION
     *
     * @param \WP_Post $addon The addon post object.
     *
     * @return bool
     */
    protected function user_can_access_addon( $addon ) {
        $user_id = dokan_get_current_user_id();

        if ( (int) $addon->post_author === $user_id ) {
            return true;
        }

        // Check vendor staff access
        if ( function_exists( 'dokan_get_vendor_staff' ) ) {
            $author_ids = dokan_get_vendor_staff( $user_id );

            return in_array( (int) $addon->post_author, $author_ids, true );
        }

        return false;
    }

    /**
     * Prepare the item for the REST response.
     *
     * @since DOKAN_VERSION
     *
     * @param \WP_Post        $item    WordPress post object.
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response Response object.
     */
    public function prepare_item_for_response( $item, $request ) {
        $priority       = get_post_meta( $item->ID, '_priority', true );
        $all_products   = (int) get_post_meta( $item->ID, '_all_products', true );
        $product_addons = get_post_meta( $item->ID, '_product_addons', true );

        // Get assigned category names
        $categories  = [];
        $category_ids = (array) wp_get_post_terms(
            $item->ID,
            apply_filters( 'woocommerce_product_addons_global_post_terms', [ 'product_cat' ] ),
            [ 'fields' => 'all' ]
        );

        foreach ( $category_ids as $term ) {
            if ( is_object( $term ) ) {
                $categories[] = [
                    'id'   => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                ];
            }
        }

        $data = [
            'id'           => $item->ID,
            'name'         => $item->post_title,
            'priority'     => (int) $priority,
            'all_products' => 1 === $all_products,
            'categories'   => $categories,
            'field_count'  => is_array( $product_addons ) ? count( $product_addons ) : 0,
        ];

        $context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
        $data     = $this->filter_response_by_context( $data, $context );
        $response = rest_ensure_response( $data );

        /**
         * Filters the product addon data for a REST API response.
         *
         * @since DOKAN_VERSION
         *
         * @param WP_REST_Response $response The response object.
         * @param \WP_Post         $item     Product addon post object.
         * @param WP_REST_Request  $request  Request object.
         */
        return apply_filters( 'dokan_rest_prepare_product_addon_object', $response, $item, $request );
    }

    /**
     * Get the query params for collections.
     *
     * @since DOKAN_VERSION
     *
     * @return array Collection parameters.
     */
    public function get_collection_params() {
        $params = parent::get_collection_params();

        $params['orderby'] = [
            'description' => __( 'Sort collection by object attribute.', 'dokan' ),
            'type'        => 'string',
            'default'     => 'date',
            'enum'        => [ 'date', 'id', 'title' ],
        ];

        return $params;
    }

    /**
     * Get the Product Addon schema, conforming to JSON Schema.
     *
     * @since DOKAN_VERSION
     *
     * @return array Item schema data.
     */
    public function get_item_schema() {
        if ( $this->schema ) {
            return $this->add_additional_fields_schema( $this->schema );
        }

        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'product_addon',
            'type'       => 'object',
            'properties' => [
                'id'           => [
                    'description' => __( 'Unique identifier for the product addon.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'name'         => [
                    'description' => __( 'Product addon reference name.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                ],
                'priority'     => [
                    'description' => __( 'Display priority/order.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                ],
                'all_products' => [
                    'description' => __( 'Whether addon applies to all products.', 'dokan' ),
                    'type'        => 'boolean',
                    'context'     => [ 'view', 'edit' ],
                ],
                'categories'   => [
                    'description' => __( 'Product categories this addon applies to.', 'dokan' ),
                    'type'        => 'array',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'id'   => [
                                'description' => __( 'Category ID.', 'dokan' ),
                                'type'        => 'integer',
                            ],
                            'name' => [
                                'description' => __( 'Category name.', 'dokan' ),
                                'type'        => 'string',
                            ],
                            'slug' => [
                                'description' => __( 'Category slug.', 'dokan' ),
                                'type'        => 'string',
                            ],
                        ],
                    ],
                ],
                'field_count'  => [
                    'description' => __( 'Number of addon fields.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
            ],
        ];

        $this->schema = $schema;

        return $this->add_additional_fields_schema( $this->schema );
    }
}
