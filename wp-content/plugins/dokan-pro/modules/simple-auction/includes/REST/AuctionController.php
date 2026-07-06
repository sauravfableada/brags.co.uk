<?php

namespace WeDevs\DokanPro\Modules\Auction\REST;

use DateInterval;
use WC_REST_Products_Controller;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Auction REST Controller
 *
 * Extends WooCommerce's product REST controller to provide vendor-scoped
 * auction product endpoints for the React vendor dashboard.
 *
 * @since 5.0.0
 *
 * @package WeDevs\DokanPro\Modules\Auction\REST
 */
class AuctionController extends WC_REST_Products_Controller {

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
    protected $rest_base = 'auction/products';

    /**
     * Register REST routes.
     *
     * Only registers the routes we need — not the full WC product CRUD.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function register_routes(): void {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_items' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                    'args'                => array_merge(
                        $this->get_collection_params(),
                        [
                            'status' => [
                                'default'           => 'all',
                                'type'              => 'string',
                                'sanitize_callback' => 'sanitize_text_field',
                            ],
                            'start_date' => [
                                'default'           => '',
                                'type'              => 'string',
                                'sanitize_callback' => 'sanitize_text_field',
                                'validate_callback' => [ $this, 'validate_date_param' ],
                            ],
                            'end_date' => [
                                'default'           => '',
                                'type'              => 'string',
                                'sanitize_callback' => 'sanitize_text_field',
                                'validate_callback' => [ $this, 'validate_date_param' ],
                            ],
                        ]
                    ),
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/summary',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_products_summary' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            [
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'delete_item' ],
                    'permission_callback' => [ $this, 'delete_item_permissions_check' ],
                    'args'                => [
                        'id' => [
                            'description'       => __( 'Unique identifier for the auction product.', 'dokan' ),
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                            'required'          => true,
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/auction/activity',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_activity' ],
                    'permission_callback' => [ $this, 'get_activity_permissions_check' ],
                    'args'                => $this->get_activity_args(),
                ],
            ]
        );
    }

    /**
     * Check if the current user can list auction products.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return true|WP_Error
     */
    public function get_items_permissions_check( $request ) {
        if ( ! is_user_logged_in() || ! current_user_can( 'dokan_view_auction_menu' ) ) {
            return new WP_Error(
                'dokan_rest_forbidden',
                __( 'You do not have permission to access auction products.', 'dokan' ),
                [ 'status' => 403 ]
            );
        }

        return true;
    }

