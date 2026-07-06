<?php

namespace WeDevs\DokanPro\REST;

use WC_REST_Product_Variations_Controller;
use WeDevs\DokanPro\Product\PayloadResolver;
use WeDevs\Dokan\Traits\VendorAuthorizable;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * REST API product variations controller for Dokan v2.
 *
 * Extends WooCommerce's variation controller with Dokan vendor
 * permission checks and hook integration.
 *
 * @since 5.0.0
 */
class ProductVariationControllerV2 extends WC_REST_Product_Variations_Controller {

    use VendorAuthorizable;

    /**
     * Whether the rest_pre_dispatch filter has already been registered.
     *
     * @since 5.0.0
     *
     * @var bool
     */
    private static $filter_registered = false;

    /**
     * Endpoint namespace.
     *
     * @since 5.0.0
     *
     * @var string
     */
    protected $namespace = 'dokan/v2';

    /**
     * Register the routes for product variations.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register_routes() {
        if ( ! self::$filter_registered ) {
            add_filter( 'rest_pre_dispatch', [ $this, 'resolve_variation_payload_before_validation' ], 1, 3 );
            self::$filter_registered = true;
        }

        // Collection: GET (list) + POST (create).
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                'args'   => [
                    'product_id' => [
                        'description' => __( 'Unique identifier for the variable product.', 'dokan' ),
                        'type'        => 'integer',
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_items' ],
                    'permission_callback' => [ $this, 'check_variation_permission' ],
                    'args'                => $this->get_collection_params(),
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'create_item' ],
                    'permission_callback' => [ $this, 'check_variation_permission' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );

        // Single: GET + PUT/PATCH + DELETE.
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            [
                'args'   => [
                    'product_id' => [
                        'description' => __( 'Unique identifier for the variable product.', 'dokan' ),
                        'type'        => 'integer',
                    ],
                    'id'         => [
                        'description' => __( 'Unique identifier for the variation.', 'dokan' ),
                        'type'        => 'integer',
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_item' ],
                    'permission_callback' => [ $this, 'check_variation_permission' ],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update_item' ],
                    'permission_callback' => [ $this, 'check_variation_permission' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'delete_item' ],
                    'permission_callback' => [ $this, 'check_variation_permission' ],
                    'args'                => [
                        'force' => [
                            'default'     => true,
                            'type'        => 'boolean',
                            'description' => __( 'Whether to bypass trash and force deletion.', 'dokan' ),
                        ],
                    ],
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );

        // Batch: PUT/PATCH.
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/batch',
            [
                'args'   => [
                    'product_id' => [
                        'description' => __( 'Unique identifier for the variable product.', 'dokan' ),
                        'type'        => 'integer',
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'batch_items' ],
                    'permission_callback' => [ $this, 'check_variation_permission' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );

        // Generate: POST (create all variation combinations from attributes).
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/generate',
            [
                'args' => [
                    'product_id'     => [
                        'description' => __( 'Unique identifier for the variable product.', 'dokan' ),
                        'type'        => 'integer',
                    ],
                    'delete'         => [
                        'description' => __( 'Deletes unused variations.', 'dokan' ),
                        'type'        => 'boolean',
                    ],
                    'default_values' => [
                        'description' => __( 'Default values for generated variations.', 'dokan' ),
                        'type'        => 'object',
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'generate' ],
                    'permission_callback' => [ $this, 'check_variation_permission' ],
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
    }

    /**
     * Resolve variation request body before WC schema validation.
     *
     * Runs PayloadResolver::variation_schema_to_wc_api() so the frontend can
     * send data using form field IDs and the server resolves types/keys.
     *
     * @since 5.0.0
     *
     * @param mixed           $result  Response to replace the short-circuit result with.
     * @param \WP_REST_Server $server  Server instance.
     * @param WP_REST_Request $request Request used to generate the response.
     *
     * @return mixed Unchanged result so dispatch continues; request body is modified in place.
     */
    public function resolve_variation_payload_before_validation( $result, $server, $request ) {
        $route  = trim( $request->get_route(), '/' );
        $prefix = trim( $this->namespace . '/products/\d+/variations', '/' );

        // Match create (dokan/v2/products/123/variations) or update (dokan/v2/products/123/variations/456).
        if ( ! preg_match( '#^' . $prefix . '(/\d+)?$#', $route ) ) {
            return $result;
        }

        $method = $request->get_method();
        if ( ! in_array( $method, [ 'POST', 'PUT', 'PATCH' ], true ) ) {
            return $result;
        }

        if ( ! class_exists( PayloadResolver::class ) ) {
            return $result;
        }

        $params   = $request->get_params();
        $resolved = PayloadResolver::resolve( $params );
        $request->set_body( wp_json_encode( $resolved ) );

        return $result;
    }

