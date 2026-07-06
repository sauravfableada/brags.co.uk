<?php

namespace WeDevs\DokanPro\ProductRejection;

use RuntimeException;
use Throwable;
use WC_Product;
use WP_Error;

/**
 * Product Rejection ProductStatusService Manager
 *
 * @since 3.16.0
 */
class ProductStatusService {
    /**
     * Meta key for storing rejection reason.
     *
     * @since 3.16.0
     *
     * @var string
     */
    public const META_REJECTION_HISTORY = '_dokan_rejection_history';

    /**
     * Meta key for tracking resubmission time.
     *
     * @since 3.16.0
     *
     * @var string
     */
    public const META_RESUBMITTED_DATE = '_dokan_resubmitted_date';

    /**
     * Rejection status.
     *
     * @since 3.16.0
     *
     * @var string
     */
    public const STATUS_REJECTED = 'reject';

    /**
     * Pending status.
     *
     * @since 3.16.0
     *
     * @var string
     */
    public const STATUS_PENDING = 'pending';

    public function is_allowed_for_rejection( WC_Product $product ): bool {
        // Get allowed product types.
        $allowed_types = dokan_get_product_types();

        /**
         * Filter allowed product types for rejection.
         *
         * @since 3.16.0
         *
         * @param array      $allowed_types Allowed product types
         * @param WC_Product $product       Product object
         */
        $allowed_types = (array) apply_filters( 'dokan_product_rejection_allowed_product_types', array_keys( $allowed_types ), $product );

        return in_array( $product->get_type(), $allowed_types, true );
    }

    /**
     * Check if a product is eligible for rejection metabox.
     *
     * @since 3.16.0
     *
     * @param WC_Product $product Product object
     *
     * @return bool True if product is eligible, false otherwise
     */
    public function is_metabox_eligible_for_product( WC_Product $product ): bool {
        $valid_statuses = [
            self::STATUS_PENDING,
            self::STATUS_REJECTED,
        ];

        /**
         * Filter valid post statuses for showing metabox.
         *
         * @since 3.16.0
         *
         * @param array      $valid_statuses Valid post statuses
         * @param WC_Product $product        Product object
         */
        $valid_statuses = (array) apply_filters( 'dokan_product_rejection_metabox_statuses', $valid_statuses, $product );

        return in_array( $product->get_status(), $valid_statuses, true );
    }

    /**
     * Check if a product is in its initial pending state.
     *
     * @since 3.16.0
     *
     * @param WC_Product $product Product ID or product object
     *
     * @return bool True if product is new and pending, false otherwise
     */
    public function is_pending( WC_Product $product ): bool {
        $is_pending = static::STATUS_PENDING === $product->get_status();

        /**
         * Filter whether a pending product is considered new
         *
         * Allows customizing the logic for determining if a product
         * is a first-time submission.
         *
         * @since 3.16.0
         *
         * @param bool       $is_new_submission Whether product is a new submission
         * @param WC_Product $product           Product object
         */
        return apply_filters( 'dokan_product_is_pending_new', $is_pending, $product );
    }

    /**
     * Check if a product is rejected.
     *
     * @since 3.16.0
     *
     * @param int|WC_Product $product Product ID or product object
     *
     * @return bool True if product is rejected, false otherwise
     */
    public function is_rejected( $product ): bool {
        if ( is_numeric( $product ) ) {
            $product = wc_get_product( $product );
        }

        if ( ! $product instanceof WC_Product ) {
            return false;
        }

        $is_rejected = static::STATUS_REJECTED === $product->get_status();

        /**
         * Filter whether a product is considered rejected
         *
         * Allows customizing the logic for determining if a product
         * is in a rejected state.
         *
         * @since 3.16.0
         *
         * @param bool       $is_rejected Whether product is rejected
         * @param WC_Product $product     Product object
         */
        return apply_filters( 'dokan_product_is_rejected', $is_rejected, $product );
    }

    /**
     * Check if product is resubmitted
     *
     * @since 3.16.0
     *
     * @param WC_Product $product Product to check
     *
     * @return bool Whether product is resubmitted
     */
    public function is_resubmitted( WC_Product $product ): bool {
        $is_resubmitted = ! empty( $product->get_meta( static::META_RESUBMITTED_DATE ) );

        /**
         * Filter whether a product is considered resubmitted
         *
         * Allows customizing the logic for determining if a product
         * was resubmitted after rejection.
         *
         * @since 3.16.0
         *
         * @param bool       $is_resubmitted Whether product was resubmitted
         * @param WC_Product $product        Product object
         */
        return apply_filters( 'dokan_product_was_resubmitted', $is_resubmitted, $product );
    }