    /**
     * Check if the current user can delete this auction product.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return true|WP_Error
     */
    public function delete_item_permissions_check( $request ) {
        $product_id = (int) $request->get_param( 'id' );

        if ( ! current_user_can( 'dokan_delete_auction_product' ) ) {
            return new WP_Error(
                'dokan_rest_forbidden',
                __( 'You do not have permission to delete auction products.', 'dokan' ),
                [ 'status' => 403 ]
            );
        }

        $product = wc_get_product( $product_id );
        if ( ! $product || $product->get_type() !== 'auction' ) {
            return new WP_Error(
                'dokan_rest_invalid_product',
                __( 'Auction product not found.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        if ( ! dokan_is_product_author( $product_id ) ) {
            return new WP_Error(
                'dokan_rest_forbidden',
                __( 'You do not have permission to delete this product.', 'dokan' ),
                [ 'status' => 403 ]
            );
        }

        return true;
    }

    /**
     * Check if the current user can view auction activity.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return true|WP_Error
     */
    public function get_activity_permissions_check( WP_REST_Request $request ) {
        return $this->get_items_permissions_check( $request );
    }

    /**
     * Prepare objects query.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return array
     */
    protected function prepare_objects_query( $request ) {
        $args = parent::prepare_objects_query( $request );

        $args['author'] = dokan_get_current_user_id();

        $args['tax_query'][] = [
            'taxonomy' => 'product_type',
            'field'    => 'slug',
            'terms'    => 'auction',
        ];

        // Include past auctions.
        $args['auction_archive']    = true;
        $args['show_past_auctions'] = true;

        // Handle status filter.
        $status           = sanitize_text_field( (string) $request->get_param( 'status' ) );
        $allowed_statuses = array_keys( dokan_get_post_status() );

        if ( $status && in_array( $status, $allowed_statuses, true ) ) {
            $args['post_status'] = $status;
        } else {
            $args['post_status'] = $allowed_statuses;
        }

        // Handle date filtering — already validated by validate_date_param.
        $start_date = (string) $request->get_param( 'start_date' );
        $end_date   = (string) $request->get_param( 'end_date' );

        if ( $start_date ) {
            $args['date_query'][] = [
                'after'     => $start_date,
                'inclusive' => true,
            ];
        }

        if ( $end_date ) {
            if ( empty( $args['date_query'] ) ) {
                $args['date_query'][] = [
                    'inclusive' => true,
                ];
            }
            $args['date_query'][0]['before'] = $end_date;
        }

        return $args;
    }

    /**
     * Prepare a single product for the REST response, adding auction-specific fields.
     *
     * @since 5.0.0
     *
     * @param \WC_Data         $object  Object data.
     * @param WP_REST_Request  $request Request object.
     *
     * @return WP_REST_Response
     */
    public function prepare_object_for_response( $object, $request ) {
        $response   = parent::prepare_object_for_response( $object, $request );
        $data       = $response->get_data();
        $product    = wc_get_product( $object->get_id() );
        $product_id = $product ? $product->get_id() : 0;

        if ( $product instanceof \WC_Product_Auction ) {
            $is_closed = (bool) $product->is_closed();

            $data['auction_is_closed']   = $is_closed;
            $data['auction_fail_reason'] = (string) $product->get_auction_fail_reason();
            $data['auction_is_payed']    = (bool) $product->get_auction_payed();
            $data['auction_closed_val']  = (string) $product->get_auction_closed();
            $data['auction_start_date']  = (string) $product->get_auction_dates_from();
            $data['auction_end_date']    = (string) $product->get_auction_dates_to();
            $data['auction_current_bid'] = (string) $product->get_auction_current_bid();
            $data['auction_bid_count']   = (int) $product->get_auction_bid_count();

            // Auction products don't track stock like regular products.
            // An auction is "in stock" when it's not closed.
            $data['in_stock']     = ! $is_closed;
            $data['stock_status'] = $is_closed ? 'outofstock' : 'instock';
        }

        // Dokan-specific fields.
        $data['edit_url']  = $this->get_auction_edit_url( $product_id );
        $data['page_view'] = (int) get_post_meta( $product_id, 'pageview', true );

        $response->set_data( $data );

        /**
         * Filter a single auction product's REST response data.
         *
         * @since 5.0.0
         *
         * @param array $data       Product data array.
         * @param int   $product_id WooCommerce product ID.
         */
        $data = apply_filters( 'dokan_rest_prepare_auction_object', $response, $product_id );

        return $response;
    }

    /**
     * Delete an auction product.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function delete_item( $request ) {
        $product_id = (int) $request->get_param( 'id' );

        // Force permanent delete.
        $request->set_param( 'force', true );

        $result = parent::delete_item( $request );

        if ( ! is_wp_error( $result ) ) {
            /**
             * Fires after an auction product is deleted via REST.
             *
             * @since 5.0.0
             *
             * @param int $product_id Deleted product ID.
             */
            do_action( 'dokan_delete_auction_product', $product_id );
        }

        return $result;
    }

    /**
     * Returns per-status product counts for the current vendor's auction products.
     *
     * @since 5.0.0
     *
     * @return WP_REST_Response|WP_Error
     */
    public function get_products_summary() {
        $vendor_id = dokan_get_current_user_id();

        $product_types = dokan_get_product_types();

        $excluded_types = array_values(
            array_diff(
                array_unique(
                    array_merge(
                        array_keys( $product_types ),
                        (array) apply_filters( 'dokan_product_listing_exclude_type', [] )
                    )
                ),
                [ 'auction' ]
            )
        );

        $post_counts = dokan_count_posts( 'product', $vendor_id, $excluded_types );

        $data = [
            'post_counts' => [
                'publish' => (int) ( $post_counts->publish ?? 0 ),
                'draft'   => (int) ( $post_counts->draft ?? 0 ),
                'pending' => (int) ( $post_counts->pending ?? 0 ),
                'reject'  => (int) ( $post_counts->reject ?? 0 ),
            ],
        ];

        /**
         * Filter the auction product listing summary data.
         *
         * @since 5.0.0
         *
         * @param array $data      Summary data.
         * @param int   $vendor_id Current vendor user ID.
         */
        $data = apply_filters( 'dokan_auction_listing_summary_data', $data, $vendor_id );

        return rest_ensure_response( $data );
    }

