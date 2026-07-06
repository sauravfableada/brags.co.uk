<?php
namespace WeDevs\DokanPro\Modules\RequestForQuotation\Api;

use WeDevs\DokanPro\Modules\RequestForQuotation\Model\Quote;
use WeDevs\DokanPro\Modules\RequestForQuotation\SettingsHelper;
use WP_Error;
use WC_Product;
use WP_REST_Server;
use WC_Product_Variation;
use WP_HTTP_Response;
use WP_REST_Response;
use WC_Data_Exception;
use Exception;
use WeDevs\Dokan\Abstracts\DokanRESTController;
use WeDevs\DokanPro\Modules\RequestForQuotation\Helper;

/**
 * Request A Quote Controller Class
 */
class RequestForQuotationController extends DokanRESTController {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'dokan/v1';

    /**
     * Route name
     *
     * @var string
     */
    protected $base = 'request-for-quote';

    /**
     * Quote status
     *
     * @since 3.7.30
     *
     * @var array
     */
    protected const QUOTE_STATUS = [
        'UPDATED'   => Quote::STATUS_UPDATE,
        'PENDING'   => Quote::STATUS_PENDING,
        'APPROVED'  => Quote::STATUS_APPROVED,
        'CONVERTED' => Quote::STATUS_CONVERTED,
    ];