    /**
     * Check if product has rejection history
     *
     * @since 3.16.0
     *
     * @param WC_Product $product Product to check
     *
     * @return bool Whether product has rejection history
     */
    public function is_rejection_history_exists( WC_Product $product ): bool {
        $rejection_history = (array) $product->get_meta( static::META_REJECTION_HISTORY );

        return ! empty( $rejection_history );
    }

    /**
     * Saves product rejection metadata and updates product status.
     *
     * @since 3.16.0
     *
     * @param WC_Product $product    Product being rejected
     * @param string     $reason     Rejection reason
     * @param array      $extra_data Optional additional metadata
     *
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function save_rejection_meta( WC_Product $product, string $reason, array $extra_data = [] ) {
        try {
            $admin_id = get_current_user_id();
            if ( ! $admin_id || empty( $reason ) ) {
                throw new RuntimeException( __( 'Invalid rejection parameters', 'dokan' ) );
            }

            // Prepare new rejection data
            $new_rejection = wp_parse_args(
                $extra_data,
                [
                    'reason'   => wp_strip_all_tags( $reason ),
                    'date'     => get_gmt_from_date( dokan_format_datetime() ),
                    'admin_id' => absint( $admin_id ),
                ]
            );

            /**
             * Filters the rejection data before saving.
             *
             * @since 3.16.0
             *
             * @param array      $new_rejection New rejection details
             * @param WC_Product $product       Product being rejected
             */
            $new_rejection = apply_filters( 'dokan_product_rejection_data', $new_rejection, $product );

            // Get existing rejection history
            $rejection_history = (array) $product->get_meta( static::META_REJECTION_HISTORY );

            // Append new rejection to history
            $rejection_history[] = $new_rejection;

            /**
             * Action before saving rejection metadata.
             *
             * @since 3.16.0
             *
             * @param WC_Product $product           Product being rejected
             * @param array      $rejection_history Complete rejection history
             * @param array      $new_rejection     New rejection data being added
             */
            do_action( 'dokan_before_save_rejection', $product, $rejection_history, $new_rejection );

            // Update product metadata and status
            $product->update_meta_data( static::META_REJECTION_HISTORY, $rejection_history );
            $product->set_status( static::STATUS_REJECTED );
            $product->save();

            /**
             * Action after saving rejection metadata.
             *
             * @since 3.16.0
             *
             * @param WC_Product $product           Product being rejected
             * @param array      $rejection_history Complete rejection history
             * @param array      $new_rejection     New rejection data that was added
             */
            do_action( 'dokan_after_save_rejection', $product, $rejection_history, $new_rejection );