    /**
     * Get auction activity log for the current vendor.
     *
     * @since 5.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function get_activity( WP_REST_Request $request ) {
        global $wpdb;

        $vendor_id  = dokan_get_current_user_id();
        $page       = max( 1, (int) $request->get_param( 'page' ) );
        $per_page   = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );
        $offset     = ( $page - 1 ) * $per_page;
        $search     = sanitize_text_field( (string) $request->get_param( 'search' ) );
        $start_date = sanitize_text_field( (string) $request->get_param( 'start_date' ) );
        $end_date   = sanitize_text_field( (string) $request->get_param( 'end_date' ) );

        $date_filter   = '';
        $search_filter = '';

        $end_date_plus = $end_date
            ? dokan_current_datetime()
                ->modify( $end_date )
                ->add( new DateInterval( 'PT1M' ) )
                ->format( 'Y-m-d H:i' )
            : '';

        if ( $start_date && $end_date ) {
            $date_filter = $wpdb->prepare(
                ' AND date BETWEEN CAST( %s AS DATETIME ) AND CAST( %s AS DATETIME )',
                $start_date,
                $end_date_plus
            );
        } elseif ( $start_date ) {
            $date_filter = $wpdb->prepare( ' AND date >= CAST( %s AS DATETIME )', $start_date );
        } elseif ( $end_date ) {
            $date_filter = $wpdb->prepare( ' AND date <= CAST( %s AS DATETIME )', $end_date_plus );
        }

        if ( $search ) {
            $like          = '%' . $wpdb->esc_like( $search ) . '%';
            $search_filter = $wpdb->prepare(
                "AND ( `{$wpdb->users}`.user_nicename LIKE %s OR `{$wpdb->posts}`.post_title LIKE %s OR `{$wpdb->users}`.user_email = %s )",
                $like,
                $like,
                sanitize_email( $search )
            );
        }

        $count_query = $wpdb->prepare(
            "SELECT COUNT(*)
            FROM `{$wpdb->prefix}simple_auction_log` SL
            LEFT JOIN `{$wpdb->users}` ON SL.userid = `{$wpdb->users}`.ID
            LEFT JOIN `{$wpdb->posts}` ON SL.auction_id = `{$wpdb->posts}`.ID
            WHERE `{$wpdb->posts}`.post_author = %d
                {$search_filter}
                {$date_filter}
            ;",
            $vendor_id
        );

        $total_items = (int) $wpdb->get_var( $count_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        $items_query = $wpdb->prepare(
            "SELECT SL.*, `{$wpdb->users}`.user_nicename, `{$wpdb->users}`.user_email,
                    `{$wpdb->posts}`.post_title, `{$wpdb->posts}`.ID AS post_id
            FROM `{$wpdb->prefix}simple_auction_log` SL
            LEFT JOIN `{$wpdb->users}` ON SL.userid = `{$wpdb->users}`.ID
            LEFT JOIN `{$wpdb->posts}` ON SL.auction_id = `{$wpdb->posts}`.ID
            WHERE `{$wpdb->posts}`.post_author = %d
                {$search_filter}
                {$date_filter}
            ORDER BY date DESC
            LIMIT %d, %d;",
            $vendor_id,
            $offset,
            $per_page
        );

        $rows = $wpdb->get_results( $items_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        $items = [];
        foreach ( (array) $rows as $row ) {
            $items[] = [
                'post_id'       => (int) $row['post_id'],
                'post_title'    => $row['post_title'],
                'user_nicename' => $row['user_nicename'],
                'user_email'    => $row['user_email'],
                'bid'           => (string) $row['bid'],
                'date'          => $row['date'],
                'proxy'         => (bool) $row['proxy'],
            ];
        }

        $max_pages = $per_page > 0 ? (int) ceil( $total_items / $per_page ) : 0;

        $response = rest_ensure_response( $items );
        $response->header( 'X-WP-Total', (int) $total_items );
        $response->header( 'X-WP-TotalPages', $max_pages );

        return $response;
    }

    /**
     * Build the vendor dashboard edit URL for an auction product.
     *
     * @since 5.0.0
     *
     * @param int $product_id WooCommerce product ID.
     *
     * @return string
     */
    protected function get_auction_edit_url( int $product_id ): string {
        $dashboard_page_id = (int) dokan_get_option( 'dashboard', 'dokan_pages', 0 );

        if ( ! $dashboard_page_id ) {
            return '';
        }

        return add_query_arg(
            [
                'product_id' => $product_id,
                'action'     => 'edit',
            ],
            rtrim( get_permalink( $dashboard_page_id ), '/' ) . '/auction/'
        );
    }

    /**
     * Query args for GET /auction/activity.
     *
     * @since 5.0.0
     *
     * @return array
     */
    protected function get_activity_args(): array {
        return [
            'page'       => [
                'default'           => 1,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'per_page'   => [
                'default'           => 10,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'search'     => [
                'default'           => '',
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'start_date' => [
                'default'           => '',
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => [ $this, 'validate_date_param' ],
            ],
            'end_date'   => [
                'default'           => '',
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => [ $this, 'validate_date_param' ],
            ],
        ];
    }

    /**
     * Validate a date parameter in YYYY-MM-DD format.
     *
     * @since 5.0.0
     *
     * @param string          $value   Parameter value.
     * @param WP_REST_Request $request Request object.
     * @param string          $param   Parameter name.
     *
     * @return true|WP_Error
     */
    public function validate_date_param( $value, $request, $param ) {
        if ( empty( $value ) ) {
            return true;
        }

        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
            return new WP_Error(
                'rest_invalid_param',
                /* translators: 1: parameter name, 2: expected format */
                sprintf( __( '%1$s must be a valid date in %2$s format.', 'dokan' ), $param, 'YYYY-MM-DD' ),
                [ 'status' => 400 ]
            );
        }

        return true;
    }
}
