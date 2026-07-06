<?php

namespace WeDevs\DokanPro\ProductRejection;

use Automattic\WooCommerce\Admin\Overrides\Order;
use RuntimeException;
use Throwable;
use WC_Product;
use WeDevs\Dokan\Vendor\Vendor;
use WP_Post;
use WP_Query;

/**
 * Handles admin-side functionality for product rejection.
 *
 * Manages metaboxes, product list modifications, status filtering,
 * and admin UI elements for the product rejection workflow.
 *
 * @since 3.16.0
 */
class Admin {
    /**
     * ProductStatusService handler instance.
     *
     * @since 3.16.0
     *
     * @var ProductStatusService
     */
    protected ProductStatusService $product_status_service;

    /**
     * Constructor for the Admin class.
     *
     * Initializes the admin handler and registers necessary hooks.
     *
     * @since 3.16.0
     *
     * @param ProductStatusService $product_status_service ProductStatusService handler
     */
    public function __construct( ProductStatusService $product_status_service ) {
        $this->product_status_service = $product_status_service;
        $this->register_hooks();
    }

    /**
     * Initialize hooks for admin functionality.
     *
     * @since 3.16.0
     *
     * @return void
     */
    protected function register_hooks(): void {
        // Meta boxes
        add_action( 'add_meta_boxes', array( $this, 'register_metabox' ), 10, 2 );
        add_action( 'add_meta_boxes', array( $this, 'add_product_boxes_sort_order' ), 10000 );

        // Product list modifications
        add_filter( 'display_post_states', array( $this, 'display_reject_state' ), 10, 2 );
        add_filter( 'post_row_actions', array( $this, 'add_reject_action' ), 10, 2 );

        // Add filtering support
        add_action( 'restrict_manage_posts', array( $this, 'add_status_filter' ) );
        add_filter( 'parse_query', array( $this, 'filter_products_by_status' ) );

        // Cleanup rejection metadata on status transitions
        add_action( 'dokan_after_product_reject', array( $this, 'on_product_reject' ) );
        add_action( 'transition_post_status', array( $this, 'on_all_status_transitions' ), 10, 3 );
    }

    /**
     * Register the rejection metabox.
     *
     * @since 3.16.0
     *
     * @param string        $post_type Post type
     * @param WP_Post|Order $post      Post object
     *
     * @return void
     */
    public function register_metabox( string $post_type, $post ): void {
        try {
            if ( 'product' !== $post_type ) {
                return;
            }

            $product = wc_get_product( $post );
            if ( ! $product instanceof WC_Product ) {
                return;
            }

            // Validate product type.
            if ( ! $this->product_status_service->is_allowed_for_rejection( $product ) ||
                ! $this->product_status_service->is_metabox_eligible_for_product( $product ) ) {
                return;
            }

            // Get and validate vendor
            $vendor_id = dokan_get_vendor_by_product( $product->get_id(), true );
            $vendor    = dokan()->vendor->get( $vendor_id );
            if ( ! $vendor instanceof Vendor ) {
                return;
            }

            add_meta_box(
                'dokan-product-rejection',
                __( 'Reject', 'dokan' ),
                [ $this, 'render_metabox' ],
                $post_type,
                'side',
                'default',
                [
                    'product' => $product,
                    'vendor'  => $vendor,
                ]
            );

            /**
             * Action after registering rejection metabox.
             *
             * @since 3.16.0
             *
             * @param WC_Product $product Product object
             * @param Vendor     $vendor  Vendor object
             */
            do_action( 'dokan_after_register_rejection_metabox', $product, $vendor );
        } catch ( Throwable $e ) {
            dokan_log( sprintf( 'Failed to register rejection metabox for product ID %d: %s', $post->ID, $e->getMessage() ) );
        }
    }

