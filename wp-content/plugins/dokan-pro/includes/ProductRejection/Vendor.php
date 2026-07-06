<?php

namespace WeDevs\DokanPro\ProductRejection;

use Throwable;
use WC_Product;

/**
 * Class Vendor
 *
 * @since 3.16.0
 */
class Vendor {
    /**
     * ProductStatusService handler instance
     *
     * @since 3.16.0
     *
     * @var ProductStatusService
     */
    protected ProductStatusService $product_status_service;

    /**
     * Constructor for the Vendor class
     *
     * @since 3.16.0
     *
     * @param ProductStatusService $product_status_service ProductStatusService handler instance
     */
    public function __construct( ProductStatusService $product_status_service ) {
        $this->product_status_service = $product_status_service;
        $this->register_hooks();
    }

    /**
     * Register hooks
     *
     * @note  We intentionally left to show rejected products in the product widgets at the dashboard
     * @since 3.16.0
     *
     * @return void
     * @see   dokan-lite/includes/Dashboard/Templates/Dashboard.php:200
     */
    protected function register_hooks(): void {
        // Vendor dashboard integration
        add_filter( 'dokan_get_post_status', array( $this, 'add_post_status' ) );
        add_filter( 'dokan_get_product_status', array( $this, 'add_listing_post_statuses' ) );
        add_filter( 'dokan_product_listing_post_statuses', array( $this, 'add_listing_post_statuses' ) );
        add_filter( 'dokan_get_post_status_label_class', array( $this, 'add_post_status_label_class' ) );
        add_filter( 'dokan_post_edit_default_status', array( $this, 'add_edit_default_status' ), 1000, 2 );

        // Product single page
        add_action( 'dokan_product_edit_before_main', array( $this, 'show_rejection_message' ) );

        // Resubmission handling
        add_action( 'dokan_product_updated', array( $this, 'handle_resubmission' ) );
    }

    /**
     * Add rejected status to post status list
     *
     * @since 3.16.0
     *
     * @param array $statuses List of post statuses
     *
     * @return array Modified list of post statuses
     */
    public function add_post_status( array $statuses ): array {
        /**
         * Filter the post status text for rejected products
         *
         * @since 3.16.0
         *
         * @param string $status_text The status text to display
         */
        $statuses[ ProductStatusService::STATUS_REJECTED ] = apply_filters( 'dokan_product_rejected_status_text', __( 'Rejected', 'dokan' ) );

        return $statuses;
    }

    /**
     * Add rejected status label class
     *
     * @since 3.16.0
     *
     * @param array $classes Array of post status label classes
     *
     * @return array Modified array of label classes
     */
    public function add_post_status_label_class( array $classes ): array {
        /**
         * Filter the CSS class for rejected product status labels
         *
         * @since 3.16.0
         *
         * @param string $class The CSS class for rejected status
         */
        $classes[ ProductStatusService::STATUS_REJECTED ] = apply_filters( 'dokan_product_rejected_label_class', 'dokan-label-danger' );

        return $classes;
    }

    /**
     * Add rejected status as default status for product edit page
     *
     * @since 3.16.0
     *
     * @param string     $status  Current product status
     * @param WC_Product $product Product object
     *
     * @return string Modified product status
     */
    public function add_edit_default_status( string $status, WC_Product $product ): string {
        if ( $this->product_status_service->is_rejected( $product ) ) {
            /**
             * Filter the default status for rejected products in edit mode
             *
             * @since 3.16.0
             *
             * @param string     $status  Default status for rejected products
             * @param WC_Product $product Product object being edited
             */
            return apply_filters( 'dokan_product_rejected_edit_status', ProductStatusService::STATUS_PENDING, $product );
        }

        return $status;
    }

    /**
     * Add rejected status to product listing post statuses
     *
     * @since 3.16.0
     *
     * @param array $post_statuses List of post statuses
     *
     * @return array Modified list of post statuses
     */
    public function add_listing_post_statuses( array $post_statuses ): array {
        $post_statuses[] = ProductStatusService::STATUS_REJECTED;

        return $post_statuses;
    }

    /**
     * Show rejection message on product edit page
     *
     * @since 3.16.0
     *
     * @return void
     */
    public function show_rejection_message(): void {
        if ( ! isset( $_GET['product_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }

        $product_id = absint( $_GET['product_id'] ); // phpcs:ignore WordPress.Security.NonceVerification
        if ( ! $this->product_status_service->is_rejected( $product_id ) ) {
            return;
        }

        $message = $this->product_status_service->get_rejection_message( $product_id );
        if ( empty( $message ) ) {
            return;
        }

        /**
         * Filter the template parameters for rejection message
         *
         * @since 3.16.0
         *
         * @param array $params     Template parameters
         * @param int   $product_id Product ID
         */
        $template_params = apply_filters(
            'dokan_product_rejection_message_params',
            array(
                'pro'               => true,
                'product_id'        => $product_id,
                'rejection_message' => $message,
            ),
            $product_id
        );

        dokan_get_template_part( 'product-rejection/vendor/notice', '', $template_params );
    }

    /**
     * Handle product resubmission event
     *
     * @since 3.16.0
     *
     * @param int $product_id Product ID being resubmitted
     *
     * @return void
     */
    public function handle_resubmission( int $product_id ): void {
        try {
            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                return;
            }

            // Only handle products being the product author
            if ( ! dokan_is_product_author( $product_id ) ) {
                return;
            }

            // Validate product type.
            if ( ! $this->product_status_service->is_allowed_for_rejection( $product ) ||
                ! $this->product_status_service->is_pending( $product ) ||
                ! $this->product_status_service->is_rejection_history_exists( $product ) ) {
                return;
            }

            // Track resubmission event
            $this->product_status_service->save_resubmission_date( $product );

            /**
             * Trigger product resubmission action
             *
             * This action will be handled by the Manager class to process
             * the resubmission, clear meta data, and track the event.
             *
             * @since 3.16.0
             *
             * @param int        $product_id Product ID
             * @param WC_Product $product    Product object
             */
            do_action( 'dokan_product_resubmitted', $product_id, $product );
        } catch ( Throwable $e ) {
            dokan_log( sprintf( 'Error handling resubmission for product #%d: %s', $product_id, $e->getMessage() ) );
        }
    }
}
