<?php

namespace WeDevs\DokanPro\Refund;

use WeDevs\Dokan\Traits\ChainableContainer;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Hooks {
    use ChainableContainer;

    /**
     * Hooks related to Dokan Pro Refund
     *
     * @since 3.0.0
     *
     * @return void
     */
    public function __construct() {
        add_action( 'wp_ajax_dokan_refund_request', [ Ajax::class, 'dokan_refund_request' ] );
        add_action( 'wp_ajax_woocommerce_refund_line_items', [ Ajax::class, 'intercept_wc_ajax_request' ], 1 );
        add_action( 'dokan_pro_refund_approved', [ self::class, 'after_refund_approved' ] );
        add_action( 'dokan_refund_request_created', [ $this, 'add_order_note_on_refund_request_create' ], 1, 1 );

        new RefundCache();

        $this->container['non_dokan_auto_refund'] = ProcessAutomaticRefund::instance();
        $this->container['refunds'] = new AdminMenu();
    }

    /**
     * After refund approval hook
     *
     * @since 3.0.0
     *
     * @param \WeDevs\DokanPro\Refund\Refund $refund
     *
     * @return void
     */
    public static function after_refund_approved( $refund ) {
        $vendor       = dokan()->vendor->get( $refund->get_seller_id() );
        $vendor_email = $vendor->get_email();

        do_action( 'dokan_refund_processed_notification', $vendor_email, $refund->get_order_id(), 'approved', $refund->get_refund_amount(), $refund->get_refund_reason() );
    }



    /**
     * Add an Order note on refund request create.
     *
     * @since 3.4.2
     *
     * @param Refund $refund
     *
     * @return void
     */
    public function add_order_note_on_refund_request_create( $refund ) {
        if ( ! ProcessAutomaticRefund::instance()->is_auto_refundable_gateway( $refund ) ) {
            return;
        }
        $order = wc_get_order( $refund->get_order_id() );

        if ( ! $order ) {
            return;
        }
        $order->add_order_note(
            // translators: 1:Refund request ID, 2: Formatted Refund amount, 3: Refund reasons.
            sprintf( __( 'A new request for refund is placed for the admin approval - Refund request ID: #%1$s - Refund Amount: %2$s - Reason: %3$s', 'dokan' ), $refund->get_id(), wc_price( $refund->get_refund_amount() ), $refund->get_refund_reason() )
        );
    }
}