    /**
     * Add default sort order for meta boxes on product page.
     *
     * @since 3.16.0
     *
     * @return void
     */
    public function add_product_boxes_sort_order(): void {
        $current_value = get_user_meta( get_current_user_id(), 'meta-box-order_product', true );

        if ( ! empty( $current_value ) && isset( $current_value['side'] ) ) {
            $meta_box_ids = explode( ',', $current_value['side'] );
            if ( in_array( 'dokan-product-rejection', $meta_box_ids, true ) ) {
                return;
            }
        }

        update_user_meta(
            get_current_user_id(),
            'meta-box-order_product',
            array(
                'side'     => 'submitdiv,dokan-product-rejection,postimagediv,woocommerce-product-images,product_catdiv,tagsdiv-product_tag',
                'normal'   => 'woocommerce-product-data,postcustom,slugdiv,postexcerpt',
                'advanced' => '',
            )
        );
    }

    /**
     * Render the metabox content.
     *
     * @since 3.16.0
     *
     * @param WP_Post $post Post object
     * @param array   $args Callback args
     *
     * @return void
     */
    public function render_metabox( WP_Post $post, array $args ): void {
        try {
            $product = $args['args']['product'] ?? null;
            $vendor  = $args['args']['vendor'] ?? null;

            if ( ! $product instanceof WC_Product ) {
                throw new RuntimeException( sprintf( 'Invalid product object. Product ID: %d', $post->ID ) );
            }

            if ( ! $vendor instanceof Vendor ) {
                throw new RuntimeException( sprintf( 'Vendor not found. Product ID: %d', $post->ID ) );
            }

            $params = $this->prepare_metabox_params( $product, $vendor );

            dokan_get_template_part( 'product-rejection/admin/metabox', '', $params );
        } catch ( Throwable $e ) {
            dokan_log( sprintf( 'Error rendering rejection metabox: %s', $e->getMessage() ) );
        }
    }

    /**
     * Prepare parameters for metabox template.
     *
     * @since 3.16.0
     *
     * @param WC_Product $product Product object
     * @param Vendor     $vendor  Vendor object
     *
     * @return array Template parameters
     */
    protected function prepare_metabox_params( WC_Product $product, Vendor $vendor ): array {
        $params = [
            'pro'               => true,
            'product'           => $product,
            'vendor'            => $vendor,
            'is_rejected'       => $this->product_status_service->is_rejected( $product ),
            'is_resubmitted'    => $this->product_status_service->is_resubmitted( $product ),
            'submitted_date'    => dokan_format_date( dokan_format_datetime( $product->get_date_modified() ) ),
            'rejection_history' => array(),
            'resubmission_time' => '',
        ];

        if ( $params['is_resubmitted'] ) {
            $params['resubmission_time'] = dokan_format_date(
                $this->product_status_service->get_resubmission_date( $product )
            );
        }

        $rejection_details = $this->product_status_service->get_rejection_details( $product );
        if ( ! is_wp_error( $rejection_details ) ) {
            $params['rejection_history'] = $rejection_details;
        }

        // Shop name
        $params['shop_name'] = $vendor->get_shop_name() ? $vendor->get_shop_name() : $vendor->get_name();

        /**
         * Filter metabox template parameters.
         *
         * @since 3.16.0
         *
         * @param array      $params            Template parameters
         * @param WC_Product $product           Product object
         * @param Vendor     $vendor            Vendor object
         * @param array|null $rejection_details Rejection details if any
         */
        return (array) apply_filters( 'dokan_product_rejection_metabox_params', $params, $product, $vendor, $params['rejection_history'] );
    }