    /**
     * Class constructor
     *
     * @since 3.7.4
     */
    public function __construct() {
        add_filter( 'dokan_rest_product_object_query', [ $this, 'remove_author_arg' ], 10, 2 );
    }

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
                    'callback'            => [ $this, 'get_dokan_request_quotes' ],
                    'args'                => $this->get_collection_params(),
                    'permission_callback' => [ $this, 'get_request_quote_permissions_check' ],
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'create_request_quote' ],
                    'permission_callback' => [ $this, 'create_request_quote_permissions_check' ],
                ],
                'schema' => [ $this, 'get_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace, '/' . $this->base . '/(?P<id>[\d]+)/', [
                'args' => [
                    'id' => [
                        'description' => __( 'Unique identifier for the object.', 'dokan' ),
                        'type'        => 'integer',
                    ],
                ],

                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_dokan_request_single_quote' ],
                    'permission_callback' => [ $this, 'get_request_quote_permissions_check' ],
                ],

                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update_request_quote' ],
                    'permission_callback' => [ $this, 'get_request_quote_permissions_check' ],
                ],

                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'delete_request_quote' ],
                    'permission_callback' => [ $this, 'get_request_quote_permissions_check' ],
                ],

            ]
        );

        register_rest_route(
            $this->namespace, '/' . $this->base . '/(?P<id>[\d]+)/restore', [
                'args' => [
                    'id' => [
                        'description' => __( 'Unique identifier for the object.', 'dokan' ),
                        'type'        => 'integer',
                    ],
                ],

                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'restore_request_quote' ],
                    'permission_callback' => [ $this, 'restore_request_quote_permissions_check' ],
                ],
            ]
        );
        register_rest_route(
            $this->namespace, '/' . $this->base . '/batch', [
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'request_quote_batch_items' ],
                    'permission_callback' => [ $this, 'batch_items_permissions_check' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                ],
            ]
        );
        register_rest_route(
            $this->namespace, '/' . $this->base . '/convert-to-order', [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'convert_to_order' ],
                    'permission_callback' => [ $this, 'convert_to_order_permissions_check' ],
                    'args' => [
                        'quote_id' => [
                            'description'       => __( 'Request quote id', 'dokan' ),
                            'type'              => 'number',
                            'sanitize_callback' => 'absint',
                            'required'          => true,
                        ],
                        'status' => [
                            'description'       => __( 'Request quote status', 'dokan' ),
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                            'required'          => true,
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Get single object
     *
     * @since 3.6.0
     *
     * @return array|null
     */
    public function get_quote_object_by_id( $quote_id ) {
        return Helper::get_request_quote_by_id( $quote_id );
    }

    /**
     * Get all request_quote
     *
     * @since 3.6.0
     *
     * @return object
     */
    public function get_dokan_request_quotes( $request ) {
        $status  = ( empty( $request['status'] ) || $request['status'] === 'all' ) ? '' : $request['status'];
        $limit   = empty( $request['per_page'] ) ? 10 : $request['per_page'];
        $orderby = empty( $request['orderby'] ) ? 'id' : $request['orderby'];
        $order   = empty( $request['order'] ) ? 'DESC' : $request['order'];
        $offset  = empty( $request['page'] ) ? 1 : ( $request['page'] - 1 ) * $limit;
        $args    = [
            'posts_per_page' => $limit,
            'offset'         => $offset,
            'status'         => $status,
            'order'          => $order,
            'orderby'        => $orderby,
        ];

        if ( ! empty( $request['customer'] ) ) {
            $args['customer_name'] = sanitize_text_field( $request['customer'] );
        }

        if ( ! empty( $request['date']['from_date'] ) && ! empty( $request['date']['to_date'] ) ) {
            $args['date_from'] = esc_html( $request['date']['from_date'] );
            $args['date_to']   = esc_html( $request['date']['to_date'] );
        }

        $data   = Helper::get_request_quote( $args );
        $result = [];
        if ( ! empty( $data ) ) {
            foreach ( $data as $key => $value ) {
                $res      = $this->prepare_response_for_object( $value, $request );
                $result[] = $this->prepare_response_for_collection( $res );
            }
        }

        $count      = Helper::get_request_quote_count();
        $response   = rest_ensure_response( $result );
        $post_count = array_reduce(
            (array) $count,
            function ( $total, $status_count ) {
                return $total + $status_count;
            },
            0
        );

        $response->header( 'X-Status-All', $post_count );
        $response->header( 'X-Status-Publish', $count->publish );
        $response->header( 'X-Status-Approved', $count->approve );
        $response->header( 'X-Status-Pending', $count->pending );
        $response->header( 'X-Status-Trash', $count->trash );
        $response->header( 'X-Status-Draft', $count->draft );
        $response->header( 'X-Status-Updated', $count->updated );
        $response->header( 'X-Status-Accepted', $count->accepted );
        $response->header( 'X-Status-Rejected', $count->reject );
        $response->header( 'X-Status-Expired', $count->expired );
        $response->header( 'X-Status-Cancelled', $count->cancel );
        $response->header( 'X-Status-Converted', $count->converted );
        $response->header( 'X-Status-Future', $count->future );

        return $this->format_collection_response( $response, $request, $post_count );
    }

    /**
     * Get taxonomy terms.
     *
     * @param WC_Product $product  Product instance.
     * @param string     $taxonomy Taxonomy slug.
     *
     * @return array
     */
    protected function get_taxonomy_terms( $product, $taxonomy = 'cat' ) {
        $terms = [];

        foreach ( wc_get_object_terms( $product->get_id(), 'product_' . $taxonomy ) as $term ) {
            $terms[] = [
                'id'   => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            ];
        }

        return $terms;
    }

    /**
     * Get the images for a product or product variation.
     *
     * @param WC_Product|WC_Product_Variation $product Product instance.
     *
     * @return array
     */
    protected function get_images( $product ) {
        $images         = [];
        $attachment_ids = [];

        // Add featured image.
        if ( has_post_thumbnail( $product->get_id() ) ) {
            $attachment_ids[] = $product->get_image_id();
        }

        // Add gallery images.
        $attachment_ids = array_merge( $attachment_ids, $product->get_gallery_image_ids() );

        // Build image data.
        foreach ( $attachment_ids as $position => $attachment_id ) {
            $attachment_post = get_post( $attachment_id );
            if ( is_null( $attachment_post ) ) {
                continue;
            }

            $attachment = wp_get_attachment_image_src( $attachment_id, 'full' );
            if ( ! is_array( $attachment ) ) {
                continue;
            }

            $images[] = [
                'id'                => (int) $attachment_id,
                'date_created'      => wc_rest_prepare_date_response( $attachment_post->post_date, false ),
                'date_created_gmt'  => wc_rest_prepare_date_response( strtotime( $attachment_post->post_date_gmt ) ),
                'date_modified'     => wc_rest_prepare_date_response( $attachment_post->post_modified, false ),
                'date_modified_gmt' => wc_rest_prepare_date_response( strtotime( $attachment_post->post_modified_gmt ) ),
                'src'               => current( $attachment ),
                'name'              => get_the_title( $attachment_id ),
                'alt'               => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
                'position'          => (int) $position,
            ];
        }

        // Set a placeholder image if the product has no images set.
        if ( empty( $images ) ) {
            $images[] = [
                'id'                => 0,
                'date_created'      => wc_rest_prepare_date_response( dokan_current_datetime()->format( 'Y-m-d H:i:s' ), false ),
                // Default to now.
                'date_created_gmt'  => wc_rest_prepare_date_response( time() ),
                // Default to now.
                'date_modified'     => wc_rest_prepare_date_response( dokan_current_datetime()->format( 'Y-m-d H:i:s' ), false ),
                'date_modified_gmt' => wc_rest_prepare_date_response( time() ),
                'src'               => wc_placeholder_img_src(),
                'name'              => __( 'Placeholder', 'dokan' ),
                'alt'               => __( 'Placeholder', 'dokan' ),
                'position'          => 0,
            ];
        }

        return $images;
    }

    /**
     * Get product attribute taxonomy name.
     *
     * @since  3.6.0
     *
     * @param WC_Product $product Product data.
     *
     * @param string     $slug    Taxonomy name.
     *
     * @return string
     */
    protected function get_attribute_taxonomy_name( $slug, $product ) {
        $attributes = $product->get_attributes();

        if ( ! isset( $attributes[ $slug ] ) ) {
            return str_replace( 'pa_', '', $slug );
        }

        $attribute = $attributes[ $slug ];

        // Taxonomy attribute name.
        if ( $attribute->is_taxonomy() ) {
            $taxonomy = $attribute->get_taxonomy_object();

            return $taxonomy->attribute_label;
        }

        // Custom product attribute name.
        return $attribute->get_name();
    }

    /**
     * Get default attributes.
     *
     * @param WC_Product $product Product instance.
     *
     * @return array
     */
    protected function get_default_attributes( $product ) {
        $default = [];
        if ( ! $product->is_type( 'variable' ) ) {
            return $default;
        }

        foreach ( array_filter( (array) $product->get_default_attributes(), 'strlen' ) as $key => $value ) {
            if ( 0 === strpos( $key, 'pa_' ) ) {
                $default[] = [
                    'id'     => wc_attribute_taxonomy_id_by_name( $key ),
                    'name'   => $this->get_attribute_taxonomy_name( $key, $product ),
                    'option' => $value,
                ];
            } else {
                $default[] = [
                    'id'     => 0,
                    'name'   => $this->get_attribute_taxonomy_name( $key, $product ),
                    'option' => $value,
                ];
            }
        }

        return $default;
    }

    /**
     * Get attribute options.
     *
     * @param int   $product_id Product ID.
     * @param array $attribute  Attribute data.
     *
     * @return array
     */
    protected function get_attribute_options( $product_id, $attribute ) {
        if ( isset( $attribute['is_taxonomy'] ) && $attribute['is_taxonomy'] ) {
            return wc_get_product_terms(
                $product_id, $attribute['name'], [
                    'fields' => 'names',
                ]
            );
        } elseif ( isset( $attribute['value'] ) ) {
            return array_map( 'trim', explode( '|', $attribute['value'] ) );
        }

        return [];
    }

    /**
     * Get the attributes for a product or product variation.
     *
     * @param WC_Product|WC_Product_Variation $product Product instance.
     *
     * @return array
     */
    protected function get_attributes( $product ) {
        $attributes = [];

        if ( ! $product->is_type( 'variation' ) ) {
            foreach ( $product->get_attributes() as $attribute ) {
                $attributes[] = [
                    'id'        => $attribute['is_taxonomy'] ? wc_attribute_taxonomy_id_by_name( $attribute['name'] ) : 0,
                    'slug'      => $attribute['name'],
                    'name'      => $this->get_attribute_taxonomy_name( $attribute['name'], $product ),
                    'position'  => (int) $attribute['position'],
                    'visible'   => (bool) $attribute['is_visible'],
                    'variation' => (bool) $attribute['is_variation'],
                    'options'   => $this->get_attribute_options( $product->get_id(), $attribute ),
                ];
            }

            return $attributes;
        }

        $_product = wc_get_product( $product->get_parent_id() );
        foreach ( $product->get_variation_attributes() as $attribute_name => $attribute ) {
            $name = str_replace( 'attribute_', '', $attribute_name );

            if ( ! $attribute ) {
                continue;
            }

            // Taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`.
            if ( 0 === strpos( $attribute_name, 'attribute_pa_' ) ) {
                $option_term  = get_term_by( 'slug', $attribute, $name );
                $attributes[] = [
                    'id'     => wc_attribute_taxonomy_id_by_name( $name ),
                    'slug'   => $attribute_name,
                    'name'   => $this->get_attribute_taxonomy_name( $name, $_product ),
                    'option' => $option_term && ! is_wp_error( $option_term ) ? $option_term->name : $attribute,
                ];
            } else {
                $attributes[] = [
                    'id'     => 0,
                    'slug'   => $attribute_name,
                    'name'   => $this->get_attribute_taxonomy_name( $name, $_product ),
                    'option' => $attribute,
                ];
            }
        }

        return $attributes;
    }

    /**
     * Get the downloads for a product or product variation.
     *
     * @param WC_Product|WC_Product_Variation $product Product instance.
     *
     * @return array
     */
    protected function get_downloads( $product ) {
        $downloads = [];

        if ( $product->is_downloadable() ) {
            foreach ( $product->get_downloads() as $file_id => $file ) {
                $downloads[] = [
                    'id'   => $file_id, // MD5 hash.
                    'name' => $file['name'],
                    'file' => $file['file'],
                ];
            }
        }

        return $downloads;
    }

    /**
     * Get single request_quote object
     *
     * @since 3.6.0
     *
     * @return \WP_Error
     */
    public function get_dokan_request_single_quote( $request ) {
        $quote_id = $request['id'];

        if ( empty( $quote_id ) ) {
            return new WP_Error( 'no_qupte_found', __( 'No quote found', 'dokan' ), [ 'status' => 404 ] );
        }

        $data['quote']         = (object) $this->get_quote_object_by_id( (int) $quote_id );
        $data['quote_details'] = Helper::get_request_quote_details_by_quote_id( $quote_id );
        $result                = [];
        if ( ! empty( $data ) ) {
            $res    = $this->prepare_response_for_single_quote_object( (object) $data, $request );
            $result = $this->prepare_response_for_collection( $res );
        }

        return rest_ensure_response( $result );
    }

    /**
     * Create request_quote
     *
     * @since 3.6.0
     *
     * @return \WP_Error
     */
    public function create_request_quote( $request ) {
        $params      = $request->get_params();
        $product_ids = ! empty( $params['product_ids'] ) ? $params['product_ids'] : '';
        $quantity    = ! empty( $params['offer_product_quantity'] ) ? $params['offer_product_quantity'] : '';
        $offer_price = ! empty( $params['offer_price'] ) ? $params['offer_price'] : '';

        if ( empty( $product_ids ) ) {
            return new WP_Error( 'no_quote_added', esc_html__( 'No quote product found to add.', 'dokan' ), [ 'status' => 404 ] );
        }

        if ( empty( $params['quote_title'] ) ) {
            return new \WP_Error( 'no-quote-name', esc_html__( 'You must provide a name.', 'dokan' ) );
        }

        if ( empty( $params['customer_info'] ) ) {
            return new \WP_Error( 'no-customer-info', esc_html__( 'You must provide customers information.', 'dokan' ) );
        }

        if ( empty( $params['store_info']['store_id'] ) ) {
            return new \WP_Error( 'no-store-info', esc_html__( 'You must provide store information.', 'dokan' ) );
        }

        $post_authors           = [];
        $rearranged_product_ids = [];
        foreach ( $product_ids as $product_id ) {
            $author_id                              = dokan_get_vendor_by_product( $product_id, true );
            $rearranged_product_ids[ $author_id ][] = $product_id;
            if ( ! in_array( $author_id, $post_authors, true ) ) {
                $post_authors[] = $author_id;
            }
        }

        $data = [];
        foreach ( $post_authors as $post_author ) {
            $request_quote = Helper::create_request_quote( $params );

            if ( is_wp_error( $request_quote ) ) {
                return new WP_Error( $request_quote->get_error_code(), $request_quote->get_error_message(), [ 'status' => 404 ] );
            }

            if ( ! empty( $rearranged_product_ids ) ) {
                $quote_details['quote_id'] = $request_quote;
                foreach ( $rearranged_product_ids[ $post_author ] as $key => $product_id ) {
                    $quote_details['product_id']  = $product_id;
                    $quote_details['quantity']    = $quantity[ $key ];
                    $quote_details['offer_price'] = $offer_price[ $key ];

                    Helper::create_request_quote_details( $quote_details );
                }
                do_action( 'after_dokan_request_quote_inserted', $request_quote );
            }

            $data[] = $this->prepare_response_for_object( (object) $this->get_quote_object_by_id( (int) $request_quote ), $request );
        }

        return rest_ensure_response( $data );
    }

    /**
     * Create request_quote
     *
     * @since 3.6.0
     *
     * @return  WP_Error | WP_HTTP_Response | WP_REST_Response
     *
     * @throws WC_Data_Exception | Exception
     */
    public function convert_to_order( $request ) {
        $quote_id = absint( $request->get_param( 'quote_id' ) );
        $quote_status = $request->get_param( 'status' ) ?? self::QUOTE_STATUS['PENDING'];

        // Request Validation
        if ( empty( $quote_id ) ) {
            return new WP_Error( 'no_quote_found', __( 'No quote found', 'dokan' ), [ 'status' => 404 ] );
        } elseif ( ! in_array( $quote_status, array_values( self::QUOTE_STATUS ), true ) ) {
            return new WP_Error( 'invalid_status_for_quote', __( 'Invalid Status', 'dokan' ), [ 'status' => 422 ] );
        }

        $quote         = (object) $this->get_quote_object_by_id( $quote_id );
        $quote_details = Helper::get_request_quote_details_by_quote_id( $quote_id );

        if ( empty( $quote_details ) ) {
            return new WP_Error( 'no_quote_found', __( 'No quote found', 'dokan' ), [ 'status' => 404 ] );
        }

        $order_id = Helper::convert_quote_to_order( $quote, $quote_details );

        Helper::change_status( 'dokan_request_quotes', $quote_id, $quote_status );
        Helper::update_dokan_request_quote_converted( $quote_id, 'Admin', $order_id );

        $data = $this->prepare_response_for_object( (object) $this->get_quote_object_by_id( $quote_id ), $request );

        return rest_ensure_response( $data );
    }

    /**
     * Update request_quote
     *
     * @since 3.6.0
     *
     * @param \WP_REST_Request $request Request object.
     *
     * @return \WP_Error
     */
    public function update_request_quote( $request ) {
        if ( empty( trim( $request['id'] ) ) ) {
            return new WP_Error( 'no_id', __( 'No quote id found', 'dokan' ), [ 'status' => 404 ] );
        }

        $quote_id                = trim( $request['id'] );
        $params                  = $request->get_params();
        $params['user_id']       = ! empty( $params['user_id'] ) ? $params['user_id'] : 0;
        $params['customer_info'] = ! empty( $params['customer_info'] ) ? maybe_serialize( $params['customer_info'] ) : 0;
        $quantity                = ! empty( $params['offer_product_quantity'] ) ? $params['offer_product_quantity'] : '';
        $product_ids             = ! empty( $params['product_ids'] ) ? $params['product_ids'] : '';
        $offer_price             = ! empty( $params['offer_price'] ) ? $params['offer_price'] : '';

        if ( empty( $product_ids ) ) {
            return new WP_Error( 'no_quote_added', __( 'No quote product found to add.', 'dokan' ), [ 'status' => 404 ] );
        }

        $post_authors = [];
        foreach ( $product_ids as $product_id ) {
            $author_id = dokan_get_vendor_by_product( $product_id, true );
            if ( ! in_array( $author_id, $post_authors, true ) ) {
                $post_authors[] = $author_id;
            }
        }

        if ( count( $post_authors ) > 1 ) {
            Helper::delete( 'quotes', $quote_id, 'id', true );
            Helper::delete( 'quote_details', $quote_id, 'quote_id', true );

            return $this->create_request_quote( $request );
        }

        $request_quote = Helper::update_request_quote( $quote_id, $params );

        if ( is_wp_error( $request_quote ) ) {
            return new WP_Error( $request_quote->get_error_code(), $request_quote->get_error_message(), [ 'status' => 404 ] );
        }

        $old_quote_details = Helper::get_request_quote_details_by_quote_id( $quote_id );
        Helper::delete( 'quote_details', $quote_id, 'quote_id', true );
        $quote_details['quote_id'] = $quote_id;
        foreach ( $product_ids as $key => $product_id ) {
            $quote_details['product_id']  = $product_id;
            $quote_details['quantity']    = $quantity[ $key ];
            $quote_details['offer_price'] = $offer_price[ $key ];

            Helper::create_request_quote_details( $quote_details );
        }

        $new_quote_details = Helper::get_request_quote_details_by_quote_id( $quote_id );
        if ( empty( $new_quote_details ) ) {
            Helper::delete( 'quotes', $quote_id, 'id', true );

            return new WP_Error( 'deleted-successfully', __( 'Quote deleted successfully.', 'dokan' ) );
        }

        do_action( 'after_dokan_request_quote_updated', $quote_id, $old_quote_details, $new_quote_details );

        $data = $this->prepare_response_for_object( (object) $this->get_quote_object_by_id( (int) $quote_id ), $request );

        return rest_ensure_response( $data );
    }

    /**
     * Delete request_quote
     *
     * @since 3.6.0
     *
     * @return array|\WP_Error
     */
    public function delete_request_quote( $request ) {
        $request_quote = $this->get_quote_object_by_id( $request['id'] );

        if ( is_wp_error( $request_quote ) ) {
            return $request_quote;
        }

        $id       = $request_quote->id;
        $force    = ! empty( $request['force'] ) ? (bool) $request['force'] : false;
        $previous = $this->prepare_response_for_object( $request_quote, $request );

        // If we're forcing, then delete permanently.
        $result = Helper::delete( 'quotes', $id, 'id', $force );

        if ( ! $result ) {
            return new WP_Error( 'dokan_rest_cannot_delete', __( 'The quote cannot be deleted.', 'dokan' ), [ 'status' => 500 ] );
        }

        return rest_ensure_response( $previous );
    }

    /**
     * Restore request_quote
     *
     * @since 3.6.0
     *
     * @return array|\WP_Error
     */
    public function restore_request_quote( $request ) {
        if ( empty( trim( $request['id'] ) ) ) {
            return new WP_Error( 'no_id', __( 'No quote id found', 'dokan' ), [ 'status' => 404 ] );
        }

        $request_quote = $this->get_quote_object_by_id( $request['id'] );

        if ( is_wp_error( $request_quote ) ) {
            return $request_quote;
        }

        $update = Helper::change_status( 'dokan_request_quotes', $request['id'] );

        if ( ! $update ) {
            return new WP_Error( 'dokan_rest_cannot_delete', __( 'The quote not updated.', 'dokan' ), [ 'status' => 500 ] );
        }

        return $this->prepare_response_for_object( $request_quote, $request );
    }

    /**
     * Trash, delete and restore bulk action
     *
     * JSON data format for sending to API
     *     {
     *         "trash" : [
     *             "1", "9", "7"
     *         ],
     *         "delete" : [
     *             "2"
     *         ],
     *         "restore" : [
     *             "4"
     *         ]
     *     }
     *
     * @since 3.6.0
     *
     * @return bool|\WP_Error
     */
    public function request_quote_batch_items( $request ) {
        $params = $request->get_params();

        if ( empty( $params['action'] ) ) {
            return new WP_Error( 'bulk_action_not_found', __( 'No action found for bulk updating', 'dokan' ), [ 'status' => 404 ] );
        }

        if ( empty( $params['items'] ) ) {
            return new WP_Error( 'item_not_found', __( 'No item found for bulk updating', 'dokan' ), [ 'status' => 404 ] );
        }

        $status         = $params['action'];
        $quote_items    = $params['items'];
        $allowed_status = [
            Quote::STATUS_PENDING,
            Quote::STATUS_APPROVED,
            Quote::STATUS_TRASH,
            Quote::STATUS_DELETE,
            Quote::STATUS_RESTORE,
        ];

        foreach ( $quote_items as $quote_id ) {
            if ( ! in_array( $status, $allowed_status, true ) ) {
                continue;
            }

            switch ( $status ) {
                case Quote::STATUS_PENDING:
                    Helper::change_status( 'dokan_request_quotes', $quote_id );
                    break;
                case Quote::STATUS_APPROVED:
                    Helper::change_status( 'dokan_request_quotes', $quote_id, 'approve' );
                    break;
                case Quote::STATUS_TRASH:
                    Helper::delete( 'quotes', $quote_id, 'id' );
                    break;
                case Quote::STATUS_DELETE:
                    Helper::delete( 'quotes', $quote_id, 'id', true );
                    Helper::delete( 'quote_details', $quote_id, 'id', true );
                    break;
                case Quote::STATUS_RESTORE:
                    Helper::change_status( 'dokan_request_quotes', $quote_id );
                    break;
            }
        }

        return true;
    }

    /**
     * Get request_quote permissions check
     *
     * @since 3.6.0
     *
     * @return bool
     */
    public function get_request_quote_permissions_check() {
        return user_can( get_current_user_id(), 'manage_options' );
    }

    /**
     * Get restore request_quote permissions check
     *
     * @since 3.6.0
     *
     * @return bool
     */
    public function restore_request_quote_permissions_check() {
        return user_can( get_current_user_id(), 'manage_options' );
    }

    /**
     * Get restore request_quote permissions check
     *
     * @since 3.6.0
     *
     * @return bool
     */
    public function batch_items_permissions_check() {
        return user_can( get_current_user_id(), 'manage_options' );
    }

    /**
     * Get restore request_quote permissions check
     *
     * @since 3.6.0
     *
     * @return bool
     */
    public function convert_to_order_permissions_check() {
        return user_can( get_current_user_id(), 'manage_options' );
    }

    /**
     * Create request_quote permissions check
     *
     * @since 3.6.0
     *
     * @return bool
     */
    public function create_request_quote_permissions_check() {
        return user_can( get_current_user_id(), 'manage_options' );
    }

    /**
     * Prepare data for response
     *
     * @since 3.6.0
     *
     * @param mixed            $quote
     * @param \WP_REST_Request $request Request object.
     *
     * @return array
     */
    public function prepare_response_for_object( $quote, $request ) {
        $user_info     = ! empty( $quote->customer_info ) ? maybe_unserialize( $quote->customer_info ) : [];
        $store_info    = ! empty( $quote->store_info ) ? maybe_unserialize( $quote->store_info ) : [];
        $store_id      = ! empty( $store_info['store_id'] ) ? absint( $store_info['store_id'] ) : '';
        $store_name    = ! empty( $store_id ) ? dokan()->vendor->get( $store_id )->get_shop_name() : '';
        $customer_name = $user_info['name_field'] ?? '';
        $data          = [
            'id'            => $quote->id,
            'user_id'       => absint( $quote->user_id ?? 0 ),
            'quote_title'   => $quote->quote_title ?? '',
            'status'        => $quote->status,
            // Calculate the difference in human-readable format.
            'created_at'    => human_time_diff( $quote->created_at, time() ) . ' ' . __( 'ago', 'dokan' ),
            'order_url'     => $quote->order_id ? get_edit_post_link( $quote->order_id, false ) : '',
            'store_name'    => $store_name,
            'customer_name' => $customer_name,
        ];

        $response = rest_ensure_response( $data );
        $response->add_links( $this->prepare_links( $quote, $request ) );

        return apply_filters( 'dokan_rest_prepare_request_quote_object', $response, $quote, $request );
    }

    /**
     * Prepare data for response
     *
     * @since 3.6.0
     *
     * @param mixed            $quote_request
     * @param \WP_REST_Request $request       Request object.
     *
     * @return void|array
     */
    public function prepare_response_for_single_quote_object( $quote_request, $request ) {
        if ( empty( $quote_request->quote->id ) ) {
            return;
        }

        $user_info               = ( ! empty( $quote_request->quote->user_id ) && $quote_request->quote->user_id > 0 ) ? get_userdata( $quote_request->quote->user_id ) : [];
        $customer['user_id']     = ( ! empty( $quote_request->quote->user_id ) && $quote_request->quote->user_id > 0 ) ? $quote_request->quote->user_id : 0;
        $customer['user_login']  = ! empty( $user_info ) ? $user_info->data->user_login : '';
        $customer['user_email']  = ! empty( $user_info ) ? $user_info->data->user_email : '';
        $customer['user_avatar'] = get_avatar_url( $customer['user_id'] );

        $products = [];
        foreach ( $quote_request->quote_details as $quote_detail ) {
            $product_obj = wc_get_product( $quote_detail->product_id );
            if ( ! $product_obj ) {
                continue;
            }

            $product                        = $product_obj->get_data();
            $data['id']                     = $product['id'];
            $data['images'][0]['src']       = get_the_post_thumbnail_url( $quote_detail->product_id );
            $data['permalink']              = get_permalink( $quote_detail->product_id );
            $data['name']                   = $product['name'];
            $data['store']['name']          = dokan_get_vendor_by_product( $quote_detail->product_id )->get_shop_name();
            $data['price']                  = $product['price'];
            $data['offer_price']            = $quote_detail->offer_price;
            $data['offer_product_quantity'] = $quote_detail->quantity;
            $products[]                     = $data;
        }
        $store_info    = ! empty( $quote_request->quote->store_info ) ? maybe_unserialize( $quote_request->quote->store_info ) : [];
        $customer_info = ! empty( $quote_request->quote->customer_info ) ? maybe_unserialize( $quote_request->quote->customer_info ) : [];

        if ( ! empty( $store_info['store_id'] ) ) {
            $store_info['user_avatar'] = get_avatar_url( $store_info['store_id'] );
        }

        $data = [
            'quote_id'       => $quote_request->quote->id,
            'title'          => $quote_request->quote->quote_title,
            'store_info'     => $store_info,
            'customer_info'  => $customer_info,
            'customer'       => $customer,
            'products'       => $products,
            'quote_details'  => $quote_request->quote_details,
            'status'         => $quote_request->quote->status,
            'shipping_cost'  => $quote_request->quote->shipping_cost ?? 0.00,
            'show_converter' => SettingsHelper::is_quote_converter_display_enabled(),
            'converted_by'   => $quote_request->quote->converted_by,
        ];

        $expiry_rules = Helper::get_quote_expiry_rules();
        if ( ! empty( $expiry_rules['expiry_date'] ) ) {
            $data['expiry_days']           = absint( $expiry_rules['expiry_date'] );
            $data['enable_expiry_date']    = ! empty( $expiry_rules['enable_expiry_date'] ) ? filter_var( $expiry_rules['enable_expiry_date'], FILTER_VALIDATE_BOOLEAN ) : false;
            $data['expiry_formatted_date'] = ! empty( $quote_request->quote->expiry_date ) ? dokan_current_datetime()->setTimestamp( $quote_request->quote->expiry_date )->format( 'jS M Y' ) : '';
        }

        if ( ! empty( $quote_request->quote->order_id ) ) {
            $data['order_url'] = get_edit_post_link( $quote_request->quote->order_id, false );
        }

        if ( ! empty( $quote_request->quote->expected_date ) ) {
            $data['expected_date'] = dokan_current_datetime()->setTimestamp( $quote_request->quote->expected_date )->format( 'Y-m-d' );
        }

        $response = rest_ensure_response( $data );
        // Make quote details id to quote id to prepare links.
        $quote_request->id = $request['id'];
        $response->add_links( $this->prepare_links( $quote_request, $request ) );

        return apply_filters( 'dokan_rest_prepare_request_single_quote_object', $response, $quote_request, $request );
    }

    /**
     * Prepare links for the request.
     *
     * @param mixed            $quote_object  Object data.
     * @param \WP_REST_Request $request       Request object.
     *
     * @return array Links for the given post.
     */
    protected function prepare_links( $quote_object, $request ) {
        return [
            'self'       => [
                'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->base, is_a( $quote_object, 'WC_Product' ) ? $quote_object->get_id() : $quote_object->id ) ),
            ],
            'collection' => [
                'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->base ) ),
            ],
        ];
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

    /**
     * Retrieves the contact schema, conforming to JSON Schema.
     *
     * @since 3.6.0
     *
     * @return array
     */
    public function get_item_schema() {
        if ( $this->schema ) {
            return $this->add_additional_fields_schema( $this->schema );
        }

        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'contact',
            'type'       => 'object',
            'properties' => [
                'id'            => [
                    'description' => __( 'Unique identifier for the object.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'user_id'       => [
                    'description' => __( 'Unique identifier for the user.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'required'    => true,
                ],
                'order_id'      => [
                    'description' => __( 'Unique identifier for the order.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                ],
                'quote_title'   => [
                    'description' => __( 'Title of the quote.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'required'    => true,
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
                'status'        => [
                    'description' => __( 'Status of the quote.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
                'customer_info' => [
                    'description' => __( 'Customer info', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'arg_options' => [
                        'sanitize_callback' => [ 'array_map' => 'sanitize_text_field' ],
                    ],
                ],
                'created_at'    => [
                    'description' => __( "The date the object was published, in the site's timezone.", 'dokan' ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
            ],
        ];

        $this->schema = $schema;

        return $this->add_additional_fields_schema( $this->schema );
    }

    /**
     * Retrieves the contact schema, conforming to JSON Schema.
     *
     * @since 3.6.0
     *
     * @return array
     */
    public function get_product_item_schema() {
        if ( $this->schema ) {
            return $this->add_additional_fields_schema( $this->schema );
        }

        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'contact',
            'type'       => 'object',
            'properties' => [
                'id'          => [
                    'description' => __( 'Unique identifier for the object.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'quote_id'    => [
                    'description' => __( 'Unique identifier for the Request Quote.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'required'    => true,
                ],
                'product_id'  => [
                    'description' => __( 'Unique identifier for the product.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'required'    => true,
                ],
                'quantity'    => [
                    'description' => __( 'Quantity of the product.', 'dokan' ),
                    'type'        => 'double',
                    'context'     => [ 'view', 'edit' ],
                    'required'    => true,
                ],
                'offer_price' => [
                    'description' => __( 'Status of the quote.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'required'    => true,
                ],
            ],
        ];

        $this->schema = $schema;

        return $this->add_additional_fields_schema( $this->schema );
    }

    /**
     * Remove author args from query args.
     *
     * @since 3.7.4
     *
     * @param array $args
     * @param \WP_REST_Request $request Full details about the request.
     *
     * @return array
     */
    public function remove_author_arg( $args, $request ) {
        $params = $request->get_params();

        // Author scoping and catalog-visibility filtering below apply only to the customer-facing quote catalog; leave every other product listing untouched.
        if ( ! isset( $params['request_a_quote'] ) || intval( $params['request_a_quote'] ) !== 1 ) {
            return $args;
        }

        unset( $args['author'] );

        if ( empty( $args['tax_query'] ) ) {
            $args['tax_query'] = []; // phpcs:ignore
        }

        if ( isset( $params['include_hidden_products'] ) && dokan_string_to_bool( $params['include_hidden_products'] ) ) {
            return $args;
        }

        $args['tax_query'][] = [
            'taxonomy'  => 'product_visibility',
            'terms'     => [ 'exclude-from-catalog' ],
            'field'     => 'name',
            'operator'  => 'NOT IN',
        ];

        return $args;
    }
}