    /**
     * Check if the current user has permission to manage variations for a product.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return bool|WP_Error
     */
    public function check_variation_permission( $request ) {
        if ( ! current_user_can( 'dokandar' ) ) {
            return false;
        }

        $product_id = absint( $request['product_id'] );

        if ( ! $product_id ) {
            return new WP_Error(
                'dokan_rest_missing_product_id',
                __( 'Product ID is required.', 'dokan' ),
                [ 'status' => 400 ]
            );
        }

        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            return new WP_Error(
                'dokan_rest_product_invalid_id',
                __( 'Invalid product ID.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        $author_id = (int) get_post_field( 'post_author', $product_id );

        if ( $author_id !== get_current_user_id() && ! current_user_can( 'manage_woocommerce' ) ) {
            return new WP_Error(
                'dokan_rest_cannot_manage_variation',
                __( 'You do not have permission to manage this product\'s variations.', 'dokan' ),
                [ 'status' => 403 ]
            );
        }

        return true;
    }

    /**
     * Prepare objects query.
     *
     * Forces variations to be sorted by menu_order (ASC) then ID (ASC),
     * matching WooCommerce admin behavior.
     *
     * @since 5.0.0
     *
     * @param array           $prepared_args Prepared arguments.
     * @param WP_REST_Request $request       Request object.
     *
     * @return array
     */
    protected function prepare_objects_query( $request ) {
        $args = parent::prepare_objects_query( $request );

        $args['orderby'] = [
            'menu_order' => 'ASC',
            'ID'         => 'ASC',
        ];

        return $args;
    }

    /**
     * Prepare a single variation for response.
     *
     * Keeps all original WC response data and transforms the attributes
     * to the FormSchema format (with options and selected_value).
     *
     * @since 5.0.0
     *
     * @param \WC_Product_Variable     $product  Product data.
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response
     */
    public function prepare_object_for_response( $product, $request ) {
        $response       = parent::prepare_object_for_response( $product, $request );
        $parent_product = wc_get_product( $product->get_parent_id() );

        if ( ! $parent_product ) {
            return $response;
        }

        $data             = $response->get_data();
        $attribute_values = $product->get_attributes( 'edit' );
        $formatted_attrs  = [];

        foreach ( $parent_product->get_attributes( 'edit' ) as $attribute ) {
            if ( ! $attribute->get_variation() ) {
                continue;
            }

            $attr_key     = sanitize_title( $attribute->get_name() );
            $selected_val = $attribute_values[ $attr_key ] ?? '';
            $options      = [];
            $selected     = null;

            if ( $attribute->is_taxonomy() ) {
                foreach ( $attribute->get_terms() as $term ) {
                    $opt = [
                        'value' => $term->slug,
                        'label' => apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute->get_name(), $parent_product ),
                    ];
                    $options[] = $opt;
                    if ( $selected_val === $opt['value'] ) {
                        $selected = $opt;
                    }
                }
            } else {
                foreach ( $attribute->get_options() as $option ) {
                    $opt = [
                        'value' => $option,
                        'label' => apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute->get_name(), $parent_product ),
                    ];
                    $options[] = $opt;
                    if ( $selected_val === $opt['value'] ) {
                        $selected = $opt;
                    }
                }
            }

            $formatted_attrs[] = [
                'label'          => wc_attribute_label( $attribute->get_name() ),
                'value'          => $attr_key,
                'selected_value' => $selected,
                'options'        => $options,
            ];
        }

        $data['attributes'] = $formatted_attrs;
        $response->set_data( $data );

        return $response;
    }

    /**
     * Create a single variation.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function create_item( $request ) {
        $response = parent::create_item( $request );

        if ( ! is_wp_error( $response ) ) {
            $variation_id = (int) $response->data['id'];
            $object       = wc_get_product( $variation_id );

            do_action( "dokan_rest_insert_{$this->post_type}_object", $object, $request, true );
        }

        return $response;
    }

    /**
     * Update a single variation.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function update_item( $request ) {
        $response = parent::update_item( $request );

        if ( ! is_wp_error( $response ) ) {
            $variation_id = (int) $response->data['id'];
            $object       = wc_get_product( $variation_id );

            do_action( "dokan_rest_insert_{$this->post_type}_object", $object, $request, false );
        }

        return $response;
    }

    /**
     * Delete a single variation.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function delete_item( $request ) {
        $variation_id = absint( $request['id'] );
        $object       = wc_get_product( $variation_id );
        $response     = parent::delete_item( $request );

        if ( ! is_wp_error( $response ) && $object ) {
            do_action( "dokan_rest_delete_{$this->post_type}_object", $object, $response, $request );
        }

        return $response;
    }
}
