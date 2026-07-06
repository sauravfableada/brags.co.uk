<?php

namespace WeDevs\DokanPro\Modules\Booking\REST;

use WeDevs\Dokan\Abstracts\DokanRESTController;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Booking Product REST Controller.
 *
 * @since 5.0.0
 */
class BookingProductController extends DokanRESTController {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'dokan/v1';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'booking/products';

    /**
     * Register routes.
     *
     * @since 5.0.0
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
            '/' . $this->rest_base . '/summary',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_summary' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                ],
            ]
        );
    }

    /**
     * Check permissions for listing booking products.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return bool|WP_Error
     */
    public function get_items_permissions_check( $request ) {
        if ( ! current_user_can( 'dokan_manage_booking_products' ) ) {
            return new WP_Error(
                'dokan_rest_cannot_list_booking_products',
                __( 'Sorry, you are not allowed to list booking products.', 'dokan' ),
                [ 'status' => rest_authorization_required_code() ]
            );
        }

        return true;
    }

    /**
     * Get a collection of booking products.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function get_items( $request ) {
        $vendor_id = dokan_get_current_user_id();
        $per_page  = $request->get_param( 'per_page' ) ? absint( $request->get_param( 'per_page' ) ) : 10;
        $page      = $request->get_param( 'page' ) ? absint( $request->get_param( 'page' ) ) : 1;
        $search    = $request->get_param( 'search' ) ? sanitize_text_field( $request->get_param( 'search' ) ) : '';
        $status    = $request->get_param( 'status' ) ? sanitize_text_field( $request->get_param( 'status' ) ) : '';
        $date      = $request->get_param( 'year_month' ) ? sanitize_text_field( $request->get_param( 'year_month' ) ) : '';
        $category  = $request->get_param( 'category' ) ? absint( $request->get_param( 'category' ) ) : 0;
        $brand     = $request->get_param( 'product_brand' ) ? absint( $request->get_param( 'product_brand' ) ) : 0;
        $in_stock  = $request->get_param( 'in_stock' );
        $filter_by = $request->get_param( 'filter_by_other' ) ? sanitize_text_field( $request->get_param( 'filter_by_other' ) ) : '';

        /**
         * Filter the post-statuses allowed when listing vendor booking products.
         *
         * Shared across the product listing endpoints, so Pro modules (e.g. product-editor,
         * vendor-subscription-product) can extend the accepted set.
         *
         * @since 5.0.0
         *
         * @param string[] $post_statuses Default allowed post statuses.
         */
        $post_statuses = apply_filters( 'dokan_product_listing_post_statuses', [ 'publish', 'draft', 'pending' ] );

