<?php

namespace WeDevs\DokanPro\ProductRejection;

use Exception;
use Throwable;
use WC_Product;

/**
 * Product ProductStatusService Rollback Handler Class
 *
 * @since 3.16.0
 */
class StatusRollback {
    /**
     * Queue group identifier
     *
     * @var string
     */
    protected const QUEUE_GROUP = 'dokan-product-status-rollback';

    /**
     * Batch size for processing
     *
     * @var int
     */
    protected const BATCH_SIZE = 10;

    /**
     * Constructor.
     *
     * @since 3.16.0
     */
    public function __construct() {
        $this->register_hooks();
    }

    /**
     * Set up necessary hooks
     *
     * @since 3.16.0
     *
     * @return void
     */
    protected function register_hooks(): void {
        add_action( 'dokan_rollback_product_status_draft_to_reject_schedule', array( $this, 'process_draft_operation' ) );
    }

    /**
     * Handle plugin activation
     *
     * Rolls back draft products with previous rejection status
     *
     * @since 3.16.0
     *
     * @return void
     */
    public static function rollback_on_activate(): void {
        try {
            // Register the rollback task
            WC()->queue()->add( 'dokan_rollback_product_status_draft_to_reject_schedule', array(), self::QUEUE_GROUP );

            dokan_log( 'Product status rollback schedule initiated.' );
        } catch ( Throwable $e ) {
            dokan_log(
                sprintf(
                    'Error initiating product status rollback: %s',
                    $e->getMessage()
                ),
                'error'
            );
        }
    }

    /**
     * Handle plugin deactivation
     *
     * @since 3.16.0
     *
     * @return void
     */
    public static function rollback_on_deactivate(): void {
        try {
            // Schedule the first batch
            WC()->queue()->add( 'dokan_rollback_product_status_reject_to_draft_schedule', array(), self::QUEUE_GROUP );

            dokan_log( 'Product status rollback schedule initiated.' );
        } catch ( Throwable $e ) {
            dokan_log(
                sprintf(
                    'Error initiating product status rollback: %s',
                    $e->getMessage()
                ),
                'error'
            );
        }
    }

    /**
     * Process draft to reject batch operation
     *
     * @since 3.16.0
     *
     * @return void
     */
    public function process_draft_operation(): void {
        global $wpdb;

        try {
            // Get draft products with previous rejection status
            $products = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT p.ID
                    FROM $wpdb->posts p
                    INNER JOIN $wpdb->postmeta pm ON p.ID = pm.post_id
                    WHERE p.post_type = 'product'
                    AND p.post_status = %s
                    AND pm.meta_key = %s
                    ORDER BY p.ID
                    LIMIT %d",
                    'draft',
                    '_dokan_previous_status',
                    self::BATCH_SIZE
                )
            );

            if ( empty( $products ) ) {
                return;
            }

            $processed = 0;
            foreach ( $products as $product_id ) {
                try {
                    $product = wc_get_product( $product_id );
                    if ( ! $product instanceof WC_Product ) {
                        throw new Exception( 'Invalid product' );
                    }

                    /**
                     * Filter the target status for product rollback
                     *
                     * @since 3.16.0
                     *
                     * @param string     $target_status Target status
                     * @param WC_Product $product       Product object
                     */
                    $target_status = apply_filters( 'dokan_product_rollback_status', 'reject', $product );

                    /**
                     * Action before product rollback
                     *
                     * @since 3.16.0
                     *
                     * @param WC_Product $product       Product object
                     * @param string     $target_status Target status
                     */
                    do_action( 'dokan_before_product_rollback', $product, $target_status );

                    // Track previous status
                    $product->add_meta_data( '_dokan_previous_status', $product->get_status(), true );
                    $product->set_status( $target_status );
                    $product->save();

                    /**
                     * Action after product status rollback
                     *
                     * @since 3.16.0
                     *
                     * @param WC_Product $product       Product object
                     * @param string     $target_status Target status
                     */
                    do_action( 'dokan_after_product_status_rollback', $product, $target_status );

                    ++$processed;
                } catch ( Throwable $e ) {
                    dokan_log(
                        sprintf(
                            'Error rolling back product #%d: %s',
                            $product_id,
                            $e->getMessage()
                        ),
                        'error'
                    );
                }
            }

            // Schedule next batch if needed
            WC()->queue()->add( 'dokan_rollback_product_status_draft_to_reject_schedule', array(), self::QUEUE_GROUP );
        } catch ( Throwable $e ) {
            dokan_log(
                sprintf(
                    'Error processing draft->reject: %s',
                    $e->getMessage()
                ),
                'error'
            );
        }
    }
}
