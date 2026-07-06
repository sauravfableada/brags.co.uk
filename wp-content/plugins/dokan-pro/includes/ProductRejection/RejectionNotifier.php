<?php

namespace WeDevs\DokanPro\ProductRejection;

use Exception;
use Throwable;
use WC_Product;
use WeDevs\DokanPro\Emails\ProductRejected;

/**
 * Product Rejection Email Processing Class
 *
 * @since 3.16.0
 */
class RejectionNotifier {

    /**
     * Constructor for the product rejection email handler
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
        add_action( 'dokan_after_product_reject', [ $this, 'schedule_email' ], 10, 3 );
        add_action( 'dokan_product_rejection_send_email', [ $this, 'trigger_email' ], 10, 3 );
    }

    /**
     * Schedule rejection email for a product
     *
     * @since 3.16.0
     *
     * @param WC_Product $product Product that was rejected
     * @param string     $date    Rejection reason date
     * @param string     $reason  Rejection reason provided by admin
     *
     * @return void
     */
    public function schedule_email( WC_Product $product, string $date, string $reason ): void {
        try {
            // Check if email should be scheduled
            $should_schedule = $this->should_schedule_email( $product );
            if ( ! $should_schedule ) {
                $this->trigger_email( $product->get_id(), $date, $reason );

                return;
            }

            // Cancel any existing scheduled emails
            $this->cancel_existing_schedule( $product->get_id() );

            // Calculate schedule time
            $delay         = $this->get_email_delay( $product );
            $schedule_time = time() + $delay;

            // Prepare schedule parameters
            $schedule_params = [
                'product_id' => $product->get_id(),
                'date'       => $date,
                'reason'     => $reason,
            ];

            /**
             * Filter email schedule parameters
             *
             * @since 3.16.0
             *
             * @param array      $schedule_params Schedule parameters
             * @param WC_Product $product         Product being rejected
             * @param string     $date            Rejection date
             * @param string     $reason          Rejection reason
             */
            $schedule_params = apply_filters( 'dokan_rejection_email_schedule_params', $schedule_params, $product, $date, $reason );

            // Schedule the email
            WC()->queue()->schedule_single( $schedule_time, 'dokan_product_rejection_send_email', $schedule_params, 'dokan-product-rejection' );

            /**
             * Action after scheduling rejection email
             *
             * @since 3.16.0
             *
             * @param WC_Product $product  Product being rejected
             * @param string     $reason   Rejection reason
             * @param array      $schedule Schedule details
             */
            do_action(
                'dokan_after_schedule_rejection_email',
                $product,
                $reason,
                [
                    'time'   => $schedule_time,
                    'delay'  => $delay,
                    'params' => $schedule_params,
                ]
            );

            dokan_log( sprintf( 'Rejection email scheduled for product #%d', $product->get_id() ) );
        } catch ( Throwable $e ) {
            dokan_log( sprintf( 'Error scheduling rejection email: %s', $e->getMessage() ), 'error' );
        }
    }

    /**
     * Check if email should be scheduled
     *
     * @since 3.16.0
     *
     * @param WC_Product $product Product being rejected
     *
     * @return bool Whether to schedule the email
     */
    protected function should_schedule_email( WC_Product $product ): bool {
        $is_disable_cron = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;

        /**
         * Filter whether to schedule email sending
         *
         * @since 3.16.0
         *
         * @param bool       $should_schedule Default true
         * @param WC_Product $product         Product being rejected
         */
        return apply_filters( 'dokan_product_rejection_should_schedule', ! $is_disable_cron, $product );
    }

    /**
     * Get email delay time in seconds
     *
     * @since 3.16.0
     *
     * @param WC_Product $product Product being rejected
     *
     * @return int Delay time in seconds
     */
    protected function get_email_delay( WC_Product $product ): int {
        /**
         * Filter email delay time
         *
         * @since 3.16.0
         *
         * @param int        $delay   Default delay time in seconds
         * @param WC_Product $product Product being rejected
         */
        return (int) apply_filters( 'dokan_product_rejection_email_delay', 10, $product );
    }

    /**
     * Cancel existing scheduled emails for a product
     *
     * @since 3.16.0
     *
     * @param int $product_id Product ID
     *
     * @return void
     */
    protected function cancel_existing_schedule( int $product_id ): void {
        try {
            WC()->queue()->cancel_all( 'dokan_product_rejection_send_email', [ 'product_id' => $product_id ], 'dokan-product-rejection' );

            /**
             * Action after cancelling scheduled emails
             *
             * @since 3.16.0
             *
             * @param int $product_id Product ID
             */
            do_action( 'dokan_after_cancel_rejection_email', $product_id );
        } catch ( Throwable $e ) {
            dokan_log( sprintf( 'Error cancelling scheduled emails: %s', $e->getMessage() ), 'error' );
        }
    }

    /**
     * Trigger the rejection email
     *
     * @since 3.16.0
     *
     * @param int    $product_id Product ID that was rejected
     * @param string $date       Rejection date
     * @param string $reason     Rejection reason provided by admin
     *
     * @return bool Whether the email was sent successfully
     */
    public function trigger_email( int $product_id, string $date, string $reason ): bool {
        try {
            /**
             * Action before triggering rejection email
             *
             * @since 3.16.0
             *
             * @param int    $product_id Product ID
             * @param string $reason     Rejection reason
             */
            do_action( 'dokan_before_trigger_rejection_email', $product_id, $reason );

            // Get email instance
            $email = WC()->mailer()->emails['Dokan_Product_Rejected'] ?? null;

            if ( ! $email instanceof ProductRejected ) {
                throw new Exception( 'Product rejection email class not found' );
            }

            // Sanitize and validate reason
            $reason = wp_strip_all_tags( $reason );

            // Trigger the email
            $sent = $email->trigger( $product_id, $date, $reason );

            if ( ! $sent ) {
                throw new Exception( 'Email sending failed' );
            }

            /**
             * Action after triggering rejection email
             *
             * @since 3.16.0
             *
             * @param int    $product_id Product ID
             * @param string $reason     Rejection reason
             * @param bool   $sent       Whether email was sent
             */
            do_action( 'dokan_after_trigger_rejection_email', $product_id, $date, $reason, $sent );

            dokan_log( sprintf( 'Rejection email sent successfully for product #%d', $product_id ) );

            return true;
        } catch ( Throwable $e ) {
            dokan_log( sprintf( 'Email sending failed for product #%d: %s', $product_id, $e->getMessage() ), 'error' );

            return false;
        }
    }
}