    /**
     * Display virtual status states for products.
     *
     * @since 3.16.0
     *
     * @param array   $post_states Array of post states
     * @param WP_Post $post        Post object
     *
     * @return array Modified array of post states
     */
    public function display_reject_state( array $post_states, $post ): array {
        if ( ! $post || ! $post instanceof WP_Post ) {
            return $post_states;
        }
        
        global $wp_query;

        if ( ! $wp_query->is_main_query() || 'product' !== $post->post_type || ! empty( $wp_query->query['post_status'] ) ) {
            return $post_states;
        }

        $product = wc_get_product( $post );
        if ( ! $product instanceof WC_Product ) {
            return $post_states;
        }

        if ( $this->product_status_service->is_resubmitted( $product ) ) {
            $post_states['resubmitted'] = sprintf(
                '<span class="resubmitted-status" data-resubmitted-at="%2$s">%1$s</span>',
                esc_html__( 'Resubmitted', 'dokan' ),
                esc_attr( dokan_format_date( $this->product_status_service->get_resubmission_date( $product ) ) )
            );
        }

        if ( $this->product_status_service->is_rejected( $product ) ) {
            $post_states['rejected'] = sprintf(
                '<span class="rejected-status">%s</span>',
                esc_html__( 'Rejected', 'dokan' )
            );
        }

        return $post_states;
    }

    /**
     * Add reject action in row actions.
     *
     * @since 3.16.0
     *
     * @param array   $actions Array of row action links
     * @param WP_Post $post    Post object
     *
     * @return array Modified array of action links
     */
    public function add_reject_action( array $actions, WP_Post $post ): array {
        if ( 'product' !== $post->post_type || ! current_user_can( 'manage_woocommerce' ) ) {
            return $actions;
        }

        $product = wc_get_product( $post );
        if ( ! $product instanceof WC_Product ||
            ! $this->product_status_service->is_allowed_for_rejection( $product ) ||
            ! $this->product_status_service->is_pending( $product ) ) {
            return $actions;
        }

        $actions['reject'] = sprintf(
            '<a href="#" class="reject-product" data-product-id="%d" data-submission-type="%s">%s</a>',
            $post->ID,
            $this->product_status_service->is_resubmitted( $product ) ? 'resubmission' : 'new',
            esc_html__( 'Reject', 'dokan' )
        );

        return $actions;
    }

    /**
     * Add status filter dropdown to products list.
     *
     * @since 3.16.0
     *
     * @return void
     */
    public function add_status_filter(): void {
        global $typenow;

        if ( 'product' !== $typenow ) {
            return;
        }

        $current_status = isset( $_GET['product_approval_status'] ) ? sanitize_key( $_GET['product_approval_status'] ) : ''; // phpcs:ignore WordPress.Security

        $statuses = [
            ''               => __( 'All States', 'dokan' ),
            'new_submission' => __( 'Pending', 'dokan' ),
            // product status is pending and rejection metadata is not exists
            'resubmitted'    => __( 'Resubmitted', 'dokan' ),
            // product status is pending, but it was rejected, and vendor request to review again
            'rejected'       => __( 'Rejected', 'dokan' ),
            // product status is rejected
        ];

        /**
         * Filter available status options in dropdown.
         *
         * @since 3.16.0
         *
         * @param array $statuses Array of status keys and labels
         */
        $statuses = (array) apply_filters( 'dokan_product_approval_status_options', $statuses );

        dokan_get_template_part(
            'product-rejection/admin/filters',
            '',
            [
                'pro'      => true,
                'current'  => $current_status,
                'statuses' => $statuses,
            ]
        );
    }

    /**
     * Filter products by approval status.
     *
     * @since 3.16.0
     *
     * @param WP_Query $query Query object
     *
     * @return void
     */
    public function filter_products_by_status( WP_Query $query ): void {
        global $pagenow;

        if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() ) {
            return;
        }

        $status = isset( $_GET['product_approval_status'] ) ? sanitize_key( $_GET['product_approval_status'] ) : ''; // phpcs:ignore WordPress.Security
        if ( empty( $status ) ) {
            return;
        }

        $meta_query = ! empty( $query->get( 'meta_query' ) ) ? $query->get( 'meta_query' ) : [];