        $args = [
            'post_type'      => 'product',
            'post_status'    => $post_statuses,
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'author'         => $vendor_id,
            'orderby'        => 'post_date',
            'order'          => 'DESC',
            'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                [
                    'taxonomy' => 'product_type',
                    'field'    => 'slug',
                    'terms'    => 'booking',
                ],
            ],
        ];

        if ( ! empty( $status ) && in_array( $status, $post_statuses, true ) ) {
            $args['post_status'] = $status;
        }

        if ( ! empty( $search ) ) {
            $args['s'] = $search;
        }

        if ( ! empty( $date ) ) {
            $args['m'] = $date;
        }

        if ( $category > 0 ) {
            $args['tax_query'][] = [
                'taxonomy'         => 'product_cat',
                'field'            => 'term_id',
                'terms'            => $category,
                'include_children' => false,
            ];

            if ( ! isset( $args['tax_query']['relation'] ) ) {
                $args['tax_query']['relation'] = 'AND';
            }
        }

        if ( $brand > 0 ) {
            $args['tax_query'][] = [
                'taxonomy' => 'product_brand',
                'field'    => 'term_id',
                'terms'    => $brand,
            ];

            if ( ! isset( $args['tax_query']['relation'] ) ) {
                $args['tax_query']['relation'] = 'AND';
            }
        }

        if ( null !== $in_stock ) {
            $stock_status = $in_stock ? 'instock' : 'outofstock';

            if ( ! isset( $args['meta_query'] ) ) {
                $args['meta_query'] = []; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            }

            $args['meta_query'][] = [
                'key'   => '_stock_status',
                'value' => $stock_status,
            ];
        }

        if ( ! empty( $filter_by ) ) {
            switch ( $filter_by ) {
                case 'featured':
                    $args['tax_query'][] = [
                        'taxonomy' => 'product_visibility',
                        'field'    => 'name',
                        'terms'    => 'featured',
                    ];
                    break;

                case 'top_rated':
                    $args['meta_key']  = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                    $args['orderby']   = 'meta_value_num';
                    $args['order']     = 'DESC';
                    break;

                case 'best_selling':
                    $args['meta_key']  = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                    $args['orderby']   = 'meta_value_num';
                    $args['order']     = 'DESC';
                    break;

                case 'low_stock':
                    if ( ! isset( $args['meta_query'] ) ) {
                        $args['meta_query'] = []; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                    }

                    $args['meta_query'][] = [
                        'key'     => '_stock',
                        'value'   => get_option( 'woocommerce_notify_low_stock_amount', 2 ),
                        'compare' => '<=',
                        'type'    => 'NUMERIC',
                    ];
                    $args['meta_query'][] = [
                        'key'   => '_manage_stock',
                        'value' => 'yes',
                    ];
                    break;

                case 'out_of_stock':
                    if ( ! isset( $args['meta_query'] ) ) {
                        $args['meta_query'] = []; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                    }

                    $args['meta_query'][] = [
                        'key'   => '_stock_status',
                        'value' => 'outofstock',
                    ];
                    break;
            }
        }

        $query = new WP_Query( apply_filters( 'dokan_product_listing_query', $args ) );

        $data = [];

        foreach ( $query->posts as $post ) {
            $product = wc_get_product( $post->ID );

            if ( ! $product ) {
                continue;
            }

            $item_response = rest_ensure_response( $this->prepare_product_data( $product, $post ) );

            /**
             * Filter a single booking product's REST response data.
             *
             * @since 5.0.0
             *
             * @param WP_REST_Response $item_response Booking product response.
             * @param int              $product_id    WooCommerce product ID.
             */
            $item_response = apply_filters( 'dokan_rest_prepare_booking_object', $item_response, $product->get_id() );

            $data[] = $item_response->get_data();
        }

        $response    = rest_ensure_response( $data );
        $total_items = $query->found_posts;
        $max_pages   = ceil( $total_items / $per_page );

        $response->header( 'X-WP-Total', (int) $total_items );
        $response->header( 'X-WP-TotalPages', (int) $max_pages );

        return $response;
    }

    /**
     * Get booking product summary (status counts, stock counts, month options).
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response
     */
    public function get_summary( $request ) {
        $vendor_id = dokan_get_current_user_id();

        $data = [
            'post_counts'           => $this->count_booking_posts( $vendor_id ),
            'instock_count'         => $this->count_booking_stock_posts( $vendor_id, 'instock' ),
            'outofstock_count'      => $this->count_booking_stock_posts( $vendor_id, 'outofstock' ),
            'months'                => $this->get_booking_months_data( $vendor_id ),
            'low_stock_threshold'   => (int) get_option( 'woocommerce_notify_low_stock_amount', 2 ),
        ];

        /**
         * Allow Pro modules to append per-request data to the booking product listing summary.
         *
         * @since 5.0.0
         *
         * @param array $data      Summary data array.
         * @param int   $vendor_id Current vendor ID.
         */
        $data = (array) apply_filters( 'dokan_product_listing_summary_data', $data, $vendor_id );

        return rest_ensure_response( $data );
    }

    /**
     * Count booking products by status for a vendor.
     *
     * @since 5.0.0
     *
     * @param int $vendor_id Vendor user ID.
     *
     * @return object Status counts keyed by post_status.
     */
    private function count_booking_posts( $vendor_id ) {
        global $wpdb;

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_status, COUNT(*) AS num_posts
                FROM {$wpdb->posts} AS posts
                INNER JOIN {$wpdb->term_relationships} AS tr ON posts.ID = tr.object_id
                INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                INNER JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
                WHERE tt.taxonomy = 'product_type'
                    AND t.slug = 'booking'
                    AND posts.post_type = 'product'
                    AND posts.post_author = %d
                GROUP BY posts.post_status",
                $vendor_id
            ),
            ARRAY_A
        );
        // phpcs:enable

        $post_status = array_keys( dokan_get_post_status() );
        $counts      = array_fill_keys( get_post_stati(), 0 );
        $total       = 0;

        foreach ( (array) $results as $row ) {
            if ( ! in_array( $row['post_status'], $post_status, true ) ) {
                continue;
            }

            $counts[ $row['post_status'] ] = (int) $row['num_posts'];
            $total                         += (int) $row['num_posts'];
        }

        $counts['total'] = $total;

        return (object) $counts;
    }

    /**
     * Count booking products by stock status for a vendor.
     *
     * @since 5.0.0
     *
     * @param int    $vendor_id  Vendor user ID.
     * @param string $stock_type Stock type (instock or outofstock).
     *
     * @return int
     */
    private function count_booking_stock_posts( $vendor_id, $stock_type ) {
        global $wpdb;

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.post_status, COUNT(*) AS num_posts
                FROM {$wpdb->posts} AS p
                INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
                WHERE p.post_type = 'product'
                    AND p.post_author = %d
                    AND pm.meta_key = '_stock_status'
                    AND pm.meta_value = %s
                    AND p.ID IN (
                        SELECT tr.object_id
                        FROM {$wpdb->terms} AS t
                        INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
                        INNER JOIN {$wpdb->term_relationships} AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
                        WHERE tt.taxonomy = 'product_type' AND t.slug = 'booking'
                    )
                GROUP BY p.post_status",
                $vendor_id,
                $stock_type
            ),
            ARRAY_A
        );
        // phpcs:enable

        $post_status = array_keys( dokan_get_post_status() );
        $total       = 0;

        foreach ( (array) $results as $row ) {
            if ( ! in_array( $row['post_status'], $post_status, true ) ) {
                continue;
            }

            $total += (int) $row['num_posts'];
        }

        return $total;
    }

    /**
     * Get month options for booking products.
     *
     * @since 5.0.0
     *
     * @param int $vendor_id Vendor user ID.
     *
     * @return array
     */
    private function get_booking_months_data( $vendor_id ) {
        global $wpdb, $wp_locale;

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $months = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT YEAR(p.post_date) AS year, MONTH(p.post_date) AS month
                FROM {$wpdb->posts} AS p
                INNER JOIN {$wpdb->term_relationships} AS tr ON p.ID = tr.object_id
                INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                INNER JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
                WHERE p.post_type = 'product'
                    AND p.post_author = %d
                    AND p.post_status != 'auto-draft'
                    AND tt.taxonomy = 'product_type'
                    AND t.slug = 'booking'
                ORDER BY p.post_date DESC",
                $vendor_id
            )
        );
        // phpcs:enable

        $items = [];

        foreach ( (array) $months as $row ) {
            if ( 0 === (int) $row->year || 0 === (int) $row->month ) {
                continue;
            }

            $month_padded = zeroise( (int) $row->month, 2 );
            $items[]      = [
                'value' => $row->year . $month_padded,
                'label' => $wp_locale->get_month( (int) $row->month ) . ' ' . $row->year,
            ];
        }

        return $items;
    }

    /**
     * Prepare product data for the REST response.
     * Returns a shape compatible with the product list UI (ProductItem).
     *
     * @since 5.0.0
     *
     * @param \WC_Product $product WC product object.
     * @param \WP_Post    $post    Post object.
     *
     * @return array
     */
    private function prepare_product_data( $product, $post ) {
        $images     = [];
        $image_id   = $product->get_image_id();
        $gallery_ids = $product->get_gallery_image_ids();

        if ( $image_id ) {
            $images[] = [
                'id'  => $image_id,
                'src' => wp_get_attachment_image_url( $image_id, 'thumbnail' ) ?: '',
                'name' => get_the_title( $image_id ),
                'alt'  => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
            ];
        }

        foreach ( $gallery_ids as $gid ) {
            $images[] = [
                'id'  => $gid,
                'src' => wp_get_attachment_image_url( $gid, 'thumbnail' ) ?: '',
                'name' => get_the_title( $gid ),
                'alt'  => get_post_meta( $gid, '_wp_attachment_image_alt', true ),
            ];
        }

        $categories = [];

        foreach ( $product->get_category_ids() as $cat_id ) {
            $term = get_term( $cat_id, 'product_cat' );
            if ( $term && ! is_wp_error( $term ) ) {
                $categories[] = [
                    'id'   => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                ];
            }
        }

        return [
            'id'             => $product->get_id(),
            'name'           => $product->get_name(),
            'slug'           => $product->get_slug(),
            'type'           => $product->get_type(),
            'status'         => $post->post_status,
            'sku'            => $product->get_sku(),
            'price'          => $product->get_price(),
            'regular_price'  => $product->get_regular_price(),
            'sale_price'     => $product->get_sale_price(),
            'price_html'     => $product->get_price_html(),
            'on_sale'        => $product->is_on_sale(),
            'manage_stock'   => $product->get_manage_stock(),
            'stock_quantity' => $product->get_stock_quantity(),
            'in_stock'       => $product->is_in_stock(),
            'total_sales'    => (int) $product->get_total_sales(),
            'virtual'        => $product->is_virtual(),
            'downloadable'   => $product->is_downloadable(),
            'categories'     => $categories,
            'images'         => $images,
            'date_created'   => $post->post_date,
            'date_modified'  => $post->post_modified,
            'permalink'      => $product->get_permalink(),
            'earning'        => null,
            'page_view'      => (int) get_post_meta( $post->ID, 'pageview', true ),
            'edit_url'       => dokan_get_navigation_url( 'booking/edit/?product_id=' . $product->get_id() ),
            'currency'       => get_woocommerce_currency(),
        ];
    }

    /**
     * Get collection params.
     *
     * @since 5.0.0
     *
     * @return array
     */
    public function get_collection_params() {
        $params = parent::get_collection_params();

        $params['per_page'] = [
            'description'       => __( 'Maximum number of items to be returned in result set.', 'dokan' ),
            'type'              => 'integer',
            'default'           => 10,
            'minimum'           => 1,
            'maximum'           => 100,
            'sanitize_callback' => 'absint',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['page'] = [
            'description'       => __( 'Current page of the collection.', 'dokan' ),
            'type'              => 'integer',
            'default'           => 1,
            'minimum'           => 1,
            'sanitize_callback' => 'absint',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['status'] = [
            'description'       => __( 'Product status to filter by.', 'dokan' ),
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['year_month'] = [
            'description'       => __( 'Date filter in YYYYMM format.', 'dokan' ),
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['category'] = [
            'description'       => __( 'Product category ID to filter by.', 'dokan' ),
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['product_brand'] = [
            'description'       => __( 'Product brand term ID to filter by.', 'dokan' ),
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['in_stock'] = [
            'description'       => __( 'Filter by stock status.', 'dokan' ),
            'type'              => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        $params['filter_by_other'] = [
            'description'       => __( 'Additional filter: featured, top_rated, best_selling, low_stock, out_of_stock.', 'dokan' ),
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        return $params;
    }

    /**
     * Get item schema.
     *
     * @since 5.0.0
     *
     * @return array
     */
    public function get_item_schema() {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'booking-product',
            'type'       => 'object',
            'properties' => [
                'id'             => [
                    'description' => __( 'Unique identifier for the product.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'name'           => [
                    'description' => __( 'Product name.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'slug'           => [
                    'description' => __( 'Product slug.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'type'           => [
                    'description' => __( 'Product type.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'status'         => [
                    'description' => __( 'Product status.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'sku'            => [
                    'description' => __( 'Product SKU.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'price'          => [
                    'description' => __( 'Product price.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'regular_price'  => [
                    'description' => __( 'Product regular price.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'sale_price'     => [
                    'description' => __( 'Product sale price.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'price_html'     => [
                    'description' => __( 'Product price HTML.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'on_sale'        => [
                    'description' => __( 'Whether the product is on sale.', 'dokan' ),
                    'type'        => 'boolean',
                    'context'     => [ 'view' ],
                ],
                'manage_stock'   => [
                    'description' => __( 'Whether stock management is enabled.', 'dokan' ),
                    'type'        => 'boolean',
                    'context'     => [ 'view' ],
                ],
                'stock_quantity' => [
                    'description' => __( 'Product stock quantity.', 'dokan' ),
                    'type'        => [ 'integer', 'null' ],
                    'context'     => [ 'view' ],
                ],
                'in_stock'       => [
                    'description' => __( 'Whether the product is in stock.', 'dokan' ),
                    'type'        => 'boolean',
                    'context'     => [ 'view' ],
                ],
                'images'         => [
                    'description' => __( 'Product images.', 'dokan' ),
                    'type'        => 'array',
                    'context'     => [ 'view' ],
                ],
                'categories'     => [
                    'description' => __( 'Product categories.', 'dokan' ),
                    'type'        => 'array',
                    'context'     => [ 'view' ],
                ],
                'date_created'   => [
                    'description' => __( 'Product creation date.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'date_modified'  => [
                    'description' => __( 'Product last modified date.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'permalink'      => [
                    'description' => __( 'Product permalink.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'edit_url'       => [
                    'description' => __( 'URL to edit the product.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                ],
                'page_view'      => [
                    'description' => __( 'Product page view count.', 'dokan' ),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                ],
                'earning'        => [
                    'description' => __( 'Product earning.', 'dokan' ),
                    'type'        => [ 'number', 'null' ],
                    'context'     => [ 'view' ],
                ],
                'advertisement'  => [
                    'description' => __( 'Product advertisement data.', 'dokan' ),
                    'type'        => [ 'object', 'null' ],
                    'context'     => [ 'view' ],
                ],
            ],
        ];
    }
}