            return true;
        } catch ( Throwable $e ) {
            return new WP_Error( 'save_rejection_failed', $e->getMessage() );
        }
    }

    /**
     * Gets complete rejection history and details for a product.
     *
     * @since 3.16.0
     *
     * @param int|WC_Product $product_id Product ID or product object
     *
     * @return array|WP_Error Array containing rejection history and details or WP_Error on failure
     */
    public function get_rejection_details( $product_id ) {
        try {
            // Get product object if ID provided
            if ( is_numeric( $product_id ) ) {
                $product = wc_get_product( $product_id );
            } else {
                $product = $product_id;
            }

            // Validate product object
            if ( ! $product instanceof WC_Product ) {
                throw new RuntimeException( __( 'Invalid product ID', 'dokan' ) );
            }

            // Verify product is in rejected state
            if ( ! $this->is_rejected( $product ) && ! $this->is_resubmitted( $product ) ) {
                throw new RuntimeException( __( 'Product is not rejected', 'dokan' ) );
            }

            // Get rejection history
            $rejection_history = (array) $product->get_meta( static::META_REJECTION_HISTORY );
            $rejection_history = array_filter( $rejection_history );
            if ( empty( $rejection_history ) ) {
                throw new RuntimeException( __( 'No rejection history found', 'dokan' ) );
            }

            // Get latest rejection details
            $current_rejection = end( $rejection_history );

            $rejection_details = [
                'history'     => $rejection_history,
                'current'     => $current_rejection,
                'latest_date' => $current_rejection['date'] ?? '',
                'status'      => $product->get_status(),
            ];

            /**
             * Filters the rejection details before return.
             *
             * @since 3.16.0
             *
             * @param array      $rejection_details Complete rejection details
             * @param WC_Product $product           Product object
             * @param array      $rejection_history Complete rejection history
             */
            return apply_filters( 'dokan_product_rejection_details', $rejection_details, $product, $rejection_history );
        } catch ( Throwable $e ) {
            return new WP_Error( 'get_rejection_details_failed', $e->getMessage() );
        }
    }

    public function get_rejection_date( int $product_id, bool $for_admin = false ): string {
        $histories = $this->get_rejection_details( $product_id );

        if ( is_wp_error( $histories ) || empty( $histories['latest_date'] ) ) {
            return '';
        }

        /**
         * Filter rejection date before display
         *
         * @since 3.16.0
         *
         * @param string $date      Formatted date
         * @param array  $details   Rejection details
         * @param bool   $for_admin Whether for admin display
         */
        return apply_filters( 'dokan_rejection_date', $histories['latest_date'], $histories, $for_admin );
    }

    /**
     * Get rejection message for display
     *
     * @since 3.16.0
     *
     * @param int  $product_id Product ID
     * @param bool $for_admin  Whether formatting for admin display
     *
     * @return string Formatted rejection message
     */
    public function get_rejection_message( int $product_id, bool $for_admin = false ): string {
        $histories = $this->get_rejection_details( $product_id );

        if ( is_wp_error( $histories ) || empty( $histories['current']['reason'] ) ) {
            return '';
        }

        /**
         * Filter rejection message before display
         *
         * @since 3.16.0
         *
         * @param string $message   Formatted message
         * @param array  $details   Rejection details
         * @param bool   $for_admin Whether for admin display
         */
        return apply_filters( 'dokan_rejection_message', $histories['current']['reason'], $histories, $for_admin );
    }

    /**
     * Track product resubmission timestamp
     *
     * @since 3.16.0
     *
     * @param WC_Product $product Product to track resubmission for
     *
     * @return bool True on success, false on failure
     */
    public function save_resubmission_date( WC_Product $product ): bool {
        try {
            /**
             * Filter the resubmission date before saving
             *
             * @since 3.16.0
             *
             * @param string     $resubmitted_date Resubmission timestamp
             * @param WC_Product $product          Product object
             */
            $resubmitted_date = apply_filters( 'dokan_product_resubmitted_date', get_gmt_from_date( dokan_format_datetime() ), $product );

            $product->update_meta_data( static::META_RESUBMITTED_DATE, $resubmitted_date );
            $product->save();

            return true;
        } catch ( Throwable $e ) {
            return false;
        }
    }

    /**
     * Get product resubmission time
     *
     * @since 3.16.0
     *
     * @param WC_Product $product Product to check
     *
     * @return mixed Resubmission timestamp or empty string if not resubmitted
     */
    public function get_resubmission_date( WC_Product $product ) {
        return $product->get_meta( static::META_RESUBMITTED_DATE );
    }

    /**
     * Clear rejection metadata
     *
     * @since 3.16.0
     *
     * @param WC_Product $product Product to clear
     *
     * @return void
     */
    public function clear_rejection_meta( WC_Product $product ) {
        /**
         * Action before clearing rejection meta
         *
         * @since 3.16.0
         *
         * @param WC_Product $product Product being cleared
         */
        do_action( 'dokan_before_clear_rejection', $product );

        // Clear current rejection meta
        $product->delete_meta_data( static::META_REJECTION_HISTORY );
        $product->delete_meta_data( static::META_RESUBMITTED_DATE );

        $product->save();

        /**
         * Action after clearing rejection meta
         *
         * @since 3.16.0
         *
         * @param WC_Product $product Product being cleared
         */
        do_action( 'dokan_after_clear_rejection', $product );
    }

    /**
     * Clear resubmission metadata
     *
     * @since 4.0.0
     *
     * @param WC_Product $product Product to clear
     *
     * @return void
     */
    public function clear_resubmission_meta( WC_Product $product ) {
        /**
         * Action before clearing resubmission meta
         *
         * @since 4.0.0
         *
         * @param WC_Product $product Product being cleared
         */
        do_action( 'dokan_before_clear_resubmission', $product );

        // Clear current resubmission meta
        $product->delete_meta_data( static::META_RESUBMITTED_DATE );

        $product->save();

        /**
         * Action after clearing resubmission meta
         *
         * @since 4.0.0
         *
         * @param WC_Product $product Product being cleared
         */
        do_action( 'dokan_after_clear_resubmission', $product );
    }
}