        switch ( $status ) {
            case 'new_submission':
                $query->set( 'post_status', ProductStatusService::STATUS_PENDING );
                $meta_query[] = [
                    'relation' => 'AND',
                    [
                        'key'     => ProductStatusService::META_RESUBMITTED_DATE,
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key'     => ProductStatusService::META_REJECTION_HISTORY,
                        'compare' => 'NOT EXISTS',
                    ],
                ];
                break;

            case 'resubmitted':
                $query->set( 'post_status', ProductStatusService::STATUS_PENDING );
                $meta_query[] = [
                    'key'     => ProductStatusService::META_RESUBMITTED_DATE,
                    'compare' => 'EXISTS',
                ];
                break;

            case 'rejected':
                $query->set( 'post_status', ProductStatusService::STATUS_REJECTED );
                break;
        }

        if ( ! empty( $meta_query ) ) {
            $query->set( 'meta_query', $meta_query );
        }
    }

    /**
     * Process product rejection.
     *
     * @since 4.0.0
     *
     * @param WC_Product $product Product object
     *
     * @return void
     */
    public function on_product_reject( WC_Product $product ): void {
        try {
            // Clear resubmission metadata
            $this->product_status_service->clear_resubmission_meta( $product );

            /**
             * Action after clearing resubmission meta.
             *
             * @since 4.0.0
             *
             * @param WC_Product $product Product object
             */
            do_action( 'dokan_after_clear_resubmission_meta', $product );
        } catch ( Throwable $e ) {
            dokan_log(
                sprintf(
                    'Failed to clear resubmission meta for product #%d: %s',
                    $product->get_id(),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Process product status transitions.
     *
     * Handles cleanup of rejection metadata when a product moves from rejected to published status.
     * Only processes when old status is exactly 'rejected' and new status is exactly 'publish'.
     *
     * Truth table for status transition processing:
     * +----------------+-------------+--------+------------------+
     * | Old Status     | New Status  | Result | Action           |
     * +----------------+-------------+--------+------------------+
     * | 'rejected'     | 'publish'   | TRUE   | Process cleanup  |
     * | 'rejected'     | 'draft'     | FALSE  | Skip             |
     * | 'rejected'     | 'pending'   | FALSE  | Skip             |
     * | 'draft'        | 'publish'   | FALSE  | Skip             |
     * | 'pending'      | 'publish'   | TRUE   | Process cleanup  |
     * | 'draft'        | 'pending'   | FALSE  | Skip             |
     * +----------------+-------------+--------+------------------+
     *
     * @since 3.16.0
     *
     * @param string  $new_status New post status
     * @param string  $old_status Old post status
     * @param WP_Post $post       Post object
     *
     * @return void
     */
    public function on_all_status_transitions( string $new_status, string $old_status, WP_Post $post ): void {
        try {
            // Skip if not a product
            if ( 'product' !== $post->post_type ) {
                return;
            }

            // Skip if not a valid status transition
            $allowed_statues = array( ProductStatusService::STATUS_PENDING, ProductStatusService::STATUS_REJECTED );

            // Only process when old status is reject and new status is published
            if ( in_array( $old_status, $allowed_statues, true ) && $new_status === 'publish' ) {
                // Get and validate product
                $product = wc_get_product( $post->ID );
                if ( ! $product instanceof WC_Product ) {
                    throw new RuntimeException( sprintf( 'Invalid product with ID: %d', $post->ID ) );
                }

                // Clear rejection metadata
                $this->product_status_service->clear_rejection_meta( $product );

                /**
                 * Action after clearing rejection meta.
                 *
                 * @since 3.16.0
                 *
                 * @param WC_Product $product Product object
                 * @param string     $status  New product status
                 */
                do_action( 'dokan_product_rejection_meta_cleared', $product, $post->post_status );
            }
        } catch ( Throwable $e ) {
            dokan_log(
                sprintf(
                    'Failed to clear rejection meta for product #%d: %s',
                    $post->ID,
                    $e->getMessage()
                )
            );
        }
    }
}
